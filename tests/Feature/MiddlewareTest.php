<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyTestFakeNow;
use App\Models\AppSetting;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class MiddlewareTest extends TestCase
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

    private function enableMaintenance(): void
    {
        AppSetting::current()->update(['maintenance_mode_enabled' => true]);
        AppSetting::flush();
    }

    // ---------------------------------------------------------------
    // Authenticate
    // ---------------------------------------------------------------

    public function test_guests_are_redirected_to_login_and_json_requests_get_401()
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->getJson('/dashboard')->assertUnauthorized();
    }

    // ---------------------------------------------------------------
    // RedirectIfAuthenticated (guest middleware)
    // ---------------------------------------------------------------

    public function test_signed_in_users_cannot_open_the_guest_pages()
    {
        $this->actingAs($this->makeStudent());

        foreach (['/login', '/register', '/forgot-password'] as $uri) {
            $this->get($uri)->assertRedirect(RouteServiceProvider::HOME);
        }
    }

    // ---------------------------------------------------------------
    // EnsureUserIsStudent / isAdmin / CheckSuperAdmin
    // ---------------------------------------------------------------

    public function test_every_admin_role_is_sent_away_from_student_pages()
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->makeAdmin($role))
                ->get('/dashboard')
                ->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_students_are_sent_away_from_admin_pages_with_an_error_message()
    {
        $student = $this->makeStudent();
        $this->makeBudget($student);

        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error', 'Unauthorized access attempt');
    }

    public function test_only_super_admins_reach_the_admin_accounts_page()
    {
        $this->actingAs($this->makeAdmin('admin'))->get('/admin/admin-accounts')->assertForbidden();
        $this->actingAs($this->makeAdmin('super_admin'))->get('/admin/admin-accounts')->assertOk();
    }

    // ---------------------------------------------------------------
    // EnsureUserIsActive
    // ---------------------------------------------------------------

    public function test_a_suspended_student_with_a_live_session_is_logged_out()
    {
        $student = $this->makeStudent(['status' => 'suspended']);
        $this->makeBudget($student);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error');

        $this->assertGuest();
    }

    public function test_a_suspended_livewire_request_gets_a_419_so_the_page_refreshes_to_login()
    {
        $student = $this->makeStudent(['status' => 'suspended']);

        $this->actingAs($student)
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson('/livewire/message/student.dashboard', [])
            ->assertStatus(419)
            ->assertJson(['message' => 'Session ended.']);

        $this->assertGuest();
    }

    public function test_active_students_are_not_affected()
    {
        $student = $this->makeStudent();
        $this->makeBudget($student);

        $this->actingAs($student)->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($student);
    }

    // ---------------------------------------------------------------
    // CheckMaintenanceMode
    // ---------------------------------------------------------------

    public function test_maintenance_mode_blocks_students_with_a_503_page()
    {
        $student = $this->makeStudent();
        $this->makeBudget($student);
        $this->enableMaintenance();

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertStatus(503)
            ->assertSee('undergoing scheduled maintenance');
    }

    public function test_maintenance_mode_lets_both_admin_roles_through()
    {
        $this->enableMaintenance();

        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->makeAdmin($role))->get('/admin/dashboard')->assertOk();
        }
    }

    public function test_a_blocked_student_can_still_log_out_during_maintenance()
    {
        $student = $this->makeStudent();
        $this->enableMaintenance();

        $response = $this->actingAs($student)->post('/');

        $this->assertNotSame(503, $response->getStatusCode());
        $this->assertGuest();
    }

    public function test_guests_can_still_reach_the_login_page_during_maintenance()
    {
        $this->enableMaintenance();

        $this->get('/login')->assertOk();
    }

    // ---------------------------------------------------------------
    // ApplyTestFakeNow
    // ---------------------------------------------------------------

    public function test_the_fake_clock_is_applied_only_inside_the_handler_and_only_locally()
    {
        Carbon::setTestNow(null);
        session(['test_fake_now' => '2020-01-01 10:00:00']);
        $middleware = new ApplyTestFakeNow();

        $seenLocally = null;
        $this->app['env'] = 'local';
        $middleware->handle(Request::create('/x'), function () use (&$seenLocally) {
            $seenLocally = Carbon::now()->toDateString();

            return response('ok');
        });

        $this->assertSame('2020-01-01', $seenLocally);
        $this->assertFalse(Carbon::hasTestNow(), 'Real time must be restored after the handler.');

        $seenInProduction = null;
        $this->app['env'] = 'production';
        $middleware->handle(Request::create('/x'), function () use (&$seenInProduction) {
            $seenInProduction = Carbon::now()->toDateString();

            return response('ok');
        });

        $this->assertNotSame('2020-01-01', $seenInProduction);
    }

    // ---------------------------------------------------------------
    // Full-page smoke tests (middleware + layout + Livewire render)
    // ---------------------------------------------------------------

    private function assertPagesRender(array $uris): void
    {
        foreach ($uris as $uri) {
            $response = $this->get($uri);
            $this->assertSame(200, $response->getStatusCode(), "GET {$uri} returned {$response->getStatusCode()}");
        }
    }

    public function test_public_pages_render_for_guests()
    {
        $this->assertPagesRender(['/', '/login', '/register', '/forgot-password', '/reset-password/some-token']);
    }

    public function test_every_student_page_renders()
    {
        $student  = $this->makeStudent();
        $this->makeBudget($student);
        $category = $this->makeCategory();
        $expense  = $this->makeExpense($student, $category, 25.00);
        $this->actingAs($student);

        $this->assertPagesRender([
            '/dashboard',
            '/expenses',
            '/expenses/create',
            '/expenses/receipt-scanner',
            "/expenses/{$expense->id}/edit",
            '/budget/add-funds',
            '/forecast',
            '/savings-goals',
            '/simulation',
            '/notification',
            '/settings',
            '/profile',
        ]);

        $this->get('/budget-setup')->assertRedirect(route('student.dashboard'));
    }

    public function test_the_setup_wizard_renders_for_a_new_student()
    {
        $this->actingAs($this->makeStudent());

        $this->assertPagesRender(['/budget-setup']);
        $this->get('/dashboard')->assertRedirect(route('student.budget-setup'));
    }

    public function test_every_admin_page_renders()
    {
        $this->actingAs($this->makeAdmin('super_admin'));

        $this->assertPagesRender([
            '/admin/dashboard',
            '/admin/users',
            '/admin/categories',
            '/admin/risk-rules',
            '/admin/ocr-ai-settings',
            '/admin/reports',
            '/admin/activity-logs',
            '/admin/settings',
            '/admin/profile',
            '/admin/admin-accounts',
        ]);
    }
}