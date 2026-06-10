<?php

namespace App\Http\Livewire\Student;

use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $safeToSpend = 0.00;
    public $currentBudget;
    public $daysRemaining = 7;

    public function mount() {
        $this->currentBudget = WeeklyBudget::where('user_id', auth()->id())
        ->latest()
        ->first();

        if(!$this->currentBudget) {
            return redirect()->route('student.budget-setup');
        }

        $this->computeBehavioralMetrics();
    }

    public function computeBehavioralMetrics() {
        $today = Carbon::today();
        $startDate = Carbon::parse($this->currentBudget->cycle_start_date);
        $endDate = $startDate->copy()->addDays(6);

        if($today->greaterThan($endDate)) {
            $this->daysRemaining = 0;
            $this->safeToSpend = 0.00;
            return;
        }

        $this->daysRemaining = $today->diffInDays($endDate) + 1;

        if($this->daysRemaining > 0) {
            $this->safeToSpend = $this->currentBudget->remaining_allowance / $this->daysRemaining;
        } else {
            $this->safeToSpend = 0.00;
        }
    }

    public function render()
    {
        return view('livewire.student.dashboard');
    }
}
