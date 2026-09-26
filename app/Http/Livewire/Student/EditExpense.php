<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Notifications\SavingsGoalAchieved;
use App\Services\BudgetCycleService;
use App\Services\RiskDetectionService;
use App\Services\SavingsGoalService;
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
    public $isSavingsLinked = false; // true if this expense is a goal contribution

    protected $rules = [
        'expense_category_id' => 'required|exists:expense_categories,id',
        'item_name' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01|max:999999',
        'transaction_date' => 'required|date|before_or_equal:today',
        'merchant_name' => 'nullable|string|max:255',
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
        $this->item_name = $expense->item_name;
        $this->amount = $expense->amount;
        $this->merchant_name = $expense->merchant_name;
        $this->transaction_date = Carbon::parse($expense->transaction_date)->format('Y-m-d');
        $this->isSavingsLinked = !is_null($expense->savings_goal_id);

        // If the expense's category was disabled by an admin since it was logged, the
        // picker no longer lists it. Clear the selection so the student must pick a
        // valid one instead of silently keeping a hidden category.
        if (!$this->isSavingsLinked && !ExpenseCategory::selectable()->whereKey($this->expense_category_id)->exists()) {
            $this->expense_category_id = null;
        }
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

        // Regular expenses must use a category students can actually pick (enabled, not
        // Savings). Never trust the client — a tampered id or a since-disabled category
        // is rejected here. Savings-linked expenses are exempt: their category is forced
        // back to the original below.
        if (!$this->isSavingsLinked && !ExpenseCategory::selectable()->whereKey($this->expense_category_id)->exists()) {
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

        // FIX: capture the affected goal + whether this edit newly crossed
        // 100%, so a student who bumps up a savings-linked expense enough
        // to hit their target gets notified — previously the status/amount
        // was mutated silently with no SavingsGoalAchieved /
        // SavingsMilestoneReached notification at all.
        $affectedGoal      = null;
        $goalNewlyAchieved = false;

        DB::transaction(function () use ($expense, $currentBudget, $oldAmount, $newAmount, $categoryIdToSave, &$affectedGoal, &$goalNewlyAchieved) {
            $adjustmentDelta = $oldAmount - $newAmount;
            $currentBudget->remaining_allowance += $adjustmentDelta;
            $currentBudget->save();

            if ($expense->savings_goal_id) {
                $goal = SavingsGoal::find($expense->savings_goal_id);

                if ($goal && $goal->status !== 'abandoned') {
                    $wasAchieved = $goal->status === 'achieved';

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

                    $affectedGoal      = $goal->fresh();
                    $goalNewlyAchieved = $isAchieved && !$wasAchieved;
                }
            }

            $expense->update([
                'expense_category_id' => $categoryIdToSave,
                'merchant_name' => $this->merchant_name ?: null,
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

        if ($affectedGoal) {
            if ($goalNewlyAchieved) {
                try {
                    auth()->user()->notify(new SavingsGoalAchieved($affectedGoal));
                } catch (\Throwable $e) {
                    \Log::warning('Notification failed: ' . $e->getMessage());
                }
            } else {
                app(SavingsGoalService::class)->checkAndNotifySavingsMilestone(auth()->user(), $affectedGoal);
            }
        }

        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        $riskService->checkLargeTransaction(auth()->user(), $expense->fresh());

        session()->flash('success', 'Transaction modified. Limits calculated smoothly!');
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.edit-expense', [
            // Enabled, non-Savings categories only. Savings is never a selectable
            // category — it's only ever applied automatically via a goal contribution.
            // The fallback ("Other") is listed last.
            'categories' => ExpenseCategory::selectable()
                ->orderBy('is_fallback')
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}