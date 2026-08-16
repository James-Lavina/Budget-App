<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

    // Evaluation States
    public $isDeficit = false;
    public $isOfflineMode = false;
    public $aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';

    protected $queryString = [
        'purchaseAmount' => ['except' => ''],
        'scenarioType'   => ['except' => ''],
    ];

    protected $rules = [
        'purchaseAmount' => 'nullable|numeric|min:0',
        'itemName'       => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->initSimulation();
    }

    public function updated($propertyName)
    {
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
        $today = Carbon::today();
        $startDate = Carbon::parse($currentBudget->cycle_start_date)->startOfDay();
        $targetResetDay = $currentBudget->reset_day ?? Auth::user()->default_reset_day ?? 'Monday';

        if (strtolower($startDate->format('l')) === strtolower($targetResetDay)) {
            $nextResetDate = $startDate->copy()->addWeek();
        } else {
            $nextResetDate = $startDate->copy()->next($targetResetDay);
        }

        $daysRemaining = $today->gte($nextResetDate) ? 0 : max(1, (int) $today->diffInDays($nextResetDate));

        return [
            'startDate'     => $startDate,
            'nextResetDate' => $nextResetDate,
            'endDate'       => $nextResetDate->copy()->subSecond(),
            'daysRemaining' => $daysRemaining,
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

        // Regular expenses excluding savings transfers
        $realConsumed = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNull('savings_goal_id')
            ->sum('amount');

        // Total transfers allocated to savings goals
        $totalSavings = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        if ($this->daysRemaining === 0) {
            $this->currentSafeToSpend = 0.00;
            $this->newSafeToSpend = 0.00;
            $this->newRemaining = 0.00;
            $this->dailyImpactDelta = 0.00;

            if ($shouldDispatchChart) {
                $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                    'spent'     => (float)$realConsumed,
                    'savings'   => (float)$totalSavings,
                    'simulated' => 0.00,
                    'remaining' => 0.00,
                    'deficit'   => 0.00,
                ]);
            }
            return;
        }

        $todaySpent = Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', Carbon::today())
            ->whereNull('savings_goal_id')
            ->sum('amount');

        $morningBalance = $currentBudget->remaining_allowance + $todaySpent;
        $todayStartingQuota = $morningBalance / $this->daysRemaining;

        $this->currentSafeToSpend = max(0, $todayStartingQuota - $todaySpent);
        $this->newSafeToSpend = $this->currentSafeToSpend;
        $this->newRemaining = (float) $currentBudget->remaining_allowance;
        $this->dailyImpactDelta = 0.00;
        $this->isDeficit = false;

        if ($shouldDispatchChart) {
            $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                'spent'     => (float)$realConsumed,
                'savings'   => (float)$totalSavings,
                'simulated' => 0.00,
                'remaining' => (float)$currentBudget->remaining_allowance,
                'deficit'   => 0.00,
            ]);
        }
    }

    public function applyPreset($amount, $name = '')
    {
        $this->purchaseAmount = $amount;
        $this->itemName = $name;
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

        if ($simulatedCost <= 0) {
            $this->calculateBaselines(true);
            $this->aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';
            return;
        }

        $realConsumed = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNull('savings_goal_id')
            ->sum('amount');

        $totalSavings = Expense::where('user_id', Auth::id())
            ->whereBetween('transaction_date', [$bounds['startDate'], $bounds['endDate']])
            ->whereNotNull('savings_goal_id')
            ->sum('amount');

        $todaySpent = Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', Carbon::today())
            ->whereNull('savings_goal_id')
            ->sum('amount');

        $this->newRemaining = $currentBudget->remaining_allowance - $simulatedCost;
        $this->isDeficit = ($this->newRemaining < 0);

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

        $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
            'spent'     => (float)$realConsumed,
            'savings'   => (float)$totalSavings,
            'simulated' => (float)$simulatedCost,
            'remaining' => (float)$remainingForChart,
            'deficit'   => (float)$deficitForChart,
        ]);

        $this->generateSimulationInsight($simulatedCost);
    }

    private function generateSimulationInsight($simulatedCost)
    {
        $item = trim($this->itemName) !== '' ? $this->itemName : 'this item';
        $this->isOfflineMode = false;

        try {
            $apiKey = env('GROQ_API_KEY') ?? config('services.groq.key');
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
                        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are an encouraging and practical student budgeting assistant.'],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => 0.5,
                        'max_tokens' => 200
                    ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $rawText = $responseData['choices'][0]['message']['content'] ?? '';

                    if (!empty(trim($rawText))) {
                        $this->aiInsight = trim($rawText);
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
        } elseif ($this->newSafeToSpend == 0) {
            $this->aiInsight = "Buying {$item} uses up your entire spending limit for today, but your remaining week stays covered.";
        } else {
            $newDaily = number_format($this->newSafeToSpend, 2);
            $this->aiInsight = "You can comfortably afford {$item}! You will still have ₱{$newDaily}/day left for the rest of the week.";
        }
    }

    public function resetSimulation()
    {
        $this->itemName = '';
        $this->purchaseAmount = '';
        $this->scenarioType = '';
        $this->isOfflineMode = false;
        $this->aiInsight = 'Type an item name and cost or choose a preset to simulate impact.';

        $this->calculateBaselines(true);
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}