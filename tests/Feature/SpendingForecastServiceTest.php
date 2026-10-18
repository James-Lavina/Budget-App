<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\IntegrationSetting;
use App\Services\SpendingForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class SpendingForecastServiceTest extends TestCase
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

    private function forecast($user): array
    {
        return app(SpendingForecastService::class)->computeLocalForecast($user);
    }

    public function test_returns_an_error_when_there_is_no_budget()
    {
        $result = $this->forecast($this->makeStudent());

        $this->assertSame('error', $result['status']);
    }

    /** Requires fix #2 (cycle length instead of hardcoded 7). */
    public function test_short_first_cycle_uses_its_real_length()
    {
        $user = $this->makeStudent();
        // Cycle started today (Wed) and ends Mon 2026-10-12 => 5 days.
        $this->makeBudget($user, 900.00, ['cycle_start_date' => '2026-10-07']);
        $this->makeExpense($user, $this->makeCategory(), 100.00, '2026-10-07 09:00:00');

        $result = $this->forecast($user);

        $this->assertSame('success', $result['status']);
        $this->assertSame(['Wed', 'Thu', 'Fri', 'Sat', 'Sun'], $result['chart']['labels']);
        $this->assertCount(5, $result['chart']['actual']);
        $this->assertCount(5, $result['chart']['projected']);
        $this->assertEquals([900, 800, 700, 600, 500], $result['chart']['projected']);
        $this->assertEquals(900, $result['chart']['actual'][0]);
        $this->assertNull($result['chart']['actual'][1]);
        $this->assertSame(5, $result['metrics']['days_left']);
        $this->assertEquals(200.00, $result['metrics']['safe_per_day']); // (900 + 100) / 5
    }

    public function test_full_cycle_has_seven_points()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);

        $result = $this->forecast($user);

        $this->assertCount(7, $result['chart']['labels']);
        $this->assertSame('Mon', $result['chart']['labels'][0]);
    }

    public function test_fresh_week_with_no_spending()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);

        $result = $this->forecast($user);

        $this->assertSame('fresh_start', $result['metrics']['state']);
    }

    public function test_on_track_when_pace_fits_the_remaining_budget()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 850.00);
        $this->makeExpense($user, $this->makeCategory(), 150.00, '2026-10-06 12:00:00');

        $result = $this->forecast($user);

        $this->assertSame('on_track', $result['metrics']['state']);
        $this->assertEquals(50.00, $result['metrics']['pace']); // 150 / 3 days
    }

    public function test_projects_the_day_the_money_runs_out()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 100.00);
        $this->makeExpense($user, $this->makeCategory(), 900.00, '2026-10-06 12:00:00');

        $result = $this->forecast($user);

        $this->assertSame('runs_out', $result['metrics']['state']);
        $this->assertSame('Thursday', $result['metrics']['runs_out_label']);
        $this->assertStringContainsString('Thursday', $result['text']['headline']);
    }

    public function test_depleted_budget()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 0.00);

        $this->assertSame('depleted', $this->forecast($user)['metrics']['state']);
    }

    public function test_final_day_has_no_projection()
    {
        Carbon::setTestNow('2026-10-11 12:00:00'); // Sunday, last day of the cycle

        $user = $this->makeStudent();
        $this->makeBudget($user, 300.00);
        $this->makeExpense($user, $this->makeCategory(), 700.00, '2026-10-09 12:00:00');

        $result = $this->forecast($user);

        $this->assertSame('final_day', $result['metrics']['state']);
        $this->assertTrue($result['metrics']['is_final_day']);
        $this->assertSame([], array_filter($result['chart']['projected'], fn ($v) => $v !== null));
    }

    public function test_savings_transfers_do_not_count_as_pace()
    {
        $user = $this->makeStudent();
        $this->makeBudget($user, 700.00);
        $savings = $this->makeCategory('Savings');
        $goal = \App\Models\SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 5000,
            'current_saved' => 300, 'status' => 'active',
        ]);
        $this->makeExpense($user, $savings, 300.00, '2026-10-06 12:00:00', ['savings_goal_id' => $goal->id]);

        $result = $this->forecast($user);

        $this->assertEquals(0.0, $result['metrics']['pace']);
        $this->assertSame('fresh_start', $result['metrics']['state']);
    }

    public function test_ai_tips_are_returned_logged_and_cached()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Eat at carinderias.|Share tricycle fares.|Bring baon from home.']]],
            ], 200),
        ]);

        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);
        $service  = app(SpendingForecastService::class);
        $forecast = $service->computeLocalForecast($user);

        $first  = $service->fetchAiInsight($user, $forecast);
        $second = $service->fetchAiInsight($user, $forecast);

        $this->assertTrue($first['is_online']);
        $this->assertCount(3, $first['tips']);
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
        $this->assertSame(1, ActivityLog::where('event_type', 'ai_forecast_requested')->count());
    }

    public function test_ai_failure_falls_back_to_local_tips_without_logging()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_test']);
        IntegrationSetting::flush();

        Http::fake(['api.groq.com/*' => Http::response([], 500)]);

        $user = $this->makeStudent();
        $this->makeBudget($user, 1000.00);
        $service  = app(SpendingForecastService::class);
        $forecast = $service->computeLocalForecast($user);

        $result = $service->fetchAiInsight($user, $forecast);

        $this->assertFalse($result['is_online']);
        $this->assertCount(3, $result['tips']);
        $this->assertSame(0, ActivityLog::where('event_type', 'ai_forecast_requested')->count());
    }
}