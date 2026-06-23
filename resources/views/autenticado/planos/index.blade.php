@php
    $profundidadeMap = [
        'cadastral' => 'Cadastral (grátis)',
        'licitacao' => 'Licitação',
        'compliance' => 'Compliance',
        'due_diligence' => 'Due Diligence',
    ];
    $frequenciaMap = [1 => 'diária', 7 => 'semanal', 15 => 'quinzenal', 30 => 'mensal'];
    $exportMap = ['csv' => 'CSV', 'excel' => 'Excel', 'api' => 'API'];
    $biMap = ['basico' => 'BI básico', 'completo' => 'BI completo'];
@endphp

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">

        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Planos de assinatura</h1>
            <p class="text-xs text-gray-500 mt-1">Mensalidade com saldo incluso, limites de uso e features premium. O saldo incluso cobre o monitoramento automático do plano.</p>
        </div>

        {{-- Minha assinatura (quando ativa/inadimplente) --}}
        @if($assinaturaAtual && in_array($assinaturaAtual->status, ['ativa', 'inadimplente']))
            <div class="bg-white rounded border border-gray-300 p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-700">
                    <span class="font-semibold">Sua assinatura:</span>
                    {{ $assinaturaAtual->plan->nome ?? '—' }} ({{ $assinaturaAtual->ciclo }})
                    @if($assinaturaAtual->status === 'inadimplente')
                        <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">Pagamento pendente</span>
                    @else
                        <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Ativa</span>
                    @endif
                    @if($assinaturaAtual->renova_em)
                        <span class="block text-[11px] text-gray-500 mt-1">Próxima cobrança: {{ $assinaturaAtual->renova_em->format('d/m/Y') }}</span>
                    @endif
                </div>
                <button type="button" id="assinatura-cancelar" class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar assinatura</button>
            </div>
        @endif

        {{-- Seletor de ciclo --}}
        <div class="flex items-center gap-4">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ciclo de cobrança:</span>
            <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                <input type="radio" name="ciclo" value="mensal" checked> Mensal
            </label>
            <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                <input type="radio" name="ciclo" value="anual"> Anual <span class="text-[11px] font-semibold text-emerald-700">(−17%)</span>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-stretch">
            @foreach($planos as $plano)
                @php
                    $caps = $plano->capabilities ?? [];
                    $isAtual = $plano->codigo === $planoAtualCodigo;
                    $isRecomendado = $plano->codigo === 'profissional';
                    $isFree = $plano->codigo === 'free';
                    $isEnterprise = $plano->codigo === 'enterprise';

                    $precoMes = $plano->preco_mensal_centavos / 100;
                    $precoAno = $plano->preco_anual_centavos / 100;

                    $exportList = collect($caps['export'] ?? [])->map(fn ($e) => $exportMap[$e] ?? $e)->implode(' + ');
                    $retencao = ($caps['retencao_meses'] ?? null) === null ? 'ilimitada' : ($caps['retencao_meses'].' meses');

                    $features = [];
                    $features[] = [$plano->creditos_inclusos > 0, \App\Support\Dinheiro::brl(app(\App\Services\PricingCatalogService::class)->creditsToCurrency((int) $plano->creditos_inclusos)).' em saldo/mês', $isEnterprise ? 'Saldo sob medida' : 'Sem saldo incluso'];
                    $features[] = [true, ($plano->limite_clientes === null ? 'Clientes ilimitados' : $plano->limite_clientes.' clientes monitorados'), null];
                    $features[] = [true, ($plano->limite_cnpjs_monitorados === null ? 'CNPJs ilimitados' : $plano->limite_cnpjs_monitorados.' CNPJs monitorados'), null];
                    $features[] = [true, 'Auto-monitor: '.($profundidadeMap[$plano->profundidade_auto_monitor] ?? $plano->profundidade_auto_monitor).' / '.($frequenciaMap[$plano->frequencia_padrao_dias] ?? $plano->frequencia_padrao_dias.'d'), null];
                    $features[] = [true, $biMap[$caps['bi'] ?? 'basico'] ?? 'BI', null];
                    $features[] = [! empty($exportList), 'Export: '.$exportList, 'Sem export'];
                    $features[] = [(bool) ($caps['pdf_executivo'] ?? false), 'PDF executivo', null];
                    $features[] = [(bool) ($caps['clearance_lote'] ?? false), 'Clearance em lote', null];
                    $features[] = [(bool) ($caps['clearance_full'] ?? false), 'Clearance Full (tributos via A1)', null];
                    $features[] = [(bool) ($caps['score_historico'] ?? false), 'Score Fiscal com histórico', null];
                    $features[] = [true, 'Retenção de histórico: '.$retencao, null];
                    $features[] = [true, ($plano->assentos_inclusos >= 9999 ? 'Assentos ilimitados' : $plano->assentos_inclusos.' assento'.($plano->assentos_inclusos > 1 ? 's' : '')), null];
                @endphp

                <div class="bg-white rounded border {{ $isRecomendado ? 'border-gray-900' : 'border-gray-300' }} overflow-hidden flex flex-col {{ $isRecomendado ? 'xl:-mt-2 xl:mb-2' : '' }}">
                    {{-- Header --}}
                    <div class="px-4 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-2 min-h-[20px]">
                            <span class="text-sm font-bold text-gray-900 uppercase tracking-wide">{{ $plano->nome }}</span>
                            @if($isAtual)
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Plano atual</span>
                            @elseif($isRecomendado)
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">Recomendado</span>
                            @endif
                        </div>
                        @if($isFree)
                            <p class="text-2xl font-bold text-gray-900">Grátis</p>
                            <p class="text-[11px] text-gray-500 mt-1">para começar</p>
                        @elseif($isEnterprise)
                            <p class="text-2xl font-bold text-gray-900">Sob consulta</p>
                            <p class="text-[11px] text-gray-500 mt-1">operação intensiva</p>
                        @else
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($precoMes, 0, ',', '.') }}<span class="text-sm font-medium text-gray-500">/mês</span></p>
                            <p class="text-[11px] text-gray-500 mt-1">ou R$ {{ number_format($precoAno, 0, ',', '.') }}/ano <span class="font-semibold text-emerald-700">(−17%)</span></p>
                        @endif
                    </div>

                    {{-- Features --}}
                    <div class="px-4 py-4 flex-1 space-y-2">
                        @foreach($features as [$ativo, $labelOn, $labelOff])
                            @php $texto = $ativo ? $labelOn : $labelOff; @endphp
                            @if($texto !== null)
                                <div class="flex items-start gap-2">
                                    @if($ativo)
                                        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                    <span class="text-xs {{ $ativo ? 'text-gray-700' : 'text-gray-400' }}">{{ $texto }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- CTA --}}
                    <div class="px-4 py-4 border-t border-gray-100 mt-auto">
                        @if($isAtual)
                            <button type="button" disabled class="w-full inline-flex items-center justify-center px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 rounded border border-gray-300 bg-gray-50 cursor-default">Plano atual</button>
                        @elseif($isEnterprise)
                            <a href="mailto:contato@fiscaldock.com.br?subject=Plano%20Enterprise" class="w-full inline-flex items-center justify-center px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-white rounded" style="background-color: #1f2937">Falar com vendas</a>
                        @else
                            <button type="button"
                                data-assinar
                                data-plano="{{ $plano->codigo }}"
                                data-nome="{{ $plano->nome }}"
                                data-ciclo-mensal-centavos="{{ $plano->preco_mensal_centavos }}"
                                data-ciclo-anual-centavos="{{ $plano->preco_anual_centavos }}"
                                class="w-full inline-flex items-center justify-center px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-white rounded" style="background-color: #1f2937">Assinar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Rodapé: saldo incluso e avulso --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded border border-gray-300 p-4 space-y-1">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo</p>
                <p class="text-sm text-gray-700">Saldo pré-pago em reais. Os planos incluem saldo por mês; estourou, adicione avulso quando precisar.</p>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4 space-y-1">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Avulso</p>
                <p class="text-sm text-gray-700">Sem assinatura? <a href="/app/creditos" data-link class="text-gray-900 underline hover:text-gray-600">Adicione saldo avulso</a> e use quando precisar.</p>
            </div>
        </div>

    </div>

    {{-- Modal do cartão (Card Payment Brick) --}}
    <div id="assinatura-modal" class="hidden fixed inset-0 z-50 items-center justify-center" style="background-color: rgba(17,24,39,.6)">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Dados do cartão</h3>
                <button type="button" id="assinatura-fechar" class="text-gray-400 text-xl leading-none">&times;</button>
            </div>
            <div id="assinatura-brick"></div>
            <p id="assinatura-erro" class="hidden mt-3 text-xs" style="color: #dc2626"></p>
        </div>
    </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    window.__MP_PUBLIC_KEY = @json($mpPublicKey);
    window.__ASSINAR_URL = @json(route('app.assinatura.criar'));
    window.__CANCELAR_URL = @json(route('app.assinatura.cancelar'));
    window.__CSRF = @json(csrf_token());
    window.__MP_TETO_CENTAVOS = @json($mpTetoCentavos);
    window.__WHATSAPP_URL = @json($whatsappUrl);
</script>
<script src="{{ asset('js/assinatura.js') }}"></script>
