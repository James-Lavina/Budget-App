<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\AddBudgetFunds;
use App\Http\Livewire\Student\Dashboard;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class AddBudgetFundsTest extends TestCase
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

    public function test_top_up_raises_remaining_and_is_tracked_separately()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 700.00);
        $this->actingAs($user);

        Livewire::test(AddBudgetFunds::class)
            ->set('amount', '250.50')
            ->call('addFunds')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $budget->refresh();
        $this->assertEquals(950.50, $budget->remaining_allowance);
        $this->assertEquals(250.50, $budget->added_funds);
        $this->assertEquals(1000.00, $budget->total_allowance);
        $this->assertDatabaseHas('activity_logs', [
            'user_id'    => $user->id,
            'event_type' => 'budget_funds_added',
        ]);
    }

    public function test_multiple_top_ups_accumulate()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 500.00);
        $this->actingAs($user);

        $component = Livewire::test(AddBudgetFunds::class);
        $component->set('amount', '100')->call('addFunds');
        $component->set('amount', '50')->call('addFunds');

        $budget->refresh();
        $this->assertEquals(650.00, $budget->remaining_allowance);
        $this->assertEquals(150.00, $budget->added_funds);
    }

    public function test_top_up_is_not_displayed_as_rollover_on_the_dashboard()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);
        $this->actingAs($user);

        Livewire::test(AddBudgetFunds::class)
            ->set('amount', '500')
            ->call('addFunds');

        Livewire::test(Dashboard::class)
            ->assertViewHas('rolloverAmount', 0)
            ->assertDontSee('rolled over from last week');
    }

    public function test_genuine_rollover_is_still_displayed()
    {
        $user = $this->makeStudent();
        // State right after a reset: 1000 base + 400 carried over, no top-ups.
        $this->makeBudget($user, 1400.00);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertViewHas('rolloverAmount', 400)
            ->assertSee('rolled over from last week');
    }

    public function test_rollover_and_top_up_together_show_only_the_rollover()
    {
        $user = $this->makeStudent();
        // 1000 base + 400 rollover + 300 top-up = 1700 remaining.
        $this->makeBudget($user, 1700.00, ['added_funds' => 300]);
        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertViewHas('rolloverAmount', 400);
    }

    public function test_cycle_reset_clears_added_funds_and_carries_over_the_unspent_balance()
    {
        Carbon::setTestNow('2026-10-12 09:00:00'); // Monday, reset day

        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00, ['added_funds' => 200]);

        app(BudgetCycleService::class)->resolve($budget, $user);

        $budget->refresh();
        $this->assertEquals(0.00, $budget->added_funds);
        $this->assertEquals(1400.00, $budget->remaining_allowance); // 1000 base + 400 unspent
        $this->assertEquals(1000.00, $budget->total_allowance);
        $this->assertSame('2026-10-12', $budget->cycle_start_date->format('Y-m-d'));
    }

    public function test_invalid_amounts_are_rejected_and_nothing_changes()
    {
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 500.00);
        $this->actingAs($user);

        foreach (['', '0', '-5', '10.123', 'abc', '10001'] as $bad) {
            Livewire::test(AddBudgetFunds::class)
                ->set('amount', $bad)
                ->call('addFunds')
                ->assertHasErrors(['amount']);
        }

        $budget->refresh();
        $this->assertEquals(500.00, $budget->remaining_allowance);
        $this->assertEquals(0.00, $budget->added_funds);
    }

    public function test_top_up_ceiling_scales_with_the_students_allowance()
    {
        $user   = $this->makeStudent(['default_allowance' => 3000]); // ceiling = 15000
        $budget = $this->makeBudget($user, 100.00);
        $this->actingAs($user);

        Livewire::test(AddBudgetFunds::class)
            ->set('amount', '12000')
            ->call('addFunds')
            ->assertHasNoErrors();

        $this->assertEquals(12100.00, $budget->fresh()->remaining_allowance);
    }

    public function test_top_up_without_a_budget_is_refused()
    {
        $user = $this->makeStudent();
        $this->actingAs($user);

        Livewire::test(AddBudgetFunds::class)
            ->set('amount', '100')
            ->call('addFunds')
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseCount('weekly_budgets', 0);
    }
}