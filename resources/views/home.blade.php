<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Jogos de futebol com transmissão na TV e no streaming em {{ $selectedDate->translatedFormat('d \d\e F \d\e Y') }}.">
    <link rel="canonical" href="{{ $isToday ? route('home') : route('fixtures.by-date', ['date' => $selectedDate->format('Y-m-d')]) }}">
    <title>{{ $isToday ? 'Futebol na TV hoje' : 'Futebol na TV em '.$selectedDate->format('d/m/Y') }}: jogos e onde assistir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <header class="bg-emerald-950 text-white">
        <div class="mx-auto max-w-5xl px-4 py-6">
            <a href="{{ route('home') }}" class="text-2xl font-black tracking-tight">Futebol na TV</a>
            <p class="mt-1 text-sm text-emerald-100">Jogos televisionados e onde assistir no Brasil</p>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        <div class="mb-6">
            <p class="text-sm font-bold uppercase tracking-wide text-emerald-800">Programação por data</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight">
                {{ $isToday ? 'Jogos na TV hoje' : 'Jogos na TV em '.$selectedDate->translatedFormat('d \d\e F') }}
            </h1>
            <p class="mt-2 max-w-2xl text-slate-600">Somente partidas com transmissão informada pela Wosti para o Brasil.</p>
        </div>

        <nav class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_auto_1fr] sm:items-center" aria-label="Navegação por data">
            <a href="{{ route('fixtures.by-date', ['date' => $selectedDate->subDay()->format('Y-m-d')]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-center font-semibold hover:bg-slate-50 sm:text-left">
                ← Dia anterior
            </a>

            <form method="get" action="{{ route('fixtures.redirect-to-date') }}" class="flex items-center justify-center gap-2">
                <label for="data" class="sr-only">Escolher data</label>
                <input id="data" name="data" type="date" value="{{ $selectedDate->format('Y-m-d') }}" class="rounded-xl border border-slate-300 px-3 py-2 font-semibold">
                <button type="submit" class="rounded-xl bg-emerald-800 px-4 py-2 font-bold text-white hover:bg-emerald-700">Ver</button>
            </form>

            <a href="{{ route('fixtures.by-date', ['date' => $selectedDate->addDay()->format('Y-m-d')]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-center font-semibold hover:bg-slate-50 sm:text-right">
                Próximo dia →
            </a>
        </nav>

        @unless ($isToday)
            <div class="mb-5 text-center">
                <a href="{{ route('home') }}" class="font-bold text-emerald-800 hover:underline">Voltar aos jogos de hoje</a>
            </div>
        @endunless

        <section aria-labelledby="selected-date">
            <h2 id="selected-date" class="mb-3 text-lg font-bold capitalize">
                {{ $selectedDate->translatedFormat('l, d \d\e F \d\e Y') }}
            </h2>

            @if ($fixturesByCompetition->isNotEmpty())
                <div class="space-y-6">
                    @foreach ($fixturesByCompetition as $leagueFixtures)
                        @php($competition = $leagueFixtures->first()->competition)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="competition-{{ $competition->id }}">
                            <header class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                @if ($competition->logoSource())
                                    <img src="{{ $competition->logoSource() }}" alt="Logo {{ $competition->name }}" width="32" height="32" class="h-8 w-8 object-contain">
                                @endif
                                <h3 id="competition-{{ $competition->id }}" class="font-black">{{ $competition->name }}</h3>
                                <span class="ml-auto text-sm text-slate-500">{{ $leagueFixtures->count() }} {{ $leagueFixtures->count() === 1 ? 'jogo' : 'jogos' }}</span>
                            </header>

                            @foreach ($leagueFixtures as $fixture)
                                <article class="grid gap-4 border-b border-slate-100 p-4 last:border-b-0 sm:grid-cols-[5rem_1fr_14rem] sm:items-center">
                                    <time datetime="{{ $fixture->starts_at->toIso8601String() }}" class="text-xl font-black text-emerald-800">
                                        {{ $fixture->starts_at->format('H:i') }}
                                    </time>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 font-bold">
                                            @if ($fixture->homeTeam->logoSource())
                                                <img src="{{ $fixture->homeTeam->logoSource() }}" alt="Escudo {{ $fixture->homeTeam->name }}" width="28" height="28" class="h-7 w-7 object-contain" loading="lazy">
                                            @endif
                                            <span>{{ $fixture->homeTeam->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 font-bold">
                                            @if ($fixture->awayTeam->logoSource())
                                                <img src="{{ $fixture->awayTeam->logoSource() }}" alt="Escudo {{ $fixture->awayTeam->name }}" width="28" height="28" class="h-7 w-7 object-contain" loading="lazy">
                                            @endif
                                            <span>{{ $fixture->awayTeam->name }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        @foreach ($fixture->channels as $channel)
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-900">{{ $channel->name }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <h2 class="text-xl font-bold">Nenhum jogo na TV nesta data</h2>
                    <p class="mt-2 text-slate-600">A Wosti ainda não informou partidas televisionadas para este dia.</p>
                </div>
            @endif
        </section>

        <p class="mt-8 text-sm text-slate-500">Horários de Brasília. A programação pode ser alterada pelos canais sem aviso prévio.</p>
    </main>
</body>
</html>
