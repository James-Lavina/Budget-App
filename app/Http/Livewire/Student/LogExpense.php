<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use App\Services\CategorySuggester;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class LogExpense extends Component
{
    public $expense_category_id;
    public $item_name;
    public $amount;
    public $transaction_date;
    public $minDate;
    public $sessionLog = [];

    // Category-mismatch state
    public $suggestedCategoryId = null;
    public $suggestedCategoryName = null;
    public $categoryAutoPicked = false;
    public $mismatchAcknowledged = false;

    protected function rules()
    {
        $rules = [
            'expense_category_id' => 'required|exists:expense_categories,id,status,enabled',
            'item_name'           => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0.01|max:999999',
            'transaction_date'    => 'required|date|before_or_equal:today',
        ];

        // A date before the current cycle would deduct from remaining_allowance
        // but be invisible to every cycle-scoped total, corrupting the numbers.
        if ($this->minDate) {
            $rules['transaction_date'] .= '|after_or_equal:' . $this->minDate;
        }

        return $rules;
    }

    protected $messages = [
        'expense_category_id.required'     => 'Please select an expense category.',
        'expense_category_id.exists'       => 'That category is no longer available. Please pick another one.',
        'item_name.required'               => 'Please provide an item description.',
        'amount.required'                  => 'Please specify the amount spent.',
        'amount.min'                       => 'Amount must be greater than zero.',
        'transaction_date.required'        => 'Please pick a transaction date.',
        'transaction_date.before_or_equal' => 'You cannot enter a future transaction.',
        'transaction_date.after_or_equal'  => "That date is before your current budget week started, so it can't be logged here.",
    ];

    // Recomputed on every call: the cycle can roll over while the page sits open.
    private function currentCycleStart(): ?string
    {
        $budget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        if (!$budget) {
            return null;
        }

        return app(BudgetCycleService::class)
            ->resolve($budget, auth()->user())['startDate']
            ->format('Y-m-d');
    }

    public function mount()
    {
        $this->minDate = $this->currentCycleStart();
        $this->transaction_date = Carbon::today()->format('Y-m-d');

        // Prefill when arriving from the What-If Simulator's "Add as Expense" button.
        $item   = request()->query('item');
        $amount = request()->query('amount');

        if (is_string($item) && trim($item) !== '') {
            $this->item_name = Str::limit(trim(strip_tags($item)), 255, '');
            $this->evaluateCategory(true);
        }

        if (is_numeric($amount) && (float) $amount > 0) {
            $this->amount = round((float) $amount, 2);
        }
    }

    // ---------------------------------------------------------------
    // Category detection
    // ---------------------------------------------------------------

    public function updatedItemName($value)
    {
        $this->mismatchAcknowledged = false;
        $this->evaluateCategory(true);
    }

    // Livewire 2 only fires updated hooks for client-side changes, never for assignments
    // made inside this class — so this always means "the student chose this category."
    public function updatedExpenseCategoryId($value)
    {
        $this->categoryAutoPicked   = false;
        $this->mismatchAcknowledged = false;
        $this->evaluateCategory(false);
    }

    private function evaluateCategory(bool $allowAutoPick): void
    {
        $this->clearSuggestion();

        if ($this->mismatchAcknowledged || mb_strlen(trim($this->item_name ?? '')) < 3) {
            return;
        }

        $match = app(CategorySuggester::class)->suggest(auth()->user(), $this->item_name);

        if (!$match) {
            if ($allowAutoPick && $this->categoryAutoPicked) {
                $this->expense_category_id = null;
                $this->categoryAutoPicked  = false;
            }
            return;
        }

        if ($allowAutoPick && (!$this->expense_category_id || $this->categoryAutoPicked)) {
            $this->expense_category_id = $match->id;
            $this->categoryAutoPicked  = true;
            return;
        }

        if ($this->expense_category_id && (int) $match->id !== (int) $this->expense_category_id) {
            $this->suggestedCategoryId   = $match->id;
            $this->suggestedCategoryName = $match->name;
        }
    }

    private function clearSuggestion(): void
    {
        $this->suggestedCategoryId   = null;
        $this->suggestedCategoryName = null;
    }

    private function currentCategoryIsFallback(): bool
    {
        return $this->expense_category_id
            && ExpenseCategory::where('id', $this->expense_category_id)->where('is_fallback', true)->exists();
    }

    public function acceptSuggestedCategory()
    {
        if ($this->suggestedCategoryId) {
            $this->expense_category_id = $this->suggestedCategoryId;
            $this->categoryAutoPicked  = false;
        }

        $this->mismatchAcknowledged = false;
        $this->clearSuggestion();
        $this->resetErrorBag('item_name');
    }

    public function dismissSuggestion()
    {
        $this->mismatchAcknowledged = true;
        $this->clearSuggestion();
        $this->resetErrorBag('item_name');
    }

    // ---------------------------------------------------------------
    // Speed-ups: frequent items + one-tap repeat
    // ---------------------------------------------------------------

    public function getFrequentItemsProperty()
    {
        if (filled($this->item_name)) {
            return collect();
        }

        $rows = Expense::where('user_id', auth()->id())
            ->whereNull('savings_goal_id')
            ->whereHas('category', fn ($q) => $q->selectable())
            ->select('item_name', 'expense_category_id', DB::raw('COUNT(*) as uses'), DB::raw('MAX(id) as last_id'))
            ->groupBy('item_name', 'expense_category_id')
            ->orderByDesc('uses')
            ->orderByDesc('last_id')
            ->limit(6)
            ->get();

        $amounts = Expense::whereIn('id', $rows->pluck('last_id'))->pluck('amount', 'id');

        return $rows->map(fn ($r) => [
            'id'        => (int) $r->last_id,
            'item_name' => $r->item_name,
            'amount'    => (float) ($amounts[$r->last_id] ?? 0),
        ]);
    }

    public function useFrequent($expenseId)
    {
        $past = Expense::where('id', $expenseId)->where('user_id', auth()->id())->first();

        if (!$past) {
            return false;
        }

        $category = ExpenseCategory::selectable()->find($past->expense_category_id);

        if (!$category) {
            session()->flash('error', 'That category is no longer available. Please pick another one.');
            return false;
        }

        $this->item_name            = $past->item_name;
        $this->expense_category_id  = $category->id;
        $this->amount               = round((float) $past->amount, 2);
        $this->categoryAutoPicked   = false;
        $this->mismatchAcknowledged = true;
        $this->clearSuggestion();
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('focus-amount');

        return true;
    }

    public function repeatExpense($expenseId)
    {
        $this->transaction_date = Carbon::today()->format('Y-m-d');

        if ($this->useFrequent($expenseId)) {
            $this->storeAndAddAnother();
        }
    }

    public function getRecentItemsProperty()
    {
        if (!$this->expense_category_id) {
            return collect();
        }

        return Expense::where('user_id', auth()->id())
            ->where('expense_category_id', $this->expense_category_id)
            ->select('item_name', DB::raw('MAX(transaction_date) as last_used'))
            ->groupBy('item_name')
            ->orderByDesc('last_used')
            ->limit(6)
            ->pluck('item_name');
    }

    public function pickRecentItem($name)
    {
        $this->item_name            = $name;
        $this->mismatchAcknowledged = false;
        $this->evaluateCategory(false);
    }

    // ---------------------------------------------------------------
    // Persistence
    // ---------------------------------------------------------------

    private function persistExpense()
    {
        $this->minDate = $this->currentCycleStart();
        $this->validate();

        if (!ExpenseCategory::selectable()->whereKey($this->expense_category_id)->exists()) {
            $this->addError('expense_category_id', 'That category is no longer available. Please pick another one.');
            return null;
        }

        $this->evaluateCategory(false);

        if ($this->suggestedCategoryId && !$this->mismatchAcknowledged && !$this->currentCategoryIsFallback()) {
            $this->addError('item_name', 'This item usually belongs to ' . $this->suggestedCategoryName . '. Confirm the category below.');
            return null;
        }

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
        if (!$currentBudget) {
            session()->flash('error', 'No active budget found. Set up your allowance first.');
            return null;
        }

        if ($this->amount > $currentBudget->remaining_allowance) {
            $this->addError('amount', 'Insufficient allowance. You only have ₱' . number_format($currentBudget->remaining_allowance, 2) . ' left.');
            return null;
        }

        $newExpense = null;
        DB::transaction(function () use ($currentBudget, &$newExpense) {
            $newExpense = Expense::create([
                'user_id'             => auth()->id(),
                'expense_category_id' => $this->expense_category_id,
                'item_name'           => $this->item_name,
                'amount'              => $this->amount,
                'transaction_date'    => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
                'tracking_type'       => 'manual',
            ]);

            $currentBudget->remaining_allowance -= $this->amount;
            $currentBudget->save();

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'expense_logged',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Logged \"{$newExpense->item_name}\" (₱" . number_format($newExpense->amount, 2) . ")",
            ]);
        });

        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        $riskService->checkLargeTransaction(auth()->user(), $newExpense, $currentBudget->total_allowance);
        $riskService->resolveNoExpenseLogsAlert(auth()->user());

        return $newExpense;
    }

    public function storeExpense()
    {
        $expense = $this->persistExpense();
        if (!$expense) return;

        session()->flash('success', 'Expense tracked successfully!');
        return redirect()->route('student.dashboard');
    }

    public function storeAndAddAnother()
    {
        $expense = $this->persistExpense();
        if (!$expense) return;

        $category = ExpenseCategory::find($expense->expense_category_id);
        $this->sessionLog[] = [
            'id'            => $expense->id,
            'item_name'     => $expense->item_name,
            'amount'        => $expense->amount,
            'category_name' => $category->name ?? 'Uncategorized',
            'category_icon' => $category->icon ?? 'default',
        ];

        $this->reset(['item_name', 'amount', 'expense_category_id']);
        $this->categoryAutoPicked   = false;
        $this->mismatchAcknowledged = false;
        $this->clearSuggestion();
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('expense-added');
    }

    public function removeFromSessionLog($expenseId)
    {
        $expense = Expense::where('id', $expenseId)->where('user_id', auth()->id())->first();
        if (!$expense) return;

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        if ($currentBudget && !app(BudgetCycleService::class)->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be removed here.');
            return;
        }

        DB::transaction(function () use ($expense, $currentBudget) {
            if ($currentBudget) {
                $currentBudget->remaining_allowance += $expense->amount;
                $currentBudget->save();
            }
            $expense->delete();
        });

        $this->sessionLog = array_values(array_filter($this->sessionLog, fn ($e) => $e['id'] !== $expenseId));
        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        $riskService->resolveLargeTransactionAlert(auth()->user(), $expenseId);
    }

    public function render()
    {
        return view('livewire.student.log-expense', [
            'categories'    => ExpenseCategory::selectable()->orderBy('is_fallback')->orderBy('name')->get(),
            'recentItems'   => $this->recentItems,
            'frequentItems' => $this->frequentItems,
        ])->layout('layouts.student');
    }
}