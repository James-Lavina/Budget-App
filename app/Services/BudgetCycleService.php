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
     */
    public function needsRollover(WeeklyBudget $budget, $user): bool
    {
        $today          = Carbon::today();
        $startDate      = Carbon::parse($budget->cycle_start_date)->startOfDay();
        $targetResetDay = $budget->reset_day ?? $user->default_reset_day ?? 'Monday';

        $isScheduledResetDay = strtolower($today->format('l')) === strtolower($targetResetDay);
        $isPastCycleWindow   = $today->gte($startDate->copy()->addDays(7));

        return ($isScheduledResetDay && !$today->isSameDay($startDate)) || $isPastCycleWindow;
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

        $newCycleStart = strtolower($today->format('l')) === strtolower($newResetDay)
            ? $today->copy()
            : $today->copy()->previous($newResetDay);

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
            'newNextReset'     => $newCycleStart->copy()->addWeek(),
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
            $severity = ($result['endingPool'] > 0 && ($result['amountSpentTotal'] / $result['endingPool']) >= 0.9)
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