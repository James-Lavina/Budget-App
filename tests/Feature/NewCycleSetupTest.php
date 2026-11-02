<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\NewCycleSetup;
use App\Notifications\WeeklyBudgetReview;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class NewCycleSetupTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_resolve_never_resets_the_cycle()
    {
        Carbon::setTestNow('2026-10-12 09:00:00'); // Monday, reset day
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00);

        app(BudgetCycleService::class)->resolve($budget, $user);

        $budget->refresh();
        $this->assertSame('2026-10-05', $budget->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(400.00, $budget->remaining_allowance);
        Notification::assertNothingSent();
    }

    public function test_students_with_an_ended_cycle_are_redirected_to_the_new_cycle_page()
    {
        Carbon::setTestNow('2026-10-12 09:00:00');
        $user = $this->makeStudent();
        $this->makeBudget($user, 400.00);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('student.new-cycle'));
        $this->actingAs($user)->get('/new-cycle')->assertOk();
    }

    public function test_the_page_redirects_to_the_dashboard_when_the_cycle_is_still_active()
    {
        Carbon::setTestNow('2026-10-07 12:00:00');
        $user = $this->makeStudent();
        $this->makeBudget($user, 400.00);
        $this->actingAs($user);

        Livewire::test(NewCycleSetup::class)->assertRedirect(route('student.dashboard'));
    }

    public function test_confirming_sets_the_entered_allowance_and_adds_the_unspent_balance()
    {
        Carbon::setTestNow('2026-10-12 09:00:00');
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00, ['added_funds' => 200]);
        $this->actingAs($user);

        Livewire::test(NewCycleSetup::class)
            ->assertSet('total_allowance', '1000.00')
            ->set('total_allowance', '1200')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $budget->refresh();
        $this->assertEquals(1200.00, $budget->total_allowance);
        $this->assertEquals(1600.00, $budget->remaining_allowance); // 1200 + 400 unspent
        $this->assertEquals(0.00, $budget->added_funds);
        $this->assertSame('2026-10-12', $budget->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(1000.00, $user->fresh()->default_allowance); // default untouched

        Notification::assertSentTo($user, WeeklyBudgetReview::class);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'weekly_cycle_started']);
    }

    public function test_a_stale_cycle_is_realigned_to_the_last_reset_day()
    {
        Carbon::setTestNow('2026-10-13 09:00:00'); // Tuesday, reset day was missed
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00);
        $this->actingAs($user);

        Livewire::test(NewCycleSetup::class)->set('total_allowance', '1000')->call('confirm');

        $this->assertSame('2026-10-12', $budget->fresh()->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(1400.00, $budget->fresh()->remaining_allowance);
    }

    public function test_a_second_submit_does_not_roll_over_twice()
    {
        Carbon::setTestNow('2026-10-12 09:00:00');
        $user   = $this->makeStudent();
        $budget = $this->makeBudget($user, 400.00);

        $service = app(BudgetCycleService::class);
        $this->assertNotNull($service->rollover($budget, $user, 1000.00));
        $this->assertNull($service->rollover($budget->fresh(), $user, 1000.00));

        $this->assertEquals(1400.00, $budget->fresh()->remaining_allowance);
        Notification::assertTimesSent(1, WeeklyBudgetReview::class);
    }

    public function test_allowance_validation()
    {
        Carbon::setTestNow('2026-10-12 09:00:00');
        $user = $this->makeStudent();
        $this->makeBudget($user, 400.00);
        $this->actingAs($user);

        foreach (['', '0', '10.123', 'abc', '1000000'] as $bad) {
            Livewire::test(NewCycleSetup::class)
                ->set('total_allowance', $bad)
                ->call('confirm')
                ->assertHasErrors(['total_allowance']);
        }
    }
}