<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\ScanExpense;
use App\Models\Expense;
use App\Models\IntegrationSetting;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

/**
 * NOTE: tests that exercise a failing Groq call sleep for ~3 seconds because
 * ScanExpense waits between its two retry attempts.
 */
class ScanExpenseTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-07 12:00:00');
        config(['services.ocr_space.key' => null]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function giveGroqKey(): void
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();
    }

    private function groqReply(array $payload): array
    {
        return ['choices' => [['message' => ['content' => json_encode($payload)]]]];
    }

    private function receiptFile(): UploadedFile
    {
        return UploadedFile::fake()->image('receipt.jpg', 600, 800);
    }

    private function setUpStudent(float $remaining = 1000.00): array
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, $remaining);
        $this->actingAs($user);

        return [$user, $this->makeCategory('Food & Dining', ['keywords' => 'chickenjoy,fries'])];
    }

    public function test_groq_success_extracts_items_and_saving_creates_expenses()
    {
        [$user, $food] = $this->setUpStudent();
        $this->giveGroqKey();

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqReply([
                'merchant_name'    => 'Jollibee',
                'transaction_date' => '2026-10-06',
                'items'            => [
                    ['item_name' => 'Chickenjoy', 'amount' => 99.00, 'category' => 'Food & Dining'],
                    ['item_name' => 'Coke', 'amount' => 35.5, 'category' => 'Food & Dining'],
                ],
            ]), 200),
        ]);

        $component = Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('step', 2)
            ->assertSet('merchant_name', 'Jollibee')
            ->assertSet('transaction_date', '2026-10-06')
            ->assertSet('usedBackupOcr', false);

        $items = $component->get('items');
        $this->assertCount(2, $items);
        $this->assertSame('Chickenjoy', $items[0]['item_name']);
        $this->assertSame('99.00', $items[0]['amount']);
        $this->assertSame('35.50', $items[1]['amount']);
        $this->assertEquals($food->id, $items[0]['expense_category_id']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com')
            && $request->hasHeader('Authorization', 'Bearer gsk_test'));
        $this->assertSame(1, Receipt::count());

        $component->call('saveVerifiedExpense')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $this->assertSame(2, Expense::where('user_id', $user->id)->where('tracking_type', 'ocr')->count());
        $this->assertEquals(865.50, $user->weeklyBudgets()->first()->remaining_allowance);
        $this->assertSame('processed', Receipt::first()->status);
        $this->assertNotNull(Receipt::first()->expense_id);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'expense_scanned']);
    }

    public function test_receipt_date_outside_the_current_cycle_is_replaced_with_today()
    {
        $this->setUpStudent();
        $this->giveGroqKey();

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqReply([
                'merchant_name'    => 'Old Store',
                'transaction_date' => '2026-09-01',
                'items'            => [['item_name' => 'Rice', 'amount' => 50, 'category' => 'Food & Dining']],
            ]), 200),
        ]);

        $component = Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('transaction_date', '2026-10-07');

        $this->assertNotNull($component->get('dateNotice'));
    }

    public function test_missing_receipt_date_defaults_to_today_without_a_notice()
    {
        $this->setUpStudent();
        $this->giveGroqKey();

        Http::fake([
            'api.groq.com/*' => Http::response($this->groqReply([
                'merchant_name'    => 'Store',
                'transaction_date' => null,
                'items'            => [['item_name' => 'Rice', 'amount' => 50, 'category' => 'Food & Dining']],
            ]), 200),
        ]);

        $component = Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('transaction_date', '2026-10-07');

        $this->assertNull($component->get('dateNotice'));
    }

    /** Requires the parseReceiptText() regex fix (item names were truncated to one letter). */
    public function test_ocr_space_fallback_is_used_when_groq_fails()
    {
        [$user, $food] = $this->setUpStudent();
        $this->giveGroqKey();
        $this->makeCategory('Other', ['is_fallback' => true]);
        config(['services.ocr_space.key' => 'ocr_test']);

        Http::fake([
            'api.groq.com/*'  => Http::response([], 500),
            'api.ocr.space/*' => Http::response([
                'IsErroredOnProcessing' => false,
                'ParsedResults'         => [['ParsedText' => "JOLLIBEE\nChickenjoy 99.00\nFries 45.00\nTOTAL 144.00"]],
            ], 200),
        ]);

        $component = Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('step', 2)
            ->assertSet('usedBackupOcr', true)
            ->assertSet('merchant_name', 'JOLLIBEE');

        $items = $component->get('items');
        $this->assertSame(['Chickenjoy', 'Fries'], array_column($items, 'item_name'));
        $this->assertSame(['99.00', '45.00'], array_column($items, 'amount'));
        $this->assertEquals($food->id, $items[0]['expense_category_id']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.ocr.space'));
    }

    public function test_receipt_is_marked_failed_when_every_provider_fails()
    {
        $this->setUpStudent();
        $this->giveGroqKey();
        Http::fake(['api.groq.com/*' => Http::response([], 500)]);

        Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('step', 1);

        $this->assertSame('failed', Receipt::first()->status);
        $this->assertSame(0, Expense::count());
    }

    /** Requires fix #5 (ConnectionException handling + failScan cleanup). */
    public function test_network_failure_marks_the_receipt_failed_and_deletes_the_image()
    {
        $this->setUpStudent();
        $this->giveGroqKey();
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: timed out');
        });

        Livewire::test(ScanExpense::class)
            ->set('receiptImage', $this->receiptFile())
            ->call('processReceipt')
            ->assertSet('step', 1)
            ->assertSet('isProcessing', false);

        $this->assertSame('failed', Receipt::first()->status);
        $this->assertSame([], Storage::disk('local')->allFiles('receipts'));
    }

    public function test_non_image_uploads_are_rejected()
    {
        $this->setUpStudent();

        Livewire::test(ScanExpense::class)
            ->set('receiptImage', UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['receiptImage']);
    }

    public function test_processing_without_an_image_does_nothing()
    {
        $this->setUpStudent();
        Http::fake();

        Livewire::test(ScanExpense::class)
            ->call('processReceipt')
            ->assertSet('step', 1);

        Http::assertNothingSent();
        $this->assertSame(0, Receipt::count());
    }

    public function test_saving_is_blocked_when_the_total_exceeds_the_remaining_allowance()
    {
        [$user, $food] = $this->setUpStudent(50.00);

        Livewire::test(ScanExpense::class)
            ->set('step', 2)
            ->set('transaction_date', '2026-10-07')
            ->set('items', [['item_name' => 'Lunch', 'amount' => '144.00', 'expense_category_id' => $food->id]])
            ->call('saveVerifiedExpense')
            ->assertHasErrors(['items']);

        $this->assertSame(0, Expense::count());
        $this->assertEquals(50.00, $user->weeklyBudgets()->first()->remaining_allowance);
    }

    public function test_saving_rejects_disabled_categories_bad_amounts_future_dates_and_empty_lists()
    {
        [$user, $food] = $this->setUpStudent();
        $disabled = $this->makeCategory('Hidden', ['status' => 'disabled']);

        $base = Livewire::test(ScanExpense::class)->set('step', 2)->set('transaction_date', '2026-10-07');

        $base->set('items', [['item_name' => 'X', 'amount' => '10.00', 'expense_category_id' => $disabled->id]])
            ->call('saveVerifiedExpense')
            ->assertHasErrors(['items.0.expense_category_id']);

        $base->set('items', [['item_name' => 'X', 'amount' => '10.123', 'expense_category_id' => $food->id]])
            ->call('saveVerifiedExpense')
            ->assertHasErrors(['items.0.amount']);

        $base->set('items', [])
            ->call('saveVerifiedExpense')
            ->assertHasErrors(['items']);

        $base->set('items', [['item_name' => 'X', 'amount' => '10.00', 'expense_category_id' => $food->id]])
            ->set('transaction_date', '2026-10-09')
            ->call('saveVerifiedExpense')
            ->assertHasErrors(['transaction_date']);

        $this->assertSame(0, Expense::count());
    }

    public function test_items_can_be_added_and_removed_on_the_verify_step()
    {
        $this->setUpStudent();
        $this->makeCategory('Other', ['is_fallback' => true]);

        $component = Livewire::test(ScanExpense::class)
            ->set('step', 2)
            ->call('addItem')
            ->call('addItem');

        $this->assertCount(2, $component->get('items'));

        $component->call('removeItem', 0);

        $this->assertCount(1, $component->get('items'));
    }
}