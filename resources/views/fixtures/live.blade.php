<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('partials.google-tag-head')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0756b9">
    <title>Jogos ao vivo agora na TV: horários e onde assistir</title>
    <meta name="description" content="Veja quais jogos de futebol estão ao vivo agora, de acordo com o horário programado, e confira onde assistir na TV e no streaming no Brasil.">
    <link rel="canonical" href="{{ route('fixtures.live') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Jogos ao vivo agora na TV">
    <meta property="og:description" content="Confira as partidas em andamento e os canais de transmissão informados para o Brasil.">
    <meta property="og:url" content="{{ route('fixtures.live') }}">
    <meta name="twitter:title" content="Jogos ao vivo agora na TV">
    <meta name="twitter:description" content="Confira as partidas em andamento e onde assistir futebol ao vivo.">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Jogos ao vivo agora na TV',
        'description' => 'Partidas de futebol consideradas em andamento de acordo com o horário programado.',
        'url' => route('fixtures.live'),
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'Futebol na TV', 'url' => route('home')],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @include('partials.social-image-meta')
    @include('partials.pwa-head')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-950 antialiased">
@include('partials.google-tag-body')
@include('partials.site-header')
<main>
    <section class="hero-panel border-b border-blue-100">
        <div class="mx-auto max-w-6xl px-4 pb-8 pt-7 sm:px-6 sm:pb-14 sm:pt-11">
            <nav class="mb-5 text-sm font-bold text-blue-700"><a href="{{ route('home') }}">Início</a> <span class="mx-2 text-slate-300">/</span> Jogos ao vivo</nav>
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-red-200 bg-white/90 px-3 py-1.5 text-xs font-extrabold uppercase tracking-widest text-red-700"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Programação em andamento</p>
                <h1 class="text-3xl font-black tracking-[-.045em] sm:text-5xl">Jogos ao vivo agora na TV</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">Veja as partidas que estão dentro da janela prevista de transmissão e confira em quais canais assistir.</p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 pb-14 pt-7 sm:px-6 sm:pt-9">
        <div class="mb-7 flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-xs font-extrabold uppercase tracking-widest text-blue-700">Atualizado às {{ $now->format('H:i') }}</p><p class="mt-1 text-sm leading-6 text-slate-600">Uma partida é considerada ao vivo desde o início programado até {{ $liveWindowMinutes / 60 }} horas depois.</p></div>
            <a href="{{ route('home') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-extrabold text-blue-700 shadow-sm ring-1 ring-blue-200 hover:bg-blue-100">Ver agenda completa</a>
        </div>

        @if($fixturesByCompetition->isNotEmpty())
            <div class="mb-5 flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Em andamento</p><h2 class="mt-1 text-xl font-black">{{ $fixtures->count() }} {{ $fixtures->count() === 1 ? 'partida ao vivo' : 'partidas ao vivo' }}</h2></div><span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-extrabold text-red-700"><span class="h-2 w-2 rounded-full bg-red-500"></span>AO VIVO</span></div>
            <div class="space-y-5">
            @foreach($fixturesByCompetition as $leagueFixtures)
                @php($competition = $leagueFixtures->first()->competition)
                <section class="league-card overflow-hidden rounded-2xl border border-slate-200/80 bg-white" aria-labelledby="live-competition-{{ $competition->id }}">
                    <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3.5 sm:px-5">
                        <a href="{{ $competition->publicUrl() }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white shadow-sm">@if($competition->logoSource())<img src="{{ $competition->logoSource() }}" alt="Logo {{ $competition->name }}" class="h-7 w-7 object-contain">@endif</a>
                        <h3 id="live-competition-{{ $competition->id }}" class="min-w-0 truncate font-black"><a href="{{ $competition->publicUrl() }}" class="hover:text-blue-700 hover:underline">{{ $competition->name }}</a></h3>
                        <span class="ml-auto shrink-0 rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold text-red-700">{{ $leagueFixtures->count() }} ao vivo</span>
                    </header>
                    <div class="divide-y divide-slate-100">
                    @foreach($leagueFixtures as $fixture)
                        <article class="fixture-row relative grid gap-4 px-4 py-4 sm:grid-cols-[7rem_minmax(0,1fr)_minmax(12rem,auto)] sm:items-center sm:px-5">
                            <a href="{{ $fixture->publicUrl() }}" class="absolute inset-0 z-10" aria-label="Ver detalhes de {{ $fixture->homeTeam->name }} x {{ $fixture->awayTeam->name }}"></a>
                            <div class="sm:border-r sm:border-slate-100 sm:py-2"><span class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-red-700"><i class="h-2.5 w-2.5 rounded-full bg-red-500"></i>Ao vivo</span><time datetime="{{ $fixture->starts_at->toIso8601String() }}" class="mt-1 block text-sm font-bold text-slate-500">Desde {{ $fixture->starts_at->format('H:i') }}</time></div>
                            <div class="space-y-2.5">@foreach([$fixture->homeTeam, $fixture->awayTeam] as $team)<div class="flex min-w-0 items-center gap-3 font-bold text-slate-800"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-50">@if($team->logoSource())<img src="{{ $team->logoSource() }}" alt="Escudo {{ $team->name }}" class="h-7 w-7 object-contain" loading="lazy">@endif</span><span class="truncate">{{ $team->name }}</span></div>@endforeach</div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">@foreach($fixture->channels as $channel)<span class="channel-pill inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-extrabold text-blue-800"><span>▻</span>{{ $channel->name }}</span>@endforeach<span class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white shadow-sm shadow-blue-600/20">Ver partida <span class="ml-1">→</span></span></div>
                        </article>
                    @endforeach
                    </div>
                </section>
            @endforeach
            </div>
        @else
            <section class="rounded-3xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-blue-50 text-blue-600">@include('partials.ball-icon')</span>
                <h2 class="mt-5 text-2xl font-black">Nenhum jogo ao vivo agora</h2>
                <p class="mx-auto mt-3 max-w-xl leading-7 text-slate-600">Neste momento, nenhuma partida com transmissão informada está dentro da janela prevista de duas horas.</p>
                <a href="{{ route('home') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-md shadow-blue-600/20 hover:bg-blue-700">Ver jogos de hoje <span class="ml-2">→</span></a>
            </section>
        @endif

        <section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="como-calculamos-ao-vivo">
            <h2 id="como-calculamos-ao-vivo" class="text-2xl font-black tracking-tight">Como identificamos os jogos ao vivo?</h2>
            <p class="mt-3 max-w-3xl leading-7 text-slate-600">A indicação é calculada pelo horário oficial da programação: cada partida permanece nesta página por duas horas a partir do início previsto. O status não representa placar, minuto real ou confirmação de que o jogo não sofreu atraso.</p>
            <p class="mt-3 max-w-3xl leading-7 text-slate-600">Consulte a página da partida para ver os canais e plataformas de transmissão informados para o Brasil. A programação pode sofrer alterações pelas emissoras e organizadores.</p>
        </section>
    </div>
</main>
@include('partials.legal-footer')
</body>
</html>
