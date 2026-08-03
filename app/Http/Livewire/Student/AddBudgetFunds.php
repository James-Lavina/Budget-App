<?php

namespace App\Http\Livewire\Student;

use App\Models\WeeklyBudget;
use Livewire\Component;

class AddBudgetFunds extends Component
{
    public $amount;

    protected $rules = [
        'amount' => 'required|numeric|min:1',
    ];

    public function addFunds()
    {
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