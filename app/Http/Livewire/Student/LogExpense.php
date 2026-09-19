<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\WeeklyBudget;
use App\Notifications\LowAllowanceWarning;
use App\Services\BudgetCycleService;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LogExpense extends Component
{
    public $expense_category_id;
    public $item_name;
    public $amount;
    public $transaction_date;
    public $sessionLog = [];

    // NEW: holds the suggested category id when item_name history disagrees
    // with the currently selected category. Null = no conflict detected.
    public $suggestedCategoryId = null;
    public $suggestedCategoryName = null;

    protected $rules = [
        'expense_category_id' => 'required|exists:expense_categories,id',
        'item_name' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0.01|max:999999',
        'transaction_date' => 'required|date|before_or_equal:today',
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

    // NEW: fires whenever the item name field changes. Debounced on the
    // Blade side (wire:model.live.debounce.500ms) so this doesn't query on
    // every keystroke.
    public function updatedItemName($value)
    {
        $this->checkCategoryMismatch();
    }

    public function updatedExpenseCategoryId($value)
    {
        // Re-check on category change too, and clear a stale suggestion
        // once the student picks the category being suggested.
        $this->checkCategoryMismatch();
    }

    private function checkCategoryMismatch()
    {
        $this->suggestedCategoryId = null;
        $this->suggestedCategoryName = null;

        $term = trim($this->item_name ?? '');
        if (strlen($term) < 3 || !$this->expense_category_id) {
            return;
        }

        // Look at this student's own logging history for the same item
        // name (case-insensitive exact match, not fuzzy — avoids false
        // positives on partial words). Find the category they used most
        // often for it.
        $dominant = Expense::where('user_id', auth()->id())
            ->whereRaw('LOWER(item_name) = ?', [strtolower($term)])
            ->select('expense_category_id', DB::raw('COUNT(*) as uses'))
            ->groupBy('expense_category_id')
            ->orderByDesc('uses')
            ->first();

        // Only nudge if: history exists, it disagrees with the current
        // pick, and the history has at least 2 prior uses (avoids
        // overriding a one-off mistake from the past).
        if ($dominant && $dominant->uses >= 2 && (int) $dominant->expense_category_id !== (int) $this->expense_category_id) {
            $category = ExpenseCategory::find($dominant->expense_category_id);
            if ($category) {
                $this->suggestedCategoryId = $category->id;
                $this->suggestedCategoryName = $category->name;
            }
        }
    }

    // Called when the student taps "Use [Category]" on the nudge.
    public function acceptSuggestedCategory()
    {
        if ($this->suggestedCategoryId) {
            $this->expense_category_id = $this->suggestedCategoryId;
        }
        $this->suggestedCategoryId = null;
        $this->suggestedCategoryName = null;
    }

    public function dismissSuggestion()
    {
        $this->suggestedCategoryId = null;
        $this->suggestedCategoryName = null;
    }

    // NEW: recent distinct item names the student has logged under the
    // currently selected category, most-recent-first, for one-tap fill.
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
        $this->item_name = $name;
        $this->checkCategoryMismatch();
    }

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

        $newExpense = null;
        DB::transaction(function() use ($currentBudget, &$newExpense) {
            $newExpense = Expense::create([
                'user_id' => auth()->id(),
                'expense_category_id' => $this->expense_category_id,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date . ' ' . Carbon::now()->format('H:i:s'),
                'tracking_type' => 'manual',
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

        $riskSettings = RiskSetting::current();
        if ($riskSettings->low_remaining_budget_enabled) {
            $thresholdAmount = $currentBudget->total_allowance * ($riskSettings->low_remaining_budget_threshold / 100);
            if ($currentBudget->remaining_allowance <= $thresholdAmount) {
                $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                    ->where('notifiable_type', 'App\Models\User')
                    ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                    ->where('data', 'LIKE', '%"resolved":false%')
                    ->where('created_at', '>=', $currentBudget->created_at)
                    ->exists();

                if (!$alreadyNotified) {
                    $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);
                    auth()->user()->notify(new LowAllowanceWarning($percentageLeft, $currentBudget->remaining_allowance));
                }
            } else {
                DatabaseNotification::where('notifiable_id', auth()->id())
                    ->where('notifiable_type', 'App\Models\User')
                    ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                    ->where('data', 'LIKE', '%"resolved":false%')
                    ->where('created_at', '>=', $currentBudget->created_at)
                    ->get()
                    ->each(function ($notification) {
                        $data = $notification->data;
                        $data['resolved'] = true;
                        $notification->update(['data' => $data]);
                    });
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

        $this->reset(['item_name', 'amount']);
        $this->suggestedCategoryId = null;
        $this->suggestedCategoryName = null;
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

        $this->sessionLog = array_values(array_filter($this->sessionLog, fn($e) => $e['id'] !== $expenseId));
        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        $riskService->resolveLargeTransactionAlert(auth()->user(), $expenseId);
    }

    public function render()
    {
        return view('livewire.student.log-expense', [
            'categories' => ExpenseCategory::whereRaw('LOWER(name) != ?', ['savings'])
                ->orderBy('name', 'asc')
                ->get(),
            'recentItems' => $this->recentItems,
        ])->layout('layouts.student');
    }
}