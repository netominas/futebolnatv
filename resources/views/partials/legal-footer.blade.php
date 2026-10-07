<footer class="mt-12 border-t border-slate-200 bg-white/70">
    <div class="mx-auto grid max-w-6xl gap-7 px-4 py-9 sm:grid-cols-[1fr_auto] sm:px-6">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 font-black text-slate-800">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100"><img src="{{ asset('images/futebol-na-tv-logo.png') }}" alt="Futebol na TV" class="h-9 w-9 object-contain" width="36" height="36" loading="lazy"></span>
                <span>Futebol na TV</span>
            </a>
            <p class="mt-3 max-w-md text-sm leading-6 text-slate-500">Guia independente de jogos televisionados e plataformas de streaming no Brasil. Horários e transmissões podem mudar sem aviso prévio.</p>
            <div class="mt-4 flex flex-wrap gap-2"><button type="button" data-pwa-install class="hidden rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700">Instalar Futebol na TV</button><button type="button" data-push-open class="hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-extrabold text-blue-700 hover:bg-blue-100"><span aria-hidden="true">♢</span> <span data-push-label>Ativar alertas</span></button></div>
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

<button type="button" data-push-open data-push-floating aria-label="Configurar notificações de jogos" class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] left-4 z-40 hidden items-center gap-2 rounded-2xl border border-white/30 bg-slate-900 px-3.5 py-3 text-sm font-extrabold text-white shadow-[0_14px_35px_rgba(15,23,42,.3)] ring-4 ring-slate-200/80 sm:hidden"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/></svg><span data-push-label>Alertas</span></button>

<dialog id="push-preferences" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border-0 bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-slate-950/60" aria-labelledby="push-preferences-title">
    <div class="p-6"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-extrabold uppercase tracking-widest text-blue-600">Futebol na TV</p><h2 id="push-preferences-title" class="mt-1 text-2xl font-black">Alertas de jogos</h2><p class="mt-2 text-sm leading-6 text-slate-500">Receba informações mesmo quando o site estiver fechado.</p></div><button type="button" data-push-close aria-label="Fechar" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-xl text-slate-600">×</button></div>
    <div class="mt-6 space-y-3"><label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="checkbox" data-push-daily checked class="mt-1 h-4 w-4 accent-blue-600"><span><strong class="block">Resumo dos jogos do dia</strong><small class="mt-1 block leading-5 text-slate-500">Uma notificação pela manhã com os principais jogos na TV.</small></span></label><label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="checkbox" data-push-reminders class="mt-1 h-4 w-4 accent-blue-600"><span><strong class="block">Lembretes antes das partidas</strong><small class="mt-1 block leading-5 text-slate-500">Avisos 30 minutos antes dos jogos das ligas prioritárias.</small></span></label></div>
    <p data-push-status class="mt-4 min-h-5 text-sm font-bold text-slate-500"></p>
    <div class="mt-4 grid gap-2 sm:grid-cols-2"><button type="button" data-push-enable class="rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700">Ativar notificações</button><button type="button" data-push-disable class="hidden rounded-xl border border-red-200 bg-red-50 px-5 py-3 font-extrabold text-red-700 hover:bg-red-100">Desativar</button></div>
    <p class="mt-4 text-xs leading-5 text-slate-400">Você pode alterar ou cancelar os alertas a qualquer momento. Não enviamos notificações sem sua autorização.</p></div>
</dialog>
