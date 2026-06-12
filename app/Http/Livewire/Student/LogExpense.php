<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
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

        session()->flash('success', 'Expense tracked successfully!');

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.log-expense', [
            'categories' => ExpenseCategory::orderBy('name', 'asc')->get()
        ]);
    }
}
