<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExpenseCategoryWidget extends Component
{
    public $categoriesData = [];
    public $totalSpent = 0;
    public $hasExpenses = false;

    // Any event that changes money in the current cycle refreshes the doughnut.
    protected $listeners = [
        'expenseUpdated'       => 'loadCategoryBreakdown',
        'refreshBudgetMetrics' => 'loadCategoryBreakdown',
        'refreshSavings'       => 'loadCategoryBreakdown',
    ];

    public function mount()
    {
        $this->loadCategoryBreakdown();
    }

    public function loadCategoryBreakdown()
    {
        $userId       = auth()->id();
        $activeBudget = WeeklyBudget::where('user_id', $userId)->latest()->first();

        if (!$activeBudget) {
            $this->categoriesData = [];
            $this->totalSpent     = 0;
            $this->hasExpenses    = false;
            return;
        }

        $cycle = app(BudgetCycleService::class)->resolve($activeBudget, auth()->user());

        // All money out of the budget in this cycle, savings transfers included.
        $rawExpenses = Expense::where('expenses.user_id', $userId)
            ->whereBetween('expenses.transaction_date', [$cycle['startDate'], $cycle['endDate']])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name', 'expense_categories.color', DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.name', 'expense_categories.color')
            ->get();

        $this->totalSpent  = (float) $rawExpenses->sum('total_amount');
        $this->hasExpenses = $this->totalSpent > 0;

        if (!$this->hasExpenses) {
            $this->categoriesData = [];
            return;
        }

        $this->categoriesData = $rawExpenses->map(function ($item) {
            return [
                'name'       => $item->name,
                'total'      => floatval($item->total_amount),
                'percentage' => number_format(($item->total_amount / $this->totalSpent) * 100, 1),
                'color'      => ExpenseCategory::colorToHex($item->color),
            ];
        })->toArray();

        $this->dispatchBrowserEvent('updateCategoryChart', [
            'labels' => array_column($this->categoriesData, 'name'),
            'values' => array_column($this->categoriesData, 'total'),
            'colors' => array_column($this->categoriesData, 'color'),
        ]);
    }

    public function render()
    {
        return view('livewire.student.expense-category-widget');
    }
}