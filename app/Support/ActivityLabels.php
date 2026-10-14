<?php

namespace App\Support;

use Illuminate\Support\Str;

class ActivityLabels
{
    private const LABELS = [
        'expense_logged'            => 'Logged Expense',
        'expense_scanned'           => 'Scanned Receipt',
        'expense_deleted'           => 'Deleted Expense',
        'expense_bulk_deleted'      => 'Bulk Deleted Expenses',
        'expense_edited'            => 'Edited Expense',
        'ai_forecast_requested'     => 'Requested AI Forecast',
        'ai_simulation_requested'   => 'Requested AI Simulation',
        'auth_login'                => 'Logged In',
        'auth_login_failed'         => 'Failed Login Attempt',
        'auth_logout'               => 'Logged Out',
        'user_registered'           => 'Registered Account',
        'user_edited'               => 'Edited User',
        'user_suspended'            => 'Suspended User',
        'user_reactivated'          => 'Reactivated User',
        'user_deleted'              => 'Deleted User',
        'admin_created'             => 'Created Admin',
        'admin_demoted'             => 'Revoked Admin Access',
        'admin_deleted'             => 'Deleted Admin',
        'category_created'          => 'Created Category',
        'category_edited'           => 'Edited Category',
        'category_disabled'         => 'Disabled Category',
        'category_enabled'          => 'Enabled Category',
        'category_deleted'          => 'Deleted Category',
        'risk_rules_updated'        => 'Updated Risk Rules',
        'ocr_ai_settings_updated'   => 'Updated OCR & AI Settings',
        'app_settings_updated'      => 'Updated Application Settings',
        'maintenance_mode_enabled'  => 'Enabled Maintenance Mode',
        'maintenance_mode_disabled' => 'Disabled Maintenance Mode',
        'password_changed'          => 'Changed Password',
        'profile_updated'           => 'Updated Profile',
        'password_reset_requested'  => 'Requested Password Reset',
        'password_reset_completed'  => 'Reset Password',
        'savings_goal_created'      => 'Created Savings Goal',
        'savings_goal_funded'       => 'Funded Savings Goal',
        'savings_goal_archived'     => 'Archived Savings Goal',
        'savings_goal_unarchived'   => 'Restored Savings Goal',
        'savings_goal_deleted'      => 'Deleted Savings Goal',
        'budget_funds_added'        => 'Added Budget Funds',
        'budget_settings_updated'   => 'Updated Budget Settings',
        'budget_setup_completed'    => 'Completed Budget Setup',
        'notification_failed'       => 'Notification Delivery Failed',
        'report_exported'           => 'Exported Report',
        'data_exported'             => 'Downloaded Personal Data',
        'account_deleted'           => 'Deleted Own Account',
        'ocr_upload'                => 'OCR Receipt Scan',
        'budget_exceeded'           => 'Budget Exceeded',
    ];

    public static function for(string $eventType): string
    {
        return self::LABELS[$eventType] ?? Str::headline($eventType);
    }
}