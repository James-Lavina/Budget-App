<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class RiskDetectionServiceTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday, day 3 of 7
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): RiskDetectionService
    {
        return app(RiskDetectionService::class);
    }

    private function alerts(User $user, string $type)
    {
        return DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->get()
            ->filter(fn ($n) => ($n->data['anomaly_type'] ?? null) === $type)
            ->values();
    }

    private function isResolved($notification): bool
    {
        return (bool) ($notification->fresh()->data['resolved'] ?? false);
    }

    public function test_a_healthy_budget_creates_no_alerts()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);

        $this->service()->evaluateSpendingRisk($user);

        $this->assertDatabaseCount('risk_logs', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_student_without_a_budget_is_ignored()
    {
        $user = $this->makeStudent();

        $this->service()->evaluateSpendingRisk($user);

        $this->assertDatabaseCount('risk_logs', 0);
    }

    public function test_overspending_alert_is_created_once_then_resolved_on_recovery()
    {
        $user    = $this->makeStudent();
        $budget  = $this->makeBudget($user, 150.00);
        $expense = $this->makeExpense($user, $this->makeCategory(), 850.00, '2026-10-06 12:00:00');

        $this->service()->evaluateSpendingRisk($user);
        $this->service()->evaluateSpendingRisk($user); // idempotent

        $logs = RiskLog::where('user_id', $user->id)->where('anomaly_type', 'overspending_threshold')->get();
        $this->assertCount(1, $logs);
        $this->assertSame('high', $logs->first()->severity_tier);
        $this->assertFalse((bool) $logs->first()->resolved);

        $alerts = $this->alerts($user, 'overspending_threshold');
        $this->assertCount(1, $alerts);
        $this->assertFalse($this->isResolved($alerts->first()));

        // Student deletes most of the spending: 30% used.
        $expense->update(['amount' => 300]);
        $budget->update(['remaining_allowance' => 700]);
        $this->service()->evaluateSpendingRisk($user);

        $this->assertTrue((bool) $logs->first()->fresh()->resolved);
        $this->assertTrue($this->isResolved($alerts->first()));
    }

    public function test_overspending_rule_can_be_disabled_or_retuned_by_the_admin()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 150.00);
        $this->makeExpense($user, $this->makeCategory(), 850.00, '2026-10-06 12:00:00');

        RiskSetting::current()->update(['overspending_enabled' => false]);
        $this->service()->evaluateSpendingRisk($user);
        $this->assertSame(0, RiskLog::where('anomaly_type', 'overspending_threshold')->count());

        RiskSetting::current()->update(['overspending_enabled' => true, 'overspending_threshold' => 90]);
        $this->service()->evaluateSpendingRisk($user);
        $this->assertSame(0, RiskLog::where('anomaly_type', 'overspending_threshold')->count()); // 85% < 90%

        RiskSetting::current()->update(['overspending_threshold' => 80]);
        $this->service()->evaluateSpendingRisk($user);
        $this->assertSame(1, RiskLog::where('anomaly_type', 'overspending_threshold')->count());
    }

    public function test_fast_early_week_spending_triggers_a_pace_alert()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 400.00);
        $this->makeExpense($user, $this->makeCategory(), 600.00, '2026-10-06 12:00:00');

        $this->service()->evaluateSpendingRisk($user);

        $log = RiskLog::where('user_id', $user->id)->where('anomaly_type', 'early_week_depletion')->first();
        $this->assertNotNull($log);
        $this->assertSame('medium', $log->severity_tier);
        $this->assertStringContainsString('Pace Check', $log->description);
    }

    public function test_low_allowance_warning_lifecycle()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 100.00);

        $this->service()->evaluateSpendingRisk($user);

        $alerts = $this->alerts($user, 'low_allowance_threshold');
        $this->assertCount(1, $alerts);
        $this->assertStringContainsString('10%', $alerts->first()->data['description']);
        $this->assertFalse($this->isResolved($alerts->first()));

        $budget->update(['remaining_allowance' => 900]);
        $this->service()->evaluateSpendingRisk($user);
        $this->assertTrue($this->isResolved($alerts->first()));

        // Dropping low again re-opens the same notification instead of duplicating it.
        $budget->update(['remaining_allowance' => 100]);
        $this->service()->evaluateSpendingRisk($user);

        $alerts = $this->alerts($user, 'low_allowance_threshold');
        $this->assertCount(1, $alerts);
        $this->assertFalse($this->isResolved($alerts->first()));
    }

    public function test_rapid_spending_alert_triggers_and_clears()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00);
        $cat    = $this->makeCategory();

        $expenses = [];
        foreach ([200, 200, 200] as $amount) {
            $expenses[] = $this->makeExpense($user, $cat, $amount, '2026-10-07 10:00:00');
        }

        $this->service()->evaluateSpendingRisk($user);

        $log = RiskLog::where('user_id', $user->id)->where('anomaly_type', 'rapid_spending')->first();
        $this->assertNotNull($log);
        $this->assertFalse((bool) $log->resolved);

        $expenses[0]->delete();
        $budget->update(['remaining_allowance' => 600]);
        $this->service()->evaluateSpendingRisk($user);

        $this->assertTrue((bool) $log->fresh()->resolved);
    }

    public function test_category_concentration_alert_appears_and_resolves_when_spending_spreads_out()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 600.00);
        $food   = $this->makeCategory('Food & Dining');
        $this->makeExpense($user, $food, 400.00, '2026-10-06 12:00:00');

        $this->service()->evaluateSpendingRisk($user);

        $alerts = $this->alerts($user, 'category_concentration');
        $this->assertCount(1, $alerts);
        $this->assertSame('Food & Dining', $alerts->first()->data['category']);

        $this->makeExpense($user, $this->makeCategory('Transportation'), 400.00, '2026-10-06 13:00:00');
        $this->makeExpense($user, $this->makeCategory('Entertainment'), 400.00, '2026-10-06 14:00:00');
        $budget->update(['remaining_allowance' => 0]);

        $this->service()->evaluateSpendingRisk($user);

        $this->assertTrue($this->isResolved($alerts->first()));
    }

    public function test_large_transaction_alert_is_created_updated_and_resolved()
    {
        $user    = $this->makeStudent();
        $this->makeBudget($user, 600.00);
        $expense = $this->makeExpense($user, $this->makeCategory(), 400.00); // 40% of the pool

        $this->service()->checkLargeTransaction($user, $expense);

        $alerts = $this->alerts($user, 'large_transaction');
        $this->assertCount(1, $alerts);
        $this->assertSame($expense->id, $alerts->first()->data['expense_id']);

        // Editing it down below 30% resolves the alert rather than leaving it stale.
        $expense->update(['amount' => 100]);
        $this->service()->checkLargeTransaction($user, $expense->fresh());

        $this->assertTrue($this->isResolved($alerts->first()));
    }

    public function test_small_and_savings_transactions_never_raise_a_large_transaction_alert()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 400.00);
        $cat  = $this->makeCategory();

        $small = $this->makeExpense($user, $cat, 100.00);
        $goal  = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 5000,
            'current_saved' => 500, 'status' => 'active',
        ]);
        $saving = $this->makeExpense($user, $this->makeCategory('Savings'), 500.00, '2026-10-07 11:00:00', [
            'savings_goal_id' => $goal->id,
        ]);

        $this->service()->checkLargeTransaction($user, $small);
        $this->service()->checkLargeTransaction($user, $saving);

        $this->assertCount(0, $this->alerts($user, 'large_transaction'));
    }

    public function test_resolving_a_deleted_large_transaction_marks_its_alert_resolved()
    {
        $user    = $this->makeStudent();
        $this->makeBudget($user, 600.00);
        $expense = $this->makeExpense($user, $this->makeCategory(), 400.00);
        $this->service()->checkLargeTransaction($user, $expense);

        $this->service()->resolveLargeTransactionAlert($user, $expense->id);

        $this->assertTrue($this->isResolved($this->alerts($user, 'large_transaction')->first()));
    }

    public function test_logging_an_expense_resolves_the_no_expense_logs_alert()
    {
        $user = $this->makeStudent();
        $notification = DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\NoExpenseLogsWarning',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => [
                'anomaly_type' => 'no_expense_logs',
                'description'  => 'No logs',
                'resolved'     => false,
            ],
        ]);

        $this->service()->resolveNoExpenseLogsAlert($user);

        $this->assertTrue($this->isResolved($notification));
    }

    public function test_default_risk_settings_are_loaded_on_first_use()
    {
        $settings = RiskSetting::current();

        $this->assertTrue($settings->overspending_enabled);
        $this->assertEquals(80, $settings->overspending_threshold);
        $this->assertEquals(3, $settings->rapid_spending_count);
        $this->assertFalse($settings->no_expense_logs_enabled);
    }
}