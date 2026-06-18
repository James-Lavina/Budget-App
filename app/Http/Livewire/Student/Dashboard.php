<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\DatabaseNotification; 
use Illuminate\Support\Str; 
use Livewire\Component;

class Dashboard extends Component
{
    public $safeToSpend = 0.00;
    public $currentBudget;
    public $daysRemaining = 7;

    public function mount() {
        $this->currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if(!$this->currentBudget) {
            return redirect()->route('student.budget-setup');
        }

        // 🌟 STEP 1: Run the automated refresh check before computing anything else
        $this->checkAndResetWeeklyCycle();

        // STEP 2: Compute metrics using the fresh or updated budget attributes
        $this->computeBehavioralMetrics();
    }

    /**
     * Automated Engine Check: Detects if the previous financial week has concluded.
     * If concluded, rolls over remaining funds as savings and triggers a new week.
     */
    private function checkAndResetWeeklyCycle() {
        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date);
        $endDate = $startDate->copy()->addDays(6);

        // If today has passed the active 7-day monitoring frame boundaries
        if ($today->greaterThan($endDate)) {
            DB::transaction(function () {
                // 1. Capture whatever unspent pocket money they managed to save
                $unspentSavings = max(0.00, $this->currentBudget->remaining_allowance);
                $oldTotalAllowance = $this->currentBudget->total_allowance;
                
                // Calculate historical performance metrics for the notification
                $amountSpent = max(0.00, $oldTotalAllowance - $unspentSavings);
                
                // 2. Behavioral Rollover Logic: Standard Baseline + Unspent Savings Reward
                $newWeeklyTotal = $oldTotalAllowance + $unspentSavings;

                // 3. Persist the updated configuration to advance the schedule forward to today
                $this->currentBudget->update([
                    'remaining_allowance' => $newWeeklyTotal,
                    'cycle_start_date' => Carbon::today(), // New week timeline officially starts now
                ]);
                
                // 4. Compile student-friendly text and write to Database Notifications
                if ($unspentSavings > 0) {
                    $description = "Weekly Review 📊: Outstanding financial discipline! Last week you spent ₱" . number_format($amountSpent, 2) . " and successfully saved ₱" . number_format($unspentSavings, 2) . ". Your fresh cycle starts with a boosted balance of ₱" . number_format($newWeeklyTotal, 2) . "!";
                    $severity = 'success';
                } else {
                    $description = "Weekly Review 📊: Cycle complete! You used your full ₱" . number_format($oldTotalAllowance, 2) . " allowance last week. A fresh tracking week has started—let's focus on steady daily pacing!";
                    $severity = 'info';
                }

                DatabaseNotification::create([
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\WeeklyBudgetReview',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => auth()->id(),
                    'data' => [
                        'anomaly_type'  => 'weekly_review',
                        'severity_tier' => $severity,
                        'description'   => $description,
                    ],
                    'read_at' => null,
                ]);
                
                // 5. Set transactional success alert context for the UI feedback banner
                if ($unspentSavings > 0) {
                    session()->flash('success', 'Outstanding financial discipline! You saved ₱' . number_format($unspentSavings, 2) . ' last week, which has been rolled over into your balance.');
                } else {
                    session()->flash('success', 'Welcome to a fresh tracking week! Your allowance baseline has been safely restored.');
                }
            });
        }
    }

    /**
     * Behavioral Metrics Engine: Formulates an intuitive daily spending countdown.
     * Subtracts today's live transaction volumes directly from today's quota.
     */
    public function computeBehavioralMetrics() {
        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date);
        $endDate = $startDate->copy()->addDays(6);

        // Double check boundary safety (fallback case)
        if($today->greaterThan($endDate)) {
            $this->daysRemaining = 0;
            $this->safeToSpend = 0.00;
            return;
        }

        $this->daysRemaining = $today->diffInDays($endDate) + 1;

        if($this->daysRemaining > 0) {
            // 1. Calculate how much the user has already spent TODAY (Using transaction_date)
            $spentToday = Expense::where('user_id', auth()->id())
                ->whereDate('transaction_date', Carbon::today())
                ->sum('amount');

            // 2. Reconstruct what the wallet balance was this morning before today's transactions
            $startingBudgetForRemainingDays = $this->currentBudget->remaining_allowance + $spentToday;

            // 3. Pinpoint today's total maximum baseline allocation quota
            $todayStartingQuota = $startingBudgetForRemainingDays / $this->daysRemaining;

            // 4. Real-time Deduction: Safe-to-Spend drops directly as transactions log
            $this->safeToSpend = max(0.00, $todayStartingQuota - $spentToday);
        } else {
            $this->safeToSpend = 0.00;
        }
    }

    public function deleteExpense($expenseId) {
        $expense = Expense::where('id', $expenseId)
            ->where('user_id', auth()->id())
            ->first();
    
        if(!$expense) {
            session()->flash('error', 'Expense record not found');
            return;
        }
    
        if($this->currentBudget) {
            DB::transaction(function () use ($expense) {
                // 1. Update the property instance directly so memory and database stay synced
                $this->currentBudget->remaining_allowance += $expense->amount;
                $this->currentBudget->save();
    
                // 2. Relational Rollover Check: If this transaction was a savings vault transfer
                if ($expense->savings_goal_id) {
                    $goal = \App\Models\SavingsGoal::find($expense->savings_goal_id);
                    
                    if ($goal) {
                        // Deduct the money back out of the specific goal target counter
                        $goal->current_saved -= $expense->amount;
    
                        // Absolute fallback safety guardrail to avoid negative numbers
                        if ($goal->current_saved < 0) {
                            $goal->current_saved = 0.00;
                        }
    
                        // Revert milestone status if it drops back below target threshold
                        if ($goal->status === 'achieved' && $goal->current_saved < $goal->target_amount) {
                            $goal->status = 'active';
                        }
    
                        $goal->save();
                    }
                }
    
                // 3. Purge the expense record safely out of the ledger
                $expense->delete();

                /**
                 * RISK METRIC SYNC: Clear today's lockout records before calculation.
                 * Wiping this allows the service to determine if removing this transaction
                 * completely drops their burn velocity back into the safe zone.
                 */
                \App\Models\RiskLog::where('user_id', auth()->id())
                    ->whereDate('created_at', Carbon::today())
                    ->delete();

                // 4. Force calculation assessment based on lower spending totals
                app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
            });
    
            // Now this method reads the freshly updated property in memory!
            $this->computeBehavioralMetrics();
            
            //  Livewire Event Dispatchers
            $this->emit('refreshSavings');
            $this->emit('expenseUpdated'); // This tells your chart component to re-render instantly!
    
            session()->flash('success', 'Transaction removed. Balance safely adjusted and savings progress updated!');
        } else {
            session()->flash('error', 'Unable to adjust framework. Active budget not found');
        }
    }

    public function render()
    {
        $recentExpenses = Expense::where('user_id', auth()->id())
            ->latest('id')
            ->take(5)
            ->get();

        return view('livewire.student.dashboard', [
            'recentExpenses' => $recentExpenses,
        ])->layout('layouts.student');
    }
}