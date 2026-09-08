<?php

namespace App\Http\Livewire\Admin;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RiskLog;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public $startDate;
    public $endDate;
    public $selectedUser = '';
    public $selectedCategory = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
        $this->endDate   = Carbon::now()->endOfWeek()->format('Y-m-d');
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

    public function exportCsv()
    {
        $expenses = $this->buildExpenseQuery()->with(['user', 'category'])->get();
        $appName = \App\Models\AppSetting::current()->application_name ?? 'BudgetWise';

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'User', 'Category', 'Item', 'Amount']);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->transaction_date->format('Y-m-d'),
                    $expense->user->name ?? 'Unknown',
                    $expense->category->name ?? 'Uncategorized',
                    $expense->item_name,
                    $expense->amount,
                ]);
            }

            fclose($handle);
        }, \Illuminate\Support\Str::slug($appName) . '-report-' . now()->format('Ymd-His') . '.csv');
    }

    public function exportPdf()
    {
        $expenses = $this->buildExpenseQuery()
            ->with(['user', 'category'])
            ->orderBy('transaction_date')
            ->get();

        $data = $this->reportSummary();

        // Category breakdown for the mini bar chart in the PDF — grouped from
        // the same filtered expense set so it always matches the line items below.
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

        // DomPDF can't fetch the logo over HTTP (no live server context inside
        // the render), so if a logo is set, read it straight off the public
        // disk and inline it as a base64 data URI instead of a <img src="url">.
        $logoDataUri = null;
        if ($appSettings->logo_path) {
            $logoFullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($appSettings->logo_path);
            if (file_exists($logoFullPath)) {
                $logoMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($appSettings->logo_path) ?? 'image/png';
                $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
            }
        }

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
            \Illuminate\Support\Str::slug($appSettings->application_name ?? 'budgetwise') . '-report-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    public function exportExcel()
    {
        $expenses = $this->buildExpenseQuery()->with(['user', 'category'])->get();
        $appName = \App\Models\AppSetting::current()->application_name ?? 'BudgetWise';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ExpenseReportExport($expenses, $appName),
            \Illuminate\Support\Str::slug($appName) . '-report-' . now()->format('Ymd-His') . '.xlsx'
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
        return view('livewire.admin.reports', array_merge(
            $this->reportSummary(),
            [
                'users'      => User::where('role', 'student')->orderBy('name')->get(),
                'categories' => ExpenseCategory::orderBy('name')->get(),
            ]
        ))->layout('layouts.admin');
    }
}