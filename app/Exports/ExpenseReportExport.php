<?php

namespace App\Exports;

use App\Support\CsvSafe;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExpenseReportExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithColumnFormatting,
    WithColumnWidths,
    WithStyles
{
    protected $expenses;
    protected $applicationName;

    public function __construct(Collection $expenses, string $applicationName = 'BudgetWise')
    {
        $this->expenses = $expenses;
        $this->applicationName = $applicationName;
    }

    public function title(): string
    {
        return 'Expense Report';
    }

    public function headings(): array
    {
        return ['Date', 'User', 'Category', 'Item', 'Amount'];
    }

    public function collection()
    {
        // Student-typed text starting with = + - @ would run as a formula in Excel,
        // so every text column goes through CsvSafe.
        return $this->expenses->map(function ($expense) {
            return [
                $expense->transaction_date->format('Y-m-d'),
                CsvSafe::cell($expense->user->name ?? 'Unknown'),
                CsvSafe::cell($expense->category->name ?? 'Uncategorized'),
                CsvSafe::cell($expense->item_name),
                (float) $expense->amount,
            ];
        });
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // Date
            'B' => 24, // User
            'C' => 20, // Category
            'D' => 36, // Item
            'E' => 16, // Amount
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Plain text stops Excel from reformatting dates by the opening machine's locale.
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => '"₱"#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->expenses->count() + 1; // +1 for header row

        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
        ]);

        // Bold total row appended after the data
        $totalRow = $lastRow + 2;
        $sheet->setCellValue("D{$totalRow}", 'Total');
        $sheet->setCellValue("E{$totalRow}", (float) $this->expenses->sum('amount'));
        $sheet->getStyle("D{$totalRow}:E{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle("E{$totalRow}")
            ->getNumberFormat()
            ->setFormatCode('"₱"#,##0.00');

        return [];
    }
}