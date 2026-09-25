<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Notifications\SavingsGoalAchieved;
use App\Notifications\SavingsMilestoneReached;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsGoalService
{
    /**
     * Single source of truth for "add funds to a savings goal" — used by both
     * GoalsManager (full page) and SavingsWidget (dashboard quick-add).
     *
     * @throws ValidationException
     */
    public function addFunds($user, SavingsGoal $goal, float $amount): array
    {
        $currentBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$currentBudget) {
            throw ValidationException::withMessages([
                'fund_amount' => 'No active budget cycle found to draw funds from.',
            ]);
        }

        $remainingNeeded = $goal->target_amount - $goal->current_saved;

        if ($amount <= 0 || $amount > $currentBudget->remaining_allowance || $amount > $remainingNeeded) {
            throw ValidationException::withMessages([
                'fund_amount' => 'Transfer halted! The amount exceeds either your remaining budget (₱'
                    . number_format($currentBudget->remaining_allowance, 2) . ') or what is left to finish this goal (₱'
                    . number_format($remainingNeeded, 2) . ').',
            ]);
        }

        $goalWasAchieved = false;

        DB::transaction(function () use ($goal, $currentBudget, $amount, $user, &$goalWasAchieved) {
            $newSavedBalance = $goal->current_saved + $amount;
            $status          = $goal->status;

            if ($newSavedBalance >= $goal->target_amount) {
                $status          = 'achieved';
                $newSavedBalance = $goal->target_amount;
                $goalWasAchieved = true;
            }

            $goal->update(['current_saved' => $newSavedBalance, 'status' => $status]);
            $currentBudget->decrement('remaining_allowance', $amount);

            $savingsCategory = ExpenseCategory::firstOrCreate(
                ['name' => 'Savings'],
                ['description' => 'Capital intentionally set aside for milestone savings targets.']
            );

            Expense::create([
                'user_id'             => $user->id,
                'expense_category_id' => $savingsCategory->id,
                'savings_goal_id'     => $goal->id,
                'item_name'           => $goal->target_name,
                'amount'              => $amount,
                'transaction_date'    => now(),
                'tracking_type'       => 'manual',
            ]);
        });

        $goal->refresh();

        if ($goalWasAchieved) {
            try {
                $user->notify(new SavingsGoalAchieved($goal));
            } catch (\Throwable $e) {
                \Log::warning('Notification failed: ' . $e->getMessage());
            }
        } else {
            $this->checkAndNotifySavingsMilestone($user, $goal);
        }

        // Also handles the low-remaining-budget alert.
        app(RiskDetectionService::class)->evaluateSpendingRisk($user);

        return ['goal' => $goal, 'goalWasAchieved' => $goalWasAchieved];
    }

    private function checkAndNotifySavingsMilestone($user, SavingsGoal $goal): void
    {
        if ($goal->target_amount <= 0) {
            return;
        }

        // floor, not round: 74.6% must not announce 75%.
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
            \Log::warning('Notification failed: ' . $e->getMessage());
        }
    }
}