<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\Profile;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(array $attributes = [])
    {
        $user = $this->makeStudent(array_merge([
            'name'                        => 'Maria Clara',
            'school'                      => 'Old School',
            'email_notifications_enabled' => true,
        ], $attributes));
        $this->actingAs($user);

        return $user;
    }

    public function test_the_form_is_prefilled_from_the_account()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->assertSet('name', 'Maria Clara')
            ->assertSet('email', $user->email)
            ->assertSet('school', 'Old School')
            ->assertSet('email_notifications_enabled', true);
    }

    public function test_name_and_school_can_change_without_a_password()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->set('name', 'Maria Santos')
            ->set('school', 'New School')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Maria Santos', $user->name);
        $this->assertSame('New School', $user->school);

        $log = ActivityLog::where('event_type', 'profile_updated')->first();
        $this->assertStringContainsString('"Maria Clara" → "Maria Santos"', $log->details);
        $this->assertSame(0, ActivityLog::where('event_type', 'password_changed')->count());
    }

    public function test_name_is_required()
    {
        $this->signIn();

        Livewire::test(Profile::class)
            ->set('name', '')
            ->call('updateProfile')
            ->assertHasErrors(['name']);
    }

    public function test_changing_the_email_requires_the_current_password()
    {
        $user = $this->signIn();
        $old  = $user->email;

        Livewire::test(Profile::class)
            ->set('email', 'new-address@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['current_password']);

        Livewire::test(Profile::class)
            ->set('email', 'new-address@example.com')
            ->set('current_password', 'not-my-password')
            ->call('updateProfile')
            ->assertHasErrors(['current_password']);

        $this->assertSame($old, $user->fresh()->email);

        Livewire::test(Profile::class)
            ->set('email', 'new-address@example.com')
            ->set('current_password', 'password')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('new-address@example.com', $user->fresh()->email);
    }

    public function test_the_email_must_be_unique_and_valid()
    {
        $this->signIn();
        $other = $this->makeStudent();

        Livewire::test(Profile::class)
            ->set('email', $other->email)
            ->set('current_password', 'password')
            ->call('updateProfile')
            ->assertHasErrors(['email']);

        Livewire::test(Profile::class)
            ->set('email', 'not-an-email')
            ->set('current_password', 'password')
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }

    public function test_keeping_your_own_email_is_not_a_uniqueness_error()
    {
        $this->signIn();

        Livewire::test(Profile::class)
            ->set('school', 'Same Email School')
            ->call('updateProfile')
            ->assertHasNoErrors();
    }

    public function test_password_change_needs_the_current_password_and_a_confirmed_new_one()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->set('new_password', 'brand-new-pass-1')
            ->set('new_password_confirmation', 'brand-new-pass-1')
            ->call('updateProfile')
            ->assertHasErrors(['current_password']);

        Livewire::test(Profile::class)
            ->set('current_password', 'password')
            ->set('new_password', 'brand-new-pass-1')
            ->set('new_password_confirmation', 'something-else')
            ->call('updateProfile')
            ->assertHasErrors(['new_password']);

        Livewire::test(Profile::class)
            ->set('current_password', 'password')
            ->set('new_password', 'short')
            ->set('new_password_confirmation', 'short')
            ->call('updateProfile')
            ->assertHasErrors(['new_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_valid_password_change_is_saved_audited_and_clears_the_fields()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->set('current_password', 'password')
            ->set('new_password', 'brand-new-pass-1')
            ->set('new_password_confirmation', 'brand-new-pass-1')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('current_password', null)
            ->assertSet('new_password', null)
            ->assertSet('new_password_confirmation', null);

        $this->assertTrue(Hash::check('brand-new-pass-1', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'event_type' => 'password_changed']);
    }

    public function test_email_alert_preference_can_be_switched_off_and_is_audited()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->set('email_notifications_enabled', false)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertFalse((bool) $user->fresh()->email_notifications_enabled);
        $log = ActivityLog::where('event_type', 'profile_updated')->first();
        $this->assertStringContainsString('email alerts: On → Off', $log->details);
    }

    public function test_an_unchanged_save_writes_no_audit_entry()
    {
        $this->signIn();

        Livewire::test(Profile::class)->call('updateProfile')->assertHasNoErrors();

        $this->assertSame(0, ActivityLog::where('event_type', 'profile_updated')->count());
    }

    public function test_the_delete_modal_opens_and_closes()
    {
        $this->signIn();

        Livewire::test(Profile::class)
            ->call('openDeleteModal')
            ->assertSet('confirmingAccountDeletion', true)
            ->set('delete_password', 'typed')
            ->call('closeDeleteModal')
            ->assertSet('confirmingAccountDeletion', false)
            ->assertSet('delete_password', null);
    }

    public function test_deleting_the_account_requires_the_password_field()
    {
        $user = $this->signIn();

        Livewire::test(Profile::class)
            ->call('deleteAccount')
            ->assertHasErrors(['delete_password']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_the_account_also_removes_receipt_images_and_notifications()
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = $this->signIn();
        Storage::disk('local')->put('receipts/mine.jpg', 'x');
        \App\Models\Receipt::create(['user_id' => $user->id, 'image_path' => 'receipts/mine.jpg', 'status' => 'processed']);
        $user->notify(new \App\Notifications\LowAllowanceWarning(10, 100.0));
        $this->assertSame(1, $user->notifications()->count());

        Livewire::test(Profile::class)
            ->set('delete_password', 'password')
            ->call('deleteAccount')
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
        Storage::disk('local')->assertMissing('receipts/mine.jpg');
    }
}