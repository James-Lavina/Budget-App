<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Models\Expense;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\DB;
use App\Models\ExpenseCategory;

class ExpenseCategoryWidget extends Component
{
    public $categoriesData = [];
    public $totalSpent = 0;
    public $hasExpenses = false;

    // Listen for global application refreshes (e.g., when a new expense gets added elsewhere)
    protected $listeners = ['expenseUpdated' => 'loadCategoryBreakdown'];

    public function mount()
    {
        $this->loadCategoryBreakdown();
    }

    public function loadCategoryBreakdown()
    {
        $userId = auth()->id();

        $activeBudget = WeeklyBudget::where('user_id', $userId)->latest()->first();
        if ($activeBudget) {
            $rawExpenses = Expense::where('expenses.user_id', $userId)
                ->where('transaction_date', '>=', $activeBudget->cycle_start_date)
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->where('expense_categories.name', 'NOT LIKE', '%Savings%')
                ->select('expense_categories.name', 'expense_categories.color', DB::raw('SUM(expenses.amount) as total_amount'))
                ->groupBy('expense_categories.name', 'expense_categories.color')
                ->get();

            $this->totalSpent = $rawExpenses->sum('total_amount');
            $this->hasExpenses = $this->totalSpent > 0;

            if ($this->hasExpenses) {
                $this->categoriesData = $rawExpenses->map(function ($item) {
                    return [
                        'name'       => $item->name,
                        'total'      => floatval($item->total_amount),
                        'percentage' => number_format(($item->total_amount / $this->totalSpent) * 100, 1),
                        // NEW: same source of truth as the Dashboard chart.
                        'color'      => ExpenseCategory::colorToHex($item->color),
                    ];
                })->toArray();

                $chartLabels = array_column($this->categoriesData, 'name');
                $chartValues = array_column($this->categoriesData, 'total');
                $chartColors = array_column($this->categoriesData, 'color');

                $this->dispatchBrowserEvent('updateCategoryChart', [
                    'labels' => $chartLabels,
                    'values' => $chartValues,
                    'colors' => $chartColors,
                ]);
            } else {
                $this->categoriesData = [];
            }
        }
    }

    public function render()
    {
        return view('livewire.student.expense-category-widget');
    }
}