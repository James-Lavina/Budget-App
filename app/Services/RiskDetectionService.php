<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\WeeklyBudget;
use App\Notifications\BudgetRiskNotification;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class RiskDetectionService
{
    /**
    * Analyzes spending speed and writes anomalies straight to the risk_logs table.
    * Evaluates financial balance longevity against physical calendar constraints.
    *
    * @param \App\Models\User $user
    * @return void
    */
    public function evaluateSpendingRisk($user)
    {
        $activeBudget = WeeklyBudget::where('user_id', $user->id)
            ->orderBy('cycle_start_date', 'desc')
            ->first();

        if (!$activeBudget) {
            return;
        }

        // Maintain strict, non-overlapping 7-day cycle windows
        $cycleStartDate = Carbon::parse($activeBudget->cycle_start_date)->startOfDay();
        $cycleEndDate = $cycleStartDate->copy()->addDays(6)->endOfDay();

        // 1. Calculate historical metrics for the current cycle
        // Filter out savings goal allocations so transfers are not flagged as spending velocity
        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->whereNull('savings_goal_id')
            ->sum('amount');

        // True starting capacity combines remaining balance + spent balance.
        $trueRemainingAllowance = (float) ($activeBudget->remaining_allowance ?? max(0, $activeBudget->total_allowance - $totalSpentInCycle));
        $actualStartingPool = $trueRemainingAllowance + $totalSpentInCycle;

        if ($actualStartingPool <= 0) {
            return;
        }

        // Standardized calendar day calculation
        $daysElapsed = max(1, $cycleStartDate->diffInDays(Carbon::now()->startOfDay()) + 1);
        $calendarDaysLeftInCycle = max(0.5, 7 - $daysElapsed);

        // 2. Base Linear Velocities evaluated against the full available pool
        $allowedDailyVelocity = $actualStartingPool / 7;
        $macroDailyVelocity = $totalSpentInCycle / $daysElapsed;

        // 3. Calculate "Today-Only" Velocity excluding savings allocations
        $spentToday = Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', Carbon::today())
            ->whereNull('savings_goal_id')
            ->sum('amount');

        // Use the higher value between the week's average and today's spike velocity
        $currentDailyVelocity = max($macroDailyVelocity, $spentToday);

        // Determine how many days the remaining wallet balance will survive at this speed
        $projectedRunwayDaysLeft = $currentDailyVelocity > 0
            ? ($trueRemainingAllowance / $currentDailyVelocity)
            : 7;

        // Anomaly type categorization based on current timeline milestones
        if ($daysElapsed <= 3) {
            $anomalyType = 'early_week_depletion';
        } else {
            $anomalyType = 'rapid_overspending';
        }

        // Track if a primary Pace Check risk alert fired during this evaluation
        $primaryRiskTriggered = false;

        // Trigger condition: Single-day spikes or unsustainable runway depletion
        if (($projectedRunwayDaysLeft < $calendarDaysLeftInCycle && $currentDailyVelocity > $allowedDailyVelocity) || ($spentToday >= $allowedDailyVelocity * 2)) {
            
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                    return;
            }

            // === RUNWAY DEFICIT MATRIX ENGINE ===
            $runwayDeficitDays = $calendarDaysLeftInCycle - $projectedRunwayDaysLeft;
            if ($runwayDeficitDays >= 3.0 || $projectedRunwayDaysLeft <= 1.0) {
                $severityTier = 'high';
            } elseif ($runwayDeficitDays >= 1.0) {
                $severityTier = 'medium';
            } else {
                $severityTier = 'low';
            }

            $description = $this->generateFeedbackString(
                $anomalyType,
                $daysElapsed,
                $trueRemainingAllowance,
                $actualStartingPool,
                $currentDailyVelocity,
                $spentToday
            );

            RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->whereDate('created_at', Carbon::today())
                ->delete();

            $riskLog = RiskLog::create([
                'user_id'       => $user->id,
                'anomaly_type'  => $anomalyType,
                'severity_tier' => $severityTier,
                'description'   => $description,
                'resolved'      => false,
            ]);

            $user->notify(new BudgetRiskNotification($riskLog));

            // Mark primary risk as triggered so secondary pacing check is skipped
            $primaryRiskTriggered = true;
        }

        //4. NEW: Category concentration check — independent of velocity checks above
        $this->checkCategoryConcentration($user, $cycleStartDate, $cycleEndDate);
    }

    /**
    * Compiles humanized behavioral summary sentences for storage.
    */
    private function generateFeedbackString($anomalyType, $daysElapsed, $trueRemaining, $actualStartingPool, $currentVelocity, $spentToday)
    {
        $remaining = number_format($trueRemaining, 2);
        $velocityFormatted = number_format($currentVelocity, 2);
        $daysRemaining = max(1, 7 - ($daysElapsed - 1));
        $safeDailyCap = number_format($trueRemaining / $daysRemaining, 2);

        return "Pace Check ⚡: Your current spending rate is around ₱{$velocityFormatted}/day with ₱{$remaining} left. To keep your budget balanced through Sunday, aim for about ₱{$safeDailyCap}/day.";
    }

    /**
     * Category concentration warning.
     * Flags when a single category dominates the cycle's spending —
     * distinct from RiskDetectionService's existing velocity-based checks,
     * which only look at overall pace, not where the money is going.
     */
    private function checkCategoryConcentration($user, $cycleStartDate, $cycleEndDate)
    {
        $totalSpent = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($q) {
                $q->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        // Skip early in the cycle — not enough data for a meaningful signal yet.
        if ($totalSpent < 200) {
            return;
        }

        $categoryTotals = Expense::where('expenses.user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->whereNull('savings_goal_id')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expense_categories.name', 'NOT LIKE', '%Savings%')
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->first();

        if (!$categoryTotals) {
            return;
        }

        $percentage = round(($categoryTotals->total / $totalSpent) * 100);

        if ($percentage < 50) {
            return;
        }

        // Avoid re-notifying for the same category within the same cycle.
        $alreadyNotified = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'category_concentration')
            ->where('data->category', $categoryTotals->name)
            ->where('created_at', '>=', $cycleStartDate)
            ->exists();

        if (!$alreadyNotified) {
            $user->notify(new \App\Notifications\CategoryConcentrationWarning(
                $categoryTotals->name,
                $percentage,
                (float) $categoryTotals->total
            ));
        }
    }

    /**
     * Large single-transaction flag.
     * Fires immediately at the point an expense is logged, rather than
     * waiting for the next velocity-based evaluation cycle — a single big
     * purchase deserves an immediate flag, not a delayed pace warning.
     */
    public function checkLargeTransaction($user, Expense $expense, $totalAllowance)
    {
        if ($expense->savings_goal_id || $totalAllowance <= 0) {
            return;
        }

        $threshold = $totalAllowance * 0.30;

        if ($expense->amount < $threshold) {
            return;
        }

        $percentage = round(($expense->amount / $totalAllowance) * 100);

        $user->notify(new \App\Notifications\LargeTransactionAlert(
            $expense->item_name,
            (float) $expense->amount,
            $percentage
        ));
    }
}