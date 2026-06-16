{{-- Banner de consentimento de cookies (LGPD). Decisão guardada em localStorage;
     não renderiza novamente após aceitar/rejeitar. Sem cookies de análise até o aceite. --}}
<div id="cookie-consent-banner"
     class="hidden fixed bottom-0 inset-x-0 z-50 px-4 pb-4 sm:px-6 sm:pb-6"
     role="dialog" aria-live="polite" aria-label="Aviso de cookies">
    <div class="max-w-5xl mx-auto rounded-lg shadow-2xl border border-gray-200 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5"
         style="background-color: #0b1f3a;">
        <p class="text-sm text-gray-200 leading-relaxed flex-1">
            Usamos cookies essenciais para o site funcionar e, com seu consentimento, cookies de análise para
            melhorar sua experiência. Saiba mais na nossa
            <a href="{{ route('privacidade') }}" class="font-semibold underline" style="color: #facc15;">Política de Privacidade</a>.
        </p>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" id="cookie-consent-reject"
                    class="px-4 py-2 rounded text-sm font-semibold text-gray-200 border border-gray-500 hover:bg-white/10 transition">
                Rejeitar
            </button>
            <button type="button" id="cookie-consent-accept"
                    class="px-4 py-2 rounded text-sm font-bold transition"
                    style="background-color: #facc15; color: #0b1f3a;">
                Aceitar
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var KEY = 'fd_cookie_consent';
    var banner = document.getElementById('cookie-consent-banner');
    if (!banner) return;

    var decidido;
    try { decidido = window.localStorage.getItem(KEY); } catch (e) { decidido = 'unavailable'; }

    if (!decidido) {
        banner.classList.remove('hidden');
    }

    function registrar(valor) {
        try { window.localStorage.setItem(KEY, valor); } catch (e) { /* modo privado: só esconde */ }
        banner.classList.add('hidden');
    }

    var ok = document.getElementById('cookie-consent-accept');
    var no = document.getElementById('cookie-consent-reject');
    if (ok) ok.addEventListener('click', function () { registrar('accepted'); });
    if (no) no.addEventListener('click', function () { registrar('rejected'); });
})();
</script>
