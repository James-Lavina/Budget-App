<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditExpense extends Component
{
    public $expenseId;
    public $expense_category_id;
    public $originalCategoryId; // category the expense had when the page loaded
    public $item_name;
    public $amount;
    public $transaction_date;
    public $isSavingsLinked = false; // true if this expense is a goal contribution

    protected $rules = [
        // No status check here: an existing expense may legitimately keep a
        // category that was disabled later. Switching to a *different* disabled
        // category is rejected manually in updateExpense().
        'expense_category_id' => 'required|exists:expense_categories,id',
        'item_name' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01|max:999999',
        'transaction_date' => 'required|date|before_or_equal:today',
    ];

    public function mount($id)
    {
        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget || !app(BudgetCycleService::class)->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be edited.');
            redirect()->route('student.expenses.index');
            return;
        }

        $this->expenseId = $expense->id;
        $this->expense_category_id = $expense->expense_category_id;
        $this->originalCategoryId = $expense->expense_category_id;
        $this->item_name = $expense->item_name;
        $this->amount = $expense->amount;
        $this->transaction_date = Carbon::parse($expense->transaction_date)->format('Y-m-d');
        $this->isSavingsLinked = !is_null($expense->savings_goal_id);
    }

    public function updateExpense()
    {
        $this->validate();

        $expense = Expense::where('id', $this->expenseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            session()->flash('error', 'Active budget cycle not found.');
            return;
        }

        if (!app(BudgetCycleService::class)->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be edited.');
            return redirect()->route('student.expenses.index');
        }

        // Category may only change to an enabled, non-Savings one. Keeping the
        // expense's existing category is always allowed, even if the admin has
        // disabled it since. Never trust the client's category id.
        if (!$this->isSavingsLinked
            && (int) $this->expense_category_id !== (int) $expense->expense_category_id
            && !ExpenseCategory::selectable()->where('id', $this->expense_category_id)->exists()) {
            $this->addError('expense_category_id', 'That category is no longer available. Please pick another one.');
            return;
        }

        $oldAmount = (float) $expense->amount;
        $newAmount = (float) $this->amount;
        $availableForThisExpense = (float) $currentBudget->remaining_allowance + $oldAmount;

        if ($newAmount > $availableForThisExpense) {
            $this->addError('amount', 'Insufficient allowance. You only have ₱' . number_format($availableForThisExpense, 2) . ' available for this transaction.');
            return;
        }

        // Defense in depth: even if the category picker is disabled in the
        // UI for savings-linked expenses, never trust the client. Force the
        // category back to the expense's original one for savings entries,
        // so a goal contribution can never be silently recategorized and
        // orphaned from its goal.
        $categoryIdToSave = $this->isSavingsLinked
            ? $expense->expense_category_id
            : $this->expense_category_id;

        DB::transaction(function () use ($expense, $currentBudget, $oldAmount, $newAmount, $categoryIdToSave) {
            $adjustmentDelta = $oldAmount - $newAmount;
            $currentBudget->remaining_allowance += $adjustmentDelta;
            $currentBudget->save();

            if ($expense->savings_goal_id) {
                $goal = SavingsGoal::find($expense->savings_goal_id);
                if ($goal && $goal->status !== 'abandoned') {
                    $newSaved = $goal->current_saved - $oldAmount + $newAmount;
                    if ($newSaved < 0) {
                        $newSaved = 0.00;
                    }
                    $isAchieved = $newSaved >= $goal->target_amount && $goal->target_amount > 0;
                    if ($isAchieved) {
                        $newSaved = $goal->target_amount;
                    }
                    $goal->update([
                        'current_saved' => $newSaved,
                        'status' => $isAchieved
                            ? 'achieved'
                            : ($goal->status === 'achieved' ? 'active' : $goal->status),
                    ]);
                }
            }

            $expense->update([
                'expense_category_id' => $categoryIdToSave,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
            ]);

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'expense_edited',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Edited \"{$expense->item_name}\": ₱" . number_format($oldAmount, 2) . " → ₱" . number_format($newAmount, 2),
            ]);
        });

        app(RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        session()->flash('success', 'Transaction modified. Limits calculated smoothly!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.edit-expense', [
            // Savings is never a selectable category — it's only ever applied
            // automatically via a goal contribution. Disabled categories are
            // hidden too, except the expense's ORIGINAL category, which stays
            // in the list so the picker never shows "nothing selected" and the
            // student can switch back to it after clicking another pill.
            'categories' => ExpenseCategory::whereRaw('LOWER(name) != ?', ['savings'])
                ->where(function ($q) {
                    $q->where('status', 'enabled')
                      ->orWhere('id', $this->originalCategoryId);
                })
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}