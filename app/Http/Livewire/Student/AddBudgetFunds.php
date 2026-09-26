<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\WeeklyBudget;
use Livewire\Component;

class AddBudgetFunds extends Component
{
    public $amount;

    public $fundsCeiling = 10000.00;

    public function mount()
    {
        $this->refreshCeiling();
    }

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

        $budget->increment('remaining_allowance', (float) $this->amount);

        // NEW: a student manually inflating their own allowance is exactly
        // the kind of event an admin auditing a student's account would
        // need to see. Previously invisible.
        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'budget_funds_added',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => 'Added ₱' . number_format($this->amount, 2) . ' to remaining budget',
        ]);

        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

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