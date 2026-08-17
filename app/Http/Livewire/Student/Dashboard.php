<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\WeeklyBudgetReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public $safeToSpend = 0.00;
    public $currentBudget;
    public $daysRemaining = 7;

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

        $this->checkAndResetWeeklyCycle();
        $this->computeBehavioralMetrics();
    }

    private function checkAndResetWeeklyCycle()
    {
        if (!$this->currentBudget) {
            return;
        }

        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date)->startOfDay();
        $targetResetDay = $this->currentBudget->reset_day ?? auth()->user()->default_reset_day ?? 'Monday';

        $isScheduledResetDay = strtolower($today->format('l')) === strtolower($targetResetDay);
        $isPastCycleWindow   = $today->gte($startDate->copy()->addDays(7));

        if (($isScheduledResetDay && !$today->isSameDay($startDate)) || $isPastCycleWindow) {
            DB::transaction(function () use ($targetResetDay) {
                $unspentSavings    = max(0.00, (float) $this->currentBudget->remaining_allowance);
                $oldTotalAllowance = (float) $this->currentBudget->total_allowance;
                $amountSpent       = max(0.00, $oldTotalAllowance - $this->currentBudget->remaining_allowance);
                $user              = auth()->user();
        
                $nextCycleBaseline = (float) ($user->default_allowance ?? 1000.00);
                $nextCycleResetDay = $user->default_reset_day ?? $targetResetDay;
        
                $newWeeklyTotal    = $nextCycleBaseline + $unspentSavings;
        
                $this->currentBudget->update([
                    'total_allowance'     => $nextCycleBaseline,
                    'remaining_allowance' => $newWeeklyTotal,
                    'reset_day'           => $nextCycleResetDay,
                    'cycle_start_date'    => Carbon::today(),
                ]);
        
                $severity = $amountSpent > $oldTotalAllowance ? 'high' : 'low';
        
                // Trigger the dedicated notification class
                $user->notify(new WeeklyBudgetReview($amountSpent, $unspentSavings, $severity));
        
                session()->flash('message', 'Weekly budget reset successfully! ₱' . number_format($unspentSavings, 2) . ' rolled over to your new cycle.');
            });
        
            $this->currentBudget->refresh();
        }
    }

    public function computeBehavioralMetrics()
    {
        if ($this->currentBudget) {
            $this->currentBudget->refresh();
        }

        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date)->startOfDay();
        $targetResetDay = $this->currentBudget->reset_day ?? auth()->user()->default_reset_day ?? 'Monday';

        if (strtolower($startDate->format('l')) === strtolower($targetResetDay)) {
            $nextResetDate = $startDate->copy()->addWeek();
        } else {
            $nextResetDate = $startDate->copy()->next($targetResetDay);
        }

        if ($today->gte($nextResetDate)) {
            $this->daysRemaining = 0;
            $this->safeToSpend = 0.00;
            return;
        }

        $this->daysRemaining = max(1, (int) $today->diffInDays($nextResetDate));

        if ($this->daysRemaining > 0) {
            $spentToday = Expense::where('user_id', auth()->id())
                ->whereDate('transaction_date', Carbon::today())
                ->whereNull('savings_goal_id')
                ->sum('amount');

            $startingBudgetForRemainingDays = $this->currentBudget->remaining_allowance + $spentToday;
            $todayStartingQuota = $startingBudgetForRemainingDays / $this->daysRemaining;
            $this->safeToSpend = max(0.00, $todayStartingQuota - $spentToday);
        } else {
            $this->safeToSpend = 0.00;
        }
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

        if ($this->currentBudget) {
            DB::transaction(function () use ($expense) {
                $this->currentBudget->remaining_allowance += $expense->amount;
                $this->currentBudget->save();

                if ($expense->savings_goal_id) {
                    $goal = \App\Models\SavingsGoal::find($expense->savings_goal_id);
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

                $expense->delete();

                \App\Models\RiskLog::where('user_id', auth()->id())
                    ->whereDate('created_at', Carbon::today())
                    ->delete();

                app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
            });

            $this->computeBehavioralMetrics();
            $this->emit('refreshSavings');
            $this->emit('expenseUpdated');

            session()->flash('success', 'Expense deleted! Balance updated.');
        } else {
            session()->flash('error', 'Active budget not found. Unable to update balance.');
        }
    }

    public function render()
    {
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date)->startOfDay();
        $targetResetDay = $this->currentBudget->reset_day ?? auth()->user()->default_reset_day ?? 'Monday';

        if (strtolower($startDate->format('l')) === strtolower($targetResetDay)) {
            $nextResetDate = $startDate->copy()->addWeek();
        } else {
            $nextResetDate = $startDate->copy()->next($targetResetDay);
        }

        $endDate = $nextResetDate->copy()->subSecond();
        $today   = Carbon::today();

        // Fast-forward evaluation date if seeded/test expenses exist past real-time today
        $latestExpenseDate = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->max('transaction_date');

        $evalDate = $today;
        if ($latestExpenseDate) {
            $latestCarbon = Carbon::parse($latestExpenseDate)->startOfDay();
            if ($latestCarbon->gt($today)) {
                $evalDate = $latestCarbon;
            }
        }

        $daysElapsed = max(1, $startDate->diffInDays($evalDate) + 1);

        $todaySavingsTotal = Expense::where('user_id', auth()->id())
            ->whereDate('transaction_date', $today)
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        $hasSavingsToday = $todaySavingsTotal > 0;

        $totalSpent = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('savings_goal_id')
            ->sum('amount');

        $dailyVelocity       = $totalSpent / $daysElapsed;
        $futureDaysRemaining = max(0, 7 - $daysElapsed);
        $projectedRemaining  = max(0, $this->currentBudget->remaining_allowance - ($dailyVelocity * $futureDaysRemaining));
        $projectedDaysLeft   = $dailyVelocity > 0 ? ($this->currentBudget->remaining_allowance / $dailyVelocity) : $this->daysRemaining;

        $remainingDailyRate = $futureDaysRemaining > 0
            ? ($this->currentBudget->remaining_allowance / $futureDaysRemaining)
            : $this->currentBudget->remaining_allowance;

        $isDepleted     = $this->currentBudget->remaining_allowance <= 0;
        $isPaceCritical = !$isDepleted && ($projectedDaysLeft < $this->daysRemaining);
        $isQuotaHitRaw  = !$isDepleted && !$isPaceCritical && ($this->safeToSpend <= 0);
        $isSavingsLocked = $isQuotaHitRaw && $hasSavingsToday;
        $isDailyQuotaHit = $isQuotaHitRaw && !$hasSavingsToday;
        $isCriticalState = $isDepleted || $isPaceCritical;

        $totalAllowance      = max(1, $this->currentBudget->total_allowance);
        $remainingPercentage = round(($this->currentBudget->remaining_allowance / $totalAllowance) * 100);

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

        $colorPalette = [
            '#ff7052',
            '#5b46f6',
            '#4fd1c5',
            '#ffc043',
            '#f43f5e',
            '#3b82f6',
            '#10b981',
            '#8b5cf6'
        ];

        $alphabeticalCategories = Expense::where('expenses.user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->distinct()
            ->orderBy('expense_categories.name', 'asc')
            ->pluck('expense_categories.name')
            ->toArray();

        $categoryColorMap = [];
        foreach ($alphabeticalCategories as $index => $catName) {
            $categoryColorMap[$catName] = $colorPalette[$index % count($colorPalette)];
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

        $cycleExpenses = Expense::with('category')
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        foreach ($cycleExpenses as $exp) {
            $expDateKey = Carbon::parse($exp->transaction_date)->format('Y-m-d');
            if (array_key_exists($expDateKey, $dailyTotals)) {
                $dailyTotals[$expDateKey] += (float) $exp->amount;
                $catName = $exp->category->name ?? 'Uncategorized';
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

        $chartCategories = array_keys($categoryTotalsMap);
        $chartColors     = [];
        foreach ($chartCategories as $cat) {
            $chartColors[] = $categoryColorMap[$cat] ?? $colorPalette[0];
        }

        return view('livewire.student.dashboard', [
            'recentExpenses'         => $recentExpenses,
            'todaySavingsTotal'      => $todaySavingsTotal,
            'dailyVelocity'          => $dailyVelocity,
            'futureDaysRemaining'    => $futureDaysRemaining,
            'projectedRemaining'     => $projectedRemaining,
            'projectedDaysLeft'      => $projectedDaysLeft,
            'remainingDailyRate'     => $remainingDailyRate,
            'isDepleted'             => $isDepleted,
            'isPaceCritical'         => $isPaceCritical,
            'isSavingsLocked'        => $isSavingsLocked,
            'isDailyQuotaHit'        => $isDailyQuotaHit,
            'isCriticalState'        => $isCriticalState,
            'remainingPercentage'    => $remainingPercentage,
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