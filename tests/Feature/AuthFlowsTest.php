<?php

namespace Tests\Feature;

use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Register;
use App\Http\Livewire\Auth\ResetPassword;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class AuthFlowsTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    // ---------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------

    public function test_student_login_redirects_to_the_student_dashboard_and_is_logged()
    {
        $user = $this->makeStudent();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('loginUser')
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'auth_login']);
    }

    public function test_admins_land_on_the_admin_dashboard()
    {
        $admin = $this->makeAdmin('super_admin');

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('loginUser')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_is_rejected_and_logged_without_a_user_id()
    {
        $user = $this->makeStudent();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('loginUser')
            ->assertHasErrors(['auth_failed']);

        $this->assertGuest();
        $log = ActivityLog::where('event_type', 'auth_login_failed')->first();
        $this->assertNull($log->user_id);
        $this->assertStringContainsString('wrong password', $log->details);
    }

    public function test_unknown_email_is_logged_differently_but_shown_the_same_error()
    {
        Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'whatever123')
            ->call('loginUser')
            ->assertHasErrors(['auth_failed']);

        $log = ActivityLog::where('event_type', 'auth_login_failed')->first();
        $this->assertStringContainsString('no matching account', $log->details);
    }

    public function test_suspended_accounts_cannot_log_in()
    {
        $user = $this->makeStudent(['status' => 'suspended']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('loginUser')
            ->assertHasErrors(['auth_failed']);

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'auth_login_failed']);
    }

    public function test_five_failed_attempts_lock_the_account_even_for_the_right_password()
    {
        $user = $this->makeStudent();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('loginUser');
        }

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('loginUser')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_input_validation()
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'short')
            ->call('loginUser')
            ->assertHasErrors(['email', 'password']);
    }

    // ---------------------------------------------------------------
    // Registration
    // ---------------------------------------------------------------

    public function test_registration_creates_a_student_and_logs_them_in()
    {
        Livewire::test(Register::class)
            ->set('name', 'Maria Santos')
            ->set('email', 'maria@example.com')
            ->set('school', 'Saint Joseph College')
            ->set('password', 'secret-pass-1')
            ->set('password_confirmation', 'secret-pass-1')
            ->call('registerUser')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertSame('student', $user->role);
        $this->assertSame('Saint Joseph College', $user->school);
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'user_registered']);
    }

    public function test_school_is_optional()
    {
        Livewire::test(Register::class)
            ->set('name', 'No School')
            ->set('email', 'noschool@example.com')
            ->set('password', 'secret-pass-1')
            ->set('password_confirmation', 'secret-pass-1')
            ->call('registerUser')
            ->assertHasNoErrors();
    }

    public function test_registration_rejects_duplicates_mismatches_and_short_passwords()
    {
        $existing = $this->makeStudent();

        Livewire::test(Register::class)
            ->set('name', 'Dup')
            ->set('email', $existing->email)
            ->set('password', 'secret-pass-1')
            ->set('password_confirmation', 'secret-pass-1')
            ->call('registerUser')
            ->assertHasErrors(['email']);

        Livewire::test(Register::class)
            ->set('name', 'Mismatch')
            ->set('email', 'mismatch@example.com')
            ->set('password', 'secret-pass-1')
            ->set('password_confirmation', 'different-pass')
            ->call('registerUser')
            ->assertHasErrors(['password']);

        Livewire::test(Register::class)
            ->set('name', 'Short')
            ->set('email', 'short@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('registerUser')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }

    /**
     * Requires the limiter fix: call RateLimiter::hit() BEFORE validate() and
     * remove the RateLimiter::clear() after a successful sign-up. As written,
     * the limiter is cleared on every success so it can never trip.
     */
    public function test_registration_is_rate_limited_per_ip()
    {
        foreach ([1, 2, 3] as $i) {
            Livewire::test(Register::class)
                ->set('name', "User {$i}")
                ->set('email', "user{$i}@example.com")
                ->set('password', 'secret-pass-1')
                ->set('password_confirmation', 'secret-pass-1')
                ->call('registerUser')
                ->assertHasNoErrors();
        }

        Livewire::test(Register::class)
            ->set('name', 'User 4')
            ->set('email', 'user4@example.com')
            ->set('password', 'secret-pass-1')
            ->set('password_confirmation', 'secret-pass-1')
            ->call('registerUser')
            ->assertHasErrors(['email']);

        $this->assertDatabaseMissing('users', ['email' => 'user4@example.com']);
    }

    // ---------------------------------------------------------------
    // Forgot / reset password
    // ---------------------------------------------------------------

    public function test_forgot_password_sends_a_link_and_logs_it()
    {
        Notification::fake();
        $user = $this->makeStudent();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('status', 'If that email is registered, a password reset link has been sent.')
            ->assertSet('email', '');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'password_reset_requested']);
    }

    public function test_forgot_password_does_not_reveal_whether_an_email_exists()
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('status', 'If that email is registered, a password reset link has been sent.');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_is_throttled()
    {
        Notification::fake();
        $user = $this->makeStudent();

        $component = Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink');

        $component->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    }

    public function test_forgot_password_requires_a_valid_email()
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'not-an-email')
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    }

    public function test_a_valid_token_resets_the_password()
    {
        $user  = $this->makeStudent();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'brand-new-pass-1')
            ->set('password_confirmation', 'brand-new-pass-1')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-1', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'password_reset_completed']);
    }

    /** Requires fix #1 (remember_token rotation in ResetPassword). */
    public function test_resetting_the_password_rotates_the_remember_token()
    {
        $user  = $this->makeStudent();
        $old   = $user->remember_token;
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'brand-new-pass-1')
            ->set('password_confirmation', 'brand-new-pass-1')
            ->call('resetPassword');

        $this->assertNotSame($old, $user->fresh()->remember_token);
    }

    public function test_an_invalid_token_is_rejected()
    {
        $user = $this->makeStudent();

        Livewire::test(ResetPassword::class, ['token' => 'not-a-real-token'])
            ->set('email', $user->email)
            ->set('password', 'brand-new-pass-1')
            ->set('password_confirmation', 'brand-new-pass-1')
            ->call('resetPassword')
            ->assertHasErrors(['email']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_requires_a_confirmed_password_of_at_least_eight_characters()
    {
        $user  = $this->makeStudent();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('resetPassword')
            ->assertHasErrors(['password']);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'long-enough-1')
            ->set('password_confirmation', 'does-not-match')
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    }

    // ---------------------------------------------------------------
    // Route guards and logout
    // ---------------------------------------------------------------

    public function test_guests_are_sent_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_logged_in_users_cannot_open_the_guest_pages()
    {
        $this->actingAs($this->makeStudent())->get('/login')->assertRedirect();
    }

    public function test_logout_ends_the_session_and_is_logged()
    {
        $user = $this->makeStudent();

        $this->actingAs($user)->post('/');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'auth_logout']);
    }
}