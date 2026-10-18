<?php

namespace Tests\Concerns;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\WeeklyBudget;

/**
 * Shared builders. All tests assume "now" is Wednesday 2026-10-07 12:00 and the
 * active cycle started Monday 2026-10-05 (reset day Monday).
 */
trait BuildsBudgetData
{
    protected function makeStudent(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'student',
            'default_allowance' => 1000,
            'default_reset_day' => 'Monday',
        ], $attributes));
    }

    protected function makeAdmin(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function makeCategory(string $name = 'Food & Dining', array $attributes = []): ExpenseCategory
    {
        return ExpenseCategory::create(array_merge(['name' => $name], $attributes));
    }

    protected function makeBudget(User $user, float $remaining = 1000.00, array $attributes = []): WeeklyBudget
    {
        return WeeklyBudget::create(array_merge([
            'user_id'             => $user->id,
            'total_allowance'     => 1000,
            'remaining_allowance' => $remaining,
            'reset_day'           => 'Monday',
            'cycle_start_date'    => '2026-10-05',
        ], $attributes));
    }

    protected function makeExpense(
        User $user,
        ExpenseCategory $category,
        float $amount,
        string $date = '2026-10-07 10:00:00',
        array $attributes = []
    ): Expense {
        return Expense::create(array_merge([
            'user_id'             => $user->id,
            'expense_category_id' => $category->id,
            'item_name'           => 'Test item',
            'amount'              => $amount,
            'transaction_date'    => $date,
            'tracking_type'       => 'manual',
        ], $attributes));
    }
}