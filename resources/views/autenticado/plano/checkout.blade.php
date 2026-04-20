@php
    $isCustomCheckout = (bool) ($pacote['is_custom'] ?? false);
    $isFeaturedCheckout = ($pacote['kind'] ?? null) === 'featured';
@endphp

{{-- Checkout - Simulacao de Gateway (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="checkout-container">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <style>
            @keyframes ck-fade-in {
                from { opacity: 0; transform: translateY(20px); }
            @keyframes ck-spin {
                to { transform: rotate(360deg); }
            }
            .ck-spinner {
                animation: ck-spin 0.8s linear infinite;
            }
            @keyframes ck-scale-in {
                from { opacity: 0; transform: scale(0.5); }
                to { opacity: 1; transform: scale(1); }
            }
            .ck-scale-in {
                animation: ck-scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
        </style>

        {{-- Voltar --}}
        <a href="/app/plano" data-link class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-900 hover:underline mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar para Faixa Comercial
        </a>

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Finalizar Compra</h1>
            <p class="mt-1 text-xs text-gray-500">
                @if($isCustomCheckout)
                    Confirme sua recarga personalizada para adicionar créditos pré-pagos à conta.
                @else
                    Complete o pagamento para adicionar os créditos da oferta promocional à sua conta.
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- Formulario de Pagamento (3/5) --}}
            <div class="lg:col-span-3 space-y-6" id="ck-form-area">

                {{-- Metodo de Pagamento --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Método de Pagamento</span>
                    </div>
                    <div class="p-5">

                    {{-- Tabs --}}
                    <div class="flex border-b border-gray-200 mb-5">
                        <button type="button" id="ck-tab-cartao"
                                class="px-4 py-2.5 text-sm font-semibold border-b-2 border-gray-800 text-gray-900 -mb-px transition-colors"
                                onclick="window._ckSwitchTab('cartao')">
                            Cartão de Crédito
                        </button>
                        <button type="button" id="ck-tab-pix"
                                class="px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-900 -mb-px transition-colors"
                                onclick="window._ckSwitchTab('pix')">
                            PIX
                        </button>
                    </div>

                    {{-- Cartao Form --}}
                    <div id="ck-panel-cartao">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Número do Cartão</label>
                                <input type="text" id="ck-card-number" maxlength="19" placeholder="0000 0000 0000 0000"
                                       class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nome no Cartão</label>
                                <input type="text" id="ck-card-name" placeholder="NOME COMPLETO"
                                       class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow uppercase">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Validade</label>
                                    <input type="text" id="ck-card-expiry" maxlength="5" placeholder="MM/AA"
                                           class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CVV</label>
                                    <input type="text" id="ck-card-cvv" maxlength="4" placeholder="000"
                                           class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow font-mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PIX Panel --}}
                    <div id="ck-panel-pix" class="hidden">
                        <div class="text-center py-6">
                            {{-- QR Code Placeholder --}}
                            <div class="w-48 h-48 mx-auto bg-gray-50 border-2 border-dashed border-gray-300 rounded flex items-center justify-center mb-4">
                                <div class="text-center">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">QR Code PIX</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">Escaneie o QR Code ou copie o código abaixo:</p>
                            <div class="flex items-center gap-2 max-w-sm mx-auto">
                                <input type="text" readonly value="00020126580014br.gov.bcb.pix..." id="ck-pix-code"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded text-xs text-gray-500 bg-gray-50 font-mono truncate">
                                <button type="button" onclick="window._ckCopyPix()"
                                        class="px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 rounded text-xs font-medium text-gray-700 transition-colors whitespace-nowrap">
                                    Copiar
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-3">Validade: 30 minutos</p>
                        </div>
                    </div>

                    </div>
                </div>

                {{-- Botao Pagar (cartao only) --}}
                <div id="ck-btn-area">
                    <button type="button" id="ck-btn-pagar" onclick="window._ckProcessPayment()"
                            class="w-full py-3 bg-gray-800 hover:bg-gray-700 text-white rounded text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Pagar R$ {{ number_format($pacote['preco'], 2, ',', '.') }}
                    </button>
                </div>
            </div>

            {{-- Resumo do Pedido (2/5) --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded border border-gray-300 overflow-hidden lg:sticky lg:top-6">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo do Pedido</span>
                    </div>
                    <div class="p-5">

                    <div class="space-y-3 pb-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Origem</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $pacote['nome'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Modelo</span>
                            <span class="text-sm font-medium text-gray-700">{{ $isCustomCheckout ? 'Valor livre' : 'Oferta promocional' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Créditos</span>
                            <span class="text-sm font-medium text-gray-700">{{ number_format($pacote['creditos'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Valor pago</span>
                            <span class="text-sm font-semibold text-gray-900 font-mono">R$ {{ number_format($pacote['preco'], 2, ',', '.') }}</span>
                        </div>
                        @if($isFeaturedCheckout)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Benefício</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                      style="background-color: #047857">{{ $pacote['badge'] ?? 'Oferta' }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <span class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Total</span>
                        <span class="text-xl font-bold text-gray-900 font-mono">R$ {{ number_format($pacote['preco'], 2, ',', '.') }}</span>
                    </div>

                    <div class="mt-4 p-3 bg-white rounded border border-gray-300 border-l-4 border-l-blue-500">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-gray-700">
                                Créditos adicionados após a confirmação do pagamento.
                                @if($isCustomCheckout)
                                    Você escolheu um valor livre acima do mínimo de R$ {{ number_format($pricing['minimum_deposit'] ?? 50, 0, ',', '.') }}.
                                @endif
                                Sua faixa comercial sobe conforme o histórico acumulado de créditos pagos.
                            </p>
                        </div>
                    </div>

                    {{-- Seguranca --}}
                    <div class="mt-4 flex items-center gap-2 text-[11px] text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Pagamento seguro e criptografado</span>
                    </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Sucesso Overlay (hidden) --}}
        <div id="ck-success-overlay" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded border border-gray-300 p-8 max-w-sm w-full text-center ck-scale-in">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: #ecfdf5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1 uppercase tracking-wide">Pagamento Confirmado</h3>
                <p class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold" style="color: #047857">{{ number_format($pacote['creditos'], 0, ',', '.') }} créditos</span> adicionados à sua conta.
                </p>
                <p class="text-[11px] text-gray-500 mb-6">{{ $pacote['nome'] }} — R$ {{ number_format($pacote['preco'], 2, ',', '.') }}</p>
                <a href="/app/plano" data-link
                   class="inline-flex items-center justify-center w-full py-2.5 text-white rounded text-sm font-semibold transition-colors"
                   style="background-color: #047857"
                   onmouseover="this.style.backgroundColor='#065f46'"
                   onmouseout="this.style.backgroundColor='#047857'">
                    Voltar para Faixa Comercial
                </a>
            </div>
        </div>

    </div>
</div>

<script>
window.initCheckout = function() {
    // Tab switching
    window._ckSwitchTab = function(tab) {
        var tabCartao = document.getElementById('ck-tab-cartao');
        var tabPix = document.getElementById('ck-tab-pix');
        var panelCartao = document.getElementById('ck-panel-cartao');
        var panelPix = document.getElementById('ck-panel-pix');
        var btnArea = document.getElementById('ck-btn-area');

        if (tab === 'cartao') {
            tabCartao.className = 'px-4 py-2.5 text-sm font-semibold border-b-2 border-gray-800 text-gray-900 -mb-px transition-colors';
            tabPix.className = 'px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-900 -mb-px transition-colors';
            panelCartao.classList.remove('hidden');
            panelPix.classList.add('hidden');
            btnArea.classList.remove('hidden');
        } else {
            tabPix.className = 'px-4 py-2.5 text-sm font-semibold border-b-2 border-gray-800 text-gray-900 -mb-px transition-colors';
            tabCartao.className = 'px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-900 -mb-px transition-colors';
            panelPix.classList.remove('hidden');
            panelCartao.classList.add('hidden');
            btnArea.classList.add('hidden');
        }
    };

    // Copy PIX code
    window._ckCopyPix = function() {
        var code = document.getElementById('ck-pix-code');
        code.select();
        document.execCommand('copy');
    };

    // Card number formatting
    var cardInput = document.getElementById('ck-card-number');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            var v = e.target.value.replace(/\D/g, '').substring(0, 16);
            var formatted = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = formatted;
        });
    }

    // Expiry formatting
    var expiryInput = document.getElementById('ck-card-expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            var v = e.target.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) {
                v = v.substring(0, 2) + '/' + v.substring(2);
            }
            e.target.value = v;
        });
    }

    // CVV - digits only
    var cvvInput = document.getElementById('ck-card-cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    }

    // Simulate payment
    window._ckProcessPayment = function() {
        var btn = document.getElementById('ck-btn-pagar');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 ck-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg> Processando...';
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        setTimeout(function() {
            btn.innerHTML = '<svg class="w-4 h-4 ck-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg> Confirmando pagamento...';

            setTimeout(function() {
                document.getElementById('ck-success-overlay').classList.remove('hidden');
            }, 1000);
        }, 1500);
    };
};

if (document.readyState !== 'loading') {
    window.initCheckout();
} else {
    document.addEventListener('DOMContentLoaded', window.initCheckout);
}
</script>
