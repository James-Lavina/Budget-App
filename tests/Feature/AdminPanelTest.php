<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\ActivityLogIndex;
use App\Http\Livewire\Admin\AdminManagement;
use App\Http\Livewire\Admin\Dashboard as AdminDashboard;
use App\Http\Livewire\Admin\ExpenseCategories;
use App\Http\Livewire\Admin\Reports;
use App\Http\Livewire\Admin\RiskRules;
use App\Http\Livewire\Admin\Settings;
use App\Http\Livewire\Admin\UserManagement;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-10-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signInAdmin(string $role = 'admin'): User
    {
        $admin = $this->makeAdmin($role);
        $this->actingAs($admin);

        return $admin;
    }

    // ---------------------------------------------------------------
    // User management
    // ---------------------------------------------------------------

    public function test_suspending_and_reactivating_a_student()
    {
        $admin   = $this->signInAdmin();
        $student = $this->makeStudent();

        Livewire::test(UserManagement::class)
            ->call('confirmSuspend', $student->id)
            ->call('toggleSuspend');

        $this->assertSame('suspended', $student->fresh()->status);

        Livewire::test(UserManagement::class)
            ->call('confirmSuspend', $student->id)
            ->call('toggleSuspend');

        $this->assertSame('active', $student->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'event_type' => 'user_suspended']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'event_type' => 'user_reactivated']);
    }

    public function test_user_management_cannot_target_admin_accounts()
    {
        $this->signInAdmin();
        $otherAdmin = $this->makeAdmin();

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(UserManagement::class)
            ->set('confirmingSuspendId', $otherAdmin->id)
            ->call('toggleSuspend');
    }

    public function test_editing_a_student_updates_and_audits_the_change()
    {
        $admin   = $this->signInAdmin();
        $student = $this->makeStudent(['name' => 'Old Name']);

        Livewire::test(UserManagement::class)
            ->call('openEdit', $student->id)
            ->set('edit_name', 'New Name')
            ->set('edit_default_allowance', '1500.50')
            ->set('edit_default_reset_day', 'Friday')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $student->refresh();
        $this->assertSame('New Name', $student->name);
        $this->assertEquals(1500.50, $student->default_allowance);
        $this->assertSame('Friday', $student->default_reset_day);

        $log = ActivityLog::where('event_type', 'user_edited')->first();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('"Old Name" → "New Name"', $log->details);
    }

    public function test_editing_a_student_validates_input()
    {
        $this->signInAdmin();
        $student = $this->makeStudent();
        $other   = $this->makeStudent();

        $component = Livewire::test(UserManagement::class)->call('openEdit', $student->id);

        $component->set('edit_email', $other->email)->call('saveEdit')->assertHasErrors(['edit_email']);
        $component->set('edit_email', $student->email)
            ->set('edit_default_allowance', '0')->call('saveEdit')->assertHasErrors(['edit_default_allowance']);
        $component->set('edit_default_allowance', '10.123')->call('saveEdit')->assertHasErrors(['edit_default_allowance']);
        $component->set('edit_default_allowance', '100')
            ->set('edit_default_reset_day', 'Funday')->call('saveEdit')->assertHasErrors(['edit_default_reset_day']);
    }

    public function test_viewing_a_student_never_triggers_their_weekly_reset()
    {
        $this->signInAdmin();
        $student = $this->makeStudent();
        $budget  = $this->makeBudget($student, 400.00, ['cycle_start_date' => '2026-09-28']); // stale cycle

        Livewire::test(UserManagement::class)
            ->call('viewUser', $student->id)
            ->assertSee($student->email);

        $this->assertSame('2026-09-28', $budget->fresh()->cycle_start_date->format('Y-m-d'));
        $this->assertEquals(400.00, $budget->fresh()->remaining_allowance);
        Notification::assertNothingSent();
    }

    public function test_search_and_status_filters()
    {
        $this->signInAdmin();
        $this->makeStudent(['name' => 'Alice Reyes', 'email' => 'alice@example.com']);
        $this->makeStudent(['name' => 'Bob Santos', 'email' => 'bob@example.com', 'status' => 'suspended']);

        Livewire::test(UserManagement::class)
            ->assertSee('Alice Reyes')
            ->assertSee('Bob Santos')
            ->set('search', 'Alice')
            ->assertSee('Alice Reyes')
            ->assertDontSee('Bob Santos')
            ->set('search', '')
            ->set('statusFilter', 'suspended')
            ->assertSee('Bob Santos')
            ->assertDontSee('Alice Reyes');
    }

    public function test_deleting_a_student_also_removes_their_receipt_files()
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->signInAdmin();
        $student = $this->makeStudent();
        Storage::disk('local')->put('receipts/a.jpg', 'x');
        Receipt::create(['user_id' => $student->id, 'image_path' => 'receipts/a.jpg', 'status' => 'processed']);

        Livewire::test(UserManagement::class)
            ->set('confirmingDeleteId', $student->id)
            ->call('deleteUser');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('receipts', ['user_id' => $student->id]);
        Storage::disk('local')->assertMissing('receipts/a.jpg');
    }

    // ---------------------------------------------------------------
    // Risk rules
    // ---------------------------------------------------------------

    public function test_saving_risk_rules_updates_the_settings_and_audits_the_diff()
    {
        $this->signInAdmin();

        Livewire::test(RiskRules::class)
            ->set('overspending_threshold', 70)
            ->set('rapid_spending_count', 4)
            ->set('no_expense_logs_enabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $settings = RiskSetting::current();
        $this->assertEquals(70, $settings->overspending_threshold);
        $this->assertEquals(4, $settings->rapid_spending_count);
        $this->assertTrue($settings->no_expense_logs_enabled);

        $log = ActivityLog::where('event_type', 'risk_rules_updated')->first();
        $this->assertStringContainsString('Overspending Threshold %: 80 → 70', $log->details);
        $this->assertStringContainsString('No Expense Logs enabled: Off → On', $log->details);
    }

    public function test_saving_unchanged_risk_rules_writes_no_audit_entry()
    {
        $this->signInAdmin();

        Livewire::test(RiskRules::class)->call('save')->assertHasNoErrors();

        $this->assertSame(0, ActivityLog::where('event_type', 'risk_rules_updated')->count());
    }

    public function test_risk_rule_bounds_are_enforced()
    {
        $this->signInAdmin();

        foreach ([
            ['overspending_threshold', 0], ['overspending_threshold', 101],
            ['daily_safe_to_spend_threshold', 0], ['rapid_spending_count', 1],
            ['rapid_spending_count', 21], ['no_expense_logs_days', 0],
            ['no_expense_logs_days', 61], ['low_remaining_budget_threshold', 101],
        ] as [$field, $value]) {
            Livewire::test(RiskRules::class)
                ->set($field, $value)
                ->call('save')
                ->assertHasErrors([$field]);
        }
    }

    // ---------------------------------------------------------------
    // App settings
    // ---------------------------------------------------------------

    public function test_saving_general_settings()
    {
        $this->signInAdmin();

        Livewire::test(Settings::class)
            ->set('application_name', 'Campus Wallet')
            ->set('primary_color', '#2563eb')
            ->set('email_notifications_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $settings = AppSetting::first();
        $this->assertSame('Campus Wallet', $settings->application_name);
        $this->assertSame('#2563eb', $settings->primary_color);
        $this->assertFalse($settings->email_notifications_enabled);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'app_settings_updated']);
    }

    public function test_only_preset_colors_and_a_name_are_accepted()
    {
        $this->signInAdmin();

        Livewire::test(Settings::class)
            ->set('primary_color', '#123456')
            ->call('save')
            ->assertHasErrors(['primary_color']);

        Livewire::test(Settings::class)
            ->set('application_name', '')
            ->call('save')
            ->assertHasErrors(['application_name']);
    }

    public function test_toggling_maintenance_mode_is_audited_with_its_own_event()
    {
        $this->signInAdmin();

        Livewire::test(Settings::class)
            ->set('maintenance_mode_enabled', true)
            ->call('save');

        $this->assertTrue((bool) AppSetting::first()->maintenance_mode_enabled);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'maintenance_mode_enabled']);
    }

    public function test_logo_upload_and_removal()
    {
        Storage::fake('public');
        $this->signInAdmin();

        Livewire::test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('logo.png', 100, 100))
            ->call('save')
            ->assertHasNoErrors();

        $path = AppSetting::first()->logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::test(Settings::class)->call('removeLogo');

        $this->assertNull(AppSetting::first()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_svg_logos_are_rejected()
    {
        Storage::fake('public');
        $this->signInAdmin();

        Livewire::test(Settings::class)
            ->set('logo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->call('save')
            ->assertHasErrors(['logo']);
    }

    // ---------------------------------------------------------------
    // Expense categories
    // ---------------------------------------------------------------

    public function test_creating_a_category_normalizes_keywords_and_stores_the_color_classes()
    {
        $this->signInAdmin();

        Livewire::test(ExpenseCategories::class)
            ->call('openCreate')
            ->set('create_name', 'Groceries')
            ->set('create_keywords', "Chicken, Burger ,chicken\nmilk tea")
            ->set('create_icon', 'food')
            ->set('create_color', 'emerald')
            ->call('store')
            ->assertHasNoErrors();

        $category = ExpenseCategory::where('name', 'Groceries')->first();
        $this->assertSame('chicken,burger,milk tea', $category->keywords);
        $this->assertSame('bg-emerald-50 text-emerald-700', $category->color);
        $this->assertSame('enabled', $category->status);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'category_created']);
    }

    public function test_category_validation()
    {
        $this->signInAdmin();
        $this->makeCategory('Existing');

        $create = fn (array $values) => tap(Livewire::test(ExpenseCategories::class)->call('openCreate'), function ($c) use ($values) {
            $values = array_merge(['create_name' => 'Fresh', 'create_icon' => 'food', 'create_color' => 'slate'], $values);
            foreach ($values as $key => $value) {
                $c->set($key, $value);
            }
            $c->call('store');
        });

        $create(['create_name' => 'Existing'])->assertHasErrors(['create_name']);
        $create(['create_icon' => 'not-a-real-icon'])->assertHasErrors(['create_icon']);
        $create(['create_color' => 'neon'])->assertHasErrors(['create_color']);
        $create(['create_name' => ''])->assertHasErrors(['create_name']);
    }

    public function test_only_one_category_can_be_the_fallback()
    {
        $this->signInAdmin();

        foreach (['Misc One', 'Misc Two'] as $name) {
            Livewire::test(ExpenseCategories::class)
                ->call('openCreate')
                ->set('create_name', $name)
                ->set('create_icon', 'food')
                ->set('create_color', 'slate')
                ->set('create_is_fallback', true)
                ->call('store');
        }

        $this->assertSame(['Misc Two'], ExpenseCategory::where('is_fallback', true)->pluck('name')->all());
    }

    public function test_editing_a_category_to_become_the_fallback_replaces_the_old_one()
    {
        $this->signInAdmin();
        $old = $this->makeCategory('Old Other', ['icon' => 'food', 'color' => 'bg-slate-100 text-slate-700', 'is_fallback' => true]);
        $new = $this->makeCategory('New Other', ['icon' => 'food', 'color' => 'bg-slate-100 text-slate-700']);

        Livewire::test(ExpenseCategories::class)
            ->call('openEdit', $new->id)
            ->set('edit_is_fallback', true)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertFalse((bool) $old->fresh()->is_fallback);
        $this->assertTrue((bool) $new->fresh()->is_fallback);
    }

    public function test_disabling_and_enabling_a_category()
    {
        $this->signInAdmin();
        $category = $this->makeCategory('Toggle Me');

        Livewire::test(ExpenseCategories::class)->call('toggleStatus', $category->id);
        $this->assertSame('disabled', $category->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'category_disabled']);

        Livewire::test(ExpenseCategories::class)->call('toggleStatus', $category->id);
        $this->assertSame('enabled', $category->fresh()->status);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'category_enabled']);
    }

    public function test_deleting_categories_is_blocked_when_in_use_or_reserved_for_savings()
    {
        $this->signInAdmin();
        $inUse   = $this->makeCategory('In Use');
        $savings = $this->makeCategory('Savings');
        $unused  = $this->makeCategory('Unused');
        $this->makeExpense($this->makeStudent(), $inUse, 10.00);

        foreach ([$inUse, $savings] as $protected) {
            Livewire::test(ExpenseCategories::class)
                ->set('confirmingDeleteId', $protected->id)
                ->call('delete');
            $this->assertDatabaseHas('expense_categories', ['id' => $protected->id]);
        }

        Livewire::test(ExpenseCategories::class)
            ->set('confirmingDeleteId', $unused->id)
            ->call('delete');

        $this->assertDatabaseMissing('expense_categories', ['id' => $unused->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'category_deleted']);
    }

    public function test_duplicate_icon_and_color_combinations_show_a_warning()
    {
        $this->signInAdmin();
        $this->makeCategory('Dining Out', ['icon' => 'food', 'color' => 'bg-emerald-50 text-emerald-700']);

        Livewire::test(ExpenseCategories::class)
            ->call('openCreate')
            ->set('create_icon', 'food')
            ->set('create_color', 'emerald')
            ->assertSee('already uses this icon and color');
    }

    // ---------------------------------------------------------------
    // Admin accounts (super admin only)
    // ---------------------------------------------------------------

    public function test_a_super_admin_can_create_an_admin()
    {
        $super = $this->signInAdmin('super_admin');

        Livewire::test(AdminManagement::class)
            ->call('openCreate')
            ->set('create_name', 'New Admin')
            ->set('create_email', 'new-admin@example.com')
            ->set('create_password', 'password123')
            ->set('create_password_confirmation', 'password123')
            ->call('store')
            ->assertHasNoErrors();

        $created = User::where('email', 'new-admin@example.com')->first();
        $this->assertSame('admin', $created->role);
        $this->assertSame('active', $created->status);
        $this->assertTrue(Hash::check('password123', $created->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $super->id, 'event_type' => 'admin_created']);
    }

    public function test_admin_creation_validates_input()
    {
        $this->signInAdmin('super_admin');
        $existing = $this->makeAdmin();

        Livewire::test(AdminManagement::class)
            ->call('openCreate')
            ->set('create_name', '')
            ->set('create_email', $existing->email)
            ->set('create_password', 'short')
            ->set('create_password_confirmation', 'different')
            ->call('store')
            ->assertHasErrors(['create_name', 'create_email', 'create_password']);
    }

    public function test_revoking_admin_access_demotes_to_student()
    {
        $this->signInAdmin('super_admin');
        $admin = $this->makeAdmin();

        Livewire::test(AdminManagement::class)
            ->call('confirmDemote', $admin->id)
            ->call('demote');

        $this->assertSame('student', $admin->fresh()->role);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'admin_demoted']);
    }

    public function test_super_admins_cannot_be_demoted_or_deleted_from_this_screen()
    {
        $this->signInAdmin('super_admin');
        $otherSuper = $this->makeAdmin('super_admin');

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::test(AdminManagement::class)
            ->set('confirmingDemoteId', $otherSuper->id)
            ->call('demote');
    }

    public function test_regular_admins_cannot_use_the_admin_accounts_component()
    {
        $this->signInAdmin('admin');

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::test(AdminManagement::class)->call('openCreate');
    }

    // ---------------------------------------------------------------
    // Activity logs
    // ---------------------------------------------------------------

    public function test_activity_log_search_matches_details_event_type_and_user_name()
    {
        $this->signInAdmin();
        $maria = $this->makeStudent(['name' => 'Maria Clara']);
        ActivityLog::create(['user_id' => $maria->id, 'event_type' => 'expense_logged', 'details' => 'Logged Zebra Snack']);
        ActivityLog::create(['user_id' => null, 'event_type' => 'auth_login_failed', 'details' => 'Failed login for ghost@example.com']);

        Livewire::test(ActivityLogIndex::class)
            ->set('search', 'Zebra')
            ->assertSee('Zebra Snack')
            ->assertDontSee('ghost@example.com')
            ->set('search', 'Maria')
            ->assertSee('Zebra Snack')
            ->set('search', 'auth_login_failed')
            ->assertSee('ghost@example.com')
            ->assertDontSee('Zebra Snack');
    }

    public function test_failed_events_are_labelled_failed()
    {
        $component = new ActivityLogIndex();

        $this->assertSame('Failed', $component->statusFor('auth_login_failed')['label']);
        $this->assertSame('Failed', $component->statusFor('notification_failed')['label']);
        $this->assertSame('Success', $component->statusFor('expense_logged')['label']);
    }

    // ---------------------------------------------------------------
    // Reports
    // ---------------------------------------------------------------

    public function test_report_summary_respects_the_date_range_and_filters()
    {
        $this->signInAdmin();
        $a   = $this->makeStudent();
        $b   = $this->makeStudent();
        $cat = $this->makeCategory();
        $this->makeExpense($a, $cat, 100.00, '2026-10-05 10:00:00');
        $this->makeExpense($a, $cat, 200.00, '2026-10-06 10:00:00');
        $this->makeExpense($b, $cat, 50.00, '2026-10-06 11:00:00', ['tracking_type' => 'ocr']);
        $this->makeExpense($b, $cat, 999.00, '2026-08-01 11:00:00'); // outside range
        RiskLog::create([
            'user_id' => $a->id, 'anomaly_type' => 'overspending_threshold',
            'severity_tier' => 'high', 'description' => 'x',
        ]);

        Livewire::test(Reports::class)
            ->set('startDate', '2026-10-01')
            ->set('endDate', '2026-10-31')
            ->assertViewHas('total_expenses', 3)
            ->assertViewHas('total_amount', 350)
            ->assertViewHas('ocr_scans', 1)
            ->assertViewHas('budget_alerts', 1)
            ->set('selectedUser', $a->id)
            ->assertViewHas('total_expenses', 2)
            ->assertViewHas('total_amount', 300)
            ->assertViewHas('ocr_scans', 0);
    }

    public function test_an_invalid_report_range_shows_zeros()
    {
        $this->signInAdmin();
        $this->makeExpense($this->makeStudent(), $this->makeCategory(), 100.00);

        Livewire::test(Reports::class)
            ->set('startDate', '2026-10-31')
            ->set('endDate', '2026-10-01')
            ->assertViewHas('total_expenses', 0)
            ->assertHasErrors(['endDate']);
    }

    // ---------------------------------------------------------------
    // Admin dashboard
    // ---------------------------------------------------------------

    public function test_dashboard_statistics()
    {
        $this->signInAdmin();
        $a   = $this->makeStudent();
        $b   = $this->makeStudent();
        $cat = $this->makeCategory();
        $this->makeExpense($a, $cat, 100.00, '2026-10-07 09:00:00');
        $this->makeExpense($b, $cat, 100.00, '2026-10-07 10:00:00');
        ActivityLog::create(['user_id' => $a->id, 'event_type' => 'ai_forecast_requested']);
        ActivityLog::create(['user_id' => $a->id, 'event_type' => 'ai_forecast_requested']);
        ActivityLog::create(['user_id' => $b->id, 'event_type' => 'ai_simulation_requested']);

        Livewire::test(AdminDashboard::class)
            ->assertViewHas('totalUsers', 2)
            ->assertViewHas('activeToday', 2)
            ->assertViewHas('totalExpenses', 2)
            ->assertViewHas('aiForecastCount', 2)
            ->assertViewHas('aiSimulationCount', 1)
            ->assertViewHas('aiForecastRequests', 3)
            ->assertViewHas('registrationLabels', ['Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed'])
            ->assertViewHas('registrationCounts', [0, 0, 0, 0, 0, 0, 2]);
    }
}