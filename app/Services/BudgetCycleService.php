<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Notifications\WeeklyBudgetReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetCycleService
{
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

        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

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

    public function isWithinCurrentCycle(WeeklyBudget $budget, $user, $date, bool $readOnly = false): bool
    {
        $cycle = $this->resolve($budget, $user, $readOnly);
        $checkDate = Carbon::parse($date);

        return $checkDate->betweenIncluded($cycle['startDate'], $cycle['endDate']);
    }

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
                // FIX
                NotificationLogger::logFailure($user, WeeklyBudgetReview::class, $e);
            }

            session()->flash('success', 'Weekly budget reset! ₱' . number_format($unspent, 2) . ' rolled over to your new cycle.');
        });

        $budget->refresh();
    }
}