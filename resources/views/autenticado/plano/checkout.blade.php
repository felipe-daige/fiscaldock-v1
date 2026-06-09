@php
    $isCustomCheckout = (bool) ($pacote['is_custom'] ?? false);
    $publicKey = config('services.mercadopago.public_key');
@endphp

{{-- Checkout — Mercado Pago Checkout Bricks (coluna única, minimalista) --}}
<div class="min-h-screen bg-gray-100" id="checkout-container"
     data-mp-public-key="{{ $publicKey }}"
     data-mp-endpoint="{{ route('app.pagamento.mercadopago.criar') }}"
     data-mp-pacote="{{ $pacote['slug'] }}"
     data-mp-amount="{{ number_format($pacote['preco'], 2, '.', '') }}"
     data-mp-creditos="{{ (int) $pacote['creditos'] }}">
    <div class="max-w-lg mx-auto px-3 sm:px-6 py-5 sm:py-10">

        <style>
            @keyframes ck-fade-in { from { opacity: 0; transform: translateY(20px); } }
            @keyframes ck-spin { to { transform: rotate(360deg); } }
            .ck-spinner { animation: ck-spin 0.8s linear infinite; }
            @keyframes ck-scale-in { from { opacity: 0; transform: scale(0.5); } to { opacity: 1; transform: scale(1); } }
            .ck-scale-in { animation: ck-scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        </style>

        {{-- Voltar --}}
        <a href="/app/plano" data-link class="inline-flex items-center gap-1.5 text-[13px] text-gray-500 hover:text-gray-900 hover:underline mb-5 sm:mb-6 py-1 -my-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>

        <div class="bg-white rounded-lg border border-gray-200 px-4 sm:px-7 py-5 sm:py-8">

            {{-- Header enxuto --}}
            <h1 class="text-lg font-bold text-gray-900">Finalizar compra</h1>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm text-gray-500">
                <span>{{ $pacote['nome'] }}</span>
                <span class="text-gray-300">·</span>
                <span>{{ number_format($pacote['creditos'], 0, ',', '.') }} créditos</span>
                <span class="text-gray-300">·</span>
                <span class="font-semibold text-gray-900 font-mono">R$ {{ number_format($pacote['preco'], 2, ',', '.') }}</span>
            </div>

            <div class="border-t border-gray-100 my-6"></div>

            {{-- Formulário de pagamento --}}
            @if(empty($publicKey))
                {{-- Gateway não configurado — fallback honesto, sem simulação --}}
                <div class="p-4 rounded border border-gray-300 border-l-4 border-l-amber-500 bg-amber-50">
                    <p class="text-sm text-gray-800 font-semibold mb-1">Pagamento on-line indisponível</p>
                    <p class="text-xs text-gray-600">O gateway de pagamento ainda não está configurado nesta conta. Tente novamente em instantes.</p>
                </div>
            @else
                {{-- Estado de carregamento do Brick --}}
                <div id="ck-brick-loading" class="py-10 text-center">
                    <svg class="w-6 h-6 ck-spinner text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="31.4 31.4" stroke-linecap="round"/>
                    </svg>
                    <span class="text-[11px] text-gray-500 uppercase tracking-wide">Carregando pagamento seguro…</span>
                </div>

                {{-- Container do Payment Brick (cartão + Pix renderizados pelo MP) --}}
                <div id="paymentBrick_container"></div>

                {{-- Resultado Pix --}}
                <div id="ck-pix-result" class="hidden text-center py-6">
                    <p class="text-sm text-gray-700 mb-3 font-semibold">Escaneie o QR Code para pagar via Pix</p>
                    <img id="ck-pix-qr" alt="QR Code Pix" class="w-48 h-48 max-w-full mx-auto mb-4 border border-gray-200 rounded">
                    <div class="flex items-center gap-2 max-w-sm mx-auto">
                        <input type="text" readonly id="ck-pix-code"
                               class="flex-1 min-w-0 px-3 py-2.5 border border-gray-300 rounded text-xs text-gray-500 bg-gray-50 font-mono truncate">
                        <div class="relative">
                            <button type="button" onclick="window._ckCopyPix && window._ckCopyPix()"
                                    class="px-3 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 rounded text-xs font-medium text-gray-700 transition-colors whitespace-nowrap">
                                Copiar
                            </button>
                            <span id="ck-pix-copied"
                                  class="pointer-events-none absolute -top-8 left-1/2 whitespace-nowrap px-2 py-1 rounded text-[11px] font-medium text-white opacity-0 transition-opacity duration-150"
                                  style="background-color: #111827; transform: translateX(-50%);">Copiado!</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-3">Os créditos entram automaticamente após a confirmação do pagamento.</p>
                </div>

                {{-- Erro --}}
                <div id="ck-error" class="hidden mt-4 p-3 rounded border border-gray-300 border-l-4 border-l-red-500 bg-red-50">
                    <p class="text-xs text-gray-800" id="ck-error-msg">Não foi possível processar o pagamento.</p>
                </div>
            @endif

            <div class="border-t border-gray-100 mt-6 pt-4">
                <div class="flex items-center gap-2 text-[11px] text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Pagamento processado pelo Mercado Pago · créditos liberados após a confirmação</span>
                </div>
            </div>

        </div>

        {{-- Sucesso Overlay (hidden) --}}
        <div id="ck-success-overlay" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6 sm:p-8 max-w-sm w-full text-center ck-scale-in">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: #ecfdf5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Pagamento aprovado</h3>
                <p class="text-sm text-gray-600 mb-1">
                    <span class="font-semibold" style="color: #047857">{{ number_format($pacote['creditos'], 0, ',', '.') }} créditos</span> serão liberados em instantes.
                </p>
                <p class="text-[11px] text-gray-400 mb-6">{{ $pacote['nome'] }} — R$ {{ number_format($pacote['preco'], 2, ',', '.') }}</p>
                <a href="/app/plano" data-link
                   class="inline-flex items-center justify-center w-full py-2.5 text-white rounded text-sm font-semibold transition-colors"
                   style="background-color: #047857"
                   onmouseover="this.style.backgroundColor='#065f46'"
                   onmouseout="this.style.backgroundColor='#047857'">
                    Voltar
                </a>
            </div>
        </div>

    </div>
</div>

<script>
window.initCheckout = function() {
    var root = document.getElementById('checkout-container');
    if (!root) return;

    // Guard de mount por nó de DOM: o spa.js re-injeta o script inline E ainda chama
    // window.initCheckout() (nome derivado da URL /app/checkout/...), então initCheckout
    // roda 2× por navegação. Como o app.innerHTML troca o nó a cada navegação, a flag é
    // fresca por visita; a 2ª chamada na mesma visita vira no-op → 1 único Brick.
    if (root.__ckInit) return;
    root.__ckInit = true;

    var PUBLIC_KEY = root.getAttribute('data-mp-public-key');
    var ENDPOINT = root.getAttribute('data-mp-endpoint');
    var PACOTE = root.getAttribute('data-mp-pacote');
    var AMOUNT = parseFloat(root.getAttribute('data-mp-amount'));
    if (!PUBLIC_KEY) return; // gateway não configurado — view já mostra o fallback

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function show(id) { var el = document.getElementById(id); if (el) el.classList.remove('hidden'); }
    function hide(id) { var el = document.getElementById(id); if (el) el.classList.add('hidden'); }

    function showError(msg) {
        var el = document.getElementById('ck-error-msg');
        if (el && msg) el.textContent = msg;
        show('ck-error');
    }

    window._ckCopyPix = function() {
        var code = document.getElementById('ck-pix-code');
        if (!code) return;
        code.select();
        try { document.execCommand('copy'); } catch (e) {}
        if (navigator.clipboard) { navigator.clipboard.writeText(code.value).catch(function(){}); }

        // Feedback visual: tooltip "Copiado!" some sozinho.
        var tip = document.getElementById('ck-pix-copied');
        if (tip) {
            tip.classList.remove('opacity-0');
            tip.classList.add('opacity-100');
            if (window._ckCopiedTimer) clearTimeout(window._ckCopiedTimer);
            window._ckCopiedTimer = setTimeout(function () {
                tip.classList.add('opacity-0');
                tip.classList.remove('opacity-100');
            }, 1500);
        }
    };

    function handleResult(d) {
        var poi = d.point_of_interaction;
        var pix = poi && poi.transaction_data;
        if (pix && (pix.qr_code || pix.qr_code_base64)) {
            // Pix: renderiza QR + copia-e-cola; crédito entra via webhook após pagar.
            hide('paymentBrick_container');
            if (pix.qr_code_base64) {
                document.getElementById('ck-pix-qr').src = 'data:image/png;base64,' + pix.qr_code_base64;
            }
            if (pix.qr_code) {
                document.getElementById('ck-pix-code').value = pix.qr_code;
            }
            show('ck-pix-result');
            return;
        }
        if (d.status === 'approved') {
            show('ck-success-overlay');
            return;
        }
        if (d.status === 'in_process' || d.status === 'pending' || d.status === 'authorized') {
            showError('Pagamento em processamento. Avisaremos assim que for confirmado — os créditos entram automaticamente.');
            return;
        }
        showError('Pagamento não aprovado' + (d.status_detail ? ' (' + d.status_detail + ').' : '.') + ' Tente outro meio de pagamento.');
    }

    function boot() {
        if (!window.MercadoPago) { showError('Falha ao carregar o pagamento seguro.'); return; }

        // Idempotência (defense-in-depth): mesmo que boot seja chamado mais de uma vez
        // (listeners de load acumulados), monta um único Brick por nó.
        if (root.__ckBooted) return;
        root.__ckBooted = true;

        // Desmonta brick anterior (re-navegação SPA) e limpa o container antes de recriar.
        if (window._ckBrickController && window._ckBrickController.unmount) {
            try { window._ckBrickController.unmount(); } catch (e) {}
            window._ckBrickController = null;
        }
        var container = document.getElementById('paymentBrick_container');
        if (container) container.innerHTML = '';

        var mp = new window.MercadoPago(PUBLIC_KEY, { locale: 'pt-BR' });
        var bricks = mp.bricks();

        bricks.create('payment', 'paymentBrick_container', {
            initialization: { amount: AMOUNT },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    bankTransfer: 'all', // Pix
                    maxInstallments: 1,
                },
            },
            callbacks: {
                onReady: function () { hide('ck-brick-loading'); },
                onSubmit: function (params) {
                    var formData = (params && params.formData) ? params.formData : params;
                    hide('ck-error');
                    return new Promise(function (resolve, reject) {
                        fetch(ENDPOINT, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ pacote: PACOTE, amount: AMOUNT, payment_data: formData }),
                        }).then(function (r) {
                            return r.json().then(function (d) { return { ok: r.ok, d: d }; });
                        }).then(function (res) {
                            if (!res.ok) { showError((res.d && res.d.error) || 'Não foi possível processar o pagamento.'); reject(); return; }
                            handleResult(res.d);
                            resolve();
                        }).catch(function () { showError('Falha de conexão. Tente novamente.'); reject(); });
                    });
                },
                onError: function (error) {
                    console.error('Brick error:', error);
                    showError('Erro no formulário de pagamento.');
                },
            },
        }).then(function (controller) {
            window._ckBrickController = controller;
        }).catch(function (e) {
            console.error('Falha ao montar o Brick:', e);
            root.__ckBooted = false; // permite re-tentar numa próxima visita
            hide('ck-brick-loading');
            showError('Não foi possível carregar o pagamento.');
        });
    }

    // Carrega o SDK do Mercado Pago dinamicamente (scripts inline do SPA rodam antes
    // de externos — então não confiamos numa <script src> na view).
    if (window.MercadoPago) {
        boot();
    } else {
        var existing = document.querySelector('script[data-mp-sdk]');
        if (existing) {
            existing.addEventListener('load', boot);
        } else {
            var s = document.createElement('script');
            s.src = 'https://sdk.mercadopago.com/js/v2';
            s.setAttribute('data-mp-sdk', '1');
            s.onload = boot;
            s.onerror = function () { hide('ck-brick-loading'); showError('Falha ao carregar o pagamento seguro.'); };
            document.head.appendChild(s);
        }
    }

    // Cleanup SPA — contrato de OBJETO (o spa.js itera Object.values e reseta {}).
    // Usar array aqui quebrava ({}.push lança) e o unmount nunca registrava → bricks empilhados.
    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.checkout = function () {
        if (window._ckBrickController && window._ckBrickController.unmount) {
            try { window._ckBrickController.unmount(); } catch (e) {}
        }
        window._ckBrickController = null;
        window._ckCopyPix = null;
        if (window._ckCopiedTimer) { clearTimeout(window._ckCopiedTimer); window._ckCopiedTimer = null; }
        if (root) { root.__ckInit = false; root.__ckBooted = false; }
    };
};

if (document.readyState !== 'loading') {
    window.initCheckout();
} else {
    document.addEventListener('DOMContentLoaded', window.initCheckout);
}
</script>
