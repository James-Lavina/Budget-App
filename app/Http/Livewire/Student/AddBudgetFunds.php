<?php

namespace App\Http\Livewire\Student;

use App\Models\WeeklyBudget;
use Livewire\Component;

class AddBudgetFunds extends Component
{
    public $amount;

    // NEW: dynamic ceiling exposed to the view, same pattern as
    // WhatIfSimulator::$purchaseCeiling.
    public $fundsCeiling = 10000.00;

    public function mount()
    {
        $this->refreshCeiling();
    }

    /**
     * NEW: caps a single top-up at 5x the student's default_allowance
     * (falls back to their latest budget's total_allowance, then a flat
     * ₱10,000 if neither exists yet). A weekly allowance top-up should
     * never need to be an order of magnitude larger than the allowance
     * itself — 5x covers legitimate cases (parent sends a semester's
     * worth of pocket money at once) without allowing 999999-style
     * garbage input that breaks chart scaling and ₱ formatting elsewhere.
     */
    private function refreshCeiling()
    {
        $user = auth()->user();
        $currentBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        $baseline = (float) ($user->default_allowance ?? $currentBudget->total_allowance ?? 2000.00);

        $this->fundsCeiling = max($baseline * 5, 10000.00);
    }

    protected function rules()
    {
        return [
            'amount' => 'required|numeric|min:1|max:' . $this->fundsCeiling,
        ];
    }

    protected $messages = [
        'amount.max' => 'That top-up is unusually large for a weekly allowance — max is ₱:max per addition.',
    ];

    public function updatedAmount()
    {
        $this->refreshCeiling();
        $this->validateOnly('amount');
    }

    public function addFunds()
    {
        $this->refreshCeiling();
        $this->validate();

        $budget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$budget) {
            session()->flash('error', 'No active budget cycle found.');
            return redirect()->route('student.dashboard');
        }

        // Add funds directly to remaining allowance
        $budget->increment('remaining_allowance', (float) $this->amount);

        session()->flash('success', 'Successfully added ₱' . number_format($this->amount, 2) . ' to your remaining budget!');

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        return view('livewire.student.add-budget-funds', [
            'currentBudget' => $currentBudget,
        ])->layout('layouts.student');
    }
}