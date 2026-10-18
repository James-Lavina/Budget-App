<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\WhatIfSimulator;
use App\Models\IntegrationSetting;
use App\Models\SavingsGoal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class WhatIfSimulatorTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wed: 5 days left until Mon 10-12
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Forces the offline (local) insight path regardless of any key in .env. */
    private function offline(): void
    {
        Http::fake(['api.groq.com/*' => Http::response([], 500)]);
    }

    private function signIn(float $remaining = 1000.00)
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, $remaining);
        $this->actingAs($user);

        return $user;
    }

    private function simulate(string $amount, string $name = '')
    {
        return Livewire::test(WhatIfSimulator::class)
            ->set('itemName', $name)
            ->set('purchaseAmount', $amount)
            ->call('simulate');
    }

    public function test_student_without_a_budget_sees_the_setup_prompt()
    {
        $user = $this->makeStudent();
        $this->actingAs($user);

        Livewire::test(WhatIfSimulator::class)->assertSet('hasBudget', false);
    }

    public function test_overview_cards_match_the_dashboard_formula()
    {
        $this->offline();
        $this->signIn();

        Livewire::test(WhatIfSimulator::class)
            ->assertSet('hasBudget', true)
            ->assertSet('daysRemaining', 5)
            ->assertSet('remainingBudget', 1000)
            ->assertSet('currentDailyQuota', 200)
            ->assertSet('purchaseCeiling', 6000);
    }

    public function test_low_risk_purchase()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('100', 'Milk Tea')
            ->assertSet('hasSimulated', true)
            ->assertSet('simulatedItem', 'Milk Tea')
            ->assertSet('simulatedAmount', 100)
            ->assertSet('newRemaining', 900)
            ->assertSet('newDailyQuota', 180)
            ->assertSet('dailyQuotaDrop', 20)
            ->assertSet('percentBefore', 100)
            ->assertSet('percentAfter', 90)
            ->assertSet('riskLevel', 'low')
            ->assertSet('isOfflineMode', true)
            ->assertSee('comfortably afford');
    }

    public function test_medium_risk_when_the_daily_budget_collapses()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('800')
            ->assertSet('riskLevel', 'medium')
            ->assertSet('simulatedItem', 'Planned purchase');
    }

    public function test_high_risk_when_almost_everything_is_spent()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('950')->assertSet('riskLevel', 'high');
    }

    public function test_deficit_purchase_is_flagged_and_cannot_be_added_as_an_expense()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('1500')
            ->assertSet('isDeficit', true)
            ->assertSet('riskLevel', 'high')
            ->assertSet('newDailyQuota', 0)
            ->assertSee("can't afford this yet")
            ->call('addAsExpense')
            ->assertNoRedirect();
    }

    public function test_spending_exactly_the_remaining_budget_is_critical_not_a_deficit()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('1000')
            ->assertSet('isDeficit', false)
            ->assertSet('isCriticalZero', true)
            ->assertSet('riskLevel', 'high');
    }

    public function test_amount_validation()
    {
        $this->offline();
        $this->signIn();

        foreach (['', '0', 'abc', '6001'] as $bad) {
            $this->simulate($bad)->assertHasErrors(['purchaseAmount']);
        }
    }

    public function test_thousands_separators_are_accepted()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('1,000')->assertSet('simulatedAmount', 1000);
    }

    public function test_quick_pick_presets_run_a_simulation()
    {
        $this->offline();
        $this->signIn();

        Livewire::test(WhatIfSimulator::class)
            ->call('applyPreset', 50, 'Milk Tea')
            ->assertSet('hasSimulated', true)
            ->assertSet('simulatedItem', 'Milk Tea');
    }

    public function test_cancel_resets_the_simulation()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('100', 'Shoes')
            ->call('resetSimulation')
            ->assertSet('hasSimulated', false)
            ->assertSet('itemName', '')
            ->assertSet('purchaseAmount', '');
    }

    public function test_add_as_expense_redirects_with_the_simulated_values()
    {
        $this->offline();
        $this->signIn();

        $this->simulate('100', 'Milk Tea')
            ->call('addAsExpense')
            ->assertRedirect(route('student.expenses.create', ['item' => 'Milk Tea', 'amount' => 100.0]));
    }

    public function test_savings_impact_reflects_the_size_of_the_purchase()
    {
        $this->offline();
        $user = $this->signIn();

        $this->simulate('100')->assertSet('savingsImpact', 'No active goal');

        SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 5000,
            'current_saved' => 100, 'status' => 'active',
        ]);

        $this->simulate('100')->assertSet('savingsImpact', 'Barely affected');
        $this->simulate('400')->assertSet('savingsImpact', 'Slightly slower');
        $this->simulate('700')->assertSet('savingsImpact', 'Much slower');
        $this->simulate('1500')->assertSet('savingsImpact', 'Goal at risk');
    }

    public function test_ai_advice_is_used_and_logged_when_groq_answers()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();
        $user = $this->signIn();

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message'       => ['content' => 'You can afford this and still stay on budget.'],
                    'finish_reason' => 'stop',
                ]],
            ], 200),
        ]);

        $this->simulate('100', 'Milk Tea')
            ->assertSet('aiInsight', 'You can afford this and still stay on budget.')
            ->assertSet('isOfflineMode', false);

        $this->assertDatabaseHas('activity_logs', [
            'user_id'    => $user->id,
            'event_type' => 'ai_simulation_requested',
        ]);
    }

    public function test_truncated_ai_answers_are_discarded_for_the_offline_message()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();
        $this->signIn();

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message'       => ['content' => 'You can afford this but'],
                    'finish_reason' => 'length',
                ]],
            ], 200),
        ]);

        $this->simulate('100')->assertSet('isOfflineMode', true);
    }
}