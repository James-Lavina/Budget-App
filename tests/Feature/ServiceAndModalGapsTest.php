<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AdminManagement;
use App\Http\Livewire\Admin\ExpenseCategories;
use App\Http\Livewire\Admin\UserManagement;
use App\Models\ActivityLog;
use App\Models\Receipt;
use App\Models\RiskLog;
use App\Models\SavingsGoal;
use App\Models\WhatIfSimulation;
use App\Services\BudgetCycleService;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class ServiceAndModalGapsTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // SavingsGoalService::checkAndNotifySavingsMilestone (real notifications)
    // ---------------------------------------------------------------

    private function milestones($user): array
    {
        return DatabaseNotification::where('notifiable_id', $user->id)
            ->get()
            ->map(fn ($n) => $n->data['milestone'] ?? null)
            ->sort()
            ->values()
            ->all();
    }

    private function goal($user, float $saved, float $target = 500): SavingsGoal
    {
        return SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => $target,
            'current_saved' => $saved, 'status' => 'active',
        ]);
    }

    public function test_no_milestone_below_25_percent()
    {
        $user = $this->makeStudent();
        $goal = $this->goal($user, 100); // 20%

        app(SavingsGoalService::class)->checkAndNotifySavingsMilestone($user, $goal);

        $this->assertSame([], $this->milestones($user));
    }

    public function test_each_milestone_is_announced_once_as_progress_grows()
    {
        $user    = $this->makeStudent();
        $goal    = $this->goal($user, 130); // 26%
        $service = app(SavingsGoalService::class);

        $service->checkAndNotifySavingsMilestone($user, $goal->fresh());
        $service->checkAndNotifySavingsMilestone($user, $goal->fresh()); // duplicate call
        $this->assertSame([25], $this->milestones($user));

        $goal->update(['current_saved' => 300]); // 60% => 50
        $service->checkAndNotifySavingsMilestone($user, $goal->fresh());
        $service->checkAndNotifySavingsMilestone($user, $goal->fresh());
        $this->assertSame([25, 50], $this->milestones($user));

        $goal->update(['current_saved' => 400]); // 80% => 75
        $service->checkAndNotifySavingsMilestone($user, $goal->fresh());
        $this->assertSame([25, 50, 75], $this->milestones($user));
    }

    public function test_a_jump_announces_only_the_highest_milestone_reached()
    {
        $user = $this->makeStudent();
        $goal = $this->goal($user, 400); // straight to 80%

        app(SavingsGoalService::class)->checkAndNotifySavingsMilestone($user, $goal);

        $this->assertSame([75], $this->milestones($user));
    }

    public function test_milestones_are_tracked_per_goal()
    {
        $user = $this->makeStudent();
        $one  = $this->goal($user, 130);
        $two  = $this->goal($user, 130);
        $service = app(SavingsGoalService::class);

        $service->checkAndNotifySavingsMilestone($user, $one);
        $service->checkAndNotifySavingsMilestone($user, $two);

        $this->assertSame([25, 25], $this->milestones($user));
    }

    public function test_a_zero_target_goal_is_ignored()
    {
        $user = $this->makeStudent();
        $goal = $this->goal($user, 0, 0);

        app(SavingsGoalService::class)->checkAndNotifySavingsMilestone($user, $goal);

        $this->assertSame([], $this->milestones($user));
    }

    // ---------------------------------------------------------------
    // BudgetCycleService boundaries
    // ---------------------------------------------------------------

    public function test_cycle_boundaries_are_inclusive_of_the_first_and_last_second()
    {
        Notification::fake();
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user);
        $cycle  = app(BudgetCycleService::class);

        $this->assertTrue($cycle->isWithinCurrentCycle($budget, $user, '2026-10-05 00:00:00'));
        $this->assertTrue($cycle->isWithinCurrentCycle($budget, $user, '2026-10-11 23:59:59'));
        $this->assertFalse($cycle->isWithinCurrentCycle($budget, $user, '2026-10-12 00:00:00'));
        $this->assertFalse($cycle->isWithinCurrentCycle($budget, $user, '2026-10-04 23:59:59'));
    }

    public function test_a_non_monday_reset_day_shortens_the_cycle()
    {
        Notification::fake();
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 1000.00, ['reset_day' => 'Friday']);
        $cycle  = app(BudgetCycleService::class);

        $resolved = $cycle->resolve($budget, $user);

        $this->assertSame('Friday', $resolved['targetResetDay']);
        $this->assertSame(2, $resolved['daysRemaining']); // Wed + Thu before Friday's reset
        $this->assertTrue($cycle->isWithinCurrentCycle($budget, $user, '2026-10-08 12:00:00'));
        $this->assertFalse($cycle->isWithinCurrentCycle($budget, $user, '2026-10-09 00:00:00'));
    }

    public function test_read_only_resolution_never_rolls_the_cycle_over()
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-14 12:00:00'); // the cycle that started 10-05 is stale
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00);
        $cycle  = app(BudgetCycleService::class);

        $readOnly = $cycle->resolve($budget, $user, true);

        $this->assertSame('2026-10-05', $readOnly['startDate']->format('Y-m-d'));
        $this->assertSame('2026-10-05', $budget->fresh()->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(400.00, $budget->fresh()->remaining_allowance);

        $cycle->resolve($budget->fresh(), $user, false);

        $this->assertSame('2026-10-12', $budget->fresh()->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(1400.00, $budget->fresh()->remaining_allowance);
    }

    // ---------------------------------------------------------------
    // Model relationships
    // ---------------------------------------------------------------

    public function test_model_relationships_are_wired_up()
    {
        $user    = $this->makeStudent();
        $cat     = $this->makeCategory();
        $goal    = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 500,
            'current_saved' => 0, 'status' => 'active',
        ]);
        $expense = $this->makeExpense($user, $cat, 50.00, '2026-10-06 10:00:00', ['savings_goal_id' => $goal->id]);
        $receipt = Receipt::create([
            'user_id' => $user->id, 'expense_id' => $expense->id,
            'image_path' => 'receipts/x.jpg', 'status' => 'processed',
        ]);
        $risk = RiskLog::create([
            'user_id' => $user->id, 'anomaly_type' => 'rapid_spending',
            'severity_tier' => 'medium', 'description' => 'x',
        ]);
        $log = ActivityLog::create(['user_id' => $user->id, 'event_type' => 'auth_login']);
        $sim = WhatIfSimulation::create([
            'user_id' => $user->id, 'item_title' => 'Shoes', 'amount' => 900, 'simulated_at' => Carbon::now(),
        ]);

        $this->assertSame([$goal->id], $user->savingsGoals->pluck('id')->all());
        $this->assertSame([$expense->id], $user->expenses->pluck('id')->all());
        $this->assertSame([$receipt->id], $user->receipts->pluck('id')->all());
        $this->assertSame([$risk->id], $user->riskLogs->pluck('id')->all());
        $this->assertSame([$log->id], $user->activityLogs->pluck('id')->all());
        $this->assertSame([$sim->id], $user->whatIfSimulations->pluck('id')->all());

        $this->assertSame($goal->id, $expense->savingsGoal->id);
        $this->assertSame($cat->id, $expense->category->id);
        $this->assertSame($receipt->id, $expense->receipt->id);
        $this->assertSame($expense->id, $receipt->expense->id);
        $this->assertSame($user->id, $receipt->user->id);
        $this->assertSame($user->id, $risk->user->id);
        $this->assertSame($user->id, $log->user->id);
        $this->assertSame($user->id, $goal->user->id);
        $this->assertSame($user->id, $sim->user->id);
        $this->assertSame($cat->id, $cat->expenses->first()->expense_category_id);
    }

    // ---------------------------------------------------------------
    // Admin modal state: opening, cancelling and closing change nothing
    // ---------------------------------------------------------------

    public function test_user_management_modals_open_and_cancel_without_side_effects()
    {
        $this->actingAs($this->makeAdmin());
        $student = $this->makeStudent();

        Livewire::test(UserManagement::class)
            ->call('viewUser', $student->id)->assertSet('viewingUserId', $student->id)
            ->call('closeView')->assertSet('viewingUserId', null)
            ->call('openEdit', $student->id)->assertSet('editingUserId', $student->id)
            ->call('closeEdit')->assertSet('editingUserId', null)
            ->call('confirmSuspend', $student->id)->assertSet('confirmingSuspendId', $student->id)
            ->call('cancelSuspend')->assertSet('confirmingSuspendId', null)
            ->call('confirmDelete', $student->id)->assertSet('confirmingDeleteId', $student->id)
            ->call('cancelDelete')->assertSet('confirmingDeleteId', null);

        $this->assertSame('active', $student->fresh()->status);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_category_modals_open_and_cancel_without_side_effects()
    {
        $this->actingAs($this->makeAdmin());
        $category = $this->makeCategory('Keep Me', ['icon' => 'food', 'color' => 'bg-slate-100 text-slate-700']);

        Livewire::test(ExpenseCategories::class)
            ->call('openCreate')->assertSet('showCreateModal', true)
            ->call('closeCreate')->assertSet('showCreateModal', false)
            ->call('openEdit', $category->id)->assertSet('editingId', $category->id)
            ->call('closeEdit')->assertSet('editingId', null)
            ->call('confirmDelete', $category->id)->assertSet('confirmingDeleteId', $category->id)
            ->call('cancelDelete')->assertSet('confirmingDeleteId', null);

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_admin_account_modals_open_and_cancel_without_side_effects()
    {
        $this->actingAs($this->makeAdmin('super_admin'));
        $admin = $this->makeAdmin('admin');

        Livewire::test(AdminManagement::class)
            ->call('openCreate')->assertSet('showCreateModal', true)
            ->call('closeCreate')->assertSet('showCreateModal', false)
            ->call('confirmDemote', $admin->id)->assertSet('confirmingDemoteId', $admin->id)
            ->call('cancelDemote')->assertSet('confirmingDemoteId', null)
            ->call('confirmDelete', $admin->id)->assertSet('confirmingDeleteId', $admin->id)
            ->call('cancelDelete')->assertSet('confirmingDeleteId', null);

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}