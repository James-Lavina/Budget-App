<?php

namespace Tests\Feature;

use App\Exports\ExpenseReportExport;
use App\Http\Livewire\Admin\Reports;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

/**
 * Calls the export methods on a Reports instance directly (as an admin) so the
 * downloaded bytes can be inspected. Livewire's test client hides the body.
 */
class ReportsExportTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00');
        $this->actingAs($this->makeAdmin());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reports(array $props = []): Reports
    {
        $component = new Reports();

        foreach (array_merge(['startDate' => '2026-10-01', 'endDate' => '2026-10-31'], $props) as $key => $value) {
            $component->{$key} = $value;
        }

        return $component;
    }

    private function body($response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    private function csvRows(string $csv): array
    {
        return array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    }

    private function seedExpenses(): array
    {
        $alice = $this->makeStudent(['name' => 'Alice Reyes']);
        $bob   = $this->makeStudent(['name' => 'Bob Santos']);
        $food  = $this->makeCategory('Food & Dining');
        $trans = $this->makeCategory('Transportation');

        $this->makeExpense($alice, $food, 100.00, '2026-10-05 10:00:00', ['item_name' => 'Lunch']);
        $this->makeExpense($alice, $trans, 20.00, '2026-10-06 10:00:00', ['item_name' => 'Jeepney']);
        $this->makeExpense($bob, $food, 50.00, '2026-10-06 11:00:00', ['item_name' => 'Snack']);
        $this->makeExpense($bob, $food, 999.00, '2026-08-01 11:00:00', ['item_name' => 'Out of range']);

        return [$alice, $bob, $food, $trans];
    }

    // ---------------------------------------------------------------
    // CSV
    // ---------------------------------------------------------------

    public function test_csv_contains_a_header_and_the_rows_in_range_in_date_order()
    {
        $this->seedExpenses();

        $response = $this->reports()->exportCsv();
        $rows     = $this->csvRows($this->body($response));

        $this->assertSame(['Date', 'User', 'Category', 'Item', 'Amount'], $rows[0]);
        $this->assertCount(4, $rows); // header + 3 expenses in range
        $this->assertSame(['2026-10-05', 'Alice Reyes', 'Food & Dining', 'Lunch'], array_slice($rows[1], 0, 4));
        $this->assertEquals(100, (float) $rows[1][4]);
        $this->assertSame('Jeepney', $rows[2][3]);
        $this->assertSame('Snack', $rows[3][3]);
        $this->assertStringContainsString('budgetwise-report-', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
    }

    public function test_csv_respects_the_student_and_category_filters()
    {
        [$alice, , , $trans] = $this->seedExpenses();

        $byUser = $this->csvRows($this->body($this->reports(['selectedUser' => $alice->id])->exportCsv()));
        $this->assertCount(3, $byUser); // header + 2

        $byCategory = $this->csvRows($this->body($this->reports(['selectedCategory' => $trans->id])->exportCsv()));
        $this->assertCount(2, $byCategory);
        $this->assertSame('Jeepney', $byCategory[1][3]);
    }

    public function test_csv_cells_cannot_start_a_spreadsheet_formula()
    {
        $user = $this->makeStudent(['name' => '=cmd|calc']);
        $cat  = $this->makeCategory();
        $this->makeExpense($user, $cat, 10.00, '2026-10-05 10:00:00', ['item_name' => '=SUM(A1)']);

        $rows = $this->csvRows($this->body($this->reports()->exportCsv()));

        foreach (array_slice($rows[1], 1, 3) as $cell) {
            $this->assertNotContains(substr($cell, 0, 1), ['=', '+', '-', '@'], "Unsafe cell: {$cell}");
        }
    }

    public function test_every_export_is_audited_with_its_filters()
    {
        [$alice] = $this->seedExpenses();

        $this->reports(['selectedUser' => $alice->id])->exportCsv();

        $log = \App\Models\ActivityLog::where('event_type', 'report_exported')->first();
        $this->assertStringContainsString('Exported CSV report (2026-10-01 to 2026-10-31', $log->details);
        $this->assertStringContainsString('user: Alice Reyes', $log->details);
    }

    public function test_an_invalid_range_exports_nothing_and_is_not_audited()
    {
        $this->seedExpenses();
        $component = $this->reports(['startDate' => '2026-10-31', 'endDate' => '2026-10-01']);

        $this->assertNull($component->exportCsv());
        $this->assertNull($component->exportPdf());
        $this->assertNull($component->exportExcel());
        $this->assertDatabaseMissing('activity_logs', ['event_type' => 'report_exported']);
    }

    // ---------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------

    public function test_pdf_export_produces_a_pdf_document()
    {
        Storage::fake('public');
        $this->seedExpenses();

        $response = $this->reports()->exportPdf();
        $content  = $this->body($response);

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'report_exported']);
    }

    public function test_pdf_export_works_with_no_expenses()
    {
        Storage::fake('public');

        $content = $this->body($this->reports()->exportPdf());

        $this->assertStringStartsWith('%PDF', $content);
    }

    // ---------------------------------------------------------------
    // Excel
    // ---------------------------------------------------------------

    public function test_excel_export_is_downloaded_with_a_timestamped_name()
    {
        Excel::fake();
        $this->seedExpenses();

        $this->reports()->exportExcel();

        Excel::assertDownloaded('budgetwise-report-20261007-120000.xlsx');
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'report_exported']);
    }

    public function test_the_excel_export_class_builds_a_real_workbook()
    {
        $this->seedExpenses();
        $expenses = Expense::with(['user', 'category'])->get();

        $binary = Excel::raw(new ExpenseReportExport($expenses, 'BudgetWise'), ExcelWriter::XLSX);

        $this->assertStringStartsWith('PK', $binary); // xlsx files are zip archives
    }
}