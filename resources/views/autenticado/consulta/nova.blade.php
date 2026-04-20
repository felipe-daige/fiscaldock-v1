{{-- Consultas - Nova Consulta --}}
<div class="bg-gray-100 min-h-screen" id="consultas-nova-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        {{-- Header Section --}}
        <div class="flex items-start justify-between gap-4 mb-4 sm:mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Nova Consulta</h1>
                    <p class="text-xs text-gray-500 mt-1">Selecione participantes, escolha o produto de consulta e execute consultas fiscais em lote.</p>
                </div>
            </div>
            <a
                href="/app/consulta/historico"
                class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                data-link
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Histórico
            </a>
        </div>
        <div class="space-y-6">
        <div id="consulta-inline-error-region"></div>

        @php
                // Metadata visual por código legado do produto (DB)
                $planoMeta = [
                    'gratuito' => [
                        'cor' => 'green',
                        'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        'consultas_display' => ['Situação Cadastral (Ativa, Inapta, Baixada)', 'Dados Cadastrais Completos', 'CNAEs Principal e Secundários', 'Quadro Societário (QSA)'],
                        'casos_uso' => ['Checar se CNPJ está ativo', 'Conferir dados cadastrais', 'Consultar sócios e QSA'],
                    ],
                    'validacao' => [
                        'cor' => 'blue',
                        'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'consultas_display' => ['Situação Cadastral', 'Dados Cadastrais', 'Simples Nacional', 'MEI'],
                        'casos_uso' => ['Conferir regime tributário', 'Validar inscrição estadual', 'Qualificar novos fornecedores'],
                    ],
                    'licitacao' => [
                        'cor' => 'blue',
                        'icone' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                        'consultas_display' => ['Tudo do Validação', 'CND Federal (PGFN/RFB)', 'CNDT', 'FGTS'],
                        'casos_uso' => ['Documentação para editais', 'Contratos públicos', 'Homologar fornecedores'],
                    ],
                    'compliance' => [
                        'cor' => 'purple',
                        'icone' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                        'consultas_display' => ['Tudo do Licitação', 'CND Estadual', 'CND Municipal', 'SINTEGRA'],
                        'casos_uso' => ['Regularidade completa', 'Auditoria de fornecedor', 'Contratos recorrentes'],
                    ],
                    'due_diligence' => [
                        'cor' => 'amber',
                        'icone' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7',
                        'consultas_display' => ['Tudo do Compliance', 'Sanções', 'CNJ', 'Protestos e processos'],
                        'casos_uso' => ['Risco ampliado', 'Due diligence comercial', 'Fornecedores críticos'],
                    ],
                    'enterprise' => [
                        'cor' => 'slate',
                        'icone' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                        'consultas_display' => [],
                        'casos_uso' => [],
                        'coming_soon' => true,
                    ],
                ];

                $hasMadeFirstPurchase = $hasMadeFirstPurchase ?? false;
                $firstPurchaseLockedProducts = $firstPurchaseLockedProducts ?? ['compliance', 'due_diligence'];
                $planosDetalhados = [];
                $planosAtivos = $planos->where('is_active', true)->where('codigo', '!=', 'enterprise')->values();
                foreach ($planosAtivos as $p) {
                    $meta = $planoMeta[$p->codigo] ?? ['cor' => 'gray', 'icone' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'consultas_display' => [], 'casos_uso' => []];
                    $requiresFirstPurchase = in_array($p->codigo, $firstPurchaseLockedProducts, true);
                    $isLocked = $requiresFirstPurchase && ! $hasMadeFirstPurchase;
                    $badgeHex = match ($p->codigo) {
                        'gratuito' => '#047857',
                        'validacao' => '#4338ca',
                        'licitacao' => '#0f766e',
                        'compliance' => '#d97706',
                        'due_diligence' => '#9a3412',
                        default => '#374151',
                    };
                    $planosDetalhados[] = [
                        'codigo' => $p->codigo,
                        'nome' => $p->nome,
                        'creditos' => $p->custo_creditos,
                        'descricao' => $p->descricao,
                        'cor' => $meta['cor'],
                        'icone' => $meta['icone'],
                        'consultas' => $meta['consultas_display'],
                        'casos_uso' => $meta['casos_uso'],
                        'coming_soon' => $p->codigo === 'enterprise' ? true : ($meta['coming_soon'] ?? false),
                        'gratuito' => $p->is_gratuito,
                        'promo' => $meta['promo'] ?? false,
                        'preco_original' => $meta['preco_original'] ?? null,
                        'requires_first_purchase' => $requiresFirstPurchase,
                        'locked' => $isLocked,
                        'badge_hex' => $badgeHex,
                    ];
                }

                $corClasses = [
                    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'text-green-600', 'badge' => 'bg-green-100 text-green-700', 'border' => 'border-green-200', 'btn' => 'bg-green-600 hover:bg-green-700'],
                    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'text-blue-600', 'badge' => 'bg-blue-100 text-blue-700', 'border' => 'border-blue-200', 'btn' => 'bg-blue-600 hover:bg-blue-700'],
                    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'text-purple-600', 'badge' => 'bg-purple-100 text-purple-700', 'border' => 'border-purple-200', 'btn' => 'bg-purple-600 hover:bg-purple-700'],
                    'amber' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => 'text-amber-600', 'badge' => 'bg-amber-100 text-amber-700', 'border' => 'border-amber-200', 'btn' => 'bg-amber-600 hover:bg-amber-700'],
                    'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'text-slate-600', 'badge' => 'bg-slate-100 text-slate-700', 'border' => 'border-slate-200', 'btn' => 'bg-slate-700 hover:bg-slate-800'],
                ];
            @endphp

            <div id="consulta-form-section">

            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">Consultas em lote</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($totalParticipantes ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Base disponível para seleção</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Clientes</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format(($clientes ?? collect())->count(), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Vínculos prontos para filtro</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Grupos</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format(($grupos ?? collect())->count(), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Segmentações cadastradas</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($credits ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Saldo disponível para consulta</p>
                    </div>
                </div>
            </div>

            {{-- Card: Adicionar CNPJ --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cadastro Rápido de CNPJ</span>
                </div>
                <div class="px-4 py-4">
                    <p class="text-sm text-gray-700 mb-4">Inclua um CNPJ manualmente para consulta imediata e associe-o a um cliente quando necessário.</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <input
                                type="text"
                                id="input-adicionar-cnpj"
                                placeholder="00.000.000/0000-00"
                                maxlength="18"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 font-mono focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            >
                        </div>
                        <div class="w-full sm:w-56">
                            <select id="select-cliente-associar" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Sem vínculo a cliente</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->razao_social ?? $cliente->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button
                            type="button"
                            id="btn-adicionar-cnpj"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-800 text-white rounded text-sm font-medium whitespace-nowrap hover:bg-gray-700"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Adicionar
                        </button>
                    </div>
                    <div id="feedback-adicionar-cnpj" class="hidden mt-2 px-3 py-2 rounded-lg text-sm"></div>
                </div>
            </div>

            {{-- Layout 2 colunas --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                {{-- Coluna Esquerda: Filtros e Lista de Participantes (2/3) --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        {{-- Tab Bar --}}
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Selecionar Participantes</span>
                                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">Nome + detalhes expandíveis</span>
                            </div>
                        </div>
                        <div class="px-4 py-3 border-b border-gray-200">
                            <div class="flex items-center gap-1 bg-gray-100 rounded p-1" id="search-tabs">
                                <button type="button" data-tab="participantes"
                                    class="search-tab flex-1 px-3 py-1.5 text-sm font-medium rounded transition bg-gray-800 text-white">
                                    Participantes
                                </button>
                                <button type="button" data-tab="clientes"
                                    class="search-tab flex-1 px-3 py-1.5 text-sm font-medium rounded transition text-gray-600 hover:text-gray-900">
                                    Clientes
                                </button>
                                <button type="button" data-tab="grupos"
                                    class="search-tab flex-1 px-3 py-1.5 text-sm font-medium rounded transition text-gray-600 hover:text-gray-900">
                                    Grupos
                                </button>
                            </div>
                        </div>

                        {{-- View: Participantes (default) --}}
                        <div id="view-participantes" class="search-view">
                            {{-- Barra de contexto (aparece ao filtrar por cliente/grupo) --}}
                            <div id="participantes-context" class="hidden px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btn-clear-filter-context" class="text-xs text-gray-700 hover:text-gray-900 font-medium">
                                        &larr; Todos
                                    </button>
                                    <span class="text-xs text-gray-400">|</span>
                                    <span id="filter-context-label" class="text-xs text-gray-700 font-medium"></span>
                                </div>
                                <button type="button" id="btn-remove-filter-chip" class="p-0.5 text-gray-400 hover:text-gray-700 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            {{-- Filtros --}}
                            <div class="px-4 py-4 border-b border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                                    <input
                                        type="text"
                                        id="filtro-busca"
                                        placeholder="Buscar documento, razão social ou fantasia..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                    <select id="filtro-origem" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todas as origens</option>
                                        <option value="NFE">NF-e</option>
                                        <option value="NFSE">NFS-e</option>
                                        <option value="CTE">CT-e</option>
                                        <option value="SPED_EFD_FISCAL">EFD Fiscal</option>
                                        <option value="SPED_EFD_CONTRIB">EFD Contribuições</option>
                                        <option value="MANUAL">Manual</option>
                                    </select>
                                    <select id="filtro-tipo-documento" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todos os tipos</option>
                                        <option value="PJ">PJ</option>
                                        <option value="PF">PF</option>
                                    </select>
                                    <select id="filtro-situacao-cadastral" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todas as situações</option>
                                        <option value="ATIVA">Ativa</option>
                                        <option value="BAIXADA">Baixada</option>
                                        <option value="INAPTA">Inapta</option>
                                        <option value="SUSPENSA">Suspensa</option>
                                        <option value="NULA">Nula</option>
                                    </select>
                                    <select id="filtro-uf" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todas as UFs</option>
                                        @foreach(($participantesUfs ?? collect()) as $uf)
                                            <option value="{{ $uf }}">{{ $uf }}</option>
                                        @endforeach
                                    </select>
                                    <select id="filtro-cliente" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todos os clientes</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}">{{ $cliente->razao_social ?? $cliente->nome }}</option>
                                        @endforeach
                                    </select>
                                    <select id="filtro-grupo" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todos os grupos</option>
                                        @foreach($grupos as $grupo)
                                            <option value="{{ $grupo->id }}">{{ $grupo->nome }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="btn-limpar-filtros-participantes" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium">
                                        Limpar filtros
                                    </button>
                                </div>

                                {{-- Acoes em massa --}}
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 pt-3 border-t border-gray-200">
                                    <div class="flex items-center gap-4 text-sm">
                                        <button type="button" id="btn-selecionar-todos" class="text-gray-700 hover:text-gray-900">
                                            Selecionar todos
                                        </button>
                                        <button type="button" id="btn-limpar-selecao" class="text-gray-500 hover:text-gray-700">
                                            Limpar
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span id="contador-selecionados" class="text-xs text-gray-500">
                                            <span id="total-selecionados">0</span> selecionados
                                        </span>
                                        <button type="button" onclick="if(window.reloadParticipantes) window.reloadParticipantes();"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition"
                                            title="Atualizar lista">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Atualizar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabela de Participantes --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="hidden md:table-header-group">
                                        <tr>
                                            <th class="w-10 px-3 py-2.5 text-left bg-gray-50">
                                                <input type="checkbox" id="checkbox-todos" class="w-4 h-4 text-gray-600 rounded border-gray-300">
                                            </th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela-participantes" class="divide-y divide-gray-100">
                                        {{-- Preenchido via JS --}}
                                        <tr id="loading-row">
                                            <td colspan="2" class="px-4 py-8 text-center text-gray-500">
                                                <svg class="animate-spin h-5 w-5 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm">Carregando...</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginacao --}}
                            <div id="paginacao-container" class="border-t border-gray-300 px-4 py-3 flex items-center justify-between">
                                <div class="text-[10px] text-gray-500 uppercase tracking-wide">
                                    <span id="pag-inicio">0</span>-<span id="pag-fim">0</span> de <span id="pag-total">0</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btn-pag-anterior" class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40" disabled>
                                        Anterior
                                    </button>
                                    <span id="pag-atual" class="text-[10px] text-gray-500 uppercase tracking-wide">1</span>
                                    <button type="button" id="btn-pag-proximo" class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40" disabled>
                                        Próximo
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- View: Clientes --}}
                        <div id="view-clientes" class="search-view hidden">
                            <div class="px-4 py-4 border-b border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                    <input type="text" id="busca-clientes" placeholder="Buscar cliente por nome ou documento..."
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                    <select id="filtro-clientes-tipo-pessoa" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todos os tipos</option>
                                        <option value="PJ">PJ</option>
                                        <option value="PF">PF</option>
                                    </select>
                                    <select id="filtro-clientes-situacao-cadastral" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todas as situações</option>
                                        <option value="ATIVA">Ativa</option>
                                        <option value="BAIXADA">Baixada</option>
                                        <option value="INAPTA">Inapta</option>
                                        <option value="SUSPENSA">Suspensa</option>
                                        <option value="NULA">Nula</option>
                                    </select>
                                    <select id="filtro-clientes-uf" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Todas as UFs</option>
                                        @foreach(($clientesUfs ?? collect()) as $uf)
                                            <option value="{{ $uf }}">{{ $uf }}</option>
                                        @endforeach
                                    </select>
                                    <select id="filtro-clientes-faixa-participantes" class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="">Qualquer volume</option>
                                        <option value="0">0 participantes</option>
                                        <option value="1-10">1 a 10</option>
                                        <option value="11-50">11 a 50</option>
                                        <option value="51+">51 ou mais</option>
                                    </select>
                                    <button type="button" id="btn-limpar-filtros-clientes" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium">
                                        Limpar filtros
                                    </button>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 pt-3 border-t border-gray-200">
                                    <div class="flex items-center gap-4 text-sm">
                                        <button type="button" id="btn-selecionar-todos-clientes-barra" class="text-gray-700 hover:text-gray-900">
                                            Selecionar todos
                                        </button>
                                        <button type="button" id="btn-limpar-selecao-clientes" class="text-gray-500 hover:text-gray-700">
                                            Limpar
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span id="contador-clientes-selecionados" class="text-xs text-gray-500">
                                            <span id="total-clientes-selecionados">0</span> selecionados
                                        </span>
                                        <button type="button" id="btn-atualizar-clientes"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition"
                                            title="Atualizar lista de clientes">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Atualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                            <table class="w-full table-fixed">
                                <thead class="hidden md:table-header-group">
                                    <tr>
                                        <th class="w-10 px-3 py-2.5 text-left bg-gray-50">
                                            <input type="checkbox" id="checkbox-todos-clientes" class="w-4 h-4 text-gray-600 rounded border-gray-300">
                                        </th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cliente</th>
                                    </tr>
                                </thead>
                                <tbody id="lista-clientes" class="divide-y divide-gray-100">
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400">Carregando clientes...</td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>

                        {{-- View: Grupos --}}
                        <div id="view-grupos" class="search-view hidden">
                            <div class="px-4 py-4 border-b border-gray-200">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center gap-4 text-sm">
                                        <button type="button" id="btn-selecionar-todos-grupos" class="text-gray-700 hover:text-gray-900">
                                            Selecionar todos
                                        </button>
                                        <button type="button" id="btn-limpar-selecao-grupos" class="text-gray-500 hover:text-gray-700">
                                            Limpar
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span id="contador-grupos-selecionados" class="text-xs text-gray-500">
                                            <span id="total-grupos-selecionados">0</span> selecionados
                                        </span>
                                        <button type="button" id="btn-atualizar-grupos"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition"
                                            title="Atualizar lista de grupos">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Atualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="lista-grupos" class="divide-y divide-gray-100">
                                <div class="px-5 py-8 text-center text-sm text-gray-400">Carregando grupos...</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coluna Direita: Tipo de Analise e Resumo (1/3) --}}
                <div class="space-y-4 lg:sticky lg:top-4 lg:self-start">
                    {{-- Card Tipo de Analise --}}
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Tipo de Consulta</span>
                                <button type="button" id="btn-ver-detalhes-planos-lote" class="text-xs text-gray-600 hover:text-gray-900">Ver detalhes</button>
                            </div>
                        </div>
                        <div class="p-3 flex flex-col gap-1.5">
                            @foreach($planosDetalhados as $idx => $pd)
                                @php
                                    $badgeHex = $pd['badge_hex'];
                                    $isLocked = $pd['locked'] ?? false;
                                @endphp
                                <label class="flex items-center gap-2 px-3 py-2 border rounded transition plano-label {{ $isLocked ? 'border-gray-200 bg-gray-50 opacity-75 cursor-not-allowed' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50 cursor-pointer' }}" data-plano-id="{{ $planosAtivos[$idx]->id }}" data-locked="{{ $isLocked ? '1' : '0' }}">
                                    <input type="radio" name="plano_id" value="{{ $planosAtivos[$idx]->id }}" class="w-4 h-4 text-gray-600 border-gray-300 flex-shrink-0" data-custo="{{ $pd['creditos'] }}" data-gratuito="{{ $pd['gratuito'] ? '1' : '0' }}" {{ $idx === 0 ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                    <div class="flex-shrink-0 w-6 h-6 rounded {{ $isLocked ? 'bg-gray-200' : 'bg-gray-100' }} flex items-center justify-center">
                                        <svg class="w-3 h-3 {{ $isLocked ? 'text-gray-500' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($isLocked)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $pd['icone'] }}"></path>
                                            @endif
                                        </svg>
                                    </div>
                                    <span class="flex-1 min-w-0 text-sm font-medium text-gray-900 truncate">{{ $pd['nome'] }}</span>
                                    @if($pd['gratuito'])
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                    @elseif($isLocked)
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Bloq.</span>
                                    @else
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badgeHex }}">{{ $pd['creditos'] }} cred.</span>
                                    @endif
                                    <button type="button" class="btn-info-plano-lote flex-shrink-0 w-6 h-6 rounded-full border border-gray-400 bg-white flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-100 hover:border-gray-500 transition" data-slide-index="{{ $idx }}" onclick="event.preventDefault(); event.stopPropagation();">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Card Resumo --}}
                    <div id="card-resumo-consulta" class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo da Execução</span>
                        </div>
                        <div class="px-4 py-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</span>
                                <span id="resumo-custo-total" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">0 créditos</span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Participantes</span>
                                    <span id="resumo-participantes" class="text-gray-900 font-medium">0</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Custo unitário</span>
                                    <span id="resumo-custo-unitario" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">0 créditos</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                                    <span class="text-gray-500">Seu saldo</span>
                                    <span id="resumo-saldo" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">{{ number_format($credits, 0, ',', '.') }} créditos</span>
                                </div>
                            </div>

                            {{-- Alerta créditos --}}
                            <div id="alerta-creditos-insuficientes" class="hidden mt-3 bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700">
                                Créditos insuficientes
                            </div>

                            <button type="button" id="btn-gerar-relatorio" class="w-full mt-4 py-2.5 rounded text-sm font-medium transition" style="background-color: #d1d5db; color: #6b7280; cursor: not-allowed;" disabled>
                                Executar Consulta
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            </div>{{-- /consulta-form-section --}}

            {{-- Seção de Progresso Inline (inicialmente oculta) --}}
            <div id="consulta-progresso-section" class="hidden">

                {{-- Card de Progresso --}}
                <div id="consulta-progresso-card" class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Processamento da Consulta</span>
                    </div>
                    <div class="p-4">
                    {{-- Header: ícone + título --}}
                    <div class="flex items-start gap-3 mb-4">
                        <div id="consulta-progresso-icon" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-700 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="progresso-titulo" class="font-semibold text-gray-900 truncate">Processando consulta...</h3>
                            <p id="consulta-progresso-subtitulo" class="text-sm text-gray-500 hidden"></p>
                        </div>
                    </div>
                    {{-- Barra de progresso --}}
                    <div class="mb-3">
                        <div class="flex justify-between text-sm mb-1">
                            <span id="progresso-mensagem" class="text-gray-600">Iniciando...</span>
                            <span id="progresso-percentual" class="font-medium text-gray-900">0%</span>
                        </div>
                        <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div id="progresso-barra" class="bg-gray-800 h-full rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                        </div>
                    </div>
                    {{-- Strip horizontal de etapas (populado via JS com o array `etapas` do plano) --}}
                    <div id="etapas-consulta-card" class="hidden mb-3 flex items-center gap-1.5 flex-wrap"></div>
                    {{-- Seção de erro (oculta por padrão) --}}
                    <div id="consulta-progresso-erro" class="hidden pt-3 border-t border-red-100">
                        <p id="consulta-progresso-erro-msg" class="text-sm text-gray-700 mb-3">Ocorreu um erro durante o processamento.</p>
                        <p class="text-sm text-gray-600 mb-4">
                            Por favor, tente novamente mais tarde.<br>
                            Se o erro persistir, entre em contato com o suporte:
                        </p>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <a href="/app/suporte"
                               id="consulta-progresso-suporte-link"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded text-white text-sm font-medium"
                               style="background-color: #1f2937;"
                               data-link>
                                Ir para Suporte
                            </a>
                            <a href="https://wa.me/5567999844366"
                               target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded text-white text-sm font-medium"
                               style="background-color: #047857;">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                WhatsApp: (67) 99984-4366
                            </a>
                        </div>
                        <div>
                            <button type="button"
                                    id="btn-tentar-novamente"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Tentar Novamente
                            </button>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Seção de Resultados (aparece ao concluir) --}}
                <div id="resultado-consulta" class="hidden mt-4">
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Consulta Concluída</h3>
                                        <p class="text-sm text-gray-600" id="resultado-consulta-info">-</p>
                                    </div>
                                </div>
                                <button type="button" id="btn-nova-consulta"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Nova Consulta
                                </button>
                            </div>
                        </div>
                        <div class="px-4 py-4">
                            <a id="link-download-relatorio" href="#"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 transition text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Baixar Relatório
                            </a>

                            {{-- Tabela de resultados (injetada por JS) --}}
                            <div id="resultados-table-container" class="mt-4 border-t border-gray-100 pt-4">
                                <div id="resultados-loading" class="text-sm text-gray-500 text-center py-4">
                                    Carregando resultados...
                                </div>
                                <div id="resultados-table-wrapper" class="hidden overflow-x-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /consulta-progresso-section --}}
            {{-- Modal: Carousel de Planos --}}
            <div id="modal-planos-carousel-lote" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded border border-gray-300 shadow-lg max-w-lg w-full mx-4 max-h-[90vh] flex flex-col relative overflow-visible">
                    {{-- Modal Header (DANFE) --}}
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Detalhes do Produto</span>
                        <div class="flex items-center gap-2">
                            <span id="carousel-counter-lote" class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">1 / {{ count($planosAtivos) }}</span>
                            <button type="button" id="btn-fechar-carousel-lote" class="p-1 text-gray-400 hover:text-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Navigation arrows --}}
                    <button type="button" id="swiper-planos-prev-lote" class="absolute -left-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white border border-gray-300 flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button type="button" id="swiper-planos-next-lote" class="absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-white border border-gray-300 flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    {{-- Swiper Carousel --}}
                    <div class="flex-1 overflow-hidden relative">
                        <div class="swiper h-full" id="swiper-planos-lote">
                            <div class="swiper-wrapper">
                                @foreach($planosDetalhados as $idx => $pd)
                                    @php $badgeHex = $pd['badge_hex']; @endphp
                                    <div class="swiper-slide">
                                        <div class="overflow-y-auto" style="max-height: calc(90vh - 180px);">
                                            {{-- Seção: Produto --}}
                                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Produto</span>
                                            </div>
                                            <div class="px-4 py-3 border-b border-gray-200">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <div class="flex-shrink-0 w-9 h-9 rounded border border-gray-200 bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-[18px] h-[18px] text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $pd['icone'] }}"></path>
                                                        </svg>
                                                    </div>
                                                    <h4 class="flex-1 text-sm font-bold text-gray-900 uppercase tracking-wide">{{ $pd['nome'] }}</h4>
                                                    @if($pd['locked'] ?? false)
                                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Bloqueado</span>
                                                    @elseif($pd['gratuito'])
                                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                                    @elseif($pd['promo'])
                                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ $pd['creditos'] }} cred.</span>
                                                    @else
                                                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badgeHex }}">{{ $pd['creditos'] }} cred.</span>
                                                    @endif
                                                </div>
                                                <p class="text-sm text-gray-700">{{ $pd['descricao'] }}</p>

                                                @if($pd['locked'] ?? false)
                                                    <div class="mt-3 bg-white rounded border border-gray-300 border-l-4 p-3 text-sm text-gray-700" style="border-left-color: #9ca3af">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span>Disponível após a primeira recarga.</span>
                                                            <a href="/app/creditos" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline whitespace-nowrap">Comprar créditos</a>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($pd['promo'])
                                                    <div class="mt-3 bg-white rounded border border-gray-300 border-l-4 p-3 text-sm text-gray-700" style="border-left-color: #d97706">
                                                        Promoção: de {{ $pd['preco_original'] }} por {{ $pd['creditos'] }} créditos/CNPJ
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Seção: Consultas Incluídas --}}
                                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consultas Incluídas</span>
                                            </div>
                                            <div class="px-4 py-3 border-b border-gray-200">
                                                <ul class="space-y-1.5">
                                                    @foreach($pd['consultas'] as $consulta)
                                                        <li class="flex items-start gap-2 text-sm text-gray-700">
                                                            <svg class="w-4 h-4 text-gray-700 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                            <span>{{ $consulta }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            {{-- Seção: Quando Usar --}}
                                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Quando Usar</span>
                                            </div>
                                            <div class="px-4 py-3">
                                                <ul class="space-y-1.5">
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

                    {{-- Footer: botão selecionar --}}
                    <div class="px-4 py-3 border-t border-gray-200 flex-shrink-0">
                        <button
                            type="button"
                            id="btn-selecionar-plano-footer-lote"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded text-white text-sm font-semibold transition-colors hover:brightness-110"
                            style="background-color: #1f2937"
                            data-plano-index="0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Selecionar este produto
                        </button>
                    </div>

                    {{-- Pagination dots --}}
                    <div class="px-4 py-2 border-t border-gray-200 flex-shrink-0">
                        <div id="swiper-planos-pagination-lote" class="flex justify-center"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #swiper-planos-pagination-lote .swiper-pagination-bullet {
        width: 8px;
        height: 8px;
        background: #d1d5db;
        opacity: 1;
        margin: 0 4px;
        border-radius: 50%;
        transition: all 0.2s;
    }
    #swiper-planos-pagination-lote .swiper-pagination-bullet-active {
        background: #1f2937;
        width: 20px;
        border-radius: 4px;
    }
