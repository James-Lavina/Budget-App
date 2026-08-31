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
            // Group the OR conditions strictly to prevent SQL precedence errors
            ->when(filled($this->search), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('item_name', 'like', '%' . trim($this->search) . '%')
                        ->orWhere('merchant_name', 'like', '%' . trim($this->search) . '%');
                });
            })
            // Target the actual foreign key column 'expense_category_id'
            ->when(filled($this->selectedCategory), function ($q) {
                $q->where('expense_category_id', $this->selectedCategory);
            });
    }

    public function updatedSelectAll($value)
    {
        $page = $this->page ?: 1;

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
        $cycleService = app(BudgetCycleService::class);

        $pageExpenses = $this->buildQuery()
            ->latest('transaction_date')
            ->forPage($page, 10)
            ->get(['id', 'transaction_date']);

        // Only current-cycle expenses are selectable via "Select page" —
        // locked rows must never enter $selected, since that's what drives
        // both the "N items selected" count and what bulkDelete() acts on.
        $pageIds = $pageExpenses
            ->filter(function ($expense) use ($currentBudget, $cycleService) {
                return $currentBudget
                    && $cycleService->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date);
            })
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

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
        $cycleService = app(BudgetCycleService::class);

        $expenses = Expense::whereIn('id', $this->selected)
            ->where('user_id', auth()->id())
            ->get();

        // Re-derive the actually-deletable set here too, so the confirmation
        // modal's total matches what bulkDelete() will really remove.
        $deletable = $expenses->filter(function ($expense) use ($currentBudget, $cycleService) {
            return $currentBudget
                && $cycleService->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date);
        });

        $this->bulkDeleteTotal = $deletable->sum('amount');
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

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        $cycleService = app(BudgetCycleService::class);

        // Split the selection into what's actually deletable (current cycle)
        // vs. locked (past cycle). Past-cycle expenses are silently skipped
        // rather than blocking the whole batch — the student still gets the
        // ones they can delete.
        $deletable = collect();
        $lockedCount = 0;

        foreach ($expenses as $expense) {
            if ($currentBudget && $cycleService->isWithinCurrentCycle($currentBudget, auth()->user(), $expense->transaction_date)) {
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

        $count = $deletable->count();
        $deletedTotal = $deletable->sum('amount');
        $itemNames = $deletable->pluck('item_name')->take(5)->implode(', ');
        $deletableIds = $deletable->pluck('id');

        DB::transaction(function () use ($deletable, $currentBudget, $count, $deletedTotal, $itemNames) {
            foreach ($deletable as $expense) {
                // Refund allowance
                $currentBudget->remaining_allowance += $expense->amount;

                // Rollback Savings Goal progress if linked
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

            $currentBudget->save();

            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'expense_bulk_deleted',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => "Bulk deleted {$count} expenses (₱" . number_format($deletedTotal, 2) . " total): {$itemNames}" . ($count > 5 ? '...' : ''),
            ]);

            // NOTE: no longer wiping today's RiskLog entries here — see
            // deleteExpense() below for the same rationale.
        });

        $riskService = app(RiskDetectionService::class);
        $riskService->evaluateSpendingRisk(auth()->user());
        // NEW: resolve any large-transaction alerts tied to the deleted
        // expenses — the flagged purchases no longer exist.
        foreach ($deletableIds as $expenseId) {
            $riskService->resolveLargeTransactionAlert(auth()->user(), $expenseId);
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->confirmingBulkDelete = false;
        $this->emit('refreshSavings');

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
            // Refund allowance
            $currentBudget->remaining_allowance += $expense->amount;
            $currentBudget->save();

            // Rollback Savings Goal progress if linked
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

            // Delete transaction. NOTE: we no longer wipe today's RiskLog
            // entries here — evaluateSpendingRisk() replaces only the
            // matching anomaly_type in place if it still applies, so a
            // blanket delete of all today's logs was erasing unrelated,
            // still-valid warnings whenever any single expense was removed.
            $expense->delete();

            $riskService = app(RiskDetectionService::class);
            $riskService->evaluateSpendingRisk(auth()->user());
            // NEW: resolve any large-transaction alert tied to this
            // specific expense — the flagged purchase no longer exists.
            $riskService->resolveLargeTransactionAlert(auth()->user(), $expense->id);
        });

        $this->emit('refreshSavings');
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

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
        $cycleStart = null;
        $cycleEnd = null;

        if ($currentBudget) {
            $cycle = app(BudgetCycleService::class)->resolve($currentBudget, auth()->user());
            $cycleStart = $cycle['startDate'];
            $cycleEnd = $cycle['endDate'];
        }

        return view('livewire.student.all-expenses', [
            'allExpenses' => $allExpenses,
            'totalSpent' => $totalSpent,
            'categories' => $categories,
            'cycleStart' => $cycleStart,
            'cycleEnd' => $cycleEnd,
        ])->layout('layouts.student');
    }
}