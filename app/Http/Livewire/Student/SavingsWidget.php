<?php

namespace App\Http\Livewire\Student;

use App\Models\SavingsGoal;
use Livewire\Component;

class SavingsWidget extends Component
{
    protected $listeners = ['refreshSavings' => '$refresh'];

    public function render()
    {
        $topGoals = SavingsGoal::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('current_saved', 'desc')
            ->take(3)
            ->get();

        return view('livewire.student.savings-widget', [
            'topGoals' => $topGoals
        ]);
    }
}