@php
    $resultados = $resultados ?? collect();
    $resumo = $resumo ?? ['total' => 0, 'autorizadas' => 0, 'alertas' => 0, 'indeterminadas' => 0, 'erros' => 0];
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
@endphp

<div
    class="min-h-screen bg-gray-100"
    id="clearance-resultado-root"
    data-status="{{ $statusLote }}"
    data-tab-id="{{ $lote->tab_id ?? '' }}"
    data-stream-url="{{ url('/app/consulta/progresso/stream') }}"
    data-json-url="{{ route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->id, 'tipo_validacao' => $tipoValidacao]) }}"
    data-await-result="{{ $aguardaPersistencia ? '1' : '0' }}"
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
                <p class="text-xs text-gray-500 mt-1">Esta página concentra o andamento do lote e recarrega o resultado final quando o provedor finalizar.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
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

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Lote</p>
                    <p class="text-lg font-bold text-gray-900">#{{ $lote->id }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">clearance em lote</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas enviadas</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($lote->total_participantes ?? 0), 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">documentos no lote</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($lote->creditos_cobrados ?? 0), 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">debitados no disparo</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processado em</p>
                    <p class="text-sm font-bold text-gray-900">{{ $lote->processado_em?->format('d/m/Y H:i') ?? 'Em andamento' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">última atualização do lote</p>
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
            @include('autenticado.partials.system-critical-error', ['errorUi' => $erroCriticoLote])
        @endif

        @if($statusLote === 'finalizado')
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado Consolidado</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['total'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Autorizadas</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['autorizadas'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Alertas</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['alertas'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Indeterminadas</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['indeterminadas'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Erros</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($resumo['erros'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            @if($resultados->isNotEmpty())
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Documento</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Destinatário / Tomador</th>
                                    <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Consultado em</th>
                                    <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($resultados as $resultado)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $resultado->tipo_documento }}</span>
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #4b5563">{{ $resultado->modelo }}</span>
                                            </div>
                                            <p class="text-sm text-gray-900 mt-1">Nº {{ $resultado->numero ?: '—' }} / Série {{ $resultado->serie ?: '—' }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $resultado->chave_acesso }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700">{{ $resultado->emit_nome ?: $resultado->emit_cnpj ?: '—' }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-700">{{ $resultado->participante_label }}</td>
                                        <td class="px-3 py-3 text-sm font-semibold text-gray-900 text-right font-mono whitespace-nowrap">{{ $resultado->valor_total_label }}</td>
                                        <td class="px-3 py-3">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $resultado->status_hex }}">{{ $resultado->status_label }}</span>
                                            @if($resultado->data_emissao_label)
                                                <p class="text-[10px] text-gray-500 mt-1">Emissão {{ $resultado->data_emissao_label }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700">{{ $resultado->consultado_em_label ?: '—' }}</td>
                                        <td class="px-3 py-3 text-right whitespace-nowrap">
                                            @if($resultado->detalhe_url)
                                                <a href="{{ $resultado->detalhe_url }}" data-link class="text-xs text-gray-700 hover:text-gray-900 hover:underline whitespace-nowrap">Ver documento</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Aguardando retorno final</p>
                    <p class="mt-2 text-sm text-gray-700">O lote foi aceito, mas os registros finais ainda não apareceram nas tabelas de snapshot do clearance. Esta página continuará verificando o resultado.</p>
                </div>
            @endif
        @endif
    </div>
</div>

<script src="{{ asset('js/clearance-resultado.js') }}?v={{ @filemtime(public_path('js/clearance-resultado.js')) ?: time() }}" defer></script>
