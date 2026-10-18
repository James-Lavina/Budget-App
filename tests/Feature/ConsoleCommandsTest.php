<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\RiskSetting;
use App\Models\SavingsGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
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

    private function gapAlerts(User $user): int
    {
        return DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->get()
            ->filter(fn ($n) => ($n->data['anomaly_type'] ?? null) === 'no_expense_logs')
            ->count();
    }

    private function enableGapRule(int $days = 3): void
    {
        RiskSetting::current()->update([
            'no_expense_logs_enabled' => true,
            'no_expense_logs_days'    => $days,
        ]);
    }

    // ---------------------------------------------------------------
    // risk:check-log-gaps
    // ---------------------------------------------------------------

    public function test_students_who_stopped_logging_are_alerted()
    {
        $this->enableGapRule(3);
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $this->makeExpense($user, $this->makeCategory(), 50.00, '2026-10-02 10:00:00'); // 5 days ago

        $this->artisan('risk:check-log-gaps')->assertExitCode(0);

        $this->assertSame(1, $this->gapAlerts($user));
    }

    public function test_recent_loggers_are_not_alerted()
    {
        $this->enableGapRule(3);
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $this->makeExpense($user, $this->makeCategory(), 50.00, '2026-10-06 10:00:00');

        $this->artisan('risk:check-log-gaps')->assertExitCode(0);

        $this->assertSame(0, $this->gapAlerts($user));
    }

    public function test_the_alert_is_not_duplicated_on_the_next_run()
    {
        $this->enableGapRule(3);
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $this->makeExpense($user, $this->makeCategory(), 50.00, '2026-10-02 10:00:00');

        $this->artisan('risk:check-log-gaps');
        $this->artisan('risk:check-log-gaps');

        $this->assertSame(1, $this->gapAlerts($user));
    }

    public function test_savings_transfers_do_not_count_as_logging_an_expense()
    {
        $this->enableGapRule(3);
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $cat  = $this->makeCategory();
        $goal = SavingsGoal::create([
            'user_id' => $user->id, 'target_name' => 'Laptop', 'target_amount' => 500,
            'current_saved' => 50, 'status' => 'active',
        ]);
        $this->makeExpense($user, $cat, 50.00, '2026-09-27 10:00:00'); // 10 days ago
        $this->makeExpense($user, $this->makeCategory('Savings'), 50.00, '2026-10-06 10:00:00', [
            'savings_goal_id' => $goal->id,
        ]);

        $this->artisan('risk:check-log-gaps');

        $this->assertSame(1, $this->gapAlerts($user));
    }

    public function test_students_with_no_expenses_are_measured_from_their_signup_date()
    {
        $this->enableGapRule(3);
        $user = $this->makeStudent(['created_at' => Carbon::now()->subDays(5)]);
        $this->makeBudget($user);

        $this->artisan('risk:check-log-gaps');

        $this->assertSame(1, $this->gapAlerts($user));
    }

    public function test_the_rule_can_be_disabled()
    {
        RiskSetting::current()->update(['no_expense_logs_enabled' => false]);
        $user = $this->makeStudent();
        $this->makeBudget($user);
        $this->makeExpense($user, $this->makeCategory(), 50.00, '2026-09-20 10:00:00');

        $this->artisan('risk:check-log-gaps')->assertExitCode(0);

        $this->assertSame(0, $this->gapAlerts($user));
    }

    public function test_suspended_students_and_students_without_a_budget_are_skipped()
    {
        $this->enableGapRule(3);
        $suspended = $this->makeStudent(['status' => 'suspended']);
        $this->makeBudget($suspended);
        $this->makeExpense($suspended, $this->makeCategory(), 50.00, '2026-09-20 10:00:00');
        $noBudget = $this->makeStudent(['created_at' => Carbon::now()->subDays(30)]);

        $this->artisan('risk:check-log-gaps');

        $this->assertSame(0, $this->gapAlerts($suspended));
        $this->assertSame(0, $this->gapAlerts($noBudget));
    }

    // ---------------------------------------------------------------
    // logs:prune-activity
    // ---------------------------------------------------------------

    private function logAged(int $daysOld): ActivityLog
    {
        $log = ActivityLog::create(['event_type' => 'auth_login', 'details' => "aged {$daysOld}"]);
        DB::table('activity_logs')->where('id', $log->id)->update(['created_at' => Carbon::now()->subDays($daysOld)]);

        return $log;
    }

    private function notificationRead(int $daysAgo): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\WeeklyBudgetReview',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->makeStudent()->id,
            'data'            => ['description' => 'x'],
            'read_at'         => Carbon::now()->subDays($daysAgo),
        ]);
    }

    public function test_prune_deletes_old_logs_and_old_read_notifications_only()
    {
        $old    = $this->logAged(200);
        $recent = $this->logAged(10);
        $oldRead = $this->notificationRead(200);
        $recentRead = $this->notificationRead(5);
        $unread = DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\WeeklyBudgetReview',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->makeStudent()->id,
            'data'            => ['description' => 'unread'],
        ]);
        DB::table('notifications')->where('id', $unread->id)->update(['created_at' => Carbon::now()->subDays(400)]);

        $this->artisan('logs:prune-activity', ['--days' => 180])->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recent->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    public function test_prune_never_goes_below_thirty_days()
    {
        $twentyDays = $this->logAged(20);
        $fortyDays  = $this->logAged(40);

        $this->artisan('logs:prune-activity', ['--days' => 5])->assertExitCode(0);

        $this->assertDatabaseHas('activity_logs', ['id' => $twentyDays->id]);
        $this->assertDatabaseMissing('activity_logs', ['id' => $fortyDays->id]);
    }

    // ---------------------------------------------------------------
    // admin:promote-super
    // ---------------------------------------------------------------

    public function test_promote_super_upgrades_an_existing_user()
    {
        $admin = $this->makeAdmin('admin');

        $this->artisan('admin:promote-super', ['email' => $admin->email])->assertExitCode(0);

        $this->assertSame('super_admin', $admin->fresh()->role);
    }

    public function test_promote_super_fails_for_an_unknown_email()
    {
        $this->artisan('admin:promote-super', ['email' => 'nobody@example.com'])->assertExitCode(1);
    }

    // ---------------------------------------------------------------
    // Scheduler
    // ---------------------------------------------------------------

    public function test_the_maintenance_commands_are_scheduled()
    {
        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode(' | ');

        $this->assertStringContainsString('risk:check-log-gaps', $commands);
        $this->assertStringContainsString('logs:prune-activity', $commands);
        $this->assertStringContainsString('auth:clear-resets', $commands);
    }
}