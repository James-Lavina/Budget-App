<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RiskSetting extends Model
{
    protected $fillable = [
        'overspending_enabled', 'overspending_threshold',
        'daily_safe_to_spend_enabled', 'daily_safe_to_spend_threshold',
        'rapid_spending_enabled', 'rapid_spending_count',
        'no_expense_logs_enabled', 'no_expense_logs_days',
        'low_remaining_budget_enabled', 'low_remaining_budget_threshold',
    ];

    protected $casts = [
        'overspending_enabled'         => 'boolean',
        'daily_safe_to_spend_enabled'  => 'boolean',
        'rapid_spending_enabled'       => 'boolean',
        'no_expense_logs_enabled'      => 'boolean',
        'low_remaining_budget_enabled' => 'boolean',
    ];

    // Single global row — every RiskDetectionService call and the admin
    // form both read/write through this so there's exactly one place
    // thresholds live, instead of the ~4 hardcoded copies today.
    public static function current(): self
    {
        return Cache::rememberForever('risk_settings', fn () => self::firstOrCreate([]));
    }

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('risk_settings'));
    }
}