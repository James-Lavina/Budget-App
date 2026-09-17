<?php

namespace App\Http\Livewire\Student;

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

        if ($isChangingPassword) {
            $user->password = Hash::make($this->new_password);
        }

        $user->name = $this->name;
        $user->email = $this->email;
        $user->school = $this->school;
        $user->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Your profile details have been successfully secured and updated.');
    }

    public function render()
    {
        return view('livewire.student.profile')->layout('layouts.student');
    }
}