<?php

namespace App\Http\Livewire\Student;

use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Services\SavingsGoalService;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

class SavingsWidget extends Component
{
    protected $listeners = ['refreshSavings' => '$refresh'];

    public $fundingGoalId;
    public $fund_amount;

    public function openFundingModal($id)
    {
        $this->fundingGoalId = $id;
        $this->fund_amount = null;
        $this->resetErrorBag();
    }

    public function closeFundingModal()
    {
        $this->fundingGoalId = null;
    }

    public function addFunds()
    {
        $goal = SavingsGoal::where('id', $this->fundingGoalId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

        if (!$currentBudget) {
            session()->flash('error', 'No active budget cycle found to draw funds from.');
            return;
        }

        $remainingNeeded = $goal->target_amount - $goal->current_saved;

        $this->validate([
            'fund_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . $currentBudget->remaining_allowance,
                'max:' . $remainingNeeded,
            ]
        ], [
            'fund_amount.max' => 'Transfer halted! The amount exceeds either your remaining budget (₱' . number_format($currentBudget->remaining_allowance, 2) . ') or what is left to finish this goal (₱' . number_format($remainingNeeded, 2) . ').'
        ]);

        $result = app(SavingsGoalService::class)->addFunds(auth()->user(), $goal, (float) $this->fund_amount);

        $this->fundingGoalId = null;
        $this->emit('refreshBudgetMetrics');
        $this->emit('refreshNotifications');

        session()->flash('success', $result['goalWasAchieved']
            ? 'Incredible! Target reached. Milestone shifted to your completed vault!'
            : 'Funds successfully transferred from your budget balance to your savings goal!');
    }

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