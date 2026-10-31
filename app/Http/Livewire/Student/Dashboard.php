<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Expense;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Models\ExpenseCategory;

class Dashboard extends Component
{
    public $safeToSpend = 0.00;
    public $spentToday = 0.00;
    public $dailyQuota = 0.00;
    public $currentBudget;
    public $daysRemaining = 7;
    public $confirmingDeleteId = null;

    protected $listeners = [
        'refreshBudgetMetrics' => 'mount',
        'expenseUpdated'       => 'mount',
    ];

    public function mount()
    {
        $this->currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$this->currentBudget) {
            return redirect()->route('student.budget-setup');
        }

        app(RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        $this->computeBehavioralMetrics();
    }

    public function computeBehavioralMetrics()
    {
        if ($this->currentBudget) {
            $this->currentBudget->refresh();
        }

        $cycle = app(BudgetCycleService::class)->resolve($this->currentBudget, auth()->user());

        if ($cycle['today']->gte($cycle['nextResetDate'])) {
            $this->daysRemaining = 0;
            $this->safeToSpend = 0.00;
            $this->dailyQuota = 0.00;
            return;
        }

        $this->daysRemaining = $cycle['daysRemaining'];

        // CHANGED: savings transfers now count toward spentToday.
        $spentToday = Expense::where('user_id', auth()->id())
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->sum('amount');

        $this->spentToday = $spentToday;

        $startingBudgetForRemainingDays = $this->currentBudget->remaining_allowance + $spentToday;
        $dailyQuota = $startingBudgetForRemainingDays / $this->daysRemaining;
        $this->dailyQuota = $dailyQuota;

        $this->safeToSpend = max(0.00, $dailyQuota - $spentToday);
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

        if (!$this->currentBudget) {
            session()->flash('error', 'Active budget not found. Unable to update balance.');
            return;
        }

        if (!app(BudgetCycleService::class)->isWithinCurrentCycle($this->currentBudget, auth()->user(), $expense->transaction_date)) {
            session()->flash('error', 'This expense belongs to a previous budget cycle and can no longer be deleted here.');
            return;
        }

        DB::transaction(function () use ($expense) {
            $lockedBudget = WeeklyBudget::whereKey($this->currentBudget->id)->lockForUpdate()->first();
            $lockedBudget->increment('remaining_allowance', $expense->amount);

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

        $this->computeBehavioralMetrics();
        $this->emit('refreshSavings');
        $this->emit('expenseUpdated');
        $this->emit('refreshNotifications');

        session()->flash('success', 'Expense deleted! Balance updated.');
    }

    public function render()
    {
        $cycle = app(BudgetCycleService::class)->resolve($this->currentBudget, auth()->user());

        $startDate     = $cycle['startDate'];
        $endDate       = $cycle['endDate'];
        $nextResetDate = $cycle['nextResetDate'];
        $today         = $cycle['today'];
        $daysElapsed   = $cycle['daysElapsed'];

        // CHANGED: savings now included in total spent.
        $totalSpent = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $cycle['evalDate']->copy()->endOfDay()])
            ->sum('amount');

        $hasNoSpendingYet = $totalSpent <= 0;

        $dailyVelocity       = $totalSpent / $daysElapsed;
        $futureDaysRemaining = $cycle['daysRemaining'];
        $isFinalDay          = $daysElapsed >= 7 || $futureDaysRemaining <= 1;
        $projectedRemaining  = $isFinalDay
            ? (float) $this->currentBudget->remaining_allowance
            : max(0, $this->currentBudget->remaining_allowance - ($dailyVelocity * $futureDaysRemaining));

        $projectedDaysLeft   = $dailyVelocity > 0 ? ($this->currentBudget->remaining_allowance / $dailyVelocity) : $this->daysRemaining;

        $remainingDailyRate = $futureDaysRemaining > 0
            ? ($this->currentBudget->remaining_allowance / $futureDaysRemaining)
            : $this->currentBudget->remaining_allowance;

        $isDepleted     = $this->currentBudget->remaining_allowance <= 0;
        $isPaceCritical = !$isDepleted && !$isFinalDay && ($projectedDaysLeft < $this->daysRemaining);
        $isDailyQuotaHit = !$isDepleted && !$isPaceCritical && ($this->safeToSpend <= 0);

        $totalAllowance      = max(1, $cycle['effectiveTotalAllowance']);
        $remainingPercentage = round(($this->currentBudget->remaining_allowance / $totalAllowance) * 100);

        // CHANGED: removed the 'savings_locked' state — savings no longer treated specially.
        if ($isDepleted) {
            $dashboardState = 'depleted';
        } elseif ($isPaceCritical) {
            $dashboardState = 'pace_critical';
        } elseif ($isDailyQuotaHit) {
            $dashboardState = 'quota_hit';
        } elseif ($hasNoSpendingYet) {
            $dashboardState = 'fresh_start';
        } else {
            $dashboardState = 'on_track';
        }

        if ($startDate->isSameMonth($endDate)) {
            $weekRangeLabel = $startDate->format('M j') . ' – ' . $endDate->format('j');
        } else {
            $weekRangeLabel = $startDate->format('M j') . ' – ' . $endDate->format('M j');
        }

        $daysOfWeek = [];
        $dailyTotals = [];
        $dailyCategoryBreakdown = [];
        $cycleDurationDays = max(1, (int) $startDate->diffInDays($nextResetDate));

        for ($i = 0; $i < $cycleDurationDays; $i++) {
            $currentLoopDate = $startDate->copy()->addDays($i);
            $dateKey = $currentLoopDate->format('Y-m-d');
            $daysOfWeek[$dateKey] = $currentLoopDate->format('D');
            $dailyTotals[$dateKey] = 0;
            $dailyCategoryBreakdown[$dateKey] = [];
        }

        // CHANGED: no longer excludes Savings category from the legend.
        $categoriesInCycle = Expense::where('expenses.user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->distinct()
            ->orderBy('expense_categories.name', 'asc')
            ->pluck('expense_categories.color', 'expense_categories.name');

        $categoryColorMap = [];
        foreach ($categoriesInCycle as $catName => $colorClass) {
            $categoryColorMap[$catName] = ExpenseCategory::colorToHex($colorClass);
        }

        $categoryTotals = Expense::where('expenses.user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $categoryTotalsMap = [];
        foreach ($categoryTotals as $cat) {
            $categoryTotalsMap[$cat->name] = (float) $cat->total_amount;
        }

        // CHANGED: no longer stripped out of the map — Savings is just another category now.
        $biggestCategoryName = array_key_first($categoryTotalsMap);

        $cycleExpenses = Expense::with('category')
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        foreach ($cycleExpenses as $exp) {
            $expDateKey = Carbon::parse($exp->transaction_date)->format('Y-m-d');
            $catName = $exp->category->name ?? 'Uncategorized';

            if (array_key_exists($expDateKey, $dailyTotals)) {
                $dailyTotals[$expDateKey] += (float) $exp->amount;
                $dailyCategoryBreakdown[$expDateKey][$catName] = ($dailyCategoryBreakdown[$expDateKey][$catName] ?? 0) + (float) $exp->amount;
            }
        }

        $highestSpent = max(array_values($dailyTotals));
        $maxDaily     = max(100, $highestSpent * 1.35);
        $step         = $maxDaily / 4;

        $recentExpenses = Expense::with('category')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->take(5)
            ->get();

        $appSettings = AppSetting::current();

        $chartCategories = array_keys($categoryTotalsMap);
        $chartColors     = [];
        foreach ($chartCategories as $cat) {
            $chartColors[] = $categoryColorMap[$cat] ?? $appSettings->primary_color;
        }

        $rolloverAmount = max(0, $totalAllowance - (float) $this->currentBudget->total_allowance - (float) $this->currentBudget->added_funds);

        $savingsGoalsForTotals = SavingsGoal::where('user_id', auth()->id())
            ->whereIn('status', ['active', 'achieved'])
            ->get();

        $savingsGoalsCount   = SavingsGoal::where('user_id', auth()->id())->where('status', 'active')->count();
        $totalSavingsSaved   = (float) $savingsGoalsForTotals->sum('current_saved');
        $totalSavingsTarget  = (float) $savingsGoalsForTotals->sum('target_amount');
        $savingsProgressPercent = $totalSavingsTarget > 0
            ? min(100, round(($totalSavingsSaved / $totalSavingsTarget) * 100))
            : 0;

        return view('livewire.student.dashboard', [
            'appSettings'            => $appSettings,
            'recentExpenses'         => $recentExpenses,
            'cycleStart'             => $startDate,
            'cycleEnd'               => $endDate,
            'nextResetDate'          => $nextResetDate,
            'rolloverAmount'         => $rolloverAmount,
            'totalSpent'             => $totalSpent,
            'hasNoSpendingYet'       => $hasNoSpendingYet,
            'dailyVelocity'          => $dailyVelocity,
            'futureDaysRemaining'    => $futureDaysRemaining,
            'projectedRemaining'     => $projectedRemaining,
            'projectedDaysLeft'      => $projectedDaysLeft,
            'remainingDailyRate'     => $remainingDailyRate,
            'isDepleted'             => $isDepleted,
            'isPaceCritical'         => $isPaceCritical,
            'isDailyQuotaHit'        => $isDailyQuotaHit,
            'isFinalDay'             => $isFinalDay,
            'remainingPercentage'    => $remainingPercentage,
            'weeklyAllowanceDisplay' => $totalAllowance,
            'dashboardState'         => $dashboardState,
            'weekRangeLabel'         => $weekRangeLabel,
            'biggestCategoryName'    => $biggestCategoryName,
            'savingsGoalsCount'      => $savingsGoalsCount,
            'totalSavingsSaved'      => $totalSavingsSaved,
            'savingsProgressPercent' => $savingsProgressPercent,
            'daysOfWeek'             => $daysOfWeek,
            'categoryColorMap'       => $categoryColorMap,
            'categoryTotalsMap'      => $categoryTotalsMap,
            'chartCategories'        => $chartCategories,
            'chartColors'            => $chartColors,
            'dailyTotals'            => $dailyTotals,
            'dailyCategoryBreakdown' => $dailyCategoryBreakdown,
            'maxDaily'               => $maxDaily,
            'step'                   => $step,
        ])->layout('layouts.student');
    }
}