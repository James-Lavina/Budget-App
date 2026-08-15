<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatIfSimulator extends Component
{
    // Form Inputs
    public $itemName = '';
    public $purchaseAmount = '';
    public $scenarioType = '';
    
    // Budget Calculations
    public $currentSafeToSpend = 0;
    public $newSafeToSpend = 0;
    public $daysRemaining = 1;
    public $newRemaining = 0;
    
    // Evaluation States
    public $isDeficit = false;
    public $isOfflineMode = false;
    public $aiInsight = 'Enter an item name and cost to see how it affects your weekly budget.'; // Updated initial copy

    protected $queryString = [
        'purchaseAmount' => ['except' => ''],
        'scenarioType' => ['except' => ''],
    ];

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

    private function calculateBaselines($shouldDispatchChart = true)
    {
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            $this->aiInsight = "Please set up an active weekly budget before testing purchase impacts."; // Updated friendly error
            return;
        }

        $today = Carbon::today();
        $cycleStartDate = Carbon::parse($currentBudget->cycle_start_date)->startOfDay();
        $cycleEndDate = $cycleStartDate->copy()->addDays(6)->endOfDay();

        $realConsumed = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

        if ($today->greaterThan($cycleEndDate)) {
            $this->daysRemaining = 0;
            $this->currentSafeToSpend = 0.00;
            $this->newSafeToSpend = 0.00;
            $this->newRemaining = 0.00;

            if ($shouldDispatchChart) {
                $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                    'spent' => (float)$realConsumed,
                    'simulated' => 0.00,
                    'remaining' => 0.00
                ]);
            }
            return;
        }

        $this->daysRemaining = (int)$today->diffInDays($cycleEndDate->copy()->startOfDay()) + 1;
        $todaySpent = Expense::where('user_id', auth()->id())
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount');

        if ($this->daysRemaining > 0) {
            $morningBalance = $currentBudget->remaining_allowance + $todaySpent;
            $todayStartingQuota = $morningBalance / $this->daysRemaining;
            $this->currentSafeToSpend = max(0, $todayStartingQuota - $todaySpent);
        } else {
            $this->currentSafeToSpend = 0.00;
        }

        $this->newSafeToSpend = $this->currentSafeToSpend;
        $this->newRemaining = $currentBudget->remaining_allowance;
        $this->isDeficit = false;

        if ($shouldDispatchChart) {
            $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                'spent' => (float)$realConsumed,
                'simulated' => 0.00,
                'remaining' => (float)$currentBudget->remaining_allowance
            ]);
        }
    }

    public function runSimulation()
    {
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            return;
        }

        $today = Carbon::today();
        $cycleStartDate = Carbon::parse($currentBudget->cycle_start_date)->startOfDay();
        $cycleEndDate = $cycleStartDate->copy()->addDays(6)->endOfDay();

        $realConsumed = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

        $simulatedCost = is_numeric($this->purchaseAmount) ? (float)$this->purchaseAmount : 0;

        if ($simulatedCost <= 0) {
            $this->resetSimulation();
            return;
        }

        if ($today->greaterThan($cycleEndDate)) {
            $this->daysRemaining = 0;
        } else {
            $this->daysRemaining = (int)$today->diffInDays($cycleEndDate->copy()->startOfDay()) + 1;
        }

        $this->newRemaining = $currentBudget->remaining_allowance - $simulatedCost;
        $this->isDeficit = ($this->newRemaining < 0);

        if ($this->isDeficit) {
            $this->newSafeToSpend = 0;
        } else {
            $todaySpent = Expense::where('user_id', auth()->id())
                ->whereDate('transaction_date', Carbon::today())
                ->sum('amount');

            if ($this->daysRemaining > 0) {
                $hypotheticalMorningBalance = $this->newRemaining + $todaySpent;
                $hypotheticalStartingQuota = $hypotheticalMorningBalance / $this->daysRemaining;
                $this->newSafeToSpend = max(0, $hypotheticalStartingQuota - $todaySpent);
            } else {
                $this->newSafeToSpend = 0.00;
            }
        }

        $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
            'spent' => (float)$realConsumed,
            'simulated' => (float)$simulatedCost,
            'remaining' => (float)max(0, $this->newRemaining)
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
                        'model' => env('GROQ_MODEL'),
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an encouraging and practical student budgeting assistant.' // Simplified persona
                            ],
                            ['role' => 'user', 'content' => $prompt]
                        ],
                        'temperature' => 0.5,
                        'max_tokens' => 600
                    ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $rawText = $responseData['choices'][0]['message']['content'] ?? '';
                    
                    if (empty(trim($rawText)) && isset($responseData['choices'][0]['message']['reasoning'])) {
                        $rawText = $responseData['choices'][0]['message']['reasoning'];
                    }

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

        // Updated offline fallback copy
        if ($this->isDeficit) {
            $this->aiInsight = "Warning! Purchasing {$item} puts you over budget by ₱" . number_format(abs($this->newRemaining), 2) . ". You will run out of cash before the week ends.";
        } elseif ($this->newSafeToSpend == 0) {
            $this->aiInsight = "Buying {$item} uses up your entire spending limit for today. Your target for today drops to ₱0.00, but the rest of your week is still covered.";
        } else {
            $newDaily = number_format($this->newSafeToSpend, 2);
            $this->aiInsight = "You can comfortably afford {$item}! You will still have ₱{$newDaily} per day left for the rest of the week.";
        }
    }

    public function resetSimulation()
    {
        $this->itemName = '';
        $this->purchaseAmount = '';
        $this->scenarioType = '';
        $this->isOfflineMode = false;
        $this->aiInsight = 'Enter an item name and cost to see how it affects your weekly budget.';
        
        $this->calculateBaselines(true);
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}