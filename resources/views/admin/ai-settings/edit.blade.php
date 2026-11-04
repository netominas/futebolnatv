<!DOCTYPE html>
<html lang="pt-BR">
<head>@include('partials.google-tag-head')<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Configurações de IA | Administração</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-950">@include('partials.google-tag-body')
@include('admin.partials.header')
<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <div class="mb-7"><p class="text-xs font-extrabold uppercase tracking-widest text-blue-700">Integrações</p><h1 class="mt-1 text-3xl font-black">Configurações de IA</h1><p class="mt-2 max-w-3xl text-slate-500">Cadastre as credenciais usadas para gerar imagens editoriais e escolha qual provedor ficará ativo.</p></div>

    @if(session('status'))<div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('admin.ai-settings.update') }}" class="space-y-6">@csrf @method('PUT')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-extrabold uppercase tracking-widest text-blue-700">Provedor ativo</p><h2 class="mt-1 text-xl font-black">Qual IA deve gerar as imagens?</h2><p class="mt-2 text-sm leading-6 text-slate-500">Somente o provedor selecionado será usado nas próximas gerações.</p></div><span class="w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide text-emerald-700">{{ $settings->active_provider === 'google' ? 'Google Gemini ativo' : 'OpenAI ativa' }}</span></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50"><input type="radio" name="active_provider" value="openai" class="mt-1" @checked(old('active_provider', $settings->active_provider) === 'openai')><span><strong class="block">OpenAI</strong><small class="mt-1 block text-slate-500">GPT Image para capas editoriais.</small></span></label>
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50"><input type="radio" name="active_provider" value="google" class="mt-1" @checked(old('active_provider', $settings->active_provider) === 'google')><span><strong class="block">Google Gemini</strong><small class="mt-1 block text-slate-500">Nano Banana para geração de imagens.</small></span></label>
            </div>
            <label class="mt-5 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4"><input type="checkbox" name="auto_generate_news_images" value="1" class="mt-1" @checked(old('auto_generate_news_images', $settings->auto_generate_news_images))><span><strong class="block text-sm">Gerar a imagem automaticamente ao criar uma notícia</strong><small class="mt-1 block leading-5 text-slate-500">A notícia será salva como rascunho e a geração acontecerá na fila, sem atrasar o painel.</small></span></label>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach([
                ['openai', 'OpenAI', $settings->openai_api_key, $settings->openai_model, $openAiModels],
                ['google', 'Google Gemini', $settings->google_api_key, $settings->google_model, $googleModels],
            ] as [$provider, $label, $configuredKey, $currentModel, $models])
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-black">{{ $label }}</h2><span @class(['rounded-full px-3 py-1 text-xs font-extrabold', 'bg-emerald-50 text-emerald-700' => filled($configuredKey), 'bg-slate-100 text-slate-500' => blank($configuredKey)])>{{ filled($configuredKey) ? 'Chave configurada' : 'Sem chave' }}</span></div>
                    <div class="mt-5"><label for="{{ $provider }}-api-key" class="mb-1.5 block text-sm font-bold">{{ filled($configuredKey) ? 'Substituir chave da API' : 'Chave da API' }}</label><input id="{{ $provider }}-api-key" name="{{ $provider }}_api_key" type="password" value="" maxlength="512" autocomplete="new-password" spellcheck="false" class="w-full rounded-xl border border-slate-300 px-4 py-3 font-mono text-sm outline-none focus:border-blue-500" placeholder="{{ filled($configuredKey) ? 'Deixe vazio para manter a chave atual' : 'Cole a chave aqui' }}"><p class="mt-1.5 text-xs leading-5 text-slate-400">O valor é criptografado no servidor e nunca volta a ser exibido.</p></div>
                    <div class="mt-4"><label for="{{ $provider }}-model" class="mb-1.5 block text-sm font-bold">Modelo de imagem</label><select id="{{ $provider }}-model" name="{{ $provider }}_model" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500">@foreach($models as $value => $modelLabel)<option value="{{ $value }}" @selected(old($provider.'_model', $currentModel) === $value)>{{ $modelLabel }}</option>@endforeach</select></div>
                    @if(filled($configuredKey))<label class="mt-5 flex items-center gap-2 text-sm font-bold text-red-700"><input type="checkbox" name="remove_{{ $provider }}_api_key" value="1"> Remover chave configurada</label>@endif
                </section>
            @endforeach
        </div>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950"><strong class="block">Segurança das credenciais</strong>As chaves ficam criptografadas com a chave da aplicação. Elas não são incluídas no Git, no código-fonte, no HTML do painel ou em mensagens de erro.</section>
        <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-extrabold text-white shadow-md shadow-blue-600/20 hover:bg-blue-700">Salvar configurações</button></div>
    </form>
</main>
</body></html>
