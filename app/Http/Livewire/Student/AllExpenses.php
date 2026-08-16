<?php

namespace App\Http\Livewire\Student;

use App\Models\ExpenseCategory; // Updated to match your ExpenseCategory model
use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\SavingsGoal;
use App\Models\RiskLog;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AllExpenses extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $selectedCategory = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'selectedCategory']);
        $this->resetPage();
    }

    public function deleteExpense($expenseId)
    {
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
        $query = Expense::where('user_id', auth()->id())
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