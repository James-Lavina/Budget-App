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

    protected $listeners = ['refreshBudgetMetrics' => 'mount'];

    public function mount() {
        $this->currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if(!$this->currentBudget) {
            return redirect()->route('student.budget-setup');
        }

        $this->checkAndResetWeeklyCycle();

        $this->computeBehavioralMetrics();
    }

    private function checkAndResetWeeklyCycle() {
        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date)->startOfDay();
        
        $endDate = $startDate->copy()->next($this->currentBudget->reset_day)->subDay()->startOfDay();

        if ($today->greaterThan($endDate)) {
            DB::transaction(function () {
                $unspentSavings = max(0.00, $this->currentBudget->remaining_allowance);
                $oldTotalAllowance = $this->currentBudget->total_allowance;
                $amountSpent = max(0.00, $oldTotalAllowance - $unspentSavings);
                
                $user = auth()->user();
                $nextCycleBaseline = (float) ($user->default_allowance ?? 1000.00);
                $nextCycleResetDay = $user->default_reset_day ?? 'Monday';

                // Reward Rollover Calculation
                $newWeeklyTotal = $nextCycleBaseline + $unspentSavings;

                // Advance track attributes into next cycle row smoothly
                $this->currentBudget->update([
                    'total_allowance'     => $nextCycleBaseline,
                    'remaining_allowance' => $newWeeklyTotal,
                    'reset_day'           => $nextCycleResetDay,
                    'cycle_start_date'    => Carbon::today(), 
                ]);
                
                // Notifications Processing
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
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date)->startOfDay();
        $endDate = $startDate->copy()->next($this->currentBudget->reset_day)->subDay()->startOfDay();

        // Safety fallback guard
        if($today->greaterThan($endDate)) {
            $this->daysRemaining = 0;
            $this->safeToSpend = 0.00;
            return;
        }

        // Accurately compute relative remaining steps (e.g., from Monday to Sunday + 1 inclusive = 7 days)
        $this->daysRemaining = $today->diffInDays($endDate) + 1;

        if($this->daysRemaining > 0) {
            $spentToday = Expense::where('user_id', auth()->id())
                ->whereDate('transaction_date', Carbon::today())
                ->sum('amount');

            $startingBudgetForRemainingDays = $this->currentBudget->remaining_allowance + $spentToday;
            $todayStartingQuota = $startingBudgetForRemainingDays / $this->daysRemaining;

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
                $this->currentBudget->remaining_allowance += $expense->amount;
                $this->currentBudget->save();
    
                if ($expense->savings_goal_id) {
                    $goal = \App\Models\SavingsGoal::find($expense->savings_goal_id);
                    if ($goal) {
                        $goal->current_saved -= $expense->amount;
                        if ($goal->current_saved < 0) {
                            $goal->current_saved = 0.00;
                        }
                        if ($goal->status === 'achieved' && $goal->current_saved < $goal->target_amount) {
                            $goal->status = 'active';
                        }
                        $goal->save();
                    }
                }
    
                $expense->delete();

                \App\Models\RiskLog::where('user_id', auth()->id())
                    ->whereDate('created_at', Carbon::today())
                    ->delete();

                app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
            });
    
            $this->computeBehavioralMetrics();
            
            $this->emit('refreshSavings');
            $this->emit('expenseUpdated'); 
    
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