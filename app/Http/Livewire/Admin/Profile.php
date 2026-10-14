<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    use EnsuresAdmin;

    public $name;
    public $email;

    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        $user = auth()->user();

        $this->name  = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile()
    {
        $user = auth()->user();

        $isChangingEmail    = $this->email !== $user->email;
        $isChangingPassword = !empty($this->new_password);

        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => [($isChangingEmail || $isChangingPassword) ? 'required' : 'nullable', 'string'],
            'new_password'     => 'nullable|min:8|confirmed',
        ], [
            'current_password.required' => 'Enter your current password to change your email or password.',
        ]);

        if (($isChangingEmail || $isChangingPassword) && !Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The provided current password does not match your records.');
            return;
        }

        $changes = [];

        if ($user->name !== $this->name) {
            $changes[] = "name: \"{$user->name}\" → \"{$this->name}\"";
        }

        if ($isChangingEmail) {
            $changes[] = "email: \"{$user->email}\" → \"{$this->email}\"";
        }

        if ($isChangingPassword) {
            $user->password = Hash::make($this->new_password);
        }

        $user->name  = $this->name;
        $user->email = $this->email;
        $user->save();

        if ($isChangingPassword) {
            Auth::logoutOtherDevices($this->new_password);
        }

        if (!empty($changes)) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'profile_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Updated admin profile: ' . implode(', ', $changes),
            ]);
        }

        if ($isChangingPassword) {
            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'password_changed',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Admin password was changed. Other sessions were signed out.',
            ]);
        }

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Your profile was updated.');
    }

    public function render()
    {
        return view('livewire.admin.profile')->layout('layouts.admin');
    }
}