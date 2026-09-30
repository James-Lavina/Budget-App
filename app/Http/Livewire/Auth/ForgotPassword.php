<?php

namespace App\Http\Livewire\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public $email = '';
    public $status = '';

    protected $rules = [
        'email' => 'required|email',
    ];

    public function sendResetLink()
    {
        $this->validate();
        $this->status = '';

        // Looked up before sendResetLink() runs purely so a successful
        // send can be attributed to a real user_id in the activity log —
        // Password::sendResetLink() itself never exposes the User model.
        $matchedUser = User::where('email', $this->email)->first();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            if ($matchedUser) {
                ActivityLog::create([
                    'user_id'    => $matchedUser->id,
                    'event_type' => 'password_reset_requested',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details'    => 'Requested a password reset link',
                ]);
            }

            $this->status = 'A password reset link has been sent to your email.';
            $this->email = '';
        } elseif ($status === Password::INVALID_USER) {
            // Deliberately vague to avoid confirming/denying an email exists —
            // same principle Login::loginUser() already follows.
            $this->addError('email', 'We could not find an account with that email address.');
        } elseif ($status === Password::RESET_THROTTLED) {
            $this->addError('email', 'Please wait a moment before requesting another reset link.');
        } else {
            $this->addError('email', 'Something went wrong. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}