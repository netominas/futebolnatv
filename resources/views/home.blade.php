<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Confira os jogos de futebol com transmissão na TV e no streaming no Brasil.">
    <title>Futebol na TV hoje: jogos e onde assistir</title>
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
        <div class="mb-7">
            <h1 class="text-3xl font-black tracking-tight">Jogos de futebol na TV</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Programação dos próximos dias com canais de TV e serviços de streaming informados pela Wosti.</p>
        </div>

        @forelse ($fixturesByDate as $date => $fixtures)
            <section class="mb-8" aria-labelledby="date-{{ $date }}">
                <h2 id="date-{{ $date }}" class="mb-3 text-lg font-bold capitalize">
                    {{ $fixtures->first()->starts_at->translatedFormat('l, d \d\e F') }}
                </h2>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @foreach ($fixtures as $fixture)
                        <article class="grid gap-3 border-b border-slate-100 p-4 last:border-b-0 sm:grid-cols-[5rem_1fr_14rem] sm:items-center">
                            <time datetime="{{ $fixture->starts_at->toIso8601String() }}" class="text-xl font-black text-emerald-800">
                                {{ $fixture->starts_at->format('H:i') }}
                            </time>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $fixture->competition->name }}</p>
                                <h3 class="mt-1 font-bold">{{ $fixture->homeTeam->name }} <span class="font-normal text-slate-400">x</span> {{ $fixture->awayTeam->name }}</h3>
                            </div>
                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                @foreach ($fixture->channels as $channel)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-900">{{ $channel->name }}</span>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h2 class="text-xl font-bold">Programação em atualização</h2>
                <p class="mt-2 text-slate-600">Ainda não há jogos televisionados disponíveis para os próximos dias.</p>
            </div>
        @endforelse

        <p class="mt-8 text-sm text-slate-500">Horários de Brasília. A programação pode ser alterada pelos canais sem aviso prévio.</p>
    </main>
</body>
</html>
