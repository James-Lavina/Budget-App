<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Notifications\WeeklyBudgetReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetCycleService
{
    /**
     * Single source of truth for "where are we in this budget cycle".
     * Every page (Dashboard, Forecast, Simulator, LogExpense, etc.) should
     * call this instead of recalculating dates/days independently.
     *
     * Also the single place a cycle rollover is triggered and persisted —
     * previously this only happened inside Dashboard::mount(), so a student
     * who never opened the Dashboard in a given week kept spending against
     * a stale, overdue cycle on every other page (quotas, safe-to-spend,
     * pace checks all silently went wrong once the cycle ran 7+ days
     * overdue, because daysRemaining/daysElapsed math assumed a live
     * window).
     *
     * @param bool $readOnly When true, skips the reset-and-rollover side
     *                       effect entirely — used by admin-facing views
     *                       that inspect a STUDENT's cycle on their behalf.
     *                       Without this, an admin opening a student's
     *                       profile in User Management could trigger that
     *                       student's weekly reset just by viewing it,
     *                       since resolve() persists to $budget as a side
     *                       effect. Every caller acting on behalf of the
     *                       authenticated student themselves (LogExpense,
     *                       EditExpense, ScanExpense, AddBudgetFunds,
     *                       SavingsGoalService, Settings, the student's own
     *                       Dashboard/Forecast/Simulator/AllExpenses) should
     *                       leave this false — the reset SHOULD happen the
     *                       moment that student's own cycle goes stale.
     */
    public function resolve(WeeklyBudget $budget, $user, bool $readOnly = false): array
    {
        if (!$readOnly) {
            $this->maybeResetCycle($budget, $user);
        }

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

        $daysElapsed   = max(1, min(7, (int) $startDate->diffInDays($evalDate) + 1));
        $daysRemaining = min(7, max(1, (int) $evalDate->diffInDays($nextResetDate)));

        $spentTodayDate = $isFastForwarded ? $evalDate->copy()->addDay() : $evalDate;

        // Single source of truth for "true starting pool this cycle" —
        // total_allowance alone excludes rollover, but remaining_allowance +
        // totalSpent reconstructs baseline + rollover. Excludes savings
        // transfers and the Savings category itself, matching the filtering
        // SpendingForecastService and the Dashboard chart both already
        // apply independently.
        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        // Savings-goal contributions ALSO draw down remaining_allowance the
        // same way regular spending does (see SavingsGoalService::addFunds),
        // so they must be added back too when reconstructing the cycle's
        // true starting pool — otherwise effectiveTotalAllowance silently
        // undercounts by whatever was saved this cycle, and every downstream
        // figure derived from it (Total Available This Week, % used,
        // forecast ceiling, simulator ceiling) drifts low by that same
        // amount.
        $totalSavedInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        $effectiveTotalAllowance = max(
            (float) $budget->total_allowance,
            (float) $budget->remaining_allowance + $totalSpentInCycle + $totalSavedInCycle
        );

        return compact(
            'today', 'startDate', 'endDate', 'nextResetDate',
            'evalDate', 'isFastForwarded', 'daysRemaining', 'daysElapsed',
            'spentTodayDate', 'targetResetDay',
            'totalSpentInCycle', 'totalSavedInCycle', 'effectiveTotalAllowance'
        );
    }

    /**
     * Whether a given date falls inside this budget's ACTIVE cycle window.
     *
     * Used to gate edit/delete so mutations never touch remaining_allowance
     * for an expense that belongs to a cycle that has already rolled over —
     * WeeklyBudget mutates the same row in place on reset, so without this
     * check, editing/deleting a past-cycle expense would manufacture
     * phantom balance in the CURRENT cycle.
     */
    public function isWithinCurrentCycle(WeeklyBudget $budget, $user, $date): bool
    {
        $cycle = $this->resolve($budget, $user);
        $checkDate = Carbon::parse($date);

        return $checkDate->betweenIncluded($cycle['startDate'], $cycle['endDate']);
    }

    /**
     * Rolls the cycle over in place — mutating $budget, the same object
     * instance every caller already holds — when the scheduled reset day
     * has passed or the cycle has run 7+ days overdue. Runs on every call
     * to resolve(), from every page in the app, so a student can no longer
     * keep spending against a stale cycle just by avoiding the Dashboard.
     *
     * Safe to call multiple times per request: once the cycle rolls over,
     * cycle_start_date advances and the trigger condition goes false on
     * any subsequent call within the same request, so no duplicate
     * rollover or duplicate WeeklyBudgetReview notification can fire.
     */
    private function maybeResetCycle(WeeklyBudget $budget, $user): void
    {
        $today          = Carbon::today();
        $startDate      = Carbon::parse($budget->cycle_start_date)->startOfDay();
        $targetResetDay = $budget->reset_day ?? $user->default_reset_day ?? 'Monday';

        $isScheduledResetDay = strtolower($today->format('l')) === strtolower($targetResetDay);
        $isPastCycleWindow   = $today->gte($startDate->copy()->addDays(7));

        if (!(($isScheduledResetDay && !$today->isSameDay($startDate)) || $isPastCycleWindow)) {
            return;
        }

        DB::transaction(function () use ($budget, $user, $startDate, $targetResetDay, $today) {
            // Snapshot the ENDING cycle's totals before the row is mutated.
            $endingResetDate = strtolower($startDate->format('l')) === strtolower($targetResetDay)
                ? $startDate->copy()->addWeek()
                : $startDate->copy()->next($targetResetDay);
            $endingEndDate = $endingResetDate->copy()->subSecond();

            $amountSpent = (float) Expense::where('user_id', $user->id)
                ->whereBetween('transaction_date', [$startDate, $endingEndDate])
                ->whereNull('savings_goal_id')
                ->whereDoesntHave('category', function ($query) {
                    $query->where('name', 'LIKE', '%Savings%');
                })
                ->sum('amount');

            $amountSaved = (float) Expense::where('user_id', $user->id)
                ->whereBetween('transaction_date', [$startDate, $endingEndDate])
                ->whereNotNull('savings_goal_id')
                ->sum('amount');

            $endingPool = max(
                (float) $budget->total_allowance,
                (float) $budget->remaining_allowance + $amountSpent + $amountSaved
            );

            $unspent = max(0.00, (float) $budget->remaining_allowance);

            $nextCycleBaseline = (float) ($user->default_allowance ?? 1000.00);
            $nextCycleResetDay = $user->default_reset_day ?? $targetResetDay;
            $newWeeklyTotal    = $nextCycleBaseline + $unspent;

            $newCycleStart = strtolower($today->format('l')) === strtolower($nextCycleResetDay)
                ? $today->copy()
                : $today->copy()->previous($nextCycleResetDay);

            $budget->update([
                'total_allowance'     => $nextCycleBaseline,
                'remaining_allowance' => $newWeeklyTotal,
                'reset_day'           => $nextCycleResetDay,
                'cycle_start_date'    => $newCycleStart,
            ]);

            $severity = ($endingPool > 0 && ($amountSpent / $endingPool) >= 0.9) ? 'medium' : 'success';

            try {
                $user->notify(new WeeklyBudgetReview($amountSpent, $unspent, $severity, $amountSaved));
            } catch (\Throwable $e) {
                \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
            }

            session()->flash('success', 'Weekly budget reset! ₱' . number_format($unspent, 2) . ' rolled over to your new cycle.');
        });

        $budget->refresh();
    }
}