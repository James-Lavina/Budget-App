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

    /**
     * "79, 57, 250" — feeds rgba(var(--brand-rgb), 0.x) in Blade so
     * translucent tints/shadows work without a second stored color.
     */
    public function primaryColorRgb(): string
    {
        return self::hexToRgbString($this->primary_color ?: '#4f39fa');
    }

    /**
     * A ~18% darker shade of the brand color, used for hover states
     * (the Tailwind indigo-600 -> indigo-700 pattern, generalized).
     */
    public function primaryColorDark(): string
    {
        return self::shadeHex($this->primary_color ?: '#4f39fa', -18);
    }

    private static function hexToRgbString(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
        return "{$r}, {$g}, {$b}";
    }

    private static function shadeHex(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $rgb = array_map(function ($c) use ($percent) {
            $c = (int) round($c + ($c * $percent / 100));
            return max(0, min(255, $c));
        }, array_map('hexdec', str_split($hex, 2)));

        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}