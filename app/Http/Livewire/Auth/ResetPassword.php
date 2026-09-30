<?php

namespace App\Http\Livewire\Auth;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ResetPassword extends Component
{
    public $token;
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8|confirmed',
    ];

    protected $messages = [
        'password.confirmed' => 'The password confirmation does not match.',
    ];

    // The default ResetPassword notification builds its link as
    // route('password.reset', ['token' => ..., 'email' => ...], false),
    // so the email is already on the query string when this loads.
    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword()
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));

                ActivityLog::create([
                    'user_id'    => $user->id,
                    'event_type' => 'password_reset_completed',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details'    => 'Password reset via emailed link',
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', 'Your password has been reset. Please log in.');
            return redirect()->route('login');
        }

        $this->addError('email', 'This password reset link is invalid or has expired.');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}