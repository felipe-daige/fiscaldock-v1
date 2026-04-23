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
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($statusLote === 'finalizado')
                    <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=csv" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">CSV</a>
                    @if($lote->hasResultados())
                        <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=pdf" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">PDF</a>
                    @endif
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
