<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Livewire\Component;

class NewCycleSetup extends Component
{
    public $total_allowance;

    protected $rules = [
        'total_allowance' => ['required', 'numeric', 'min:1', 'max:999999', 'regex:/^\d+(\.\d{1,2})?$/'],
    ];

    protected $messages = [
        'total_allowance.regex' => 'Use at most 2 decimal places (e.g. 2000.00).',
    ];

    public function mount()
    {
        $user   = auth()->user();
        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$budget) {
            return redirect()->route('student.budget-setup');
        }

        if (!app(BudgetCycleService::class)->needsRollover($budget, $user)) {
            return redirect()->route('student.dashboard');
        }

        // Pre-fill with last week's base allowance; the student can change it.
        $this->total_allowance = number_format((float) $budget->total_allowance, 2, '.', '');
    }

    public function getSummaryProperty(): array
    {
        $user   = auth()->user();
        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        $summary = app(BudgetCycleService::class)->endingCycleSummary($budget, $user);

        $summary['lastBase'] = (float) $budget->total_allowance;
        $summary['daysLeft'] = max(1, (int) Carbon::today()->diffInDays($summary['newNextReset']));

        return $summary;
    }

    public function confirm()
    {
        $this->validate();

        $user   = auth()->user();
        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$budget) {
            return redirect()->route('student.budget-setup');
        }

        $allowance = round((float) $this->total_allowance, 2);
        $result    = app(BudgetCycleService::class)->rollover($budget, $user, $allowance);

        // Already rolled over (second tab / double submit).
        if (!$result) {
            return redirect()->route('student.dashboard');
        }

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'weekly_cycle_started',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => 'Started new week: ₱' . number_format($result['newAllowance'], 2)
                . ' allowance + ₱' . number_format($result['unspent'], 2)
                . ' rolled over = ₱' . number_format($result['newTotal'], 2),
        ]);

        session()->flash('success', 'New week started! ₱' . number_format($result['unspent'], 2)
            . ' was added to your ₱' . number_format($result['newAllowance'], 2) . ' allowance.');

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.new-cycle-setup', [
            'appSettings' => AppSetting::current(),
        ]);
    }
}