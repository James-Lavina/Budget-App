<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Notifications\SavingsGoalAchieved;
use App\Notifications\SavingsMilestoneReached;
use App\Models\ActivityLog;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsGoalService
{
    public function addFunds($user, SavingsGoal $goal, float $amount): array
    {
        $goalWasAchieved = false;
        $error           = null;

        DB::transaction(function () use ($user, $goal, $amount, &$goalWasAchieved, &$error) {
            $budget = WeeklyBudget::where('user_id', $user->id)->latest()->lockForUpdate()->first();
            $locked = SavingsGoal::where('id', $goal->id)->where('user_id', $user->id)->lockForUpdate()->first();

            if (!$budget) {
                $error = 'No active budget cycle found to draw funds from.';
                return;
            }

            if (!$locked || $locked->status !== 'active') {
                $error = 'Only active goals can receive funds.';
                return;
            }

            $remainingNeeded = round($locked->target_amount - $locked->current_saved, 2);

            if ($amount <= 0 || $amount > (float) $budget->remaining_allowance || $amount > $remainingNeeded) {
                $error = 'Transfer halted! The amount exceeds either your remaining budget (₱'
                    . number_format($budget->remaining_allowance, 2) . ') or what is left to finish this goal (₱'
                    . number_format($remainingNeeded, 2) . ').';
                return;
            }

            $newSavedBalance = $locked->current_saved + $amount;
            $status          = $locked->status;

            if ($newSavedBalance >= $locked->target_amount) {
                $status          = 'achieved';
                $newSavedBalance = $locked->target_amount;
                $goalWasAchieved = true;
            }

            $locked->update(['current_saved' => $newSavedBalance, 'status' => $status]);
            $budget->decrement('remaining_allowance', $amount);

            $savingsCategory = ExpenseCategory::firstOrCreate(
                ['name' => 'Savings'],
                ['description' => 'Capital intentionally set aside for milestone savings targets.']
            );

            Expense::create([
                'user_id'             => $user->id,
                'expense_category_id' => $savingsCategory->id,
                'savings_goal_id'     => $locked->id,
                'item_name'           => $locked->target_name,
                'amount'              => $amount,
                'transaction_date'    => now(),
                'tracking_type'       => 'manual',
            ]);
        });

        if ($error) {
            throw ValidationException::withMessages(['fund_amount' => $error]);
        }

        $goal->refresh();

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'savings_goal_funded',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Added ₱" . number_format($amount, 2) . " to savings goal \"{$goal->target_name}\""
                . ($goalWasAchieved ? ' — goal reached!' : ''),
        ]);

        if ($goalWasAchieved) {
            try {
                $user->notify(new SavingsGoalAchieved($goal));
            } catch (\Throwable $e) {
                NotificationLogger::logFailure($user, SavingsGoalAchieved::class, $e, $goal->target_name);
            }
        } else {
            $this->checkAndNotifySavingsMilestone($user, $goal);
        }

        app(RiskDetectionService::class)->evaluateSpendingRisk($user);

        return ['goal' => $goal, 'goalWasAchieved' => $goalWasAchieved];
    }

    public function checkAndNotifySavingsMilestone($user, SavingsGoal $goal): void
    {
        if ($goal->target_amount <= 0) {
            return;
        }

        $progress = (int) floor(($goal->current_saved / $goal->target_amount) * 100);
        $reached  = collect([25, 50, 75])->filter(fn ($m) => $progress >= $m)->max();

        if (!$reached) {
            return;
        }

        $alreadyNotified = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data->anomaly_type', 'savings_milestone')
            ->where('data->goal_id', $goal->id)
            ->where('data->milestone', $reached)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        try {
            $user->notify(new SavingsMilestoneReached($reached, $goal->target_name, $goal->id));
        } catch (\Throwable $e) {
            // FIX
            NotificationLogger::logFailure($user, SavingsMilestoneReached::class, $e, $goal->target_name);
        }
    }
}