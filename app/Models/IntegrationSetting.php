<?php


namespace App\Models;


use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;


class IntegrationSetting extends Model
{
    protected $fillable = [
        'groq_api_key',
        'groq_vision_model',
        'groq_text_model',
        'groq_temperature',
        'groq_max_tokens',
    ];


    protected $casts = [
        'groq_temperature' => 'float',
        'groq_max_tokens'  => 'integer',
    ];


    /**
     * Encrypt on write.
     */
    public function setGroqApiKeyAttribute($value): void
    {
        $this->attributes['groq_api_key'] = filled($value) ? Crypt::encryptString($value) : null;
    }


    /**
     * Decrypt on read. An undecryptable value (e.g. APP_KEY changed after a
     * clone/migration) is treated as "no key saved" instead of crashing the page.
     */
    public function getGroqApiKeyAttribute($value): ?string
    {
        if (blank($value)) {
            return null;
        }


        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return null;
        }
    }


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
