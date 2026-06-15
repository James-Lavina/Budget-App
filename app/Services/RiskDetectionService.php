<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
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

        // Calculate explicit cycle end boundary to prevent calculation leaks
        $cycleStartDate = Carbon::parse($activeBudget->cycle_start_date)->startOfDay();
        $cycleEndDate = $cycleStartDate->copy()->addDays(7)->endOfDay();

        // 1. Calculate historical metrics for the current cycle
        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

        /**
         * FIX 1: Correct Total Allowance Baseline
         * Since this service runs inside LogExpense BEFORE remaining_allowance is deducted,
         * $activeBudget->remaining_allowance is still the balance BEFORE this purchase.
         * The absolute original total pool of the week is simply what the user set up initially.
         */
        $actualStartingPool = $activeBudget->total_allowance; 

        /**
         * FIX 2: Calculate True Remaining Balance Dynamically
         * This prevents the description string from reading stale database records.
         */
        $trueRemainingAllowance = max(0, $actualStartingPool - $totalSpentInCycle);

        // Calculate standardized calendar day values instead of raw 24-hour timestamp deltas
        $daysElapsed = max(1, $cycleStartDate->diffInDays(Carbon::now()->startOfDay()) + 1);
        $calendarDaysLeftInCycle = max(0.5, 7 - $daysElapsed);

        if ($actualStartingPool <= 0) {
            return;
        }

        // 2. Base Linear Velocities
        $allowedDailyVelocity = $actualStartingPool / 7;
        $macroDailyVelocity = $totalSpentInCycle / $daysElapsed;

        // 3. Calculate "Today-Only" Velocity to catch early-week spikes instantly
        $spentToday = Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount');

        // Use the higher value between the week's average and today's spike velocity
        $currentDailyVelocity = max($macroDailyVelocity, $spentToday);

        // Determine how many days the remaining wallet balance will survive at this speed
        $projectedRunwayDaysLeft = $currentDailyVelocity > 0
            ? ($trueRemainingAllowance / $currentDailyVelocity)
            : 7;

        // Determine anomaly type categorization based on current timeline milestones
        if ($daysElapsed <= 3) {
            $anomalyType = 'early_week_depletion';
        } else {
            $anomalyType = 'rapid_overspending';
        }

        // Trigger condition: The cash runway runs dry before the calendar week ends OR velocity breaches threshold
        if ($projectedRunwayDaysLeft < $calendarDaysLeftInCycle && $currentDailyVelocity > $allowedDailyVelocity) {

            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                return;
            }

            // Severity variance calculated cleanly by velocity ratio divergence
            $velocityRatio = $currentDailyVelocity / $allowedDailyVelocity;

            if ($velocityRatio >= 1.45) {
                $severityTier = 'high';
            } elseif ($velocityRatio >= 1.25) {
                $severityTier = 'medium';
            } else {
                $severityTier = 'low';
            }

            // Pass the accurate calculated true remaining allowance value to the feedback generator
            $description = $this->generateFeedbackString(
                $anomalyType, 
                $daysElapsed, 
                $trueRemainingAllowance, 
                $actualStartingPool, 
                $currentDailyVelocity
            );

            RiskLog::create([
                'user_id'       => $user->id,
                'anomaly_type'  => $anomalyType,
                'severity_tier' => $severityTier,
                'description'   => $description,
                'resolved'      => false,
            ]);
        }
    }

    /**
     * Compiles behavioral summary sentences for storage.
     */
    private function generateFeedbackString($anomalyType, $daysElapsed, $trueRemaining, $actualStartingPool, $currentVelocity)
    {
        $remaining = number_format($trueRemaining, 2);
        $totalPoolFormatted = number_format($actualStartingPool, 2);
        $velocityFormatted = number_format($currentVelocity, 2);

        if ($anomalyType === 'early_week_depletion') {
            return "Pacing Alert: It is Day {$daysElapsed} of your cycle, and you hit a burn velocity spike of PHP {$velocityFormatted}/day against your PHP {$totalPoolFormatted} total balance. You have PHP {$remaining} left for the week.";
        }

        return "Accelerated spending trend: Your transaction velocities (PHP {$velocityFormatted}/day) have driven your cycle totals past linear thresholds. Your remaining balance pool is monitored at PHP {$remaining}.";
    }
}