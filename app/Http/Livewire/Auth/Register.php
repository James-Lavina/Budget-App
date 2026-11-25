<?php

namespace App\Http\Livewire\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\RegistrationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Register extends Component
{
    public const OTP_EXPIRES_MINUTES      = 10;
    public const OTP_MAX_ATTEMPTS         = 5;
    public const OTP_RESEND_SECONDS       = 60;
    public const OTP_MAX_RESENDS_PER_HOUR = 5;

    private const SESSION_KEY = 'register_otp_email';

    public $step = 1;

    public $name = '';
    public $email = '';
    public $school = '';
    public $password = '';
    public $password_confirmation = '';

    public $otp = '';
    public $otpNotice = '';

    public $lockoutSeconds = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'school' => 'nullable|string|max:255',
        'password' => 'required|string|confirmed|min:8',
    ];

    protected $messages = [
        'name.required' => 'Please provide your full name.',
        'email.required' => 'The email field cannot be blank.',
        'email.unique' => 'This email address is already assigned to a user.',
        'password.required' => 'The password field is required.',
        'password.min' => 'The password must be at least 8 characters.',
        'password.confirmed' => 'The password confirmation does not match.',
    ];

    // Restores step 2 after a page refresh. The pending email comes from the
    // session, never from client-editable component state.
    public function mount()
    {
        $email   = session(self::SESSION_KEY);
        $pending = $email ? RegistrationOtp::where('email', $email)->first() : null;

        if ($pending) {
            $this->step   = 2;
            $this->email  = $pending->email;
            $this->name   = $pending->name;
            $this->school = $pending->school ?? '';
        } else {
            session()->forget(self::SESSION_KEY);
        }
    }

    private function bwShade(string $hex, float $pct, bool $lighten): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $mix = $lighten ? 255 : 0;

        $r = (int) round($r + ($mix - $r) * $pct);
        $g = (int) round($g + ($mix - $g) * $pct);
        $b = (int) round($b + ($mix - $b) * $pct);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    // ------------------------------------------------------------------
    // Step 1: validate the form, store the pending registration, send the OTP
    // ------------------------------------------------------------------
    public function registerUser()
    {
        $throttleKey = 'register|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many registration attempts. Please try again in {$this->lockoutSeconds} seconds.");
            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $this->email = Str::lower(trim((string) $this->email));
        $this->validate();

        $code = $this->generateCode();

        $pending = RegistrationOtp::updateOrCreate(
            ['email' => $this->email],
            [
                'name'         => trim($this->name),
                'school'       => filled($this->school) ? trim($this->school) : null,
                'password'     => Hash::make($this->password),
                'code_hash'    => Hash::make($code),
                'attempts'     => 0,
                'last_sent_at' => now(),
                'expires_at'   => now()->addMinutes(self::OTP_EXPIRES_MINUTES),
            ]
        );

        if (!$this->deliver($pending, $code)) {
            $pending->delete();
            $this->addError('email', 'We could not send the verification email. Check the address and try again.');
            return;
        }

        session([self::SESSION_KEY => $pending->email]);

        $this->reset(['password', 'password_confirmation', 'otp', 'otpNotice']);
        $this->resetErrorBag();
        $this->step = 2;
    }

    // ------------------------------------------------------------------
    // Step 2: verify the code and create the account
    // ------------------------------------------------------------------
    public function verifyOtp()
    {
        $this->otp = preg_replace('/\D/', '', (string) $this->otp);

        $this->validate(
            ['otp' => 'required|digits:6'],
            [
                'otp.required' => 'Enter the 6-digit code we emailed you.',
                'otp.digits'   => 'The code must be 6 digits.',
            ]
        );

        $pending = $this->pendingRecord();

        if (!$pending) {
            return $this->restart('Your verification session expired. Please fill in the form again.');
        }

        if ($pending->expires_at->isPast()) {
            $this->addError('otp', 'That code has expired. Request a new one below.');
            return;
        }

        if ($pending->attempts >= self::OTP_MAX_ATTEMPTS) {
            $this->addError('otp', 'Too many incorrect attempts. Request a new code below.');
            return;
        }

        if (!Hash::check($this->otp, $pending->code_hash)) {
            $pending->increment('attempts');
            $left = max(0, self::OTP_MAX_ATTEMPTS - (int) $pending->attempts);

            $this->addError('otp', $left > 0
                ? "Incorrect code. {$left} " . Str::plural('attempt', $left) . ' left.'
                : 'Too many incorrect attempts. Request a new code below.');
            return;
        }

        // Someone may have registered this email while the code was pending.
        if (User::where('email', $pending->email)->exists()) {
            $pending->delete();
            return $this->restart('That email is already registered. Please log in instead.');
        }

        $user = DB::transaction(function () use ($pending) {
            $user = new User([
                'name'     => $pending->name,
                'email'    => $pending->email,
                'school'   => $pending->school,
                'password' => $pending->password, // already hashed
                'role'     => 'student',
            ]);

            $user->email_verified_at = now();
            $user->save();

            $pending->delete();

            return $user;
        });

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'user_registered',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Created account ({$user->email}, email verified)" . ($user->school ? " — {$user->school}" : ''),
        ]);

        session()->forget(self::SESSION_KEY);

        Auth::login($user);
        session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function resendOtp()
    {
        $pending = $this->pendingRecord();

        if (!$pending) {
            return $this->restart('Your verification session expired. Please fill in the form again.');
        }

        $wait = $this->resendWait($pending);

        if ($wait > 0) {
            $this->addError('otp', "Please wait {$wait}s before requesting another code.");
            return;
        }

        $key = 'register-otp-resend|' . $pending->email;

        if (RateLimiter::tooManyAttempts($key, self::OTP_MAX_RESENDS_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('otp', "Too many code requests. Try again in {$minutes} " . Str::plural('minute', $minutes) . '.');
            return;
        }

        RateLimiter::hit($key, 3600);

        $code = $this->generateCode();

        $pending->update([
            'code_hash'    => Hash::make($code),
            'attempts'     => 0,
            'last_sent_at' => now(),
            'expires_at'   => now()->addMinutes(self::OTP_EXPIRES_MINUTES),
        ]);

        if (!$this->deliver($pending, $code)) {
            $this->addError('otp', 'We could not send the email. Please try again in a moment.');
            return;
        }

        $this->resetErrorBag();
        $this->otp       = '';
        $this->otpNotice = "A new code was sent to {$pending->email}.";
    }

    // "Use a different email": drops the pending row and returns to the form.
    public function backToDetails()
    {
        $email = session(self::SESSION_KEY);

        if ($email) {
            RegistrationOtp::where('email', $email)->delete();
        }

        session()->forget(self::SESSION_KEY);

        $this->reset(['otp', 'otpNotice']);
        $this->resetErrorBag();
        $this->step = 1;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function pendingRecord(): ?RegistrationOtp
    {
        $email = session(self::SESSION_KEY);

        return $email ? RegistrationOtp::where('email', $email)->first() : null;
    }

    private function restart(string $message): void
    {
        session()->forget(self::SESSION_KEY);

        $this->reset(['otp', 'otpNotice']);
        $this->resetErrorBag();
        $this->step = 1;
        $this->addError('email', $message);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function resendWait(RegistrationOtp $pending): int
    {
        if (!$pending->last_sent_at) {
            return 0;
        }

        return max(0, self::OTP_RESEND_SECONDS - $pending->last_sent_at->diffInSeconds(now()));
    }

    private function deliver(RegistrationOtp $pending, string $code): bool
    {
        try {
            Mail::to($pending->email)->send(
                new RegistrationOtpMail($pending->name, $code, self::OTP_EXPIRES_MINUTES)
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Registration OTP email failed', [
                'email'   => $pending->email,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function render()
    {
        $throttleKey = 'register|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
        } else {
            $this->lockoutSeconds = 0;
        }

        $otpEmail   = null;
        $resendWait = 0;

        if ($this->step === 2) {
            $pending    = $this->pendingRecord();
            $otpEmail   = $pending->email ?? session(self::SESSION_KEY);
            $resendWait = $pending ? $this->resendWait($pending) : 0;
        }

        $appSettings = AppSetting::current();
        $primary     = $appSettings->primary_color ?: '#4f39fa';

        $primaryDark   = $this->bwShade($primary, 0.15, false);
        $primaryDarker = $this->bwShade($primary, 0.28, false);
        $primaryLight  = $this->bwShade($primary, 0.92, true);

        $heroSvgMarkup = null;
        $heroSvgPath   = public_path('images/undraw_budgeting_klon.svg');

        if (is_file($heroSvgPath)) {
            $svg = file_get_contents($heroSvgPath);
            $svg = str_replace(['#4F46E5', '#4f46e5'], $primary, $svg);
            $heroSvgMarkup = preg_replace('/<svg([^>]*)>/', '<svg$1 preserveAspectRatio="xMidYMid meet" style="width:100%;height:100%;">', $svg, 1);
        }

        return view('livewire.auth.register', [
            'appSettings'   => $appSettings,
            'primary'       => $primary,
            'primaryDark'   => $primaryDark,
            'primaryDarker' => $primaryDarker,
            'primaryLight'  => $primaryLight,
            'heroSvgMarkup' => $heroSvgMarkup,
            'otpEmail'      => $otpEmail,
            'resendWait'    => $resendWait,
        ]);
    }
}