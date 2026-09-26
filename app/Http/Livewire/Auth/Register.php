<?php

namespace App\Http\Livewire\Auth;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $school = '';
    public $password = '';
    public $password_confirmation = '';
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

    public function render()
    {
        $throttleKey = 'register|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
        } else {
            $this->lockoutSeconds = 0;
        }

        $appSettings = AppSetting::current();
        $primary = $appSettings->primary_color ?: '#4f39fa';

        $primaryDark   = $this->bwShade($primary, 0.15, false);
        $primaryDarker = $this->bwShade($primary, 0.28, false);
        $primaryLight  = $this->bwShade($primary, 0.92, true);

        $heroSvgMarkup = null;
        $heroSvgPath = public_path('images/undraw_budgeting_klon.svg');
        if (is_file($heroSvgPath)) {
            $svg = file_get_contents($heroSvgPath);
            $svg = str_replace(['#4F46E5', '#4f46e5'], $primary, $svg);
            $heroSvgMarkup = preg_replace('/<svg([^>]*)>/', '<svg$1 preserveAspectRatio="xMidYMid meet" style="width:100%;height:100%;">', $svg, 1);
        }

        return view('livewire.auth.register', [
            'appSettings' => $appSettings,
            'primary' => $primary,
            'primaryDark' => $primaryDark,
            'primaryDarker' => $primaryDarker,
            'primaryLight' => $primaryLight,
            'heroSvgMarkup' => $heroSvgMarkup,
        ]);
    }

    public function registerUser()
    {
        $throttleKey = 'register|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->lockoutSeconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many registration attempts. Please try again in {$this->lockoutSeconds} seconds.");
            return;
        }

        $this->validate();

        RateLimiter::hit($throttleKey, 60);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'school' => $this->school,
            'password' => Hash::make($this->password),
            'role' => 'student',
        ]);

        // NEW: account creation had no audit trail. Logged against the new
        // user's own id — first entry in their history, mirroring
        // budget_setup_completed as "step 2" of onboarding.
        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'user_registered',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Created account ({$user->email})" . ($user->school ? " — {$user->school}" : ''),
        ]);

        Auth::login($user);

        RateLimiter::clear($throttleKey);

        return redirect()->route('student.dashboard');
    }
}