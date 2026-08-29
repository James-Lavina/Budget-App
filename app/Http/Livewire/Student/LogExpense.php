<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use App\Notifications\LowAllowanceWarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\DatabaseNotification; 
use Livewire\Component;

class LogExpense extends Component
{
    public $expense_category_id;
    public $merchant_name;
    public $item_name;
    public $amount;
    public $transaction_date;
    public $sessionLog = [];

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

    // Shared logic extracted so both buttons reuse it
    private function persistExpense()
    {
        $this->validate();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        if (!$currentBudget) {
            session()->flash('error', 'No active budget found. Set up your allowance first.');
            return null;
        }

        if ($this->amount > $currentBudget->remaining_allowance) {
            $this->addError('amount', 'Insufficient allowance. You only have ₱' . number_format($currentBudget->remaining_allowance, 2) . ' left.');
            return null;
        }

        RiskLog::where('user_id', auth()->id())->whereDate('created_at', Carbon::today())->delete();
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')
            ->where(function($query) {
                $query->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                    ->orWhere('data', 'LIKE', '%risk_log_id%');
            })->delete();

        $newExpense = null;

        DB::transaction(function() use ($currentBudget, &$newExpense) {
            $newExpense = Expense::create([
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

        $riskService = app(\App\Services\RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        $riskService->checkLargeTransaction(auth()->user(), $newExpense, $currentBudget->total_allowance);

        $thresholdAmount = $currentBudget->total_allowance * 0.20;
        if ($currentBudget->remaining_allowance <= $thresholdAmount) {
            $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                ->where('notifiable_type', 'App\Models\User')
                ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                ->where('created_at', '>=', $currentBudget->created_at)
                ->exists();

            if (!$alreadyNotified) {
                $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);
                auth()->user()->notify(new LowAllowanceWarning($percentageLeft, $currentBudget->remaining_allowance));
            }
        }

        return $newExpense;
    }

    public function storeExpense() {
        $expense = $this->persistExpense();
        if (!$expense) return;

        session()->flash('success', 'Expense tracked successfully!');
        return redirect()->route('student.dashboard');
    }

    public function storeAndAddAnother() {
        $expense = $this->persistExpense();
        if (!$expense) return;
    
        $category = ExpenseCategory::find($expense->expense_category_id);
    
        $this->sessionLog[] = [
            'id' => $expense->id,
            'item_name' => $expense->item_name,
            'amount' => $expense->amount,
            'category_name' => $category->name ?? 'Uncategorized',
            'category_icon' => $category->icon ?? 'default',
        ];
    
        // Reset only item-specific fields; keep category & date for the next entry
        $this->reset(['item_name', 'amount', 'merchant_name']);
        $this->resetErrorBag();
    
        $this->dispatchBrowserEvent('expense-added');
    }

    public function removeFromSessionLog($expenseId)
    {
        $expense = Expense::where('id', $expenseId)->where('user_id', auth()->id())->first();
        if (!$expense) return;

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        DB::transaction(function () use ($expense, $currentBudget) {
            if ($currentBudget) {
                $currentBudget->remaining_allowance += $expense->amount;
                $currentBudget->save();
            }
            $expense->delete();
        });

        $this->sessionLog = array_values(array_filter($this->sessionLog, fn($e) => $e['id'] !== $expenseId));

        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
    }

    public function render()
    {
        return view('livewire.student.log-expense', [
            'categories' => ExpenseCategory::whereRaw('LOWER(name) != ?', ['savings'])
                ->orderBy('name', 'asc')
                ->get()
        ])->layout('layouts.student');
    }
}