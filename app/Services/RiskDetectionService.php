<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\WeeklyBudget;
use App\Notifications\BudgetRiskNotification;
use App\Notifications\CategoryConcentrationWarning;
use App\Notifications\LargeTransactionAlert;
use App\Notifications\LowAllowanceWarning;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiskDetectionService
{
    private BudgetCycleService $cycleService;

    public function __construct(BudgetCycleService $cycleService)
    {
        $this->cycleService = $cycleService;
    }

    public function evaluateSpendingRisk($user)
    {
        $budget = WeeklyBudget::where('user_id', $user->id)
            ->orderBy('cycle_start_date', 'desc')
            ->first();

        if (!$budget) {
            return;
        }

        $cycle = $this->cycleService->resolve($budget, $user);

        $cycleStart        = $cycle['startDate'];
        $cycleEnd          = $cycle['endDate'];
        $daysElapsed       = max(1, (int) $cycle['daysElapsed']);
        $daysLeftInclToday = max(1, (int) $cycle['daysRemaining']);
        $cycleLength       = max(1, (int) $cycleStart->diffInDays($cycle['nextResetDate']));
        $resetDay          = $cycle['targetResetDay'];

        // Real pool = base + rollover, including money moved into savings goals.
        $pool      = (float) $cycle['effectiveTotalAllowance'];
        $remaining = (float) $budget->remaining_allowance;
        $spent     = (float) $cycle['totalSpentInCycle'];

        if ($pool <= 0) {
            return;
        }

        $settings   = RiskSetting::current();
        $todayStart = Carbon::today();

        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->whereNull('savings_goal_id')
            ->sum('amount');

        // Identical to the Dashboard's "Safe per Day".
        $safePerDay = ($remaining + $spentToday) / $daysLeftInclToday;

        // ---- 1. Pace check (per day) ---------------------------------------
        $allowedDailyVelocity = $pool / $cycleLength;
        $daysAfterToday       = max(0.5, $cycleLength - $daysElapsed);
        $velocity             = max($spent / $daysElapsed, $spentToday);
        $runwayDays           = $velocity > 0 ? $remaining / $velocity : $cycleLength;

        $paceType      = $daysElapsed <= 3 ? 'early_week_depletion' : 'rapid_overspending';
        $otherPaceType = $paceType === 'early_week_depletion' ? 'rapid_overspending' : 'early_week_depletion';
        $paceTriggered = ($runwayDays < $daysAfterToday && $velocity > $allowedDailyVelocity)
            || ($spentToday >= $allowedDailyVelocity * 2);

        $this->resolveOpenLogs($user, $otherPaceType);

        $this->syncRiskAlert($user, $paceType, $paceTriggered, $todayStart->copy(),
            function () use ($daysAfterToday, $runwayDays, $velocity, $remaining, $safePerDay, $resetDay) {
                $deficit = $daysAfterToday - $runwayDays;

                if ($deficit >= 3.0 || $runwayDays <= 1.0) {
                    $tier = 'high';
                } elseif ($deficit >= 1.0) {
                    $tier = 'medium';
                } else {
                    $tier = 'low';
                }

                return [$tier, $this->generateFeedbackString($velocity, $remaining, $safePerDay, $resetDay)];
            }
        );

        // ---- 2. Overspending threshold (once per cycle) --------------------
        $percentUsed = ($spent / $pool) * 100;

        $this->syncRiskAlert($user, 'overspending_threshold',
            $settings->overspending_enabled && $percentUsed >= $settings->overspending_threshold,
            $cycleStart->copy()->startOfDay(),
            function () use ($percentUsed, $spent, $pool, $daysLeftInclToday, $safePerDay) {
                return ['high',
                    "Budget Alert 🚨: You've used " . round($percentUsed) . "% of this week's allowance (₱"
                    . number_format($spent, 2) . ' of ₱' . number_format($pool, 2) . ') with '
                    . $daysLeftInclToday . ' ' . Str::plural('day', $daysLeftInclToday)
                    . ' left. Keep it to about ₱' . number_format($safePerDay, 2) . '/day to finish on budget.',
                ];
            }
        );

        // ---- 3. Daily safe-to-spend (per day) ------------------------------
        $quotaLeftPercent = $safePerDay > 0
            ? (max(0.0, $safePerDay - $spentToday) / $safePerDay) * 100
            : 0;

        $this->syncRiskAlert($user, 'daily_safe_to_spend',
            $settings->daily_safe_to_spend_enabled
                && $spentToday > 0
                && $quotaLeftPercent <= $settings->daily_safe_to_spend_threshold,
            $todayStart->copy(),
            function () use ($quotaLeftPercent, $safePerDay, $spentToday) {
                $left = max(0.0, $safePerDay - $spentToday);

                return ['medium',
                    "Daily Limit Warning ⏳: You've used " . round(100 - $quotaLeftPercent)
                    . "% of today's safe-to-spend amount — only ₱" . number_format($left, 2) . ' left for today.',
                ];
            }
        );

        // ---- 4-6. Remaining checks -----------------------------------------
        $this->checkCategoryConcentration($user, $cycleStart, $cycleEnd, $pool);
        $this->checkRapidSpending($user, $cycle['spentTodayDate'], $pool, $settings);
        $this->syncLowAllowance($user, $remaining, $pool, $cycleStart, $settings);
    }

    private function generateFeedbackString(float $velocity, float $remaining, float $safePerDay, string $resetDay): string
    {
        return "Pace Check ⚡: You're spending about ₱" . number_format($velocity, 2)
            . '/day and have ₱' . number_format($remaining, 2)
            . " left. To make it to your {$resetDay} reset, keep it to about ₱" . number_format($safePerDay, 2) . '/day.';
    }

    // ---------------------------------------------------------------------
    // RiskLog-backed alert lifecycle
    // ---------------------------------------------------------------------

    /**
     * Opens, keeps, re-opens or resolves a RiskLog-backed alert.
     * $windowStart = start of today (per-day alerts) or cycle start (per-cycle alerts).
     * Open alerts older than the window are stale and get resolved. A resolved alert
     * inside the window is re-opened instead of stacking a duplicate.
     */
    private function syncRiskAlert($user, string $type, bool $conditionHolds, Carbon $windowStart, callable $build): void
    {
        $this->resolveOpenLogs($user, $type, $windowStart);

        if (!$conditionHolds) {
            $this->resolveOpenLogs($user, $type);
            return;
        }

        $inWindow = RiskLog::where('user_id', $user->id)
            ->where('anomaly_type', $type)
            ->where('created_at', '>=', $windowStart);

        if ((clone $inWindow)->where('resolved', false)->exists()) {
            return;
        }

        $reopen = (clone $inWindow)->where('resolved', true)->latest('id')->first();

        if ($reopen) {
            $reopen->update(['resolved' => false]);

            $notifications = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->where('data->risk_log_id', $reopen->id)
                ->get();

            if ($notifications->isNotEmpty()) {
                $notifications->each(fn ($n) => $this->markNotification($n, false));
            } else {
                // Student dismissed the original; tell them again.
                $this->notifyRisk($user, $reopen);
            }

            return;
        }

        [$tier, $description] = $build();

        $riskLog = RiskLog::create([
            'user_id'       => $user->id,
            'anomaly_type'  => $type,
            'severity_tier' => $tier,
            'description'   => $description,
            'resolved'      => false,
        ]);

        $this->notifyRisk($user, $riskLog);
    }

    private function notifyRisk($user, RiskLog $riskLog): void
    {
        try {
            $user->notify(new BudgetRiskNotification($riskLog));
        } catch (\Throwable $e) {
            \Log::warning('Email notification failed (possibly offline): ' . $e->getMessage());
        }
    }

    private function resolveOpenLogs($user, string $type, ?Carbon $createdBefore = null): void
    {
        $query = RiskLog::where('user_id', $user->id)
            ->where('anomaly_type', $type)
            ->where('resolved', false);

        if ($createdBefore) {
            $query->where('created_at', '<', $createdBefore);
        }

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        RiskLog::whereIn('id', $ids)->update(['resolved' => true]);

        // Exact JSON match on risk_log_id — no LIKE prefix collisions (1 vs 12 vs 123).
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->whereIn('data->risk_log_id', $ids->all())
            ->get()
            ->each(fn ($n) => $this->markNotification($n, true));
    }

    // ---------------------------------------------------------------------
    // Notification-only alert helpers
    // ---------------------------------------------------------------------

    private function markNotification(DatabaseNotification $notification, bool $resolved): void
    {
        $data             = $notification->data;
        $data['resolved'] = $resolved;

        $attributes = ['data' => $data];

        if (!$resolved) {
            $attributes['read_at'] = null; // re-surface a re-opened alert
        }

        $notification->update($attributes);
    }

    private function notificationsSince($user, string $type, Carbon $since)
    {
        return DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', $type)
            ->where('created_at', '>=', $since)
            ->get();
    }

    // Alerts from earlier cycles can never be "active" again.
    private function expireBefore($user, string $type, Carbon $before): void
    {
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', $type)
            ->where('created_at', '<', $before)
            ->get()
            ->filter(fn ($n) => !($n->data['resolved'] ?? false))
            ->each(fn ($n) => $this->markNotification($n, true));
    }

    private function syncLowAllowance($user, float $remaining, float $pool, Carbon $cycleStart, RiskSetting $settings): void
    {
        $type = 'low_allowance_threshold';

        $this->expireBefore($user, $type, $cycleStart);

        $isLow = $settings->low_remaining_budget_enabled
            && $remaining <= $pool * ($settings->low_remaining_budget_threshold / 100);

        $existing = $this->notificationsSince($user, $type, $cycleStart);
        $open     = $existing->filter(fn ($n) => !($n->data['resolved'] ?? false));

        if (!$isLow) {
            $open->each(fn ($n) => $this->markNotification($n, true));
            return;
        }

        if ($open->isNotEmpty()) {
            return;
        }

        $previous = $existing->sortByDesc('created_at')->first();

        if ($previous) {
            $this->markNotification($previous, false);
            return;
        }

        try {
            $user->notify(new LowAllowanceWarning((int) round(($remaining / $pool) * 100), $remaining));
        } catch (\Throwable $e) {
            \Log::warning('Notification failed: ' . $e->getMessage());
        }
    }

    private function checkCategoryConcentration($user, Carbon $cycleStart, Carbon $cycleEnd, float $pool): void
    {
        $type = 'category_concentration';

        $this->expireBefore($user, $type, $cycleStart);

        $totalSpent = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStart, $cycleEnd])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($q) {
                $q->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $dominant   = null;
        $percentage = 0;

        // Only meaningful once at least 30% of the pool has been spent.
        if ($totalSpent >= 200 && $pool > 0 && ($totalSpent / $pool) >= 0.30) {
            $top = Expense::where('expenses.user_id', $user->id)
                ->whereBetween('expenses.transaction_date', [$cycleStart, $cycleEnd])
                ->whereNull('expenses.savings_goal_id')
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->where('expense_categories.name', 'NOT LIKE', '%Savings%')
                ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
                ->groupBy('expense_categories.name')
                ->orderByDesc('total')
                ->first();

            if ($top) {
                $percentage = (int) round(($top->total / $totalSpent) * 100);

                if ($percentage >= 50) {
                    $dominant = $top;
                }
            }
        }

        $existing = $this->notificationsSince($user, $type, $cycleStart);
        $isOpen   = fn ($n) => !($n->data['resolved'] ?? false);

        if (!$dominant) {
            $existing->filter($isOpen)->each(fn ($n) => $this->markNotification($n, true));
            return;
        }

        // A different category used to dominate: that alert no longer applies.
        $existing
            ->filter(fn ($n) => $isOpen($n) && ($n->data['category'] ?? null) !== $dominant->name)
            ->each(fn ($n) => $this->markNotification($n, true));

        $same = $existing
            ->filter(fn ($n) => ($n->data['category'] ?? null) === $dominant->name)
            ->sortByDesc('created_at')
            ->first();

        if ($same) {
            if (!$isOpen($same)) {
                $this->markNotification($same, false);
            }
            return;
        }

        try {
            $user->notify(new CategoryConcentrationWarning(
                $dominant->name,
                $percentage,
                (float) $dominant->total,
                $totalSpent
            ));
        } catch (\Throwable $e) {
            \Log::warning('Notification failed: ' . $e->getMessage());
        }
    }

    private function checkRapidSpending($user, $spentTodayDate, float $pool, RiskSetting $settings): void
    {
        $count = 0;
        $total = 0.0;
        $floor = $pool * 0.15;

        if ($settings->rapid_spending_enabled) {
            $large = Expense::where('user_id', $user->id)
                ->whereDate('transaction_date', $spentTodayDate)
                ->whereNull('savings_goal_id')
                ->where('amount', '>=', $floor)
                ->get(['amount']);

            $count = $large->count();
            $total = (float) $large->sum('amount');
        }

        $this->syncRiskAlert($user, 'rapid_spending',
            $settings->rapid_spending_enabled && $count >= $settings->rapid_spending_count,
            Carbon::today(),
            function () use ($count, $floor, $total) {
                return ['medium',
                    "Rapid Spending Detected ⚡: You logged {$count} purchases of ₱" . number_format($floor, 0)
                    . ' or more today (₱' . number_format($total, 2) . ' total). Take a moment before your next one.',
                ];
            }
        );
    }

    // ---------------------------------------------------------------------
    // Alerts tied to a single record
    // ---------------------------------------------------------------------

    public function resolveNoExpenseLogsAlert($user)
    {
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'no_expense_logs')
            ->where('data->resolved', false)
            ->get()
            ->each(fn ($n) => $this->markNotification($n, true));
    }

    /**
     * Idempotent per expense: creates, refreshes or resolves the alert. Measured against
     * the effective pool (base + rollover). $totalAllowance is accepted only so existing
     * callers keep working.
     */
    public function checkLargeTransaction($user, Expense $expense, $totalAllowance = null)
    {
        if ($expense->savings_goal_id) {
            return;
        }

        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$budget) {
            return;
        }

        $pool = (float) $this->cycleService->resolve($budget, $user)['effectiveTotalAllowance'];

        if ($pool <= 0) {
            return;
        }

        $existing = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'large_transaction')
            ->where('data->expense_id', $expense->id)
            ->first();

        if ($expense->amount < $pool * 0.30) {
            if ($existing) {
                $this->markNotification($existing, true); // edited down below the threshold
            }
            return;
        }

        $alert = new LargeTransactionAlert(
            $expense->id,
            $expense->item_name,
            (float) $expense->amount,
            (int) round(($expense->amount / $pool) * 100)
        );

        if ($existing) {
            $existing->update(['data' => $alert->toArray($user)]); // still large: refresh in place
            return;
        }

        try {
            $user->notify($alert);
        } catch (\Throwable $e) {
            \Log::warning('Notification failed: ' . $e->getMessage());
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
            ->each(fn ($n) => $this->markNotification($n, true));
    }
}