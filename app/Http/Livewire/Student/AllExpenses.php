<?php

namespace App\Http\Livewire\Student;

use App\Models\ExpenseCategory; 
use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\SavingsGoal;
use App\Models\RiskLog;
use App\Services\RiskDetectionService;
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

        $pageIds = $this->buildQuery()
            ->latest('transaction_date')
            ->forPage($page, 10)
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

        $this->bulkDeleteTotal = Expense::whereIn('id', $this->selected)
            ->where('user_id', auth()->id())
            ->sum('amount');

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

        $count = $expenses->count();

        DB::transaction(function () use ($expenses, $currentBudget) {
            foreach ($expenses as $expense) {
                if ($currentBudget) {
                    // Refund allowance
                    $currentBudget->remaining_allowance += $expense->amount;

                    // Rollback Savings Goal progress if linked
                    if ($expense->savings_goal_id) {
                        $goal = SavingsGoal::find($expense->savings_goal_id);
                        if ($goal) {
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
                }

                $expense->delete();
            }

            if ($currentBudget) {
                $currentBudget->save();
            }

            RiskLog::where('user_id', auth()->id())
                ->whereDate('created_at', Carbon::today())
                ->delete();
        });

        if ($currentBudget) {
            app(RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->confirmingBulkDelete = false;
        $this->emit('refreshSavings');

        if ($currentBudget) {
            session()->flash('success', "{$count} " . Str::plural('transaction', $count) . " removed. Balance safely adjusted!");
        } else {
            session()->flash('error', 'Deleted, but no active budget was found to adjust allowance against.');
        }
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

        if ($currentBudget) {
            DB::transaction(function () use ($expense, $currentBudget) {
                // Refund allowance
                $currentBudget->remaining_allowance += $expense->amount;
                $currentBudget->save();

                // Rollback Savings Goal progress if linked
                if ($expense->savings_goal_id) {
                    $goal = SavingsGoal::find($expense->savings_goal_id);
                    if ($goal) {
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

                // Delete transaction & reset risk log evaluation
                $expense->delete();
                RiskLog::where('user_id', auth()->id())
                    ->whereDate('created_at', Carbon::today())
                    ->delete();

                app(RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
            });

            $this->emit('refreshSavings');
            session()->flash('success', 'Transaction removed. Balance safely adjusted!');
        } else {
            session()->flash('error', 'Unable to adjust allowance. Active budget period not found.');
        }
    }

    public function render()
    {
        $query = $this->buildQuery();

        $totalSpent = (clone $query)->sum('amount');
        $allExpenses = $query->latest('transaction_date')->paginate(10);
        $categories = ExpenseCategory::orderBy('name')->get();

        return view('livewire.student.all-expenses', [
            'allExpenses' => $allExpenses,
            'totalSpent' => $totalSpent,
            'categories' => $categories,
        ])->layout('layouts.student');
    }
}