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

        $matchedUser = User::where('email', $this->email)->first();

        $status = Password::sendResetLink(['email' => $this->email]);

        $genericMessage = 'If that email is registered, a password reset link has been sent.';

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

            $this->status = $genericMessage;
            $this->email  = '';
        } elseif ($status === Password::INVALID_USER) {
            // Same response as success so registered emails can't be enumerated.
            $this->status = $genericMessage;
            $this->email  = '';
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