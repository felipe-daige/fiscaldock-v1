{{-- Monitoramento - Consulta Avulsa (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="monitoramento-avulso-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        {{-- Header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Consulta Avulsa</h1>
                <p class="text-xs text-gray-500 mt-1">Consulte a situação cadastral e fiscal de CNPJs.</p>
            </div>
            <a href="/app/consulta/nova" data-link
               class="inline-flex items-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>

        <div id="monitoramento-avulso-error-region" class="mb-6"></div>

        {{-- Info Box --}}
        <div class="bg-white rounded border border-gray-300 border-l-4 border-l-blue-500 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Como funciona?</h3>
                    <p class="text-xs text-gray-700 mt-1">
                        Consulte a situação cadastral e fiscal de CNPJs individualmente. Escolha o tipo de consulta e receba informações detalhadas sobre fornecedores e clientes.
                    </p>
                </div>
            </div>
        </div>

        {{-- Grid: Formulario + Info --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 items-stretch">
            {{-- Card Esquerdo: Nova Consulta --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden h-full flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Nova Consulta</h2>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <form id="form-consulta-avulsa" class="flex-1 flex flex-col">
                        @php
                            [$empresasProprias, $clientesAtivos] = collect($clientes ?? [])->partition(fn($c) => $c->is_empresa_propria);
                        @endphp

                        {{-- Step 1: De onde vem o CNPJ? --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">De onde vem o CNPJ?</label>
                            <div class="flex flex-wrap gap-2" id="origem-chips">
                                @if($empresasProprias->isNotEmpty())
                                <button type="button" class="chip-origem px-3 py-1.5 rounded-full border border-gray-300 text-sm font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-origem="propria">
                                    Minha Empresa
                                </button>
                                @endif
                                @if($clientesAtivos->isNotEmpty())
                                <button type="button" class="chip-origem px-3 py-1.5 rounded-full border border-gray-300 text-sm font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-origem="cliente">
                                    Cliente
                                </button>
                                @endif
                                @if(($participantes ?? collect())->isNotEmpty())
                                <button type="button" class="chip-origem px-3 py-1.5 rounded-full border border-gray-300 text-sm font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-origem="participante">
                                    Participante
                                </button>
                                @endif
                                <button type="button" class="chip-origem px-3 py-1.5 rounded-full border border-gray-300 text-sm font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-origem="novo">
                                    Novo CNPJ
                                </button>
                            </div>
                        </div>

                        {{-- Step 2: Selecione o CNPJ (paineis show/hide) --}}
                        <div id="step2-wrapper" class="mb-4">
                            {{-- Painel Propria Empresa --}}
                            <div id="painel-propria" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione a empresa:</label>
                                <select id="select-propria" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 text-sm">
                                    <option value="">Selecione...</option>
                                    @foreach($empresasProprias as $ep)
                                        <option value="{{ preg_replace('/\D/', '', $ep->documento) }}" data-razao="{{ $ep->razao_social ?? $ep->nome }}" data-cliente-id="{{ $ep->id }}">
                                            {{ $ep->razao_social ?? $ep->nome }}
                                            ({{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', preg_replace('/\D/', '', $ep->documento)) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Painel Cliente --}}
                            <div id="painel-cliente" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar cliente:</label>
                                <div class="relative">
                                    <input type="text" id="busca-cliente" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 text-sm" placeholder="Digite o nome ou CNPJ..." autocomplete="off">
                                    <div id="dropdown-cliente" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded max-h-48 overflow-y-auto"></div>
                                </div>
                            </div>

                            {{-- Painel Participante --}}
                            <div id="painel-participante" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar participante:</label>
                                <div class="relative">
                                    <input type="text" id="busca-participante-select" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 text-sm" placeholder="Digite o nome ou CNPJ..." autocomplete="off">
                                    <div id="dropdown-participante" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded max-h-48 overflow-y-auto"></div>
                                </div>
                            </div>

                            {{-- Painel Novo CNPJ --}}
                            <div id="painel-novo" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Salvar como:</label>
                                <div class="flex gap-2 mb-3" id="novo-subtipo-chips">
                                    <button type="button" class="chip-novo-subtipo px-3 py-1.5 rounded-full border border-gray-300 text-xs font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-subtipo="participante">Participante</button>
                                    <button type="button" class="chip-novo-subtipo px-3 py-1.5 rounded-full border border-gray-300 text-xs font-medium text-gray-600 bg-white hover:border-gray-400 transition-colors" data-subtipo="cliente">Cliente</button>
                                </div>
                                <input
                                    type="text"
                                    id="cnpj-input"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 font-mono"
                                    placeholder="00.000.000/0000-00"
                                    maxlength="18"
                                    autocomplete="off"
                                >
                                <p class="mt-1 text-xs text-gray-500">Digite o CNPJ que deseja consultar.</p>
                            </div>

                            {{-- CNPJ Confirmacao chip --}}
                            <div id="cnpj-confirmacao" class="hidden mt-3">
                                <div class="inline-flex items-center gap-2 px-3 py-2 rounded border border-gray-300 border-l-4 border-l-blue-500 bg-white max-w-full">
                                    <div class="min-w-0">
                                        <p id="confirmacao-razao" class="text-sm font-semibold text-gray-900 truncate"></p>
                                        <p id="confirmacao-cnpj" class="text-xs text-gray-500 font-mono"></p>
                                    </div>
                                    <button type="button" id="btn-limpar-selecao" class="flex-shrink-0 p-0.5 text-gray-400 hover:text-gray-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3 + Step 4 wrapper (disabled until CNPJ resolved) --}}
                        <div id="steps-34-wrapper" class="opacity-40 pointer-events-none transition-opacity duration-200">

                        {{-- Selecao do Plano - Radios hidden + Card visual --}}
                        @php
                            // Metadata visual por codigo de plano (DB)
                            $planoMeta = [
                                'gratuito' => [
                                    'cor' => 'green',
                                    'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'consultas_display' => ['Situação Cadastral (Ativa, Inapta, Baixada)', 'Dados Cadastrais Completos', 'CNAEs Principal e Secundários', 'Quadro Societário (QSA)', 'Simples Nacional e MEI'],
                                    'casos_uso' => ['Checar se CNPJ está ativo', 'Conferir regime para emitir NF', 'Consultar sócios e QSA'],
                                ],
                                'validacao' => [
                                    'cor' => 'blue',
                                    'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                                    'consultas_display' => ['Situação Cadastral (Ativa, Inapta, Baixada)', 'Dados Completos, CNAEs e QSA', 'Simples Nacional e MEI', 'SINTEGRA — IE ativa em todos os estados', 'TCU Consolidada (CEIS, CNEP, Inidôneos)'],
                                    'casos_uso' => ['Conferir IE interestadual', 'Checar listas restritivas do TCU', 'Qualificar novos fornecedores'],
                                ],
                                'licitacao' => [
                                    'cor' => 'blue',
                                    'icone' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                                    'consultas_display' => ['Tudo do Validação', 'CND Federal (PGFN/RFB)', 'CRF FGTS (Regularidade)', 'CND Estadual (ICMS)', 'CNDT Trabalhista (TST)'],
                                    'casos_uso' => ['Documentação para editais', 'Homologar com CNDs exigidas', 'Renovar contratos públicos'],
                                    'promo' => true,
                                ],
                                'compliance' => [
                                    'cor' => 'purple',
                                    'icone' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                                    'consultas_display' => ['Situação Cadastral e Dados Completos', 'SINTEGRA e TCU Consolidada', 'CND Federal, Estadual, CRF e CNDT', 'Protestos em Cartório (IEPTB Nacional)', 'Devedores da Dívida Ativa (PGFN)', 'Análise completa de risco financeiro'],
                                    'casos_uso' => ['Gestão de risco de terceiros', 'Atender Lei Anticorrupção', 'Monitorar protestos e dívidas'],
                                ],
                                'due_diligence' => [
                                    'cor' => 'amber',
                                    'icone' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7',
                                    'consultas_display' => ['Todas as CNDs (Federal, Estadual, FGTS, Trabalhista)', 'Protestos e Devedores PGFN', 'SINTEGRA e TCU Consolidada', 'Trabalho Escravo (Lista Suja — MTE)', 'IBAMA — Autuações Ambientais', 'Compliance trabalhista e ambiental (ESG)'],
                                    'casos_uso' => ['Análise pré-aquisição (M&A)', 'Atender requisitos ESG', 'Riscos trabalhistas e ambientais'],
                                ],
                                'enterprise' => [
                                    'cor' => 'slate',
                                    'icone' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                                    'consultas_display' => ['Todas as CNDs e Certidões', 'Protestos, Dívida Ativa e TCU', 'Trabalho Escravo e IBAMA (ESG)', 'Processos Judiciais (CNJ/SEEU)', 'SINTEGRA — Inscrição Estadual', 'Raio-X completo — 18 consultas por CNPJ'],
                                    'casos_uso' => ['Due diligence jurídico completo', 'Mapear litígios antes de contratar', 'Relatório para comitês internos'],
                                ],
                            ];

                            // Gerar $planosDetalhados a partir dos planos do DB
                            $planosDetalhados = [];
                            foreach ($planos as $p) {
                                $meta = $planoMeta[$p->codigo] ?? ['cor' => 'gray', 'icone' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'consultas_display' => [], 'casos_uso' => []];
                                $planosDetalhados[] = [
                                    'codigo' => $p->codigo,
                                    'nome' => $p->nome,
                                    'creditos' => $p->custo_creditos,
                                    'creditos_original' => null,
                                    'promo' => $meta['promo'] ?? false,
                                    'gratuito' => $p->is_gratuito,
                                    'descricao' => $p->descricao,
                                    'cor' => $meta['cor'],
                                    'icone' => $meta['icone'],
                                    'consultas' => $meta['consultas_display'],
                                    'casos_uso' => $meta['casos_uso'],
                                ];
                            }

                            // Hex codes por cor para DANFE Modernizado (Tailwind v4 não renderiza bg classes em badges)
                            $corClasses = [
                                'green'  => ['bg' => '#d1fae5', 'text' => '#047857', 'icon' => '#047857', 'border' => '#047857', 'btn' => '#047857'],
                                'blue'   => ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'icon' => '#1d4ed8', 'border' => '#1d4ed8', 'btn' => '#1d4ed8'],
                                'purple' => ['bg' => '#ede9fe', 'text' => '#6d28d9', 'icon' => '#6d28d9', 'border' => '#6d28d9', 'btn' => '#6d28d9'],
                                'amber'  => ['bg' => '#fef3c7', 'text' => '#b45309', 'icon' => '#b45309', 'border' => '#b45309', 'btn' => '#b45309'],
                                'slate'  => ['bg' => '#f1f5f9', 'text' => '#334155', 'icon' => '#334155', 'border' => '#334155', 'btn' => '#334155'],
                                'gray'   => ['bg' => '#f3f4f6', 'text' => '#374151', 'icon' => '#374151', 'border' => '#374151', 'btn' => '#374151'],
                            ];
                        @endphp

                        {{-- Hidden radio inputs (sr-only) --}}
                        <div class="sr-only" id="planos-grid">
                            @foreach($planos as $index => $plano)
                                @php
                                    $pdMatch = collect($planosDetalhados)->firstWhere('codigo', $plano->codigo);
                                    $creditosEfetivos = $pdMatch ? $pdMatch['creditos'] : $plano->custo_creditos;
                                @endphp
                                <input
                                    type="radio"
                                    name="plano"
                                    value="{{ $plano->codigo }}"
                                    data-creditos="{{ $creditosEfetivos }}"
                                    {{ $index === 0 ? 'checked' : '' }}
                                >
                            @endforeach
                        </div>

                        {{-- Card: Plano Selecionado --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Plano selecionado:
                            </label>
                            <div id="plano-display-card" class="rounded border border-gray-300 border-l-4 border-l-green-500 bg-white overflow-hidden">
                                <div class="p-4">
                                    {{-- Header: icon + name + badge --}}
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3.5">
                                            <div id="plano-display-icon-wrapper" class="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center" style="background-color: #d1fae5">
                                                <svg id="plano-display-icon" class="w-4 h-4" style="color: #047857" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <span id="plano-display-nome" class="text-sm font-bold text-gray-900">{{ $planosDetalhados[0]['nome'] ?? 'Gratuito' }}</span>
                                                <p id="plano-display-descricao" class="text-xs text-gray-500 mt-0.5">{{ $planosDetalhados[0]['descricao'] ?? '' }}</p>
                                            </div>
                                        </div>
                                        <span id="plano-display-badge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap flex-shrink-0 ml-2" style="background-color: #047857">
                                            Grátis
                                        </span>
                                    </div>

                                    {{-- Consultas incluidas --}}
                                    <div class="mb-3">
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Consultas incluídas</p>
                                        <ul id="plano-display-consultas" class="space-y-0.5">
                                            <li class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Situacao Cadastral
                                            </li>
                                            <li class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Dados Cadastrais Completos
                                            </li>
                                            <li class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                CNAEs Principal e Secundarios
                                            </li>
                                            <li class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Quadro Societario (QSA)
                                            </li>
                                            <li class="flex items-center gap-1.5 text-[11px] text-gray-600">
                                                <svg class="w-3 h-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Simples Nacional e MEI
                                            </li>
                                        </ul>
                                    </div>

                                    {{-- Botao Alterar plano --}}
                                    <button
                                        type="button"
                                        id="btn-alterar-plano"
                                        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        Alterar plano
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">Clique em "Alterar plano" para ver todos os planos disponíveis.</p>
                        </div>

                        {{-- Resumo e Submit --}}
                        <div class="bg-gray-50 rounded border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Custo:</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        <span id="custo-total">0</span> créditos
                                    </p>
                                    <p class="text-xs text-gray-500">Saldo: <strong>{{ $credits ?? 0 }}</strong> créditos</p>
                                </div>
                                <button
                                    type="submit"
                                    id="btn-consultar"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded bg-gray-800 text-white text-sm font-semibold transition hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    <svg class="w-4 h-4 btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <svg class="btn-spinner hidden w-4 h-4 animate-spin" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <span class="btn-text">Consultar</span>
                                </button>
                            </div>
                        </div>
                        </div>{{-- /steps-34-wrapper --}}
                    </form>
                </div>
            </div>

            {{-- Card Direito: Como Funciona --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden h-full flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-base font-semibold text-gray-900">Como Funciona</h3>
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    {{-- Passo a passo --}}
                        <div class="mb-6 flex-shrink-0">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Como Funciona</h4>
                            <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold">1</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Digite o CNPJ</p>
                                    <p class="text-xs text-gray-500">Informe o CNPJ do fornecedor ou cliente que deseja consultar. Opcionalmente, associe a um cliente para melhor organização.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold">2</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Escolha o tipo de consulta</p>
                                    <p class="text-xs text-gray-500">Quanto mais completa, mais informações você recebe</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold">3</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Notificações</p>
                                    <p class="text-xs text-gray-500">Configure frequência automática de consultas (semanal, mensal ou trimestral) e receba notificações sobre alterações</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background-color: #047857">4</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Resultado salvo automaticamente</p>
                                    <p class="text-xs text-gray-500">O participante será adicionado à sua lista para futuras consultas</p>
                                </div>
                            </div>
                            </div>
                        </div>

                        {{-- Planos disponiveis - Badges compactos --}}
                        <div class="border-t border-gray-200 pt-4 mt-4 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-gray-900">Planos disponíveis</h4>
                                <button type="button" id="btn-ver-detalhes-planos" class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
                                    Ver detalhes
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex flex-col gap-2 w-full">
                                @foreach($planosDetalhados as $idx => $pd)
                                    @php
                                        $badgeHex = match($pd['cor']) {
                                            'green' => '#047857',
                                            'blue' => '#1d4ed8',
                                            'purple' => '#6d28d9',
                                            'amber' => '#b45309',
                                            'slate' => '#334155',
                                            default => '#374151',
                                        };
                                    @endphp
                                    @if($pd['promo'] ?? false)
                                        <button
                                            type="button"
                                            class="badge-plano group w-full flex items-center justify-between gap-2 px-3 py-2 rounded border border-gray-200 border-l-4 bg-white hover:bg-gray-50 transition-colors cursor-pointer text-left"
                                            style="border-left-color: #d97706"
                                            data-slide-index="{{ $idx }}"
                                        >
                                            <div class="flex-1 min-w-0">
                                                <span class="text-xs font-semibold text-gray-800 group-hover:text-gray-900 transition-colors">{{ $pd['nome'] }}</span>
                                                <p class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors truncate">{{ $pd['descricao'] }}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap flex-shrink-0" style="background-color: #d97706">
                                                {{ $pd['creditos'] }} cred.
                                            </span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="badge-plano group w-full flex items-center justify-between gap-2 px-3 py-2 rounded border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 transition-colors cursor-pointer text-left"
                                            data-slide-index="{{ $idx }}"
                                        >
                                            <div class="flex-1 min-w-0">
                                                <span class="text-xs font-semibold text-gray-800 group-hover:text-gray-900 transition-colors">{{ $pd['nome'] }}</span>
                                                <p class="text-xs text-gray-400 group-hover:text-gray-500 transition-colors truncate">{{ $pd['descricao'] }}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white transition-colors whitespace-nowrap flex-shrink-0" style="background-color: {{ $badgeHex }}">
                                                {{ $pd['gratuito'] ? 'Grátis' : $pd['creditos'] . ' cred.' }}
                                            </span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Clique para ver detalhes.</p>
                        </div>
                </div>
            </div>
        </div>

        {{-- Modal de Progresso --}}
        <div id="modal-progresso" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded border border-gray-300 max-w-xs w-full mx-4 p-5">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h3 id="progresso-titulo" class="text-sm font-semibold text-gray-900 mb-1">Processando consulta...</h3>
                    <p id="progresso-mensagem" class="text-xs text-gray-500 mb-3">Aguarde enquanto processamos.</p>
                    <div class="w-full bg-gray-100 rounded-full h-1 mb-1">
                        <div id="progresso-barra" class="bg-gray-900 h-1 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progresso-percentual" class="text-xs text-gray-400">0%</p>
                </div>
            </div>
        </div>

        {{-- Modal de Sucesso --}}
        <div id="modal-sucesso" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded border border-gray-300 max-w-xs w-full mx-4 p-5">
                <div class="text-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: #d1fae5">
                        <svg class="w-5 h-5" style="color: #047857" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Consulta concluída</h3>
                    <p id="sucesso-mensagem" class="text-xs text-gray-500 mb-4">Resultado pronto para download.</p>
                    <div class="flex gap-2">
                        <a id="link-download-manual" href="#" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Baixar
                        </a>
                        <button type="button" id="btn-fechar-sucesso" class="flex-1 py-2 border border-gray-300 bg-white text-gray-700 rounded hover:bg-gray-50 transition text-sm font-medium">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de Erro --}}
        <div id="modal-erro" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded border border-gray-300 mx-4 p-4" style="max-width: 280px;">
                <div class="text-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mx-auto mb-2" style="background-color: #fee2e2">
                        <svg class="w-4 h-4" style="color: #b91c1c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Erro</h3>
                    <p id="erro-mensagem" class="text-xs text-gray-500 mb-3 break-words">Ocorreu um erro inesperado.</p>
                    <button type="button" id="btn-fechar-erro" class="w-full py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition text-sm font-medium">
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal: Carousel de Planos --}}
        <div id="modal-planos-carousel" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded border border-gray-300 max-w-lg w-full mx-4 max-h-[90vh] flex flex-col relative overflow-visible">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="text-base font-semibold text-gray-900">Detalhes dos Planos</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="carousel-counter" class="text-xs text-gray-400">1 / {{ count($planosDetalhados) }}</span>
                        <button type="button" id="btn-fechar-carousel" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Navigation arrows (overlay) --}}
                <button type="button" id="swiper-planos-prev" class="absolute -left-5 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white border border-gray-300 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button type="button" id="swiper-planos-next" class="absolute -right-5 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white border border-gray-300 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                {{-- Swiper Carousel --}}
                <div class="flex-1 overflow-hidden relative">
                    <div class="swiper h-full" id="swiper-planos">
                        <div class="swiper-wrapper">
                            @foreach($planosDetalhados as $idx => $pd)
                                @php $cores = $corClasses[$pd['cor']]; @endphp
                                <div class="swiper-slide">
                                    <div class="p-5 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                                        {{-- Plan header --}}
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="flex-shrink-0 w-9 h-9 rounded flex items-center justify-center" style="background-color: {{ $cores['bg'] }}">
                                                <svg class="w-[18px] h-[18px]" style="color: {{ $cores['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $pd['icone'] }}"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-base font-bold text-gray-900">{{ $pd['nome'] }}</h4>
                                                @if($pd['promo'] ?? false)
                                                    <span class="text-sm text-amber-700 font-semibold">{{ $pd['creditos'] }} cred./CNPJ</span>
                                                @else
                                                    <span class="text-sm {{ $pd['gratuito'] ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                                                        {{ $pd['gratuito'] ? 'Gratuito' : $pd['creditos'] . ' créditos/CNPJ' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($pd['promo'] ?? false)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ $pd['creditos'] }} cred.</span>
                                            @elseif($pd['gratuito'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cores['btn'] }}">{{ $pd['creditos'] }} cred.</span>
                                            @endif
                                        </div>

                                        {{-- Description --}}
                                        <p class="text-sm text-gray-600 mb-3">{{ $pd['descricao'] }}</p>

                                        @if($pd['promo'] ?? false)
                                            <div class="p-3.5 bg-gray-50 border border-gray-200 border-l-4 rounded mb-3" style="border-left-color: #d97706">
                                                <p class="text-xs font-semibold text-amber-800">&#x1f3f7;&#xfe0e; Oferta por tempo limitado</p>
                                                <p class="text-xs text-amber-700 mt-0.5">Todas as CNDs por {{ $pd['creditos'] }} créd./CNPJ — aproveite antes do reajuste.</p>
                                            </div>
                                        @endif

                                        {{-- Consultas incluidas --}}
                                        <div class="mb-3">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Consultas incluídas</p>
                                            <ul class="space-y-1">
                                                @foreach($pd['consultas'] as $consulta)
                                                    <li class="flex items-start gap-2 text-sm text-gray-700">
                                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color: {{ $cores['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <span>{{ $consulta }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        {{-- Quando usar --}}
                                        <div class="p-3 bg-gray-50 rounded border border-gray-200 mb-4">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quando usar</p>
                                            <ul class="space-y-1">
                                                @foreach($pd['casos_uso'] as $caso)
                                                    <li class="flex items-start gap-2 text-xs text-gray-600">
                                                        <svg class="w-3 h-3 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                        </svg>
                                                        <span>{{ $caso }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Footer fixo: botao selecionar --}}
                <div class="px-6 pb-4 pt-3 border-t border-gray-100 flex-shrink-0">
                    <button
                        type="button"
                        id="btn-selecionar-plano-footer"
                        class="btn-selecionar-plano w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold transition-colors"
                        data-plano-codigo="{{ $planosDetalhados[0]['codigo'] ?? '' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Selecionar este plano
                    </button>
                </div>

                {{-- Pagination dots --}}
                <div class="px-6 py-3 border-t border-gray-100 flex-shrink-0">
                    <div id="swiper-planos-pagination" class="flex justify-center"></div>
                </div>
            </div>
        </div>

        {{-- Secao: Participantes Cadastrados --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Participantes Cadastrados</h2>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            id="busca-participante"
                            class="px-3 py-2 border border-gray-300 rounded text-sm w-full sm:w-64 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="Buscar CNPJ ou razão social..."
                        >
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CNPJ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razão Social</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regime</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Consulta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white" id="participantes-tbody">
                        @forelse($participantes ?? [] as $participante)
                            <tr class="hover:bg-gray-50 participante-row" data-cnpj="{{ $participante->documento }}" data-razao="{{ $participante->razao_social ?? '' }}">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 whitespace-nowrap tabular-nums">
                                    {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $participante->documento) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $participante->razao_social ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($participante->situacao_cadastral === 'ATIVA')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Ativa</span>
                                    @elseif($participante->situacao_cadastral === 'BAIXADA' || $participante->situacao_cadastral === 'INAPTA')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">{{ $participante->situacao_cadastral }}</span>
                                    @elseif($participante->situacao_cadastral)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ $participante->situacao_cadastral }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $participante->regime_tributario ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $participante->ultima_consulta_em ? $participante->ultima_consulta_em->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="btn-reconsultar inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        data-cnpj="{{ $participante->documento }}"
                                        data-razao="{{ $participante->razao_social ?? '' }}"
                                        data-id="{{ $participante->id }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Reconsultar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-row">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Nenhum participante cadastrado ainda. Faça uma consulta para adicionar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #swiper-planos-pagination .swiper-pagination-bullet {
        width: 8px;
        height: 8px;
        background: #d1d5db;
        opacity: 1;
        margin: 0 4px;
        border-radius: 50%;
        transition: all 0.2s;
    }
    #swiper-planos-pagination .swiper-pagination-bullet-active {
        background: #3b82f6;
        width: 20px;
        border-radius: 4px;
    }
</style>

<script>
(function() {
    'use strict';

    function initMonitoramentoAvulso() {
        const container = document.getElementById('monitoramento-avulso-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento Avulso] Inicializando...');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const form = document.getElementById('form-consulta-avulsa');
        const errorRegion = document.getElementById('monitoramento-avulso-error-region');
        const cnpjInput = document.getElementById('cnpj-input');
        const custoTotal = document.getElementById('custo-total');
        const btnConsultar = document.getElementById('btn-consultar');
        const buscaParticipante = document.getElementById('busca-participante');
        const steps34 = document.getElementById('steps-34-wrapper');

        // ==========================================
        // State
        // ==========================================
        var state = {
            origemTipo: null,
            novoSubtipo: null,
            cnpjDigitos: '',
            clienteId: null,
            razaoSocial: '',
        };

        // ==========================================
        // Inline data for autocomplete (from Blade)
        // ==========================================
        var clientesData = {!! json_encode(
            $clientesAtivos->map(fn($c) => [
                'id' => $c->id,
                'cnpj' => preg_replace('/\D/', '', $c->documento),
                'razao' => $c->razao_social ?? $c->nome ?? '',
            ])->values()
        ) !!};

        var participantesData = {!! json_encode(
            ($participantes ?? collect())->map(fn($p) => [
                'id' => $p->id,
                'cnpj' => $p->documento,
                'razao' => $p->razao_social ?? '',
                'clienteId' => $p->cliente_id,
            ])->values()
        ) !!};

        // ==========================================
        // Helpers
        // ==========================================
        function formatarCnpj(valor) {
            valor = valor.replace(/\D/g, '');
            if (valor.length > 14) valor = valor.slice(0, 14);
            if (valor.length > 12) {
                valor = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
            } else if (valor.length > 8) {
                valor = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
            } else if (valor.length > 5) {
                valor = valor.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (valor.length > 2) {
                valor = valor.replace(/(\d{2})(\d{0,3})/, '$1.$2');
            }
            return valor;
        }

        function getCreditosPlano() {
            const planoSelecionado = document.querySelector('input[name="plano"]:checked');
            return planoSelecionado ? parseInt(planoSelecionado.dataset.creditos || 0) : 0;
        }

        function showInlineError(message, action) {
            if (window.showInlineError) {
                window.showInlineError(errorRegion, {
                    message: message,
                    context: {
                        action: action || 'monitoramento-avulso',
                        url: window.location.pathname + window.location.search,
                    },
                });
                return;
            }

            if (window.showToast) {
                window.showToast(message, 'warning');
                return;
            }

            alert(message);
        }

        function clearInlineError() {
            if (window.clearInlineError) {
                window.clearInlineError(errorRegion);
            }
        }

        function atualizarCalculos() {
            const creditosPlano = getCreditosPlano();
            const resolved = state.cnpjDigitos.length === 14;
            const total = resolved ? creditosPlano : 0;
            custoTotal.textContent = total.toLocaleString('pt-BR');
            btnConsultar.disabled = !resolved;
        }

        function setSteps34Enabled(enabled) {
            if (!steps34) return;
            if (enabled) {
                steps34.classList.remove('opacity-40', 'pointer-events-none');
            } else {
                steps34.classList.add('opacity-40', 'pointer-events-none');
            }
        }

        // ==========================================
        // Confirmation chip
        // ==========================================
        var confirmacao = document.getElementById('cnpj-confirmacao');
        var confirmacaoRazao = document.getElementById('confirmacao-razao');
        var confirmacaoCnpj = document.getElementById('confirmacao-cnpj');

        function showConfirmacao(cnpj14, razao) {
            state.cnpjDigitos = cnpj14;
            state.razaoSocial = razao;
            if (confirmacaoRazao) confirmacaoRazao.textContent = razao || 'CNPJ ' + formatarCnpj(cnpj14);
            if (confirmacaoCnpj) confirmacaoCnpj.textContent = formatarCnpj(cnpj14);
            if (confirmacao) confirmacao.classList.remove('hidden');
            // Hide the active panel input area (but keep the panel visible for context)
            hideActivePanelInput();
            setSteps34Enabled(true);
            atualizarCalculos();
        }

        function clearConfirmacao() {
            state.cnpjDigitos = '';
            state.clienteId = null;
            state.razaoSocial = '';
            if (confirmacao) confirmacao.classList.add('hidden');
            // Re-show the active panel input area
            showActivePanelInput();
            setSteps34Enabled(false);
            atualizarCalculos();
        }

        function hideActivePanelInput() {
            // For select-based panels, just leave them; for search panels, we can keep them
        }

        function showActivePanelInput() {
            // Nothing to do - panels stay visible
        }

        document.getElementById('btn-limpar-selecao')?.addEventListener('click', function() {
            clearConfirmacao();
            // Clear inputs in active panel
            var selectPropria = document.getElementById('select-propria');
            var buscaCliente = document.getElementById('busca-cliente');
            var buscaParticipanteSelect = document.getElementById('busca-participante-select');
            if (selectPropria) selectPropria.value = '';
            if (buscaCliente) buscaCliente.value = '';
            if (buscaParticipanteSelect) buscaParticipanteSelect.value = '';
            if (cnpjInput) cnpjInput.value = '';
        });

        // ==========================================
        // Step 1: Origem chips
        // ==========================================
        var painelIds = ['painel-propria', 'painel-cliente', 'painel-participante', 'painel-novo'];

        function hideAllPaineis() {
            painelIds.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
        }

        function setActiveChip(chip) {
            document.querySelectorAll('.chip-origem').forEach(function(c) {
                c.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
                c.classList.add('border-gray-300', 'text-gray-600', 'bg-white');
            });
            chip.classList.remove('border-gray-300', 'text-gray-600', 'bg-white');
            chip.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
        }

        document.querySelectorAll('.chip-origem').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var origem = this.dataset.origem;
                state.origemTipo = origem;
                state.novoSubtipo = null;

                setActiveChip(this);
                clearConfirmacao();
                hideAllPaineis();

                // Reset sub-chips for novo
                document.querySelectorAll('.chip-novo-subtipo').forEach(function(c) {
                    c.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
                    c.classList.add('border-gray-300', 'text-gray-600', 'bg-white');
                });

                // Show corresponding panel
                var painelMap = {
                    'propria': 'painel-propria',
                    'cliente': 'painel-cliente',
                    'participante': 'painel-participante',
                    'novo': 'painel-novo',
                };
                var painelId = painelMap[origem];
                if (painelId) {
                    var painel = document.getElementById(painelId);
                    if (painel) painel.classList.remove('hidden');
                }

                // If "novo", show CNPJ input and enable sub-chip default
                if (origem === 'novo') {
                    // Default to participante subtipo
                    state.novoSubtipo = 'participante';
                    var defaultSubChip = document.querySelector('.chip-novo-subtipo[data-subtipo="participante"]');
                    if (defaultSubChip) {
                        defaultSubChip.classList.remove('border-gray-300', 'text-gray-600', 'bg-white');
                        defaultSubChip.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
                    }
                    if (cnpjInput) cnpjInput.focus();
                }
            });
        });

        // ==========================================
        // Step 2: Painel Propria - Select
        // ==========================================
        var selectPropria = document.getElementById('select-propria');
        if (selectPropria) {
            selectPropria.addEventListener('change', function() {
                var option = this.selectedOptions[0];
                if (!option || !option.value) {
                    clearConfirmacao();
                    return;
                }
                var cnpj = option.value;
                var razao = option.dataset.razao || '';
                state.clienteId = option.dataset.clienteId || null;
                showConfirmacao(cnpj, razao);
            });
        }

        // ==========================================
        // Step 2: Painel Cliente - Autocomplete
        // ==========================================
        var buscaClienteInput = document.getElementById('busca-cliente');
        var dropdownCliente = document.getElementById('dropdown-cliente');

        function renderDropdown(dropdown, items, onSelect) {
            if (!items.length) {
                dropdown.classList.add('hidden');
                return;
            }
            var html = '';
            items.forEach(function(item, idx) {
                html += '<button type="button" class="dropdown-item w-full text-left px-3 py-2 text-sm hover:bg-gray-100 transition-colors' +
                    (idx < items.length - 1 ? ' border-b border-gray-100' : '') + '" data-idx="' + idx + '">' +
                    '<span class="font-medium text-gray-900">' + escapeHtml(item.razao || 'Sem nome') + '</span>' +
                    '<span class="text-xs text-gray-500 ml-1">' + formatarCnpj(item.cnpj) + '</span>' +
                    '</button>';
            });
            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');

            dropdown.querySelectorAll('.dropdown-item').forEach(function(el) {
                el.addEventListener('click', function() {
                    var i = parseInt(this.dataset.idx);
                    onSelect(items[i]);
                    dropdown.classList.add('hidden');
                });
            });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        if (buscaClienteInput && dropdownCliente) {
            buscaClienteInput.addEventListener('input', function() {
                var termo = this.value.toLowerCase().trim();
                if (termo.length < 2) {
                    dropdownCliente.classList.add('hidden');
                    return;
                }
                var termoDigitos = termo.replace(/\D/g, '');
                var filtered = clientesData.filter(function(c) {
                    return (c.razao && c.razao.toLowerCase().includes(termo)) ||
                           (termoDigitos && c.cnpj.includes(termoDigitos));
                }).slice(0, 10);
                renderDropdown(dropdownCliente, filtered, function(item) {
                    state.clienteId = item.id;
                    buscaClienteInput.value = item.razao;
                    showConfirmacao(item.cnpj, item.razao);
                });
            });

            buscaClienteInput.addEventListener('blur', function() {
                setTimeout(function() { dropdownCliente.classList.add('hidden'); }, 200);
            });

            buscaClienteInput.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        // ==========================================
        // Step 2: Painel Participante - Autocomplete
        // ==========================================
        var buscaParticipanteSelect = document.getElementById('busca-participante-select');
        var dropdownParticipante = document.getElementById('dropdown-participante');

        if (buscaParticipanteSelect && dropdownParticipante) {
            buscaParticipanteSelect.addEventListener('input', function() {
                var termo = this.value.toLowerCase().trim();
                if (termo.length < 2) {
                    dropdownParticipante.classList.add('hidden');
                    return;
                }
                var termoDigitos = termo.replace(/\D/g, '');
                var filtered = participantesData.filter(function(p) {
                    return (p.razao && p.razao.toLowerCase().includes(termo)) ||
                           (termoDigitos && p.cnpj.includes(termoDigitos));
                }).slice(0, 10);
                renderDropdown(dropdownParticipante, filtered, function(item) {
                    state.clienteId = item.clienteId || null;
                    buscaParticipanteSelect.value = item.razao;
                    showConfirmacao(item.cnpj, item.razao);
                });
            });

            buscaParticipanteSelect.addEventListener('blur', function() {
                setTimeout(function() { dropdownParticipante.classList.add('hidden'); }, 200);
            });

            buscaParticipanteSelect.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        // ==========================================
        // Step 2: Painel Novo - Sub-chips + CNPJ input
        // ==========================================
        document.querySelectorAll('.chip-novo-subtipo').forEach(function(chip) {
            chip.addEventListener('click', function() {
                state.novoSubtipo = this.dataset.subtipo;
                document.querySelectorAll('.chip-novo-subtipo').forEach(function(c) {
                    c.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
                    c.classList.add('border-gray-300', 'text-gray-600', 'bg-white');
                });
                this.classList.remove('border-gray-300', 'text-gray-600', 'bg-white');
                this.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
            });
        });

        if (cnpjInput) {
            cnpjInput.addEventListener('input', function(e) {
                e.target.value = formatarCnpj(e.target.value);
                var digitos = e.target.value.replace(/\D/g, '');
                if (digitos.length === 14) {
                    state.cnpjDigitos = digitos;
                    state.clienteId = null;
                    setSteps34Enabled(true);
                    atualizarCalculos();
                } else {
                    state.cnpjDigitos = '';
                    state.clienteId = null;
                    setSteps34Enabled(false);
                    atualizarCalculos();
                }
            });
        }

        // ==========================================
        // Plano display (preserved logic)
        // ==========================================
        var totalPlanos = {{ count($planosDetalhados) }};
        var planosData = {!! json_encode(
            collect($planosDetalhados)->mapWithKeys(function ($p, $idx) {
                return [$p['codigo'] => [
                    'nome' => $p['nome'],
                    'creditos' => $p['creditos'],
                    'creditos_original' => $p['creditos_original'] ?? null,
                    'promo' => $p['promo'] ?? false,
                    'cor' => $p['cor'],
                    'slideIndex' => $idx,
                    'descricao' => $p['descricao'],
                    'icone' => $p['icone'],
                    'consultas' => $p['consultas'],
                ]];
            })
        ) !!};

        var corClasses = {
            'green':  { bg: '#d1fae5', icon: '#047857', badge: '#047857', borderL: '#047857' },
            'blue':   { bg: '#dbeafe', icon: '#1d4ed8', badge: '#1d4ed8', borderL: '#1d4ed8' },
            'purple': { bg: '#ede9fe', icon: '#6d28d9', badge: '#6d28d9', borderL: '#6d28d9' },
            'amber':  { bg: '#fef3c7', icon: '#b45309', badge: '#b45309', borderL: '#b45309' },
            'slate':  { bg: '#f1f5f9', icon: '#334155', badge: '#334155', borderL: '#334155' }
        };

        function atualizarPlanoDisplay() {
            var planoSelecionado = document.querySelector('input[name="plano"]:checked');
            if (!planoSelecionado) return;

            var codigo = planoSelecionado.value;
            var plano = planosData[codigo];
            if (!plano) return;

            var cores = corClasses[plano.cor] || corClasses['green'];
            var card = document.getElementById('plano-display-card');
            var iconWrapper = document.getElementById('plano-display-icon-wrapper');
            var icon = document.getElementById('plano-display-icon');
            var nome = document.getElementById('plano-display-nome');
            var descricao = document.getElementById('plano-display-descricao');
            var badge = document.getElementById('plano-display-badge');
            var consultasList = document.getElementById('plano-display-consultas');

            if (!card) return;

            card.style.borderLeftColor = cores.borderL;
            iconWrapper.style.backgroundColor = cores.bg;
            icon.style.color = cores.icon;
            var pathEl = icon.querySelector('path');
            if (pathEl) pathEl.setAttribute('d', plano.icone);

            nome.textContent = plano.nome;
            descricao.textContent = plano.descricao;

            if (plano.promo) {
                badge.style.backgroundColor = '#d97706';
                badge.textContent = plano.creditos + ' cred.';
            } else {
                badge.style.backgroundColor = cores.badge;
                badge.textContent = plano.creditos === 0 ? 'Grátis' : plano.creditos + ' cred.';
            }

            var checkColor = cores.icon;
            var html = '';
            plano.consultas.forEach(function(consulta) {
                html += '<li class="flex items-center gap-1.5 text-[11px] text-gray-600">' +
                    '<svg class="w-3 h-3 flex-shrink-0" style="color: ' + checkColor + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' +
                    consulta +
                    '</li>';
            });
            consultasList.innerHTML = html;
        }

        // Mudanca de plano
        document.querySelectorAll('input[name="plano"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                atualizarCalculos();
                atualizarPlanoDisplay();
            });
        });

        // ==========================================
        // Tabela: Busca de participantes
        // ==========================================
        if (buscaParticipante) {
            buscaParticipante.addEventListener('input', function() {
                const termo = this.value.toLowerCase().replace(/\D/g, '');
                const termoTexto = this.value.toLowerCase();
                const rows = document.querySelectorAll('.participante-row');

                rows.forEach(function(row) {
                    const cnpj = row.dataset.cnpj || '';
                    const razao = (row.dataset.razao || '').toLowerCase();

                    if (cnpj.includes(termo) || razao.includes(termoTexto) || !termoTexto) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // ==========================================
        // Tabela: Reconsultar participante
        // ==========================================
        document.querySelectorAll('.btn-reconsultar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var cnpj = this.dataset.cnpj;
                var razao = this.dataset.razao || '';
                if (!cnpj) return;

                // Activate "Participante" chip
                var participanteChip = document.querySelector('.chip-origem[data-origem="participante"]');
                if (participanteChip) {
                    participanteChip.click();
                    // Show confirmation directly
                    if (buscaParticipanteSelect) buscaParticipanteSelect.value = razao;
                    showConfirmacao(cnpj, razao);
                } else {
                    // Fallback: activate "Novo" chip and fill CNPJ
                    var novoChip = document.querySelector('.chip-origem[data-origem="novo"]');
                    if (novoChip) {
                        novoChip.click();
                        if (cnpjInput) {
                            cnpjInput.value = formatarCnpj(cnpj);
                            cnpjInput.dispatchEvent(new Event('input'));
                        }
                    }
                }

                // Scroll to form
                document.querySelector('#form-consulta-avulsa')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // ==========================================
        // Modal helpers + SSE (preserved)
        // ==========================================
        let eventSource = null;
        let consultaLoteId = null;

        function showModal(tipo) {
            document.getElementById('modal-' + tipo)?.classList.remove('hidden');
        }

        function hideModal(tipo) {
            document.getElementById('modal-' + tipo)?.classList.add('hidden');
        }

        function updateProgresso(percentual, mensagem) {
            const barra = document.getElementById('progresso-barra');
            const pct = document.getElementById('progresso-percentual');
            const msg = document.getElementById('progresso-mensagem');
            if (barra) barra.style.width = percentual + '%';
            if (pct) pct.textContent = percentual + '%';
            if (msg && mensagem) msg.textContent = mensagem;
        }

        function fecharSSE() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        }

        function resetarBotao() {
            const btnText = btnConsultar.querySelector('.btn-text');
            const btnSpinner = btnConsultar.querySelector('.btn-spinner');
            const btnIcon = btnConsultar.querySelector('.btn-icon');
            btnConsultar.disabled = false;
            if (btnText) btnText.textContent = 'Consultar';
            if (btnSpinner) btnSpinner.classList.add('hidden');
            if (btnIcon) btnIcon.classList.remove('hidden');
            atualizarCalculos();
        }

        function iniciarSSE(tabId) {
            fecharSSE();

            const url = '/app/consulta/nova/progresso/stream?tab_id=' + encodeURIComponent(tabId);
            eventSource = new EventSource(url);

            eventSource.addEventListener('progresso', function(e) {
                try {
                    const data = JSON.parse(e.data);
                    const pct = data.progresso || 0;
                    const msg = data.mensagem || 'Processando...';
                    const status = data.status || '';

                    updateProgresso(pct, msg);

                    if (status === 'concluido') {
                        fecharSSE();
                        hideModal('progresso');

                        const linkDownload = document.getElementById('link-download-manual');
                        if (linkDownload && consultaLoteId) {
                            linkDownload.href = '/app/consulta/lote/' + consultaLoteId + '/baixar';
                        }

                        showModal('sucesso');
                        resetarBotao();
                    } else if (status === 'erro') {
                        fecharSSE();
                        hideModal('progresso');

                        const erroMsg = document.getElementById('erro-mensagem');
                        if (erroMsg) erroMsg.textContent = msg || 'Ocorreu um erro no processamento.';

                        showModal('erro');
                        resetarBotao();
                    }
                } catch (err) {
                    console.error('[Avulso SSE] Erro ao parsear:', err);
                }
            });

            eventSource.onerror = function() {
                console.warn('[Avulso SSE] Conexao perdida, tentando reconectar...');
            };
        }

        // Fechar modais
        document.getElementById('btn-fechar-sucesso')?.addEventListener('click', function() {
            hideModal('sucesso');
        });
        document.getElementById('btn-fechar-erro')?.addEventListener('click', function() {
            hideModal('erro');
        });

        // ==========================================
        // Submit (uses state instead of raw input)
        // ==========================================
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearInlineError();

                var cnpj = state.cnpjDigitos;
                if (cnpj.length !== 14) {
                    showInlineError('Por favor, selecione ou insira um CNPJ válido.', 'monitoramento-avulso-validacao');
                    return;
                }

                const planoSelecionado = document.querySelector('input[name="plano"]:checked');
                if (!planoSelecionado) {
                    showInlineError('Por favor, selecione um tipo de consulta.', 'monitoramento-avulso-validacao');
                    return;
                }

                const btnText = btnConsultar.querySelector('.btn-text');
                const btnSpinner = btnConsultar.querySelector('.btn-spinner');
                const btnIcon = btnConsultar.querySelector('.btn-icon');

                btnConsultar.disabled = true;
                if (btnText) btnText.textContent = 'Consultando...';
                if (btnSpinner) btnSpinner.classList.remove('hidden');
                if (btnIcon) btnIcon.classList.add('hidden');

                const tabId = crypto.randomUUID();

                try {
                    const payload = {
                        cnpj: cnpj,
                        plano: planoSelecionado.value,
                        tab_id: tabId,
                    };

                    if (state.clienteId) {
                        payload.cliente_id = state.clienteId;
                    }

                    if (state.origemTipo === 'novo' && state.novoSubtipo) {
                        payload.novo_subtipo = state.novoSubtipo;
                    }

                    const response = await fetch('/app/monitoramento/consulta-avulsa', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        consultaLoteId = data.consulta_lote_id;

                        updateProgresso(0, 'Iniciando consulta...');
                        showModal('progresso');
                        iniciarSSE(tabId);

                        if (window.showToast) {
                            window.showToast('Consulta iniciada!', 'success');
                        }
                    } else {
                        throw new Error(data.error || data.message || 'Erro ao realizar consulta');
                    }
                } catch (err) {
                    console.error('[Monitoramento Avulso] Erro:', err);
                    resetarBotao();

                    const erroMsg = document.getElementById('erro-mensagem');
                    if (erroMsg) erroMsg.textContent = err.message || 'Erro ao realizar consulta.';
                    showModal('erro');
                }
            });
        }

        // ==========================================
        // Modal Carousel de Planos (preserved)
        // ==========================================
        var swiperPlanos = null;
        var modalPlanos = document.getElementById('modal-planos-carousel');

        function showPlanosModal(startIndex) {
            if (!modalPlanos) return;
            modalPlanos.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            setTimeout(function() {
                if (swiperPlanos && !swiperPlanos.destroyed) {
                    swiperPlanos.slideToLoop(startIndex || 0, 0);
                    swiperPlanos.update();
                    updateCounter(startIndex || 0);
                    updateFooterButton(startIndex || 0);
                    return;
                }

                swiperPlanos = new Swiper('#swiper-planos', {
                    slidesPerView: 1,
                    spaceBetween: 0,
                    loop: true,
                    initialSlide: startIndex || 0,
                    navigation: {
                        prevEl: '#swiper-planos-prev',
                        nextEl: '#swiper-planos-next',
                    },
                    pagination: {
                        el: '#swiper-planos-pagination',
                        clickable: true,
                    },
                    on: {
                        slideChange: function() {
                            updateCounter(this.realIndex);
                            updateFooterButton(this.realIndex);
                        },
                    },
                });

                updateCounter(startIndex || 0);
                updateFooterButton(startIndex || 0);
            }, 50);
        }

        function hidePlanosModal() {
            if (!modalPlanos) return;
            modalPlanos.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function updateCounter(index) {
            var counter = document.getElementById('carousel-counter');
            if (counter) {
                counter.textContent = (index + 1) + ' / ' + totalPlanos;
            }
        }

        var footerBtnCodigos = {!! json_encode(collect($planosDetalhados)->pluck('codigo')->values()) !!};

        function updateFooterButton(index) {
            var btn = document.getElementById('btn-selecionar-plano-footer');
            if (!btn) return;
            btn.dataset.planoCodigo = footerBtnCodigos[index] || '';
        }

        if (modalPlanos) {
            modalPlanos.addEventListener('click', function(e) {
                if (e.target === modalPlanos) {
                    hidePlanosModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalPlanos && !modalPlanos.classList.contains('hidden')) {
                hidePlanosModal();
            }
        });

        document.getElementById('btn-fechar-carousel')?.addEventListener('click', hidePlanosModal);

        var btnVerDetalhes = document.getElementById('btn-ver-detalhes-planos');
        if (btnVerDetalhes) {
            btnVerDetalhes.addEventListener('click', function() {
                showPlanosModal(0);
            });
        }

        document.querySelectorAll('.badge-plano').forEach(function(badge) {
            badge.addEventListener('click', function() {
                var idx = parseInt(this.dataset.slideIndex) || 0;
                showPlanosModal(idx);
            });
        });

        var btnAlterarPlano = document.getElementById('btn-alterar-plano');
        if (btnAlterarPlano) {
            btnAlterarPlano.addEventListener('click', function() {
                var planoSelecionado = document.querySelector('input[name="plano"]:checked');
                var slideIndex = 0;
                if (planoSelecionado && planosData[planoSelecionado.value]) {
                    slideIndex = planosData[planoSelecionado.value].slideIndex;
                }
                showPlanosModal(slideIndex);
            });
        }

        document.querySelectorAll('.btn-selecionar-plano').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var codigo = this.dataset.planoCodigo;
                var radio = document.querySelector('input[name="plano"][value="' + codigo + '"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
                hidePlanosModal();

                if (window.showToast) {
                    window.showToast('Plano selecionado!', 'success');
                }
            });
        });

        // ==========================================
        // Init
        // ==========================================
        atualizarCalculos();
        atualizarPlanoDisplay();

        // Pre-fill via ?cnpj= query param
        var urlParams = new URLSearchParams(window.location.search);
        var cnpjParam = urlParams.get('cnpj');
        if (cnpjParam) {
            var cnpjLimpo = cnpjParam.replace(/\D/g, '');
            if (cnpjLimpo.length === 14) {
                // Activate "Novo" chip
                var novoChip = document.querySelector('.chip-origem[data-origem="novo"]');
                if (novoChip) {
                    novoChip.click();
                    if (cnpjInput) {
                        cnpjInput.value = formatarCnpj(cnpjLimpo);
                        cnpjInput.dispatchEvent(new Event('input'));
                        cnpjInput.classList.add('ring-2', 'ring-blue-500');
                        setTimeout(function() {
                            cnpjInput.classList.remove('ring-2', 'ring-blue-500');
                        }, 2000);
                    }
                }
            }
        }

        console.log('[Monitoramento Avulso] Inicializacao concluida');
    }

    // Expor globalmente para SPA
    window.initMonitoramentoAvulso = initMonitoramentoAvulso;

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoAvulso, { once: true });
    } else {
        initMonitoramentoAvulso();
    }
})();
</script>
