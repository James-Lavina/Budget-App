<?php

namespace App\Http\Livewire\Admin;

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
        }, 'budgetwise-report-' . now()->format('Ymd-His') . '.csv');
    }

    public function exportPdf()
    {
        $data = $this->reportSummary();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
            'summary'    => $data,
            'startDate'  => $this->startDate,
            'endDate'    => $this->endDate,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'budgetwise-report-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    public function exportExcel()
    {
        $expenses = $this->buildExpenseQuery()->with(['user', 'category'])->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ExpenseReportExport($expenses),
            'budgetwise-report-' . now()->format('Ymd-His') . '.xlsx'
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