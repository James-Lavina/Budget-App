<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

/**
 * Single point of failure-handling for every ->notify() call in the app.
 * Previously each of the 8 call sites (RiskDetectionService x4,
 * BudgetCycleService, SavingsGoalService x2, GoalsManager) caught
 * \Throwable independently and only wrote to \Log::warning() — invisible
 * to an admin using the in-app Activity Logs page. This centralizes that
 * into one method so every notification failure produces both:
 *   1. A server log entry (for developers debugging via log files), and
 *   2. An ActivityLog row (for admins reviewing delivery health in-app).
 *
 * Never throws — a broken notification pipeline must never break the
 * action that triggered it (an expense still saves even if its risk
 * alert can't be delivered).
 */
class NotificationLogger
{
    /**
     * @param mixed          $user             The intended recipient. Skipped
     *                                         gracefully if null (defensive —
     *                                         every current call site has a
     *                                         real user, but this must never
     *                                         throw regardless).
     * @param string         $notificationClass Fully-qualified notification
     *                                         class name, e.g.
     *                                         LowAllowanceWarning::class.
     * @param \Throwable     $e                The caught exception.
     * @param string|null    $context          Optional short identifier —
     *                                         a category name, goal name,
     *                                         item name, or anomaly type —
     *                                         so an admin can tell which
     *                                         specific alert failed, not
     *                                         just which type.
     */
    public static function logFailure($user, string $notificationClass, \Throwable $e, ?string $context = null): void
    {
        $shortName = class_basename($notificationClass);
        $suffix    = $context ? " ({$context})" : '';

        \Log::warning("Notification failed: {$shortName}{$suffix} — " . $e->getMessage());

        if (!$user) {
            return;
        }

        ActivityLog::create([
            'user_id'    => $user->id,
            'event_type' => 'notification_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Failed to deliver \"{$shortName}\"{$suffix}: " . Str::limit($e->getMessage(), 150),
        ]);
    }
}