</style>

{{-- Dados para JS --}}
@php
    $consultaLoteJsVersion = @filemtime(public_path('js/consulta-lote.js')) ?: time();
@endphp
<script>
    window.consultaData = {
        credits: {{ $credits ?? 0 }},
        csrfToken: '{{ csrf_token() }}',
        routes: {
            getParticipantes: '/app/consulta/nova/participantes',
            getParticipantesGrupo: '/app/consulta/nova/participantes/grupo/',
            getClientes: '/app/consulta/nova/clientes',
            getGrupos: '/app/consulta/nova/grupos',
            calcularCusto: '/app/consulta/nova/calcular-custo',
            executar: '/app/consulta/nova/executar',
            adicionarCnpj: '/app/consulta/nova/adicionar-cnpj',
            progressoStream: '/app/consulta/nova/progresso/stream',
            baixarLote: '/app/consulta/lote/{id}/baixar',
            loteStatus: '/app/consulta/lote/{id}/status',
            resultadosLote: '/app/consulta/lote/{id}/resultados',
            participantesPorClientes: '/app/consulta/nova/participantes-por-clientes'
        },
        planos: {
            @foreach($planosAtivos as $plano)
                {{ $plano->id }}: {
                    codigo: '{{ $plano->codigo }}',
                    consultas: {!! json_encode($plano->consultas_incluidas) !!}
                },
            @endforeach
        },
        planosDetalhados: {!! json_encode(collect($planosDetalhados)->values()) !!},
        corClasses: {!! json_encode($corClasses) !!}
    };
