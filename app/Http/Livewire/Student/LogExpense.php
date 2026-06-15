<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
         * TESTING OVERRIDE: Clear out today's risk logs for this specific user.
         * This removes the anti-spam block so that every single manual button click
         * during your testing phase is guaranteed to create a fresh risk log row.
         */
        RiskLog::where('user_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->delete();

        DB::transaction(function() use ($currentBudget) {
            // 1. Persist the transaction row first so RiskDetectionService can read it
            Expense::create([
                'user_id' => auth()->id(),
                'expense_category_id' => $this->expense_category_id,
                'merchant_name' => $this->merchant_name,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
                'tracking_type' => 'manual',
            ]);

            // 2. Run risk verification while the budget retains its context
            app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

            // 3. Deduct the funds and finalize balance tracking state changes
            $currentBudget->remaining_allowance -= $this->amount;
            $currentBudget->save();
        });

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