@php($total=$teams->count()+$competitions->count()+$channels->count())
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $canSearch ? 'Busca por '.$query : 'Buscar' }} | Futebol na TV</title>
    <meta name="description" content="Encontre times, campeonatos e canais com jogos de futebol transmitidos na TV e no streaming.">
    <meta name="robots" content="noindex,follow">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-950 antialiased">
@include('partials.site-header')
<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
    <nav class="mb-6 text-sm font-bold text-blue-700"><a href="{{ route('home') }}">Início</a> <span class="mx-2 text-slate-300">/</span> Busca</nav>
    <header class="mb-8">
        <p class="text-xs font-extrabold uppercase tracking-widest text-blue-700">Encontre sua transmissão</p>
        <h1 class="mt-2 text-3xl font-black tracking-[-.04em] sm:text-4xl">
            @if($canSearch)
                Resultados para “{{ $query }}”
            @else
                Buscar no Futebol na TV
            @endif
        </h1>
        @if($canSearch)
            <p class="mt-3 text-slate-600">{{ $total }} {{ $total === 1 ? 'resultado encontrado' : 'resultados encontrados' }}.</p>
        @else
            <p class="mt-3 text-slate-600">Digite pelo menos dois caracteres para pesquisar times, campeonatos e canais.</p>
        @endif
    </header>

    @if($canSearch && $total === 0)
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm"><h2 class="text-xl font-black">Nenhum resultado encontrado</h2><p class="mt-2 text-slate-600">Tente pesquisar por outro nome ou uma palavra mais curta.</p></div>
    @endif

    <div class="space-y-8">
        @foreach([['Times',$teams,'Escudo'],['Campeonatos',$competitions,'Logo'],['Canais',$channels,'Logo']] as [$heading,$items,$alt])
            @if($items->isNotEmpty())
                <section><h2 class="mb-4 text-2xl font-black">{{ $heading }}</h2><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($items as $item)<a href="{{ $item->publicUrl() }}" class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-slate-50">@if($item->logoSource())<img src="{{ $item->logoSource() }}" alt="{{ $alt }} {{ $item->name }}" loading="lazy">@endif</span><strong class="min-w-0 font-black text-slate-800 group-hover:text-blue-700">{{ $item->name }}</strong><span class="ml-auto text-blue-400">→</span></a>@endforeach</div></section>
            @endif
        @endforeach
    </div>
</main>
@include('partials.legal-footer')
</body>
</html>
