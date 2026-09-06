<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpenseReportExport implements FromCollection, WithHeadings
{
    protected $expenses;

    public function __construct(Collection $expenses)
    {
        $this->expenses = $expenses;
    }

    public function headings(): array
    {
        return ['Date', 'User', 'Category', 'Item', 'Amount'];
    }

    public function collection()
    {
        return $this->expenses->map(function ($expense) {
            return [
                $expense->transaction_date->format('Y-m-d'),
                $expense->user->name ?? 'Unknown',
                $expense->category->name ?? 'Uncategorized',
                $expense->item_name,
                $expense->amount,
            ];
        });
    }
}