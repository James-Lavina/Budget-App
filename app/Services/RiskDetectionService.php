<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use App\Notifications\BudgetRiskNotification;
use Carbon\Carbon;

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

        $actualStartingPool = $activeBudget->total_allowance;

        // Calculate fresh remaining allowance ignoring savings allocations
        $trueRemainingAllowance = max(0, $actualStartingPool - $totalSpentInCycle);

        // Standardized calendar day calculation
        $daysElapsed = max(1, $cycleStartDate->diffInDays(Carbon::now()->startOfDay()) + 1);
        $calendarDaysLeftInCycle = max(0.5, 7 - $daysElapsed);

        if ($actualStartingPool <= 0) {
            return;
        }

        // 2. Base Linear Velocities
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

        // Trigger condition: Single-day spikes or unsustainable runway depletion
        if (($projectedRunwayDaysLeft < $calendarDaysLeftInCycle && $currentDailyVelocity > $allowedDailyVelocity) || ($spentToday >= $allowedDailyVelocity * 2)) {
            
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                $isTestingOverrideActive = true;
                if (!$isTestingOverrideActive) {
                    return;
                }
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
        }
    }

    /**
     * Compiles behavioral summary sentences for storage.
     */
    private function generateFeedbackString($anomalyType, $daysElapsed, $trueRemaining, $actualStartingPool, $currentVelocity, $spentToday)
    {
        $remaining = number_format($trueRemaining, 2);
        $totalPoolFormatted = number_format($actualStartingPool, 2);
        $velocityFormatted = number_format($currentVelocity, 2);
        $todayFormatted = number_format($spentToday, 2);

        $daysRemaining = max(1, 7 - ($daysElapsed - 1));
        $safeDailyCap = number_format($trueRemaining / $daysRemaining, 2);

        return "Budget Pace Alert: Your spending is at PHP {$velocityFormatted}/day. You have PHP {$remaining} remaining. To safely stretch this until the end of your cycle, try to limit your average spending to PHP {$safeDailyCap}/day.";
    }
}