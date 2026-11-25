<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Settings extends Component
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public $reset_day;

    protected function rules()
    {
        return ['reset_day' => ['required', Rule::in(self::DAYS)]];
    }

    public function mount()
    {
        $user = auth()->user();

        $this->reset_day = $user->default_reset_day
            ?? optional($user->latestWeeklyBudget)->reset_day
            ?? 'Monday';
    }

    public function updateSettings()
    {
        $this->validate();

        $user = auth()->user();
        $old  = $user->default_reset_day ?? 'Monday';

        if ($old === $this->reset_day) {
            session()->flash('success', 'Settings saved.');
            return;
        }

        $user->update(['default_reset_day' => $this->reset_day]);

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'budget_settings_updated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Updated budget settings: reset day: {$old} → {$this->reset_day}",
        ]);

        session()->flash('success', "Saved. Your new reset day ({$this->reset_day}) starts at your next weekly reset.");
    }

    public function render()
    {
        return view('livewire.student.settings')->layout('layouts.student');
    }
}