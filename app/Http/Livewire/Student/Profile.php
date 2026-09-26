<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Profile extends Component
{
    public $name;
    public $email;
    public $school;
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->school = $user->school;
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $isChangingEmail = ($this->email !== $user->email);
        $isChangingPassword = !empty($this->new_password);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'school' => 'nullable|string|max:255',
            'current_password' => [
                ($isChangingEmail || $isChangingPassword) ? 'required' : 'nullable',
                'string'
            ],
            'new_password' => 'nullable|min:8|confirmed',
        ], [
            'current_password.required' => 'You must enter your current password to authorize changes to your security profile.',
        ]);

        if ($isChangingEmail || $isChangingPassword) {
            if (!Hash::check($this->current_password, $user->password)) {
                $this->addError('current_password', 'The provided current password does not match your records.');
                return;
            }
        }

        // FIX: capture what actually changed before overwriting, so the
        // activity log records real before/after values.
        $changes = [];
        if ($user->name !== $this->name) {
            $changes[] = "name: \"{$user->name}\" → \"{$this->name}\"";
        }
        if ($isChangingEmail) {
            $changes[] = "email: \"{$user->email}\" → \"{$this->email}\"";
        }
        if ($user->school !== $this->school) {
            $changes[] = "school: \"" . ($user->school ?? '—') . "\" → \"" . ($this->school ?? '—') . "\"";
        }

        if ($isChangingPassword) {
            $user->password = Hash::make($this->new_password);
        }

        $user->name = $this->name;
        $user->email = $this->email;
        $user->school = $this->school;
        $user->save();

        // FIX: profile edits (name/email/school) had no audit trail at all.
        if (!empty($changes)) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'profile_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Updated profile: ' . implode(', ', $changes),
            ]);
        }

        // FIX: a password change is security-sensitive and previously left
        // no trace anywhere. Never logs the password value itself.
        if ($isChangingPassword) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'password_changed',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Account password was changed.',
            ]);
        }

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Your profile details have been successfully secured and updated.');
    }

    public function render()
    {
        return view('livewire.student.profile')->layout('layouts.student');
    }
}