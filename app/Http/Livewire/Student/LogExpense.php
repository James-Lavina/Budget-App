<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\DatabaseNotification; 
use Illuminate\Support\Str; 
use Livewire\Component;

class LogExpense extends Component
{
    public $expense_category_id;
    public $merchant_name;
    public $item_name;
    public $amount;
    public $transaction_date;

    protected $rules = [
        'expense_category_id' => 'required|exists:expense_categories,id',
        'item_name' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01|max:999999',
        'transaction_date' => 'required|date|before_or_equal:today',
        'merchant_name' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'expense_category_id.required' => 'Please select an expense category.',
        'item_name.required' => 'Please provide an item description.',
        'amount.required' => 'Please specify the amount spent.',
        'amount.min' => 'Amount must be greater than zero.',
        'transaction_date.required' => 'Please pick a transaction date.',
        'transaction_date.before_or_equal' => 'You cannot enter a future transaction.',
    ];

    public function mount() {
        $this->transaction_date = Carbon::today()->format('Y-m-d');
    }

    public function storeExpense() {
        $this->validate();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if(!$currentBudget) {
            session()->flash('error', 'No active budget found. Set up your allowance first.');
            return redirect()->route('student.budget-setup');
        }

        if ($this->amount > $currentBudget->remaining_allowance) {
            $this->addError('amount', 'Insufficient allowance. You only have ₱' . number_format($currentBudget->remaining_allowance, 2) . ' left.');
            return;
        }

        /**
         * TESTING OVERRIDES: Clear out today's records for this specific user.
         * FIX: Added a check to also clear out BudgetRiskNotifications during testing cycles.
         */
        RiskLog::where('user_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->delete();

        DatabaseNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')
            ->where(function($query) {
                $query->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                      ->orWhere('data', 'LIKE', '%risk_log_id%'); 
            })->delete();

        DB::transaction(function() use ($currentBudget) {
            Expense::create([
                'user_id' => auth()->id(),
                'expense_category_id' => $this->expense_category_id,
                'merchant_name' => $this->merchant_name,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
                'tracking_type' => 'manual',
            ]);

            $currentBudget->remaining_allowance -= $this->amount;
            $currentBudget->save();
        });

        // === POST-TRANSACTION PROCESSING ===
        // FIX: Moved outside and after the budget is decremented so calculations evaluate correctly!
        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        $thresholdAmount = $currentBudget->total_allowance * 0.20;
        
        if ($currentBudget->remaining_allowance <= $thresholdAmount) {
            
            $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                ->where('notifiable_type', 'App\Models\User')
                ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                ->where('created_at', '>=', $currentBudget->created_at)
                ->exists();

            if (!$alreadyNotified) {
                $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);
                
                DatabaseNotification::create([
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\LowAllowanceWarning',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => auth()->id(),
                    'data' => [
                        'anomaly_type' => 'low_allowance_threshold',
                        'severity_tier' => 'medium', 
                        'description' => "Budget Critical! ⚠️ Your remaining allowance has dropped to {$percentageLeft}% (₱" . number_format($currentBudget->remaining_allowance, 2) . " left). Consider lowering your daily velocity to survive the cycle.",
                    ],
                    'read_at' => null,
                ]);
            }
        }

        session()->flash('success', 'Expense tracked successfully!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.log-expense', [
            'categories' => ExpenseCategory::orderBy('name', 'asc')->get()
        ])->layout('layouts.student');
    }
}