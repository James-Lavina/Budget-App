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
     * Fully compatible with rolled-over savings balances.
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

        $totalSpentInCycle = Expense::where('user_id', $user->id)
            ->where('transaction_date', '>=', $activeBudget->cycle_start_date)
            ->sum('amount');

        $actualStartingPool = $activeBudget->remaining_allowance + $totalSpentInCycle;

        $startDate = Carbon::parse($activeBudget->cycle_start_date);
        $daysElapsed = max(1, $startDate->diffInDays(Carbon::now()) + 1); 

        $idealPaceAmount = ($daysElapsed / 7) * $actualStartingPool;

        if ($totalSpentInCycle > ($idealPaceAmount * 1.20)) {
            
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                return; 
            }

            if ($daysElapsed <= 3) {
                $anomalyType = 'early_week_depletion';
            } else {
                $anomalyType = 'rapid_overspending';
            }

            if ($totalSpentInCycle > ($idealPaceAmount * 1.45)) {
                $severityTier = 'high';
            } elseif ($totalSpentInCycle > ($idealPaceAmount * 1.30)) {
                $severityTier = 'medium';
            } else {
                $severityTier = 'low';
            }

            $description = $this->generateFeedbackString($anomalyType, $daysElapsed, $activeBudget, $actualStartingPool);

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
    private function generateFeedbackString($anomalyType, $daysElapsed, $budget, $actualStartingPool)
    {
        $remaining = number_format($budget->remaining_allowance, 2);
        $totalPoolFormatted = number_format($actualStartingPool, 2);

        if ($anomalyType === 'early_week_depletion') {
            return "Pacing Alert: It is only Day {$daysElapsed} of your cycle, but you have consumed a significant portion of your ₱{$totalPoolFormatted} available balance (including rolled-over savings). You have ₱{$remaining} remaining for the week.";
        }

        return "Accelerated spending trend: Your transaction velocities have driven your cycle totals past linear thresholds. Your remaining balance pool is safely monitored at ₱{$remaining}.";
    }
}