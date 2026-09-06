<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'application_name',
        'primary_color',
        'logo_path',
        'email_notifications_enabled',
        'maintenance_mode_enabled',
    ];

    protected $casts = [
        'email_notifications_enabled' => 'boolean',
        'maintenance_mode_enabled'    => 'boolean',
    ];

    public static function current(): self
    {
        return Cache::rememberForever('app_settings', function () {
            return static::firstOrCreate(['id' => 1], [
                'application_name'            => 'BudgetWise',
                'primary_color'               => '#4f39fa',
                'email_notifications_enabled' => true,
                'maintenance_mode_enabled'    => false,
            ]);
        });
    }

    public static function flush(): void
    {
        Cache::forget('app_settings');
    }
}