<footer class="mt-12 border-t border-slate-200 bg-white/70">
    <div class="mx-auto grid max-w-6xl gap-7 px-4 py-9 sm:grid-cols-[1fr_auto] sm:px-6">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 font-black text-slate-800">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100"><img src="{{ asset('images/futebol-na-tv-logo.png') }}" alt="Futebol na TV" class="h-9 w-9 object-contain" width="36" height="36" loading="lazy"></span>
                <span>Futebol na TV</span>
            </a>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">Guia independente de jogos televisionados e plataformas de streaming no Brasil. Horários e transmissões podem mudar sem aviso prévio.</p>
            <button type="button" data-pwa-install class="mt-4 hidden rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700">Instalar Futebol na TV</button>
        </div>
        <nav class="grid grid-cols-2 gap-x-7 gap-y-2 text-sm font-bold text-slate-600"><a class="hover:text-blue-700" href="{{ route('pages.about') }}">Sobre</a><a class="hover:text-blue-700" href="{{ route('pages.contact') }}">Contato</a><a class="hover:text-blue-700" href="{{ route('pages.privacy') }}">Privacidade</a><a class="hover:text-blue-700" href="{{ route('pages.cookies') }}">Cookies</a><a class="hover:text-blue-700" href="{{ route('pages.terms') }}">Termos de Uso</a><a class="hover:text-blue-700" href="{{ route('pages.editorial') }}">Política Editorial</a></nav>
    </div>
    <div class="border-t border-slate-100 px-4 py-4 text-center text-xs text-slate-400">© {{ now()->year }} Futebol na TV. Todos os direitos reservados.</div>
</footer>

<button
    type="button"
    data-pwa-install
    data-pwa-floating
    aria-label="Instalar o aplicativo Futebol na TV"
    class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 z-50 hidden items-center gap-2.5 rounded-2xl border border-white/30 bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-[0_14px_40px_rgba(7,86,185,.38)] ring-4 ring-blue-100/80 transition hover:-translate-y-0.5 hover:bg-blue-700 active:translate-y-0 sm:hidden"
>
    <span class="grid h-8 w-8 place-items-center rounded-xl bg-white/15" aria-hidden="true">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3v11m0 0 4-4m-4 4-4-4"/>
            <path d="M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>
        </svg>
    </span>
    <span>Instalar app</span>
    <span class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white bg-emerald-400" aria-hidden="true"></span>
</button>
