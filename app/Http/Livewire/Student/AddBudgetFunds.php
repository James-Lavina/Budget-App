<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\DB;
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
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $this->fundsCeiling, 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }

    protected $messages = [
        'amount.max'   => 'That top-up is unusually large for a weekly allowance — max is ₱:max per addition.',
        'amount.regex' => 'Use at most 2 decimal places.',
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

        $amount = round((float) $this->amount, 2);

        DB::transaction(function () use ($budget, $amount) {
            $locked = WeeklyBudget::whereKey($budget->id)->lockForUpdate()->first();
            $locked->remaining_allowance += $amount;
            $locked->added_funds         += $amount;
            $locked->save();
        });

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