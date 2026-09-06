<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'groq_api_key',
        'groq_vision_model',
        'groq_text_model',
        'groq_temperature',
        'groq_max_tokens',
    ];

    // Laravel encrypts/decrypts this automatically on save/read.
    protected $casts = [
        'groq_api_key'      => 'encrypted',
        'groq_temperature'  => 'float',
        'groq_max_tokens'   => 'integer',
    ];

    /**
     * Singleton accessor — this app only ever has one settings row.
     * Cached so every Groq API call doesn't hit the DB + decrypt on every request.
     */
    public static function current(): self
    {
        return Cache::rememberForever('integration_settings', function () {
            return self::first() ?? self::create([
                'groq_vision_model' => 'qwen/qwen3.6-27b',
                'groq_text_model'   => 'openai/gpt-oss-120b',
                'groq_temperature'  => 0.50,
                'groq_max_tokens'   => 400,
            ]);
        });
    }

    public static function flush(): void
    {
        Cache::forget('integration_settings');
    }
}