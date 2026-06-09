<?php

namespace App\Http\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public $email;
    public $password;
    public $lockoutSeconds = 0;

    protected $message = [
        'email.required' => 'The email field cannot be blank.',
        'email.email' => 'Please enter a valid email address.',
        'password.required' => 'The password field is required.'
    ];

    public function render()
    {
        return view('livewire.auth.login');
    }

    public function loginUser() {
        $this->validate([
            'email' => 'required|email|string',
            'password' => 'required|string|min:8'
        ]);

        $throttleKey = Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many login attempts. Please try again in {$this->lockoutSeconds} seconds.");
            return;
        }

        if(Auth::attempt(['email' => $this->email, 'password' => $this->password])){
            request()->session()->regenerate();

            RateLimiter::clear($throttleKey);

            if(auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('student.dashboard');
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('auth_failed', 'The provided credentials do not match our records.');
    }
}
