<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\OcrAiSettings;
use App\Models\ActivityLog;
use App\Models\IntegrationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class OcrAiSettingsTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->makeAdmin());
    }

    private function storeKey(string $key = 'gsk_stored'): void
    {
        IntegrationSetting::create(['groq_api_key' => $key]);
        IntegrationSetting::flush();
    }

    private function groqOk(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]], 200)]);
    }

    public function test_the_form_loads_the_current_models_and_limits_but_never_the_key()
    {
        $this->storeKey();

        Livewire::test(OcrAiSettings::class)
            ->assertSet('groq_vision_model', 'qwen/qwen3.6-27b')
            ->assertSet('groq_text_model', 'openai/gpt-oss-120b')
            ->assertSet('groq_temperature', 0.5)
            ->assertSet('groq_max_tokens', 400)
            ->assertSet('groq_api_key', null)
            ->assertViewHas('hasKey', true);
    }

    public function test_validation()
    {
        $cases = [
            ['groq_temperature', 3],
            ['groq_temperature', -1],
            ['groq_max_tokens', 10],
            ['groq_max_tokens', 5000],
            ['groq_vision_model', ''],
            ['groq_text_model', ''],
        ];

        foreach ($cases as [$field, $value]) {
            Livewire::test(OcrAiSettings::class)
                ->set($field, $value)
                ->call('save')
                ->assertHasErrors([$field]);
        }
    }

    public function test_a_new_key_is_stored_encrypted_and_models_changes_are_audited()
    {
        Livewire::test(OcrAiSettings::class)
            ->set('groq_api_key', 'gsk_brand_new')
            ->set('groq_text_model', 'llama-test-model')
            ->set('groq_max_tokens', 800)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('groq_api_key', null);

        $raw = DB::table('integration_settings')->value('groq_api_key');
        $this->assertStringNotContainsString('gsk_brand_new', $raw);
        $this->assertSame('gsk_brand_new', IntegrationSetting::current()->groq_api_key);

        $log = ActivityLog::where('event_type', 'ocr_ai_settings_updated')->first();
        $this->assertStringContainsString('API key: set', $log->details);
        $this->assertStringContainsString('Text Model', $log->details);
        $this->assertStringContainsString('Max Tokens: 400 → 800', $log->details);
        $this->assertStringNotContainsString('gsk_', $log->details);
    }

    public function test_saving_with_no_changes_writes_no_audit_entry()
    {
        Livewire::test(OcrAiSettings::class)->call('save')->assertHasNoErrors();

        $this->assertSame(0, ActivityLog::where('event_type', 'ocr_ai_settings_updated')->count());
    }

    public function test_the_saved_key_can_be_removed()
    {
        $this->storeKey();

        Livewire::test(OcrAiSettings::class)
            ->assertViewHas('hasKey', true)
            ->call('clearApiKey')
            ->assertViewHas('hasKey', false);

        $this->assertNull(IntegrationSetting::current()->groq_api_key);
        $log = ActivityLog::where('event_type', 'ocr_ai_settings_updated')->first();
        $this->assertStringContainsString('API key: cleared', $log->details);
    }

    public function test_connection_tests_need_a_key()
    {
        Livewire::test(OcrAiSettings::class)
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'error')
            ->assertSet('visionTestMessage', 'No API key saved or entered.')
            ->call('testTextConnection')
            ->assertSet('textTestStatus', 'error')
            ->assertSet('textTestMessage', 'No API key saved or entered.');
    }

    public function test_the_vision_test_uses_the_stored_key_and_vision_model()
    {
        $this->storeKey('gsk_stored');
        $this->groqOk();

        Livewire::test(OcrAiSettings::class)
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'success');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer gsk_stored')
            && $request['model'] === 'qwen/qwen3.6-27b');
    }

    public function test_the_text_test_uses_the_text_model_and_a_typed_key_wins_over_the_stored_one()
    {
        $this->storeKey('gsk_stored');
        $this->groqOk();

        Livewire::test(OcrAiSettings::class)
            ->set('groq_api_key', 'gsk_typed')
            ->call('testTextConnection')
            ->assertSet('textTestStatus', 'success');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer gsk_typed')
            && $request['model'] === 'openai/gpt-oss-120b');
    }

    public function test_api_errors_and_network_failures_are_reported()
    {
        $this->storeKey();

        Http::fake(['api.groq.com/*' => Http::response([], 401)]);
        Livewire::test(OcrAiSettings::class)
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'error')
            ->call('testTextConnection')
            ->assertSet('textTestStatus', 'error');

        Http::fake(function () {
            throw new ConnectionException('timed out');
        });
        Livewire::test(OcrAiSettings::class)
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'error');
    }

    public function test_a_blank_model_name_is_reported_without_calling_the_api()
    {
        $this->storeKey();
        Http::fake();

        Livewire::test(OcrAiSettings::class)
            ->set('groq_vision_model', '')
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'error')
            ->assertSet('visionTestMessage', 'No model name entered.');

        Http::assertNothingSent();
    }

    public function test_saving_resets_any_previous_test_results()
    {
        $this->storeKey();
        $this->groqOk();

        Livewire::test(OcrAiSettings::class)
            ->call('testVisionConnection')
            ->assertSet('visionTestStatus', 'success')
            ->call('save')
            ->assertSet('visionTestStatus', null)
            ->assertSet('textTestStatus', null);
    }
}