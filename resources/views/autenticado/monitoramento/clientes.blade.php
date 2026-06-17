{{-- Monitoramento de Clientes - Placeholder (DANFE Modernizado) --}}
@inject('entitlements', 'App\Services\Entitlements\EntitlementService')
@php
    // Fase 5.2: freio de consumo do auto-monitor. Só faz sentido com assinatura da conta (cap vive nela).
    $u = auth()->user();
    $assinaturaConta = $u ? $u->subscription()->first() : null;
    $capEfetivo = $u ? $entitlements->consumptionCap($u) : 0;
    $consumoCiclo = $u ? $entitlements->consumoMonitoramentoNoCiclo($u) : 0;
    $capPadrao = $u ? (int) $entitlements->planFor($u)->creditos_inclusos : 0;
    $limiteAtual = $assinaturaConta?->limite_consumo_automatico;
@endphp
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Monitoramento de Clientes</h1>
            <p class="text-xs text-gray-500 mt-1">Acompanhe o status fiscal dos seus clientes.</p>
        </div>

        {{-- Freio de consumo do auto-monitor (Fase 5.2) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Freio de consumo do auto-monitor</span>
            </div>
            <div class="p-4">
                <p class="text-xs text-gray-500 mb-4 max-w-2xl">Defina o teto de créditos que o monitoramento automático pode gastar por ciclo. Ao atingir o teto, as assinaturas de monitoramento são pausadas automaticamente até o próximo ciclo — protegendo seu saldo de consumo inesperado.</p>
                @if(! $assinaturaConta)
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-xs text-gray-700 flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-semibold text-gray-800 mb-0.5">O freio se ativa com uma assinatura de monitoramento</p>
                        <p>Planos pagos de monitoramento incluem uma cota mensal de créditos, e é sobre ela que o freio atua. No seu plano atual, o monitoramento automático básico (cadastral) não consome créditos — então não há teto a definir.</p>
                        <a href="/app/planos" data-link class="inline-block mt-1 text-blue-600 hover:underline font-semibold">Ver planos de monitoramento →</a>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Teto efetivo</p>
                        <p class="text-base font-bold text-gray-900">{{ number_format($capEfetivo, 0, ',', '.') }} <span class="text-[11px] font-normal text-gray-400">cr/ciclo</span></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Consumido no ciclo</p>
                        <p class="text-base font-bold text-gray-900">{{ number_format($consumoCiclo, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Padrão do plano</p>
                        <p class="text-base font-bold text-gray-900">{{ number_format($capPadrao, 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1">Teto personalizado (créditos)</label>
                        <input type="number" id="input-limite-consumo" min="0" max="1000000" value="{{ $limiteAtual }}" placeholder="Padrão do plano" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded w-48">
                    </div>
                    <button id="btn-salvar-limite" type="button" class="px-3 py-2.5 rounded bg-gray-800 hover:bg-gray-700 text-white text-[13px] font-semibold transition">Salvar</button>
                    <span id="limite-feedback" class="text-[12px]"></span>
                </div>
                <p class="text-[11px] text-gray-400 mt-2">Deixe em branco para usar o padrão do plano ({{ number_format($capPadrao, 0, ',', '.') }} créditos inclusos). Use <span class="font-semibold">0</span> para não impor freio (o saldo passa a ser o único limite).</p>
                @endif
            </div>
        </div>

        {{-- Conteúdo --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Em Desenvolvimento</span>
            </div>
            <div class="p-10 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                <h2 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Em Desenvolvimento</h2>
                <p class="mt-2 text-xs text-gray-500 max-w-md mx-auto">
                    Esta funcionalidade está sendo desenvolvida para permitir o monitoramento centralizado dos seus clientes.
                </p>

                {{-- Features Preview --}}
                <div class="bg-gray-50 border border-gray-200 rounded p-5 max-w-lg mx-auto mt-6 text-left">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-3">O que você poderá fazer aqui</h3>
                    <ul class="text-xs text-gray-700 space-y-2">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Visualizar status fiscal de todos os clientes
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Acompanhar CNDs e certidões por cliente
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Receber alertas de vencimento e irregularidades
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Gerar relatórios consolidados por cliente
                        </li>
                    </ul>
                </div>

                <a href="/app/participantes" data-link class="mt-6 inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Ver Participantes
                </a>
            </div>
        </div>

        {{-- Contato --}}
        <div class="mt-6 text-center">
            <p class="text-[11px] text-gray-500">
                Tem alguma sugestão? Entre em contato pelo
                <a href="mailto:suporte@fiscaldock.com.br" class="text-gray-700 hover:text-gray-900 hover:underline">suporte@fiscaldock.com.br</a>
            </p>
        </div>

    </div>
</div>

@if($assinaturaConta)
<script>
(function () {
    // Fase 5.2: salvar teto de consumo do auto-monitor. Listener no botão específico do render
    // atual (SPA reinsere o partial → elemento antigo some, sem vazamento).
    const btn = document.getElementById('btn-salvar-limite');
    const input = document.getElementById('input-limite-consumo');
    const feedback = document.getElementById('limite-feedback');
    if (!btn || !input) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    btn.addEventListener('click', async function () {
        const raw = input.value.trim();
        const limite = raw === '' ? null : parseInt(raw, 10);

        if (limite !== null && (isNaN(limite) || limite < 0 || limite > 1000000)) {
            feedback.textContent = 'Valor inválido.';
            feedback.style.color = '#dc2626';
            return;
        }

        btn.disabled = true;
        feedback.textContent = 'Salvando…';
        feedback.style.color = '#6b7280';

        try {
            const resp = await fetch('{{ route('app.monitoramento.limite-consumo') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ limite }),
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                feedback.textContent = '✓ Teto atualizado (' + Number(data.cap_efetivo).toLocaleString('pt-BR') + ' cr/ciclo).';
                feedback.style.color = '#047857';
            } else {
                feedback.textContent = data.error || data.message || 'Não foi possível salvar.';
                feedback.style.color = '#dc2626';
            }
        } catch (e) {
            feedback.textContent = 'Erro de conexão.';
            feedback.style.color = '#dc2626';
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endif
