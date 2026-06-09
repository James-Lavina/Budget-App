<?php

namespace App\Http\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Register extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $lockoutSeconds = 0;

    protected $messages = [
        'name.required' => 'Please provide your full name.',
        'email.required' => 'The email field cannot be blank.',
        'email.unique' => 'This email address is already assigned to a user.',
        'password.required' => 'The password field is required',
        'password.min' => 'The password must be at least 8 characters.',
        'password.confirmed' => 'The password confirmation does not match.',
    ];

    public function render()
    {
        return view('livewire.auth.register');
    }

    public function registerUser() {
        $throttleKey = 'register|' . request()->ip();
    
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
    
            $this->addError('email', "Too many registration attempts from this network. Please try again in {$this->lockoutSeconds} seconds.");
            return;
        }
    
        RateLimiter::hit($throttleKey, 60);
    
        $this->validate([
            'name' => 'required|string|max:255',
            'email' =>  'required|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8'
        ]);
    
        $user = User::create([
            'name' => $this->name,
            'email'  => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'student',
        ]);
    
        Auth::login($user);
    
        RateLimiter::clear($throttleKey);
        
        return redirect()->route('student.dashboard');
    }

}