<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\AllExpenses;
use App\Http\Livewire\Student\EditExpense;
use App\Models\Expense;
use App\Models\SavingsGoal;
use App\Notifications\SavingsGoalAchieved;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-07 12:00:00'); // cycle: Mon 2026-10-05 .. Sun 2026-10-11
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(float $remaining = 1000.00): array
    {
        $user     = $this->makeStudent();
        $budget   = $this->makeBudget($user, $remaining);
        $category = $this->makeCategory();
        $this->actingAs($user);

        return [$user, $budget, $category];
    }

    // ---------------------------------------------------------------
    // All Expenses
    // ---------------------------------------------------------------

    public function test_deleting_an_expense_refunds_the_budget()
    {
        [$user, $budget, $cat] = $this->signIn(900.00);
        $expense = $this->makeExpense($user, $cat, 100.00);

        Livewire::test(AllExpenses::class)->call('deleteExpense', $expense->id);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'expense_deleted']);
    }

    public function test_a_student_cannot_delete_someone_elses_expense()
    {
        [$user, $budget, $cat] = $this->signIn();
        $other   = $this->makeStudent();
        $expense = $this->makeExpense($other, $cat, 100.00);

        Livewire::test(AllExpenses::class)->call('deleteExpense', $expense->id);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    public function test_expenses_from_a_previous_cycle_are_locked()
    {
        [$user, $budget, $cat] = $this->signIn(900.00);
        $old = $this->makeExpense($user, $cat, 100.00, '2026-09-30 10:00:00');

        Livewire::test(AllExpenses::class)->call('deleteExpense', $old->id);

        $this->assertDatabaseHas('expenses', ['id' => $old->id]);
        $this->assertEquals(900.00, $budget->fresh()->remaining_allowance);
    }

    public function test_deleting_a_savings_transfer_reverts_the_goal()
    {
        [$user, $budget] = $this->signIn(500.00);
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 500,
            'current_saved' => 500, 'status' => 'achieved',
        ]);
        $transfer = $this->makeExpense($user, $this->makeCategory('Savings'), 500.00, '2026-10-06 10:00:00', [
            'savings_goal_id' => $goal->id,
            'item_name'       => 'Laptop',
        ]);

        Livewire::test(AllExpenses::class)->call('deleteExpense', $transfer->id);

        $goal->refresh();
        $this->assertEquals(0.00, $goal->current_saved);
        $this->assertSame('active', $goal->status);
        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    public function test_bulk_delete_removes_current_cycle_rows_and_skips_locked_ones()
    {
        [$user, $budget, $cat] = $this->signIn(650.00);
        $a   = $this->makeExpense($user, $cat, 100.00, '2026-10-06 10:00:00');
        $b   = $this->makeExpense($user, $cat, 200.00, '2026-10-07 09:00:00');
        $old = $this->makeExpense($user, $cat, 50.00, '2026-09-30 10:00:00');

        Livewire::test(AllExpenses::class)
            ->set('selected', [(string) $a->id, (string) $b->id, (string) $old->id])
            ->call('confirmBulkDelete')
            ->assertSet('confirmingBulkDelete', true)
            ->assertSet('bulkDeleteTotal', 300)
            ->call('bulkDelete')
            ->assertSet('selected', [])
            ->assertSet('confirmingBulkDelete', false);

        $this->assertDatabaseMissing('expenses', ['id' => $a->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $b->id]);
        $this->assertDatabaseHas('expenses', ['id' => $old->id]);
        $this->assertEquals(950.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'expense_bulk_deleted']);
    }

    public function test_bulk_delete_of_only_locked_rows_changes_nothing()
    {
        [$user, $budget, $cat] = $this->signIn(950.00);
        $old = $this->makeExpense($user, $cat, 50.00, '2026-09-30 10:00:00');

        Livewire::test(AllExpenses::class)
            ->set('selected', [(string) $old->id])
            ->call('bulkDelete');

        $this->assertDatabaseHas('expenses', ['id' => $old->id]);
        $this->assertEquals(950.00, $budget->fresh()->remaining_allowance);
    }

    public function test_select_page_only_selects_current_cycle_rows()
    {
        [$user, , $cat] = $this->signIn();
        $a   = $this->makeExpense($user, $cat, 100.00, '2026-10-06 10:00:00');
        $b   = $this->makeExpense($user, $cat, 200.00, '2026-10-07 09:00:00');
        $this->makeExpense($user, $cat, 50.00, '2026-09-30 10:00:00');

        $component = Livewire::test(AllExpenses::class)->set('selectAll', true);

        $selected = $component->get('selected');
        sort($selected);
        $expected = [(string) $a->id, (string) $b->id];
        sort($expected);

        $this->assertSame($expected, $selected);
    }

    public function test_search_and_category_filters()
    {
        [$user, , $food] = $this->signIn();
        $transport = $this->makeCategory('Transportation');
        $this->makeExpense($user, $food, 100.00, '2026-10-06 10:00:00', ['item_name' => 'Lunch Special']);
        $this->makeExpense($user, $transport, 20.00, '2026-10-06 11:00:00', ['item_name' => 'Jeepney Ride']);

        Livewire::test(AllExpenses::class)
            ->assertSee('Lunch Special')
            ->assertSee('Jeepney Ride')
            ->set('search', 'Lunch')
            ->assertSee('Lunch Special')
            ->assertDontSee('Jeepney Ride')
            ->call('clearFilters')
            ->set('selectedCategory', $transport->id)
            ->assertSee('Jeepney Ride')
            ->assertDontSee('Lunch Special');
    }

    public function test_students_only_see_their_own_expenses()
    {
        [$user, , $cat] = $this->signIn();
        $this->makeExpense($user, $cat, 100.00, '2026-10-06 10:00:00', ['item_name' => 'My Lunch']);
        $this->makeExpense($this->makeStudent(), $cat, 100.00, '2026-10-06 10:00:00', ['item_name' => 'Their Lunch']);

        Livewire::test(AllExpenses::class)
            ->assertSee('My Lunch')
            ->assertDontSee('Their Lunch');
    }

    // ---------------------------------------------------------------
    // Edit Expense
    // ---------------------------------------------------------------

    public function test_editing_an_amount_adjusts_the_budget_and_logs_the_change()
    {
        [$user, $budget, $cat] = $this->signIn(900.00);
        $expense = $this->makeExpense($user, $cat, 100.00);

        Livewire::test(EditExpense::class, ['id' => $expense->id])
            ->set('amount', '150.00')
            ->set('item_name', 'Bigger lunch')
            ->call('updateExpense')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $expense->refresh();
        $this->assertEquals(150.00, $expense->amount);
        $this->assertSame('Bigger lunch', $expense->item_name);
        $this->assertEquals(850.00, $budget->fresh()->remaining_allowance);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'expense_edited']);
    }

    public function test_lowering_an_amount_refunds_the_difference()
    {
        [$user, $budget, $cat] = $this->signIn(900.00);
        $expense = $this->makeExpense($user, $cat, 100.00);

        Livewire::test(EditExpense::class, ['id' => $expense->id])
            ->set('amount', '40')
            ->call('updateExpense')
            ->assertHasNoErrors();

        $this->assertEquals(960.00, $budget->fresh()->remaining_allowance);
    }

    public function test_an_edit_cannot_push_spending_past_the_available_allowance()
    {
        [$user, $budget, $cat] = $this->signIn(50.00);
        $expense = $this->makeExpense($user, $cat, 100.00);

        Livewire::test(EditExpense::class, ['id' => $expense->id])
            ->set('amount', '200')
            ->call('updateExpense')
            ->assertHasErrors(['amount']);

        $this->assertEquals(50.00, $budget->fresh()->remaining_allowance);
        $this->assertEquals(100.00, $expense->fresh()->amount);
    }

    public function test_edit_validation()
    {
        [$user, , $cat] = $this->signIn(900.00);
        $expense = $this->makeExpense($user, $cat, 100.00);

        Livewire::test(EditExpense::class, ['id' => $expense->id])
            ->set('amount', '10.123')
            ->call('updateExpense')
            ->assertHasErrors(['amount'])
            ->set('amount', '10')
            ->set('transaction_date', '2026-10-01') // before the cycle started
            ->call('updateExpense')
            ->assertHasErrors(['transaction_date'])
            ->set('transaction_date', '2026-10-09') // future
            ->call('updateExpense')
            ->assertHasErrors(['transaction_date']);
    }

    public function test_expenses_from_a_previous_cycle_cannot_be_edited()
    {
        [$user, , $cat] = $this->signIn();
        $old = $this->makeExpense($user, $cat, 100.00, '2026-09-30 10:00:00');

        Livewire::test(EditExpense::class, ['id' => $old->id])
            ->assertRedirect(route('student.expenses.index'));
    }

    public function test_another_students_expense_is_not_found()
    {
        [, , $cat] = $this->signIn();
        $expense = $this->makeExpense($this->makeStudent(), $cat, 100.00);

        $this->withoutExceptionHandling();

        $notFound = false;
        try {
            Livewire::test(EditExpense::class, ['id' => $expense->id]);
        } catch (\Throwable $e) {
            $notFound = $e instanceof ModelNotFoundException
                || $e->getPrevious() instanceof ModelNotFoundException;
        }

        $this->assertTrue($notFound, 'Opening another student\'s expense must fail with a not-found error.');
    }

    public function test_savings_transfers_are_capped_by_what_the_goal_still_needs()
    {
        [$user, $budget] = $this->signIn(800.00);
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 500,
            'current_saved' => 200, 'status' => 'active',
        ]);
        $transfer = $this->makeExpense($user, $this->makeCategory('Savings'), 200.00, '2026-10-06 10:00:00', [
            'savings_goal_id' => $goal->id,
            'item_name'       => 'Laptop',
        ]);

        Livewire::test(EditExpense::class, ['id' => $transfer->id])
            ->set('amount', '600')
            ->call('updateExpense')
            ->assertHasErrors(['amount']);

        Livewire::test(EditExpense::class, ['id' => $transfer->id])
            ->set('amount', '300')
            ->call('updateExpense')
            ->assertHasNoErrors();

        $this->assertEquals(300.00, $goal->fresh()->current_saved);
        $this->assertEquals(700.00, $budget->fresh()->remaining_allowance);
    }

    public function test_raising_a_savings_transfer_to_the_target_achieves_the_goal()
    {
        [$user] = $this->signIn(800.00);
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 500,
            'current_saved' => 200, 'status' => 'active',
        ]);
        $transfer = $this->makeExpense($user, $this->makeCategory('Savings'), 200.00, '2026-10-06 10:00:00', [
            'savings_goal_id' => $goal->id,
            'item_name'       => 'Laptop',
        ]);

        Livewire::test(EditExpense::class, ['id' => $transfer->id])
            ->set('amount', '500')
            ->call('updateExpense')
            ->assertHasNoErrors();

        $this->assertSame('achieved', $goal->fresh()->status);
        Notification::assertSentTo($user, SavingsGoalAchieved::class);
    }
}