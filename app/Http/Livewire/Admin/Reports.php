<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RiskLog;
use App\Models\User;
use App\Support\CsvSafe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class Reports extends Component
{
    use EnsuresAdmin;

    public $startDate;
    public $endDate;
    public $selectedUser = '';
    public $selectedCategory = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
        $this->endDate   = Carbon::now()->endOfWeek()->format('Y-m-d');
    }

    private function rangeIsValid(): bool
    {
        $validator = Validator::make(
            ['startDate' => $this->startDate, 'endDate' => $this->endDate],
            [
                'startDate' => 'required|date',
                'endDate'   => 'required|date|after_or_equal:startDate',
            ],
            [
                'startDate.required'     => 'Pick a start date.',
                'endDate.required'       => 'Pick an end date.',
                'endDate.after_or_equal' => 'End date must be on or after the start date.',
            ]
        );

        $this->resetErrorBag(['startDate', 'endDate']);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            return false;
        }

        return true;
    }

    private function buildExpenseQuery()
    {
        return Expense::whereBetween('transaction_date', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->when(filled($this->selectedUser), function ($q) {
                $q->where('user_id', $this->selectedUser);
            })
            ->when(filled($this->selectedCategory), function ($q) {
                $q->where('expense_category_id', $this->selectedCategory);
            });
    }

    private function buildRiskLogQuery()
    {
        return RiskLog::whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->when(filled($this->selectedUser), function ($q) {
                $q->where('user_id', $this->selectedUser);
            });
    }

    // Exports contain student financial data, so every one leaves an audit entry.
    private function logExport(string $format): void
    {
        $filters = [];

        if (filled($this->selectedUser)) {
            $filters[] = 'user: ' . (User::find($this->selectedUser)->name ?? "ID {$this->selectedUser}");
        }

        if (filled($this->selectedCategory)) {
            $filters[] = 'category: ' . (ExpenseCategory::find($this->selectedCategory)->name ?? "ID {$this->selectedCategory}");
        }

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'report_exported',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Exported {$format} report ({$this->startDate} to {$this->endDate}"
                . (!empty($filters) ? ', ' . implode(', ', $filters) : ', all students') . ')',
        ]);
    }

    public function exportCsv()
    {
        if (!$this->rangeIsValid()) {
            return;
        }

        $query = $this->buildExpenseQuery()
            ->with(['user', 'category'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        $appName = AppSetting::current()->application_name ?? 'BudgetWise';

        $this->logExport('CSV');

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'User', 'Category', 'Item', 'Amount']);

            // Chunked so a large range doesn't load every expense into memory.
            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $expense) {
                    fputcsv($handle, [
                        $expense->transaction_date->format('Y-m-d'),
                        CsvSafe::cell($expense->user->name ?? 'Unknown'),
                        CsvSafe::cell($expense->category->name ?? 'Uncategorized'),
                        CsvSafe::cell($expense->item_name),
                        $expense->amount,
                    ]);
                }
            });

            fclose($handle);
        }, Str::slug($appName) . '-report-' . now()->format('Ymd-His') . '.csv');
    }

    public function exportPdf()
    {
        if (!$this->rangeIsValid()) {
            return;
        }

        $expenses = $this->buildExpenseQuery()
            ->with(['user', 'category'])
            ->orderBy('transaction_date')
            ->get();

        $data = $this->reportSummary();

        $categoryBreakdown = $expenses
            ->groupBy(fn ($e) => $e->category->name ?? 'Uncategorized')
            ->map(function ($group, $name) {
                $sample = $group->first();

                return [
                    'name'  => $name,
                    'total' => $group->sum('amount'),
                    'count' => $group->count(),
                    'color' => ExpenseCategory::colorToHex($sample->category->color ?? null),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $categoryGrandTotal = $categoryBreakdown->sum('total');

        $appSettings = AppSetting::current();

        // DomPDF can't fetch the logo over HTTP, so inline it as a base64 data URI.
        $logoDataUri = null;

        if ($appSettings->logo_path) {
            $logoFullPath = Storage::disk('public')->path($appSettings->logo_path);

            if (file_exists($logoFullPath)) {
                $logoMime    = Storage::disk('public')->mimeType($appSettings->logo_path) ?? 'image/png';
                $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
            }
        }

        $this->logExport('PDF');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
            'summary'            => $data,
            'expenses'           => $expenses,
            'categoryBreakdown'  => $categoryBreakdown,
            'categoryGrandTotal' => $categoryGrandTotal,
            'startDate'          => $this->startDate,
            'endDate'            => $this->endDate,
            'filterUser'         => filled($this->selectedUser) ? User::find($this->selectedUser) : null,
            'filterCategory'     => filled($this->selectedCategory) ? ExpenseCategory::find($this->selectedCategory) : null,
            'primaryColor'       => $appSettings->primary_color ?? '#4f46e5',
            'applicationName'    => $appSettings->application_name ?? 'BudgetWise',
            'logoDataUri'        => $logoDataUri,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            Str::slug($appSettings->application_name ?? 'budgetwise') . '-report-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    public function exportExcel()
    {
        if (!$this->rangeIsValid()) {
            return;
        }

        $expenses = $this->buildExpenseQuery()->with(['user', 'category'])->get();
        $appName  = AppSetting::current()->application_name ?? 'BudgetWise';

        $this->logExport('Excel');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ExpenseReportExport($expenses, $appName),
            Str::slug($appName) . '-report-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function reportSummary(): array
    {
        return [
            'total_expenses' => $this->buildExpenseQuery()->count(),
            'total_amount'   => $this->buildExpenseQuery()->sum('amount'),
            'budget_alerts'  => $this->buildRiskLogQuery()->count(),
            'ocr_scans'      => (clone $this->buildExpenseQuery())->where('tracking_type', 'ocr')->count(),
        ];
    }

    public function render()
    {
        $summary = $this->rangeIsValid()
            ? $this->reportSummary()
            : ['total_expenses' => 0, 'total_amount' => 0, 'budget_alerts' => 0, 'ocr_scans' => 0];

        return view('livewire.admin.reports', array_merge($summary, [
            'users'      => User::where('role', 'student')->orderBy('name')->get(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]))->layout('layouts.admin');
    }
}