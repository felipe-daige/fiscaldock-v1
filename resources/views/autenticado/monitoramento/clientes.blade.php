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
                <p class="text-xs text-gray-500 mb-4 max-w-2xl">Defina o limite de gasto em R$ que o monitoramento automático pode consumir por ciclo. Ao atingir o limite, as assinaturas de monitoramento são pausadas automaticamente até o próximo ciclo — protegendo seu saldo de consumo inesperado.</p>
                @if(! $assinaturaConta)
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-xs text-gray-700 flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-semibold text-gray-800 mb-0.5">O freio se ativa com uma assinatura de monitoramento</p>
                        <p>Planos pagos de monitoramento incluem uma cota mensal em R$, e é sobre ela que o freio atua. No seu plano atual, o monitoramento automático básico (cadastral) não tem custo adicional — então não há limite a definir.</p>
                        <a href="/app/planos" data-link class="inline-block mt-1 text-blue-600 hover:underline font-semibold">Ver planos de monitoramento →</a>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Teto efetivo</p>
                        <p class="text-base font-bold text-gray-900">@brl(app(\App\Services\PricingCatalogService::class)->creditsToCurrency((int) $capEfetivo)) <span class="text-[11px] font-normal text-gray-400">/ciclo</span></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Consumido no ciclo</p>
                        <p class="text-base font-bold text-gray-900">@brl(app(\App\Services\PricingCatalogService::class)->creditsToCurrency((int) $consumoCiclo))</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Padrão do plano</p>
                        <p class="text-base font-bold text-gray-900">@brl(app(\App\Services\PricingCatalogService::class)->creditsToCurrency((int) $capPadrao))</p>
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
                <p class="text-[11px] text-gray-400 mt-2">Deixe em branco para usar o padrão do plano (@brl(app(\App\Services\PricingCatalogService::class)->creditsToCurrency((int) $capPadrao)) em saldo incluso). Use <span class="font-semibold">0</span> para não impor limite (o saldo passa a ser o único controle).</p>
                @endif
            </div>
        </div>

        {{-- Ferramentas de monitoramento (atalhos para telas reais) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ferramentas de monitoramento</span>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="/app/participantes" data-link class="block border border-gray-200 rounded p-4 hover:border-gray-400 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-3-6.65"></path></svg>
                        <span class="text-sm font-semibold text-gray-900">Participantes</span>
                    </div>
                    <p class="text-[11px] text-gray-500">Veja todos os CNPJs e ative o monitoramento de cada um.</p>
                </a>
                <a href="/app/alertas" data-link class="block border border-gray-200 rounded p-4 hover:border-gray-400 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span class="text-sm font-semibold text-gray-900">Alertas</span>
                    </div>
                    <p class="text-[11px] text-gray-500">Vencimentos de certidões e irregularidades detectadas.</p>
                </a>
                <a href="/app/monitoramento/historico" data-link class="block border border-gray-200 rounded p-4 hover:border-gray-400 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold text-gray-900">Histórico</span>
                    </div>
                    <p class="text-[11px] text-gray-500">Consultas e re-monitoramentos já executados.</p>
                </a>
                <a href="/app/monitoramento/grupos" data-link class="block border border-gray-200 rounded p-4 hover:border-gray-400 hover:shadow-sm transition">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span class="text-sm font-semibold text-gray-900">Grupos</span>
                    </div>
                    <p class="text-[11px] text-gray-500">Organize participantes em grupos para monitorar em lote.</p>
                </a>
            </div>
        </div>

        {{-- Nota honesta: visão consolidada por cliente ainda em construção --}}
        <div class="mt-4 bg-gray-50 border border-gray-200 rounded p-3 text-[11px] text-gray-500">
            A visão consolidada de status fiscal por cliente (CNDs, certidões e relatórios reunidos por cliente) ainda está em construção. Por enquanto, use as ferramentas acima.
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
