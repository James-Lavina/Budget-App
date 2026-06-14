<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WhatIfSimulator extends Component
{
    // --- Core Properties ---
    public $itemName = '';
    public $purchaseAmount = ''; 
    
    // --- Budget Tracker Metrics ---
    public $daysRemaining = 7;
    public $currentRemaining = 0;
    public $totalAllowance = 0;
    public $alreadySpent = 0;
    
    // --- Calculation Variance Outputs ---
    public $currentSafeToSpend = 0;
    public $newSafeToSpend = 0;
    public $newRemaining = 0;
    public $isDeficit = false;

    // --- State & Engine Controllers ---
    public $aiInsight = '';
    public $loadingAi = false;
    public $isOfflineMode = false; 

    /**
     * Catches incoming data if redirecting from the dashboard quick test widget
     */
    public function mount()
    {
        if (request()->has('purchaseAmount')) {
            $this->purchaseAmount = request()->query('purchaseAmount');
        }
        
        if (request()->query('scenarioType') === 'major_purchase') {
            $this->itemName = 'Quick Test Purchase';
        }
    }

    /**
     * Helper method to sync fresh allocation data directly from your database
     */
    private function loadCurrentBudget()
    {
        $userId = auth()->id();
        $currentBudget = WeeklyBudget::where('user_id', $userId)->latest()->first();

        if ($currentBudget) {
            // FIX: Successfully mapping your live database values back to the application state
            $this->totalAllowance = floatval($currentBudget->total_allowance);
            $this->currentRemaining = floatval($currentBudget->remaining_allowance);
            $this->alreadySpent = $this->totalAllowance - $this->currentRemaining;

            // Calculate true days remaining until your weekly reset day cycle
            $startDate = Carbon::parse($currentBudget->cycle_start_date);
            $daysElapsed = max(0, $startDate->diffInDays(Carbon::now()));
            $this->daysRemaining = max(1, 7 - $daysElapsed);
        } else {
            $this->totalAllowance = 0;
            $this->currentRemaining = 0;
            $this->alreadySpent = 0;
            $this->daysRemaining = 7;
        }
    }

    /**
     * Livewire Lifecycle Hook: Runs automatically when the page shell finishes rendering
     */
    public function initSimulation()
    {
        // First, load your real wallet numbers so the baseline values display instantly
        $this->loadCurrentBudget();

        // FIX: Only auto-simulate if an amount was actually passed from the widget!
        if (!empty($this->purchaseAmount) && floatval($this->purchaseAmount) > 0) {
            $this->runSimulation();
        } else {
            // Otherwise, keep the fields clean and just render your default starting values
            $this->currentSafeToSpend = $this->currentRemaining / max(1, $this->daysRemaining);
            $this->newSafeToSpend = $this->currentSafeToSpend;
            $this->newRemaining = $this->currentRemaining;
            $this->isDeficit = false;
            $this->aiInsight = "Enter a planned item title and custom price parameters to check how it affects your remaining allowance for this week.";

            // Render a clean starting bar chart layout
            $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
                'spent' => $this->alreadySpent,
                'simulated' => 0,
                'remaining' => $this->currentRemaining
            ]);
        }
    }

    /**
     * Primary Mathematical Processor: Evaluates form choices when user clicks 'Test Impact'
     */
    public function runSimulation()
    {
        // Grab fresh database records to prevent stale calculations
        $this->loadCurrentBudget();

        $amount = floatval($this->purchaseAmount ?: 0);
        $days = max(1, intval($this->daysRemaining));

        // Calculate baseline daily allowance limit
        $this->currentSafeToSpend = $this->currentRemaining / $days;

        // Map post-purchase balances
        $this->newRemaining = $this->currentRemaining - $amount;
        $this->isDeficit = $this->newRemaining < 0;

        if ($this->isDeficit) {
            $this->newSafeToSpend = 0;
        } else {
            $this->newSafeToSpend = $this->newRemaining / $days;
        }

        // Dispatch variables to your frontend Chart.js instance canvas layer
        $this->dispatchBrowserEvent('renderWeeklyImpactChart', [
            'spent' => $this->alreadySpent,
            'simulated' => $amount,
            'remaining' => max(0, $this->newRemaining)
        ]);

        // Only fire prediction feedback text logs if they are actually simulating an item cost
        if ($amount > 0) {
            $this->generateWeeklyAIAnalysis();
        } else {
            $this->aiInsight = "Enter a planned item title and custom price parameters to check how it affects your remaining allowance for this week.";
        }
    }

    /**
     * Evaluation Engine: Chooses between Cloud AI or localized rule heuristics
     */
    private function generateWeeklyAIAnalysis()
    {
        $this->loadingAi = true;
        $this->aiInsight = '';
        $this->isOfflineMode = false; 

        $amount = floatval($this->purchaseAmount ?: 0);
        $currentPool = max(1, $this->currentRemaining);
        $percentageOfRemaining = ($amount / $currentPool) * 100;
        
        $item = !empty($this->itemName) ? trim($this->itemName) : 'this planned purchase';

        if ($this->isDeficit) {
            $deficitAmount = number_format(abs($this->newRemaining), 2);
            $financialFact = "This purchase is dangerous because it causes a budget overdraft of ₱{$deficitAmount}. It wipes out their entire remaining allowance, dropping their daily safe-to-spend cash from ₱" . number_format($this->currentSafeToSpend, 2) . " down to ₱0.00.";
        } else {
            $dropAmount = number_format(($this->currentSafeToSpend - $this->newSafeToSpend), 2);
            $financialFact = "This purchase is mathematically safe, but it reduces their remaining allowance. It drops their daily safe-to-spend limit from ₱" . number_format($this->currentSafeToSpend, 2) . " down to ₱" . number_format($this->newSafeToSpend, 2) . " (a reduction of ₱{$dropAmount} per day).";
        }

        try {
            if (!env('GROQ_API_KEY')) {
                throw new \Exception('Missing API Key Configuration');
            }

            $response = Http::timeout(6)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type' => 'application/json'
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a friendly, savvy upperclassman student who is great with money. Speak in a casual, supportive, peer-to-peer tone using simple language. Do not use complex financial words like "velocity", "parameters", "metrics", "expenditure", or "deficit". Give exactly 2 sentences of practical student advice based on the financial facts provided. Do not use markdown bolding.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "The student wants to buy: '{$item}' which costs ₱" . number_format($amount, 2) . ". Days left in their weekly budget cycle: {$this->daysRemaining} days. Here are the exact financial facts of this choice: {$financialFact}"
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $this->aiInsight = $response->json()['choices'][0]['message']['content'];
                $this->isOfflineMode = false;
            } else {
                throw new \Exception('API Error');
            }

        } catch (\Exception $e) {
            // --- OFFLINE FAIL-SAFE ENGINE ---
            $this->isOfflineMode = true; 
            
            if ($this->isDeficit) {
                $deficitAmt = number_format(abs($this->newRemaining), 2);
                $this->aiInsight = "Buying {$item} right now is going to wipe out your wallet completely and leave you short by ₱{$deficitAmt}. It is best to pass on this one until your budget resets next week.";
                
            } elseif ($percentageOfRemaining >= 100 || $this->newRemaining == 0) {
                $this->aiInsight = "Buying {$item} right now will completely clean out every single peso you have left for the week. Your daily spending limit drops straight to ₱0.00, leaving you with nothing until the next reset.";
                
            } elseif ($percentageOfRemaining > 50) {
                $dropPercent = number_format(($this->currentSafeToSpend - $this->newSafeToSpend), 2);
                $this->aiInsight = "Grabbing {$item} eats up more than half of what you have left for the week. It drops your daily spending cash by ₱{$dropPercent}, so make sure it is worth pinching pennies.";
                
            } else {
                $newDaily = number_format($this->newSafeToSpend, 2);
                $this->aiInsight = "You can totally handle getting {$item} without breaking your flow. You will still have a comfortable ₱{$newDaily} left to play with every day until the week ends.";
            }
        }

        $this->loadingAi = false;
    }

    /**
     * Form control shortcut to reset values back to pristine baselines
     */
    public function resetSimulation()
    {
        $this->itemName = '';
        $this->purchaseAmount = '';
        $this->runSimulation();
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}