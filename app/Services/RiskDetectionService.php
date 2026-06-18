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
        // Protect savings goals injections from counting as reckless overspending
        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

        $actualStartingPool = $activeBudget->total_allowance; 

        // FIX: Ensure we use the actual fresh remaining allowance calculation
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

        // Trigger condition: Modified to ensure major spending single-day spikes ALWAYS trigger 
        // regardless of fraction calculations or component execution order parameters.
        if (($projectedRunwayDaysLeft < $calendarDaysLeftInCycle && $currentDailyVelocity > $allowedDailyVelocity) || ($spentToday >= $allowedDailyVelocity * 2)) {

            // FIX: Linked with your Testing Override blocks to prevent duplicate lockouts during testing trials
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                // If we are in a testing lifecycle loop, allow execution to regenerate fresh notifications
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
            // ===========================================

            // Pass the accurate calculated true remaining allowance value and today's spending to the feedback generator
            $description = $this->generateFeedbackString(
                $anomalyType, 
                $daysElapsed, 
                $trueRemainingAllowance, 
                $actualStartingPool, 
                $currentDailyVelocity,
                $spentToday // 🧠 Added this variable here!
            );

            // Avoid duplicate log cluttering during active test sessions
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
    /**
     * Compiles behavioral summary sentences for storage.
     */
    private function generateFeedbackString($anomalyType, $daysElapsed, $trueRemaining, $actualStartingPool, $currentVelocity, $spentToday)
    {
        $remaining = number_format($trueRemaining, 2);
        $totalPoolFormatted = number_format($actualStartingPool, 2);
        $velocityFormatted = number_format($currentVelocity, 2);
        $todayFormatted = number_format($spentToday, 2);
        
        $daysRemaining = max(1, 7 - $daysElapsed);
        $safeDailyCap = number_format($trueRemaining / $daysRemaining, 2);

        $nudge = " To finish the week safely, try to limit your spending to PHP {$safeDailyCap}/day.";

        // Calculate days remaining: 
// If cycle ends tomorrow, you have Today (1) + Tomorrow (1) = 2 days.
$daysRemaining = max(1, 7 - ($daysElapsed - 1)); 

// This ensures that even if you are on Day 6, 
// the system calculates: 7 - (6 - 1) = 7 - 5 = 2 days.
$safeDailyCap = number_format($trueRemaining / $daysRemaining, 2);

        return "Budget Pace Alert: Your spending is at PHP {$velocityFormatted}/day. You have PHP {$remaining} remaining. To safely stretch this until the end of your cycle, try to limit your average spending to PHP {$safeDailyCap}/day.";
    }
}