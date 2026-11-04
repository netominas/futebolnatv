<header class="bg-slate-950 text-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <div><strong class="block text-lg font-black">Futebol na TV</strong><small class="text-slate-400">Administração</small></div>
        <nav class="flex flex-wrap items-center gap-1 text-sm font-bold" aria-label="Administração">
            @foreach([
                ['admin.channels.index', 'Canais', 'admin.channels.*'],
                ['admin.competitions.index', 'Campeonatos', 'admin.competitions.*'],
                ['admin.news.index', 'Notícias', 'admin.news.*'],
                ['admin.pwa-installations.index', 'Instalações PWA', 'admin.pwa-installations.*'],
                ['admin.push.index', 'Push', 'admin.push.*'],
                ['admin.ai-settings.edit', 'Configurações de IA', 'admin.ai-settings.*'],
            ] as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @class(['rounded-lg px-3 py-2 hover:bg-white/10', 'bg-white/10' => request()->routeIs($pattern)])>{{ $label }}</a>
            @endforeach
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="rounded-lg px-3 py-2 hover:bg-white/10">Sair</button></form>
        </nav>
    </div>
</header>
