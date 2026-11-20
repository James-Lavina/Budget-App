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

        // Real spending only. Savings transfers still count as expenses everywhere else
        // (remaining, history, safe-to-spend) but must never trigger a risk alert.
        $spent = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStart, $cycleEnd])
            ->excludingSavings()
            ->sum('amount');

        $saved         = max(0.0, (float) $cycle['totalSpentInCycle'] - $spent);
        $riskRemaining = $remaining + $saved; // the balance as if nothing had been moved to goals

        if ($pool <= 0) {
            return;
        }

        $settings   = RiskSetting::current();
        $todayStart = Carbon::today();

        $spentTodayAll = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->sum('amount');

        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->excludingSavings()
            ->sum('amount');

        // Identical to the Dashboard's "Safe per Day". Unaffected by a deposit,
        // because the deposit lowers $remaining and raises $spentTodayAll by the same amount.
        $safePerDay = ($remaining + $spentTodayAll) / $daysLeftInclToday;

        // ---- 1. Pace check (per day) ---------------------------------------
        $pace = $this->cycleService->pace($cycle, $remaining, $spent, $spentToday, $saved);

        $allowedDailyVelocity = $pool / $cycleLength;
        $velocity             = max($pace['velocity'], $spentToday);
        $runwayDays           = $velocity > 0 ? $riskRemaining / $velocity : $cycleLength;
        $tierDays             = max(0.5, $pace['daysAfterToday']);

        $paceType      = $daysElapsed <= 3 ? 'early_week_depletion' : 'rapid_overspending';
        $otherPaceType = $paceType === 'early_week_depletion' ? 'rapid_overspending' : 'early_week_depletion';

        $paceTriggered = $pace['isOverPace']
            || ($spentToday >= $allowedDailyVelocity * 2);

        $this->resolveOpenLogs($user, $otherPaceType);

        $this->syncRiskAlert($user, $paceType, $paceTriggered, $todayStart->copy(),
            function () use ($tierDays, $runwayDays, $velocity, $remaining, $safePerDay, $resetDay) {
                $deficit = $tierDays - $runwayDays;

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
            function () use ($percentUsed, $spent, $pool, $daysLeftInclToday, $remaining) {
                return ['high',
                    "You've used " . round($percentUsed) . "% of this week's allowance (₱"
                    . number_format($spent, 2) . ' of ₱' . number_format($pool, 2) . ") and have {$daysLeftInclToday} "
                    . Str::plural('day', $daysLeftInclToday) . ' left. Spending about ₱'
                    . number_format($remaining / $daysLeftInclToday, 2) . ' a day will help you finish the week.',
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
                    "You've used " . round(100 - $quotaLeftPercent) . "% of today's limit. Only ₱"
                    . number_format($left, 2) . ' left to spend today.',
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
        return "You're spending about ₱" . number_format($velocity, 2)
            . '/day, but only ₱' . number_format($remaining, 2)
            . " is left until your {$resetDay} reset. Try to keep it to about ₱" . number_format($safePerDay, 2) . '/day.';
    }

    // ---------------------------------------------------------------------
    // RiskLog-backed alert lifecycle
    // ---------------------------------------------------------------------

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

        $openLog = (clone $inWindow)->where('resolved', false)->latest('id')->first();

        if ($openLog) {
            // Keep the stored text and tier in step with the latest numbers.
            [$tier, $description] = $build();

            if ($openLog->description !== $description || $openLog->severity_tier !== $tier) {
                $openLog->update(['severity_tier' => $tier, 'description' => $description]);
            }

            $this->syncRiskNotifications($user, $openLog);

            return;
        }

        $reopen = (clone $inWindow)->where('resolved', true)->latest('id')->first();

        if ($reopen) {
            [$tier, $description] = $build();

            $reopen->update([
                'resolved'      => false,
                'severity_tier' => $tier,
                'description'   => $description,
            ]);

            $this->syncRiskNotifications($user, $reopen, false);

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

    /**
     * Brings every notification tied to a risk log up to date with the log's text, tier
     * and title. Creates the notification when none exists. Pass $resolved = false to
     * reopen: the notification becomes unread, unresolved and shows as new.
     */
    private function syncRiskNotifications($user, RiskLog $log, ?bool $resolved = null): void
    {
        $notifications = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->risk_log_id', $log->id)
            ->get();

        if ($notifications->isEmpty()) {
            $this->notifyRisk($user, $log);
            return;
        }

        $title = BudgetRiskNotification::titleFor($log->anomaly_type);

        $notifications->each(function ($n) use ($log, $title, $resolved) {
            $data    = $n->data;
            $changed = ($data['description'] ?? null) !== $log->description
                || ($data['severity_tier'] ?? null) !== $log->severity_tier
                || ($data['title'] ?? null) !== $title;

            if (!$changed && $resolved !== false) {
                return;
            }

            $data['description']   = $log->description;
            $data['severity_tier'] = $log->severity_tier;
            $data['title']         = $title;

            $attributes = [];

            if ($resolved === false) {
                $data['resolved']         = false;
                $attributes['read_at']    = null;
                $attributes['created_at'] = now();
            }

            $attributes['data'] = $data;

            $n->update($attributes);
        });
    }

    private function notifyRisk($user, RiskLog $riskLog): void
    {
        try {
            $user->notify(new BudgetRiskNotification($riskLog));
        } catch (\Throwable $e) {
            NotificationLogger::logFailure($user, BudgetRiskNotification::class, $e, $riskLog->anomaly_type);
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
            // A reopened alert is new again: unread, and aged from now.
            $attributes['read_at']    = null;
            $attributes['created_at'] = now();
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

        $percentLeft = (int) round(($remaining / $pool) * 100);

        if ($open->isNotEmpty()) {
            $latest = $open->sortByDesc('created_at')->first();
            $fresh  = (new LowAllowanceWarning($percentLeft, $remaining))->toArray($user);
            $data   = $latest->data;

            if (($data['description'] ?? null) !== $fresh['description'] || ($data['title'] ?? null) !== $fresh['title']) {
                $data['description'] = $fresh['description'];
                $data['title']       = $fresh['title'];
                $latest->update(['data' => $data]);
            }

            return;
        }

        $previous = $existing->sortByDesc('created_at')->first();

        if ($previous) {
            $fresh = (new LowAllowanceWarning($percentLeft, $remaining))->toArray($user);
            $data  = $previous->data;

            $data['description'] = $fresh['description'];
            $data['title']       = $fresh['title'];
            $previous->update(['data' => $data]);

            $this->markNotification($previous->fresh(), false);

            return;
        }

        try {
            $user->notify(new LowAllowanceWarning($percentLeft, $remaining));
        } catch (\Throwable $e) {
            NotificationLogger::logFailure($user, LowAllowanceWarning::class, $e);
        }
    }

    private function checkCategoryConcentration($user, Carbon $cycleStart, Carbon $cycleEnd, float $pool): void
    {
        $type = 'category_concentration';

        $this->expireBefore($user, $type, $cycleStart);

        $totalSpent = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStart, $cycleEnd])
            ->excludingSavings()
            ->sum('amount');

        $dominant   = null;
        $percentage = 0;

        if ($totalSpent >= 200 && $pool > 0 && ($totalSpent / $pool) >= 0.30) {
            $top = Expense::where('expenses.user_id', $user->id)
                ->whereBetween('expenses.transaction_date', [$cycleStart, $cycleEnd])
                ->excludingSavings()
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
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

        $existing
            ->filter(fn ($n) => $isOpen($n) && ($n->data['category'] ?? null) !== $dominant->name)
            ->each(fn ($n) => $this->markNotification($n, true));

        $same = $existing
            ->filter(fn ($n) => ($n->data['category'] ?? null) === $dominant->name)
            ->sortByDesc('created_at')
            ->first();

        if ($same) {
            $fresh = (new CategoryConcentrationWarning(
                $dominant->name,
                $percentage,
                (float) $dominant->total,
                $totalSpent
            ))->toArray($user);

            $data = $same->data;

            if (($data['description'] ?? null) !== $fresh['description'] || ($data['title'] ?? null) !== $fresh['title']) {
                $data['description'] = $fresh['description'];
                $data['title']       = $fresh['title'];
                $same->update(['data' => $data]);
            }

            if (!$isOpen($same)) {
                $this->markNotification($same->fresh(), false);
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
            NotificationLogger::logFailure($user, CategoryConcentrationWarning::class, $e, $dominant->name);
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
                ->where('amount', '>=', $floor)
                ->excludingSavings()
                ->get(['amount']);

            $count = $large->count();
            $total = (float) $large->sum('amount');
        }

        $this->syncRiskAlert($user, 'rapid_spending',
            $settings->rapid_spending_enabled && $count >= $settings->rapid_spending_count,
            Carbon::today(),
            function () use ($count, $floor, $total) {
                return ['medium',
                    "You've made {$count} purchases of ₱" . number_format($floor, 0) . ' or more today (₱'
                    . number_format($total, 2) . ' in all). Take a moment before the next one.',
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
                $this->markNotification($existing, true);
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
            $existing->update(['data' => $alert->toArray($user)]);
            return;
        }

        try {
            $user->notify($alert);
        } catch (\Throwable $e) {
            NotificationLogger::logFailure($user, LargeTransactionAlert::class, $e, $expense->item_name);
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