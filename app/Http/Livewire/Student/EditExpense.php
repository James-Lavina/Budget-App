<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditExpense extends Component
{
    public $expenseId;
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

    public function mount($id) {
        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->expenseId = $expense->id;
        $this->expense_category_id = $expense->expense_category_id;
        $this->item_name = $expense->item_name;
        $this->amount = $expense->amount;
        $this->merchant_name = $expense->merchant_name;
        $this->transaction_date = Carbon::parse($expense->transaction_date)->format('Y-m-d');
    }

    public function updateExpense() {
        $this->validate();

        $expense = Expense::where('id', $this->expenseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if(!$currentBudget) {
            session()->flash('error', 'Active budget cycle not found.');
            return; 
        }

        DB::transaction(function () use ($expense, $currentBudget) {
            $oldAmount = $expense->amount;
            $newAmount = $this->amount;
            $adjustmentDelta = $oldAmount - $newAmount;

            $currentBudget->remaining_allowance += $adjustmentDelta;
            $currentBudget->save();

            $expense->update([
                'expense_category_id' => $this->expense_category_id,
                'merchant_name' => $this->merchant_name ?: null,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
            ]);
        });

        \App\Models\RiskLog::where('user_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->delete();

        // Regenerate and evaluate the alert based on the edited numerical metrics
        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        session()->flash('success', 'Transaction modified. Limits calculated smoothly!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.edit-expense', [
            'categories' => ExpenseCategory::orderBy('name', 'asc')->get()
        ])->layout('layouts.student');
    }
}
