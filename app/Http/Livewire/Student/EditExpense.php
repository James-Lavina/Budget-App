<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Notifications\SavingsGoalAchieved;
use App\Services\BudgetCycleService;
use App\Services\NotificationLogger;
use App\Services\RiskDetectionService;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditExpense extends Component
{
    public $expenseId;
    public $expense_category_id;
    public $item_name;
    public $amount;
    public $transaction_date;
    public $minDate;
    public $isSavingsLinked = false;

    protected function rules()
    {
        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999',
            'transaction_date' => 'required|date|before_or_equal:today',
        ];

        if ($this->minDate) {
            $rules['transaction_date'] .= '|after_or_equal:' . $this->minDate;
        }

        return $rules;
    }

    protected $messages = [
        'transaction_date.after_or_equal' => "That date is before your current budget week started, so it can't be used.",
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

        $this->minDate = $this->currentCycleStart($currentBudget);

        $this->expenseId = $expense->id;
        $this->expense_category_id = $expense->expense_category_id;
        $this->item_name = $expense->item_name;
        $this->amount = $expense->amount;
        $this->transaction_date = Carbon::parse($expense->transaction_date)->format('Y-m-d');
        $this->isSavingsLinked = !is_null($expense->savings_goal_id);

        if (!$this->isSavingsLinked && !ExpenseCategory::selectable()->whereKey($this->expense_category_id)->exists()) {
            $this->expense_category_id = null;
        }
    }

    private function currentCycleStart(WeeklyBudget $budget): string
    {
        return app(BudgetCycleService::class)
            ->resolve($budget, auth()->user())['startDate']
            ->format('Y-m-d');
    }

    public function updateExpense()
    {
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            session()->flash('error', 'Active budget cycle not found.');
            return;
        }

        $this->minDate = $this->currentCycleStart($currentBudget);
        $this->validate();

        $expense = Expense::where('id', $this->expenseId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!app(BudgetCycleService::class)->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be edited.');
            return redirect()->route('student.expenses.index');
        }

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

        $categoryIdToSave = $this->isSavingsLinked
            ? $expense->expense_category_id
            : $this->expense_category_id;

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
                    NotificationLogger::logFailure(auth()->user(), SavingsGoalAchieved::class, $e, $affectedGoal->target_name);
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
            'categories' => ExpenseCategory::selectable()
                ->orderBy('is_fallback')
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}