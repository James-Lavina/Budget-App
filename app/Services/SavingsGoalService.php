<?php

namespace App\Services;

use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RiskSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\ValidationException;

class SavingsGoalService
{
    /**
     * Single source of truth for "add funds to a savings goal" — used by
     * both GoalsManager (full page) and SavingsWidget (dashboard quick-add),
     * so the two entry points can never drift out of sync.
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
            $status = $goal->status;
        
            if ($newSavedBalance >= $goal->target_amount) {
                $status = 'achieved';
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
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => 'App\Notifications\SavingsGoalAchieved',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => [
                    'anomaly_type' => 'goal_achieved',
                    'severity_tier' => 'success',
                    'description' => 'Target Smashed! 🎯 You successfully saved ₱' . number_format($goal->target_amount, 2) . ' for your "' . $goal->target_name . '" goal.',
                ],
                'read_at' => null,
            ]);
        } else {
            $this->checkAndNotifySavingsMilestone($user, $goal);
        }

        app(RiskDetectionService::class)->evaluateSpendingRisk($user);
        $this->checkLowRemainingBudget($user, $currentBudget->fresh());

        return ['goal' => $goal, 'goalWasAchieved' => $goalWasAchieved];
    }

    private function checkAndNotifySavingsMilestone($user, SavingsGoal $goal): void
    {
        if ($goal->target_amount <= 0) {
            return;
        }

        $progressPercentage = round(($goal->current_saved / $goal->target_amount) * 100);
        $milestones = [25, 50, 75];
        $reachedMilestone = null;

        foreach ($milestones as $milestone) {
            if ($progressPercentage >= $milestone) {
                $reachedMilestone = $milestone;
            }
        }

        if (!$reachedMilestone) {
            return;
        }

        $alreadyNotified = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User')
            ->where('data', 'LIKE', '%"anomaly_type":"savings_milestone"%')
            ->where('data', 'LIKE', '%"milestone":' . $reachedMilestone . '%')
            ->where('data', 'LIKE', '%"goal_id":' . $goal->id . '%')
            ->exists();

        if (!$alreadyNotified) {
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => 'App\Notifications\SavingsMilestoneReached',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => [
                    'anomaly_type' => 'savings_milestone',
                    'milestone' => $reachedMilestone,
                    'goal_id' => $goal->id,
                    'severity_tier' => 'success',
                    'description' => "Milestone Unlocked! 📈 You've saved {$reachedMilestone}% of your target for '{$goal->target_name}'.",
                ],
                'read_at' => null,
            ]);
        }
    }

    private function checkLowRemainingBudget($user, WeeklyBudget $currentBudget): void
    {
        $riskSettings = RiskSetting::current();

        if (!$riskSettings->low_remaining_budget_enabled) {
            return;
        }

        $thresholdAmount = $currentBudget->total_allowance * ($riskSettings->low_remaining_budget_threshold / 100);

        if ($currentBudget->remaining_allowance <= $thresholdAmount) {
            $alreadyNotified = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                ->where('data', 'LIKE', '%"resolved":false%')
                ->where('created_at', '>=', $currentBudget->created_at)
                ->exists();

            if (!$alreadyNotified) {
                $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);

                DatabaseNotification::create([
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\LowAllowanceWarning',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $user->id,
                    'data' => [
                        'anomaly_type' => 'low_allowance_threshold',
                        'severity_tier' => 'medium',
                        'description' => "Great job saving! 🎯 Heads up: you have ₱" . number_format($currentBudget->remaining_allowance, 2) . " left for food and daily expenses this week.",
                        'resolved' => false,
                    ],
                    'read_at' => null,
                ]);
            }
        }
    }
}