</script>
<script src="/js/consulta-lote.js?v={{ $consultaLoteJsVersion }}"></script>
<script>
(function() {
    function tryInit(attempts) {
        if (typeof window.initConsultaLote === 'function') {
            window.initConsultaLote();
            // Safety: se loading ficar travado, forçar reload
            setTimeout(function() {
                var lr = document.getElementById('loading-row');
                if (lr && lr.style.display !== 'none' && lr.parentNode && lr.offsetParent !== null) {
                    if (typeof window.reloadParticipantes === 'function') {
                        window.reloadParticipantes();
                    }
                }
            }, 2000);
        } else if (attempts < 50) {
            setTimeout(function() { tryInit(attempts + 1); }, 100);
        } else {
            forceLoadScript();
        }
    }

    function forceLoadScript() {
        var existing = document.querySelector('script[src*="consulta-lote"]');
        if (existing) existing.parentNode.removeChild(existing);
        window._consultaLoteModuleLoaded = false;

        var s = document.createElement('script');
        s.src = '/js/consulta-lote.js?_=' + Date.now();
        s.onload = function() {
            if (typeof window.initConsultaLote === 'function') {
                window.initConsultaLote();
            } else {
                showError();
            }
        };
        s.onerror = function() { showError(); };
        document.head.appendChild(s);
    }

    function showError() {
        var lr = document.getElementById('loading-row');
        if (lr) lr.style.display = 'none';
        var tb = document.getElementById('tabela-participantes');
        if (tb) tb.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-500">Erro ao carregar. Clique em Atualizar.</td></tr>';

        window.reloadParticipantes = function() {
            if (typeof window.initConsultaLote === 'function') {
                window.initConsultaLote();
            } else {
                if (tb) tb.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><span class="text-sm">Carregando...</span></td></tr>';
                forceLoadScript();
            }
        };
    }

    tryInit(0);
})();
</script>
