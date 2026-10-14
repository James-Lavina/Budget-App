<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class OcrAiSettings extends Component
{
    use EnsuresAdmin;

    // Write-only. Public properties are serialized into the page and every Livewire
    // round trip, so the stored key must never be loaded into this one.
    public $groq_api_key;

    public $groq_vision_model;
    public $groq_text_model;
    public $groq_temperature;
    public $groq_max_tokens;

    public $visionTestStatus = null; // 'success' | 'error' | null
    public $visionTestMessage = '';

    public $textTestStatus = null;   // 'success' | 'error' | null
    public $textTestMessage = '';

    protected $rules = [
        'groq_api_key'      => 'nullable|string|max:500',
        'groq_vision_model' => 'required|string|max:255',
        'groq_text_model'   => 'required|string|max:255',
        'groq_temperature'  => 'required|numeric|min:0|max:2',
        'groq_max_tokens'   => 'required|integer|min:50|max:4000',
    ];

    public function mount()
    {
        $s = IntegrationSetting::current();

        $this->groq_vision_model = $s->groq_vision_model;
        $this->groq_text_model   = $s->groq_text_model;
        $this->groq_temperature  = $s->groq_temperature;
        $this->groq_max_tokens   = $s->groq_max_tokens;
    }

    public function save()
    {
        $this->validate();

        $s       = IntegrationSetting::current();
        $changes = [];
        $data    = [
            'groq_vision_model' => $this->groq_vision_model,
            'groq_text_model'   => $this->groq_text_model,
            'groq_temperature'  => $this->groq_temperature,
            'groq_max_tokens'   => $this->groq_max_tokens,
        ];

        // A blank field means "keep the current key". Only a typed value replaces it.
        $newKey = trim((string) $this->groq_api_key);

        if ($newKey !== '') {
            $changes[]            = empty($s->groq_api_key) ? 'API key: set' : 'API key: changed';
            $data['groq_api_key'] = $newKey;
        }

        if ($s->groq_vision_model !== $this->groq_vision_model) {
            $changes[] = "Vision Model: \"{$s->groq_vision_model}\" → \"{$this->groq_vision_model}\"";
        }

        if ($s->groq_text_model !== $this->groq_text_model) {
            $changes[] = "Text Model: \"{$s->groq_text_model}\" → \"{$this->groq_text_model}\"";
        }

        if ((float) $s->groq_temperature !== (float) $this->groq_temperature) {
            $changes[] = "Temperature: {$s->groq_temperature} → {$this->groq_temperature}";
        }

        if ((int) $s->groq_max_tokens !== (int) $this->groq_max_tokens) {
            $changes[] = "Max Tokens: {$s->groq_max_tokens} → {$this->groq_max_tokens}";
        }

        $s->update($data);
        IntegrationSetting::flush();

        if (!empty($changes)) {
            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'ocr_ai_settings_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Updated OCR & AI settings: ' . implode(', ', $changes),
            ]);
        }

        $this->groq_api_key     = null;
        $this->visionTestStatus = null;
        $this->textTestStatus   = null;

        session()->flash('success', 'AI settings saved successfully.');
    }

    public function clearApiKey()
    {
        $s = IntegrationSetting::current();

        if (empty($s->groq_api_key)) {
            return;
        }

        $s->update(['groq_api_key' => null]);
        IntegrationSetting::flush();

        $this->groq_api_key = null;

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'ocr_ai_settings_updated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => 'Updated OCR & AI settings: API key: cleared',
        ]);

        session()->flash('success', 'Saved API key removed.');
    }

    // The key typed in the field wins; otherwise the stored one is used for tests.
    private function activeKey(): ?string
    {
        $typed = trim((string) $this->groq_api_key);

        if ($typed !== '') {
            return $typed;
        }

        return IntegrationSetting::current()->groq_api_key ?: null;
    }

    public function testVisionConnection()
    {
        $this->visionTestStatus  = null;
        $this->visionTestMessage = '';

        $key = $this->activeKey();

        if (empty($key)) {
            $this->visionTestStatus  = 'error';
            $this->visionTestMessage = 'No API key saved or entered.';
            return;
        }

        $result = $this->pingModel($this->groq_vision_model, $key);

        $this->visionTestStatus  = $result['ok'] ? 'success' : 'error';
        $this->visionTestMessage = $result['ok']
            ? 'Connected — ' . $this->groq_vision_model . ' responded successfully.'
            : $result['message'];
    }

    public function testTextConnection()
    {
        $this->textTestStatus  = null;
        $this->textTestMessage = '';

        $key = $this->activeKey();

        if (empty($key)) {
            $this->textTestStatus  = 'error';
            $this->textTestMessage = 'No API key saved or entered.';
            return;
        }

        $result = $this->pingModel($this->groq_text_model, $key);

        $this->textTestStatus  = $result['ok'] ? 'success' : 'error';
        $this->textTestMessage = $result['ok']
            ? 'Connected — ' . $this->groq_text_model . ' responded successfully.'
            : $result['message'];
    }

    private function pingModel(?string $model, string $key): array
    {
        if (empty($model)) {
            return ['ok' => false, 'message' => 'No model name entered.'];
        }

        try {
            $response = Http::withToken($key)
                ->timeout(10)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with the single word: OK'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'OK'];
            }

            return [
                'ok'      => false,
                'message' => 'Groq returned HTTP ' . $response->status() . '. Check the model name.',
            ];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    public function render()
    {
        return view('livewire.admin.ocr-ai-settings', [
            'hasKey' => !empty(IntegrationSetting::current()->groq_api_key),
        ])->layout('layouts.admin');
    }
}