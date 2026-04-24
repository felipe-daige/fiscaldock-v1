@php
    $resultados = $resultados ?? collect();
    $resumo = $resumo ?? ['total' => 0, 'ja_no_acervo' => 0, 'autorizadas' => 0, 'alertas' => 0, 'indeterminadas' => 0, 'erros' => 0];
    $statusMeta = $statusMeta ?? ['label' => 'Pendente', 'hex' => '#9ca3af'];
    $aguardaPersistencia = (bool) ($aguardaPersistencia ?? false);
    $statusLote = \App\Models\ConsultaLote::normalizeStatus($lote->status ?? 'pendente');
    $tipoValidacao = strtolower((string) ($tipoValidacao ?? ''));
    $tipoValidacaoLabel = match ($tipoValidacao) {
        'full' => 'Full',
        'basico' => 'Básico',
        default => null,
    };
    $erroCriticoLote = $lote->publicErrorUi([
        'context' => 'clearance-lote',
        'url' => request()->getPathInfo(),
    ]);
    $awaitResultMessage = 'O processamento do lote foi concluído, mas os snapshots ainda não estão disponíveis nesta tela. A verificação continua automaticamente.';
    $divergencia = $divergencia ?? [
        'veredito' => ['severidade' => 'ok', 'total_criticas' => 0, 'total_revisar' => 0, 'valor_divergente' => 0.0, 'mensagem' => ''],
        'kpis' => [
            'existencia' => ['total' => 0, 'encontradas' => 0, 'nao_encontradas' => 0],
            'status' => ['total' => 0, 'canceladas_declaradas' => 0, 'denegadas' => 0, 'inutilizadas' => 0],
            'valor' => ['notas_divergentes' => 0, 'valor_divergente' => 0],
            'roi' => ['creditos' => 0, 'custo_reais' => 0, 'exposicao_reais' => 0],
        ],
        'breakdown' => [
            'notas_frias' => ['count' => 0, 'valor' => 0],
            'canceladas_declaradas' => ['count' => 0, 'valor' => 0],
            'valor_divergente' => ['count' => 0, 'valor' => 0],
            'partes_divergentes' => ['count' => 0, 'valor' => 0],
            'operacionais' => ['count' => 0, 'valor' => 0],
        ],
        'divergencias' => collect(),
        'sem_divergencia' => collect(),
        'ruido' => collect(),
    ];
    $veredictoHex = match ($divergencia['veredito']['severidade']) {
        'critica' => '#dc2626',
        'revisar' => '#d97706',
        default => '#047857',
    };
    $veredictoLabel = match ($divergencia['veredito']['severidade']) {
        'critica' => 'Atenção crítica',
        'revisar' => 'Revisar',
        default => 'Tudo certo',
    };
    $formatMoney = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $breakdownCards = [
        ['key' => 'notas_frias', 'label' => 'Notas frias', 'sub' => 'chave inexistente na SEFAZ', 'hex' => '#dc2626'],
        ['key' => 'canceladas_declaradas', 'label' => 'Canceladas declaradas', 'sub' => 'SEFAZ cancelou, escriturado', 'hex' => '#dc2626'],
        ['key' => 'valor_divergente', 'label' => 'Valor divergente', 'sub' => 'acima da tolerância', 'hex' => '#d97706'],
        ['key' => 'partes_divergentes', 'label' => 'Emitente / destinatário', 'sub' => 'CNPJ ou nome diferente', 'hex' => '#d97706'],
        ['key' => 'operacionais', 'label' => 'Operacionais', 'sub' => 'NCM, quantidade, itens', 'hex' => '#6b7280'],
    ];
@endphp

