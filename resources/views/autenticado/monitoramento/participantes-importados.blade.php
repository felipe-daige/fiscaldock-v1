{{-- Monitoramento - Lista de Participantes Importados --}}
<div class="bg-gray-100 min-h-screen" id="monitoramento-participantes-importados-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <style>
            .view-toggle-btn.active-view {
                background-color: #1f2937;
                color: #ffffff;
            }
        </style>

        {{-- Page Header --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Participantes</h1>
                    <p class="mt-1 text-xs text-gray-500">Base operacional de participantes vinculados às importações e consultas.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="/app/participante/novo"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700"
                        data-link
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Participante
                    </a>
                    <a
                        href="/app/dashboard"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                        data-link
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar
                    </a>
                </div>
            </div>
        </div>

        {{-- Estatísticas --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalParticipantes ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Participantes importados</p>
                </div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação Ativa</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalAtiva ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Cadastro regular</p>
                </div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Irregular</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalIrregular ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Requer atenção</p>
                </div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sem Consulta</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalSemConsulta ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Aguardando consulta</p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <form id="form-filtros" method="GET" action="/app/participantes" class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div>
                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Importação</label>
                    <select name="importacao" id="filtro-importacao" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todas as importações</option>
                        @foreach($importacoes ?? [] as $imp)
                            <option value="{{ $imp->id }}" {{ ($filtros['importacao'] ?? '') == $imp->id ? 'selected' : '' }}>
                                {{ $imp->filename ?? 'Importacao #' . $imp->id }} - {{ $imp->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Cliente</label>
                    <select name="cliente" id="filtro-cliente" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os clientes</option>
                        @foreach($clientes ?? [] as $cli)
                            <option value="{{ $cli->id }}" {{ ($filtros['cliente'] ?? '') == $cli->id ? 'selected' : '' }}>
                                {{ $cli->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Origem</label>
                    <select name="origem" id="filtro-origem" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todas as origens</option>
                        @foreach($origens ?? [] as $ori)
                            <option value="{{ $ori }}" {{ ($filtros['origem'] ?? '') == $ori ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', $ori) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Tipo de Documento</label>
                    <select name="tipo_documento" id="filtro-tipo-documento" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        <option value="CNPJ" {{ ($filtros['tipo_documento'] ?? '') === 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                        <option value="CPF" {{ ($filtros['tipo_documento'] ?? '') === 'CPF' ? 'selected' : '' }}>CPF</option>
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Buscar</label>
                    <div class="relative">
                        <input
                            type="text"
                            name="busca"
                            id="busca-participantes"
                            placeholder="Documento ou nome..."
                            value="{{ $filtros['busca'] ?? '' }}"
                            class="w-full border border-gray-300 rounded text-[13px] py-2.5 pl-10 pr-4 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        >
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200">
                <div>
                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Regime</label>
                <select name="regime" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">Regime: Todos</option>
                    <option value="simples nacional" {{ ($filtros['regime'] ?? '') == 'simples nacional' ? 'selected' : '' }}>Simples Nacional</option>
                    <option value="lucro presumido" {{ ($filtros['regime'] ?? '') == 'lucro presumido' ? 'selected' : '' }}>Lucro Presumido</option>
                    <option value="lucro real" {{ ($filtros['regime'] ?? '') == 'lucro real' ? 'selected' : '' }}>Lucro Real</option>
                    <option value="mei" {{ ($filtros['regime'] ?? '') == 'mei' ? 'selected' : '' }}>MEI</option>
                </select>
                </div>
                <div>
                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Situação</label>
                <select name="situacao" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">Situação: Todos</option>
                    <option value="ATIVA" {{ ($filtros['situacao'] ?? '') == 'ATIVA' ? 'selected' : '' }}>Ativa</option>
                    <option value="BAIXADA" {{ ($filtros['situacao'] ?? '') == 'BAIXADA' ? 'selected' : '' }}>Baixada</option>
                    <option value="SUSPENSA" {{ ($filtros['situacao'] ?? '') == 'SUSPENSA' ? 'selected' : '' }}>Suspensa</option>
                    <option value="INAPTA" {{ ($filtros['situacao'] ?? '') == 'INAPTA' ? 'selected' : '' }}>Inapta</option>
                </select>
                </div>
                <div>
                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">UF</label>
                <select name="uf" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">UF: Todos</option>
                    @foreach($ufs ?? [] as $ufOpt)
                        <option value="{{ $ufOpt }}" {{ ($filtros['uf'] ?? '') == $ufOpt ? 'selected' : '' }}>{{ $ufOpt }}</option>
                    @endforeach
                </select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">
                        Filtrar
                    </button>
                    <a href="/app/participantes" class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-2" data-link>
                        Limpar
                    </a>
                </div>
                <div class="flex items-center gap-1 self-start sm:self-auto">
                    <button id="btn-view-list"
                        class="p-2 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 transition-colors view-toggle-btn active-view"
                        title="Visualização em lista" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <button id="btn-view-cards"
                        class="p-2 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 transition-colors view-toggle-btn"
                        title="Visualização em cards" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </button>
                </div>
            </div>
            </div>
        </form>

        {{-- Barra de selecao global --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4 text-sm">
                <button type="button" id="btn-selecionar-todos-filtro" class="text-gray-700 hover:text-gray-900 font-medium underline">
                    Selecionar todos (<span id="total-filtrado">{{ $participantes->total() }}</span>)
                </button>
                <button type="button" id="btn-limpar-selecao-geral" class="text-gray-500 hover:text-gray-700 hidden">
                    Limpar selecao
                </button>
            </div>
            <span id="selecao-persistente-info" class="text-xs text-gray-500 hidden">
                <span id="total-selecionados-persistente">0</span> selecionados (todas as paginas)
            </span>
        </div>

        {{-- Acoes em lote (aparece quando ha selecao) --}}
        <div id="acoes-lote" class="hidden bg-white border border-gray-300 rounded p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded text-white text-sm font-bold" id="count-selecionados" style="background-color: #374151">0</span>
                    <span class="text-sm font-medium text-gray-900"><span id="participantes-selecionados-label">participantes selecionados</span></span>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="btn-exportar" class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Exportar
                    </button>
                    <button type="button" id="btn-monitorar-selecionados" class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Consultar
                    </button>
                    <button type="button" id="btn-bulk-delete" class="inline-flex items-center gap-2 px-4 py-2 rounded border text-white text-sm font-medium transition hover:opacity-90" style="background-color: #b91c1c; border-color: #b91c1c">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Deletar
                    </button>
                    <button type="button" id="btn-limpar-selecao" class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        {{-- Lista de Participantes --}}
        <div id="participantes-list-view" class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="w-10 px-3 py-2.5 text-left bg-gray-50">
                                <input type="checkbox" id="select-all" class="w-4 h-4 rounded border-gray-300 text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            </th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                            <th class="w-[260px] px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Situação / CND</th>
                            <th class="w-[220px] px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status de Consulta</th>
                            <th class="w-[120px] px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Origem</th>
                            <th class="w-20 px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="participantes-tbody">
                        @forelse($participantes ?? [] as $part)
                            @php
                                $isCpf = $part->is_cpf;
                                $origemLabel = match($part->origem_tipo) {
                                    'SPED_EFD_FISCAL' => ['label' => 'EFD Fiscal', 'color' => '#4338ca'],
                                    'SPED_EFD_CONTRIB' => ['label' => 'EFD Contrib', 'color' => '#7c3aed'],
                                    'NFE' => ['label' => 'NF-e', 'color' => '#0f766e'],
                                    'NFSE' => ['label' => 'NFS-e', 'color' => '#0891b2'],
                                    'MANUAL' => ['label' => 'Manual', 'color' => '#6b7280'],
                                    default => ['label' => $part->origem_tipo, 'color' => '#6b7280'],
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" data-participante-id="{{ $part->id }}" data-href="/app/participante/{{ $part->id }}">
                                <td class="px-3 py-3">
                                    <input
                                        type="checkbox"
                                        class="checkbox-participante w-4 h-4 rounded border-gray-300 text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100"
                                        value="{{ $part->id }}"
                                        data-bloqueado="{{ $isCpf ? '1' : '0' }}"
                                        {{ $isCpf ? 'disabled' : '' }}
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="truncate text-sm text-gray-900" title="{{ $part->razao_social }}">{{ $part->razao_social ?? '-' }}</div>
                                            @if($isCpf)
                                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white flex-shrink-0" style="background-color: #9ca3af">CPF</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] font-mono text-gray-500 mt-1">{{ $part->cnpj_formatado }}</div>
                                        <div class="mt-1 flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->consulta_status_hex }}">
                                                {{ $part->consulta_status_label }}
                                            </span>
                                            @if($part->assinatura_label)
                                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->assinatura_hex }}">
                                                    {{ $part->assinatura_label }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-1">
                                            {{ $part->consulta_status_meta }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-1 truncate">
                                            @if($part->cliente)
                                                Cliente: {{ $part->cliente->razao_social ?? '-' }}
                                            @else
                                                Sem vínculo com cliente
                                            @endif
                                        </div>
                                    </div>
                                    @if($isCpf)
                                        <div class="text-[11px] text-gray-500 mt-1">Cadastro bloqueado para seleção em lote</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex flex-col items-center gap-1" title="{{ $part->situacao_cadastral ?? '' }}">
                                        <div class="flex items-center justify-center gap-2 flex-wrap">
                                            @if($part->situacao_cadastral === 'ATIVA')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #047857">
                                                    Ativa
                                                </span>
                                            @elseif($part->situacao_cadastral)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #d97706">
                                                    {{ $part->situacao_cadastral }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #9ca3af">
                                                    Sem Mov.
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->cnd_federal_status_hex }}">
                                                {{ $part->cnd_federal_status_label }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-gray-500 leading-tight text-center">
                                            {{ $part->cnd_federal_meta }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="text-[11px] text-gray-500 leading-tight text-center">
                                            {{ $part->consulta_status_meta }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="min-w-0 flex flex-col items-center">
                                        <span class="inline-flex max-w-full items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemLabel['color'] }}">
                                            {{ $origemLabel['label'] }}
                                        </span>
                                        <div class="text-[11px] text-gray-500 mt-1 leading-tight text-center" title="Base: {{ $part->created_at?->format('d/m/Y') ?? '-' }}">
                                            Base: {{ $part->created_at?->format('d/m/Y') ?? '-' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right align-top">
                                    <button type="button" class="acoes-btn p-2 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                        data-id="{{ $part->id }}"
                                        data-nome="{{ $part->razao_social }}"
                                        data-cnpj="{{ $part->cnpj_formatado }}">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2 uppercase tracking-wide">Nenhum participante encontrado</h3>
                                        <p class="text-sm text-gray-600 mb-4">
                                            @if(($filtros['importacao'] ?? null) || ($filtros['cliente'] ?? null) || ($filtros['origem'] ?? null) || ($filtros['busca'] ?? null) || ($filtros['regime'] ?? null) || ($filtros['situacao'] ?? null) || ($filtros['uf'] ?? null) || ($filtros['tipo_documento'] ?? null))
                                                Nenhum participante corresponde aos filtros aplicados.
                                            @else
                                                Importe participantes de um arquivo SPED para comecar.
                                            @endif
                                        </p>
                                        <a
                                            href="/app/importacao/efd"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700"
                                            data-link
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            Importar SPED
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(isset($participantes) && $participantes->hasPages())
                <div class="border-t border-gray-300 px-4 py-3">
                    {{ $participantes->links() }}
                </div>
            @endif
        </div>

        {{-- Cards View --}}
        <div id="participantes-cards" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($participantes ?? [] as $part)
                @php
                    $isCpf = $part->is_cpf;
                    $cardOrigemLabel = match($part->origem_tipo) {
                        'SPED_EFD_FISCAL'  => ['label' => 'EFD Fiscal', 'color' => '#4338ca'],
                        'SPED_EFD_CONTRIB' => ['label' => 'EFD Contrib', 'color' => '#7c3aed'],
                        'NFE'              => ['label' => 'NF-e', 'color' => '#0f766e'],
                        'NFSE'             => ['label' => 'NFS-e', 'color' => '#0891b2'],
                        'MANUAL'           => ['label' => 'Manual', 'color' => '#6b7280'],
                        default            => ['label' => $part->origem_tipo ?? 'Manual', 'color' => '#6b7280'],
                    };
                @endphp
                <div class="participante-card bg-white border border-gray-300 rounded p-4 hover:bg-gray-50/50 transition-colors cursor-pointer flex flex-col gap-3"
                     data-href="/app/participante/{{ $part->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="text-sm font-semibold text-gray-900 truncate min-w-0">{{ $part->razao_social ?? '-' }}</div>
                                @if($isCpf)
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white flex-shrink-0" style="background-color: #9ca3af">CPF</span>
                                @endif
                            </div>
                            <div class="text-[11px] font-mono text-gray-500 mt-1">{{ $part->cnpj_formatado }}</div>
                            @if($isCpf)
                                <div class="text-[11px] text-gray-500 mt-1">Cadastro bloqueado para seleção em lote</div>
                            @endif
                            <div class="mt-2 flex items-center justify-center gap-2 flex-wrap">
                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->consulta_status_hex }}">
                                    {{ $part->consulta_status_label }}
                                </span>
                                @if($part->assinatura_label)
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->assinatura_hex }}">
                                        {{ $part->assinatura_label }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 mt-1 text-center">{{ $part->consulta_status_meta }}</p>
                            <div class="text-[11px] text-gray-500 mt-1 truncate">
                                @if($part->cliente)
                                    Cliente: {{ $part->cliente->razao_social ?? '-' }}
                                @else
                                    Sem vínculo com cliente
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white flex-shrink-0" style="background-color: {{ $cardOrigemLabel['color'] }}">
                            {{ $cardOrigemLabel['label'] }}
                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex flex-col gap-2">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide text-center">Status de consulta</p>
                            <div class="flex flex-col items-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->consulta_status_hex }}">
                                        {{ $part->consulta_status_label }}
                                    </span>
                                    @if($part->assinatura_label)
                                        <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->assinatura_hex }}">
                                            {{ $part->assinatura_label }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1 leading-tight text-center">{{ $part->consulta_status_meta }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide text-center">Situação / CND</p>
                            <div class="flex flex-col items-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    @if($part->situacao_cadastral === 'ATIVA')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #047857">Ativa</span>
                                    @elseif($part->situacao_cadastral)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #d97706">{{ $part->situacao_cadastral }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #9ca3af">Sem Mov.</span>
                                    @endif
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $part->cnd_federal_status_hex }}">
                                        {{ $part->cnd_federal_status_label }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1 leading-tight text-center">{{ $part->cnd_federal_meta }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide text-center">Origem</p>
                            <div class="flex flex-col items-center">
                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cardOrigemLabel['color'] }}">
                                    {{ $cardOrigemLabel['label'] }}
                                </span>
                                <p class="text-xs text-gray-500 text-center mt-1">{{ $part->created_at?->format('d/m/Y') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-400 text-sm">Nenhum participante encontrado.</div>
            @endforelse
        </div>

    </div>

    </div>
</div>

{{-- Modais (fora do container para overlay correto) --}}

{{-- Dropdown de acoes do participante (menu kebab) --}}
<div id="dropdown-acoes" class="hidden fixed z-[9999] bg-white rounded border border-gray-300 w-56 py-1">
    <div class="px-3 py-2 border-b border-gray-100">
        <p class="text-sm font-semibold text-gray-900 truncate" id="dropdown-acoes-nome"></p>
        <p class="text-xs text-gray-500 font-mono whitespace-nowrap tabular-nums" id="dropdown-acoes-cnpj"></p>
    </div>
    <a id="dropdown-acoes-ver" href="#"
       class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
       data-link>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        Ver detalhes
    </a>
    <a id="dropdown-acoes-editar" href="#"
       class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
       data-link>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        Editar
    </a>
    <button type="button" id="dropdown-acoes-excluir"
        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors" style="color: #b91c1c">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        Excluir
    </button>
</div>

{{-- Modal de confirmacao de exclusao --}}
<div id="modal-excluir-participante" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-excluir-overlay"></div>
        <div class="relative bg-white rounded border border-gray-300 max-w-md w-full p-6 z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded border border-gray-300 flex items-center justify-center">
                    <svg class="w-5 h-5" style="color: #b91c1c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Excluir participante?</h3>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-700 mb-2">
                    <span class="font-medium" id="modal-excluir-cnpj"></span> — <span id="modal-excluir-nome"></span>
                </p>
                <p class="text-sm text-gray-500">
                    Todo o histórico associado será removido permanentemente, incluindo assinaturas, consultas e scores. As notas fiscais onde este participante aparece serão mantidas.
                </p>
                <p class="text-sm text-red-600 font-medium mt-2">
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="btn-cancelar-exclusao" class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-exclusao" class="px-4 py-2 rounded text-white text-sm font-medium transition hover:opacity-90" style="background-color: #b91c1c">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmacao de exclusao em lote --}}
<div id="modal-bulk-delete" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-bulk-delete-overlay"></div>
        <div class="relative bg-white rounded border border-gray-300 max-w-md w-full p-6 z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded border border-gray-300 flex items-center justify-center">
                    <svg class="w-5 h-5" style="color: #b91c1c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Excluir <span id="modal-bulk-delete-count">0</span> <span id="modal-bulk-delete-label">participantes</span>?</h3>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-2">
                    Todo o histórico associado será removido permanentemente, incluindo assinaturas, consultas e scores. As notas fiscais onde estes participantes aparecem serão mantidas.
                </p>
                <p class="text-sm text-red-600 font-medium">
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="btn-cancelar-bulk-delete" class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-bulk-delete" class="px-4 py-2 rounded text-white text-sm font-medium transition hover:opacity-90" style="background-color: #b91c1c">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var STORAGE_KEY = 'participantes_selecionados';

    function initMonitoramentoParticipantesImportados() {
        var container = document.getElementById('monitoramento-participantes-importados-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento Participantes Importados] Inicializando...');

        var selectAll = document.getElementById('select-all');
        var checkboxes = document.querySelectorAll('.checkbox-participante');
        var acoesLote = document.getElementById('acoes-lote');
        var countSelecionados = document.getElementById('count-selecionados');
        var btnLimparSelecao = document.getElementById('btn-limpar-selecao');
        var btnMonitorar = document.getElementById('btn-monitorar-selecionados');
        var btnExportar = document.getElementById('btn-exportar');
        var btnBulkDelete = document.getElementById('btn-bulk-delete');
        var btnSelecionarTodosFiltro = document.getElementById('btn-selecionar-todos-filtro');
        var btnLimparSelecaoGeral = document.getElementById('btn-limpar-selecao-geral');
        var infoSelecaoPersistente = document.getElementById('selecao-persistente-info');
        var totalSelecionadosPersistente = document.getElementById('total-selecionados-persistente');

        // === Persistencia via sessionStorage ===
        function carregarSelecao() {
            try {
                var raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) return new Set(JSON.parse(raw).map(Number));
            } catch (e) {}
            return new Set();
        }

        function salvarSelecao(setIds) {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(setIds)));
            } catch (e) {}
        }

        function limparSelecaoStorage() {
            try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
        }

        var selectedIds = carregarSelecao();

        // Funcao para obter IDs selecionados (do Set persistente)
        function getIdsSelecionados() {
            return Array.from(selectedIds);
        }

        function checkboxBloqueado(cb) {
            return cb.disabled || cb.dataset.bloqueado === '1';
        }

        // Funcao para atualizar contagem e visibilidade das acoes em lote
        function atualizarAcoesLote() {
            var count = selectedIds.size;

            if (countSelecionados) countSelecionados.textContent = count;
            var labelSelecionados = document.getElementById('participantes-selecionados-label');
            if (labelSelecionados) labelSelecionados.textContent = count === 1 ? 'participante selecionado' : 'participantes selecionados';

            if (acoesLote) {
                if (count > 0) {
                    acoesLote.classList.remove('hidden');
                } else {
                    acoesLote.classList.add('hidden');
                }
            }

            // Atualizar info de selecao persistente
            if (infoSelecaoPersistente && totalSelecionadosPersistente) {
                if (count > 0) {
                    totalSelecionadosPersistente.textContent = count;
                    infoSelecaoPersistente.classList.remove('hidden');
                } else {
                    infoSelecaoPersistente.classList.add('hidden');
                }
            }

            // Botao limpar geral
            if (btnLimparSelecaoGeral) {
                if (count > 0) {
                    btnLimparSelecaoGeral.classList.remove('hidden');
                } else {
                    btnLimparSelecaoGeral.classList.add('hidden');
                }
            }

            // Atualizar estado do checkbox "selecionar todos" da pagina
            var checkboxesAtual = Array.from(document.querySelectorAll('.checkbox-participante'));
            var checkboxesElegiveis = checkboxesAtual.filter(function(cb) {
                return !checkboxBloqueado(cb);
            });
            var checkedCount = 0;
            checkboxesElegiveis.forEach(function(cb) {
                if (cb.checked) checkedCount++;
            });
            if (selectAll) {
                selectAll.checked = checkedCount === checkboxesElegiveis.length && checkedCount > 0;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxesElegiveis.length;
                selectAll.disabled = checkboxesElegiveis.length === 0;
            }

            salvarSelecao(selectedIds);
        }

        // Sincronizar checkboxes da pagina atual com o Set persistente
        function sincronizarCheckboxes() {
            checkboxes.forEach(function(cb) {
                var id = Number(cb.value);
                if (checkboxBloqueado(cb)) {
                    cb.checked = false;
                    selectedIds.delete(id);
                    return;
                }
                cb.checked = selectedIds.has(id);
            });
        }

        // Ao carregar: restaurar selecao dos checkboxes visiveis
        sincronizarCheckboxes();
        atualizarAcoesLote();

        // Event listener para checkbox "selecionar todos" (pagina atual)
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(cb) {
                    if (checkboxBloqueado(cb)) {
                        cb.checked = false;
                        return;
                    }
                    var id = Number(cb.value);
                    cb.checked = selectAll.checked;
                    if (selectAll.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                atualizarAcoesLote();
            });
        }

        // Event listeners para checkboxes individuais
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (checkboxBloqueado(cb)) {
                    cb.checked = false;
                    selectedIds.delete(Number(cb.value));
                    atualizarAcoesLote();
                    return;
                }
                var id = Number(cb.value);
                if (cb.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                atualizarAcoesLote();
            });
        });

        // === Botao "Selecionar todos (N)" - busca AJAX ===
        if (btnSelecionarTodosFiltro) {
            btnSelecionarTodosFiltro.addEventListener('click', async function() {
                btnSelecionarTodosFiltro.disabled = true;
                btnSelecionarTodosFiltro.textContent = 'Carregando...';

                try {
                    // Construir query string com filtros atuais
                    var params = new URLSearchParams();
                    var filtroImportacao = document.getElementById('filtro-importacao');
                    var filtroCliente = document.getElementById('filtro-cliente');
                    var filtroOrigem = document.getElementById('filtro-origem');
                    var filtroTipoDocumento = document.getElementById('filtro-tipo-documento');
                    var buscaInput = document.getElementById('busca-participantes');
                    var filtroRegime = formFiltros ? formFiltros.querySelector('select[name="regime"]') : null;
                    var filtroSituacao = formFiltros ? formFiltros.querySelector('select[name="situacao"]') : null;
                    var filtroUf = formFiltros ? formFiltros.querySelector('select[name="uf"]') : null;

                    if (filtroImportacao && filtroImportacao.value) params.set('importacao', filtroImportacao.value);
                    if (filtroCliente && filtroCliente.value) params.set('cliente', filtroCliente.value);
                    if (filtroOrigem && filtroOrigem.value) params.set('origem', filtroOrigem.value);
                    if (filtroTipoDocumento && filtroTipoDocumento.value) params.set('tipo_documento', filtroTipoDocumento.value);
                    if (buscaInput && buscaInput.value) params.set('busca', buscaInput.value);
                    if (filtroRegime && filtroRegime.value) params.set('regime', filtroRegime.value);
                    if (filtroSituacao && filtroSituacao.value) params.set('situacao', filtroSituacao.value);
                    if (filtroUf && filtroUf.value) params.set('uf', filtroUf.value);

                    var url = '/app/participantes/todos-ids' + (params.toString() ? '?' + params.toString() : '');
                    var res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    var data = await res.json();
                    if (!data.success) throw new Error('Erro ao buscar IDs');

                    // Adicionar todos ao Set
                    data.ids.forEach(function(id) { selectedIds.add(Number(id)); });
                    sincronizarCheckboxes();
                    atualizarAcoesLote();

                    window.showToast && window.showToast(data.total + ' participantes selecionados', 'success');
                } catch (err) {
                    console.error('[Participantes] Erro ao selecionar todos:', err);
                    window.showToast && window.showToast('Erro ao selecionar todos os participantes', 'error');
                } finally {
                    var totalEl = document.getElementById('total-filtrado');
                    var total = totalEl ? totalEl.textContent : '?';
                    btnSelecionarTodosFiltro.disabled = false;
                    btnSelecionarTodosFiltro.innerHTML = 'Selecionar todos (<span id="total-filtrado">' + total + '</span>)';
                }
            });
        }

        // === Botao limpar selecao geral ===
        if (btnLimparSelecaoGeral) {
            btnLimparSelecaoGeral.addEventListener('click', function() {
                selectedIds.clear();
                limparSelecaoStorage();
                sincronizarCheckboxes();
                if (selectAll) selectAll.checked = false;
                atualizarAcoesLote();
            });
        }

        // Botao limpar selecao (na barra de acoes)
        if (btnLimparSelecao) {
            btnLimparSelecao.addEventListener('click', function() {
                selectedIds.clear();
                limparSelecaoStorage();
                sincronizarCheckboxes();
                if (selectAll) selectAll.checked = false;
                atualizarAcoesLote();
            });
        }

        // Botao exportar (placeholder)
        if (btnExportar) {
            btnExportar.addEventListener('click', function() {
                if (window.showToast) {
                    window.showToast('Funcionalidade de exportacao em desenvolvimento', 'info');
                }
            });
        }

        // Botao consultar selecionados
        if (btnMonitorar) {
            btnMonitorar.addEventListener('click', function() {
                var selecionados = getIdsSelecionados();
                if (selecionados.length === 0) {
                    alert('Selecione pelo menos um participante.');
                    return;
                }
                // Limpar selecao ao navegar
                limparSelecaoStorage();
                // Redirecionar para nova consulta com IDs pre-selecionados
                window.location.href = '/app/consulta/nova?participantes=' + selecionados.join(',');
            });
        }

        // === Bulk delete ===
        var modalBulkDelete = document.getElementById('modal-bulk-delete');
        var modalBulkDeleteOverlay = document.getElementById('modal-bulk-delete-overlay');
        var modalBulkDeleteCount = document.getElementById('modal-bulk-delete-count');
        var modalBulkDeleteLabel = document.getElementById('modal-bulk-delete-label');
        var btnCancelarBulkDelete = document.getElementById('btn-cancelar-bulk-delete');
        var btnConfirmarBulkDelete = document.getElementById('btn-confirmar-bulk-delete');

        function abrirModalBulkDelete() {
            var ids = getIdsSelecionados();
            if (ids.length === 0) return;
            if (modalBulkDeleteCount) modalBulkDeleteCount.textContent = ids.length;
            if (modalBulkDeleteLabel) modalBulkDeleteLabel.textContent = ids.length === 1 ? 'participante' : 'participantes';
            if (modalBulkDelete) modalBulkDelete.classList.remove('hidden');
        }

        function fecharModalBulkDelete() {
            if (modalBulkDelete) modalBulkDelete.classList.add('hidden');
        }

        if (btnBulkDelete) btnBulkDelete.addEventListener('click', abrirModalBulkDelete);
        if (btnCancelarBulkDelete) btnCancelarBulkDelete.addEventListener('click', fecharModalBulkDelete);
        if (modalBulkDeleteOverlay) modalBulkDeleteOverlay.addEventListener('click', fecharModalBulkDelete);

        if (btnConfirmarBulkDelete) {
            btnConfirmarBulkDelete.addEventListener('click', async function() {
                var ids = getIdsSelecionados();
                if (ids.length === 0) return;

                btnConfirmarBulkDelete.disabled = true;
                btnConfirmarBulkDelete.innerHTML = '<svg class="animate-spin w-4 h-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Excluindo...';

                try {
                    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    var res = await fetch('/app/participantes/bulk-delete', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: ids.map(Number) }),
                    });

                    var data = await res.json();

                    if (!res.ok || !data.success) {
                        throw new Error(data.error || data.message || 'Erro ao excluir participantes');
                    }

                    window.showToast && window.showToast(data.message || 'Participantes excluidos com sucesso!', 'success');

                    // Remover linhas da tabela que estao na pagina
                    ids.forEach(function(id) {
                        var row = document.querySelector('tr[data-participante-id="' + id + '"]');
                        if (row) row.remove();
                    });

                    // Limpar selecao
                    selectedIds.clear();
                    limparSelecaoStorage();
                    sincronizarCheckboxes();
                    if (selectAll) selectAll.checked = false;
                    atualizarAcoesLote();
                    fecharModalBulkDelete();

                } catch (err) {
                    console.error('[Monitoramento Participantes] Erro ao excluir em lote:', err);
                    window.showToast && window.showToast(err.message || 'Erro ao excluir participantes', 'error');
                    fecharModalBulkDelete();
                } finally {
                    btnConfirmarBulkDelete.disabled = false;
                    btnConfirmarBulkDelete.textContent = 'Excluir';
                }
            });
        }

        // Submit do formulario via SPA (se usar data-link)
        var formFiltros = document.getElementById('form-filtros');
        if (formFiltros) {
            formFiltros.addEventListener('submit', function(e) {
                // Deixar o form submeter normalmente se SPA router nao estiver ativo
            });
        }

        // === Clique na linha abre perfil do participante ===
        var tbody = document.getElementById('participantes-tbody');
        if (tbody) {
            tbody.addEventListener('click', function(e) {
                // Ignorar cliques em checkbox, botoes e links
                if (e.target.closest('input[type="checkbox"], button, a')) return;

                var row = e.target.closest('tr[data-href]');
                if (!row) return;

                // Navegar via SPA
                var href = row.dataset.href;
                if (href && window.navigateTo) {
                    window.navigateTo(href);
                } else if (href) {
                    window.location.href = href;
                }
            });
        }

        // === Exclusao de participante com modal ===
        var modal = document.getElementById('modal-excluir-participante');
        var modalOverlay = document.getElementById('modal-excluir-overlay');
        var modalCnpj = document.getElementById('modal-excluir-cnpj');
        var modalNome = document.getElementById('modal-excluir-nome');
        var btnCancelar = document.getElementById('btn-cancelar-exclusao');
        var btnConfirmar = document.getElementById('btn-confirmar-exclusao');
        var participanteIdParaExcluir = null;

        function abrirModalExclusao(id, cnpj, nome) {
            participanteIdParaExcluir = id;
            if (modalCnpj) modalCnpj.textContent = cnpj;
            if (modalNome) modalNome.textContent = nome || 'Sem razao social';
            if (modal) modal.classList.remove('hidden');
        }

        function fecharModalExclusao() {
            if (modal) modal.classList.add('hidden');
            participanteIdParaExcluir = null;
        }

        // === Dropdown de acoes (kebab menu) ===
        var dropdownAcoes = document.getElementById('dropdown-acoes');
        var dropdownAcoesNome = document.getElementById('dropdown-acoes-nome');
        var dropdownAcoesCnpj = document.getElementById('dropdown-acoes-cnpj');
        var dropdownAcoesVer = document.getElementById('dropdown-acoes-ver');
        var dropdownAcoesEditar = document.getElementById('dropdown-acoes-editar');
        var dropdownAcoesExcluir = document.getElementById('dropdown-acoes-excluir');
        var acaoParticipanteId = null;
        var dropdownBtnAtual = null;

        function posicionarDropdown(btnElement) {
            if (!dropdownAcoes || !btnElement) return;
            dropdownAcoes.style.visibility = 'hidden';
            dropdownAcoes.classList.remove('hidden');
            var dropdownHeight = dropdownAcoes.offsetHeight;
            var dropdownWidth = dropdownAcoes.offsetWidth;
            dropdownAcoes.classList.add('hidden');
            dropdownAcoes.style.visibility = '';

            var rect = btnElement.getBoundingClientRect();
            var spaceBelow = window.innerHeight - rect.bottom;
            var spaceAbove = rect.top;

            var left = rect.right - dropdownWidth;
            if (left < 8) left = 8;

            var top;
            if (spaceBelow >= dropdownHeight + 4) {
                top = rect.bottom + 4;
            } else if (spaceAbove >= dropdownHeight + 4) {
                top = rect.top - dropdownHeight - 4;
            } else {
                top = rect.bottom + 4;
            }

            dropdownAcoes.style.top = top + 'px';
            dropdownAcoes.style.left = left + 'px';
        }

        function abrirDropdownAcoes(btnElement, id, nome, cnpj) {
            if (!dropdownAcoes.classList.contains('hidden') && dropdownBtnAtual === btnElement) {
                fecharDropdownAcoes();
                return;
            }
            acaoParticipanteId = id;
            dropdownBtnAtual = btnElement;
            if (dropdownAcoesNome) dropdownAcoesNome.textContent = nome || 'Sem razao social';
            if (dropdownAcoesCnpj) dropdownAcoesCnpj.textContent = cnpj || '';
            if (dropdownAcoesVer) dropdownAcoesVer.href = '/app/participante/' + id;
            if (dropdownAcoesEditar) dropdownAcoesEditar.href = '/app/participante/' + id + '/editar';
            posicionarDropdown(btnElement);
            dropdownAcoes.classList.remove('hidden');
        }

        function fecharDropdownAcoes() {
            if (dropdownAcoes) dropdownAcoes.classList.add('hidden');
            dropdownBtnAtual = null;
        }

        container.addEventListener('click', function(e) {
            var acaoBtn = e.target.closest('.acoes-btn');
            if (acaoBtn) {
                e.stopPropagation();
                abrirDropdownAcoes(acaoBtn, acaoBtn.dataset.id, acaoBtn.dataset.nome, acaoBtn.dataset.cnpj);
            }
        });

        document.addEventListener('click', function(e) {
            if (dropdownAcoes && !dropdownAcoes.classList.contains('hidden') && !dropdownAcoes.contains(e.target)) {
                fecharDropdownAcoes();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdownAcoes && !dropdownAcoes.classList.contains('hidden')) {
                fecharDropdownAcoes();
            }
        });

        window.addEventListener('scroll', function() {
            if (dropdownAcoes && !dropdownAcoes.classList.contains('hidden')) {
                fecharDropdownAcoes();
            }
        }, true);

        if (dropdownAcoesVer) {
            dropdownAcoesVer.addEventListener('click', fecharDropdownAcoes);
        }

        if (dropdownAcoesExcluir) {
            dropdownAcoesExcluir.addEventListener('click', function() {
                var id = acaoParticipanteId;
                var cnpj = dropdownAcoesCnpj ? dropdownAcoesCnpj.textContent : '';
                var nome = dropdownAcoesNome ? dropdownAcoesNome.textContent : '';
                fecharDropdownAcoes();
                abrirModalExclusao(id, cnpj, nome);
            });
        }

        if (btnCancelar) btnCancelar.addEventListener('click', fecharModalExclusao);
        if (modalOverlay) modalOverlay.addEventListener('click', fecharModalExclusao);

        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', async function() {
                if (!participanteIdParaExcluir) return;

                btnConfirmar.disabled = true;
                btnConfirmar.textContent = 'Excluindo...';

                try {
                    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    var res = await fetch('/app/participante/' + participanteIdParaExcluir, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    var data = await res.json();

                    if (!res.ok || !data.success) {
                        throw new Error(data.error || 'Erro ao excluir participante');
                    }

                    window.showToast && window.showToast(data.message || 'Participante excluido com sucesso!', 'success');

                    // Remover do Set persistente
                    selectedIds.delete(Number(participanteIdParaExcluir));
                    salvarSelecao(selectedIds);

                    var row = document.querySelector('tr[data-participante-id="' + participanteIdParaExcluir + '"]');
                    if (row) row.remove();

                    fecharModalExclusao();
                    atualizarAcoesLote();

                } catch (err) {
                    console.error('[Monitoramento Participantes] Erro:', err);
                    window.showToast && window.showToast(err.message || 'Erro ao excluir participante', 'error');
                    fecharModalExclusao();
                } finally {
                    btnConfirmar.disabled = false;
                    btnConfirmar.textContent = 'Excluir';
                }
            });
        }

        // === Toggle lista/cards ===
        var VIEW_KEY = 'participantes_view_mode';
        var tableWrapper = document.getElementById('participantes-list-view');
        var cardsGrid = document.getElementById('participantes-cards');
        var btnViewList = document.getElementById('btn-view-list');
        var btnViewCards = document.getElementById('btn-view-cards');

        function setView(mode) {
            localStorage.setItem(VIEW_KEY, mode);
            if (mode === 'cards') {
                if (tableWrapper) tableWrapper.classList.add('hidden');
                if (cardsGrid) cardsGrid.classList.remove('hidden');
                if (btnViewList) btnViewList.classList.remove('active-view');
                if (btnViewCards) btnViewCards.classList.add('active-view');
            } else {
                if (tableWrapper) tableWrapper.classList.remove('hidden');
                if (cardsGrid) cardsGrid.classList.add('hidden');
                if (btnViewCards) btnViewCards.classList.remove('active-view');
                if (btnViewList) btnViewList.classList.add('active-view');
            }
        }

        if (btnViewList) btnViewList.addEventListener('click', function() { setView('list'); });
        if (btnViewCards) btnViewCards.addEventListener('click', function() { setView('cards'); });

        if (cardsGrid) {
            cardsGrid.addEventListener('click', function(e) {
                var card = e.target.closest('.participante-card');
                if (!card || !card.dataset.href) return;
                var href = card.dataset.href;
                if (window.navigateTo) {
                    window.navigateTo(href);
                } else {
                    window.location.href = href;
                }
            });
        }

        // Restaurar preferencia (padrao: lista)
        setView(localStorage.getItem(VIEW_KEY) || 'list');

        console.log('[Monitoramento Participantes Importados] Inicializacao concluida');
    }

    // Expor globalmente para SPA
    window.initMonitoramentoParticipantesImportados = initMonitoramentoParticipantesImportados;

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoParticipantesImportados, { once: true });
    } else {
        initMonitoramentoParticipantesImportados();
    }
})();
</script>
