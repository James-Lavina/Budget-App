<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\WeeklyBudget;
use App\Notifications\WeeklyBudgetReview;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BudgetCycleServiceTest extends TestCase
{
    use RefreshDatabase;

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

    private function seedCycle(string $startDate, float $remaining = 400.00): array
    {
        $user = User::factory()->create([
            'role'              => 'student',
            'default_allowance' => 1000,
            'default_reset_day' => 'Monday',
        ]);

        $category = ExpenseCategory::create(['name' => 'Food & Dining']);

        $budget = WeeklyBudget::create([
            'user_id'             => $user->id,
            'total_allowance'     => 1000,
            'remaining_allowance' => $remaining,
            'reset_day'           => 'Monday',
            'cycle_start_date'    => $startDate,
        ]);

        Expense::create([
            'user_id'             => $user->id,
            'expense_category_id' => $category->id,
            'item_name'           => 'Lunch',
            'amount'              => 600,
            'transaction_date'    => $startDate . ' 12:00:00',
            'tracking_type'       => 'manual',
        ]);

        return [$user, $budget];
    }

    public function test_rollover_carries_unspent_allowance_and_notifies_once()
    {
        Carbon::setTestNow('2026-10-12 09:00:00'); // Monday
        [$user, $budget] = $this->seedCycle('2026-10-05');

        $service = app(BudgetCycleService::class);
        $service->resolve($budget, $user);
        $service->resolve($budget, $user); // second call must be a no-op

        $budget->refresh();

        $this->assertEquals(1400.00, $budget->remaining_allowance); // 1000 base + 400 unspent
        $this->assertEquals(1000.00, $budget->total_allowance);
        $this->assertSame('2026-10-12', $budget->cycle_start_date->format('Y-m-d'));
        Notification::assertTimesSent(1, WeeklyBudgetReview::class);
    }

    public function test_no_reset_in_the_middle_of_a_cycle()
    {
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday
        [$user, $budget] = $this->seedCycle('2026-10-05');

        app(BudgetCycleService::class)->resolve($budget, $user);

        $budget->refresh();

        $this->assertEquals(400.00, $budget->remaining_allowance);
        $this->assertSame('2026-10-05', $budget->cycle_start_date->format('Y-m-d'));
        Notification::assertNothingSent();
    }

    public function test_stale_cycle_resets_even_when_the_reset_day_was_missed()
    {
        Carbon::setTestNow('2026-10-13 09:00:00'); // Tuesday, 8 days after cycle start
        [$user, $budget] = $this->seedCycle('2026-10-05');

        app(BudgetCycleService::class)->resolve($budget, $user);

        $budget->refresh();

        $this->assertSame('2026-10-12', $budget->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(1400.00, $budget->remaining_allowance);
        Notification::assertTimesSent(1, WeeklyBudgetReview::class);
    }
}