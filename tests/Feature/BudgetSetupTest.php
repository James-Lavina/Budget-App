<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\BudgetSetup;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class BudgetSetupTest extends TestCase
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

    public function test_students_who_finished_setup_are_sent_to_the_dashboard()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $this->actingAs($user);

        Livewire::test(BudgetSetup::class)->assertRedirect(route('student.dashboard'));
    }

    public function test_step_one_rejects_invalid_allowances()
    {
        $this->actingAs($this->makeStudent());

        foreach (['', '0', '100.123', 'abc', '1000000'] as $bad) {
            Livewire::test(BudgetSetup::class)
                ->set('total_allowance', $bad)
                ->call('next')
                ->assertHasErrors(['total_allowance'])
                ->assertSet('step', 1);
        }
    }

    public function test_step_one_rejects_an_invalid_reset_day()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(BudgetSetup::class)
            ->set('total_allowance', '1000')
            ->set('reset_day', 'Funday')
            ->call('next')
            ->assertHasErrors(['reset_day'])
            ->assertSet('step', 1);
    }

    public function test_wizard_moves_forward_and_back()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(BudgetSetup::class)
            ->set('total_allowance', '1000')
            ->call('next')->assertSet('step', 2)
            ->call('next')->assertSet('step', 3)
            ->call('next')->assertSet('step', 4)
            ->call('back')->assertSet('step', 3)
            ->call('back')->assertSet('step', 2)
            ->call('back')->assertSet('step', 1)
            ->call('back')->assertSet('step', 1);
    }

    public function test_finishing_creates_the_budget_the_goal_and_an_audit_entry()
    {
        $user = $this->makeStudent(['default_allowance' => 500, 'default_reset_day' => 'Monday']);
        $this->actingAs($user);

        Livewire::test(BudgetSetup::class)
            ->set('total_allowance', '1500')
            ->set('reset_day', 'Friday')
            ->call('next')
            ->call('next')
            ->set('goal_name', 'Laptop')
            ->set('goal_amount', '500')
            ->call('next')
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $budget = WeeklyBudget::where('user_id', $user->id)->first();
        $this->assertNotNull($budget);
        $this->assertEquals(1500.00, $budget->total_allowance);
        $this->assertEquals(1500.00, $budget->remaining_allowance);
        $this->assertSame('Friday', $budget->reset_day);
        $this->assertSame('2026-10-07', $budget->cycle_start_date->format('Y-m-d'));

        $user->refresh();
        $this->assertEquals(1500.00, $user->default_allowance);
        $this->assertSame('Friday', $user->default_reset_day);

        $goal = SavingsGoal::where('user_id', $user->id)->first();
        $this->assertSame('Laptop', $goal->target_name);
        $this->assertEquals(500.00, $goal->target_amount);
        $this->assertEquals(0.00, $goal->current_saved);
        $this->assertSame('active', $goal->status);

        $this->assertDatabaseHas('activity_logs', [
            'user_id'    => $user->id,
            'event_type' => 'budget_setup_completed',
        ]);
    }

    public function test_the_goal_step_can_be_skipped()
    {
        $user = $this->makeStudent();
        $this->actingAs($user);

        Livewire::test(BudgetSetup::class)
            ->set('total_allowance', '1000')
            ->set('goal_name', 'Half typed')
            ->call('skipGoal')
            ->assertSet('step', 4)
            ->assertSet('goal_name', null)
            ->call('finish')
            ->assertRedirect(route('student.dashboard'));

        $this->assertSame(1, WeeklyBudget::where('user_id', $user->id)->count());
        $this->assertSame(0, SavingsGoal::count());
    }

    public function test_a_half_filled_goal_blocks_the_next_step()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(BudgetSetup::class)
            ->set('step', 3)
            ->set('goal_name', 'Laptop')
            ->call('next')
            ->assertHasErrors(['goal_amount'])
            ->assertSet('step', 3);
    }

    public function test_goal_date_cannot_be_in_the_past()
    {
        $this->actingAs($this->makeStudent());

        Livewire::test(BudgetSetup::class)
            ->set('step', 3)
            ->set('goal_name', 'Laptop')
            ->set('goal_amount', '500')
            ->set('goal_date', '2026-10-01')
            ->call('next')
            ->assertHasErrors(['goal_date']);
    }

    public function test_finish_revalidates_everything_server_side()
    {
        $user = $this->makeStudent();
        $this->actingAs($user);

        Livewire::test(BudgetSetup::class)
            ->set('step', 4)
            ->set('total_allowance', '-5')
            ->call('finish')
            ->assertHasErrors(['total_allowance']);

        $this->assertSame(0, WeeklyBudget::count());
    }

    public function test_a_second_submit_cannot_create_a_duplicate_budget()
    {
        $user = $this->makeStudent();
        $this->actingAs($user);

        $component = Livewire::test(BudgetSetup::class)->set('total_allowance', '1000');

        // Simulates finishing setup in another tab.
        $this->makeBudget($user);

        $component->call('finish')->assertRedirect(route('student.dashboard'));

        $this->assertSame(1, WeeklyBudget::where('user_id', $user->id)->count());
    }

    public function test_first_cycle_preview_accounts_for_a_short_first_week()
    {
        $this->actingAs($this->makeStudent());

        // Today is Wednesday and the reset day is Monday => 5 days.
        Livewire::test(BudgetSetup::class)
            ->set('total_allowance', '1000')
            ->set('reset_day', 'Monday')
            ->assertSee('about ₱200.00 per day')
            ->assertSee('Your first week runs 5 days');
    }
}