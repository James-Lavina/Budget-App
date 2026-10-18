<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\AllExpenses;
use App\Http\Livewire\Student\ExpenseCategoryWidget;
use App\Http\Livewire\Student\GoalsManager;
use App\Http\Livewire\Student\LogExpense;
use App\Http\Livewire\Student\Profile;
use App\Http\Livewire\Student\SavingsWidget;
use App\Http\Livewire\Student\ScanExpense;
use App\Http\Livewire\Student\SpendingForecast;
use App\Models\IntegrationSetting;
use App\Models\SavingsGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class StudentWidgetsTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(float $remaining = 1000.00): array
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, $remaining);
        $this->actingAs($user);

        return [$user, $budget];
    }

    private function goal($user, string $name, float $saved = 0, string $status = 'active', float $target = 500): SavingsGoal
    {
        return SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => $name, 'target_amount' => $target,
            'current_saved' => $saved, 'status' => $status,
        ]);
    }

    private function body($response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    // ---------------------------------------------------------------
    // Savings widget (dashboard)
    // ---------------------------------------------------------------

    public function test_the_widget_shows_the_three_most_saved_active_goals()
    {
        [$user] = $this->signIn();
        $this->goal($user, 'Laptop', 400);
        $this->goal($user, 'Phone', 300);
        $this->goal($user, 'Shoes', 200);
        $this->goal($user, 'Camera', 100);
        $this->goal($user, 'Trip', 500, 'achieved');

        Livewire::test(SavingsWidget::class)
            ->assertSee('Laptop')
            ->assertSee('Phone')
            ->assertSee('Shoes')
            ->assertDontSee('Camera')
            ->assertDontSee('Trip');
    }

    public function test_funding_a_goal_from_the_widget()
    {
        [$user, $budget] = $this->signIn();
        $goal = $this->goal($user, 'Laptop');

        Livewire::test(SavingsWidget::class)
            ->call('openFundingModal', $goal->id)
            ->assertSet('fundingGoalId', $goal->id)
            ->set('fund_amount', '100')
            ->call('addFunds')
            ->assertHasNoErrors()
            ->assertSet('fundingGoalId', null);

        $this->assertEquals(100.00, $goal->fresh()->current_saved);
        $this->assertEquals(900.00, $budget->fresh()->remaining_allowance);
    }

    public function test_the_widget_modal_can_be_closed_and_rejects_bad_amounts()
    {
        [$user] = $this->signIn();
        $goal = $this->goal($user, 'Laptop');

        Livewire::test(SavingsWidget::class)
            ->call('openFundingModal', $goal->id)
            ->call('closeFundingModal')
            ->assertSet('fundingGoalId', null);

        Livewire::test(SavingsWidget::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '600') // more than the goal still needs
            ->call('addFunds')
            ->assertHasErrors(['fund_amount']);
    }

    public function test_the_widget_cannot_fund_another_students_goal()
    {
        $this->signIn();
        $theirs = $this->goal($this->makeStudent(), 'Theirs');

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(SavingsWidget::class)
            ->set('fundingGoalId', $theirs->id)
            ->set('fund_amount', '10')
            ->call('addFunds');
    }

    // ---------------------------------------------------------------
    // Goals manager extras
    // ---------------------------------------------------------------

    public function test_switching_goal_tabs_and_closing_the_create_modal()
    {
        [$user] = $this->signIn();
        $this->goal($user, 'Active One');
        $this->goal($user, 'Finished One', 500, 'achieved');

        Livewire::test(GoalsManager::class)
            ->assertSee('Active One')
            ->assertDontSee('Finished One')
            ->set('activeTab', 'achieved')
            ->assertSee('Finished One')
            ->assertDontSee('Active One')
            ->call('openCreateModal')
            ->set('target_name', 'Half typed')
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false)
            ->assertSet('target_name', '');
    }

    // ---------------------------------------------------------------
    // Spending forecast page component
    // ---------------------------------------------------------------

    public function test_the_forecast_page_shows_the_local_forecast_straight_away()
    {
        $this->signIn();

        Livewire::test(SpendingForecast::class)
            ->assertSet('aiLoaded', false)
            ->assertSee('Fresh week');
    }

    public function test_the_forecast_page_without_a_budget_explains_what_to_do()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(SpendingForecast::class)
            ->assertSee('Set up your weekly budget first');
    }

    public function test_ai_tips_fill_in_after_the_first_paint()
    {
        $this->signIn();
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Eat at carinderias.|Share tricycle fares.|Bring baon from home.']]],
            ], 200),
        ]);

        Livewire::test(SpendingForecast::class)
            ->call('loadAiInsight')
            ->assertSet('aiLoaded', true)
            ->assertSee('Eat at carinderias.')
            ->assertSee('Bring baon from home.');
    }

    public function test_offline_ai_falls_back_to_local_tips()
    {
        $this->signIn();
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();
        Http::fake(['api.groq.com/*' => Http::response([], 500)]);

        $component = Livewire::test(SpendingForecast::class)->call('loadAiInsight');

        $this->assertTrue($component->get('aiLoaded'));
        $this->assertCount(3, $component->get('aiInsight')['tips']);
    }

    // ---------------------------------------------------------------
    // Category breakdown widget (dashboard)
    // ---------------------------------------------------------------

    public function test_the_category_widget_totals_this_cycle_without_savings()
    {
        [$user] = $this->signIn();
        $food  = $this->makeCategory('Food & Dining', ['color' => 'bg-emerald-50 text-emerald-700']);
        $trans = $this->makeCategory('Transportation', ['color' => 'bg-blue-50 text-blue-700']);
        $this->makeExpense($user, $food, 300.00, '2026-10-06 12:00:00');
        $this->makeExpense($user, $trans, 100.00, '2026-10-07 09:00:00');
        $this->makeExpense($user, $food, 999.00, '2026-09-30 12:00:00'); // previous cycle
        $this->makeExpense($user, $this->makeCategory('Savings'), 50.00, '2026-10-06 13:00:00');

        $component = Livewire::test(ExpenseCategoryWidget::class)
            ->call('loadCategoryBreakdown')
            ->assertSet('hasExpenses', true)
            ->assertSet('totalSpent', 400)
            ->assertDispatchedBrowserEvent('updateCategoryChart');

        $rows = collect($component->get('categoriesData'))->keyBy('name');
        $this->assertCount(2, $rows);
        $this->assertEquals(300, $rows['Food & Dining']['total']);
        $this->assertSame('75.0', $rows['Food & Dining']['percentage']);
        $this->assertSame('#059669', $rows['Food & Dining']['color']);
        $this->assertSame('25.0', $rows['Transportation']['percentage']);
        $this->assertSame('#2563eb', $rows['Transportation']['color']);
    }

    public function test_the_category_widget_is_empty_without_spending()
    {
        $this->signIn();

        Livewire::test(ExpenseCategoryWidget::class)
            ->assertSet('hasExpenses', false)
            ->assertSet('categoriesData', []);
    }

    // ---------------------------------------------------------------
    // Log expense: recent items
    // ---------------------------------------------------------------

    public function test_recent_items_for_the_chosen_category_can_be_picked()
    {
        [$user] = $this->signIn();
        $food = $this->makeCategory('Food & Dining', ['keywords' => 'chicken']);
        $this->makeExpense($user, $food, 80.00, '2026-10-05 12:00:00', ['item_name' => 'Chicken lunch']);

        Livewire::test(LogExpense::class)
            ->assertDontSee('pickRecentItem')          // no category chosen yet
            ->set('expense_category_id', $food->id)
            ->assertSee('pickRecentItem')              // recent-item buttons appear
            ->call('pickRecentItem', 'Chicken lunch')
            ->assertSet('item_name', 'Chicken lunch')
            ->assertSet('suggestedCategoryId', null);
    }

    public function test_picking_a_recent_item_that_belongs_elsewhere_shows_the_nudge()
    {
        [$user] = $this->signIn();
        $food      = $this->makeCategory('Food & Dining');
        $transport = $this->makeCategory('Transportation', ['keywords' => 'jeepney']);
        $this->makeExpense($user, $food, 20.00, '2026-10-05 12:00:00', ['item_name' => 'Jeepney ride']);

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $food->id)
            ->call('pickRecentItem', 'Jeepney ride')
            ->assertSet('suggestedCategoryId', $transport->id);
    }

    // ---------------------------------------------------------------
    // All expenses: cancel and filter behaviour
    // ---------------------------------------------------------------

    public function test_cancelling_a_bulk_delete_keeps_the_expenses()
    {
        [$user] = $this->signIn();
        $expense = $this->makeExpense($user, $this->makeCategory(), 50.00, '2026-10-06 10:00:00');

        Livewire::test(AllExpenses::class)
            ->set('selected', [(string) $expense->id])
            ->call('confirmBulkDelete')
            ->assertSet('confirmingBulkDelete', true)
            ->call('cancelBulkDelete')
            ->assertSet('confirmingBulkDelete', false);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_changing_a_filter_clears_the_selection()
    {
        [$user] = $this->signIn();
        $cat = $this->makeCategory();
        $this->makeExpense($user, $cat, 50.00, '2026-10-06 10:00:00');

        Livewire::test(AllExpenses::class)
            ->set('selectAll', true)
            ->set('search', 'zzz')
            ->assertSet('selected', [])
            ->assertSet('selectAll', false)
            ->set('selectAll', true)
            ->set('selectedCategory', $cat->id)
            ->assertSet('selected', [])
            ->assertSet('selectAll', false);
    }

    // ---------------------------------------------------------------
    // Receipt scanner
    // ---------------------------------------------------------------

    public function test_a_selected_receipt_can_be_cleared()
    {
        Storage::fake('local');
        $this->signIn();

        Livewire::test(ScanExpense::class)
            ->set('receiptImage', UploadedFile::fake()->image('receipt.jpg'))
            ->call('clearReceiptImage')
            ->assertSet('receiptImage', null);
    }

    // ---------------------------------------------------------------
    // "Download my data" contents
    // ---------------------------------------------------------------

    public function test_the_data_export_contains_my_records_and_nothing_sensitive_or_foreign()
    {
        [$user, $budget] = $this->signIn(900.00);
        $cat = $this->makeCategory('Food & Dining');
        $this->makeExpense($user, $cat, 100.00, '2026-10-06 12:00:00', ['item_name' => 'My lunch']);
        $this->goal($user, 'Laptop', 50);
        DatabaseNotification::create([
            'id' => (string) Str::uuid(), 'type' => 'App\Notifications\LowAllowanceWarning',
            'notifiable_type' => User::class, 'notifiable_id' => $user->id,
            'data' => ['anomaly_type' => 'low_allowance_threshold', 'description' => 'Low'],
        ]);

        $other = $this->makeStudent(['email' => 'someone.else@example.com']);
        $this->makeBudget($other);
        $this->makeExpense($other, $cat, 77.00, '2026-10-06 12:00:00', ['item_name' => 'Secret meal']);

        $response = (new Profile())->exportData();
        $json     = $this->body($response);
        $data     = json_decode($json, true);

        foreach (['exported_at', 'profile', 'weekly_budgets', 'expenses', 'savings_goals', 'receipts',
                  'risk_alerts', 'simulations', 'notifications', 'activity_log'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        $this->assertSame($user->email, $data['profile']['email']);
        $this->assertCount(1, $data['expenses']);
        $this->assertSame('My lunch', $data['expenses'][0]['item']);
        $this->assertSame('Food & Dining', $data['expenses'][0]['category']);
        $this->assertCount(1, $data['savings_goals']);
        $this->assertCount(1, $data['notifications']);

        $this->assertArrayNotHasKey('password', $data['profile']);
        $this->assertStringNotContainsString($user->password, $json);
        $this->assertStringNotContainsString('someone.else@example.com', $json);
        $this->assertStringNotContainsString('Secret meal', $json);

        $this->assertStringContainsString('-my-data-', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.json', $response->headers->get('content-disposition'));
    }
}