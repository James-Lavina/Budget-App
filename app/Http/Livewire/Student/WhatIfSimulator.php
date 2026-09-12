<?php

namespace App\Http\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use App\Models\Expense;
use App\Models\WeeklyBudget;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\BudgetCycleService;
use Illuminate\Support\Str;

class WhatIfSimulator extends Component
{
    // Form Inputs
    public $itemName = '';
    public $purchaseAmount = '';
    public $scenarioType = '';

    // Budget Calculations
    public $currentSafeToSpend = 0.00;
    public $newSafeToSpend = 0.00;
    public $dailyImpactDelta = 0.00;
    public $daysRemaining = 1;
    public $newRemaining = 0.00;

    // NEW: dynamic input ceiling, computed once per request and exposed to the view
    public $purchaseCeiling = 50000.00;

    // Chart data
    public $chartSpent = 0.00;
    public $chartSavings = 0.00;
    public $chartSimulated = 0.00;
    public $chartRemaining = 0.00;
    public $chartDeficit = 0.00;

    // Evaluation States
    public $isDeficit = false;
    public $isCriticalZero = false;
    public $isOfflineMode = false;
    public $aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';

    protected $queryString = [
        'purchaseAmount' => ['except' => ''],
        'scenarioType'   => ['except' => ''],
    ];

    /**
     * NEW: dynamic rules() replaces the old static $rules array so the
     * purchaseAmount ceiling can scale with the student's actual budget.
     * Ceiling = greater of (3x total_allowance) or (total_allowance + 5000),
     * so small budgets still get reasonable headroom to simulate with.
     */
    protected function rules()
    {
        return [
            'purchaseAmount' => 'nullable|numeric|min:0|max:' . $this->purchaseCeiling,
            'itemName'       => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'purchaseAmount.max' => 'That amount is unusually high for a simulation — try a smaller test value (up to ₱:max).',
    ];

    public function mount()
    {
        $this->refreshCeiling();
        $this->initSimulation();
    }

    /**
     * NEW: recomputes purchaseCeiling from the student's active budget.
     * Called on mount and whenever the budget could have changed underneath
     * this component (defensive — cheap query, guards against stale ceilings
     * if a student adds funds or changes settings in another tab/session).
     */
    private function refreshCeiling()
    {
        $currentBudget = WeeklyBudget::where('user_id', Auth::id())->latest()->first();
        $this->purchaseCeiling = $currentBudget
            ? (float) max($currentBudget->total_allowance * 3, $currentBudget->total_allowance + 5000)
            : 50000.00;
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'purchaseAmount') {
            $this->refreshCeiling();
        }
        $this->validateOnly($propertyName);
        $this->runSimulation();
    }

    public function initSimulation()
    {
        if ($this->purchaseAmount && is_numeric($this->purchaseAmount)) {
            $this->purchaseAmount = (float)$this->purchaseAmount;
            if ($this->scenarioType === 'major_purchase' && empty($this->itemName)) {
                $this->itemName = 'Quick Tested Item';
            }
        }

        $hasIncomingSimulation = ($this->purchaseAmount > 0);
        $this->calculateBaselines(!$hasIncomingSimulation);

        if ($hasIncomingSimulation) {
            $this->runSimulation();
        }
    }

    private function getCycleBounds($currentBudget)
    {
        $cycle = app(BudgetCycleService::class)->resolve($currentBudget, Auth::user());
        return [
            'startDate'      => $cycle['startDate'],
            'nextResetDate'  => $cycle['nextResetDate'],
            'endDate'        => $cycle['endDate'],
            'evalDate'       => $cycle['evalDate'],
            'daysRemaining'  => $cycle['daysRemaining'],
            'spentTodayDate' => $cycle['spentTodayDate'],
        ];
    }

