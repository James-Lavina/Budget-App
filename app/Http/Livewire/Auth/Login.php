<?php

namespace App\Http\Livewire\Auth;

use App\Models\ActivityLog;
use App\Models\User;
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

            // NEW: activity_logs.user_id is nullable specifically to support
            // logging failed attempts against no user — labelFor() in
            // ActivityLogIndex already had 'auth_login'/'auth_login_failed'
            // cases mapped, but nothing ever wrote either event. This was a
            // dead audit trail, not an existing feature working elsewhere.
            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'auth_login',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Logged in successfully',
            ]);

            if(in_array(auth()->user()->role, ['admin', 'super_admin'])) {
                return redirect()->route('admin.dashboard');
            }
            
            return redirect()->route('student.dashboard');
        }

        RateLimiter::hit($throttleKey, 60);

        // NEW: logged against no user (matching_user lookup below only for
        // the details string, never for auth) since the credentials didn't
        // match — user_id stays null, exactly what the migration's comment
        // anticipated ("Nullable for tracking failed login attempts").
        // Never logs the submitted password.
        $matchedUser = User::where('email', $this->email)->first();

        ActivityLog::create([
            'user_id'    => null,
            'event_type' => 'auth_login_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => $matchedUser
                ? "Failed login attempt for {$this->email} (wrong password)"
                : "Failed login attempt for {$this->email} (no matching account)",
        ]);

        $this->addError('auth_failed', 'The provided credentials do not match our records.');
    }
}