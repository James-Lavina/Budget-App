<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\Dashboard;
use App\Models\SavingsGoal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

/**
 * "Now" is Wednesday 2026-10-07 12:00. The cycle is Mon 10-05 .. Sun 10-11,
 * so 3 days have elapsed and 5 days remain (including today).
 */
class DashboardStatesTest extends TestCase
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
        $food   = $this->makeCategory('Food & Dining');
        $this->actingAs($user);

        return [$user, $budget, $food];
    }

    public function test_a_student_without_a_budget_is_sent_to_setup()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(Dashboard::class)->assertRedirect(route('student.budget-setup'));
    }

    public function test_fresh_start()
    {
        $this->signIn();

        Livewire::test(Dashboard::class)
            ->assertViewHas('dashboardState', 'fresh_start')
            ->assertSet('daysRemaining', 5)
            ->assertSet('dailyQuota', 200)
            ->assertSet('safeToSpend', 200)
            ->assertSee('Fresh start this week')
            ->assertSee('if spent evenly');
    }

    public function test_on_track()
    {
        [$user, , $food] = $this->signIn(850.00);
        $this->makeExpense($user, $food, 150.00, '2026-10-06 12:00:00');

        Livewire::test(Dashboard::class)
            ->assertViewHas('dashboardState', 'on_track')
            ->assertSet('dailyQuota', 170)
            ->assertSee('On track this week')
            ->assertSee('still finish the week on budget');
    }

    public function test_pace_critical_when_the_balance_will_not_last()
    {
        [$user, , $food] = $this->signIn(100.00);
        $this->makeExpense($user, $food, 900.00, '2026-10-06 12:00:00');

        Livewire::test(Dashboard::class)
            ->assertViewHas('dashboardState', 'pace_critical')
            ->assertSee('Spending too fast')
            ->assertSee('to catch back up');
    }

    public function test_depleted_budget()
    {
        [$user, , $food] = $this->signIn(0.00);
        $this->makeExpense($user, $food, 1000.00, '2026-10-06 12:00:00');

        Livewire::test(Dashboard::class)
            ->assertViewHas('dashboardState', 'depleted')
            ->assertSee('Budget exhausted')
            ->assertSee('used your full budget');
    }

    public function test_daily_quota_hit_without_savings()
    {
        [$user, , $food] = $this->signIn(1000.00);
        $this->makeExpense($user, $food, 400.00, '2026-10-07 10:00:00');

        Livewire::test(Dashboard::class)
            ->assertSet('spentToday', 400)
            ->assertSet('safeToSpend', 0)
            ->assertViewHas('dashboardState', 'quota_hit')
            ->assertSee('daily budget is used');
    }

    public function test_quota_hit_with_a_savings_transfer_today_is_shown_as_savings_locked()
    {
        [$user, , $food] = $this->signIn(1000.00);
        $this->makeExpense($user, $food, 400.00, '2026-10-07 10:00:00');
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 5000,
            'current_saved' => 50, 'status' => 'active',
        ]);
        $this->makeExpense($user, $this->makeCategory('Savings'), 50.00, '2026-10-07 11:00:00', [
            'savings_goal_id' => $goal->id,
            'item_name'       => 'Laptop',
        ]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('dashboardState', 'savings_locked')
            ->assertViewHas('totalSavedThisWeek', 50)
            ->assertSee('Saved today')
            ->assertSee('You saved ₱50.00 today');
    }

    public function test_savings_transfers_do_not_count_as_spending_today()
    {
        [$user] = $this->signIn(950.00);
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 5000,
            'current_saved' => 50, 'status' => 'active',
        ]);
        $this->makeExpense($user, $this->makeCategory('Savings'), 50.00, '2026-10-07 11:00:00', [
            'savings_goal_id' => $goal->id,
        ]);

        Livewire::test(Dashboard::class)
            ->assertSet('spentToday', 0)
            ->assertViewHas('totalSpent', 0);
    }

    public function test_biggest_category_and_week_label()
    {
        [$user, , $food] = $this->signIn();
        $transport = $this->makeCategory('Transportation');
        $this->makeExpense($user, $food, 300.00, '2026-10-06 12:00:00');
        $this->makeExpense($user, $transport, 100.00, '2026-10-06 13:00:00');

        Livewire::test(Dashboard::class)
            ->assertViewHas('biggestCategoryName', 'Food & Dining')
            ->assertViewHas('weekRangeLabel', 'Oct 5 – 11');
    }

    public function test_savings_progress_card_counts_active_and_achieved_goals()
    {
        [$user] = $this->signIn();
        SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Active', 'target_amount' => 500,
            'current_saved' => 200, 'status' => 'active',
        ]);
        SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Done', 'target_amount' => 500,
            'current_saved' => 500, 'status' => 'achieved',
        ]);
        SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Archived', 'target_amount' => 9000,
            'current_saved' => 9000, 'status' => 'abandoned',
        ]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('savingsGoalsCount', 1)
            ->assertViewHas('totalSavingsSaved', 700)
            ->assertViewHas('savingsProgressPercent', 70);
    }

    public function test_recent_transactions_show_only_the_five_newest()
    {
        [$user, , $food] = $this->signIn();
        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot'] as $i => $name) {
            $this->makeExpense($user, $food, 10.00, '2026-10-06 1' . $i . ':00:00', ['item_name' => $name]);
        }

        Livewire::test(Dashboard::class)
            ->assertSee('Foxtrot')
            ->assertSee('Bravo')
            ->assertDontSee('Alpha');
    }

    public function test_deleting_from_the_dashboard_refunds_and_recomputes()
    {
        [$user, $budget, $food] = $this->signIn(900.00);
        $expense = $this->makeExpense($user, $food, 100.00, '2026-10-07 10:00:00');

        Livewire::test(Dashboard::class)
            ->set('confirmingDeleteId', $expense->id)
            ->call('deleteExpense', $expense->id)
            ->assertSet('confirmingDeleteId', null)
            ->assertSet('spentToday', 0);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    public function test_previous_cycle_and_foreign_expenses_cannot_be_deleted_from_the_dashboard()
    {
        [$user, $budget, $food] = $this->signIn(900.00);
        $old   = $this->makeExpense($user, $food, 100.00, '2026-09-30 10:00:00');
        $other = $this->makeExpense($this->makeStudent(), $food, 100.00, '2026-10-06 10:00:00');

        Livewire::test(Dashboard::class)
            ->call('deleteExpense', $old->id)
            ->call('deleteExpense', $other->id);

        $this->assertDatabaseHas('expenses', ['id' => $old->id]);
        $this->assertDatabaseHas('expenses', ['id' => $other->id]);
        $this->assertEquals(900.00, $budget->fresh()->remaining_allowance);
    }
}