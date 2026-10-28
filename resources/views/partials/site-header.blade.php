<header class="brand-header text-white">
    <div class="relative z-10 mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:px-6 sm:py-5 lg:flex-row lg:items-center">
        <div class="flex w-full items-center justify-between lg:w-auto">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-2xl border border-white/20 bg-white/15"><img src="{{ asset('images/futebol-na-tv-logo.png') }}" alt="Futebol na TV" class="h-9 w-9 object-contain" width="36" height="36"></span>
                <span><strong class="block text-xl font-black">Futebol na TV</strong><small class="hidden text-blue-100 sm:block">Seu guia de transmissões ao vivo</small></span>
            </a>
            <div class="flex items-center gap-2 sm:hidden">
                @if(request()->routeIs('home', 'fixtures.tomorrow', 'fixtures.by-date'))
                    <button type="button" data-calendar-open aria-label="Abrir calendário de jogos" aria-controls="mobile-calendar" class="grid h-11 w-11 place-items-center rounded-xl border border-white/25 bg-white/15 text-white shadow-sm transition hover:bg-white/25">
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                @endif
                <div class="relative" data-mobile-menu-container>
                    <button type="button" data-mobile-menu-toggle aria-label="Abrir menu principal" aria-controls="mobile-site-menu" aria-expanded="false" class="grid h-11 w-11 place-items-center rounded-xl border border-white/25 bg-white/15 text-white shadow-sm transition hover:bg-white/25">
                        <svg data-mobile-menu-open-icon aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                        <svg data-mobile-menu-close-icon aria-hidden="true" viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                    <div id="mobile-site-menu" data-mobile-menu class="absolute right-0 top-[calc(100%+.75rem)] z-50 hidden w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl shadow-slate-950/25">
                        <div class="border-b border-slate-100 px-4 py-3"><p class="text-xs font-extrabold uppercase tracking-[.16em] text-blue-600">Navegação</p></div>
                        <nav class="grid gap-1 p-2 text-sm font-extrabold" aria-label="Menu principal mobile">
                            <a href="{{ route('home') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('home', 'fixtures.tomorrow', 'fixtures.by-date')]) @if(request()->routeIs('home', 'fixtures.tomorrow', 'fixtures.by-date')) aria-current="page" @endif><span>Jogos na TV</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                            <a href="{{ route('fixtures.live') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('fixtures.live')]) @if(request()->routeIs('fixtures.live')) aria-current="page" @endif><span class="inline-flex items-center gap-2"><i class="h-2 w-2 rounded-full bg-red-500 ring-2 ring-red-100"></i>Ao vivo</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                            <a href="{{ route('teams.index') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('teams.*')]) @if(request()->routeIs('teams.*')) aria-current="page" @endif><span>Times</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                            <a href="{{ route('channels.index') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('channels.*')]) @if(request()->routeIs('channels.*')) aria-current="page" @endif><span>Canais</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                            <a href="{{ route('competitions.index') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('competitions.*')]) @if(request()->routeIs('competitions.*')) aria-current="page" @endif><span>Campeonatos</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                            <a href="{{ route('news.index') }}" @class(['flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-blue-50 hover:text-blue-700', 'bg-blue-50 text-blue-700' => request()->routeIs('news.*')]) @if(request()->routeIs('news.*')) aria-current="page" @endif><span>Notícias</span><span aria-hidden="true" class="text-blue-400">→</span></a>
                        </nav>
                        <div class="border-t border-slate-100 p-2"><button type="button" data-pwa-install class="hidden w-full rounded-xl bg-blue-600 px-3 py-2.5 text-sm font-extrabold text-white hover:bg-blue-700">Instalar aplicativo</button></div>
                    </div>
                </div>
            </div>
        </div>
        <form action="{{ route('search') }}" method="GET" role="search" class="relative w-full lg:ml-auto lg:max-w-xs">
            <label for="site-search" class="sr-only">Buscar times, campeonatos e canais</label>
            <input id="site-search" name="q" type="search" value="{{ request('q') }}" minlength="2" maxlength="80" placeholder="Buscar time, campeonato ou canal" class="h-11 w-full rounded-xl border border-white/20 bg-white/95 px-4 pr-12 text-sm font-bold text-slate-900 outline-none placeholder:text-slate-400 focus:border-white focus:ring-2 focus:ring-white/30">
            <button type="submit" aria-label="Buscar" class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-xl text-blue-700 hover:bg-blue-50">⌕</button>
        </form>
        <nav class="hidden w-full items-center gap-1 overflow-x-auto text-xs font-extrabold sm:flex lg:w-auto lg:overflow-visible lg:text-sm">
            <a href="{{ route('home') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Jogos</a>
            <a href="{{ route('fixtures.live') }}" class="inline-flex items-center gap-1.5 rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3"><span class="h-2 w-2 rounded-full bg-red-400 ring-2 ring-red-300/30"></span>Ao vivo</a>
            <a href="{{ route('teams.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Times</a>
            <a href="{{ route('channels.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Canais</a>
            <a href="{{ route('competitions.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3"><span class="sm:hidden">Ligas</span><span class="hidden sm:inline">Campeonatos</span></a>
            <a href="{{ route('news.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Notícias</a>
            <button type="button" data-pwa-install class="hidden rounded-xl border border-white/25 bg-white/15 px-3 py-2 hover:bg-white/25">Instalar app</button>
        </nav>
    </div>
</header>
