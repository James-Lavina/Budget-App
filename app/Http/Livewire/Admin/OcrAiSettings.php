<?php

namespace App\Http\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class OcrAiSettings extends Component
{
    public $groq_api_key;
    public $groq_vision_model;
    public $groq_text_model;
    public $groq_temperature;
    public $groq_max_tokens;

    // CHANGED: split into two independent pairs — one per model — instead
    // of a single shared $testStatus/$testMessage. Each model now has its
    // own "Test" button and result, since a passing text-model test used
    // to mask a broken vision model (or vice versa).
    public $visionTestStatus = null; // 'success' | 'error' | null
    public $visionTestMessage = '';

    public $textTestStatus = null; // 'success' | 'error' | null
    public $textTestMessage = '';

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

        // Only write an ActivityLog row when something actually changed —
        // a no-op Save click produces no audit entry at all.
        if (!empty($changes)) {
            ActivityLog::create([
                'user_id'    => auth()->id(),
                'event_type' => 'ocr_ai_settings_updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Updated OCR & AI settings: ' . implode(', ', $changes),
            ]);
        }

        $this->visionTestStatus = null;
        $this->textTestStatus = null;
        session()->flash('success', 'AI settings saved successfully.');
    }

    /**
     * Tests only the Vision Model (Receipt Scanner) — independent of the
     * Text Model test below. Uses whatever is currently typed into the
     * field, not necessarily the saved value, so an admin can validate a
     * model name before committing to Save.
     */
    public function testVisionConnection()
    {
        $this->visionTestStatus = null;
        $this->visionTestMessage = '';

        if (empty($this->groq_api_key)) {
            $this->visionTestStatus = 'error';
            $this->visionTestMessage = 'Enter an API key first.';
            return;
        }

        $result = $this->pingModel($this->groq_vision_model);

        $this->visionTestStatus = $result['ok'] ? 'success' : 'error';
        $this->visionTestMessage = $result['ok']
            ? 'Connected — ' . $this->groq_vision_model . ' responded successfully.'
            : $result['message'];
    }

    /**
     * Tests only the Text Model (AI Coach) — independent of the Vision
     * Model test above.
     */
    public function testTextConnection()
    {
        $this->textTestStatus = null;
        $this->textTestMessage = '';

        if (empty($this->groq_api_key)) {
            $this->textTestStatus = 'error';
            $this->textTestMessage = 'Enter an API key first.';
            return;
        }

        $result = $this->pingModel($this->groq_text_model);

        $this->textTestStatus = $result['ok'] ? 'success' : 'error';
        $this->textTestMessage = $result['ok']
            ? 'Connected — ' . $this->groq_text_model . ' responded successfully.'
            : $result['message'];
    }

    /**
     * Sends a minimal text-only chat completion to the given model to
     * verify the API key + model name are valid and reachable. Shared by
     * both testVisionConnection() and testTextConnection() — Groq's vision
     * models are multimodal chat models and accept plain text-only
     * messages fine, so there's no need to upload a real image just to
     * test connectivity.
     */
    private function pingModel(string $model): array
    {
        if (empty($model)) {
            return ['ok' => false, 'message' => 'No model name entered.'];
        }

        try {
            $response = Http::withToken($this->groq_api_key)
                ->timeout(10)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with the single word: OK'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'OK'];
            }

            return [
                'ok' => false,
                'message' => 'Groq returned HTTP ' . $response->status() . '. Check the model name.',
            ];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    public function render()
    {
        return view('livewire.admin.ocr-ai-settings')->layout('layouts.admin');
    }
}