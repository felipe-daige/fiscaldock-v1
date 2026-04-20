{{-- Dashboard de Notas Fiscais --}}
<div class="min-h-screen bg-gray-100" id="dashboard-nf-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <style>
            .dnf-skeleton {
                background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
                background-size: 200% 100%;
                animation: dnf-shimmer 1.5s infinite;
                border-radius: 0.25rem;
            }
            @keyframes dnf-shimmer {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
        </style>

        {{-- Page Header --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Dashboard de Notas Fiscais</h1>
                    <p class="mt-0.5 text-xs text-gray-500">Análise consolidada das notas fiscais importadas</p>
                </div>
            </div>
        </div>

        {{-- Filtros Globais --}}
        <div class="bg-white rounded border border-gray-300 p-4 sm:p-5 mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3">
                {{-- Periodo Inicio --}}
                <div class="flex-1 min-w-[140px]">
                    <label for="dnf-periodo-inicio" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Período Início</label>
                    <input type="month" id="dnf-periodo-inicio" value="{{ $filtros['periodo_inicio'] }}"
                           class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>

                {{-- Periodo Fim --}}
                <div class="flex-1 min-w-[140px]">
                    <label for="dnf-periodo-fim" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Período Fim</label>
                    <input type="month" id="dnf-periodo-fim" value="{{ $filtros['periodo_fim'] }}"
                           class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>

                {{-- Cliente --}}
                <div class="flex-1 min-w-[180px]">
                    <label for="dnf-cliente" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                    <select id="dnf-cliente"
                            class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os Clientes</option>
                        @foreach($clientes ?? [] as $cliente)
                            @if($cliente->is_empresa_propria)
                                <option value="{{ $cliente->id }}">★ {{ $cliente->razao_social ?? $cliente->nome }} (Minha Empresa)</option>
                            @else
                                <option value="{{ $cliente->id }}">{{ $cliente->razao_social ?? $cliente->nome }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Participante --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="dnf-participante" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Participante</label>
                    <select id="dnf-participante"
                            class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os Participantes</option>
                        @foreach($participantes ?? [] as $part)
                            <option value="{{ $part->id }}">{{ $part->razao_social }} ({{ $part->documento }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo EFD --}}
                <div class="flex-1 min-w-[160px]">
                    <label for="dnf-tipo-efd" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo EFD</label>
                    <select id="dnf-tipo-efd"
                            class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="todos" {{ $filtros['tipo_efd'] === 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="EFD ICMS/IPI" {{ $filtros['tipo_efd'] === 'EFD ICMS/IPI' ? 'selected' : '' }}>EFD ICMS/IPI</option>
                        <option value="EFD PIS/COFINS" {{ $filtros['tipo_efd'] === 'EFD PIS/COFINS' ? 'selected' : '' }}>EFD PIS/COFINS</option>
                    </select>
                </div>

                {{-- Importacao --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="dnf-importacao" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Importação</label>
                    <select id="dnf-importacao"
                            class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todas as importações</option>
                        @foreach($importacoes as $imp)
                            <option value="{{ $imp->id }}" {{ $filtros['importacao_id'] == $imp->id ? 'selected' : '' }}>
                                {{ $imp->filename }} ({{ $imp->tipo_efd }} &mdash; {{ $imp->concluido_em?->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Botao Filtrar --}}
                <div class="flex-shrink-0">
                    <button id="dnf-btn-filtrar"
                            class="w-full sm:w-auto px-5 py-2 bg-gray-800 text-white text-sm font-medium rounded hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-400 transition-colors">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabs de Navegacao --}}
        @php
            $defaultTab = 'visao-geral';
            $tabs = [
                'visao-geral' => ['label' => 'Visão Geral', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                'cfop' => ['label' => 'CFOP', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                'participantes' => ['label' => 'Participantes', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                'tributario' => ['label' => 'Tributário', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                'alertas' => ['label' => 'Alertas', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                'compliance' => ['label' => 'Compliance', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ];
            $tabClass = fn($tab) => $tab === $defaultTab
                ? 'dnf-tab active border-gray-800 text-gray-900 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm'
                : 'dnf-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm';
        @endphp

        <div class="mb-4 sm:mb-6" data-default-tab="{{ $defaultTab }}">
            <div class="border-b border-gray-200 scroll-fade-right sm:after:hidden">
                <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                    @foreach($tabs as $tabKey => $tab)
                        <button data-tab="{{ $tabKey }}" class="{{ $tabClass($tabKey) }}">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        {{-- Tab: Visao Geral --}}
        <div id="tab-visao-geral" class="dnf-tab-content">

            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-6 mb-4 sm:mb-6">
                {{-- Total Notas --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6 min-h-[88px] sm:min-h-[112px]">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2 whitespace-nowrap">Total de Notas</p>
                    <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-kpi-total">
                        <span class="dnf-skeleton inline-block w-16 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1 sm:mt-2 whitespace-nowrap">Notas no período</p>
                </div>

                {{-- Valor Entradas --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6 min-h-[88px] sm:min-h-[112px]">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2 whitespace-nowrap">Entradas</p>
                    <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-kpi-entradas">
                        <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1 sm:mt-2 whitespace-nowrap">Valor total de entradas</p>
                </div>

                {{-- Valor Saidas --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6 min-h-[88px] sm:min-h-[112px]">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2 whitespace-nowrap">Saídas</p>
                    <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-kpi-saidas">
                        <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1 sm:mt-2 whitespace-nowrap">Valor total de saídas</p>
                </div>

                {{-- Saldo --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6 min-h-[88px] sm:min-h-[112px]">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2 whitespace-nowrap">Saldo</p>
                    <p class="text-lg font-bold whitespace-nowrap" id="dnf-kpi-saldo">
                        <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1 sm:mt-2 whitespace-nowrap">Entradas - Saídas</p>
                </div>

                {{-- Participantes Unicos --}}
                <div class="col-span-2 lg:col-span-1 bg-white rounded border border-gray-300 p-3 sm:p-6 min-h-[88px] sm:min-h-[112px]">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2 whitespace-nowrap">Participantes</p>
                    <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-kpi-participantes">
                        <span class="dnf-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1 sm:mt-2 whitespace-nowrap">Únicos no período</p>
                </div>
            </div>

            {{-- Grafico de Evolucao Temporal --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6 mb-4 sm:mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Evolução Mensal — Entradas vs Saídas</h3>
                <div id="dnf-chart-evolucao" style="min-height: 320px;">
                    <div class="flex items-center justify-center h-64 text-gray-400">
                        <svg class="animate-spin h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Carregando...
                    </div>
                </div>
            </div>

            {{-- Breakdown por Tipo de Documento + Top 5 Participantes --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Tipo de Documento --}}
                <div class="bg-white rounded border border-gray-300 p-4 sm:p-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Notas Fiscais por Modelo</h3>
                    <div id="dnf-table-modelos">
                        <div class="space-y-3">
                            <div class="dnf-skeleton h-[180px] w-full">&nbsp;</div>
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        </div>
                    </div>
                </div>

                {{-- Top 5 Participantes --}}
                <div class="bg-white rounded border border-gray-300 p-4 sm:p-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Top 5 Participantes por Volume</h3>
                    <div id="dnf-table-participantes">
                        <div class="space-y-3">
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                            <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estado vazio (hidden por padrao) --}}
            <div id="dnf-empty-state" class="hidden">
                <div class="bg-white rounded border border-gray-300">
                    <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Nenhuma nota encontrada</p>
                        <p class="text-sm text-gray-400 mt-1">Importe arquivos EFD para visualizar os dados</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: CFOP --}}
        <div id="tab-cfop" class="dnf-tab-content hidden">

            {{-- KPI Cards Resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-6 mb-4 sm:mb-6">
                {{-- Entradas --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">CFOPs de Entrada</p>
                            <p class="text-xl sm:text-2xl font-semibold text-green-600 whitespace-nowrap" id="dnf-cfop-entradas-valor">
                                <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1 sm:mt-2" id="dnf-cfop-entradas-info">
                                <span class="dnf-skeleton inline-block w-32 h-4">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Saidas --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">CFOPs de Saída</p>
                            <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-cfop-saidas-valor">
                                <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1 sm:mt-2" id="dnf-cfop-saidas-info">
                                <span class="dnf-skeleton inline-block w-32 h-4">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtro local + Tabela --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-medium text-gray-700">Ranking de CFOPs</h3>
                        <div class="flex items-center gap-1 text-xs text-gray-400" id="dnf-cfop-sort-controls">
                            <span>Ordenar:</span>
                            <button data-cfop-sort="valor_total" class="cfop-sort-btn px-1.5 py-0.5 rounded text-gray-800 font-medium">Valor ↓</button>
                            <span>·</span>
                            <button data-cfop-sort="qtd_itens" class="cfop-sort-btn px-1.5 py-0.5 rounded text-gray-400 hover:text-gray-600">Qtd</button>
                            <span>·</span>
                            <button data-cfop-sort="cfop" class="cfop-sort-btn px-1.5 py-0.5 rounded text-gray-400 hover:text-gray-600">CFOP</button>
                        </div>
                    </div>
                    <div class="flex gap-1 bg-gray-100 rounded p-0.5" id="dnf-cfop-filtro-local">
                        <button data-cfop-filtro="todos" class="cfop-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-700 shadow-sm">Todos</button>
                        <button data-cfop-filtro="entrada" class="cfop-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700">Entradas</button>
                        <button data-cfop-filtro="saida" class="cfop-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700">Saídas</button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400 mb-3 pb-3 border-b border-gray-100">
                    <span>Barra = volume relativo de itens</span>
                    <span class="hidden sm:inline">&middot;</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded text-[10px] font-bold inline-flex items-center justify-center text-white" style="background-color: #047857">E</span> Entrada</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded text-[10px] font-bold inline-flex items-center justify-center text-white" style="background-color: #d97706">S</span> Saída</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded text-[10px] font-bold inline-flex items-center justify-center text-white" style="background-color: #d97706">D</span> Devolução</span>
                    <span class="inline-flex items-center gap-1"><span class="w-4 h-4 rounded text-[10px] font-bold inline-flex items-center justify-center text-white" style="background-color: #7c3aed">T</span> Transferência</span>
                </div>
                <div id="dnf-cfop-table">
                    <div class="space-y-3">
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                    </div>
                </div>
            </div>

            {{-- Estado vazio CFOP --}}
            <div id="dnf-cfop-empty" class="hidden">
                <div class="bg-white rounded border border-gray-300">
                    <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Nenhum CFOP encontrado</p>
                        <p class="text-sm text-gray-400 mt-1">Importe arquivos EFD para visualizar os dados</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Participantes --}}
        <div id="tab-participantes" class="dnf-tab-content hidden">

            {{-- KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                {{-- Total Participantes --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Total Participantes</p>
                            <p class="text-xl sm:text-2xl font-semibold text-gray-900 whitespace-nowrap" id="dnf-part-total">
                                <span class="dnf-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Fornecedores --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Fornecedores</p>
                            <p class="text-xl sm:text-2xl font-semibold text-green-600 whitespace-nowrap" id="dnf-part-fornecedores">
                                <span class="dnf-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Clientes --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Clientes</p>
                            <p class="text-lg font-bold text-gray-900 whitespace-nowrap" id="dnf-part-clientes">
                                <span class="dnf-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Concentracao --}}
            <div class="bg-white rounded border border-gray-300 border-l-4 border-l-amber-500 p-3 sm:p-4 mb-4 sm:mb-6" id="dnf-part-concentracao-card">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-amber-800" id="dnf-part-concentracao">
                        <span class="dnf-skeleton inline-block w-64 h-4">&nbsp;</span>
                    </p>
                </div>
            </div>

            {{-- Filtro local + Busca + Tabela --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h3 class="text-sm font-medium text-gray-700">Ranking de Participantes</h3>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        <input type="text" id="dnf-part-busca" placeholder="Buscar por nome ou CNPJ..."
                               class="px-3 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-full sm:w-56">
                        <div class="flex gap-1 bg-gray-100 rounded p-0.5" id="dnf-part-filtro-local">
                            <button data-part-filtro="todos" class="part-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-700 shadow-sm">Todos</button>
                            <button data-part-filtro="fornecedor" class="part-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700">Fornecedores</button>
                            <button data-part-filtro="cliente" class="part-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700">Clientes</button>
                        </div>
                    </div>
                </div>
                <div id="dnf-part-table">
                    <div class="space-y-3">
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                    </div>
                </div>
                <div id="dnf-part-paginacao" class="mt-4"></div>
            </div>

            {{-- Estado vazio --}}
            <div id="dnf-part-empty" class="hidden">
                <div class="bg-white rounded border border-gray-300">
                    <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Nenhum participante encontrado</p>
                        <p class="text-sm text-gray-400 mt-1">Importe arquivos EFD para visualizar os dados</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Tributário --}}
        <div id="tab-tributario" class="dnf-tab-content hidden">

            {{-- Alerta PIS/COFINS --}}
            <div id="dnf-trib-alerta" class="hidden bg-white rounded border border-gray-300 border-l-4 border-l-amber-500 p-3 sm:p-4 mb-4 sm:mb-6">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-sm text-amber-800">Dados de PIS/COFINS podem estar incompletos. Isso ocorre por uma limitação no processamento de arquivos EFD PIS/COFINS. Os valores de ICMS não são afetados.</p>
                </div>
            </div>

            {{-- KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                {{-- Saldo ICMS --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Saldo ICMS</p>
                            <p class="text-xl sm:text-2xl font-semibold whitespace-nowrap" id="dnf-trib-icms">
                                <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1 sm:mt-2" id="dnf-trib-icms-detalhe">
                                <span class="dnf-skeleton inline-block w-40 h-4">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Saldo PIS --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Saldo PIS</p>
                            <p class="text-xl sm:text-2xl font-semibold whitespace-nowrap" id="dnf-trib-pis">
                                <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1 sm:mt-2" id="dnf-trib-pis-detalhe">
                                <span class="dnf-skeleton inline-block w-40 h-4">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Saldo COFINS --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Saldo COFINS</p>
                            <p class="text-xl sm:text-2xl font-semibold whitespace-nowrap" id="dnf-trib-cofins">
                                <span class="dnf-skeleton inline-block w-24 h-7 sm:h-9">&nbsp;</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1 sm:mt-2" id="dnf-trib-cofins-detalhe">
                                <span class="dnf-skeleton inline-block w-40 h-4">&nbsp;</span>
                            </p>
                        </div>
                        <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gray-100 rounded flex items-center justify-center ml-2 sm:ml-4 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gráfico evolução ICMS --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6 mb-4 sm:mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Evolução Mensal — Débito vs Crédito ICMS</h3>
                <div id="dnf-trib-chart" style="min-height: 320px;">
                    <div class="flex items-center justify-center h-64 text-gray-400">
                        <svg class="animate-spin h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Carregando...
                    </div>
                </div>
            </div>

            {{-- Tabela consolidada por período --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6 mb-4 sm:mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Consolidado por Período</h3>
                <div id="dnf-trib-table-periodo">
                    <div class="space-y-3">
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                    </div>
                </div>
            </div>

            {{-- Análise por CST (colapsável) --}}
            <div class="bg-white rounded border border-gray-300 p-4 sm:p-6">
                <button id="dnf-trib-cst-toggle" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-sm font-medium text-gray-700">Análise por CST ICMS</h3>
                    <svg id="dnf-trib-cst-chevron" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="dnf-trib-cst-table" class="hidden mt-4">
                    <div class="space-y-3">
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                        <div class="dnf-skeleton h-8 w-full">&nbsp;</div>
                    </div>
                </div>
            </div>

            {{-- Estado vazio --}}
            <div id="dnf-trib-empty" class="hidden">
                <div class="bg-white rounded border border-gray-300">
                    <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Nenhum dado tributário encontrado</p>
                        <p class="text-sm text-gray-400 mt-1">Importe arquivos EFD para visualizar os dados</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Alertas --}}
        <div id="tab-alertas" class="dnf-tab-content hidden">

            {{-- Resumo por severidade --}}
            <div id="dnf-alertas-resumo" class="grid grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                {{-- Alta --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide tracking-wide">Alta</p>
                            <p class="text-2xl font-semibold text-red-600" id="dnf-alertas-alta">
                                <span class="dnf-skeleton inline-block w-8 h-7">&nbsp;</span>
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Media --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide tracking-wide">Média</p>
                            <p class="text-2xl font-semibold text-yellow-600" id="dnf-alertas-media">
                                <span class="dnf-skeleton inline-block w-8 h-7">&nbsp;</span>
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Baixa --}}
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide tracking-wide">Baixa</p>
                            <p class="text-2xl font-semibold text-gray-600" id="dnf-alertas-baixa">
                                <span class="dnf-skeleton inline-block w-8 h-7">&nbsp;</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lista de alertas --}}
            <div id="dnf-alertas-list" class="space-y-3">
                <div class="dnf-skeleton h-16 rounded">&nbsp;</div>
                <div class="dnf-skeleton h-16 rounded">&nbsp;</div>
                <div class="dnf-skeleton h-16 rounded">&nbsp;</div>
            </div>

            {{-- Empty state --}}
            <div id="dnf-alertas-empty" class="hidden">
                <div class="bg-white rounded border border-gray-300">
                    <div class="flex flex-col items-center justify-center py-16 sm:py-24 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Nenhum alerta encontrado</p>
                        <p class="text-sm text-gray-400 mt-1">Suas notas fiscais estão em conformidade</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Compliance --}}
        <div id="tab-compliance" class="dnf-tab-content hidden">
            {{-- 4 KPI cards de cobertura --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Consultados</p>
                    <p id="dnf-comp-consultados" class="text-lg sm:text-xl font-bold text-gray-900">
                        <span class="inline-block h-6 w-16 bg-gray-200 rounded animate-pulse"></span>
                    </p>
                    <p id="dnf-comp-consultados-pct" class="text-xs text-gray-400 mt-0.5">&mdash;</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Regulares</p>
                    <p id="dnf-comp-regulares" class="text-lg sm:text-xl font-bold text-green-600">
                        <span class="inline-block h-6 w-16 bg-gray-200 rounded animate-pulse"></span>
                    </p>
                    <p id="dnf-comp-regulares-pct" class="text-xs text-gray-400 mt-0.5">&mdash;</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Exposição fiscal</p>
                    <p id="dnf-comp-exposicao" class="text-lg sm:text-xl font-bold text-red-600">
                        <span class="inline-block h-6 w-16 bg-gray-200 rounded animate-pulse"></span>
                    </p>
                    <p id="dnf-comp-exposicao-sub" class="text-xs text-gray-400 mt-0.5">&mdash;</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-4">
                    <p class="text-xs text-gray-500 mb-1">Não consultados</p>
                    <p id="dnf-comp-nao-consultados" class="text-lg sm:text-xl font-bold text-yellow-600">
                        <span class="inline-block h-6 w-16 bg-gray-200 rounded animate-pulse"></span>
                    </p>
                    <p id="dnf-comp-nao-consultados-sub" class="text-xs text-gray-400 mt-0.5">&mdash;</p>
                </div>
            </div>

            {{-- Filtros locais + Botao lote --}}
            <div class="bg-white rounded border border-gray-300 p-3 sm:p-4 mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-wrap gap-2" id="dnf-comp-filtro-local">
                        <button data-filtro="todos" class="px-3 py-1.5 text-xs font-medium rounded text-white" style="background-color: #1f2937">Todos</button>
                        <button data-filtro="regulares" class="px-3 py-1.5 text-xs font-medium rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">Regulares</button>
                        <button data-filtro="irregulares" class="px-3 py-1.5 text-xs font-medium rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">Irregulares</button>
                        <button data-filtro="nao_consultados" class="px-3 py-1.5 text-xs font-medium rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">Não consultados</button>
                    </div>
                    <div class="flex gap-2">
                        <select id="dnf-comp-ordenar" class="text-xs border border-gray-300 rounded-md px-2 py-1.5">
                            <option value="volume">Maior volume</option>
                            <option value="consulta">Última consulta</option>
                            <option value="nome">Nome</option>
                        </select>
                        <button id="dnf-comp-consultar-todos" disabled
                            class="px-3 py-1.5 text-xs font-medium rounded bg-gray-100 text-gray-400 cursor-not-allowed"
                            title="Em breve — consulta via InfoSimples">
                            Consultar todos pendentes
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabela de participantes --}}
            <div id="dnf-comp-tabela-wrap" class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="border-b border-gray-300">
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CNPJ</th>
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">UF</th>
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</th>
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime</th>
                                <th class="px-3 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Volume</th>
                                <th class="px-3 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Última consulta</th>
                                <th class="px-3 sm:px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="dnf-comp-tbody">
                            @for($i = 0; $i < 5; $i++)
                            <tr class="border-t border-gray-100">
                                @for($j = 0; $j < 8; $j++)
                                <td class="px-3 sm:px-4 py-3"><span class="inline-block h-4 w-20 bg-gray-200 rounded animate-pulse"></span></td>
                                @endfor
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Empty state --}}
            <div id="dnf-comp-empty" class="hidden bg-white rounded border border-gray-300 py-16 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-500">Nenhum participante encontrado</p>
                <p class="text-sm text-gray-400 mt-1">Importe arquivos EFD para ver dados de compliance</p>
            </div>

            {{-- Secao informativa: consultas futuras --}}
            <div class="mt-4 sm:mt-6 bg-white rounded border border-gray-300 p-4 sm:p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Consultas disponíveis em breve</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">SINTEGRA</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Valida IE estadual</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">Simples Nacional</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Regime tributário</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">CND Federal</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Regularidade fiscal</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">CEIS / CNEP</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Empresas inidôneas</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">Protestos</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">IEPTB / CENPROT</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 rounded border border-dashed border-gray-300 bg-gray-50">
                        <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-2 border border-gray-300">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-600">Trabalho escravo</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Lista suja MTE</p>
                        <span class="mt-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-500">Em breve</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ApexCharts (local) --}}
<script src="/js/apexcharts.min.js"></script>
<script>
(function() {
    'use strict';

    const container = document.getElementById('dashboard-nf-container');
    if (!container) return;

    const ACTIVE_CLASS = 'active border-gray-800 text-gray-900 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm';
    const INACTIVE_CLASS = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm';

    let currentTab = '{{ $defaultTab }}';
    let tabClickHandler = null;
    let filtrarClickHandler = null;
    let evolucaoChart = null;
    let modeloDonutChart = null;

    // CFOP state
    let cfopData = null;
    let cfopLoaded = false;
    let cfopFiltroLocal = 'todos';
    let cfopSortCol = 'valor_total';
    let cfopSortAsc = false;
    let cfopFiltroHandler = null;

    // Participantes state
    let partData = null;
    let partLoaded = false;
    let partFiltroLocal = 'todos';
    let partPage = 1;
    let partBuscaTimeout = null;
    let partFiltroHandler = null;
    let partBuscaHandler = null;

    // Tributário state
    let tribData = null;
    let tribLoaded = false;
    let tribChart = null;
    let tribCstOpen = false;
    let tribCstToggleHandler = null;

    // Alertas state
    let alertasData = null;
    let alertasLoaded = false;

    // Compliance state
    let compData = null;
    let compLoaded = false;
    let compFiltroLocal = 'todos';
    let compOrdem = 'volume';
    let compFiltroHandler = null;
    let compOrdemHandler = null;

    // ─── Formatacao ─────────────────────────────────────────

    function formatBrl(value) {
        const abs = Math.abs(value);
        const sign = value < 0 ? '-' : '';
        if (abs >= 1e9) return sign + 'R$ ' + (abs / 1e9).toFixed(1).replace('.', ',') + ' bi';
        if (abs >= 1e6) return sign + 'R$ ' + (abs / 1e6).toFixed(1).replace('.', ',') + ' mi';
        if (abs >= 1e3) return sign + 'R$ ' + (abs / 1e3).toFixed(1).replace('.', ',') + ' mil';
        return sign + 'R$ ' + abs.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatNum(value) {
        return Number(value).toLocaleString('pt-BR');
    }

    function mesLabel(yyyyMm) {
        const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        const [y, m] = yyyyMm.split('-');
        return meses[parseInt(m, 10) - 1] + '/' + y.slice(2);
    }

    // ─── Filtros ────────────────────────────────────────────

    function getFilterParams() {
        const params = new URLSearchParams();
        const periodoInicio = document.getElementById('dnf-periodo-inicio')?.value;
        const periodoFim = document.getElementById('dnf-periodo-fim')?.value;
        const tipoEfd = document.getElementById('dnf-tipo-efd')?.value;
        const importacaoId = document.getElementById('dnf-importacao')?.value;
        const clienteId = document.getElementById('dnf-cliente')?.value;
        const participanteId = document.getElementById('dnf-participante')?.value;

        if (periodoInicio) params.set('periodo_inicio', periodoInicio);
        if (periodoFim) params.set('periodo_fim', periodoFim);
        if (tipoEfd && tipoEfd !== 'todos') params.set('tipo_efd', tipoEfd);
        if (importacaoId) params.set('importacao_id', importacaoId);
        if (clienteId) params.set('cliente_id', clienteId);
        if (participanteId) params.set('participante_id', participanteId);

        return params.toString();
    }

    // ─── Data loading ───────────────────────────────────────

    async function loadVisaoGeral() {
        const params = getFilterParams();
        const contentEls = [
            'dnf-kpi-total', 'dnf-kpi-entradas', 'dnf-kpi-saidas',
            'dnf-kpi-saldo', 'dnf-kpi-participantes'
        ];
        const emptyState = document.getElementById('dnf-empty-state');

        try {
            const resp = await fetch('/app/notas-fiscais/dashboard/visao-geral?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar dados');

            const data = await resp.json();

            if (data.kpis.total_notas === 0) {
                // Esconder conteudo, mostrar empty state
                container.querySelectorAll('#tab-visao-geral > :not(#dnf-empty-state)').forEach(el => el.classList.add('hidden'));
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }

            // Garantir que conteudo visivel
            container.querySelectorAll('#tab-visao-geral > .hidden:not(#dnf-empty-state)').forEach(el => el.classList.remove('hidden'));
            if (emptyState) emptyState.classList.add('hidden');

            renderKpis(data.kpis);
            renderEvolucao(data.evolucao);
            renderPorModelo(data.por_modelo);
            renderTopParticipantes(data.top_participantes);
        } catch (err) {
            console.error('[Dashboard NF] Erro:', err);
            container.querySelectorAll('#tab-visao-geral > :not(#dnf-empty-state)').forEach(el => el.classList.add('hidden'));
            if (emptyState) emptyState.classList.remove('hidden');
        }
    }

    // ─── Render KPIs ────────────────────────────────────────

    function renderKpis(kpis) {
        const el = (id) => document.getElementById(id);

        el('dnf-kpi-total').textContent = formatNum(kpis.total_notas);
        el('dnf-kpi-entradas').textContent = formatBrl(kpis.valor_entradas);
        el('dnf-kpi-saidas').textContent = formatBrl(kpis.valor_saidas);

        const saldoEl = el('dnf-kpi-saldo');
        saldoEl.textContent = formatBrl(kpis.saldo);
        saldoEl.className = saldoEl.className.replace(/text-(green|red|gray)-\d+/g, '');
        saldoEl.classList.add(kpis.saldo >= 0 ? 'text-green-600' : 'text-red-500');

        el('dnf-kpi-participantes').textContent = formatNum(kpis.participantes_unicos);
    }

    // ─── Render Evolucao (ApexCharts) ───────────────────────

    function renderEvolucao(evolucao) {
        const chartEl = document.getElementById('dnf-chart-evolucao');
        if (!chartEl) return;

        if (evolucaoChart) {
            evolucaoChart.destroy();
            evolucaoChart = null;
        }

        if (!evolucao || evolucao.length === 0) {
            chartEl.innerHTML = '<div class="flex items-center justify-center h-64 text-gray-400 text-sm">Sem dados para o periodo selecionado</div>';
            return;
        }

        const categories = evolucao.map(e => mesLabel(e.mes));
        const seriesEntradas = evolucao.map(e => parseFloat(e.entradas) || 0);
        const seriesSaidas = evolucao.map(e => parseFloat(e.saidas) || 0);

        const options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            series: [
                { name: 'Entradas', data: seriesEntradas },
                { name: 'Saidas', data: seriesSaidas },
            ],
            colors: ['#047857', '#d97706'],
            plotOptions: {
                bar: { columnWidth: '60%', borderRadius: 4 },
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '12px', colors: '#6b7280' } },
            },
            yaxis: {
                labels: {
                    style: { fontSize: '12px', colors: '#6b7280' },
                    formatter: (val) => formatBrl(val),
                },
            },
            tooltip: {
                y: { formatter: (val) => formatBrl(val) },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '13px',
            },
            grid: {
                borderColor: '#f3f4f6',
                strokeDashArray: 4,
            },
            responsive: [
                {
                    breakpoint: 640,
                    options: {
                        chart: { height: 240 },
                        xaxis: { labels: { style: { fontSize: '10px' }, rotate: -45, maxHeight: 60 } },
                        legend: { position: 'bottom', fontSize: '11px' },
                    }
                }
            ],
        };

        chartEl.innerHTML = '';
        evolucaoChart = new ApexCharts(chartEl, options);
        evolucaoChart.render();
    }

    // ─── Render Tabela Modelos (com donut + entradas/saidas) ──

    function renderPorModelo(modelos) {
        const el = document.getElementById('dnf-table-modelos');
        if (!el) return;

        if (!modelos || modelos.length === 0) {
            el.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">Sem dados</p>';
            return;
        }

        // Donut chart container
        let html = '<div id="dnf-chart-modelos" style="min-height:180px"></div>';

        // Lista detalhada por tipo
        html += '<div class="divide-y divide-gray-100 mt-4">';
        modelos.forEach(m => {
            const totalVal = m.valor_total || 1;
            const entPct = Math.round((m.entradas.valor / totalVal) * 100);
            const saiPct = 100 - entPct;

            html += '<div class="py-3">';
            // Header: label + notas count + percentage badge
            html += '<div class="flex items-center justify-between mb-1.5">';
            html += '  <span class="text-sm font-medium text-gray-800">' + escapeHtml(m.label) + '</span>';
            html += '  <div class="flex items-center gap-2">';
            html += '    <span class="text-xs text-gray-500">' + formatNum(m.quantidade) + ' notas</span>';
            html += '    <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full font-medium">' + m.percentual + '%</span>';
            html += '  </div>';
            html += '</div>';
            // Stacked proportion bar
            html += '<div class="flex h-1.5 rounded-full overflow-hidden bg-gray-100 mb-2">';
            if (m.entradas.valor > 0) html += '<div class="transition-all duration-500" style="width:' + entPct + '%;background-color:#047857"></div>';
            if (m.saidas.valor > 0) html += '<div class="transition-all duration-500" style="width:' + saiPct + '%;background-color:#d97706"></div>';
            html += '</div>';
            // Sub-rows: entradas e saidas
            html += '<div class="flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs">';
            html += '  <span class="text-gray-600"><span class="inline-block w-2 h-2 rounded-full mr-1" style="background-color:#047857"></span>' + formatNum(m.entradas.quantidade) + ' entradas &middot; ' + formatBrl(m.entradas.valor) + '</span>';
            html += '  <span class="text-gray-600"><span class="inline-block w-2 h-2 rounded-full mr-1" style="background-color:#d97706"></span>' + formatNum(m.saidas.quantidade) + ' sa\u00eddas &middot; ' + formatBrl(m.saidas.valor) + '</span>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';

        el.innerHTML = html;

        // Render donut chart
        renderModeloDonut(modelos);
    }

    function renderModeloDonut(modelos) {
        const chartEl = document.getElementById('dnf-chart-modelos');
        if (!chartEl || typeof ApexCharts === 'undefined') return;

        if (modeloDonutChart) {
            try { modeloDonutChart.destroy(); } catch (e) { /* ignore */ }
            modeloDonutChart = null;
        }

        const cores = ['#374151', '#047857', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#65a30d', '#db2777', '#4f46e5'];

        const options = {
            chart: { type: 'donut', height: 180, fontFamily: 'inherit' },
            series: modelos.map(m => m.valor_total),
            labels: modelos.map(m => m.label),
            colors: cores.slice(0, modelos.length),
            legend: { position: 'right', fontSize: '11px', offsetY: 0 },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#fff'] },
            tooltip: {
                y: { formatter: (val) => formatBrl(val) }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: { show: true, fontSize: '11px', color: '#6b7280' },
                            value: { show: true, fontSize: '14px', fontWeight: 600, formatter: (val) => formatBrl(parseFloat(val)) },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '11px',
                                color: '#6b7280',
                                formatter: (w) => {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatBrl(total);
                                }
                            }
                        }
                    }
                }
            },
            responsive: [{
                breakpoint: 640,
                options: {
                    chart: { height: 200 },
                    legend: { position: 'bottom', fontSize: '10px' },
                    plotOptions: { pie: { donut: { size: '60%' } } }
                }
            }]
        };

        modeloDonutChart = new ApexCharts(chartEl, options);
        modeloDonutChart.render();
    }

    // ─── Render Tabela Top Participantes ─────────────────────

    function renderTopParticipantes(participantes) {
        const el = document.getElementById('dnf-table-participantes');
        if (!el) return;

        if (!participantes || participantes.length === 0) {
            el.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">Sem dados</p>';
            return;
        }

        let html = '<div class="overflow-x-auto"><table class="w-full text-sm">';
        html += '<thead><tr class="border-b border-gray-200">';
        html += '<th class="text-left py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>';
        html += '<th class="text-right py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>';
        html += '<th class="text-right py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</th>';
        html += '</tr></thead><tbody>';

        participantes.forEach((p, i) => {
            const bg = i % 2 === 0 ? '' : 'bg-gray-50';
            html += '<tr class="' + bg + '">';
            html += '<td class="py-2 px-2"><div class="truncate max-w-[200px]"><a href="/app/participante/' + p.participante_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(p.razao_social) + '</a></div>';
            if (p.cnpj) html += '<div class="text-xs text-gray-400">' + escapeHtml(p.cnpj) + '</div>';
            html += '</td>';
            html += '<td class="py-2 px-2 text-right text-gray-600">' + formatNum(p.total_notas) + '</td>';
            html += '<td class="py-2 px-2 text-right text-gray-600">' + formatBrl(p.valor_total) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        el.innerHTML = html;
    }

    // ─── CFOP Data loading ──────────────────────────────────

    async function loadCfop() {
        const params = getFilterParams();
        const emptyEl = document.getElementById('dnf-cfop-empty');
        const kpiCards = container.querySelectorAll('#tab-cfop > .grid');
        const tableCard = container.querySelector('#tab-cfop > .bg-white:not(.grid)');

        try {
            const resp = await fetch('/app/notas-fiscais/dashboard/cfop?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar dados CFOP');

            const data = await resp.json();
            cfopData = data;
            cfopLoaded = true;

            if (!data.cfops || data.cfops.length === 0) {
                kpiCards.forEach(el => el.classList.add('hidden'));
                if (tableCard) tableCard.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            kpiCards.forEach(el => el.classList.remove('hidden'));
            if (tableCard) tableCard.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            renderCfopResumo(data.resumo);
            renderCfopTable(data.cfops, cfopFiltroLocal);
        } catch (err) {
            console.error('[Dashboard NF] Erro CFOP:', err);
            kpiCards.forEach(el => el.classList.add('hidden'));
            if (tableCard) tableCard.classList.add('hidden');
            if (emptyEl) emptyEl.classList.remove('hidden');
        }
    }

    function renderCfopResumo(resumo) {
        const el = (id) => document.getElementById(id);
        const totalValor = (resumo.entradas.valor_total || 0) + (resumo.saidas.valor_total || 0);
        const entPct = totalValor > 0 ? Math.round((resumo.entradas.valor_total / totalValor) * 100) : 0;
        const saiPct = totalValor > 0 ? Math.round((resumo.saidas.valor_total / totalValor) * 100) : 0;

        el('dnf-cfop-entradas-valor').textContent = formatBrl(resumo.entradas.valor_total);
        el('dnf-cfop-entradas-info').textContent = resumo.entradas.qtd_cfops + ' CFOPs distintos \u00b7 ' + formatNum(resumo.entradas.qtd_itens) + ' itens \u00b7 ' + entPct + '% do valor';

        el('dnf-cfop-saidas-valor').textContent = formatBrl(resumo.saidas.valor_total);
        el('dnf-cfop-saidas-info').textContent = resumo.saidas.qtd_cfops + ' CFOPs distintos \u00b7 ' + formatNum(resumo.saidas.qtd_itens) + ' itens \u00b7 ' + saiPct + '% do valor';
    }

    function renderCfopTable(cfops, filtro) {
        const el = document.getElementById('dnf-cfop-table');
        if (!el) return;

        let filtered = filtro === 'todos' ? cfops : cfops.filter(c => c.tipo === filtro);

        // Sort
        filtered = [...filtered].sort((a, b) => {
            let va = a[cfopSortCol], vb = b[cfopSortCol];
            if (typeof va === 'string') va = va.toLowerCase();
            if (typeof vb === 'string') vb = vb.toLowerCase();
            if (va < vb) return cfopSortAsc ? -1 : 1;
            if (va > vb) return cfopSortAsc ? 1 : -1;
            return 0;
        });

        if (filtered.length === 0) {
            el.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">Sem dados para o filtro selecionado</p>';
            return;
        }

        const maxQtd = Math.max(...filtered.map(c => c.qtd_itens || 0), 1);
        let html = '<div class="space-y-3 sm:space-y-2">';

        let totalItens = 0, totalValor = 0;

        filtered.forEach((c, i) => {
            const badge = cfopNaturezaBadge(c.natureza);
            const barWidth = Math.round(((c.qtd_itens || 0) / maxQtd) * 100);
            totalItens += (c.qtd_itens || 0);
            totalValor += (c.valor_total || 0);

            // Desktop
            html += '<div class="hidden sm:flex items-center gap-3 py-1.5">';
            html += '  <span class="text-xs font-bold text-gray-400 w-6 shrink-0">#' + (i + 1) + '</span>';
            html += '  <span class="font-mono text-sm font-semibold text-gray-800 w-12 shrink-0">' + c.cfop + '</span>';
            html += '  <span class="inline-flex items-center justify-center w-5 h-5 rounded text-xs font-bold text-white shrink-0" style="' + badge.style + '" title="' + badge.label + '">' + badge.letter + '</span>';
            html += '  <span class="text-sm text-gray-600 truncate flex-1 min-w-0" title="' + escapeHtml(c.descricao) + '">' + escapeHtml(c.descricao) + '</span>';
            html += '  <div class="w-32 lg:w-48 shrink-0"><div class="bg-gray-100 rounded-full h-2"><div class="' + badge.barColor + ' rounded-full h-2 transition-all duration-500" style="width: ' + barWidth + '%"></div></div></div>';
            html += '  <span class="text-sm font-semibold text-gray-800 w-14 text-right shrink-0 whitespace-nowrap">' + formatNum(c.qtd_itens) + '</span>';
            html += '  <span class="text-xs text-gray-400 w-12 text-right shrink-0 whitespace-nowrap">(' + c.percentual.toFixed(1).replace('.', ',') + '%)</span>';
            html += '  <span class="text-sm font-semibold text-gray-700 w-36 text-right shrink-0 whitespace-nowrap">' + formatBrl(c.valor_total) + '</span>';
            html += '</div>';

            // Mobile
            html += '<div class="sm:hidden">';
            html += '  <div class="flex items-center gap-2 mb-1.5">';
            html += '    <span class="text-xs font-bold text-gray-400">#' + (i + 1) + '</span>';
            html += '    <span class="font-mono text-sm font-semibold text-gray-800">' + c.cfop + '</span>';
            html += '    <span class="inline-flex items-center justify-center w-5 h-5 rounded text-xs font-bold text-white" style="' + badge.style + '" title="' + badge.label + '">' + badge.letter + '</span>';
            html += '    <span class="text-xs text-gray-600 truncate flex-1 min-w-0">' + escapeHtml(c.descricao) + '</span>';
            html += '  </div>';
            html += '  <div class="flex items-center gap-2">';
            html += '    <div class="flex-1"><div class="bg-gray-100 rounded-full h-1.5"><div class="' + badge.barColor + ' rounded-full h-1.5 transition-all duration-500" style="width: ' + barWidth + '%"></div></div></div>';
            html += '    <span class="text-xs font-semibold text-gray-800 shrink-0 whitespace-nowrap">' + formatNum(c.qtd_itens) + '</span>';
            html += '    <span class="text-xs text-gray-400 shrink-0 whitespace-nowrap">(' + c.percentual.toFixed(1).replace('.', ',') + '%)</span>';
            html += '    <span class="text-xs font-semibold text-gray-700 shrink-0 whitespace-nowrap">' + formatBrl(c.valor_total) + '</span>';
            html += '  </div>';
            html += '</div>';
        });

        // Subtotais footer
        html += '<div class="border-t border-gray-200 pt-3 mt-3 flex items-center justify-between text-xs text-gray-500 font-medium">';
        html += '  <span>' + formatNum(filtered.length) + ' CFOPs &middot; ' + formatNum(totalItens) + ' itens</span>';
        html += '  <span>Total ' + formatBrl(totalValor) + '</span>';
        html += '</div>';

        html += '</div>';
        el.innerHTML = html;

        // Update sort controls styling
        updateCfopSortControls();
    }

    function cfopNaturezaBadge(natureza) {
        const map = {
            'entrada':       { style: 'background-color:#047857', letter: 'E', barColor: 'bg-green-500', label: 'Entrada' },
            'saida':         { style: 'background-color:#d97706', letter: 'S', barColor: 'bg-amber-500', label: 'Sa\u00edda' },
            'devolucao':     { style: 'background-color:#dc2626', letter: 'D', barColor: 'bg-red-500',   label: 'Devolu\u00e7\u00e3o' },
            'transferencia': { style: 'background-color:#7c3aed', letter: 'T', barColor: 'bg-purple-500', label: 'Transfer\u00eancia' },
        };
        return map[natureza] || { style: 'background-color:#9ca3af', letter: '?', barColor: 'bg-gray-400', label: natureza || 'Outro' };
    }

    function updateCfopSortControls() {
        const arrows = { true: '\u2191', false: '\u2193' };
        document.querySelectorAll('#dnf-cfop-sort-controls .cfop-sort-btn').forEach(btn => {
            const col = btn.getAttribute('data-cfop-sort');
            if (col === cfopSortCol) {
                btn.className = 'cfop-sort-btn px-1.5 py-0.5 rounded text-gray-800 font-medium';
                const labels = { 'valor_total': 'Valor', 'qtd_itens': 'Qtd', 'cfop': 'CFOP' };
                btn.textContent = (labels[col] || col) + ' ' + arrows[cfopSortAsc];
            } else {
                btn.className = 'cfop-sort-btn px-1.5 py-0.5 rounded text-gray-400 hover:text-gray-600';
                const labels = { 'valor_total': 'Valor', 'qtd_itens': 'Qtd', 'cfop': 'CFOP' };
                btn.textContent = labels[col] || col;
            }
        });
    }

    function setupCfopFiltroLocal() {
        const filtroContainer = document.getElementById('dnf-cfop-filtro-local');
        if (!filtroContainer) return;

        cfopFiltroHandler = function(e) {
            const btn = e.target.closest('[data-cfop-filtro]');
            if (!btn) return;

            cfopFiltroLocal = btn.getAttribute('data-cfop-filtro');

            filtroContainer.querySelectorAll('.cfop-filtro-btn').forEach(b => {
                if (b.getAttribute('data-cfop-filtro') === cfopFiltroLocal) {
                    b.className = 'cfop-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-700 shadow-sm';
                } else {
                    b.className = 'cfop-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700';
                }
            });

            if (cfopData && cfopData.cfops) {
                renderCfopTable(cfopData.cfops, cfopFiltroLocal);
            }
        };

        filtroContainer.addEventListener('click', cfopFiltroHandler);

        // Sort controls
        document.querySelectorAll('#dnf-cfop-sort-controls .cfop-sort-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const col = this.getAttribute('data-cfop-sort');
                if (cfopSortCol === col) {
                    cfopSortAsc = !cfopSortAsc;
                } else {
                    cfopSortCol = col;
                    cfopSortAsc = col === 'cfop';
                }
                if (cfopData && cfopData.cfops) {
                    renderCfopTable(cfopData.cfops, cfopFiltroLocal);
                }
            });
        });
    }

    // ─── Participantes Data loading ──────────────────────────

    async function loadParticipantes() {
        const params = new URLSearchParams(getFilterParams());
        if (partFiltroLocal !== 'todos') params.set('tipo', partFiltroLocal);
        const buscaEl = document.getElementById('dnf-part-busca');
        if (buscaEl && buscaEl.value.trim()) params.set('busca', buscaEl.value.trim());
        params.set('page', partPage);

        const emptyEl = document.getElementById('dnf-part-empty');
        const kpiCards = container.querySelector('#tab-participantes > .grid');
        const concCard = document.getElementById('dnf-part-concentracao-card');
        const tableCard = container.querySelector('#tab-participantes > .bg-white');

        try {
            const resp = await fetch('/app/notas-fiscais/dashboard/participantes?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar participantes');

            const data = await resp.json();
            partData = data;
            partLoaded = true;

            if (!data.participantes.data || data.participantes.data.length === 0) {
                if (kpiCards) kpiCards.classList.add('hidden');
                if (concCard) concCard.classList.add('hidden');
                if (tableCard) tableCard.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (kpiCards) kpiCards.classList.remove('hidden');
            if (concCard) concCard.classList.remove('hidden');
            if (tableCard) tableCard.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            renderPartResumo(data.resumo);
            renderPartConcentracao(data.resumo.concentracao);
            renderPartTable(data.participantes.data);
            renderPartPaginacao(data.participantes);
        } catch (err) {
            console.error('[Dashboard NF] Erro Participantes:', err);
            if (kpiCards) kpiCards.classList.add('hidden');
            if (concCard) concCard.classList.add('hidden');
            if (tableCard) tableCard.classList.add('hidden');
            if (emptyEl) emptyEl.classList.remove('hidden');
        }
    }

    function renderPartResumo(resumo) {
        const el = (id) => document.getElementById(id);
        el('dnf-part-total').textContent = formatNum(resumo.total_participantes);
        el('dnf-part-fornecedores').textContent = formatNum(resumo.total_fornecedores);
        el('dnf-part-clientes').textContent = formatNum(resumo.total_clientes);
    }

    function renderPartConcentracao(conc) {
        const el = document.getElementById('dnf-part-concentracao');
        if (!el) return;

        let txt = 'Top 5 fornecedores = ' + conc.top5_entradas_pct.toFixed(1).replace('.', ',') + '% das entradas';
        txt += ' \u00b7 Top 5 clientes = ' + conc.top5_saidas_pct.toFixed(1).replace('.', ',') + '% das saídas';

        if (conc.top5_entradas_pct > 80 || conc.top5_saidas_pct > 80) {
            txt += ' <span class="inline-block ml-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#dc2626">Alta concentração</span>';
        }

        el.innerHTML = txt;
    }

    function renderPartTable(participantes) {
        const el = document.getElementById('dnf-part-table');
        if (!el) return;

        if (!participantes || participantes.length === 0) {
            el.innerHTML = '<p class="text-sm text-gray-400 text-center py-8">Sem dados para o filtro selecionado</p>';
            return;
        }

        let html = '<div class="overflow-x-auto"><table class="w-full text-sm">';
        html += '<thead><tr class="border-b border-gray-200">';
        html += '<th class="text-left py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>';
        html += '<th class="text-center py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">UF</th>';
        html += '<th class="text-right py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>';
        html += '<th class="text-right py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Entradas</th>';
        html += '<th class="text-right py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saídas</th>';
        html += '<th class="text-center py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Papel</th>';
        html += '<th class="text-center py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</th>';
        html += '<th class="text-left py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime</th>';
        html += '<th class="text-center py-2 px-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide"></th>';
        html += '</tr></thead><tbody>';

        participantes.forEach((p, i) => {
            const bg = i % 2 === 0 ? '' : 'bg-gray-50';
            html += '<tr class="' + bg + ' hover:bg-gray-100 transition-colors">';
            html += '<td class="py-2 px-2"><div class="truncate max-w-[220px]" title="' + escapeHtml(p.razao_social) + '"><a href="/app/participante/' + p.participante_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(p.razao_social) + '</a></div>';
            if (p.cnpj) html += '<div class="text-xs text-gray-400">' + escapeHtml(p.cnpj) + '</div>';
            html += '</td>';
            html += '<td class="py-2 px-2 text-center text-gray-600">' + escapeHtml(p.uf || '\u2014') + '</td>';
            html += '<td class="py-2 px-2 text-right text-gray-600">' + formatNum(p.total_notas) + '</td>';
            html += '<td class="py-2 px-2 text-right text-green-600">' + formatBrl(p.valor_entradas) + '</td>';
            html += '<td class="py-2 px-2 text-right text-gray-700">' + formatBrl(p.valor_saidas) + '</td>';
            html += '<td class="py-2 px-2 text-center">' + papelBadge(p.papel) + '</td>';
            html += '<td class="py-2 px-2 text-center">' + situacaoBadge(p.situacao_cadastral) + '</td>';
            html += '<td class="py-2 px-2 text-gray-600 text-sm truncate max-w-[120px]">' + escapeHtml(p.regime_tributario || '\u2014') + '</td>';
            html += '<td class="py-2 px-2 text-center"><a href="/app/participante/' + p.participante_id + '" class="text-gray-600 hover:text-gray-900 hover:underline text-xs font-medium">Ver</a></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        el.innerHTML = html;
    }

    function renderPartPaginacao(pag) {
        const el = document.getElementById('dnf-part-paginacao');
        if (!el) return;

        if (pag.last_page <= 1) {
            el.innerHTML = '<p class="text-xs text-gray-400">' + pag.total + ' participante' + (pag.total !== 1 ? 's' : '') + '</p>';
            return;
        }

        let html = '<div class="flex items-center justify-between">';
        html += '<p class="text-xs text-gray-400">' + pag.total + ' participantes \u00b7 Pagina ' + pag.current_page + ' de ' + pag.last_page + '</p>';
        html += '<div class="flex gap-1">';

        if (pag.current_page > 1) {
            html += '<button data-part-page="' + (pag.current_page - 1) + '" class="part-page-btn px-3 py-1 text-sm rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Anterior</button>';
        }

        const start = Math.max(1, pag.current_page - 2);
        const end = Math.min(pag.last_page, pag.current_page + 2);
        for (let i = start; i <= end; i++) {
            const active = i === pag.current_page ? 'text-white border-gray-800" style="background-color:#1f2937' : 'border-gray-300 text-gray-600 hover:bg-gray-50';
            html += '<button data-part-page="' + i + '" class="part-page-btn px-3 py-1 text-sm rounded border ' + active + '">' + i + '</button>';
        }

        if (pag.current_page < pag.last_page) {
            html += '<button data-part-page="' + (pag.current_page + 1) + '" class="part-page-btn px-3 py-1 text-sm rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Proximo</button>';
        }

        html += '</div></div>';
        el.innerHTML = html;

        el.querySelectorAll('.part-page-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                partPage = parseInt(this.getAttribute('data-part-page'), 10);
                loadParticipantes();
            });
        });
    }

    function papelBadge(papel) {
        const map = {
            'fornecedor': ['#047857', 'Fornecedor'],
            'cliente': ['#374151', 'Cliente'],
            'ambos': ['#4338ca', 'Ambos'],
        };
        const [hex, label] = map[papel] || ['#9ca3af', papel];
        return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:' + hex + '">' + label + '</span>';
    }

    function situacaoBadge(situacao) {
        if (!situacao) return '<span class="text-gray-300">\u2014</span>';
        const lower = situacao.toLowerCase();
        if (lower === 'ativa') {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#047857">Ativa</span>';
        }
        if (['baixada', 'inapta', 'suspensa', 'nula'].includes(lower)) {
            return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#dc2626">' + escapeHtml(situacao) + '</span>';
        }
        return '<span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#9ca3af">' + escapeHtml(situacao) + '</span>';
    }

    function setupPartFiltroLocal() {
        const filtroContainer = document.getElementById('dnf-part-filtro-local');
        if (!filtroContainer) return;

        partFiltroHandler = function(e) {
            const btn = e.target.closest('[data-part-filtro]');
            if (!btn) return;

            partFiltroLocal = btn.getAttribute('data-part-filtro');
            partPage = 1;

            filtroContainer.querySelectorAll('.part-filtro-btn').forEach(b => {
                if (b.getAttribute('data-part-filtro') === partFiltroLocal) {
                    b.className = 'part-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-700 shadow-sm';
                } else {
                    b.className = 'part-filtro-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700';
                }
            });

            loadParticipantes();
        };

        filtroContainer.addEventListener('click', partFiltroHandler);
    }

    function setupPartBusca() {
        const buscaEl = document.getElementById('dnf-part-busca');
        if (!buscaEl) return;

        partBuscaHandler = function() {
            clearTimeout(partBuscaTimeout);
            partBuscaTimeout = setTimeout(function() {
                partPage = 1;
                loadParticipantes();
            }, 400);
        };

        buscaEl.addEventListener('input', partBuscaHandler);
    }

    // ─── Tributário ──────────────────────────────────────────

    async function loadTributario() {
        const params = getFilterParams();
        const contentEls = ['dnf-trib-icms', 'dnf-trib-pis', 'dnf-trib-cofins'];
        const emptyState = document.getElementById('dnf-trib-empty');

        try {
            const resp = await fetch('/app/notas-fiscais/dashboard/tributario?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar dados');

            tribData = await resp.json();
            tribLoaded = true;

            if (!tribData.evolucao || tribData.evolucao.length === 0) {
                container.querySelectorAll('#tab-tributario > :not(#dnf-trib-empty)').forEach(function(el) { el.classList.add('hidden'); });
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }

            container.querySelectorAll('#tab-tributario > :not(#dnf-trib-empty)').forEach(function(el) { el.classList.remove('hidden'); });
            if (emptyState) emptyState.classList.add('hidden');

            renderTribAlerta(tribData.alerta_pis_cofins);
            renderTribSaldos(tribData.saldos);
            renderTribEvolucao(tribData.evolucao);
            renderTribPeriodo(tribData.por_periodo, tribData.saldos);
            renderTribCsts(tribData.csts);
        } catch (e) {
            contentEls.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.innerHTML = '<span class="text-red-500 text-sm">Erro ao carregar</span>';
            });
        }
    }

    function renderTribAlerta(alerta) {
        var el = document.getElementById('dnf-trib-alerta');
        if (el) el.classList.toggle('hidden', !alerta);
    }

    function renderTribSaldos(saldos) {
        var items = [
            { id: 'dnf-trib-icms', detalhe: 'dnf-trib-icms-detalhe', data: saldos.icms },
            { id: 'dnf-trib-pis', detalhe: 'dnf-trib-pis-detalhe', data: saldos.pis },
            { id: 'dnf-trib-cofins', detalhe: 'dnf-trib-cofins-detalhe', data: saldos.cofins },
        ];

        items.forEach(function(item) {
            var el = document.getElementById(item.id);
            var detalheEl = document.getElementById(item.detalhe);
            var saldo = item.data.saldo;

            if (el) {
                var cor = saldo > 0 ? 'text-red-600' : (saldo < 0 ? 'text-green-600' : 'text-gray-800');
                var label = saldo > 0 ? 'A pagar' : (saldo < 0 ? 'Crédito acum.' : '');
                el.innerHTML = '<span class="' + cor + '">' + formatBrl(saldo) + '</span>'
                    + (label ? ' <span class="text-xs font-normal text-gray-400">' + label + '</span>' : '');
            }

            if (detalheEl) {
                detalheEl.innerHTML = 'Deb <span class="text-red-500">' + formatBrl(item.data.debito)
                    + '</span> · Cred <span class="text-green-500">' + formatBrl(item.data.credito) + '</span>';
            }
        });
    }

    function renderTribEvolucao(evolucao) {
        if (tribChart) {
            tribChart.destroy();
            tribChart = null;
        }

        var chartEl = document.getElementById('dnf-trib-chart');
        if (!chartEl) return;
        chartEl.innerHTML = '';

        var options = {
            chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [
                { name: 'Débito ICMS', data: evolucao.map(function(e) { return parseFloat(e.icms_debito); }) },
                { name: 'Crédito ICMS', data: evolucao.map(function(e) { return parseFloat(e.icms_credito); }) }
            ],
            colors: ['#dc2626', '#047857'],
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
            xaxis: { categories: evolucao.map(function(e) { return mesLabel(e.mes); }), labels: { style: { fontSize: '11px' } } },
            yaxis: { labels: { formatter: function(val) { return formatBrl(val); }, style: { fontSize: '11px' } } },
            tooltip: { y: { formatter: function(val) { return formatBrl(val); } } },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
            grid: { borderColor: '#e5e7eb' },
            dataLabels: { enabled: false },
            responsive: [{ breakpoint: 640, options: { chart: { height: 260 }, plotOptions: { bar: { columnWidth: '80%' } } } }]
        };

        tribChart = new ApexCharts(chartEl, options);
        tribChart.render();
    }

    function renderTribPeriodo(porPeriodo, saldos) {
        var el = document.getElementById('dnf-trib-table-periodo');
        if (!el) return;

        function valCell(val, colorize) {
            if (!colorize) return '<td class="px-2 sm:px-3 py-2 text-xs text-right text-gray-700">' + formatBrl(val) + '</td>';
            var cor = val > 0 ? 'text-red-600' : (val < 0 ? 'text-green-600' : 'text-gray-600');
            return '<td class="px-2 sm:px-3 py-2 text-xs text-right ' + cor + ' font-medium">' + formatBrl(val) + '</td>';
        }

        var html = '<div class="overflow-x-auto -mx-4 sm:mx-0"><table class="min-w-full text-xs">'
            + '<thead><tr class="bg-gray-50 border-b">'
            + '<th class="px-2 sm:px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Período</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">ICMS Déb</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">ICMS Créd</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo ICMS</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">PIS Déb</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">PIS Créd</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo PIS</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">COF Déb</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">COF Créd</th>'
            + '<th class="px-2 sm:px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo COF</th>'
            + '</tr></thead><tbody>';

        porPeriodo.forEach(function(r, idx) {
            var bg = idx % 2 === 0 ? '' : ' bg-gray-50';
            html += '<tr class="border-b' + bg + '">'
                + '<td class="px-2 sm:px-3 py-2 text-xs font-medium text-gray-800">' + mesLabel(r.mes) + '</td>'
                + valCell(r.icms_debito, false)
                + valCell(r.icms_credito, false)
                + valCell(r.saldo_icms, true)
                + valCell(r.pis_debito, false)
                + valCell(r.pis_credito, false)
                + valCell(r.saldo_pis, true)
                + valCell(r.cofins_debito, false)
                + valCell(r.cofins_credito, false)
                + valCell(r.saldo_cofins, true)
                + '</tr>';
        });

        html += '</tbody><tfoot><tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">'
            + '<td class="px-2 sm:px-3 py-2 text-xs text-gray-800">Total</td>'
            + valCell(saldos.icms.debito, false)
            + valCell(saldos.icms.credito, false)
            + valCell(saldos.icms.saldo, true)
            + valCell(saldos.pis.debito, false)
            + valCell(saldos.pis.credito, false)
            + valCell(saldos.pis.saldo, true)
            + valCell(saldos.cofins.debito, false)
            + valCell(saldos.cofins.credito, false)
            + valCell(saldos.cofins.saldo, true)
            + '</tr></tfoot></table></div>';

        el.innerHTML = html;
    }

    function renderTribCsts(csts) {
        var el = document.getElementById('dnf-trib-cst-table');
        if (!el) return;

        if (!csts || csts.length === 0) {
            el.innerHTML = '<p class="text-sm text-gray-400">Nenhum CST encontrado</p>';
            return;
        }

        var html = '<div class="overflow-x-auto -mx-4 sm:mx-0"><table class="min-w-full text-xs">'
            + '<thead><tr class="bg-gray-50 border-b">'
            + '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CST</th>'
            + '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Descrição</th>'
            + '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Qtd Itens</th>'
            + '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</th>'
            + '</tr></thead><tbody>';

        csts.forEach(function(c, idx) {
            var bg = idx % 2 === 0 ? '' : ' bg-gray-50';
            html += '<tr class="border-b' + bg + '">'
                + '<td class="px-3 py-2 text-xs font-mono font-medium text-gray-800">' + escapeHtml(c.cst || '—') + '</td>'
                + '<td class="px-3 py-2 text-xs text-gray-700">' + escapeHtml(c.descricao) + '</td>'
                + '<td class="px-3 py-2 text-xs text-right text-gray-700">' + formatNum(c.qtd_itens) + '</td>'
                + '<td class="px-3 py-2 text-xs text-right text-gray-700">' + formatBrl(c.valor_total) + '</td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';
        el.innerHTML = html;
    }

    function setupTribCstToggle() {
        var btn = document.getElementById('dnf-trib-cst-toggle');
        if (!btn) return;

        tribCstToggleHandler = function() {
            tribCstOpen = !tribCstOpen;
            var table = document.getElementById('dnf-trib-cst-table');
            var chevron = document.getElementById('dnf-trib-cst-chevron');
            if (table) table.classList.toggle('hidden', !tribCstOpen);
            if (chevron) chevron.style.transform = tribCstOpen ? 'rotate(180deg)' : '';
        };

        btn.addEventListener('click', tribCstToggleHandler);
    }

    // ─── Alertas ─────────────────────────────────────────────

    async function loadAlertas() {
        var params = getFilterParams();
        var resumoEl = document.getElementById('dnf-alertas-resumo');
        var listEl = document.getElementById('dnf-alertas-list');
        var emptyEl = document.getElementById('dnf-alertas-empty');

        try {
            var resp = await fetch('/app/notas-fiscais/dashboard/alertas?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar alertas');

            var data = await resp.json();
            alertasData = data;
            alertasLoaded = true;

            if (data.resumo.total === 0) {
                if (resumoEl) resumoEl.classList.add('hidden');
                if (listEl) listEl.classList.add('hidden');
                if (emptyEl) emptyEl.classList.remove('hidden');
                return;
            }

            if (resumoEl) resumoEl.classList.remove('hidden');
            if (listEl) listEl.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            renderAlertasResumo(data.resumo);
            renderAlertasList(data.alertas);
        } catch (err) {
            console.error('[Dashboard NF] Erro Alertas:', err);
            if (resumoEl) resumoEl.classList.add('hidden');
            if (listEl) listEl.innerHTML = '';
            if (emptyEl) emptyEl.classList.remove('hidden');
        }
    }

    function renderAlertasResumo(resumo) {
        var el = function(id) { return document.getElementById(id); };
        el('dnf-alertas-alta').textContent = resumo.alta;
        el('dnf-alertas-media').textContent = resumo.media;
        el('dnf-alertas-baixa').textContent = resumo.baixa;
    }

    function renderAlertasList(alertas) {
        var listEl = document.getElementById('dnf-alertas-list');
        if (!listEl) return;

        var severidadeOrder = { alta: 0, media: 1, baixa: 2 };
        var sorted = alertas.slice().sort(function(a, b) {
            return severidadeOrder[a.severidade] - severidadeOrder[b.severidade];
        });

        var sevConfig = {
            alta:  { badgeStyle: 'background-color:#dc2626', border: 'border-gray-200', dot: 'bg-red-400' },
            media: { badgeStyle: 'background-color:#d97706', border: 'border-gray-200', dot: 'bg-yellow-400' },
            baixa: { badgeStyle: 'background-color:#9ca3af', border: 'border-gray-200', dot: 'bg-gray-400' },
        };

        var html = '';
        sorted.forEach(function(alerta) {
            var cfg = sevConfig[alerta.severidade] || sevConfig.baixa;
            var isPaid = alerta.tipo === 'paid' && !alerta.disponivel;
            var sevLabel = alerta.severidade.charAt(0).toUpperCase() + alerta.severidade.slice(1);

            html += '<div class="bg-white rounded border border-gray-300 overflow-hidden">';

            // Header
            html += '<button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors dnf-alerta-toggle">';
            html += '<div class="flex items-center gap-3 min-w-0">';
            html += '<div class="w-2.5 h-2.5 rounded-full flex-shrink-0 ' + cfg.dot + '"></div>';
            html += '<div class="min-w-0">';
            html += '<span class="text-sm font-medium text-gray-900">' + escapeHtml(alerta.titulo) + '</span>';
            if (alerta.total_afetados > 0) {
                html += ' <span class="text-xs text-gray-500">(' + alerta.total_afetados + ')</span>';
            }
            html += '</div>';
            html += '</div>';
            html += '<div class="flex items-center gap-2 flex-shrink-0">';
            if (isPaid) {
                html += '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#374151">Pro</span>';
            }
            html += '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="' + cfg.badgeStyle + '">' + sevLabel + '</span>';
            html += '<svg class="w-4 h-4 text-gray-400 transition-transform dnf-alerta-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
            html += '</div>';
            html += '</button>';

            // Body
            html += '<div class="dnf-alerta-body hidden border-t ' + cfg.border + ' px-4 py-3">';
            if (isPaid) {
                html += '<div class="flex flex-col items-center py-6 text-center">';
                html += '<p class="text-sm text-gray-500 mb-3">' + escapeHtml(alerta.descricao) + '</p>';
                html += '<button class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded hover:bg-gray-700 transition-colors dnf-alertas-compliance-cta">';
                html += 'Ative consultas de compliance';
                html += '</button>';
                html += '</div>';
            } else {
                html += '<p class="text-sm text-gray-600 mb-3">' + escapeHtml(alerta.descricao) + '</p>';
                html += renderAlertaDetalhes(alerta);
            }
            html += '</div>';

            html += '</div>';
        });

        listEl.innerHTML = html;

        // Toggle handlers
        listEl.querySelectorAll('.dnf-alerta-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var body = this.nextElementSibling;
                var chevron = this.querySelector('.dnf-alerta-chevron');
                if (body) body.classList.toggle('hidden');
                if (chevron) chevron.style.transform = body && body.classList.contains('hidden') ? '' : 'rotate(180deg)';
            });
        });

        // CTA -> compliance tab
        listEl.querySelectorAll('.dnf-alertas-compliance-cta').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var compTab = container.querySelector('[data-tab="compliance"]');
                if (compTab) compTab.click();
            });
        });
    }

    function renderAlertaDetalhes(alerta) {
        if (!alerta.detalhes || (Array.isArray(alerta.detalhes) && alerta.detalhes.length === 0)) return '';

        switch (alerta.id) {
            case 'notas_duplicadas': return renderDetalheDuplicadas(alerta.detalhes);
            case 'notas_valor_zerado':
            case 'notas_sem_itens': return renderDetalheNotas(alerta.detalhes);
            case 'participantes_sem_cnpj': return renderDetalheParticipantes(alerta.detalhes);
            case 'cfops_inconsistentes': return renderDetalheCfops(alerta.detalhes);
            case 'gap_temporal': return renderDetalheGaps(alerta.detalhes);
            case 'pis_cofins_incompleto': return renderDetalhePisCofins(alerta.detalhes);
            default: return '';
        }
    }

    function renderDetalheDuplicadas(detalhes) {
        var h = '<div class="overflow-x-auto"><table class="min-w-full text-xs">';
        h += '<thead><tr class="border-b border-gray-200">';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Número</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Série</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Modelo</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>';
        h += '<th class="text-right py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ocorrências</th>';
        h += '</tr></thead><tbody>';
        detalhes.forEach(function(d) {
            h += '<tr class="border-b border-gray-100">';
            h += '<td class="py-1.5 pr-4">' + (d.nota_id ? '<a href="/app/notas-fiscais/efd/' + d.nota_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.numero || '')) + '</a>' : '<span class="text-gray-700">' + escapeHtml(String(d.numero || '')) + '</span>') + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.serie || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.modelo || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 truncate max-w-[200px]">' + (d.participante_id ? '<a href="/app/participante/' + d.participante_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.participante || '')) + '</a>' : escapeHtml(String(d.participante || ''))) + '</td>';
            h += '<td class="py-1.5 text-right font-medium text-red-600">' + d.qtd + '</td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        if (detalhes.length >= 50) h += '<p class="text-xs text-gray-400 mt-2">Exibindo os 50 primeiros resultados</p>';
        return h;
    }

    function renderDetalheNotas(detalhes) {
        var h = '<div class="overflow-x-auto"><table class="min-w-full text-xs">';
        h += '<thead><tr class="border-b border-gray-200">';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Número</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Série</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Modelo</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Data</th>';
        h += '<th class="text-right py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor</th>';
        h += '</tr></thead><tbody>';
        detalhes.forEach(function(d) {
            h += '<tr class="border-b border-gray-100">';
            h += '<td class="py-1.5 pr-4">' + (d.nota_id ? '<a href="/app/notas-fiscais/efd/' + d.nota_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.numero || '')) + '</a>' : escapeHtml(String(d.numero || ''))) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.serie || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.modelo || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 truncate max-w-[200px]">' + (d.participante_id ? '<a href="/app/participante/' + d.participante_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.participante || '')) + '</a>' : escapeHtml(String(d.participante || 'N/A'))) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.data_emissao || '')) + '</td>';
            h += '<td class="py-1.5 text-right text-gray-700">' + formatBrl(d.valor_total || 0) + '</td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        if (detalhes.length >= 50) h += '<p class="text-xs text-gray-400 mt-2">Exibindo os 50 primeiros resultados</p>';
        return h;
    }

    function renderDetalheParticipantes(detalhes) {
        var h = '<div class="overflow-x-auto"><table class="min-w-full text-xs">';
        h += '<thead><tr class="border-b border-gray-200">';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CNPJ/CPF</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Razão Social</th>';
        h += '<th class="text-right py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>';
        h += '</tr></thead><tbody>';
        detalhes.forEach(function(d) {
            h += '<tr class="border-b border-gray-100">';
            h += '<td class="py-1.5 pr-4 text-gray-700 font-mono text-xs">' + escapeHtml(String(d.cnpj || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 truncate max-w-[200px]">' + (d.participante_id ? '<a href="/app/participante/' + d.participante_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.razao_social || '')) + '</a>' : escapeHtml(String(d.razao_social || ''))) + '</td>';
            h += '<td class="py-1.5 text-right text-gray-700">' + d.total_notas + '</td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        return h;
    }

    function renderDetalheCfops(detalhes) {
        var h = '<div class="overflow-x-auto"><table class="min-w-full text-xs">';
        h += '<thead><tr class="border-b border-gray-200">';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Nota N.</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Série</th>';
        h += '<th class="text-left py-2 pr-4 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Operação</th>';
        h += '<th class="text-left py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CFOP</th>';
        h += '</tr></thead><tbody>';
        detalhes.forEach(function(d) {
            h += '<tr class="border-b border-gray-100">';
            h += '<td class="py-1.5 pr-4">' + (d.nota_id ? '<a href="/app/notas-fiscais/efd/' + d.nota_id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(String(d.numero || '')) + '</a>' : escapeHtml(String(d.numero || ''))) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.serie || '')) + '</td>';
            h += '<td class="py-1.5 pr-4 text-gray-700">' + escapeHtml(String(d.tipo_operacao || '')) + '</td>';
            h += '<td class="py-1.5 text-gray-700 font-mono">' + escapeHtml(String(d.cfop || '')) + '</td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        if (detalhes.length >= 50) h += '<p class="text-xs text-gray-400 mt-2">Exibindo os 50 primeiros resultados</p>';
        return h;
    }

    function renderDetalheGaps(detalhes) {
        var labels = detalhes.map(function(m) { return mesLabel(m); });
        var h = '<div class="flex flex-wrap gap-2">';
        labels.forEach(function(l) {
            h += '<span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color:#d97706">' + escapeHtml(l) + '</span>';
        });
        h += '</div>';
        return h;
    }

    function renderDetalhePisCofins(detalhes) {
        var h = '<div class="grid grid-cols-3 gap-4 text-center">';
        h += '<div class="bg-gray-50 rounded p-3">';
        h += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total de itens</p>';
        h += '<p class="text-lg font-bold text-gray-900">' + formatNum(detalhes.total_itens) + '</p>';
        h += '</div>';
        h += '<div class="bg-gray-50 rounded p-3">';
        h += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Itens sem tributo</p>';
        h += '<p class="text-lg font-bold text-gray-900">' + formatNum(detalhes.itens_sem_tributo) + '</p>';
        h += '</div>';
        h += '<div class="bg-gray-50 rounded p-3">';
        h += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Percentual</p>';
        h += '<p class="text-lg font-bold text-gray-900">' + detalhes.percentual + '%</p>';
        h += '</div>';
        h += '</div>';
        return h;
    }

    // ─── Helpers ─────────────────────────────────────────────

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Compliance ──────────────────────────────────────────

    function formatCnpj(cnpj) {
        if (!cnpj) return '—';
        const digits = cnpj.replace(/\D/g, '');
        if (digits.length === 14) {
            return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        }
        if (digits.length === 11) {
            return digits.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        }
        return cnpj;
    }

    async function loadCompliance() {
        const params = getFilterParams();
        const tabelaWrap = document.getElementById('dnf-comp-tabela-wrap');
        const emptyEl = document.getElementById('dnf-comp-empty');

        try {
            const resp = await fetch('/app/notas-fiscais/dashboard/compliance?' + params, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar compliance');
            compData = await resp.json();
            compLoaded = true;

            if (!compData.participantes || compData.participantes.length === 0) {
                tabelaWrap.classList.add('hidden');
                emptyEl.classList.remove('hidden');
                renderCompKpis(compData.kpis);
                return;
            }

            tabelaWrap.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            renderCompKpis(compData.kpis);
            renderCompTabela(compData.participantes);
        } catch (e) {
            console.error('Erro compliance:', e);
            tabelaWrap.classList.add('hidden');
            emptyEl.classList.remove('hidden');
        }
    }

    function renderCompKpis(kpis) {
        const el = (id) => document.getElementById(id);
        el('dnf-comp-consultados').textContent = kpis.consultados + ' / ' + kpis.total;
        el('dnf-comp-consultados-pct').textContent = kpis.consultados_pct + '% dos participantes';
        el('dnf-comp-regulares').textContent = kpis.regulares;
        el('dnf-comp-regulares-pct').textContent = kpis.regulares_pct + '% dos consultados';
        el('dnf-comp-exposicao').textContent = formatBrl(kpis.exposicao);
        el('dnf-comp-exposicao-sub').textContent = kpis.irregulares + ' participante' + (kpis.irregulares !== 1 ? 's' : '') + ' irregular' + (kpis.irregulares !== 1 ? 'es' : '');
        el('dnf-comp-nao-consultados').textContent = kpis.nao_consultados;
        el('dnf-comp-nao-consultados-sub').textContent = 'Sem consulta realizada';
    }

    function renderCompTabela(participantes) {
        let filtered = participantes;
        if (compFiltroLocal === 'regulares') {
            filtered = participantes.filter(p => p.situacao_cadastral === 'ATIVA');
        } else if (compFiltroLocal === 'irregulares') {
            filtered = participantes.filter(p => p.irregular);
        } else if (compFiltroLocal === 'nao_consultados') {
            filtered = participantes.filter(p => !p.ultima_consulta_em);
        }

        if (compOrdem === 'volume') {
            filtered.sort((a, b) => b.volume - a.volume);
        } else if (compOrdem === 'consulta') {
            filtered.sort((a, b) => {
                if (!a.ultima_consulta_em && !b.ultima_consulta_em) return 0;
                if (!a.ultima_consulta_em) return 1;
                if (!b.ultima_consulta_em) return -1;
                return b.ultima_consulta_em.localeCompare(a.ultima_consulta_em);
            });
        } else if (compOrdem === 'nome') {
            filtered.sort((a, b) => (a.razao_social || '').localeCompare(b.razao_social || ''));
        }

        const tbody = document.getElementById('dnf-comp-tbody');
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Nenhum participante neste filtro</td></tr>';
            return;
        }

        let html = '';
        filtered.forEach((p, i) => {
            const rowBg = i % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            let badgeSituacao;
            if (!p.situacao_cadastral) {
                badgeSituacao = '<span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="background-color:#9ca3af">Não consultado</span>';
            } else if (p.situacao_cadastral === 'ATIVA') {
                badgeSituacao = '<span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="background-color:#047857">ATIVA</span>';
            } else {
                badgeSituacao = '<span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="background-color:#dc2626">' + escapeHtml(p.situacao_cadastral) + '</span>';
            }

            const volumeClass = p.irregular && p.volume > 0 ? 'text-red-600 font-semibold' : 'text-gray-900';

            html += '<tr class="border-t border-gray-100 ' + rowBg + '">'
                + '<td class="px-3 sm:px-4 py-3 font-medium max-w-[200px] truncate" title="' + escapeHtml(p.razao_social) + '"><a href="/app/participante/' + p.id + '" class="text-gray-900 hover:text-gray-600 hover:underline">' + escapeHtml(p.razao_social) + '</a></td>'
                + '<td class="px-3 sm:px-4 py-3 text-gray-600 text-xs font-mono">' + formatCnpj(p.cnpj) + '</td>'
                + '<td class="px-3 sm:px-4 py-3 text-gray-600">' + escapeHtml(p.uf || '—') + '</td>'
                + '<td class="px-3 sm:px-4 py-3">' + badgeSituacao + '</td>'
                + '<td class="px-3 sm:px-4 py-3 text-gray-600 text-xs">' + escapeHtml(p.regime_tributario || '—') + '</td>'
                + '<td class="px-3 sm:px-4 py-3 text-right ' + volumeClass + '">' + formatBrl(p.volume) + '</td>'
                + '<td class="px-3 sm:px-4 py-3 text-gray-600 text-xs">' + (p.ultima_consulta_em || '<span class="text-yellow-600">Nunca</span>') + '</td>'
                + '<td class="px-3 sm:px-4 py-3 text-center">'
                + '<button disabled class="px-2.5 py-1 text-xs font-medium rounded bg-gray-100 text-gray-400 cursor-not-allowed" title="Em breve — consulta via InfoSimples">Consultar</button>'
                + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
    }

    function setupCompFiltroLocal() {
        const filtroEl = document.getElementById('dnf-comp-filtro-local');
        if (!filtroEl) return;

        compFiltroHandler = function(e) {
            const btn = e.target.closest('[data-filtro]');
            if (!btn) return;
            const filtro = btn.getAttribute('data-filtro');
            if (filtro === compFiltroLocal) return;

            compFiltroLocal = filtro;
            filtroEl.querySelectorAll('[data-filtro]').forEach(b => {
                b.className = b.getAttribute('data-filtro') === filtro
                    ? 'px-3 py-1.5 text-xs font-medium rounded bg-gray-800 text-white'
                    : 'px-3 py-1.5 text-xs font-medium rounded bg-white border border-gray-300 text-gray-600 hover:bg-gray-50';
            });

            if (compData && compData.participantes) {
                renderCompTabela(compData.participantes);
            }
        };

        filtroEl.addEventListener('click', compFiltroHandler);
    }

    function setupCompOrdem() {
        const ordenarEl = document.getElementById('dnf-comp-ordenar');
        if (!ordenarEl) return;

        compOrdemHandler = function() {
            compOrdem = ordenarEl.value;
            if (compData && compData.participantes) {
                renderCompTabela(compData.participantes);
            }
        };

        ordenarEl.addEventListener('change', compOrdemHandler);
    }

    // ─── Tabs ────────────────────────────────────────────────

    function setupTabs() {
        const tabButtons = container.querySelectorAll('[data-tab]');
        const tabContents = container.querySelectorAll('.dnf-tab-content');

        tabClickHandler = function(e) {
            const btn = e.target.closest('[data-tab]');
            if (!btn) return;

            const tabName = btn.getAttribute('data-tab');
            if (tabName === currentTab) return;

            currentTab = tabName;

            tabButtons.forEach(b => {
                b.className = b.getAttribute('data-tab') === tabName
                    ? 'dnf-tab ' + ACTIVE_CLASS
                    : 'dnf-tab ' + INACTIVE_CLASS;
            });

            tabContents.forEach(tc => {
                const id = tc.id.replace('tab-', '');
                tc.classList.toggle('hidden', id !== tabName);
            });

            // Lazy load data on first tab activation
            if (tabName === 'cfop' && !cfopLoaded) {
                loadCfop();
            } else if (tabName === 'participantes' && !partLoaded) {
                loadParticipantes();
            } else if (tabName === 'tributario' && !tribLoaded) {
                loadTributario();
            } else if (tabName === 'alertas' && !alertasLoaded) {
                loadAlertas();
            } else if (tabName === 'compliance' && !compLoaded) {
                loadCompliance();
            }
        };

        tabButtons.forEach(btn => btn.addEventListener('click', tabClickHandler));
    }

    function setupFiltros() {
        const btnFiltrar = document.getElementById('dnf-btn-filtrar');
        if (!btnFiltrar) return;

        filtrarClickHandler = function() {
            if (currentTab === 'visao-geral') {
                loadVisaoGeral();
            } else if (currentTab === 'cfop') {
                cfopLoaded = false;
                loadCfop();
            } else if (currentTab === 'participantes') {
                partLoaded = false;
                partPage = 1;
                loadParticipantes();
            } else if (currentTab === 'tributario') {
                tribLoaded = false;
                loadTributario();
            } else if (currentTab === 'alertas') {
                alertasLoaded = false;
                loadAlertas();
            } else if (currentTab === 'compliance') {
                compLoaded = false;
                loadCompliance();
            }
        };

        btnFiltrar.addEventListener('click', filtrarClickHandler);
    }

    function cleanup() {
        const tabButtons = container.querySelectorAll('[data-tab]');
        if (tabClickHandler) {
            tabButtons.forEach(btn => btn.removeEventListener('click', tabClickHandler));
        }
        const btnFiltrar = document.getElementById('dnf-btn-filtrar');
        if (btnFiltrar && filtrarClickHandler) {
            btnFiltrar.removeEventListener('click', filtrarClickHandler);
        }
        if (evolucaoChart) {
            evolucaoChart.destroy();
            evolucaoChart = null;
        }
        const cfopFiltroEl = document.getElementById('dnf-cfop-filtro-local');
        if (cfopFiltroEl && cfopFiltroHandler) {
            cfopFiltroEl.removeEventListener('click', cfopFiltroHandler);
        }
        tabClickHandler = null;
        filtrarClickHandler = null;
        cfopFiltroHandler = null;
        cfopData = null;
        cfopLoaded = false;
        const partFiltroEl = document.getElementById('dnf-part-filtro-local');
        if (partFiltroEl && partFiltroHandler) {
            partFiltroEl.removeEventListener('click', partFiltroHandler);
        }
        const partBuscaEl = document.getElementById('dnf-part-busca');
        if (partBuscaEl && partBuscaHandler) {
            partBuscaEl.removeEventListener('input', partBuscaHandler);
        }
        clearTimeout(partBuscaTimeout);
        partFiltroHandler = null;
        partBuscaHandler = null;
        partData = null;
        partLoaded = false;
        if (tribChart) {
            tribChart.destroy();
            tribChart = null;
        }
        var tribCstBtn = document.getElementById('dnf-trib-cst-toggle');
        if (tribCstBtn && tribCstToggleHandler) {
            tribCstBtn.removeEventListener('click', tribCstToggleHandler);
        }
        tribCstToggleHandler = null;
        tribData = null;
        tribLoaded = false;
        tribCstOpen = false;
        alertasData = null;
        alertasLoaded = false;
        const compFiltroEl = document.getElementById('dnf-comp-filtro-local');
        if (compFiltroEl && compFiltroHandler) {
            compFiltroEl.removeEventListener('click', compFiltroHandler);
        }
        const compOrdenarEl = document.getElementById('dnf-comp-ordenar');
        if (compOrdenarEl && compOrdemHandler) {
            compOrdenarEl.removeEventListener('change', compOrdemHandler);
        }
        compFiltroHandler = null;
        compOrdemHandler = null;
        compData = null;
        compLoaded = false;
        compFiltroLocal = 'todos';
        compOrdem = 'volume';
    }

    // ─── Init ────────────────────────────────────────────────

    setupTabs();
    setupFiltros();
    setupCfopFiltroLocal();
    setupPartFiltroLocal();
    setupPartBusca();
    setupTribCstToggle();
    setupCompFiltroLocal();
    setupCompOrdem();
    loadVisaoGeral();

    // Registrar cleanup para SPA navigation
    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.dashboardNotasFiscais = cleanup;
})();
</script>
