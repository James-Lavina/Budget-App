<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\GoalsManager;
use App\Models\Expense;
use App\Models\SavingsGoal;
use App\Notifications\SavingsGoalAchieved;
use App\Notifications\SavingsMilestoneReached;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class GoalsManagerTest extends TestCase
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

    private function makeGoal($user, array $attributes = []): SavingsGoal
    {
        return SavingsGoal::create(array_merge([
            'user_id'       => $user->id,
            'target_name'   => 'Exam fees',
            'target_amount' => 500,
            'current_saved' => 0,
            'status'        => 'active',
        ], $attributes));
    }

    public function test_creating_a_goal()
    {
        [$user] = $this->signIn();

        Livewire::test(GoalsManager::class)
            ->call('openCreateModal')
            ->set('target_name', 'Concert tickets')
            ->set('target_amount', '800')
            ->set('target_date', '2026-12-01')
            ->call('storeGoal')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $goal = SavingsGoal::where('user_id', $user->id)->first();
        $this->assertSame('Concert tickets', $goal->target_name);
        $this->assertSame('active', $goal->status);
        $this->assertEquals(0.00, $goal->current_saved);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'savings_goal_created']);
    }

    public function test_a_goal_that_starts_fully_funded_is_achieved_and_notifies()
    {
        [$user] = $this->signIn();

        Livewire::test(GoalsManager::class)
            ->set('target_name', 'Already there')
            ->set('target_amount', '500')
            ->set('already_saved', '900')
            ->call('storeGoal');

        $goal = SavingsGoal::first();
        $this->assertSame('achieved', $goal->status);
        $this->assertEquals(500.00, $goal->current_saved); // clamped to the target
        Notification::assertSentTo($user, SavingsGoalAchieved::class);
    }

    public function test_goal_validation()
    {
        $this->signIn();

        Livewire::test(GoalsManager::class)
            ->set('target_name', '')
            ->set('target_amount', '0')
            ->set('target_date', '2026-10-01')
            ->call('storeGoal')
            ->assertHasErrors(['target_name', 'target_amount', 'target_date']);

        $this->assertSame(0, SavingsGoal::count());
    }

    public function test_funding_a_goal_through_the_component()
    {
        [$user, $budget] = $this->signIn();
        $goal = $this->makeGoal($user);

        Livewire::test(GoalsManager::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '200')
            ->call('addFunds')
            ->assertHasNoErrors()
            ->assertSet('fundingGoalId', null);

        $this->assertEquals(200.00, $goal->fresh()->current_saved);
        $this->assertEquals(800.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseHas('expenses', ['savings_goal_id' => $goal->id, 'amount' => 200.00]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'savings_goal_funded']);
    }

    public function test_funding_more_than_the_goal_needs_is_rejected()
    {
        [$user, $budget] = $this->signIn();
        $goal = $this->makeGoal($user);

        Livewire::test(GoalsManager::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '600')
            ->call('addFunds')
            ->assertHasErrors(['fund_amount']);

        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    public function test_funding_more_than_the_remaining_budget_is_rejected()
    {
        [$user, $budget] = $this->signIn(100.00);
        $goal = $this->makeGoal($user);

        Livewire::test(GoalsManager::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '200')
            ->call('addFunds')
            ->assertHasErrors(['fund_amount']);

        $this->assertEquals(100.00, $budget->fresh()->remaining_allowance);
    }

    public function test_fund_amount_allows_at_most_two_decimals()
    {
        [$user] = $this->signIn();
        $goal = $this->makeGoal($user);

        Livewire::test(GoalsManager::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '10.123')
            ->call('addFunds')
            ->assertHasErrors(['fund_amount']);
    }

    public function test_an_achieved_goal_cannot_receive_more_funds()
    {
        [$user] = $this->signIn();
        $goal = $this->makeGoal($user, ['current_saved' => 500, 'status' => 'achieved']);

        Livewire::test(GoalsManager::class)
            ->call('openFundingModal', $goal->id)
            ->set('fund_amount', '50')
            ->call('addFunds')
            ->assertHasErrors(['fund_amount']);
    }

    public function test_milestone_notification_is_sent_at_25_percent()
    {
        [$user] = $this->signIn();
        $goal = $this->makeGoal($user);

        app(SavingsGoalService::class)->addFunds($user, $goal, 130.00); // 26%

        Notification::assertSentTo($user, SavingsMilestoneReached::class, fn ($n) => $n->milestone === 25);
    }

    public function test_completing_a_goal_sends_the_achievement_notification()
    {
        [$user] = $this->signIn();
        $goal = $this->makeGoal($user);

        $result = app(SavingsGoalService::class)->addFunds($user, $goal, 500.00);

        $this->assertTrue($result['goalWasAchieved']);
        Notification::assertSentTo($user, SavingsGoalAchieved::class);
    }

    public function test_archive_and_restore()
    {
        [$user] = $this->signIn();
        $goal = $this->makeGoal($user);

        Livewire::test(GoalsManager::class)
            ->call('abandonGoal', $goal->id)
            ->assertSet('confirmingAbandonId', $goal->id)
            ->call('executeAbandon')
            ->assertSet('confirmingAbandonId', null);

        $this->assertSame('abandoned', $goal->fresh()->status);

        Livewire::test(GoalsManager::class)->call('unarchiveGoal', $goal->id);

        $this->assertSame('active', $goal->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'savings_goal_archived']);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'savings_goal_unarchived']);
    }

    public function test_deleting_an_active_goal_refunds_what_was_saved()
    {
        [$user, $budget] = $this->signIn();
        $goal = $this->makeGoal($user);
        app(SavingsGoalService::class)->addFunds($user, $goal, 200.00);
        $this->assertEquals(800.00, $budget->fresh()->remaining_allowance);

        Livewire::test(GoalsManager::class)
            ->call('deleteGoal', $goal->id)
            ->call('executeDelete');

        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
        $this->assertSame(0, Expense::where('savings_goal_id', $goal->id)->count());
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'savings_goal_deleted']);
    }

    public function test_deleting_an_achieved_goal_does_not_refund_the_money()
    {
        [$user, $budget] = $this->signIn();
        $goal = $this->makeGoal($user);
        app(SavingsGoalService::class)->addFunds($user, $goal, 500.00);
        $this->assertSame('achieved', $goal->fresh()->status);

        Livewire::test(GoalsManager::class)
            ->set('confirmingDeleteId', $goal->id)
            ->call('executeDelete');

        $this->assertEquals(500.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
    }

    public function test_students_cannot_touch_another_students_goal()
    {
        $this->signIn();
        $other     = $this->makeStudent();
        $otherGoal = $this->makeGoal($other);

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(GoalsManager::class)
            ->set('confirmingAbandonId', $otherGoal->id)
            ->call('executeAbandon');
    }

    public function test_tab_counts()
    {
        [$user] = $this->signIn();
        $this->makeGoal($user);
        $this->makeGoal($user, ['target_name' => 'Done', 'current_saved' => 500, 'status' => 'achieved']);
        $this->makeGoal($user, ['target_name' => 'Old', 'status' => 'abandoned']);
        $this->makeGoal($this->makeStudent(), ['target_name' => 'Someone else']);

        Livewire::test(GoalsManager::class)
            ->assertViewHas('counts', ['active' => 1, 'achieved' => 1, 'abandoned' => 1])
            ->assertSee('Exam fees')
            ->assertDontSee('Someone else');
    }
}