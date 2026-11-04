<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.ai-settings.edit', [
            'settings' => AiSetting::current(),
            'openAiModels' => [
                'gpt-image-2.5-flare' => 'GPT Image 2.5 Flare — rápido e equilibrado',
                'gpt-image-2.5-sunburst' => 'GPT Image 2.5 Sunburst — maior precisão',
            ],
            'googleModels' => [
                'gemini-3.1-flash-image' => 'Gemini 3.1 Flash Image — recomendado',
                'gemini-3.1-flash-lite-image' => 'Gemini 3.1 Flash Lite Image — econômico',
                'gemini-3-pro-image' => 'Gemini 3 Pro Image — qualidade profissional',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_provider' => ['required', Rule::in(['openai', 'google'])],
            'auto_generate_news_images' => ['nullable', 'boolean'],
            'openai_api_key' => ['nullable', 'string', 'max:512'],
            'openai_model' => ['required', Rule::in(['gpt-image-2.5-flare', 'gpt-image-2.5-sunburst'])],
            'google_api_key' => ['nullable', 'string', 'max:512'],
            'google_model' => ['required', Rule::in(['gemini-3.1-flash-image', 'gemini-3.1-flash-lite-image', 'gemini-3-pro-image'])],
            'remove_openai_api_key' => ['nullable', 'boolean'],
            'remove_google_api_key' => ['nullable', 'boolean'],
        ]);

        $settings = AiSetting::current();
        $openAiKey = $request->boolean('remove_openai_api_key') ? null : $settings->openai_api_key;
        $googleKey = $request->boolean('remove_google_api_key') ? null : $settings->google_api_key;

        if (filled($validated['openai_api_key'] ?? null)) {
            $openAiKey = trim($validated['openai_api_key']);
        }
        if (filled($validated['google_api_key'] ?? null)) {
            $googleKey = trim($validated['google_api_key']);
        }

        if ($validated['active_provider'] === 'openai' && blank($openAiKey)) {
            throw ValidationException::withMessages([
                'openai_api_key' => 'Cadastre a chave da OpenAI antes de ativar esse provedor.',
            ]);
        }
        if ($validated['active_provider'] === 'google' && blank($googleKey)) {
            throw ValidationException::withMessages([
                'google_api_key' => 'Cadastre a chave do Google Gemini antes de ativar esse provedor.',
            ]);
        }

        $settings->update([
            'active_provider' => $validated['active_provider'],
            'auto_generate_news_images' => $request->boolean('auto_generate_news_images'),
            'openai_api_key' => $openAiKey,
            'openai_model' => $validated['openai_model'],
            'google_api_key' => $googleKey,
            'google_model' => $validated['google_model'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Configurações de IA atualizadas com segurança.');
    }
}