<div
    class="min-h-screen bg-gray-100"
    id="clearance-resultado-root"
    data-status="{{ $statusLote }}"
    data-tab-id="{{ $lote->tab_id ?? '' }}"
    data-stream-url="{{ url('/app/consulta/progresso/stream') }}"
    data-json-url="{{ route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->id, 'tipo_validacao' => $tipoValidacao]) }}"
    data-await-result="{{ $aguardaPersistencia ? '1' : '0' }}"
    data-await-result-message="{{ $awaitResultMessage }}"
    data-poll-result="1"
    data-progress-snapshot='@json($progressSnapshot ?? null)'
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="/app/clearance/notas" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Voltar para verificação
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Resultado do Clearance</h1>
                <p class="text-xs text-gray-500 mt-1">Esta pagina concentra o andamento do lote e recarrega o resultado final quando o provedor finalizar.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                    Lote #{{ $lote->id }}
                </span>
                @if($tipoValidacaoLabel)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                        {{ $tipoValidacaoLabel }}
                    </span>
                @endif
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusMeta['hex'] }}">
                    {{ $statusMeta['label'] }}
                </span>
            </div>
        </div>

        @if($statusLote === 'finalizado')
            <div class="rounded border overflow-hidden mb-4" style="border-color: {{ $veredictoHex }}">
                <div class="px-4 py-3 text-white" style="background-color: {{ $veredictoHex }}">
                    <p class="text-[10px] font-semibold uppercase tracking-widest opacity-80">Veredito do lote</p>
                    <p class="text-lg font-bold leading-tight mt-1">{{ $veredictoLabel }}</p>
                    <p class="text-sm opacity-95 mt-1">{{ $divergencia['veredito']['mensagem'] }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Indicadores operacionais</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Existência SEFAZ</p>
                    <p class="text-lg font-bold text-gray-900">{{ $divergencia['kpis']['existencia']['encontradas'] }} de {{ $divergencia['kpis']['existencia']['total'] }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">documentos localizados</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status SEFAZ</p>
                    <p class="text-lg font-bold text-gray-900">{{ $divergencia['kpis']['status']['canceladas_declaradas'] + $divergencia['kpis']['status']['denegadas'] + $divergencia['kpis']['status']['inutilizadas'] }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">canceladas / denegadas escrituradas</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Divergência de valor</p>
                    <p class="text-lg font-bold text-gray-900">{{ $formatMoney($divergencia['kpis']['valor']['valor_divergente']) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $divergencia['kpis']['valor']['notas_divergentes'] }} nota(s) afetada(s)</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">ROI da auditoria</p>
                    <p class="text-lg font-bold text-gray-900">{{ $formatMoney($divergencia['kpis']['roi']['custo_reais']) }} → {{ $formatMoney($divergencia['kpis']['roi']['exposicao_reais']) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $divergencia['kpis']['roi']['creditos'] }} créditos · exposição detectada</p>
                </div>
            </div>
        </div>

        @if($statusLote === 'finalizado')
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Categorias de divergência</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                    @foreach($breakdownCards as $card)
                        @php $d = $divergencia['breakdown'][$card['key']] ?? ['count' => 0, 'valor' => 0]; @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background-color: {{ $d['count'] > 0 ? $card['hex'] : '#e5e7eb' }}"></span>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</p>
                            </div>
                            <p class="text-lg font-bold {{ $d['count'] > 0 ? 'text-gray-900' : 'text-gray-300' }} mt-1">{{ $d['count'] }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ $d['count'] > 0 ? $formatMoney($d['valor']) : $card['sub'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(in_array($statusLote, ['pendente', 'processando'], true))
            <div id="clearance-resultado-progresso" class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Andamento</span>
                    <span id="clearance-resultado-percent" class="text-[10px] text-gray-500 font-mono">0%</span>
                </div>
                <div class="p-4">
                    <p id="clearance-resultado-mensagem" class="text-sm text-gray-600 mb-1">Iniciando clearance...</p>
                    <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: #e5e7eb">
                        <div id="clearance-resultado-bar" class="h-full" style="background-color: #1f2937; width: 8%; transition: width 350ms ease-out"></div>
                    </div>
                    <p id="clearance-resultado-etapa-label" class="text-[11px] text-gray-500 mt-2 hidden"></p>
                    <div id="clearance-resultado-steps" class="hidden mt-3 flex flex-wrap gap-2"></div>
                </div>
            </div>
        @endif

        @if($statusLote === 'erro')
            <div class="bg-white rounded border border-gray-300 p-4 mb-4 border-l-4 border-l-red-500">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Falha crítica do processamento do lote</p>
                <p class="mt-2 text-sm text-gray-700">O lote encontrou uma falha operacional durante o processamento e não foi concluído integralmente.</p>
                <p class="mt-2 text-sm text-gray-700">Essa ocorrência não representa, por si só, o status fiscal das notas que já tiveram snapshot persistido.</p>
                <p class="mt-2 text-sm text-gray-700">Os snapshots persistidos antes da falha continuam válidos e permanecem listados abaixo.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $erroCriticoLote['action_url'] ?? config('support.whatsapp_url', 'https://wa.me/5567999844366') }}"
                       target="{{ $erroCriticoLote['action_target'] ?? '_blank' }}"
                       rel="{{ $erroCriticoLote['action_rel'] ?? 'noopener noreferrer' }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded text-white text-sm font-medium"
                       style="background-color: #1f2937;">
                        {{ $erroCriticoLote['action_label'] ?? config('support.contact_label', 'Falar com o suporte') }}
                    </a>
                </div>
            </div>
        @endif

        @if($divergencia['divergencias']->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Divergências a investigar</p>
                    <p class="mt-1 text-[11px] text-gray-500">Listagem ordenada por severidade. Ruído abaixo da tolerância ({{ $formatMoney(\App\Services\Clearance\DivergenciaService::TOLERANCIA_ABSOLUTA_RUIDO) }} / {{ number_format(\App\Services\Clearance\DivergenciaService::TOLERANCIA_PERCENTUAL_RUIDO, 1, ',', '.') }}%) fica em seção separada.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Documento</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Declarado</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">SEFAZ</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Δ</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Severidade</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($divergencia['divergencias'] as $linha)
                                @php
                                    $sevHex = $linha->severidade === 'critica' ? '#dc2626' : '#d97706';
                                    $sevLabel = $linha->severidade === 'critica' ? 'Crítica' : 'Revisar';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $linha->tipo_documento }}</span>
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #4b5563">{{ $linha->modelo }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900 mt-1">Nº {{ $linha->numero ?: '—' }} / Série {{ $linha->serie ?: '—' }}</p>
                                        <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $linha->chave_acesso }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700">{{ $linha->emit_nome ?: $linha->emit_cnpj ?: '—' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right font-mono whitespace-nowrap">{{ $linha->declarado_valor_label }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right font-mono whitespace-nowrap">{{ $linha->valor_total_label ?? ($linha->valor_total !== null ? $formatMoney($linha->valor_total) : '—') }}</td>
                                    <td class="px-3 py-3 text-sm text-right font-mono whitespace-nowrap" style="color: {{ $sevHex }}">
                                        <span class="font-bold">{{ $linha->delta_valor_label }}</span>
                                        <p class="text-[10px] mt-0.5">{{ $linha->delta_percentual_label }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $sevHex }}">{{ $sevLabel }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap">
                                        @if($linha->detalhe_url ?? null)
                                            <a href="{{ $linha->detalhe_url }}" data-link class="text-xs text-gray-700 hover:text-gray-900 hover:underline">Ver documento</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($statusLote === 'finalizado' && $resultados->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 p-4 mb-4 border-l-4" style="border-left-color: #047857">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Nenhuma divergência acima da tolerância</p>
                <p class="mt-2 text-sm text-gray-700">Todos os {{ $resultados->count() }} documento(s) auditado(s) estão em conformidade com a SEFAZ dentro das tolerâncias definidas.</p>
            </div>
        @elseif($statusLote === 'finalizado')
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Aguardando snapshots persistidos</p>
                <p class="mt-2 text-sm text-gray-700">{{ $awaitResultMessage }}</p>
            </div>
        @endif

        @if($statusLote === 'finalizado' && ($divergencia['sem_divergencia']->isNotEmpty() || $divergencia['ruido']->isNotEmpty()))
            <details class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <summary class="cursor-pointer px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 flex items-center justify-between">
                    <span>
                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sem divergência</span>
                        <span class="ml-2 text-gray-900 font-semibold">{{ $divergencia['sem_divergencia']->count() + $divergencia['ruido']->count() }} documento(s)</span>
                        @if($divergencia['ruido']->isNotEmpty())
                            <span class="ml-2 text-[11px] text-gray-500">({{ $divergencia['ruido']->count() }} dentro do ruído abaixo da tolerância)</span>
                        @endif
                    </span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-wide">expandir</span>
                </summary>
                <div class="border-t border-gray-200">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Documento</th>
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emitente</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Declarado</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">SEFAZ</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Δ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($divergencia['sem_divergencia']->concat($divergencia['ruido']) as $linha)
                                <tr>
                                    <td class="px-3 py-2 text-[11px] text-gray-500 font-mono">{{ $linha->tipo_documento }} {{ $linha->numero }}/{{ $linha->serie }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700">{{ $linha->emit_nome ?: $linha->emit_cnpj ?: '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 text-right font-mono">{{ $linha->declarado_valor_label }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 text-right font-mono">{{ $linha->valor_total_label ?? ($linha->valor_total !== null ? $formatMoney($linha->valor_total) : '—') }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500 text-right font-mono">{{ $linha->delta_valor_label }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>

            <p class="text-[11px] text-gray-500 mt-2">
                Dados confrontados com a Receita Federal via InfoSimples em
                {{ $lote->processado_em?->format('d/m/Y H:i') ?? '—' }}.
                Snapshot arquivado por documento ({{ $divergencia['kpis']['existencia']['total'] }} chave(s)) para evidência de auditoria.
            </p>
        @endif
    </div>
</div>

<script src="{{ asset('js/clearance-resultado.js') }}?v={{ @filemtime(public_path('js/clearance-resultado.js')) ?: time() }}" defer></script>
