<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BudgetSetup extends Component
{
    public const TOTAL_STEPS = 4;

    public $step = 1;

    // Step 1
    public $total_allowance;
    public $reset_day = 'Monday';

    // Step 3 (optional)
    public $goal_name;
    public $goal_amount;
    public $goal_date;

    protected $messages = [
        'total_allowance.regex' => 'Use at most 2 decimal places (e.g. 2000.00).',
        'goal_amount.regex'     => 'Use at most 2 decimal places (e.g. 500.00).',
    ];

    public function mount()
    {
        // Students who already finished setup never see the wizard again.
        if (WeeklyBudget::where('user_id', auth()->id())->exists()) {
            return redirect()->route('student.dashboard');
        }
    }

    protected function stepRules(int $step): array
    {
        if ($step === 1) {
            return [
                'total_allowance' => ['required', 'numeric', 'min:1', 'max:999999', 'regex:/^\d+(\.\d{1,2})?$/'],
                'reset_day'       => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            ];
        }

        if ($step === 3 && (filled($this->goal_name) || filled($this->goal_amount))) {
            return [
                'goal_name'   => 'required|string|max:255',
                'goal_amount' => ['required', 'numeric', 'min:1', 'max:999999', 'regex:/^\d+(\.\d{1,2})?$/'],
                'goal_date'   => 'nullable|date|after_or_equal:today',
            ];
        }

        return [];
    }

    /**
     * Mirrors BudgetCycleService::resolve(): the first cycle starts today and
     * ends at the next occurrence of the reset day, so it can be shorter than 7 days.
     */
    public function getFirstCycleProperty(): array
    {
        $today = Carbon::today();
        $nextReset = strtolower($today->format('l')) === strtolower($this->reset_day)
            ? $today->copy()->addWeek()
            : $today->copy()->next($this->reset_day);

        $days = max(1, min(7, (int) $today->diffInDays($nextReset)));
        $perDay = is_numeric($this->total_allowance) && $this->total_allowance > 0
            ? round($this->total_allowance / $days, 2)
            : 0.00;

        return ['days' => $days, 'nextReset' => $nextReset, 'perDay' => $perDay];
    }

    public function next()
    {
        $rules = $this->stepRules($this->step);
        if (!empty($rules)) {
            $this->validate($rules);
        }

        if ($this->step < self::TOTAL_STEPS) {
            $this->step++;
        }
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->resetErrorBag();
        }
    }

    public function skipGoal()
    {
        $this->reset(['goal_name', 'goal_amount', 'goal_date']);
        $this->resetErrorBag();
        $this->step = 4;
    }

    public function finish()
    {
        // Double-submit / second-tab guard: setup is one-time only.
        if (WeeklyBudget::where('user_id', auth()->id())->exists()) {
            return redirect()->route('student.dashboard');
        }

        // Re-validate every step server-side; never trust the client's step counter.
        $this->validate($this->stepRules(1));
        $goalRules = $this->stepRules(3);
        if (!empty($goalRules)) {
            $this->validate($goalRules);
        }

        $user      = auth()->user();
        $allowance = round((float) $this->total_allowance, 2);

        DB::transaction(function () use ($user, $allowance) {
            $user->update([
                'default_allowance' => $allowance,
                'default_reset_day' => $this->reset_day,
            ]);

            WeeklyBudget::create([
                'user_id'             => $user->id,
                'total_allowance'     => $allowance,
                'remaining_allowance' => $allowance,
                'reset_day'           => $this->reset_day,
                'cycle_start_date'    => Carbon::today(),
            ]);

            if (filled($this->goal_name) && filled($this->goal_amount)) {
                SavingsGoal::create([
                    'user_id'       => $user->id,
                    'target_name'   => $this->goal_name,
                    'target_amount' => round((float) $this->goal_amount, 2),
                    'current_saved' => 0.00,
                    'target_date'   => $this->goal_date ?: null,
                    'status'        => 'active',
                ]);
            }
        });

        // NEW: the seed event for a student's entire history had no
        // audit trail — lower priority, but this is the record an admin
        // would look for first when reviewing an account from day one.
        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'budget_setup_completed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Completed initial budget setup: ₱" . number_format($allowance, 2) . " weekly allowance, resets {$this->reset_day}"
                . (filled($this->goal_name) && filled($this->goal_amount)
                    ? ", with savings goal \"{$this->goal_name}\" (₱" . number_format((float) $this->goal_amount, 2) . ')'
                    : ''),
        ]);

        session()->flash('success', "You're all set! Your budget is live.");
        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.budget-setup', [
            'appSettings' => AppSetting::current(),
            'categories'  => ExpenseCategory::selectable()->orderBy('name')->get(),
        ]);
    }
}