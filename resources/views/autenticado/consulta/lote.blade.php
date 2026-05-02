@php
    $jsVersion = @filemtime(public_path('js/consulta-lote-detalhe.js')) ?: time();
    $statusLote = $statusLote ?? \App\Models\ConsultaLote::normalizeStatus($lote->status ?? 'pendente');
    $statusMeta = $statusMeta ?? ['label' => 'Pendente', 'hex' => '#9ca3af'];
    $etapasJson = e(json_encode($etapas ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $resultados = $resultados ?? new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 20, 1);
    $temResultadosNoLote = $temResultadosNoLote ?? false;
    $erroCriticoLote = $lote->publicErrorUi([
        'url' => request()->getPathInfo(),
    ]);
@endphp

<div
    class="min-h-screen bg-gray-100"
    id="consulta-lote-detalhe-root"
    data-status="{{ $statusLote }}"
    data-tab-id="{{ $lote->tab_id ?? '' }}"
    data-stream-url="{{ url('/app/consulta/progresso/stream') }}"
    data-status-url="{{ route('app.consulta.lote.status', ['id' => $lote->id]) }}"
    data-resultados-url="{{ route('app.consulta.lote.resultados', ['id' => $lote->id]) }}"
    data-detail-url="{{ request()->fullUrlWithoutQuery(['page_resultados', 'per_page_resultados']) }}"
    data-await-result="{{ $aguardaPersistencia ? '1' : '0' }}"
    data-etapas="{{ $etapasJson }}"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="/app/consulta/historico" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Voltar para histórico
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Detalhe da Consulta</h1>
                <p class="text-xs text-gray-500 mt-1">O lote abre nesta página e acompanha o processamento até a disponibilidade do resultado final.</p>
                @if($lote->parent_lote_id || $lote->retryLotes()->count() > 0)
                    <div class="flex items-center flex-wrap gap-1.5 mt-2">
                        @if($lote->parent_lote_id)
                            <a href="/app/consulta/lote/{{ $lote->parent_lote_id }}" data-link class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white hover:opacity-80" style="background-color: #6366f1">↺ Retry do lote #{{ $lote->parent_lote_id }}</a>
                        @endif
                        @php $countRetries = $lote->retryLotes()->count(); @endphp
                        @if($countRetries > 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0891b2">{{ $countRetries }} retry{{ $countRetries === 1 ? '' : 's' }} derivado{{ $countRetries === 1 ? '' : 's' }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($statusLote === 'finalizado')
                    <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=csv" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">CSV</a>
                    @if($lote->hasResultados())
                        <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=pdf" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">PDF</a>
                    @endif
                @endif
                @if(in_array($statusLote, ['finalizado', 'erro'], true))
                    <button type="button" data-retry-trigger="{{ $lote->id }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium hidden">
                        Retentar pendentes
                    </button>
                @endif
                <a href="/app/consulta/nova" data-link class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">Nova consulta</a>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusMeta['hex'] }}">
                    {{ $statusMeta['label'] }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Lote</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-lg font-bold text-gray-900">#{{ $lote->id }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $lote->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Produto</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $lote->plano?->nome ?? 'Sem plano' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $lote->plano?->codigo ?? '—' }}</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($lote->total_participantes ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">incluídos no lote</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($lote->creditos_cobrados ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">debitados no disparo</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $lote->cliente?->razao_social ?? 'Não informado' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">vínculo do lote</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processado em</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $lote->processado_em?->format('d/m/Y H:i') ?? 'Em andamento' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">última atualização persistida</p>
                </div>
            </div>
        </div>

        @if(in_array($statusLote, ['pendente', 'processando'], true))
            <div id="consulta-lote-progresso-card" class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Andamento da Consulta</span>
                    <span id="consulta-lote-percent" class="text-[10px] text-gray-500 font-mono">0%</span>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <p id="consulta-lote-mensagem" class="text-sm text-gray-700">Iniciando consulta...</p>
                    </div>
                    <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: #e5e7eb">
                        <div id="consulta-lote-bar" class="h-full" style="background-color: #1f2937; width: 6%; transition: width 350ms ease-out"></div>
                    </div>
                    <p id="consulta-lote-etapa" class="text-xs text-gray-600 mt-3 hidden"></p>
                    <div id="consulta-lote-steps" class="mt-3 flex flex-wrap gap-2">
                        @foreach(($etapas ?? []) as $etapa)
                            <div class="etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="{{ $etapa['numero'] ?? '' }}">
                                <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </span>
                                <span>{{ $etapa['label'] ?? ('Etapa '.($etapa['numero'] ?? '?')) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 p-6 text-center">
                <p class="text-sm text-gray-500">O resultado consolidado aparecerá aqui assim que o processamento terminar.</p>
                <p class="text-xs text-gray-400 mt-1">Você pode fechar esta aba e voltar depois pelo histórico sem perder o lote.</p>
            </div>
        @endif

        @if($statusLote === 'erro')
            @include('autenticado.partials.system-critical-error', ['errorUi' => $erroCriticoLote])
        @endif

        @if($statusLote === 'finalizado' && $aguardaPersistencia)
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Aguardando resultado final</p>
                <p class="mt-2 text-sm text-gray-700">A consulta já foi concluída pelo provedor, mas o resultado consolidado ainda não ficou disponível. Esta página continuará verificando automaticamente.</p>
            </div>
        @endif

        @if($statusLote === 'finalizado' && ! $aguardaPersistencia)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado Consolidado</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total do lote</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['total_lote'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Resultados</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['total_resultados'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sucesso</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['sucesso'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Falha</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['erro'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Com Sinalização</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['com_parecer'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            @if($temResultadosNoLote)
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participantes Consultados</span>
                            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ number_format((int) $resultados->total(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] text-gray-500 uppercase tracking-wide">20 por página</span>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                                    <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Situação</th>
                                    <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Regime Tributário</th>
                                    <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">CND Federal</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">FGTS</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNDT</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Sinalizações</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Consultado em</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($resultados as $resultado)
                                    @php
                                        $regimeTributario = $resultado['regime_tributario'] ?? null;
                                        $cndFederal = $resultado['cnd_federal_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                                        $fgts = $resultado['fgts_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                                        $cndt = $resultado['cndt_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-3 py-3">
                                            <div class="text-sm text-gray-900">
                                                @if(!empty($resultado['participante_id']))
                                                    <a href="/app/participante/{{ $resultado['participante_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">
                                                        {{ $resultado['razao_social'] ?: 'Sem razão social' }}
                                                    </a>
                                                @else
                                                    {{ $resultado['razao_social'] ?: 'Sem razão social' }}
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-500 mt-1 font-mono">
                                                {{ $resultado['documento_formatado'] ?: '—' }}@if(!empty($resultado['uf'])) · {{ $resultado['uf'] }}@endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700 text-center">{{ $resultado['situacao_cadastral'] ?: '—' }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-700 text-center whitespace-nowrap">{{ $regimeTributario ?: '—' }}</td>
                                        <td class="px-3 py-3 text-center"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cndFederal['hex'] }}">{{ $cndFederal['label'] }}</span></td>
                                        <td class="px-3 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $fgts['hex'] }}">{{ $fgts['label'] }}</span></td>
                                        <td class="px-3 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cndt['hex'] }}">{{ $cndt['label'] }}</span></td>
                                        <td class="px-3 py-3">
                                            @if(!empty($resultado['parecer']))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($resultado['parecer'] as $parecer)
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $parecer['hex'] ?? '#374151' }}" title="{{ $parecer['tooltip'] ?? ($parecer['descricao'] ?? '') }}">
                                                            {{ $parecer['badge_label'] ?? ($parecer['titulo'] ?? ($parecer['chave'] ?? 'Parecer')) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $resultado['status_hex'] }}">
                                                {{ $resultado['status_label'] }}
                                            </span>
                                            @if(!empty($resultado['mensagem_exibivel']))
                                                <p class="text-[11px] text-gray-500 mt-1">{{ $resultado['mensagem_exibivel'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700">{{ $resultado['consultado_em_label'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach($resultados as $resultado)
                            @php
                                $regimeTributario = $resultado['regime_tributario'] ?? null;
                                $cndFederal = $resultado['cnd_federal_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                                $fgts = $resultado['fgts_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                                $cndt = $resultado['cndt_badge'] ?? ['label' => '—', 'hex' => '#9ca3af'];
                            @endphp
                            <div class="px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 font-medium">{{ $resultado['razao_social'] ?: 'Sem razão social' }}</p>
                                        <p class="text-[11px] text-gray-500 mt-1 font-mono">{{ $resultado['documento_formatado'] ?: '—' }}@if(!empty($resultado['uf'])) · {{ $resultado['uf'] }}@endif</p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $resultado['status_hex'] }}">{{ $resultado['status_label'] }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 mt-3 text-sm text-gray-700">
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase">Situação</p>
                                        <p>{{ $resultado['situacao_cadastral'] ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">Consultado em</p>
                                        <p>{{ $resultado['consultado_em_label'] ?: '—' }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase whitespace-nowrap">Regime Tributário</p>
                                        <p>{{ $regimeTributario ?: '—' }}</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[10px] text-gray-400 uppercase whitespace-nowrap">CND Federal</p>
                                        <div class="mt-1 flex justify-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cndFederal['hex'] }}">{{ $cndFederal['label'] }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">FGTS</p>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $fgts['hex'] }}">{{ $fgts['label'] }}</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">CNDT</p>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cndt['hex'] }}">{{ $cndt['label'] }}</span>
                                    </div>
                                </div>
                                @if(!empty($resultado['parecer']))
                                    <div class="flex flex-wrap gap-1 mt-3">
                                        @foreach($resultado['parecer'] as $parecer)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $parecer['hex'] ?? '#374151' }}" title="{{ $parecer['tooltip'] ?? ($parecer['descricao'] ?? '') }}">
                                                {{ $parecer['badge_label'] ?? ($parecer['titulo'] ?? ($parecer['chave'] ?? 'Parecer')) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if(!empty($resultado['mensagem_exibivel']))
                                    <p class="text-xs text-gray-500 mt-3">{{ $resultado['mensagem_exibivel'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-300 px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                        <span class="text-[10px] text-gray-500 uppercase tracking-wide">
                            Mostrando {{ $resultados->firstItem() }}–{{ $resultados->lastItem() }} de {{ $resultados->total() }} participantes
                        </span>
                        <div class="flex items-center gap-2">
                            @if($resultados->onFirstPage())
                                <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Anterior</span>
                            @else
                                <a href="{{ $resultados->previousPageUrl() }}" data-link data-consulta-lote-pagination class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</a>
                            @endif

                            <span class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $resultados->currentPage() }} / {{ $resultados->lastPage() }}</span>

                            @if($resultados->hasMorePages())
                                <a href="{{ $resultados->nextPageUrl() }}" data-link data-consulta-lote-pagination class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Próxima</a>
                            @else
                                <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Próxima</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sem resultados individuais</p>
                    <p class="mt-2 text-sm text-gray-700">O lote foi finalizado, mas ainda não há linhas individuais disponíveis para exibição.</p>
                </div>
            @endif
        @endif
    </div>
</div>

@if(in_array($statusLote, ['finalizado', 'erro'], true))
    <div id="retry-pendentes-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col">
            <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Retentar pendentes do lote #{{ $lote->id }}</h2>
                    <p class="text-[11px] text-gray-500 mt-0.5" data-retry-summary>Carregando pendentes...</p>
                </div>
                <button type="button" data-retry-close class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-3" data-retry-body>
                <div class="text-center py-8 text-sm text-gray-500" data-retry-loading>Carregando...</div>
                <div class="hidden" data-retry-empty>
                    <p class="text-sm text-gray-700">Não há participantes pendentes neste lote.</p>
                </div>
                <div class="hidden" data-retry-error>
                    <p class="text-sm text-red-600" data-retry-error-msg></p>
                </div>
                <div class="hidden" data-retry-list>
                    <div class="flex items-center justify-between mb-2">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                            <input type="checkbox" data-retry-toggle-all checked class="rounded border-gray-300">
                            Selecionar todos
                        </label>
                        <span class="text-[10px] text-gray-500 uppercase tracking-wide" data-retry-selected-count>0 selecionados</span>
                    </div>
                    <div class="border border-gray-200 rounded divide-y divide-gray-100" data-retry-items></div>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50">
                <div class="grid grid-cols-2 gap-3 mb-3 text-xs">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Custo do retry</p>
                        <p class="text-sm font-bold text-gray-900" data-retry-custo>—</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Saldo atual</p>
                        <p class="text-sm font-bold text-gray-900" data-retry-saldo>—</p>
                    </div>
                </div>
                <p class="text-[11px] text-red-600 hidden mb-2" data-retry-saldo-aviso>Saldo insuficiente para esse retry.</p>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-retry-close class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">Cancelar</button>
                    <button type="button" data-retry-confirm class="px-4 py-1.5 bg-gray-900 text-white hover:bg-gray-700 rounded text-xs font-medium disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>Confirmar retry</button>
                </div>
            </div>
        </div>
    </div>
@endif

<script src="/js/consulta-lote-detalhe.js?v={{ $jsVersion }}"></script>
<script>
(function() {
    const scrollKey = 'consulta-lote-detalhe-scroll-y';

    function storePaginationScroll() {
        try {
            window.sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
        } catch (_) {}
    }

    function restorePaginationScroll() {
        try {
            const raw = window.sessionStorage.getItem(scrollKey);
            if (raw === null) {
                return;
            }

            window.sessionStorage.removeItem(scrollKey);

            const scrollY = parseInt(raw, 10);
            if (Number.isNaN(scrollY) || scrollY < 0) {
                return;
            }

            window.requestAnimationFrame(function() {
                window.scrollTo(0, scrollY);
            });
        } catch (_) {}
    }

    window.storeConsultaLoteScroll = storePaginationScroll;

    document.querySelectorAll('[data-consulta-lote-pagination]').forEach(function(link) {
        link.addEventListener('click', storePaginationScroll);
    });

    restorePaginationScroll();

    function tryInit(attempts) {
        if (typeof window.initConsultaLoteDetalhe === 'function') {
            window.initConsultaLoteDetalhe();
        } else if (attempts < 50) {
            setTimeout(function() { tryInit(attempts + 1); }, 100);
        }
    }

    tryInit(0);
})();
</script>

@if(in_array($statusLote, ['finalizado', 'erro'], true))
<script>
(function() {
    const trigger = document.querySelector('[data-retry-trigger="{{ $lote->id }}"]');
    const modal = document.getElementById('retry-pendentes-modal');
    if (!trigger || !modal) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const pendentesUrl = "{{ route('app.consulta.lote.pendentes', ['id' => $lote->id]) }}";
    const retentarUrl = "{{ route('app.consulta.lote.retentar', ['id' => $lote->id]) }}";

    const summary = modal.querySelector('[data-retry-summary]');
    const loading = modal.querySelector('[data-retry-loading]');
    const empty = modal.querySelector('[data-retry-empty]');
    const errorBox = modal.querySelector('[data-retry-error]');
    const errorMsg = modal.querySelector('[data-retry-error-msg]');
    const list = modal.querySelector('[data-retry-list]');
    const itemsContainer = modal.querySelector('[data-retry-items]');
    const toggleAll = modal.querySelector('[data-retry-toggle-all]');
    const selectedCount = modal.querySelector('[data-retry-selected-count]');
    const custoEl = modal.querySelector('[data-retry-custo]');
    const saldoEl = modal.querySelector('[data-retry-saldo]');
    const saldoAviso = modal.querySelector('[data-retry-saldo-aviso]');
    const confirmBtn = modal.querySelector('[data-retry-confirm]');
    const closeBtns = modal.querySelectorAll('[data-retry-close]');

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

                summary.textContent = `${pendentesData.length} participante${pendentesData.length === 1 ? '' : 's'} pendente${pendentesData.length === 1 ? '' : 's'} ou com falha`;
                renderList();
                showState('list');
            })
            .catch(function(err) {
                summary.textContent = 'Erro ao carregar.';
                errorMsg.textContent = 'Não foi possível carregar os pendentes. Tente novamente.';
                showState('error');
            });
    }

    function statusMeta(status) {
        const map = {
            'pendente': { label: 'Pendente', hex: '#9ca3af' },
            'erro': { label: 'Erro', hex: '#dc2626' },
            'timeout': { label: 'Timeout', hex: '#f59e0b' },
        };
        return map[status] || { label: status || '—', hex: '#6b7280' };
    }

    function formatCnpj(raw) {
        if (!raw) return '—';
        const digits = String(raw).replace(/\D/g, '');
        if (digits.length !== 14) return raw;
        return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }

    function renderList() {
        itemsContainer.innerHTML = '';
        pendentesData.forEach(function(p) {
            const meta = statusMeta(p.status);
            const row = document.createElement('label');
            row.className = 'flex items-start gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer';
            row.innerHTML = `
                <input type="checkbox" data-retry-item="${p.id}" checked class="mt-1 rounded border-gray-300">
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 truncate">${escapeHtml(p.razao_social || 'Sem razão social')}</p>
                    <p class="text-[11px] text-gray-500 font-mono">${escapeHtml(formatCnpj(p.cnpj))}${p.uf ? ' · ' + escapeHtml(p.uf) : ''}</p>
                    ${p.error_message ? `<p class="text-[11px] text-red-600 mt-0.5">${escapeHtml(p.error_message)}</p>` : ''}
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wide text-white px-2 py-0.5 rounded shrink-0" style="background-color: ${meta.hex}">${escapeHtml(meta.label)}</span>
            `;
            itemsContainer.appendChild(row);
        });

        itemsContainer.querySelectorAll('[data-retry-item]').forEach(function(cb) {
            cb.addEventListener('change', updateCusto);
        });
        updateCusto();
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function getSelectedIds() {
        return Array.from(itemsContainer.querySelectorAll('[data-retry-item]:checked'))
            .map(function(cb) { return parseInt(cb.dataset.retryItem, 10); })
            .filter(function(v) { return !isNaN(v); });
    }

    function updateCusto() {
        const selected = getSelectedIds();
        const custo = selected.length * custoUnitario;
        selectedCount.textContent = `${selected.length} selecionado${selected.length === 1 ? '' : 's'}`;
        custoEl.textContent = selected.length === 0 ? '—' : formatBRL(custo);

        const insufficient = selected.length > 0 && custo > saldoAtual;
        saldoAviso.classList.toggle('hidden', !insufficient);
        confirmBtn.disabled = selected.length === 0 || insufficient;
    }

    toggleAll.addEventListener('change', function() {
        itemsContainer.querySelectorAll('[data-retry-item]').forEach(function(cb) {
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
                participante_ids: selected,
                tab_id: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(36).slice(2, 10),
            }),
        })
            .then(function(r) {
                return r.json().then(function(data) { return { ok: r.ok, data: data }; });
            })
            .then(function(res) {
                if (!res.ok || res.data?.success === false) {
                    throw new Error(res.data?.error || res.data?.message || 'Falha ao disparar retry.');
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
