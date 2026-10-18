<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\AdminManagement;
use App\Http\Livewire\Admin\ExpenseCategories;
use App\Http\Livewire\Admin\OcrAiSettings;
use App\Http\Livewire\Admin\Profile as AdminProfile;
use App\Http\Livewire\Admin\Reports;
use App\Http\Livewire\Admin\UserManagement;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\ExpenseCategory;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student']);
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_students_cannot_open_admin_pages()
    {
        $this->actingAs($this->student())
            ->get('/admin/dashboard')
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_admins_can_open_the_admin_dashboard()
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_regular_admins_cannot_open_admin_accounts()
    {
        $this->actingAs($this->admin('admin'))
            ->get('/admin/admin-accounts')
            ->assertForbidden();

        $this->actingAs($this->admin('super_admin'))
            ->get('/admin/admin-accounts')
            ->assertOk();
    }

    public function test_livewire_actions_reject_non_admins()
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->student());

        $this->expectException(HttpException::class);

        Livewire::test(Reports::class)->call('exportCsv');
    }

    public function test_maintenance_mode_blocks_students_with_503()
    {
        $settings = AppSetting::current();
        $settings->update(['maintenance_mode_enabled' => true]);
        Cache::forever('app_settings', $settings->fresh());

        $this->actingAs($this->student())
            ->get('/dashboard')
            ->assertStatus(503);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk();
    }

    // ---------------------------------------------------------------
    // OCR & AI settings
    // ---------------------------------------------------------------

    public function test_api_key_is_never_loaded_into_the_component()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_super_secret_key']);
        IntegrationSetting::flush();

        $this->actingAs($this->admin());

        Livewire::test(OcrAiSettings::class)
            ->assertSet('groq_api_key', null)
            ->assertDontSee('gsk_super_secret_key');
    }

    public function test_saving_with_a_blank_key_keeps_the_stored_key()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_super_secret_key']);
        IntegrationSetting::flush();

        $this->actingAs($this->admin());

        Livewire::test(OcrAiSettings::class)
            ->set('groq_api_key', '')
            ->call('save')
            ->assertHasNoErrors();

        IntegrationSetting::flush();

        $this->assertSame('gsk_super_secret_key', IntegrationSetting::first()->groq_api_key);
    }

    public function test_typing_a_new_key_replaces_it_and_never_logs_the_value()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_old']);
        IntegrationSetting::flush();

        $this->actingAs($this->admin());

        Livewire::test(OcrAiSettings::class)
            ->set('groq_api_key', 'gsk_brand_new')
            ->call('save');

        IntegrationSetting::flush();

        $this->assertSame('gsk_brand_new', IntegrationSetting::first()->groq_api_key);

        $log = ActivityLog::where('event_type', 'ocr_ai_settings_updated')->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('gsk_', $log->details);
    }

    // ---------------------------------------------------------------
    // Reports
    // ---------------------------------------------------------------

    public function test_report_rejects_an_end_date_before_the_start_date()
    {
        $this->actingAs($this->admin());

        Livewire::test(Reports::class)
            ->set('startDate', '2026-10-10')
            ->set('endDate', '2026-10-01')
            ->call('exportCsv')
            ->assertHasErrors(['endDate']);

        $this->assertDatabaseMissing('activity_logs', ['event_type' => 'report_exported']);
    }

    public function test_report_export_is_audited()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(Reports::class)
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->call('exportCsv');

        $this->assertDatabaseHas('activity_logs', [
            'user_id'    => $admin->id,
            'event_type' => 'report_exported',
        ]);
    }

    // ---------------------------------------------------------------
    // Categories
    // ---------------------------------------------------------------

    public function test_savings_category_cannot_be_renamed()
    {
        $this->actingAs($this->admin());

        $savings = ExpenseCategory::create([
            'name'  => 'Savings',
            'icon'  => 'cash',
            'color' => 'bg-slate-100 text-slate-700',
        ]);

        Livewire::test(ExpenseCategories::class)
            ->call('openEdit', $savings->id)
            ->set('edit_name', 'Something Else')
            ->call('update')
            ->assertHasErrors(['edit_name']);

        $this->assertSame('Savings', $savings->fresh()->name);
    }

    public function test_new_categories_cannot_use_the_savings_name()
    {
        $this->actingAs($this->admin());

        Livewire::test(ExpenseCategories::class)
            ->call('openCreate')
            ->set('create_name', 'Savings Plus')
            ->set('create_icon', 'cash')
            ->set('create_color', 'slate')
            ->call('store')
            ->assertHasErrors(['create_name']);

        $this->assertDatabaseMissing('expense_categories', ['name' => 'Savings Plus']);
    }

    // ---------------------------------------------------------------
    // Deletions
    // ---------------------------------------------------------------

    public function test_deleting_an_admin_keeps_their_audit_trail()
    {
        $super = $this->admin('super_admin');
        $other = $this->admin('admin');

        ActivityLog::create([
            'user_id'    => $other->id,
            'event_type' => 'category_created',
            'details'    => 'Created category "Test"',
        ]);

        $this->actingAs($super);

        Livewire::test(AdminManagement::class)
            ->set('confirmingDeleteId', $other->id)
            ->call('deleteAdmin');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);

        $kept = ActivityLog::where('event_type', 'category_created')->first();

        $this->assertNotNull($kept, 'The deleted admin\'s log row was erased.');
        $this->assertNull($kept->user_id);
        $this->assertStringContainsString('former admin', $kept->details);
    }

    public function test_deleting_a_student_removes_their_notifications()
    {
        $student = $this->student();

        DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\LowAllowanceWarning',
            'notifiable_type' => User::class,
            'notifiable_id'   => $student->id,
            'data'            => ['anomaly_type' => 'low_allowance_threshold', 'description' => 'x'],
        ]);

        $this->actingAs($this->admin());

        Livewire::test(UserManagement::class)
            ->set('confirmingDeleteId', $student->id)
            ->call('deleteUser');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $student->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'user_deleted']);
    }

    // ---------------------------------------------------------------
    // Admin profile
    // ---------------------------------------------------------------

    public function test_admin_can_update_their_name_without_a_password()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(AdminProfile::class)
            ->set('name', 'Renamed Admin')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Renamed Admin', $admin->fresh()->name);
    }

    public function test_admin_email_change_requires_the_current_password()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(AdminProfile::class)
            ->set('email', 'new-admin@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['current_password']);

        $this->assertNotSame('new-admin@example.com', $admin->fresh()->email);
    }
}