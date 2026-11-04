@if($article->hero_image_generation_status)
    @php([$generationLabel, $generationClass] = match($article->hero_image_generation_status) {
        'queued' => ['Imagem na fila de geração', 'bg-amber-50 text-amber-800 border-amber-200'],
        'processing' => ['Imagem sendo gerada pela IA', 'bg-blue-50 text-blue-800 border-blue-200'],
        'completed' => ['Imagem gerada por IA — revise antes de publicar', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        'failed' => ['Não foi possível gerar a imagem automaticamente', 'bg-red-50 text-red-800 border-red-200'],
        default => ['Status da imagem atualizado', 'bg-slate-50 text-slate-700 border-slate-200'],
    })
    <div class="mt-4 rounded-xl border px-4 py-3 text-sm font-bold {{ $generationClass }}">
        {{ $generationLabel }}
        @if($article->hero_image_generation_status === 'failed' && $article->hero_image_generation_error)<p class="mt-1 font-normal">{{ $article->hero_image_generation_error }}</p>@endif
    </div>
@endif
