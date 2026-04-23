@php
    $notaResultado = $notaResultado ?? null;
    $statusMeta = $statusMeta ?? ['label' => 'Pendente', 'hex' => '#9ca3af'];
    $tipoDocumento = strtoupper((string) ($tipoDocumento ?? 'NFE'));
    $chaveConsultada = $chaveConsultada ?? null;
    $aguardaPersistencia = (bool) ($aguardaPersistencia ?? false);
    $statusLote = \App\Models\ConsultaLote::normalizeStatus($lote->status ?? 'pendente');
    $erroCriticoLote = $lote->publicErrorUi([
        'context' => 'clearance-busca-avulsa',
        'url' => request()->getPathInfo(),
    ]);
@endphp

<div
    class="min-h-screen bg-gray-100"
    id="clearance-resultado-root"
    data-status="{{ $statusLote }}"
    data-tab-id="{{ $lote->tab_id ?? '' }}"
    data-stream-url="{{ url('/app/consulta/progresso/stream') }}"
    data-json-url="{{ route('app.clearance.buscar.resultado', ['consultaLoteId' => $lote->id, 'tipo_documento' => strtolower($tipoDocumento), 'chave_acesso' => $chaveConsultada]) }}"
    data-await-result="{{ $aguardaPersistencia ? '1' : '0' }}"
    data-poll-result="1"
    data-progress-snapshot='@json($progressSnapshot ?? null)'
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="/app/clearance/buscar" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Voltar para busca avulsa
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Resultado da Busca de Nota</h1>
                <p class="text-xs text-gray-500 mt-1">A consulta abre nesta página e acompanha o processamento até a finalização do DF-e.</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start" style="background-color: {{ $statusMeta['hex'] }}">
                {{ $statusMeta['label'] }}
            </span>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Consulta</p>
                    <p class="text-lg font-bold text-gray-900">#{{ $lote->id }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">busca avulsa</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Documento</p>
                    <p class="text-lg font-bold text-gray-900">{{ $tipoDocumento === 'CTE' ? 'CT-e' : 'NF-e' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">consulta unitária</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente</p>
                    <p class="text-sm font-bold text-gray-900">{{ $lote->cliente?->razao_social ?? 'Não informado' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">vínculo obrigatório</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($lote->creditos_cobrados ?? 0), 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">debitados nesta consulta</p>
                </div>
            </div>
        </div>

        @if(in_array($statusLote, ['pendente', 'processando'], true))
            <div id="clearance-resultado-progresso" class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Andamento</span>
                    <span id="clearance-resultado-percent" class="text-[10px] text-gray-500 font-mono">0%</span>
                </div>
                <div class="p-4">
                    <p id="clearance-resultado-mensagem" class="text-sm text-gray-600 mb-1">Iniciando consulta...</p>
                    <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: #e5e7eb">
                        <div id="clearance-resultado-bar" class="h-full" style="background-color: #1f2937; width: 8%; transition: width 350ms ease-out"></div>
                    </div>
                    <p id="clearance-resultado-etapa-label" class="text-[11px] text-gray-500 mt-2 hidden"></p>
                    <div id="clearance-resultado-steps" class="hidden mt-3 flex flex-wrap gap-2"></div>
                </div>
            </div>
        @endif

        @if($statusLote === 'erro')
            @include('autenticado.partials.system-critical-error', ['errorUi' => $erroCriticoLote])
        @elseif($notaResultado)
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado Final</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start sm:self-auto" style="background-color: {{ $notaResultado['situacao_hex'] ?? '#374151' }}">
                        {{ $notaResultado['situacao'] ?? 'INDETERMINADO' }}
                    </span>
                </div>
                <div class="p-4 space-y-3">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                        <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">{{ $notaResultado['tipo_documento'] ?? $tipoDocumento }}</p>
                        </div>
                        <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">{{ $notaResultado['situacao'] ?? '—' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor total</p>
                            <p class="text-sm font-bold text-gray-900 font-mono mt-1 whitespace-nowrap">{{ $notaResultado['valor_total_label'] ?? '—' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emissão</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">{{ $notaResultado['data_emissao'] ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                        <div class="border border-gray-200 rounded p-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emitente</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $notaResultado['emit'] ?? '—' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded p-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Destinatário / Tomador</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $notaResultado['dest'] ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[1fr,240px] gap-2">
                        <div class="border border-gray-200 rounded p-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Chave consultada</p>
                            <p class="text-xs text-gray-900 font-mono break-all mt-1">{{ $notaResultado['nfe_id'] ?? $chaveConsultada ?? '—' }}</p>
                        </div>
                        <div class="border border-gray-200 rounded p-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente associado</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $notaResultado['cliente_nome'] ?? ($lote->cliente?->razao_social ?? '—') }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 pt-1">
                        @if(!empty($notaResultado['detalhe_url']))
                            <a href="{{ $notaResultado['detalhe_url'] }}" data-link class="px-4 py-2 rounded text-sm font-medium text-white text-center" style="background-color: #374151">Ver detalhe do documento</a>
                        @endif
                        <a href="/app/clearance/buscar" data-link class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-sm font-medium text-center">Nova busca</a>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Aguardando persistência</p>
                <p class="mt-2 text-sm text-gray-700">O provedor finalizou a consulta, mas o resultado final ainda não apareceu no acervo/tabelas canônicas. Esta página continuará tentando carregar o retorno.</p>
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/clearance-resultado.js') }}?v={{ @filemtime(public_path('js/clearance-resultado.js')) ?: time() }}" defer></script>
