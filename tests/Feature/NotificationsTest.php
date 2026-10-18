<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\NotificationBadge;
use App\Http\Livewire\Student\NotificationCenter;
use App\Http\Livewire\Student\NotificationIndex;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\RiskLog;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Notifications\BudgetRiskNotification;
use App\Notifications\CategoryConcentrationWarning;
use App\Notifications\LargeTransactionAlert;
use App\Notifications\LowAllowanceWarning;
use App\Notifications\NoExpenseLogsWarning;
use App\Notifications\SavingsGoalAchieved;
use App\Notifications\SavingsMilestoneReached;
use App\Notifications\WeeklyBudgetReview;
use App\Services\NotificationLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class NotificationsTest extends TestCase
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

    private function notify(User $user, array $data = [], $readAt = null, $createdAt = null): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\WeeklyBudgetReview',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => array_merge([
                'anomaly_type'  => 'weekly_review',
                'severity_tier' => 'success',
                'description'   => 'Hello there',
            ], $data),
            'read_at'    => $readAt,
            'created_at' => $createdAt ?? Carbon::now(),
        ]);
    }

    private function student(array $attributes = []): User
    {
        return $this->makeStudent(array_merge(['name' => 'Maria Clara'], $attributes));
    }

    // ---------------------------------------------------------------
    // Badge
    // ---------------------------------------------------------------

    public function test_the_badge_counts_only_my_unread_notifications()
    {
        $user = $this->student();
        $this->actingAs($user);
        $this->notify($user);
        $this->notify($user);
        $this->notify($user, [], Carbon::now());
        $this->notify($this->makeStudent());

        Livewire::test(NotificationBadge::class)->assertViewHas('unreadCount', 2);
    }

    // ---------------------------------------------------------------
    // Dropdown (center)
    // ---------------------------------------------------------------

    public function test_the_dropdown_shows_the_six_newest_unread_and_the_total_count()
    {
        $user = $this->student();
        $this->actingAs($user);
        foreach (range(1, 8) as $i) {
            $this->notify($user, ['description' => "Notice {$i}"], null, Carbon::now()->subMinutes($i));
        }
        $this->notify($user, ['description' => 'Notice read'], Carbon::now());

        Livewire::test(NotificationCenter::class)
            ->assertViewHas('totalUnreadCount', 8)
            ->assertSee('Notice 1')
            ->assertSee('Notice 6')
            ->assertDontSee('Notice 7')
            ->assertDontSee('Notice read');
    }

    public function test_mark_as_read_and_mark_all_as_read()
    {
        $user = $this->student();
        $this->actingAs($user);
        $first  = $this->notify($user);
        $second = $this->notify($user);

        Livewire::test(NotificationCenter::class)->call('markAsRead', $first->id);

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($second->fresh()->read_at);

        Livewire::test(NotificationCenter::class)->call('markAllAsRead');

        $this->assertNotNull($second->fresh()->read_at);
    }

    public function test_dismiss_marks_the_notification_read_but_keeps_the_row()
    {
        $user = $this->student();
        $this->actingAs($user);
        $notification = $this->notify($user);

        Livewire::test(NotificationCenter::class)->call('dismiss', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_student_cannot_read_another_students_notification()
    {
        $user  = $this->student();
        $other = $this->makeStudent();
        $this->actingAs($user);
        $theirs = $this->notify($other);

        Livewire::test(NotificationCenter::class)->call('markAsRead', $theirs->id);
        Livewire::test(NotificationIndex::class)->call('markAsRead', $theirs->id);
        Livewire::test(NotificationCenter::class)->call('markAllAsRead');

        $this->assertNull($theirs->fresh()->read_at);
    }

    // ---------------------------------------------------------------
    // Full page
    // ---------------------------------------------------------------

    public function test_the_index_marks_notifications_read_and_reports_the_unread_count()
    {
        $user = $this->student();
        $this->actingAs($user);
        $one = $this->notify($user);
        $this->notify($user);

        Livewire::test(NotificationIndex::class)
            ->assertViewHas('unreadCount', 2)
            ->call('markAsRead', $one->id)
            ->assertViewHas('unreadCount', 1)
            ->call('markAllAsRead')
            ->assertViewHas('unreadCount', 0);
    }

    public function test_non_recurring_notifications_can_be_deleted_but_other_students_cannot()
    {
        $user  = $this->student();
        $other = $this->makeStudent();
        $this->actingAs($user);
        $mine   = $this->notify($user);
        $theirs = $this->notify($other);

        Livewire::test(NotificationIndex::class)
            ->call('delete', $mine->id)
            ->call('delete', $theirs->id);

        $this->assertNull(DatabaseNotification::find($mine->id));
        $this->assertNotNull(DatabaseNotification::find($theirs->id));
    }

    public function test_resolved_alerts_are_labelled_resolved()
    {
        $user = $this->student();
        $this->actingAs($user);
        $this->notify($user, [
            'anomaly_type' => 'low_allowance_threshold',
            'description'  => 'Old low balance warning',
            'resolved'     => true,
        ]);

        Livewire::test(NotificationIndex::class)
            ->assertSee('Old low balance warning')
            ->assertSee('Resolved');
    }

    // ---------------------------------------------------------------
    // Notification classes
    // ---------------------------------------------------------------

    public function test_email_is_only_a_channel_when_both_the_student_and_the_admin_allow_it()
    {
        $user = $this->student(['email_notifications_enabled' => true]);
        $note = new WeeklyBudgetReview(100.0, 50.0);

        $this->assertSame(['database', 'mail'], $note->via($user));

        AppSetting::current()->update(['email_notifications_enabled' => false]);
        AppSetting::flush();
        $this->assertSame(['database'], $note->via($user));

        AppSetting::current()->update(['email_notifications_enabled' => true]);
        AppSetting::flush();
        $user->update(['email_notifications_enabled' => false]);
        $this->assertSame(['database'], $note->via($user->fresh()));
    }

    public function test_in_app_only_notifications_never_use_mail()
    {
        $user = $this->student(['email_notifications_enabled' => true]);

        foreach ([
            new LowAllowanceWarning(10, 100.0),
            new CategoryConcentrationWarning('Food', 60, 600.0, 1000.0),
            new LargeTransactionAlert(1, 'Laptop', 400.0, 40),
            new NoExpenseLogsWarning(5),
            new SavingsMilestoneReached(50, 'Laptop', 1),
        ] as $notification) {
            $this->assertSame(['database'], $notification->via($user), get_class($notification));
        }
    }

    public function test_risk_email_subjects_follow_the_alert_type_and_severity()
    {
        $user = $this->student();

        foreach ([
            'early_week_depletion'   => 'Pace Check',
            'rapid_overspending'     => 'Pace Check',
            'overspending_threshold' => 'Budget Alert',
            'daily_safe_to_spend'    => 'Daily Limit',
            'rapid_spending'         => 'Rapid Spending',
            'something_new'          => 'Budget Update',
        ] as $type => $label) {
            $log = new RiskLog(['anomaly_type' => $type, 'severity_tier' => 'high', 'description' => 'Details here']);
            $mail = (new BudgetRiskNotification($log))->toMail($user);

            $this->assertSame("[{$label}: High Priority] Budget Alert", $mail->subject, $type);
            $this->assertSame('Hello Maria Clara,', $mail->greeting);
            $this->assertSame(route('student.dashboard'), $mail->actionUrl);
            $this->assertStringContainsString('Details here', $mail->introLines[1]);
        }
    }

    public function test_risk_notification_array_payload()
    {
        $log = new RiskLog(['anomaly_type' => 'rapid_spending', 'severity_tier' => 'medium', 'description' => 'Careful']);
        $log->id = 42;

        $payload = (new BudgetRiskNotification($log))->toArray($this->student());

        $this->assertSame(42, $payload['risk_log_id']);
        $this->assertSame('rapid_spending', $payload['anomaly_type']);
        $this->assertSame('medium', $payload['severity_tier']);
        $this->assertFalse($payload['resolved']);
    }

    public function test_goal_achieved_email_and_payload()
    {
        $user = $this->student();
        $goal = new SavingsGoal(['target_name' => 'Laptop', 'target_amount' => 500]);
        $note = new SavingsGoalAchieved($goal);

        $mail = $note->toMail($user);
        $this->assertStringContainsString('Laptop', $mail->subject);
        $this->assertSame(route('student.goals'), $mail->actionUrl);

        $payload = $note->toArray($user);
        $this->assertSame('goal_achieved', $payload['anomaly_type']);
        $this->assertSame('success', $payload['severity_tier']);
        $this->assertStringContainsString('₱500.00', $payload['description']);
        $this->assertStringContainsString('Laptop', $payload['description']);
    }

    public function test_weekly_review_summary_wording()
    {
        $user = $this->student();

        $withSavings = (new WeeklyBudgetReview(300.0, 200.0, 'success', 50.0))->toArray($user);
        $this->assertStringContainsString(
            'Last week you spent ₱300.00, saved ₱50.00 and rolled over ₱200.00 into your new budget.',
            $withSavings['description']
        );

        $without = (new WeeklyBudgetReview(300.0, 200.0, 'medium'))->toArray($user);
        $this->assertStringContainsString('Last week you spent ₱300.00 and rolled over ₱200.00', $without['description']);
        $this->assertStringNotContainsString('saved', $without['description']);
        $this->assertSame('medium', $without['severity_tier']);

        $mail = (new WeeklyBudgetReview(300.0, 200.0))->toMail($user);
        $this->assertSame(route('student.dashboard'), $mail->actionUrl);
    }

    public function test_mail_messages_render_without_errors()
    {
        $user = $this->student();

        $html = (string) (new WeeklyBudgetReview(300.0, 200.0, 'success', 50.0))->toMail($user)->render();

        $this->assertStringContainsString('Hello Maria Clara!', $html);
        $this->assertStringContainsString('View Dashboard', $html);
    }

    public function test_in_app_notification_payloads()
    {
        $user = $this->student();

        $low = (new LowAllowanceWarning(10, 100.0))->toArray($user);
        $this->assertSame('low_allowance_threshold', $low['anomaly_type']);
        $this->assertStringContainsString('10%', $low['description']);
        $this->assertStringContainsString('₱100.00', $low['description']);
        $this->assertFalse($low['resolved']);

        $category = (new CategoryConcentrationWarning('Food & Dining', 60, 600.0, 1000.0))->toArray($user);
        $this->assertSame('Food & Dining', $category['category']);
        $this->assertStringContainsString('60%', $category['description']);
        $this->assertStringContainsString('of ₱1,000.00 spent', $category['description']);

        $noTotal = (new CategoryConcentrationWarning('Food', 60, 600.0))->toArray($user);
        $this->assertStringNotContainsString('spent)', $noTotal['description']);

        $large = (new LargeTransactionAlert(5, 'Laptop', 400.0, 40))->toArray($user);
        $this->assertSame(5, $large['expense_id']);
        $this->assertStringContainsString('"Laptop" used ₱400.00 (40% of your weekly allowance)', $large['description']);

        $gap = (new NoExpenseLogsWarning(5))->toArray($user);
        $this->assertSame('low', $gap['severity_tier']);
        $this->assertStringContainsString('in 5 days', $gap['description']);

        $milestone = (new SavingsMilestoneReached(50, 'Laptop', 7))->toArray($user);
        $this->assertSame(50, $milestone['milestone']);
        $this->assertSame(7, $milestone['goal_id']);
        $this->assertStringContainsString("saved 50% of your target for 'Laptop'", $milestone['description']);
    }

    // ---------------------------------------------------------------
    // NotificationLogger
    // ---------------------------------------------------------------

    public function test_a_failed_notification_is_recorded_for_admins()
    {
        $user = $this->student();

        NotificationLogger::logFailure($user, SavingsGoalAchieved::class, new \RuntimeException('SMTP exploded'), 'Laptop');

        $log = ActivityLog::where('event_type', 'notification_failed')->first();
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringContainsString('SavingsGoalAchieved', $log->details);
        $this->assertStringContainsString('(Laptop)', $log->details);
        $this->assertStringContainsString('SMTP exploded', $log->details);
    }

    public function test_the_logger_never_throws_without_a_recipient_and_truncates_long_messages()
    {
        NotificationLogger::logFailure(null, SavingsGoalAchieved::class, new \RuntimeException('no user'));
        $this->assertSame(0, ActivityLog::count());

        NotificationLogger::logFailure($this->student(), SavingsGoalAchieved::class, new \RuntimeException(str_repeat('x', 500)));
        $this->assertLessThan(300, strlen(ActivityLog::first()->details));
    }
}