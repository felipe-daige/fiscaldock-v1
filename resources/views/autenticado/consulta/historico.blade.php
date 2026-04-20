@php
    $buscaFiltro = $filtros['busca'] ?? '';
    $statusFiltro = $filtros['status'] ?? '';
    $planoFiltro = $filtros['plano_id'] ?? '';
    $dataInicio = $filtros['data_inicio'] ?? '';
    $dataFim = $filtros['data_fim'] ?? '';
@endphp

<div class="bg-gray-100 min-h-screen" id="consultas-historico-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="flex items-start justify-between gap-4 mb-4 sm:mb-6">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Histórico de Consultas</h1>
                <p class="text-xs text-gray-500 mt-1">Consolidado dos lotes executados, créditos consumidos e relatórios disponíveis para exportação.</p>
            </div>
            <a
                href="/app/consulta/nova"
                class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                data-link
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova Consulta
            </a>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Consultas</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['total_lotes'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">lotes filtrados</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['total_participantes'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">processados</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['total_creditos'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">consumidos</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Concluídas</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['concluidos'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">com relatório</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processando</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['processando'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">em andamento</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Erro</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['erro'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">lotes com falha</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 hidden sm:block">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
                    @if(($filtrosAtivos ?? 0) > 0)
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $filtrosAtivos }} ativos</span>
                    @endif
                </div>
            </div>
            <form method="GET" action="/app/consulta/historico">
                <div class="px-4 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                        <input
                            type="text"
                            name="busca"
                            value="{{ $buscaFiltro }}"
                            placeholder="Buscar lote, produto ou erro..."
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 xl:col-span-2"
                        >
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <option value="">Todos os status</option>
                            <option value="pendente" {{ $statusFiltro === 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="processando" {{ $statusFiltro === 'processando' ? 'selected' : '' }}>Processando</option>
                            <option value="concluido" {{ $statusFiltro === 'concluido' ? 'selected' : '' }}>Concluído</option>
                            <option value="erro" {{ $statusFiltro === 'erro' ? 'selected' : '' }}>Erro</option>
                        </select>
                        <select name="plano_id" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            <option value="">Todos os produtos</option>
                            @foreach(($planosFiltro ?? collect()) as $plano)
                                <option value="{{ $plano->id }}" {{ (string) $planoFiltro === (string) $plano->id ? 'selected' : '' }}>{{ $plano->nome }}</option>
                            @endforeach
                        </select>
                        <input
                            type="date"
                            name="data_inicio"
                            value="{{ $dataInicio }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        >
                        <input
                            type="date"
                            name="data_fim"
                            value="{{ $dataFim }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        >
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 pt-3 border-t border-gray-200">
                        <div class="text-xs text-gray-500">
                            {{ $lotes->total() }} lote{{ $lotes->total() === 1 ? '' : 's' }} encontrado{{ $lotes->total() === 1 ? '' : 's' }}
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium">
                                Filtrar
                            </button>
                            <a href="/app/consulta/historico" data-link class="flex-1 sm:flex-none px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium text-center">
                                Limpar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if($lotes->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consultas Recentes</span>
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">CSV + PDF</span>
                    </div>
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Lote / Data</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Produto</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participantes</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Créditos</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($lotes as $lote)
                                @php
                                    $statusMeta = match($lote->status) {
                                        'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                        'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                        'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                        default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-3">
                                        <div class="text-sm text-gray-900 font-medium">Lote #{{ $lote->id }}</div>
                                        <div class="text-[11px] text-gray-500 mt-1">{{ $lote->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        <div class="text-gray-900">{{ $lote->plano?->nome ?? 'Sem plano' }}</div>
                                        @if($lote->processado_em)
                                            <div class="text-[11px] text-gray-500 mt-1">Processado em {{ $lote->processado_em->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ number_format($lote->total_participantes, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-sm font-semibold text-gray-900 font-mono">
                                        {{ number_format($lote->creditos_cobrados, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusMeta['hex'] }}">{{ $statusMeta['label'] }}</span>
                                        @if($lote->isErro() && $lote->error_code)
                                            <div class="text-[11px] text-gray-500 mt-1">{{ $lote->error_code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        @if($lote->isConcluido())
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=csv" class="text-xs text-gray-900 hover:text-gray-600 hover:underline">CSV</a>
                                                @if($lote->hasResultados())
                                                    <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=pdf" class="text-xs text-gray-900 hover:text-gray-600 hover:underline">PDF</a>
                                                @endif
                                            </div>
                                        @elseif($lote->isErro())
                                            <span class="text-xs text-gray-500" title="{{ $lote->error_message }}">Falha no processamento</span>
                                        @else
                                            <span class="text-xs text-gray-400">Aguardando</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach($lotes as $lote)
                        @php
                            $statusMeta = match($lote->status) {
                                'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                            };
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] text-gray-400 uppercase">Lote</p>
                                    <p class="text-sm text-gray-900 font-medium">#{{ $lote->id }} · {{ $lote->plano?->nome ?? 'Sem plano' }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusMeta['hex'] }}">{{ $statusMeta['label'] }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3 text-sm text-gray-700">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Data</p>
                                    <p>{{ $lote->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Participantes</p>
                                    <p>{{ number_format($lote->total_participantes, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Créditos</p>
                                    <p class="font-mono text-gray-900">{{ number_format($lote->creditos_cobrados, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Ações</p>
                                    <div class="flex items-center gap-3">
                                        @if($lote->isConcluido())
                                            <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=csv" class="text-xs text-gray-600 hover:text-gray-900 hover:underline">CSV</a>
                                            @if($lote->hasResultados())
                                                <a href="/app/consulta/lote/{{ $lote->id }}/baixar?formato=pdf" class="text-xs text-gray-600 hover:text-gray-900 hover:underline">PDF</a>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">Indisponível</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($lote->isErro() && $lote->error_message)
                                <p class="text-xs text-gray-500 mt-3">{{ $lote->error_message }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($lotes->hasPages())
                    <div class="border-t border-gray-300 px-4 py-3">
                        {{ $lotes->links() }}
                    </div>
                @endif
            </div>
        @elseif($relatoriosLegados->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Relatórios Legados</span>
                </div>
                <div class="px-4 py-6 text-sm text-gray-700">
                    Há apenas relatórios do sistema antigo disponíveis no momento.
                </div>
            </div>
        @else
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="px-6 py-10 text-center">
                    <svg class="w-14 h-14 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 uppercase tracking-wide">Nenhuma consulta registrada</h3>
                    <p class="text-sm text-gray-600 mt-2 mb-6">Execute sua primeira consulta em lote para começar a consolidar o histórico de relatórios e créditos consumidos.</p>
                    <a href="/app/consulta/nova" data-link class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Iniciar Consulta
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
