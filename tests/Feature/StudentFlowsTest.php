<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\LogExpense;
use App\Http\Livewire\Student\NotificationIndex;
use App\Http\Livewire\Student\Profile;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\WeeklyBudget;
use App\Notifications\WeeklyBudgetReview;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday, mid-cycle
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function studentWithBudget(float $remaining = 1000.00): array
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
            'cycle_start_date'    => '2026-10-05',
        ]);

        return [$user, $budget, $category];
    }

    public function test_logging_an_expense_deducts_the_budget()
    {
        [$user, $budget, $category] = $this->studentWithBudget();
        $this->actingAs($user);

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Test item')
            ->set('expense_category_id', $category->id)
            ->set('amount', '150.50')
            ->set('transaction_date', '2026-10-07')
            ->call('storeExpense')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $this->assertEquals(849.50, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'amount' => 150.50]);
    }

    public function test_expense_with_more_than_two_decimals_is_rejected()
    {
        [$user, $budget, $category] = $this->studentWithBudget();
        $this->actingAs($user);

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Test item')
            ->set('expense_category_id', $category->id)
            ->set('amount', '10.123')
            ->set('transaction_date', '2026-10-07')
            ->call('storeExpense')
            ->assertHasErrors(['amount']);

        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_expense_larger_than_remaining_allowance_is_rejected()
    {
        [$user, $budget, $category] = $this->studentWithBudget(100.00);
        $this->actingAs($user);

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Test item')
            ->set('expense_category_id', $category->id)
            ->set('amount', '500')
            ->set('transaction_date', '2026-10-07')
            ->call('storeExpense')
            ->assertHasErrors(['amount']);

        $this->assertEquals(100.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_funding_a_goal_moves_money_and_completes_it_at_target()
    {
        [$user, $budget] = $this->studentWithBudget();

        $goal = SavingsGoal::create([
            'user_id'       => $user->id,
            'target_name'   => 'Exam fees',
            'target_amount' => 500,
            'current_saved' => 0,
            'status'        => 'active',
        ]);

        $service = app(SavingsGoalService::class);

        $first = $service->addFunds($user, $goal, 200.00);
        $this->assertFalse($first['goalWasAchieved']);
        $this->assertEquals(800.00, $budget->fresh()->remaining_allowance);
        $this->assertEquals(200.00, $goal->fresh()->current_saved);
        $this->assertDatabaseHas('expenses', ['savings_goal_id' => $goal->id, 'amount' => 200.00]);

        $second = $service->addFunds($user, $goal->fresh(), 300.00);
        $this->assertTrue($second['goalWasAchieved']);
        $this->assertSame('achieved', $goal->fresh()->status);
        $this->assertEquals(500.00, $budget->fresh()->remaining_allowance);
    }

    public function test_archived_goal_cannot_be_funded()
    {
        [$user, $budget] = $this->studentWithBudget();

        $goal = SavingsGoal::create([
            'user_id'       => $user->id,
            'target_name'   => 'Old goal',
            'target_amount' => 500,
            'current_saved' => 0,
            'status'        => 'abandoned',
        ]);

        try {
            app(SavingsGoalService::class)->addFunds($user, $goal, 100.00);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fund_amount', $e->errors());
        }

        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    public function test_active_alerts_cannot_be_deleted_but_resolved_ones_can()
    {
        [$user] = $this->studentWithBudget();

        $make = function (bool $resolved) use ($user) {
            return DatabaseNotification::create([
                'id'              => (string) Str::uuid(),
                'type'            => 'App\Notifications\LowAllowanceWarning',
                'notifiable_type' => User::class,
                'notifiable_id'   => $user->id,
                'data'            => [
                    'anomaly_type'  => 'low_allowance_threshold',
                    'severity_tier' => 'medium',
                    'description'   => 'Low allowance',
                    'resolved'      => $resolved,
                ],
            ]);
        };

        $active   = $make(false);
        $resolved = $make(true);

        $this->actingAs($user);

        Livewire::test(NotificationIndex::class)
            ->call('delete', $active->id)
            ->call('delete', $resolved->id);

        $this->assertNotNull(DatabaseNotification::find($active->id));
        $this->assertNotNull(DatabaseNotification::find($active->id)->read_at);
        $this->assertNull(DatabaseNotification::find($resolved->id));
    }

    public function test_students_can_opt_out_of_email_alerts()
    {
        [$user] = $this->studentWithBudget();
        $review = new WeeklyBudgetReview(100.0, 50.0);

        $user->update(['email_notifications_enabled' => false]);
        $this->assertSame(['database'], $review->via($user->fresh()));

        $user->update(['email_notifications_enabled' => true]);
        $this->assertSame(['database', 'mail'], $review->via($user->fresh()));
    }

    public function test_admins_are_redirected_away_from_student_pages()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_data_export_is_logged()
    {
        [$user] = $this->studentWithBudget();
        $this->actingAs($user);

        Livewire::test(Profile::class)->call('exportData');

        $this->assertDatabaseHas('activity_logs', [
            'user_id'    => $user->id,
            'event_type' => 'data_exported',
        ]);
    }

    public function test_account_deletion_requires_the_password_and_removes_all_data()
    {
        [$user, $budget, $category] = $this->studentWithBudget();

        Expense::create([
            'user_id'             => $user->id,
            'expense_category_id' => $category->id,
            'item_name'           => 'Lunch',
            'amount'              => 80,
            'transaction_date'    => '2026-10-06 12:00:00',
            'tracking_type'       => 'manual',
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('delete_password', 'wrong-password')
            ->call('deleteAccount')
            ->assertHasErrors(['delete_password']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        Livewire::test(Profile::class)
            ->set('delete_password', 'password') // UserFactory default
            ->call('deleteAccount')
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('weekly_budgets', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'account_deleted', 'user_id' => null]);
    }
}