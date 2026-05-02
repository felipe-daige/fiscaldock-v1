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
                @php $clearanceRetryCount = $lote->retryLotes()->count(); @endphp
                @if($lote->parent_lote_id || $clearanceRetryCount > 0)
                    <div class="flex items-center flex-wrap gap-1.5 mt-2">
                        @if($lote->parent_lote_id)
                            <a href="{{ route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->parent_lote_id]) }}" data-link class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white hover:opacity-80" style="background-color: #6366f1">↺ Retry do lote #{{ $lote->parent_lote_id }}</a>
                        @endif
                        @if($clearanceRetryCount > 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0891b2">{{ $clearanceRetryCount }} retry{{ $clearanceRetryCount === 1 ? '' : 's' }} derivado{{ $clearanceRetryCount === 1 ? '' : 's' }}</span>
                        @endif
                    </div>
                @endif
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
                @if(in_array($statusLote, ['finalizado', 'erro'], true))
                    <button type="button" data-clearance-retry-trigger="{{ $lote->id }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium hidden">
                        Retentar pendentes
                    </button>
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
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Comparar</th>
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
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        @if($linha->chave_acesso ?? null)
                                            <a href="{{ route('app.clearance.nota.comparar', ['chave' => $linha->chave_acesso]) }}?lote_id={{ $consultaLoteId ?? request('lote_id') ?? '' }}"
                                               data-link
                                               class="text-xs font-medium text-blue-700 hover:text-blue-900 hover:underline">Comparar ↗</a>
                                        @endif
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

@if(in_array($statusLote, ['finalizado', 'erro'], true))
    <div id="clearance-retry-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
            <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Retentar pendentes do lote #{{ $lote->id }}</h2>
                    <p class="text-[11px] text-gray-500 mt-0.5" data-clearance-retry-summary>Carregando pendentes...</p>
                </div>
                <button type="button" data-clearance-retry-close class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-3" data-clearance-retry-body>
                <div class="text-center py-8 text-sm text-gray-500" data-clearance-retry-loading>Carregando...</div>
                <div class="hidden" data-clearance-retry-empty>
                    <p class="text-sm text-gray-700">Não há notas pendentes neste lote.</p>
                </div>
                <div class="hidden" data-clearance-retry-error>
                    <p class="text-sm text-red-600" data-clearance-retry-error-msg></p>
                </div>
                <div class="hidden" data-clearance-retry-list>
                    <div class="flex items-center justify-between mb-2">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" data-clearance-retry-toggle-all checked class="rounded border-gray-300">
                            Selecionar todas
                        </label>
                        <span class="text-[10px] text-gray-500 uppercase tracking-wide" data-clearance-retry-selected-count>0 selecionadas</span>
                    </div>
                    <div class="border border-gray-200 rounded divide-y divide-gray-100" data-clearance-retry-items></div>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50">
                <div class="grid grid-cols-2 gap-3 mb-3 text-xs">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Custo do retry</p>
                        <p class="text-sm font-bold text-gray-900" data-clearance-retry-custo>—</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Saldo atual</p>
                        <p class="text-sm font-bold text-gray-900" data-clearance-retry-saldo>—</p>
                    </div>
                </div>
                <p class="text-[11px] text-red-600 hidden mb-2" data-clearance-retry-saldo-aviso>Saldo insuficiente para esse retry.</p>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-clearance-retry-close class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">Cancelar</button>
                    <button type="button" data-clearance-retry-confirm class="px-4 py-1.5 bg-gray-900 text-white hover:bg-gray-700 rounded text-xs font-medium disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>Confirmar retry</button>
                </div>
            </div>
        </div>
    </div>

<script>
(function() {
    const trigger = document.querySelector('[data-clearance-retry-trigger="{{ $lote->id }}"]');
    const modal = document.getElementById('clearance-retry-modal');
    if (!trigger || !modal) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const pendentesUrl = "{{ route('app.clearance.notas.pendentes', ['consultaLoteId' => $lote->id]) }}";
    const retentarUrl = "{{ route('app.clearance.notas.retentar', ['consultaLoteId' => $lote->id]) }}";
    const tipoValidacao = @json($tipoValidacao ?: 'basico');

    const summary = modal.querySelector('[data-clearance-retry-summary]');
    const loading = modal.querySelector('[data-clearance-retry-loading]');
    const empty = modal.querySelector('[data-clearance-retry-empty]');
    const errorBox = modal.querySelector('[data-clearance-retry-error]');
    const errorMsg = modal.querySelector('[data-clearance-retry-error-msg]');
    const list = modal.querySelector('[data-clearance-retry-list]');
    const itemsContainer = modal.querySelector('[data-clearance-retry-items]');
    const toggleAll = modal.querySelector('[data-clearance-retry-toggle-all]');
    const selectedCount = modal.querySelector('[data-clearance-retry-selected-count]');
    const custoEl = modal.querySelector('[data-clearance-retry-custo]');
    const saldoEl = modal.querySelector('[data-clearance-retry-saldo]');
    const saldoAviso = modal.querySelector('[data-clearance-retry-saldo-aviso]');
    const confirmBtn = modal.querySelector('[data-clearance-retry-confirm]');
    const closeBtns = modal.querySelectorAll('[data-clearance-retry-close]');

    let pendentesData = [];
    let custoUnitario = 0;
    let saldoAtual = 0;

    function open() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        loadPendentes();
    }

    function close() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function showState(state) {
        loading.classList.toggle('hidden', state !== 'loading');
        empty.classList.toggle('hidden', state !== 'empty');
        errorBox.classList.toggle('hidden', state !== 'error');
        list.classList.toggle('hidden', state !== 'list');
    }

    function formatBRL(creditos) {
        const reais = (creditos * 0.20).toFixed(2).replace('.', ',');
        return `${creditos} créditos (R$ ${reais})`;
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function formatMoney(v) {
        if (v === null || v === undefined || isNaN(parseFloat(v))) return '—';
        return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function loadPendentes() {
        showState('loading');
        confirmBtn.disabled = true;
        custoEl.textContent = '—';
        saldoEl.textContent = '—';
        saldoAviso.classList.add('hidden');

        fetch(pendentesUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                pendentesData = data.pendentes || [];
                custoUnitario = data.custo_unitario || 0;
                saldoAtual = data.saldo_atual || 0;
                saldoEl.textContent = `${saldoAtual} créditos`;

                if (pendentesData.length === 0) {
                    summary.textContent = 'Sem pendentes.';
                    showState('empty');
                    return;
                }

                summary.textContent = `${pendentesData.length} nota${pendentesData.length === 1 ? '' : 's'} sem snapshot SEFAZ`;
                renderList();
                showState('list');
            })
            .catch(function() {
                summary.textContent = 'Erro ao carregar.';
                errorMsg.textContent = 'Não foi possível carregar os pendentes. Tente novamente.';
                showState('error');
            });
    }

    function renderList() {
        itemsContainer.innerHTML = '';
        pendentesData.forEach(function(n) {
            const row = document.createElement('label');
            row.className = 'flex items-start gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer';
            row.innerHTML = `
                <input type="checkbox" data-clearance-retry-item="${n.id}" checked class="mt-1 rounded border-gray-300">
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 truncate">
                        <span class="font-mono text-[11px] text-gray-500">${escapeHtml(n.tipo_documento || 'NFE')} ${escapeHtml(n.numero || '—')}/${escapeHtml(n.serie || '—')}</span>
                        · ${escapeHtml(n.emit_razao_social || n.emit_cnpj || 'Sem emitente')}
                    </p>
                    <p class="text-[11px] text-gray-500 font-mono mt-0.5">…${escapeHtml(n.chave_sufixo || '—')}${n.emit_uf ? ' · ' + escapeHtml(n.emit_uf) : ''}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs font-mono text-gray-700">${formatMoney(n.valor_total)}</p>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">${escapeHtml(n.status || 'pendente')}</p>
                </div>
            `;
            itemsContainer.appendChild(row);
        });

        itemsContainer.querySelectorAll('[data-clearance-retry-item]').forEach(function(cb) {
            cb.addEventListener('change', updateCusto);
        });
        updateCusto();
    }

    function getSelectedIds() {
        return Array.from(itemsContainer.querySelectorAll('[data-clearance-retry-item]:checked'))
            .map(function(cb) { return parseInt(cb.dataset.clearanceRetryItem, 10); })
            .filter(function(v) { return !isNaN(v); });
    }

    function updateCusto() {
        const selected = getSelectedIds();
        const custo = selected.length * custoUnitario;
        selectedCount.textContent = `${selected.length} selecionada${selected.length === 1 ? '' : 's'}`;
        custoEl.textContent = selected.length === 0 ? '—' : formatBRL(custo);

        const insufficient = selected.length > 0 && custo > saldoAtual;
        saldoAviso.classList.toggle('hidden', !insufficient);
        confirmBtn.disabled = selected.length === 0 || insufficient;
    }

    toggleAll.addEventListener('change', function() {
        itemsContainer.querySelectorAll('[data-clearance-retry-item]').forEach(function(cb) {
            cb.checked = toggleAll.checked;
        });
        updateCusto();
    });

    confirmBtn.addEventListener('click', function() {
        const selected = getSelectedIds();
        if (selected.length === 0) return;

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Disparando...';

        fetch(retentarUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                nota_ids: selected,
                tipo: tipoValidacao,
                tab_id: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(36).slice(2, 10),
            }),
        })
            .then(function(r) {
                return r.json().then(function(data) { return { ok: r.ok, data: data }; });
            })
            .then(function(res) {
                if (!res.ok || res.data?.success === false) {
                    throw new Error(res.data?.error || 'Falha ao disparar retry.');
                }
                if (res.data.redirect_url) {
                    window.location.href = res.data.redirect_url;
                } else {
                    window.location.reload();
                }
            })
            .catch(function(err) {
                errorMsg.textContent = err.message || 'Erro ao disparar retry.';
                showState('error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmar retry';
            });
    });

    trigger.addEventListener('click', open);
    closeBtns.forEach(function(btn) { btn.addEventListener('click', close); });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) close();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    // Reveal trigger only if there are pendentes
    fetch(pendentesUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(data) {
            if (data && Array.isArray(data.pendentes) && data.pendentes.length > 0) {
                trigger.classList.remove('hidden');
            }
        })
        .catch(function() {});
})();
</script>
@endif
