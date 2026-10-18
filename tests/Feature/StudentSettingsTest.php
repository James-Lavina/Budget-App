<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\Settings;
use App\Http\Livewire\Student\SimulationWidget;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class StudentSettingsTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(float $remaining = 1000.00): array
    {
        $user   = $this->makeStudent(['default_allowance' => 1000, 'default_reset_day' => 'Monday']);
        $budget = $this->makeBudget($user, $remaining);
        $this->actingAs($user);

        return [$user, $budget];
    }

    // ---------------------------------------------------------------
    // Budget settings
    // ---------------------------------------------------------------

    public function test_the_form_is_prefilled_from_the_students_defaults()
    {
        $this->signIn();

        Livewire::test(Settings::class)
            ->assertSet('total_allowance', 1000)
            ->assertSet('reset_day', 'Monday')
            ->assertSet('update_current_week', false);
    }

    public function test_by_default_changes_only_apply_to_future_cycles()
    {
        [$user, $budget] = $this->signIn();

        Livewire::test(Settings::class)
            ->set('total_allowance', '1500')
            ->set('reset_day', 'Friday')
            ->call('updateSettings')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals(1500.00, $user->default_allowance);
        $this->assertSame('Friday', $user->default_reset_day);

        $budget->refresh();
        $this->assertEquals(1000.00, $budget->total_allowance);
        $this->assertEquals(1000.00, $budget->remaining_allowance);
        $this->assertSame('Monday', $budget->reset_day);

        $log = \App\Models\ActivityLog::where('event_type', 'budget_settings_updated')->first();
        $this->assertStringContainsString('weekly allowance: ₱1,000.00 → ₱1,500.00', $log->details);
        $this->assertStringContainsString('reset day: Monday → Friday', $log->details);
        $this->assertStringNotContainsString('applied immediately', $log->details);
    }

    public function test_the_new_allowance_becomes_the_baseline_at_the_next_reset()
    {
        [$user, $budget] = $this->signIn();

        Livewire::test(Settings::class)
            ->set('total_allowance', '1500')
            ->call('updateSettings');

        Carbon::setTestNow('2026-10-12 09:00:00'); // Monday: reset day
        app(BudgetCycleService::class)->resolve($budget->fresh(), $user->fresh());

        $budget->refresh();
        $this->assertEquals(1500.00, $budget->total_allowance);
        $this->assertEquals(2500.00, $budget->remaining_allowance); // 1500 base + 1000 unspent
    }

    public function test_applying_to_the_current_week_adjusts_the_remaining_balance()
    {
        [$user, $budget] = $this->signIn(600.00); // 400 already spent

        Livewire::test(Settings::class)
            ->set('total_allowance', '1500')
            ->set('update_current_week', true)
            ->call('updateSettings')
            ->assertHasNoErrors()
            ->assertSet('update_current_week', false);

        $budget->refresh();
        $this->assertEquals(1500.00, $budget->total_allowance);
        $this->assertEquals(1100.00, $budget->remaining_allowance); // 600 + 500 difference

        $log = \App\Models\ActivityLog::where('event_type', 'budget_settings_updated')->first();
        $this->assertStringContainsString('applied immediately to current week', $log->details);
    }

    public function test_lowering_the_allowance_never_drives_the_balance_negative()
    {
        [$user, $budget] = $this->signIn(100.00);

        Livewire::test(Settings::class)
            ->set('total_allowance', '800')
            ->set('update_current_week', true)
            ->call('updateSettings');

        $budget->refresh();
        $this->assertEquals(800.00, $budget->total_allowance);
        $this->assertEquals(0.00, $budget->remaining_allowance);
    }

    public function test_validation()
    {
        $this->signIn();

        foreach (['', '0', '10.123', '1000000', 'abc'] as $bad) {
            Livewire::test(Settings::class)
                ->set('total_allowance', $bad)
                ->call('updateSettings')
                ->assertHasErrors(['total_allowance']);
        }

        Livewire::test(Settings::class)
            ->set('reset_day', 'Funday')
            ->call('updateSettings')
            ->assertHasErrors(['reset_day']);
    }

    public function test_an_unchanged_save_writes_no_audit_entry()
    {
        $this->signIn();

        Livewire::test(Settings::class)->call('updateSettings')->assertHasNoErrors();

        $this->assertSame(0, \App\Models\ActivityLog::where('event_type', 'budget_settings_updated')->count());
    }

    // ---------------------------------------------------------------
    // Quick "what-if" widget
    // ---------------------------------------------------------------

    public function test_the_quick_widget_sends_the_amount_to_the_simulator()
    {
        $this->signIn();

        Livewire::test(SimulationWidget::class)
            ->set('quickAmount', '250')
            ->call('calculateImpact')
            ->assertRedirect(route('student.simulation', [
                'scenarioType'   => 'major_purchase',
                'purchaseAmount' => '250',
            ]));
    }

    public function test_the_quick_widget_validates_the_amount()
    {
        $this->signIn();

        foreach (['', '0', 'abc', '50001'] as $bad) {
            Livewire::test(SimulationWidget::class)
                ->set('quickAmount', $bad)
                ->call('calculateImpact')
                ->assertHasErrors(['quickAmount']);
        }
    }

    public function test_the_simulator_runs_immediately_when_opened_from_the_widget()
    {
        $this->signIn();
        Http::fake(['api.groq.com/*' => Http::response([], 500)]);

        $this->get('/simulation?scenarioType=major_purchase&purchaseAmount=250')
            ->assertOk()
            ->assertSee('Quick Tested Item')
            ->assertSee('₱250.00');
    }
}