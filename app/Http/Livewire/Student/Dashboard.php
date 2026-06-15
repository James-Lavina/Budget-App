<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
                
                // 2. Behavioral Rollover Logic: Standard Baseline + Unspent Savings Reward
                $newWeeklyTotal = $this->currentBudget->total_allowance + $unspentSavings;

                // 3. Persist the updated configuration to advance the schedule forward to today
                $this->currentBudget->update([
                    'remaining_allowance' => $newWeeklyTotal,
                    'cycle_start_date' => Carbon::today(), // New week timeline officially starts now
                ]);
                
                // 4. Set transactional success alert context for the UI feedback banner
                if ($unspentSavings > 0) {
                    session()->flash('success', 'Outstanding financial discipline! You saved ₱' . number_format($unspentSavings, 2) . ' last week, which has been rolled over into your balance.');
                } else {
                    session()->flash('success', 'Welcome to a fresh tracking week! Your allowance baseline has been safely restored.');
                }
            });
        }
    }

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
            $this->safeToSpend = $this->currentBudget->remaining_allowance / $this->daysRemaining;
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