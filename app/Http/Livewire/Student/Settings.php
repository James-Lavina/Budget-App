<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use App\Models\WeeklyBudget;

class Settings extends Component
{
    public $total_allowance;
    public $reset_day;
    public $update_current_week = false;

    protected $rules = [
        'total_allowance' => 'required|numeric|min:1|max:999999',
        'reset_day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
    ];

    public function mount()
    {
        $user = auth()->user();
        $currentBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();
        
        $this->total_allowance = $user->default_allowance ?? ($currentBudget->total_allowance ?? 1000.00);
        $this->reset_day = $user->default_reset_day ?? ($currentBudget->reset_day ?? 'Monday');
    }

    public function updateSettings()
    {
        $this->validate();

        $user = auth()->user();
        $currentBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        $user->update([
            'default_allowance' => (float) $this->total_allowance,
            'default_reset_day' => $this->reset_day,
        ]);

        if ($this->update_current_week && $currentBudget) {
            $oldTotalBaseline = (float) $currentBudget->total_allowance;
            $newTotalBaseline = (float) $this->total_allowance;
            $currentRemaining = (float) $currentBudget->remaining_allowance;

            $difference = $newTotalBaseline - $oldTotalBaseline;
            $finalRemaining = max(0.00, $currentRemaining + $difference);

            $currentBudget->update([
                'total_allowance'     => $newTotalBaseline,
                'remaining_allowance' => $finalRemaining,
                'reset_day'           => $this->reset_day,
            ]);

            $this->update_current_week = false;
            $this->emit('refreshBudgetMetrics');
        }

        session()->flash('success', 'Budget configurations and future cycle templates saved successfully.');
    }

    public function render()
    {
        return view('livewire.student.settings')->layout('layouts.student');
    }
}