<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\WeeklyBudget;
use App\Notifications\BudgetRiskNotification;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class RiskDetectionService
{
    private BudgetCycleService $cycleService;

    public function __construct(BudgetCycleService $cycleService)
    {
        $this->cycleService = $cycleService;
    }

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

        // Single source of truth for cycle dates/day-counts — same service
        // Dashboard/Forecast/Simulator use. Also makes this respect the
        // /test/fast-forward flow via $cycle['evalDate'], instead of the
        // previous hand-rolled Carbon::now()/addDays(6) math which could
        // silently drift from what the student sees elsewhere in the app.
        $cycle = $this->cycleService->resolve($activeBudget, $user);
        $cycleStartDate = $cycle['startDate'];
        $cycleEndDate   = $cycle['endDate'];
        // FIX: use spentTodayDate, NOT evalDate directly. When a cycle is
        // fast-forwarded (isFastForwarded=true), BudgetCycleService defines
        // "today" for spending purposes as evalDate+1 day — this is the same
        // field Dashboard/Forecast/Simulator all use for their "spent today"
        // figures. Using evalDate directly here (as an earlier version of
        // this file did) made RiskDetectionService disagree with what the
        // UI displays: it would count the last seeded day's expenses as
        // "today" while the Dashboard correctly showed ₱0 spent today.
        $spentTodayDate = $cycle['spentTodayDate'];
        $daysElapsed    = $cycle['daysElapsed'];

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

        // Used ONLY for the runway/pace-check comparison below — this
        // intentionally excludes today (it answers "how many days AFTER
        // today are left to compare against projected runway"). Kept as
        // its own variable, separate from $cycle['daysRemaining'], because
        // that one is inclusive-of-today (see $daysRemainingIncludingToday
        // below) and floored at 1, not 0.5 — different semantics needed here.
        $calendarDaysLeftInCycle = max(0.5, 7 - $daysElapsed);

        // FIX: per-day quota math (Daily Safe-to-Spend below) needs "today"
        // included in the divisor. $cycle['daysRemaining'] from
        // BudgetCycleService is already inclusive-of-today and floored at 1
        // (min(7, max(1, ...))), which is exactly the semantics that was
        // missing before — reusing $calendarDaysLeftInCycle there inflated
        // the quota (up to 2x on the last day of the cycle), which is why
        // the Daily Safe-to-Spend Warning almost never fired.
        $daysRemainingIncludingToday = $cycle['daysRemaining'];

        // 2. Base Linear Velocities evaluated against the full available pool
        $allowedDailyVelocity = $actualStartingPool / 7;
        $macroDailyVelocity = $totalSpentInCycle / $daysElapsed;

        // 3. Calculate "Today-Only" Velocity excluding savings allocations
        $spentToday = Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $spentTodayDate)
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
        $triggerCondition = ($projectedRunwayDaysLeft < $calendarDaysLeftInCycle && $currentDailyVelocity > $allowedDailyVelocity) || ($spentToday >= $allowedDailyVelocity * 2);

        if ($triggerCondition) {
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
            ->where('anomaly_type', $anomalyType)
            ->where('resolved', false)
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if (!$alreadyLoggedToday) {
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

            try {
                $user->notify(new BudgetRiskNotification($riskLog));
            } catch (\Throwable $e) {
                \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
            }
            
            // Mark primary risk as triggered so secondary pacing check is skipped
            $primaryRiskTriggered = true;
        }
        } else {
            // The overspending condition that used to be true is no longer
            // true — e.g. the student deleted or lowered the amount of the
            // expense that caused it. Mark both the internal RiskLog rows
            // AND their linked notifications as resolved instead of
            // leaving them permanently "active" with no way to tell they
            // were fixed.
            $resolvedLogIds = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->pluck('id');

            if ($resolvedLogIds->isNotEmpty()) {
                RiskLog::whereIn('id', $resolvedLogIds)->update(['resolved' => true]);
                foreach ($resolvedLogIds as $logId) {
                    DatabaseNotification::where('notifiable_id', $user->id)
                        ->where('notifiable_type', 'App\Models\User')
                        ->where('data', 'LIKE', '%"risk_log_id":' . $logId . '%')
                        ->get()
                        ->each(function ($notification) {
                            $data = $notification->data;
                            $data['resolved'] = true;
                            $notification->update(['data' => $data]);
                        });
                }
            }
        }

        // === Overspending Threshold (admin-configurable) ===
        // A separate, simpler condition from the velocity/runway model above:
        // flags a flat %-of-allowance breach regardless of pacing math.
        $riskSettings = RiskSetting::current();
        if ($riskSettings->overspending_enabled) {
            $overspendPercent = $actualStartingPool > 0
                ? ($totalSpentInCycle / $actualStartingPool) * 100
                : 0;

            if ($overspendPercent >= $riskSettings->overspending_threshold) {
                $alreadyLoggedOverspend = RiskLog::where('user_id', $user->id)
                    ->where('anomaly_type', 'overspending_threshold')
                    ->where('resolved', false)
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                    if (!$alreadyLoggedOverspend) {
                        $overspendRiskLog = RiskLog::create([
                            'user_id'       => $user->id,
                            'anomaly_type'  => 'overspending_threshold',
                            'severity_tier' => 'high',
                            'description'   => "Overspending Alert 🚨: You've used " . round($overspendPercent) . "% of your weekly allowance.",
                            'resolved'      => false,
                        ]);
                    
                        try {
                            $user->notify(new BudgetRiskNotification($overspendRiskLog));
                        } catch (\Throwable $e) {
                            \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
                        }

                    }
            } else {
                // No longer over threshold — resolve any still-open alert.
                $resolvedOverspendIds = RiskLog::where('user_id', $user->id)
                    ->where('anomaly_type', 'overspending_threshold')
                    ->where('resolved', false)
                    ->whereDate('created_at', Carbon::today())
                    ->pluck('id');

                if ($resolvedOverspendIds->isNotEmpty()) {
                    RiskLog::whereIn('id', $resolvedOverspendIds)->update(['resolved' => true]);
                    foreach ($resolvedOverspendIds as $logId) {
                        DatabaseNotification::where('notifiable_id', $user->id)
                            ->where('notifiable_type', 'App\Models\User')
                            ->where('data', 'LIKE', '%"risk_log_id":' . $logId . '%')
                            ->get()
                            ->each(function ($notification) {
                                $data = $notification->data;
                                $data['resolved'] = true;
                                $notification->update(['data' => $data]);
                            });
                    }
                }
            }
        }

        // === Daily Safe-to-Spend Warning (admin-configurable) ===
        // A per-day check, independent of the weekly pacing/velocity model above:
        // flags when TODAY's remaining safe-to-spend quota drops below a flat
        // percentage, regardless of whether the weekly pace itself looks fine.
        // Scoped by whereDate('created_at', today) — same pattern as Overspending
        // Threshold and Rapid Spending — so it self-resets every calendar day
        // without any separate cron/reset logic needed.
        if ($riskSettings->daily_safe_to_spend_enabled) {
            // FIX: uses $daysRemainingIncludingToday (not $calendarDaysLeftInCycle)
            // so today's quota is spread across the correct number of days.
            $todayStartingQuota = ($trueRemainingAllowance + $spentToday) / $daysRemainingIncludingToday;
            $safeToSpendToday = max(0.00, $todayStartingQuota - $spentToday);
            $remainingQuotaPercent = $todayStartingQuota > 0
                ? ($safeToSpendToday / $todayStartingQuota) * 100
                : 0;

            if ($remainingQuotaPercent <= $riskSettings->daily_safe_to_spend_threshold) {
                $alreadyLoggedSafeSpend = RiskLog::where('user_id', $user->id)
                    ->where('anomaly_type', 'daily_safe_to_spend')
                    ->where('resolved', false)
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if (!$alreadyLoggedSafeSpend) {
                    $percentUsedToday = 100 - $remainingQuotaPercent;
                    $safeSpendLog = RiskLog::create([
                        'user_id'       => $user->id,
                        'anomaly_type'  => 'daily_safe_to_spend',
                        'severity_tier' => 'medium',
                        'description'   => "Daily Limit Warning ⏳: You've used " . round($percentUsedToday) . "% of today's safe-to-spend quota — only ₱" . number_format($safeToSpendToday, 2) . " left for today.",
                        'resolved'      => false,
                    ]);

                    try {
                        $user->notify(new BudgetRiskNotification($safeSpendLog));
                    } catch (\Throwable $e) {
                        \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
                    }
                }
            } else {
                // Quota recovered above threshold (e.g. a same-day expense was
                // edited or deleted) — resolve any still-open alert instead of
                // leaving a stale flag active for the rest of the day.
                $resolvedSafeSpendIds = RiskLog::where('user_id', $user->id)
                    ->where('anomaly_type', 'daily_safe_to_spend')
                    ->where('resolved', false)
                    ->whereDate('created_at', Carbon::today())
                    ->pluck('id');

                if ($resolvedSafeSpendIds->isNotEmpty()) {
                    RiskLog::whereIn('id', $resolvedSafeSpendIds)->update(['resolved' => true]);
                    foreach ($resolvedSafeSpendIds as $logId) {
                        DatabaseNotification::where('notifiable_id', $user->id)
                            ->where('notifiable_type', 'App\Models\User')
                            ->where('data', 'LIKE', '%"risk_log_id":' . $logId . '%')
                            ->get()
                            ->each(function ($notification) {
                                $data = $notification->data;
                                $data['resolved'] = true;
                                $notification->update(['data' => $data]);
                            });
                    }
                }
            }
        }

        //4. Category concentration check — independent of velocity checks above
        $this->checkCategoryConcentration($user, $cycleStartDate, $cycleEndDate, $actualStartingPool);

        //5. Rapid spending check — independent of the checks above
        $this->checkRapidSpending($user, $spentTodayDate);
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
    private function checkCategoryConcentration($user, $cycleStartDate, $cycleEndDate, $startingPool)
    {
        $totalSpent = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($q) {
                $q->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $categoryTotals = null;
        $percentage = 0;

        // Only meaningful once at least 30% of the cycle's pool has been spent.
        if ($totalSpent >= 200 && $startingPool > 0 && ($totalSpent / $startingPool) >= 0.30) {
            $categoryTotals = Expense::where('expenses.user_id', $user->id)
                ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
                ->whereNull('savings_goal_id')
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->where('expense_categories.name', 'NOT LIKE', '%Savings%')
                ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
                ->groupBy('expense_categories.name')
                ->orderByDesc('total')
                ->first();

            if ($categoryTotals) {
                $percentage = round(($categoryTotals->total / $totalSpent) * 100);
            }
        }

        $conditionHolds = $categoryTotals && $percentage >= 50;

        if ($conditionHolds) {
            // Avoid re-notifying for the same category within the same cycle.
            $alreadyNotified = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->where('data->anomaly_type', 'category_concentration')
                ->where('data->category', $categoryTotals->name)
                ->where('data->resolved', false)
                ->where('created_at', '>=', $cycleStartDate)
                ->exists();

            if (!$alreadyNotified) {
                try {
                    $user->notify(new \App\Notifications\CategoryConcentrationWarning(
                        $categoryTotals->name,
                        $percentage,
                        (float) $categoryTotals->total,
                        (float) $totalSpent
                    ));
                } catch (\Throwable $e) {
                    \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
                }
            }
        } else {
            // NEW: the dominant category no longer accounts for 50%+ of
            // spending this cycle (expense edited/deleted, or spending
            // diversified since). Resolve any still-open concentration
            // warnings instead of leaving them stuck "active" forever.
            DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->where('data->anomaly_type', 'category_concentration')
                ->where('data->resolved', false)
                ->where('created_at', '>=', $cycleStartDate)
                ->get()
                ->each(function ($notification) {
                    $data = $notification->data;
                    $data['resolved'] = true;
                    $notification->update(['data' => $data]);
                });
        }
    }

    /**
     * Rapid spending flag.
     * Counts same-day transactions that individually clear a lower bar
     * (15% of allowance) than the single "large transaction" alert (30%) —
     * requiring 3 purchases each over 30% in one day would almost never be
     * reachable for a weekly student budget, so this uses its own floor.
     */
    private function checkRapidSpending($user, $spentTodayDate)
    {
        $settings = RiskSetting::current();
        if (!$settings->rapid_spending_enabled) {
            return;
        }

        $activeBudget = WeeklyBudget::where('user_id', $user->id)
            ->orderBy('cycle_start_date', 'desc')
            ->first();

        if (!$activeBudget || $activeBudget->total_allowance <= 0) {
            return;
        }

        $largeTxnFloor = $activeBudget->total_allowance * 0.15;

        $largeTxnCountToday = Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $spentTodayDate)
            ->whereNull('savings_goal_id')
            ->where('amount', '>=', $largeTxnFloor)
            ->count();

        $anomalyType = 'rapid_spending';

        if ($largeTxnCountToday >= $settings->rapid_spending_count) {
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadyLoggedToday) {
                return;
            }

            $riskLog = RiskLog::create([
                'user_id'       => $user->id,
                'anomaly_type'  => $anomalyType,
                'severity_tier' => 'medium',
                'description'   => "Rapid Spending Detected ⚡: You've logged {$largeTxnCountToday} sizeable purchases today. Take a moment before your next one.",
                'resolved'      => false,
            ]);

            try {
                $user->notify(new BudgetRiskNotification($riskLog));
            } catch (\Throwable $e) {
                \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
            }
        } else {
            // Count no longer qualifies (e.g. one of today's transactions
            // was edited or deleted) — resolve any still-open alert instead
            // of leaving a stale flag active.
            $resolvedLogIds = RiskLog::where('user_id', $user->id)
                ->where('anomaly_type', $anomalyType)
                ->where('resolved', false)
                ->whereDate('created_at', Carbon::today())
                ->pluck('id');

            if ($resolvedLogIds->isNotEmpty()) {
                RiskLog::whereIn('id', $resolvedLogIds)->update(['resolved' => true]);
                foreach ($resolvedLogIds as $logId) {
                    DatabaseNotification::where('notifiable_id', $user->id)
                        ->where('notifiable_type', 'App\Models\User')
                        ->where('data', 'LIKE', '%"risk_log_id":' . $logId . '%')
                        ->get()
                        ->each(function ($notification) {
                            $data = $notification->data;
                            $data['resolved'] = true;
                            $notification->update(['data' => $data]);
                        });
                }
            }
        }
    }

    /**
     * Resolves any still-open "no expense logs" alert — called when the
     * student actually logs a fresh expense, since the alert itself is
     * created by a daily cron job (risk:check-log-gaps) rather than any
     * user action, so nothing else clears it automatically.
     */
    public function resolveNoExpenseLogsAlert($user)
    {
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'no_expense_logs')
            ->where('data->resolved', false)
            ->get()
            ->each(function ($notification) {
                $data = $notification->data;
                $data['resolved'] = true;
                $notification->update(['data' => $data]);
            });
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

        try {
            $user->notify(new \App\Notifications\LargeTransactionAlert(
                $expense->id,
                $expense->item_name,
                (float) $expense->amount,
                $percentage
            ));
        } catch (\Throwable $e) {
            \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
        }
    }

    public function resolveLargeTransactionAlert($user, $expenseId)
    {
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'large_transaction')
            ->where('data->expense_id', $expenseId)
            ->where('data->resolved', false)
            ->get()
            ->each(function ($notification) {
                $data = $notification->data;
                $data['resolved'] = true;
                $notification->update(['data' => $data]);
            });
    }
}