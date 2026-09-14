<footer class="mt-12 border-t border-slate-200 bg-white/70">
    <div class="mx-auto grid max-w-6xl gap-7 px-4 py-9 sm:grid-cols-[1fr_auto] sm:px-6">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 font-black text-slate-800">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100"><img src="{{ asset('images/futebol-na-tv-logo.png') }}" alt="Futebol na TV" class="h-9 w-9 object-contain" width="36" height="36" loading="lazy"></span>
                <span>Futebol na TV</span>
            </a>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">Guia independente de jogos televisionados e plataformas de streaming no Brasil. Horários e transmissões podem mudar sem aviso prévio.</p>
        </div>
        <nav class="grid grid-cols-2 gap-x-7 gap-y-2 text-sm font-bold text-slate-600"><a class="hover:text-blue-700" href="{{ route('pages.about') }}">Sobre</a><a class="hover:text-blue-700" href="{{ route('pages.contact') }}">Contato</a><a class="hover:text-blue-700" href="{{ route('pages.privacy') }}">Privacidade</a><a class="hover:text-blue-700" href="{{ route('pages.cookies') }}">Cookies</a><a class="hover:text-blue-700" href="{{ route('pages.terms') }}">Termos de Uso</a><a class="hover:text-blue-700" href="{{ route('pages.editorial') }}">Política Editorial</a></nav>
    </div>
    <div class="border-t border-slate-100 px-4 py-4 text-center text-xs text-slate-400">© {{ now()->year }} Futebol na TV. Todos os direitos reservados.</div>
</footer>
