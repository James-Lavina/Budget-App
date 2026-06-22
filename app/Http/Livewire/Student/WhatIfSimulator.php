<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Livewire\Component;

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
    public $loadingAi = false;
    public $aiInsight = 'Enter an item name and cost to simulate its impact on your allowance cycle.';

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

        // Calculate baselines. Prevent baseline chart event if running simulation immediately
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
            $this->aiInsight = "Please set up an active weekly budget before using the predictive simulator.";
            return;
        }

        $today = Carbon::today();
        $cycleStartDate = Carbon::parse($currentBudget->cycle_start_date)->startOfDay();
        
        // 🌟 SYNCHRONIZED FIX: Calculate the dynamic tracking boundary using chosen day-name configs
        $cycleEndDate = $cycleStartDate->copy()->next($currentBudget->reset_day)->subDay()->endOfDay();

        // Check if calendar dates have advanced past active monitoring states
        if ($today->greaterThan($cycleEndDate)) {
            $this->daysRemaining = 0;
            $this->currentSafeToSpend = 0.00;
            $this->newSafeToSpend = 0.00;
            return;
        }

        // 🌟 SYNCHRONIZED FIX: Mirror the precise mathematical day count steps used on Dashboard
        $this->daysRemaining = $today->diffInDays($cycleEndDate->copy()->startOfDay()) + 1;

        $realConsumed = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

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

    /**
     * Executes the predictive mathematical analysis simulations
     */
    public function runSimulation()
    {
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            return;
        }

        $this->loadingAi = true;

        $today = Carbon::today();
        $cycleStartDate = Carbon::parse($currentBudget->cycle_start_date)->startOfDay();
        
        // 🌟 SYNCHRONIZED FIX: Ensure identical query bounds mapping rules apply to predictions
        $cycleEndDate = $cycleStartDate->copy()->next($currentBudget->reset_day)->subDay()->endOfDay();
        
        $realConsumed = Expense::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$cycleStartDate, $cycleEndDate])
            ->sum('amount');

        $simulatedCost = is_numeric($this->purchaseAmount) ? (float)$this->purchaseAmount : 0;

        if ($simulatedCost <= 0) {
            $this->resetSimulation();
            return;
        }

        // Calculate hypothetical remaining pool balance
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

        // Update frontend Chart.js smoothly
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

        if ($this->isDeficit) {
            $this->aiInsight = "Danger zone! Purchasing {$item} causes an immediate weekly deficit. You will run out of funds entirely before the cycle resets.";
        } elseif ($this->newSafeToSpend == 0) {
            $this->aiInsight = "Grabbing {$item} will completely exhaust your daily allowance for today. While your upcoming days are safe, your current spending target drops to ₱0.00.";
        } else {
            $newDaily = number_format($this->newSafeToSpend, 2);
            $this->aiInsight = "You can totally handle getting {$item} without breaking your flow. You will still have a comfortable ₱{$newDaily} left to spend every day until the week ends.";
        }

        $this->loadingAi = false;
    }

    public function resetSimulation()
    {
        $this->itemName = '';
        $this->purchaseAmount = '';
        $this->scenarioType = ''; 
        $this->aiInsight = 'Enter an item name and cost to simulate its impact on your allowance cycle.';
        
        $this->calculateBaselines(true);
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}