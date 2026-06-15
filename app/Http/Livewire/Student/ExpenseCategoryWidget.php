<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Models\Expense;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\DB;

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
        
        // Locate the active budget tracking row to bound our chart parameters
        $activeBudget = WeeklyBudget::where('user_id', $userId)->latest()->first();

        if ($activeBudget) {
            // Aggregate totals grouped by category name for the current cycle
            $rawExpenses = Expense::where('expenses.user_id', $userId)
                ->where('transaction_date', '>=', $activeBudget->cycle_start_date)
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total_amount'))
                ->groupBy('expense_categories.name')
                ->get();

            $this->totalSpent = $rawExpenses->sum('total_amount');
            $this->hasExpenses = $this->totalSpent > 0;

            if ($this->hasExpenses) {
                // Map out array segments with computed percentages for our custom template legend
                $this->categoriesData = $rawExpenses->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'total' => floatval($item->total_amount),
                        'percentage' => number_format(($item->total_amount / $this->totalSpent) * 100, 1)
                    ];
                })->toArray();

                // Format structure arrays perfectly to pass right down into our ChartJS instance
                $chartLabels = array_column($this->categoriesData, 'name');
                $chartValues = array_column($this->categoriesData, 'total');

                $this->dispatchBrowserEvent('updateCategoryChart', [
                    'labels' => $chartLabels,
                    'values' => $chartValues
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