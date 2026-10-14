<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\ExpenseCategory;
use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\SavingsGoal;
use App\Services\RiskDetectionService;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AllExpenses extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $selectedCategory = '';

    public $selected = [];
    public $selectAll = false;

    public $confirmingBulkDelete = false;
    public $bulkDeleteTotal = 0;

    public $confirmingDeleteId = null;

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function clearFilters()
    {
        $this->reset(['search', 'selectedCategory']);
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    private function buildQuery()
    {
        return Expense::where('user_id', auth()->id())
            ->with('category')
            ->when(filled($this->search), function ($q) {
                $q->where('item_name', 'like', '%' . trim($this->search) . '%');
            })
            ->when(filled($this->selectedCategory), function ($q) {
                $q->where('expense_category_id', $this->selectedCategory);
            });
    }

    // Resolves the cycle once per request instead of once per expense row.
    private function cycleWindow(): ?array
    {
        $budget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        if (!$budget) {
            return null;
        }

        $cycle = app(BudgetCycleService::class)->resolve($budget, auth()->user());

        return ['budget' => $budget, 'start' => $cycle['startDate'], 'end' => $cycle['endDate']];
    }

    private function inWindow(?array $window, $date): bool
    {
        return $window && Carbon::parse($date)->betweenIncluded($window['start'], $window['end']);
    }

    public function updatedSelectAll($value)
    {
        $page   = $this->page ?: 1;
        $window = $this->cycleWindow();

        // Only current-cycle expenses are selectable; locked rows must never enter $selected.
        $pageIds = $this->buildQuery()
            ->latest('transaction_date')
            ->forPage($page, 10)
            ->get(['id', 'transaction_date'])
            ->filter(fn ($expense) => $this->inWindow($window, $expense->transaction_date))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        if ($value) {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        } else {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        }
    }

    public function confirmBulkDelete()
    {
        if (empty($this->selected)) {
            return;
        }

        $window = $this->cycleWindow();

        $expenses = Expense::whereIn('id', $this->selected)
            ->where('user_id', auth()->id())
            ->get();

        $deletable = $expenses->filter(fn ($expense) => $this->inWindow($window, $expense->transaction_date));

        $this->bulkDeleteTotal      = $deletable->sum('amount');
        $this->confirmingBulkDelete = true;
    }

    public function cancelBulkDelete()
    {
        $this->confirmingBulkDelete = false;
    }

    public function bulkDelete()
    {
        $expenses = Expense::whereIn('id', $this->selected)
            ->where('user_id', auth()->id())
            ->get();

        if ($expenses->isEmpty()) {
            $this->confirmingBulkDelete = false;
            $this->selected = [];
            $this->selectAll = false;
            return;
        }

        $window        = $this->cycleWindow();
        $currentBudget = $window['budget'] ?? null;

        // Past-cycle expenses are skipped rather than blocking the whole batch.
        $deletable   = collect();
        $lockedCount = 0;

        foreach ($expenses as $expense) {
            if ($currentBudget && $this->inWindow($window, $expense->transaction_date)) {
                $deletable->push($expense);
            } else {
                $lockedCount++;
            }
        }

        if ($deletable->isEmpty()) {
            $this->selected = [];
            $this->selectAll = false;
            $this->confirmingBulkDelete = false;
            session()->flash('error', 'None of the selected expenses can be deleted — they belong to a previous budget cycle.');
            return;
        }

        $count        = $deletable->count();
        $deletedTotal = $deletable->sum('amount');
        $itemNames    = $deletable->pluck('item_name')->take(5)->implode(', ');
        $deletableIds = $deletable->pluck('id');

        DB::transaction(function () use ($deletable, $currentBudget, $count, $deletedTotal, $itemNames) {
            $lockedBudget = WeeklyBudget::whereKey($currentBudget->id)->lockForUpdate()->first();

            foreach ($deletable as $expense) {
                $lockedBudget->remaining_allowance += $expense->amount;

                if ($expense->savings_goal_id) {
                    $goal = SavingsGoal::find($expense->savings_goal_id);

                    if ($goal && $goal->status !== 'abandoned') {
                        $goal->current_saved -= $expense->amount;

                        if ($goal->current_saved < 0) {
                            $goal->current_saved = 0.00;
                        }

                        if ($goal->status === 'achieved' && $goal->current_saved < $goal->target_amount) {
                            $goal->status = 'active';
                        }

                        $goal->save();
                    }
                }

                $expense->delete();
            }

            $lockedBudget->save();

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'expense_bulk_deleted',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Bulk deleted {$count} expenses (₱" . number_format($deletedTotal, 2) . " total): {$itemNames}" . ($count > 5 ? '...' : ''),
            ]);
        });

        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());

        foreach ($deletableIds as $expenseId) {
            $riskService->resolveLargeTransactionAlert(auth()->user(), $expenseId);
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->confirmingBulkDelete = false;

        $this->emit('refreshSavings');
        $this->emit('refreshNotifications');

        $message = "{$count} " . Str::plural('transaction', $count) . " removed. Balance safely adjusted!";

        if ($lockedCount > 0) {
            $message .= " {$lockedCount} " . Str::plural('item', $lockedCount) . " from a previous cycle " . ($lockedCount === 1 ? 'was' : 'were') . " skipped.";
        }

        session()->flash('success', $message);
    }

    public function deleteExpense($expenseId)
    {
        $this->confirmingDeleteId = null;

        $expense = Expense::where('id', $expenseId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$expense) {
            session()->flash('error', 'Expense record not found.');
            return;
        }

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            session()->flash('error', 'Unable to adjust allowance. Active budget period not found.');
            return;
        }

        if (!app(BudgetCycleService::class)->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be deleted here.');
            return;
        }

        DB::transaction(function () use ($expense, $currentBudget) {
            WeeklyBudget::whereKey($currentBudget->id)->lockForUpdate()->first()
                ->increment('remaining_allowance', $expense->amount);

            if ($expense->savings_goal_id) {
                $goal = SavingsGoal::find($expense->savings_goal_id);

                if ($goal && $goal->status !== 'abandoned') {
                    $goal->current_saved -= $expense->amount;

                    if ($goal->current_saved < 0) {
                        $goal->current_saved = 0.00;
                    }

                    if ($goal->status === 'achieved' && $goal->current_saved < $goal->target_amount) {
                        $goal->status = 'active';
                    }

                    $goal->save();
                }
            }

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'expense_deleted',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Deleted \"{$expense->item_name}\" (₱" . number_format($expense->amount, 2) . ") dated " . Carbon::parse($expense->transaction_date)->format('Y-m-d'),
            ]);

            $expense->delete();

            $riskService = app(RiskDetectionService::class);
            $riskService->evaluateSpendingRisk(auth()->user());
            $riskService->resolveLargeTransactionAlert(auth()->user(), $expense->id);
        });

        $this->emit('refreshSavings');
        $this->emit('refreshNotifications');

        session()->flash('success', 'Transaction removed. Balance safely adjusted!');
    }

    public function render()
    {
        $query = $this->buildQuery();

        $totalSpent = (clone $query)
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $allExpenses = $query->latest('transaction_date')->paginate(10);

        $categories = ExpenseCategory::orderBy('name')->get();

        $window     = $this->cycleWindow();
        $cycleStart = $window['start'] ?? null;
        $cycleEnd   = $window['end'] ?? null;

        return view('livewire.student.all-expenses', [
            'allExpenses' => $allExpenses,
            'totalSpent'  => $totalSpent,
            'categories'  => $categories,
            'cycleStart'  => $cycleStart,
            'cycleEnd'    => $cycleEnd,
        ])->layout('layouts.student');
    }
}