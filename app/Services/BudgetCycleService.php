<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;

class BudgetCycleService
{
    /**
     * Single source of truth for "where are we in this budget cycle".
     * Every page (Dashboard, Forecast, Simulator) should call this instead
     * of recalculating dates/days independently.
     */
    public function resolve(WeeklyBudget $budget, $user): array
    {
        $today          = Carbon::today();
        $startDate      = Carbon::parse($budget->cycle_start_date)->startOfDay();
        $targetResetDay = $budget->reset_day ?? $user->default_reset_day ?? 'Monday';

        if (strtolower($startDate->format('l')) === strtolower($targetResetDay)) {
            $nextResetDate = $startDate->copy()->addWeek();
        } else {
            $nextResetDate = $startDate->copy()->next($targetResetDay);
        }

        $endDate = $nextResetDate->copy()->subSecond();

        $latestExpenseDate = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->max('transaction_date');

        $evalDate        = $today;
        $isFastForwarded = false;

        if ($latestExpenseDate) {
            $latestCarbon = Carbon::parse($latestExpenseDate)->startOfDay();
            if ($latestCarbon->gt($today)) {
                $evalDate        = $latestCarbon;
                $isFastForwarded = true;
            }
        }

        // Independent, inclusive count: cycle start through eval date, inclusive.
        // This intentionally overlaps by one day with $daysRemaining below —
        // "today" counts as both an elapsed day (you may have spent already)
        // and a remaining day (you can still spend the rest of it).
        $daysElapsed = max(1, min(7, (int) $startDate->diffInDays($evalDate) + 1));

        // Today always counts as a remaining day you can still spend in.
        $daysRemaining = min(7, max(1, (int) $evalDate->diffInDays($nextResetDate)));

        $spentTodayDate = $isFastForwarded ? $evalDate->copy()->addDay() : $evalDate;

        return compact(
            'today', 'startDate', 'endDate', 'nextResetDate',
            'evalDate', 'isFastForwarded', 'daysRemaining', 'daysElapsed',
            'spentTodayDate', 'targetResetDay'
        );
    }
}