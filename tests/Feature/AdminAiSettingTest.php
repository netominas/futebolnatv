<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAiSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_settings_are_protected_by_admin_authentication(): void
    {
        $this->get(route('admin.ai-settings.edit'))
            ->assertRedirect(route('admin.login'));

        $this->put(route('admin.ai-settings.update'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_store_encrypted_keys_and_choose_the_active_provider(): void
    {
        $user = User::factory()->create();
        $openAiKey = 'sk-openai-secret-test';
        $googleKey = 'google-secret-test';

        $this->actingAs($user)->put(route('admin.ai-settings.update'), [
            'active_provider' => 'google',
            'auto_generate_news_images' => '1',
            'openai_api_key' => $openAiKey,
            'openai_model' => 'gpt-image-2.5-flare',
            'google_api_key' => $googleKey,
            'google_model' => 'gemini-3.1-flash-image',
        ])->assertRedirect()->assertSessionHas('status');

        $settings = AiSetting::firstOrFail();
        $this->assertSame('google', $settings->active_provider);
        $this->assertTrue($settings->auto_generate_news_images);
        $this->assertSame($openAiKey, $settings->openai_api_key);
        $this->assertSame($googleKey, $settings->google_api_key);
        $this->assertSame($googleKey, $settings->activeApiKey());
        $this->assertSame('gemini-3.1-flash-image', $settings->activeModel());

        $raw = DB::table('ai_settings')->first();
        $this->assertNotSame($openAiKey, $raw->openai_api_key);
        $this->assertNotSame($googleKey, $raw->google_api_key);
        $this->assertStringNotContainsString($openAiKey, $raw->openai_api_key);
        $this->assertStringNotContainsString($googleKey, $raw->google_api_key);

        $this->actingAs($user)->get(route('admin.ai-settings.edit'))
            ->assertOk()
            ->assertSee('Configurações de IA')
            ->assertSee('Chave configurada')
            ->assertDontSee($openAiKey)
            ->assertDontSee($googleKey);
    }

    public function test_blank_key_fields_keep_existing_credentials(): void
    {
        $user = User::factory()->create();
        $settings = AiSetting::create([
            'active_provider' => 'openai',
            'openai_api_key' => 'existing-openai-key',
            'openai_model' => 'gpt-image-2.5-flare',
            'google_api_key' => 'existing-google-key',
            'google_model' => 'gemini-3.1-flash-image',
        ]);

        $this->actingAs($user)->put(route('admin.ai-settings.update'), [
            'active_provider' => 'openai',
            'openai_api_key' => '',
            'openai_model' => 'gpt-image-2.5-sunburst',
            'google_api_key' => '',
            'google_model' => 'gemini-3-pro-image',
        ])->assertSessionHasNoErrors();

        $settings->refresh();
        $this->assertSame('existing-openai-key', $settings->openai_api_key);
        $this->assertSame('existing-google-key', $settings->google_api_key);
        $this->assertSame('gpt-image-2.5-sunburst', $settings->openai_model);
        $this->assertSame('gemini-3-pro-image', $settings->google_model);
    }

    public function test_secret_is_not_flashed_back_after_validation_error(): void
    {
        $user = User::factory()->create();
        $secret = 'must-never-be-flashed';

        $this->actingAs($user)->from(route('admin.ai-settings.edit'))->put(route('admin.ai-settings.update'), [
            'active_provider' => 'invalid-provider',
            'openai_api_key' => $secret,
            'openai_model' => 'gpt-image-2.5-flare',
            'google_model' => 'gemini-3.1-flash-image',
        ])->assertRedirect(route('admin.ai-settings.edit'));

        $this->assertNull(session()->getOldInput('openai_api_key'));
        $this->assertStringNotContainsString($secret, serialize(session()->all()));
    }
}
