<?php

namespace App\Http\Livewire\Admin;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use App\Models\ActivityLog;

class OcrAiSettings extends Component
{
    public $groq_api_key;
    public $groq_vision_model;
    public $groq_text_model;
    public $groq_temperature;
    public $groq_max_tokens;

    public $testStatus = null; // 'success' | 'error' | null
    public $testMessage = '';

    protected $rules = [
        'groq_api_key'      => 'nullable|string',
        'groq_vision_model' => 'required|string|max:255',
        'groq_text_model'   => 'required|string|max:255',
        'groq_temperature'  => 'required|numeric|min:0|max:2',
        'groq_max_tokens'   => 'required|integer|min:50|max:4000',
    ];

    public function mount()
    {
        $s = IntegrationSetting::current();
        $this->groq_api_key      = $s->groq_api_key; // already decrypted by the cast
        $this->groq_vision_model = $s->groq_vision_model;
        $this->groq_text_model   = $s->groq_text_model;
        $this->groq_temperature  = $s->groq_temperature;
        $this->groq_max_tokens   = $s->groq_max_tokens;
    }

    public function save()
    {
        $this->validate();

        $s = IntegrationSetting::current();

        $changes = [];

        // Never log the key value itself — only whether it was changed/cleared/set.
        if ($this->groq_api_key !== $s->groq_api_key) {
            if (empty($s->groq_api_key) && !empty($this->groq_api_key)) {
                $changes[] = 'API key: set';
            } elseif (!empty($s->groq_api_key) && empty($this->groq_api_key)) {
                $changes[] = 'API key: cleared';
            } else {
                $changes[] = 'API key: changed';
            }
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

        $s->update($this->only([
            'groq_api_key', 'groq_vision_model', 'groq_text_model',
            'groq_temperature', 'groq_max_tokens',
        ]));

        IntegrationSetting::flush();

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'ocr_ai_settings_updated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => !empty($changes)
                ? 'Updated OCR & AI settings: ' . implode(', ', $changes)
                : 'Saved OCR & AI settings — no field changes detected',
        ]);

        $this->testStatus = null;
        session()->flash('success', 'AI settings saved successfully.');
    }

    public function testConnection()
    {
        $this->testStatus = null;
        $this->testMessage = '';

        if (empty($this->groq_api_key)) {
            $this->testStatus = 'error';
            $this->testMessage = 'Enter an API key first.';
            return;
        }

        try {
            $response = Http::withToken($this->groq_api_key)
                ->timeout(10)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->groq_text_model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with the single word: OK'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                $this->testStatus = 'success';
                $this->testMessage = 'Connected — ' . $this->groq_text_model . ' responded successfully.';
            } else {
                $this->testStatus = 'error';
                $this->testMessage = 'Groq returned HTTP ' . $response->status() . '. Check your API key or model name.';
            }
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Connection failed: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.ocr-ai-settings')->layout('layouts.admin');
    }
}