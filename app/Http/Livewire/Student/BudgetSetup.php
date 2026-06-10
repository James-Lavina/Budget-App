<?php

namespace App\Http\Livewire\Student;

use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Livewire\Component;

class BudgetSetup extends Component
{
    public $total_allowance;
    public $reset_day = 'Monday';

    protected $rules = [
        'total_allowance' => 'required|numeric|min:1|max:999999',
        'reset_day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
    ];

    public function initializeEngine() {
        $this->validate();

        WeeklyBudget::create([
            'user_id' => auth()->id(),
            'total_allowance' => $this->total_allowance,
            'remaining_allowance' => $this->total_allowance,
            'reset_day' => $this->reset_day,
            'cycle_start_date' => Carbon::today(),
        ]);

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.budget-setup');
    }
}
