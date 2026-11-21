<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Notifications\WeeklyBudgetReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetCycleService
{
    public const PACE_TOLERANCE = 0.10;

    /**
     * Shared pace maths for the Dashboard and RiskDetectionService.
     *
     * $remaining      weekly_budgets.remaining_allowance
     * $realSpent      cycle spend excluding savings transfers
     * $realSpentToday today's spend excluding savings transfers
     * $saved          money moved to savings goals this cycle
     */
    public function pace(array $cycle, float $remaining, float $realSpent, float $realSpentToday, float $saved): array
    {
        $daysElapsed       = max(1, (int) $cycle['daysElapsed']);
        $daysLeftInclToday = max(1, (int) $cycle['daysRemaining']);
        $daysAfterToday    = max(0, $daysLeftInclToday - 1);

        $velocity  = $realSpent / $daysElapsed;
        $spendable = $remaining + $saved;

        // Same figure as the Dashboard's "Safe per Day", with savings neutralised.
        $paceQuota = ($spendable + $realSpentToday) / $daysLeftInclToday;

        // Needs at least 2 elapsed days: on day 1 a single normal purchase would look like a
        // runaway pace. Real day-1 spikes are still caught by the "2x daily share" rule in
        // RiskDetectionService.
        $isOverPace = $daysAfterToday > 0
            && $daysElapsed >= 2
            && $velocity > 0
            && $velocity > $paceQuota * (1 + self::PACE_TOLERANCE);

        return [
            'velocity'       => $velocity,
            'paceQuota'      => $paceQuota,
            'spendable'      => $spendable,
            'daysAfterToday' => $daysAfterToday,
            'isOverPace'     => $isOverPace,
        ];
    }

    /**
     * Read-only. Never mutates the budget. The weekly reset is performed
     * explicitly through rollover() after the student confirms the new allowance.
     * $readOnly is kept only for backward compatibility with existing callers.
     */
    public function resolve(WeeklyBudget $budget, $user, bool $readOnly = false): array
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

        $daysElapsed   = max(1, min(7, (int) $startDate->diffInDays($evalDate) + 1));
        $daysRemaining = min(7, max(1, (int) $evalDate->diffInDays($nextResetDate)));

        $spentTodayDate = $isFastForwarded ? $evalDate->copy()->addDay() : $evalDate;

        // Savings transfers count as spend.
        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        // Sub-metric only (weekly review wording). Already inside totalSpentInCycle.
        $totalSavedInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        $effectiveTotalAllowance = max(
            (float) $budget->total_allowance,
            (float) $budget->remaining_allowance + $totalSpentInCycle
        );

        return compact(
            'today', 'startDate', 'endDate', 'nextResetDate',
            'evalDate', 'isFastForwarded', 'daysRemaining', 'daysElapsed',
            'spentTodayDate', 'targetResetDay',
            'totalSpentInCycle', 'totalSavedInCycle', 'effectiveTotalAllowance'
        );
    }

    public function isWithinCurrentCycle(WeeklyBudget $budget, $user, $date, bool $readOnly = false): bool
    {
        $cycle     = $this->resolve($budget, $user, $readOnly);
        $checkDate = Carbon::parse($date);

        return $checkDate->betweenIncluded($cycle['startDate'], $cycle['endDate']);
    }

    /**
     * True when the stored cycle has ended and the student must set up the new week.
     * Derived from the same next-reset date resolve() uses, so a cycle can never get stuck.
     */
    public function needsRollover(WeeklyBudget $budget, $user): bool
    {
        $today          = Carbon::today();
        $startDate      = Carbon::parse($budget->cycle_start_date)->startOfDay();
        $targetResetDay = $budget->reset_day ?? $user->default_reset_day ?? 'Monday';

        $nextResetDate = strtolower($startDate->format('l')) === strtolower($targetResetDay)
            ? $startDate->copy()->addWeek()
            : $startDate->copy()->next($targetResetDay);

        return $today->gte($nextResetDate);
    }

    /**
     * Figures for the cycle that just ended plus the dates of the cycle that starts now.
     */
    public function endingCycleSummary(WeeklyBudget $budget, $user): array
    {
        $today          = Carbon::today();
        $startDate      = Carbon::parse($budget->cycle_start_date)->startOfDay();
        $targetResetDay = $budget->reset_day ?? $user->default_reset_day ?? 'Monday';

        $endingResetDate = strtolower($startDate->format('l')) === strtolower($targetResetDay)
            ? $startDate->copy()->addWeek()
            : $startDate->copy()->next($targetResetDay);

        $endingEndDate = $endingResetDate->copy()->subSecond();

        // Total outflow, savings transfers included.
        $amountSpentTotal = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endingEndDate])
            ->sum('amount');

        $amountSaved = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endingEndDate])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        // Pure spend (excluding savings) for the recap and the review email wording.
        $amountSpent = $amountSpentTotal - $amountSaved;

        $endingPool = max(
            (float) $budget->total_allowance,
            (float) $budget->remaining_allowance + $amountSpentTotal
        );

        $unspent = max(0.00, (float) $budget->remaining_allowance);

        $newResetDay = $user->default_reset_day ?? $targetResetDay;

        if (strtolower($newResetDay) === strtolower($targetResetDay)) {
            // Same reset day: stay calendar-aligned (also covers a missed reset day).
            $newCycleStart = strtolower($today->format('l')) === strtolower($newResetDay)
                ? $today->copy()
                : $today->copy()->previous($newResetDay);
        } else {
            // Reset day changed: never start in the past, or last cycle's expenses leak in.
            $newCycleStart = $today->copy();
        }

        $newNextReset = strtolower($newCycleStart->format('l')) === strtolower($newResetDay)
            ? $newCycleStart->copy()->addWeek()
            : $newCycleStart->copy()->next($newResetDay);

        return [
            'startDate'        => $startDate,
            'endDate'          => $endingEndDate->copy()->startOfDay(),
            'amountSpent'      => $amountSpent,
            'amountSpentTotal' => $amountSpentTotal,
            'amountSaved'      => $amountSaved,
            'endingPool'       => $endingPool,
            'unspent'          => $unspent,
            'newResetDay'      => $newResetDay,
            'newCycleStart'    => $newCycleStart,
            'newNextReset'     => $newNextReset,
        ];
    }

    /**
     * Applies the reset using the allowance the student entered this week.
     * New remaining = entered allowance + last cycle's unspent balance.
     * Returns null when the cycle was already rolled (double submit / second tab).
     */
    public function rollover(WeeklyBudget $budget, $user, float $newAllowance): ?array
    {
        $result = null;

        DB::transaction(function () use ($budget, $user, $newAllowance, &$result) {
            $locked = WeeklyBudget::whereKey($budget->id)->lockForUpdate()->first();

            if (!$locked || !$this->needsRollover($locked, $user)) {
                return;
            }

            $summary  = $this->endingCycleSummary($locked, $user);
            $newTotal = round($newAllowance + $summary['unspent'], 2);

            $locked->update([
                'total_allowance'     => $newAllowance,
                'remaining_allowance' => $newTotal,
                'added_funds'         => 0,
                'reset_day'           => $summary['newResetDay'],
                'cycle_start_date'    => $summary['newCycleStart'],
            ]);

            $result = $summary + [
                'newAllowance' => $newAllowance,
                'newTotal'     => $newTotal,
            ];
        });

        if ($result) {
            // Real spending only: moving money to savings must not make the week look "tight".
            $severity = ($result['endingPool'] > 0 && ($result['amountSpent'] / $result['endingPool']) >= 0.9)
                ? 'medium'
                : 'success';

            try {
                $user->notify(new WeeklyBudgetReview(
                    $result['amountSpent'],
                    $result['unspent'],
                    $severity,
                    $result['amountSaved']
                ));
            } catch (\Throwable $e) {
                NotificationLogger::logFailure($user, WeeklyBudgetReview::class, $e);
            }
        }

        return $result;
    }
}