    public function calculateBaselines($shouldDispatchChart = true)
    {
        $currentBudget = WeeklyBudget::where('user_id', Auth::id())->latest()->first();
        if (!$currentBudget) {
            $this->aiInsight = "Please set up an active weekly budget before testing purchase impacts.";
            return;
        }

        $bounds = $this->getCycleBounds($currentBudget);
        $this->daysRemaining = $bounds['daysRemaining'];

        $realConsumed = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['evalDate']->copy()->endOfDay()])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $totalSavings = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        if ($this->daysRemaining === 0) {
            $this->currentSafeToSpend = 0.00;
            $this->newSafeToSpend = 0.00;
            $this->newRemaining = 0.00;
            $this->dailyImpactDelta = 0.00;
            $this->isDeficit = ((float) $currentBudget->remaining_allowance < 0);

            $this->chartSpent     = (float) $realConsumed;
            $this->chartSavings   = (float) $totalSavings;
            $this->chartSimulated = 0.00;
            $this->chartRemaining = 0.00;
            $this->chartDeficit   = 0.00;

            if ($shouldDispatchChart) {
                $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                    'spent'     => $this->chartSpent,
                    'savings'   => $this->chartSavings,
                    'simulated' => $this->chartSimulated,
                    'remaining' => $this->chartRemaining,
                    'deficit'   => $this->chartDeficit,
                ]);
            }
            return;
        }

        $todaySpent = Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', $bounds['spentTodayDate'])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $morningBalance = $currentBudget->remaining_allowance + $todaySpent;
        $todayStartingQuota = $morningBalance / $this->daysRemaining;
        $this->currentSafeToSpend = max(0, $todayStartingQuota - $todaySpent);
        $this->newSafeToSpend = $this->currentSafeToSpend;
        $this->newRemaining = (float) $currentBudget->remaining_allowance;
        $this->dailyImpactDelta = 0.00;
        $this->isDeficit = ((float) $currentBudget->remaining_allowance < 0);
        $this->isCriticalZero = !$this->isDeficit && ((float) $currentBudget->remaining_allowance <= 0.01);

        $this->chartSpent     = (float) $realConsumed;
        $this->chartSavings   = (float) $totalSavings;
        $this->chartSimulated = 0.00;
        $this->chartRemaining = (float) $currentBudget->remaining_allowance;
        $this->chartDeficit   = 0.00;

        if ($shouldDispatchChart) {
            $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                'spent'     => $this->chartSpent,
                'savings'   => $this->chartSavings,
                'simulated' => $this->chartSimulated,
                'remaining' => $this->chartRemaining,
                'deficit'   => $this->chartDeficit,
            ]);
        }
    }

    public function applyPreset($amount, $name = '')
    {
        $this->refreshCeiling();

        $existingAmount = is_numeric($this->purchaseAmount) ? (float) $this->purchaseAmount : 0;
        $newAmount = min($existingAmount + (float) $amount, $this->purchaseCeiling);

        $this->purchaseAmount = number_format($newAmount, 2, '.', '');
        $this->itemName = trim($this->itemName) !== ''
            ? trim($this->itemName) . ' + ' . $name
            : $name;
        $this->runSimulation();
    }

    public function runSimulation()
    {
        $currentBudget = WeeklyBudget::where('user_id', Auth::id())->latest()->first();
        if (!$currentBudget) {
            return;
        }

        $bounds = $this->getCycleBounds($currentBudget);
        $this->daysRemaining = $bounds['daysRemaining'];

        $simulatedCost = is_numeric($this->purchaseAmount) ? (float)$this->purchaseAmount : 0;

        // NEW: hard-clamp against the ceiling even if validation was bypassed
        // (e.g. programmatic property sets, queryString hydration on load).
        if ($simulatedCost > $this->purchaseCeiling) {
            $simulatedCost = $this->purchaseCeiling;
            $this->purchaseAmount = number_format($simulatedCost, 2, '.', '');
        }

        if ($simulatedCost <= 0) {
            $this->calculateBaselines(true);
            $this->aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';
            return;
        }

        $realConsumed = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['evalDate']->copy()->endOfDay()])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $totalSavings = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        $todaySpent = Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', $bounds['spentTodayDate'])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $this->newRemaining = $currentBudget->remaining_allowance - $simulatedCost;
        $this->isDeficit = ($this->newRemaining < 0);
        $this->isCriticalZero = !$this->isDeficit && ($this->newRemaining <= 0.01);

        if ($this->isDeficit || $this->daysRemaining === 0) {
            $this->newSafeToSpend = 0.00;
        } else {
            $hypotheticalMorningBalance = $this->newRemaining + $todaySpent;
            $hypotheticalStartingQuota = $hypotheticalMorningBalance / $this->daysRemaining;
            $this->newSafeToSpend = max(0, $hypotheticalStartingQuota - $todaySpent);
        }

        $this->dailyImpactDelta = max(0, $this->currentSafeToSpend - $this->newSafeToSpend);

        $remainingForChart = max(0, $this->newRemaining);
        $deficitForChart = $this->isDeficit ? abs($this->newRemaining) : 0.00;

        $this->chartSpent     = (float) $realConsumed;
        $this->chartSavings   = (float) $totalSavings;
        $this->chartSimulated = (float) $simulatedCost;
        $this->chartRemaining = (float) $remainingForChart;
        $this->chartDeficit   = (float) $deficitForChart;

        $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
            'spent'     => $this->chartSpent,
            'savings'   => $this->chartSavings,
            'simulated' => $this->chartSimulated,
            'remaining' => $this->chartRemaining,
            'deficit'   => $this->chartDeficit,
        ]);

        $this->generateSimulationInsight($simulatedCost);
    }

    private function generateSimulationInsight($simulatedCost)
    {
        $item = trim($this->itemName) !== '' ? $this->itemName : 'this item';
        $this->isOfflineMode = false;

        $roundedCost = (int) (round($simulatedCost / 5) * 5);
        $cacheKey = sprintf(
            'simulator_ai:%d:%s:%d:%d:%d',
            Auth::id(),
            Str::slug($item),
            $roundedCost,
            $this->daysRemaining,
            $this->isDeficit ? 1 : 0
        );

        $cachedInsight = Cache::get($cacheKey);
        if ($cachedInsight !== null) {
            $this->aiInsight = $cachedInsight['text'];
            $this->isOfflineMode = $cachedInsight['offline'];
            return;
        }

        try {
            $settings = \App\Models\IntegrationSetting::current();
            $apiKey = $settings->groq_api_key ?: (env('GROQ_API_KEY') ?? config('services.groq.key'));

            if (!empty($apiKey)) {
                $prompt = "Analyze this student spending simulation scenario:\n" .
                "- Item: {$item}\n" .
                "- Cost: ₱" . number_format($simulatedCost, 2) . "\n" .
                "- Days Left in Week: {$this->daysRemaining} days\n" .
                "- New Remaining Total Cash: ₱" . number_format($this->newRemaining, 2) . "\n" .
                "- New Daily Spending Limit: ₱" . number_format($this->newSafeToSpend, 2) . "/day\n" .
                "- Over Budget Deficit?: " . ($this->isDeficit ? 'YES' : 'NO') . "\n\n" .
                "Provide concise budget advice for a university student. " .
                "Explain clearly if buying this item fits their allowance. " .
                "Keep your response under 2 sentences. Be encouraging, clear, and direct. Use '₱' for currency.";

                $response = Http::withToken($apiKey)
                    ->timeout(7)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $settings->groq_text_model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are an encouraging and practical student budgeting assistant.'],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => $settings->groq_temperature,
                        'max_tokens' => $settings->groq_max_tokens
                    ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $rawText = $responseData['choices'][0]['message']['content'] ?? '';
                    if (!empty(trim($rawText))) {
                        $this->aiInsight = trim($rawText);
                        Cache::put($cacheKey, ['text' => $this->aiInsight, 'offline' => false], now()->addMinutes(3));
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('What-If Simulator Exception: ' . $e->getMessage());
        }

        $this->isOfflineMode = true;

        if ($this->isDeficit) {
            $this->aiInsight = "Warning! Purchasing {$item} puts you over budget by ₱" . number_format(abs($this->newRemaining), 2) . ". You will run out of cash before the week ends.";
        } elseif ($this->isCriticalZero) {
            $this->aiInsight = "Heads up! Buying {$item} would use up your entire remaining balance for the week. You'll have ₱0.00 left until your next reset.";
        } elseif ($this->newSafeToSpend == 0) {
            $this->aiInsight = "Buying {$item} uses up your entire spending limit for today, but ₱" . number_format($this->newRemaining, 2) . " stays available for the rest of the week.";
        } else {
            $newDaily = number_format($this->newSafeToSpend, 2);
            $this->aiInsight = "You can comfortably afford {$item}! You will still have ₱{$newDaily}/day left for the rest of the week.";
        }

        Cache::put($cacheKey, ['text' => $this->aiInsight, 'offline' => true], now()->addMinutes(3));
    }

    public function resetSimulation()
    {
        $this->itemName = '';
        $this->purchaseAmount = '';
        $this->scenarioType = '';
        $this->isOfflineMode = false;
        $this->aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';
        $this->refreshCeiling();
        $this->calculateBaselines(true);
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}