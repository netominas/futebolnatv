<header class="brand-header text-white">
    <div class="relative z-10 mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:px-6 sm:py-5 lg:flex-row lg:items-center">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 self-start">
            <span class="grid h-11 w-11 place-items-center rounded-2xl border border-white/20 bg-white/15">@include('partials.ball-icon')</span>
            <span><strong class="block text-xl font-black">Futebol na TV</strong><small class="hidden text-blue-100 sm:block">Seu guia de transmissões ao vivo</small></span>
        </a>
        <form action="{{ route('search') }}" method="GET" role="search" class="relative w-full lg:ml-auto lg:max-w-xs">
            <label for="site-search" class="sr-only">Buscar times, campeonatos e canais</label>
            <input id="site-search" name="q" type="search" value="{{ request('q') }}" minlength="2" maxlength="80" placeholder="Buscar time, campeonato ou canal" class="h-11 w-full rounded-xl border border-white/20 bg-white/95 px-4 pr-12 text-sm font-bold text-slate-900 outline-none placeholder:text-slate-400 focus:border-white focus:ring-2 focus:ring-white/30">
            <button type="submit" aria-label="Buscar" class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-xl text-blue-700 hover:bg-blue-50">⌕</button>
        </form>
        <nav class="flex w-full items-center justify-between text-xs font-extrabold lg:w-auto lg:justify-start lg:gap-1 lg:text-sm">
            <a href="{{ route('home') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Jogos</a>
            <a href="{{ route('teams.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Times</a>
            <a href="{{ route('channels.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Canais</a>
            <a href="{{ route('competitions.index') }}" class="rounded-xl px-2 py-2 hover:bg-white/10 sm:px-3">Campeonatos</a>
        </nav>
    </div>
</header>
