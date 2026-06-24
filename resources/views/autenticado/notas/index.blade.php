@php
    $origemFiltro = $filtros['origem'] ?? '';
    $tipoFiltro = $filtros['tipo_operacao'] ?? '';
    $modeloFiltro = $filtros['modelo'] ?? '';
    $clienteFiltro = $filtros['cliente_id'] ?? '';
    $participanteFiltro = $filtros['participante_id'] ?? '';
    $importacaoFiltro = $filtros['importacao_id'] ?? '';
    $buscaFiltro = $filtros['busca'] ?? '';
    $dataInicio = $filtros['data_inicio'] ?? '';
    $dataFim = $filtros['data_fim'] ?? '';
    $ops = $kpis['operacoes'];
    $trib = $kpis['tributos'];
    $filtrosAtivos = count(array_filter($filtros, fn ($value) => $value !== null && $value !== ''));

    $origemBadgeMap = [
        'efd' => ['label' => 'EFD', 'hex' => '#4338ca'],
        'xml' => ['label' => 'XML', 'hex' => '#0f766e'],
    ];

    $tipoBadgeMap = [
        'entrada' => ['label' => 'ENTRADA', 'hex' => '#047857'],
        'saida' => ['label' => 'SAÍDA', 'hex' => '#d97706'],
    ];
@endphp

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Notas Fiscais</h1>
            <p class="text-xs text-gray-500 mt-1">Listagem consolidada de documentos importados via EFD e XML.</p>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Entradas</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($ops['entradas']['quantidade'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($ops['entradas']['valor'], 2, ',', '.') }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Saídas</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($ops['saidas']['quantidade'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($ops['saidas']['valor'], 2, ',', '.') }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Devoluções</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($ops['devolucoes']['quantidade'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($ops['devolucoes']['valor'], 2, ',', '.') }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Total</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($ops['total']['quantidade'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($ops['total']['valor'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Tributário</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">ICMS</p>
                    <div class="space-y-1 text-sm text-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <span>Crédito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['icms']['credito'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Débito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['icms']['debito'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">PIS</p>
                    <div class="space-y-1 text-sm text-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <span>Crédito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['pis']['credito'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Débito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['pis']['debito'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">COFINS</p>
                    <div class="space-y-1 text-sm text-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <span>Crédito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['cofins']['credito'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Débito</span>
                            <span class="font-mono text-gray-900">R$ {{ number_format($trib['cofins']['debito'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 hidden sm:block rounded-t">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <form id="nf-filtros-form">
                <button type="button" id="nf-filtros-toggle" class="sm:hidden w-full flex items-center justify-between px-4 py-3 text-sm text-gray-700 border-b border-gray-200 rounded-t">
                    <span class="inline-flex items-center gap-2">
                        <span>Filtros</span>
                        @if($filtrosAtivos > 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $filtrosAtivos }}</span>
                        @endif
                    </span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 nf-filtros-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="nf-filtros-grid" class="hidden sm:block px-4 sm:px-5 py-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Origem</label>
                            <select name="origem" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todas</option>
                                <option value="efd" {{ $origemFiltro === 'efd' ? 'selected' : '' }}>EFD</option>
                                <option value="xml" {{ $origemFiltro === 'xml' ? 'selected' : '' }}>XML</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">De</label>
                            <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Até</label>
                            <input type="date" name="data_fim" value="{{ $dataFim }}" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Tipo</label>
                            <select name="tipo_operacao" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todos</option>
                                <option value="entrada" {{ $tipoFiltro === 'entrada' ? 'selected' : '' }}>Entrada</option>
                                <option value="saida" {{ $tipoFiltro === 'saida' ? 'selected' : '' }}>Saída</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Modelo</label>
                            <select name="modelo" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todos</option>
                                <option value="nfe" {{ $modeloFiltro === 'nfe' ? 'selected' : '' }}>NF-e</option>
                                <option value="cte" {{ $modeloFiltro === 'cte' ? 'selected' : '' }}>CT-e</option>
                                <option value="nfce" {{ $modeloFiltro === 'nfce' ? 'selected' : '' }}>NFC-e</option>
                                <option value="nfse" {{ $modeloFiltro === 'nfse' ? 'selected' : '' }}>NFS-e</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Cliente</label>
                            <select name="cliente_id" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todos</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ $clienteFiltro == $c->id ? 'selected' : '' }}>{{ $c->razao_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Participante</label>
                            <select name="participante_id" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todos</option>
                                @foreach($participantes as $p)
                                    <option value="{{ $p->id }}" {{ $participanteFiltro == $p->id ? 'selected' : '' }}>{{ $p->razao_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Importação</label>
                            <select name="importacao_id" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Todas</option>
                                @foreach($importacoes as $imp)
                                    <option value="{{ $imp->id }}" {{ $importacaoFiltro == $imp->id ? 'selected' : '' }}>{{ $imp->filename }} · {{ $imp->tipo_efd }} · {{ $imp->created_at?->format('d/m/Y') }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- CFOP / CST: dropdowns multi-select (item-level), alinhados aos demais filtros --}}
                        @php
                            $cfopsSel = $filtros['cfops'] ?? [];
                            $cstsSel = $filtros['csts'] ?? [];
                        @endphp

                        {{-- CFOP --}}
                        <div class="relative" data-pop>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">CFOP</label>
                            <button type="button" data-pop-toggle class="w-full flex items-center justify-between gap-2 border border-gray-300 rounded text-[13px] py-2.5 px-3 bg-white hover:border-gray-400 focus:ring-1 focus:ring-gray-400">
                                <span class="truncate {{ count($cfopsSel) ? 'text-gray-900 font-medium' : 'text-gray-500' }}">{{ count($cfopsSel) ? count($cfopsSel).' selec.' : 'Todos' }}</span>
                                <svg data-pop-chevron class="w-4 h-4 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div data-pop-panel class="hidden absolute z-30 left-0 mt-1 w-80 max-w-[calc(100vw-2.5rem)] bg-white border border-gray-300 rounded shadow-lg">
                                <div class="flex items-center gap-2 p-2 border-b border-gray-200 bg-gray-50 rounded-t">
                                    <input type="text" oninput="catFiltro.buscar('cfop', this.value)" placeholder="buscar código ou descrição…" class="flex-1 min-w-0 text-[12px] py-1.5 px-2.5 border border-gray-300 rounded">
                                    <span id="cfopCount" class="text-[11px] font-semibold whitespace-nowrap" style="color:#1d4ed8">{{ count($cfopsSel) ? count($cfopsSel).' sel.' : '' }}</span>
                                </div>
                                <div id="cfopBox" class="max-h-[240px] overflow-y-auto divide-y divide-gray-100">
                                    @forelse($cfopOpcoes as $cf)
                                        <label data-row data-search="{{ strtolower($cf['codigo'].' '.$cf['descricao']) }}" class="flex items-center gap-2 px-2.5 py-1.5 text-[12px] cursor-pointer hover:bg-gray-50">
                                            <input type="checkbox" name="cfops[]" value="{{ $cf['codigo'] }}" onchange="catFiltro.contar('cfop')" @checked(in_array($cf['codigo'], $cfopsSel, true))>
                                            <span class="font-mono font-semibold text-gray-900">{{ $cf['codigo'] }}</span>
                                            @if($cf['tipo'] !== 'indefinido')
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase text-white shrink-0" style="background-color: {{ $cf['tipo'] === 'entrada' ? '#1d4ed8' : '#047857' }}">{{ $cf['tipo'] }}</span>
                                            @endif
                                            <span class="text-gray-600 truncate" title="{{ $cf['descricao'] }}">{{ $cf['descricao'] ?: '—' }}</span>
                                        </label>
                                    @empty
                                        <span class="block px-2.5 py-2 text-[11px] text-gray-400">Sem dados no período/filtro.</span>
                                    @endforelse
                                </div>
                                @if(count($cfopOpcoes))
                                    <div class="px-2.5 py-1.5 border-t border-gray-200 bg-gray-50 rounded-b flex gap-3">
                                        <button type="button" onclick="catFiltro.marcar('cfop', true)" class="text-[11px] text-blue-600 cursor-pointer">Marcar visíveis</button>
                                        <button type="button" onclick="catFiltro.marcar('cfop', false)" class="text-[11px] text-gray-500 cursor-pointer">Limpar seleção</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- CST --}}
                        <div class="relative" data-pop>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">CST</label>
                            <button type="button" data-pop-toggle class="w-full flex items-center justify-between gap-2 border border-gray-300 rounded text-[13px] py-2.5 px-3 bg-white hover:border-gray-400 focus:ring-1 focus:ring-gray-400">
                                <span class="truncate {{ count($cstsSel) ? 'text-gray-900 font-medium' : 'text-gray-500' }}">{{ count($cstsSel) ? count($cstsSel).' selec.' : 'Todos' }}</span>
                                <svg data-pop-chevron class="w-4 h-4 text-gray-400 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div data-pop-panel class="hidden absolute z-30 right-0 sm:left-0 mt-1 w-52 max-w-[calc(100vw-2.5rem)] bg-white border border-gray-300 rounded shadow-lg">
                                <div class="flex items-center gap-2 p-2 border-b border-gray-200 bg-gray-50 rounded-t">
                                    <input type="text" oninput="catFiltro.buscar('cst', this.value)" placeholder="buscar…" class="flex-1 min-w-0 text-[12px] py-1.5 px-2.5 border border-gray-300 rounded">
                                    <span id="cstCount" class="text-[11px] font-semibold whitespace-nowrap" style="color:#1d4ed8">{{ count($cstsSel) ? count($cstsSel).' sel.' : '' }}</span>
                                </div>
                                <div id="cstBox" class="max-h-[240px] overflow-y-auto divide-y divide-gray-100">
                                    @forelse($facetas['csts'] ?? [] as $ct)
                                        <label data-row data-search="{{ strtolower($ct) }}" class="flex items-center gap-2 px-2.5 py-1.5 text-[12px] cursor-pointer hover:bg-gray-50">
                                            <input type="checkbox" name="csts[]" value="{{ $ct }}" onchange="catFiltro.contar('cst')" @checked(in_array($ct, $cstsSel, true))>
                                            <span class="font-mono font-semibold text-gray-900">{{ $ct }}</span>
                                        </label>
                                    @empty
                                        <span class="block px-2.5 py-2 text-[11px] text-gray-400">Sem dados.</span>
                                    @endforelse
                                </div>
                                @if(count($facetas['csts'] ?? []))
                                    <div class="px-2.5 py-1.5 border-t border-gray-200 bg-gray-50 rounded-b flex gap-3">
                                        <button type="button" onclick="catFiltro.marcar('cst', true)" class="text-[11px] text-blue-600 cursor-pointer">Marcar visíveis</button>
                                        <button type="button" onclick="catFiltro.marcar('cst', false)" class="text-[11px] text-gray-500 cursor-pointer">Limpar seleção</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Busca</label>
                        <input type="text" name="busca" value="{{ $buscaFiltro }}" placeholder="Chave de acesso ou número da nota" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    </div>

                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">Filtrar</button>
                        <a href="/app/notas" data-link class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-2">Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        @if($notas->total() > 0)
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Origem</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Número / Série</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Modelo</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emissão</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cliente</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($notas as $n)
                                @php
                                    $origemBadge = $origemBadgeMap[$n['origem']] ?? ['label' => strtoupper($n['origem']), 'hex' => '#374151'];
                                    $tipoBadge = $tipoBadgeMap[$n['tipo_operacao']] ?? ['label' => strtoupper($n['tipo_operacao'] ?? 'N/A'), 'hex' => '#9ca3af'];
                                    $dataFormatada = $n['data_emissao'] ? \Carbon\Carbon::parse($n['data_emissao'])->format('d/m/Y') : '—';
                                    $numero = $n['numero'] ?? '—';
                                    $serie = $n['serie'] ? ' / ' . $n['serie'] : '';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer nf-row" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-sm font-mono whitespace-nowrap">
                                        <a href="/app/notas/{{ $n['origem'] }}/{{ $n['id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $numero }}{{ $serie }}</a>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $n['modelo_label'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $dataFormatada }}</td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700 max-w-xs">
                                        @if($n['participante_id'])
                                            <a href="/app/participante/{{ $n['participante_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline block truncate">{{ $n['participante_nome'] ?? '—' }}</a>
                                        @else
                                            <div class="truncate">{{ $n['participante_nome'] ?? '—' }}</div>
                                        @endif
                                        @if($n['participante_doc'])
                                            <div class="text-[11px] font-mono text-gray-400">{{ $n['participante_doc'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700 max-w-[10rem]">
                                        @if($n['cliente_id'])
                                            <a href="/app/cliente/{{ $n['cliente_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline truncate block">{{ $n['cliente_nome'] ?? '—' }}</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm font-semibold text-gray-900 text-right whitespace-nowrap font-mono">
                                        R$ {{ number_format($n['valor_total'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <button type="button" class="nf-expand-btn text-gray-400 hover:text-gray-700 transition-colors p-1" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}" title="Ver detalhes">
                                            <svg class="w-5 h-5 nf-expand-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="nf-detail-row hidden" data-detail-for="{{ $n['origem'] }}-{{ $n['id'] }}">
                                    <td colspan="9" class="px-0 py-0">
                                        <div class="nf-detail-content bg-gray-50 border-t border-gray-200"></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden divide-y divide-gray-100">
                    @foreach($notas as $n)
                        @php
                            $origemBadge = $origemBadgeMap[$n['origem']] ?? ['label' => strtoupper($n['origem']), 'hex' => '#374151'];
                            $tipoBadge = $tipoBadgeMap[$n['tipo_operacao']] ?? ['label' => strtoupper($n['tipo_operacao'] ?? 'N/A'), 'hex' => '#9ca3af'];
                            $dataFormatada = $n['data_emissao'] ? \Carbon\Carbon::parse($n['data_emissao'])->format('d/m/Y') : '—';
                            $numero = $n['numero'] ?? '—';
                            $serie = $n['serie'] ? ' / ' . $n['serie'] : '';
                        @endphp
                        <div class="px-4 py-4 nf-card cursor-pointer" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $n['modelo_label'] }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                                </div>
                                <button type="button" class="nf-expand-btn text-gray-400 hover:text-gray-700 p-2 -mr-2 min-w-[40px] min-h-[40px] flex items-center justify-center" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                                    <svg class="w-5 h-5 nf-expand-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-baseline justify-between gap-2">
                                <a href="/app/notas/{{ $n['origem'] }}/{{ $n['id'] }}" data-link class="text-sm font-mono font-medium text-gray-900 hover:text-gray-600 hover:underline">{{ $numero }}{{ $serie }}</a>
                                <span class="text-sm font-semibold font-mono text-gray-900">R$ {{ number_format($n['valor_total'], 2, ',', '.') }}</span>
                            </div>
                            <div class="mt-1 text-[11px] text-gray-500">{{ $dataFormatada }}</div>
                            @if($n['participante_nome'])
                                <div class="mt-1 text-sm text-gray-700 truncate">
                                    @if($n['participante_id'])
                                        <a href="/app/participante/{{ $n['participante_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $n['participante_nome'] }}</a>
                                    @else
                                        {{ $n['participante_nome'] }}
                                    @endif
                                </div>
                            @endif
                            @if($n['cliente_nome'])
                                <div class="mt-0.5 text-[11px] text-gray-500 truncate">
                                    @if($n['cliente_id'])
                                        <a href="/app/cliente/{{ $n['cliente_id'] }}" data-link class="text-gray-700 hover:text-gray-900 hover:underline">{{ $n['cliente_nome'] }}</a>
                                    @else
                                        {{ $n['cliente_nome'] }}
                                    @endif
                                </div>
                            @endif
                            <div class="nf-mobile-detail hidden mt-3 bg-gray-50 border border-gray-200 rounded"></div>
                        </div>
                    @endforeach
                </div>

                @if($notas->hasPages())
                    <div class="border-t border-gray-300 px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-2">
                        <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                            Mostrando {{ $notas->firstItem() }}-{{ $notas->lastItem() }} de {{ number_format($notas->total()) }}
                        </p>
                        <div class="flex items-center gap-1">
                            @if($notas->onFirstPage())
                                <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Anterior</span>
                            @else
                                <a href="{{ $notas->previousPageUrl() }}" data-link class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</a>
                            @endif

                            <span class="hidden sm:contents">
                                @foreach($notas->getUrlRange(max(1, $notas->currentPage() - 2), min($notas->lastPage(), $notas->currentPage() + 2)) as $p => $url)
                                    @if($p == $notas->currentPage())
                                        <span class="px-3 py-1.5 text-[10px] font-bold text-white rounded" style="background-color: #1f2937">{{ $p }}</span>
                                    @else
                                        <a href="{{ $url }}" data-link class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $p }}</a>
                                    @endif
                                @endforeach
                            </span>
                            <span class="sm:hidden px-3 py-1.5 text-[10px] text-gray-500">{{ $notas->currentPage() }}/{{ $notas->lastPage() }}</span>

                            @if($notas->hasMorePages())
                                <a href="{{ $notas->nextPageUrl() }}" data-link class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Próxima</a>
                            @else
                                <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Próxima</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado</span>
                </div>
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-900">Nenhuma nota encontrada</p>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($filtrosAtivos > 0)
                            Ajuste os filtros para localizar documentos.
                        @else
                            Importe arquivos EFD ou XML para visualizar notas fiscais aqui.
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Filtros CFOP/CST: busca incremental, marcar/limpar visíveis e contador de seleção.
// Opera só sobre elementos do próprio render (sem listeners em document/window) → SPA-safe.
window.catFiltro = window.catFiltro || (function () {
    const box = (g) => document.getElementById(g + 'Box');
    const rows = (g) => box(g) ? Array.from(box(g).querySelectorAll('[data-row]')) : [];
    function contar(g) {
        const el = document.getElementById(g + 'Count');
        if (!el) return;
        const n = rows(g).filter((r) => r.querySelector('input[type=checkbox]')?.checked).length;
        el.textContent = n ? `${n} selecionado${n > 1 ? 's' : ''}` : '';
    }
    return {
        contar,
        buscar(g, q) {
            q = (q || '').toLowerCase().trim();
            rows(g).forEach((r) => { r.style.display = (!q || (r.dataset.search || '').includes(q)) ? '' : 'none'; });
        },
        marcar(g, val) {
            rows(g).forEach((r) => {
                if (r.style.display === 'none') return;
                const c = r.querySelector('input[type=checkbox]');
                if (c) c.checked = val;
            });
            contar(g);
        },
    };
})();
catFiltro.contar('cfop');
catFiltro.contar('cst');

(function() {
    if (window._cleanupFunctions && window._cleanupFunctions.notasFiscais) {
        window._cleanupFunctions.notasFiscais();
    }

    var cache = {};
    var filtrosToggle = document.getElementById('nf-filtros-toggle');
    var filtrosGrid = document.getElementById('nf-filtros-grid');
    var form = document.getElementById('nf-filtros-form');

    function handleFiltrosToggle() {
        var isHidden = filtrosGrid.classList.contains('hidden');
        if (isHidden) {
            filtrosGrid.classList.remove('hidden');
            filtrosToggle.querySelector('.nf-filtros-chevron').style.transform = 'rotate(180deg)';
        } else {
            filtrosGrid.classList.add('hidden');
            filtrosToggle.querySelector('.nf-filtros-chevron').style.transform = '';
        }
    }

    if (filtrosToggle) {
        filtrosToggle.addEventListener('click', handleFiltrosToggle);
    }

    function toggleDetail(origem, id, btnEl) {
        var key = origem + '-' + id;
        var icon = btnEl.querySelector('.nf-expand-icon');
        var detailRow = document.querySelector('tr[data-detail-for="' + key + '"]');
        var card = btnEl.closest('.nf-card');
        var mobileDetail = card ? card.querySelector('.nf-mobile-detail') : null;
        var target = mobileDetail || detailRow;

        if (!target) return;

        var isOpen = !target.classList.contains('hidden');

        if (isOpen) {
            target.classList.add('hidden');
            if (icon) icon.style.transform = '';
            return;
        }

        target.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';

        var contentEl = mobileDetail || (detailRow ? detailRow.querySelector('.nf-detail-content') : null);

        if (cache[key]) {
            contentEl.innerHTML = cache[key];
            return;
        }

        contentEl.innerHTML = '<div class="px-6 py-4 text-sm text-gray-500">Carregando...</div>';

        fetch('/app/notas/' + origem + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Nota-Detalhe': 'inline' }
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Erro ' + r.status);
            return r.text();
        })
        .then(function(html) {
            cache[key] = html;
            contentEl.innerHTML = html;
        })
        .catch(function() {
            contentEl.innerHTML = '<div class="px-6 py-4 text-sm text-gray-500">Erro ao carregar detalhes.</div>';
        });
    }

    function handleExpandClick(e) {
        if (e.target.closest('a, button[type="submit"], input, select, textarea, label')) {
            return;
        }

        var btn = e.target.closest('.nf-expand-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            toggleDetail(btn.dataset.origem, btn.dataset.id, btn);
            return;
        }

        var wrapper = e.target.closest('.nf-row, .nf-card');
        if (!wrapper) return;
        var chevron = wrapper.querySelector('.nf-expand-btn');
        if (!chevron) return;
        toggleDetail(chevron.dataset.origem, chevron.dataset.id, chevron);
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        var params = new URLSearchParams();
        var formData = new FormData(form);
        formData.forEach(function(value, key) {
            if (!value) return;
            // multi-select (cfops[]/csts[]) precisa de append, senão só o último sobrevive
            if (key.endsWith('[]')) { params.append(key, value); } else { params.set(key, value); }
        });
        var url = '/app/notas' + (params.toString() ? '?' + params.toString() : '');
        var link = document.createElement('a');
        link.href = url;
        link.setAttribute('data-link', '');
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Dropdowns CFOP/CST: abre/fecha o painel ancorado; fecha ao clicar fora ou em outro.
    function fecharPops(exceto) {
        document.querySelectorAll('[data-pop-panel]').forEach(function(p) {
            if (p === exceto) return;
            p.classList.add('hidden');
            var ch = p.parentElement.querySelector('[data-pop-chevron]');
            if (ch) ch.style.transform = '';
        });
    }

    function handlePopClick(e) {
        var toggle = e.target.closest('[data-pop-toggle]');
        if (toggle) {
            e.preventDefault();
            var panel = toggle.parentElement.querySelector('[data-pop-panel]');
            var aberto = !panel.classList.contains('hidden');
            fecharPops();
            if (!aberto) {
                panel.classList.remove('hidden');
                var ch = toggle.querySelector('[data-pop-chevron]');
                if (ch) ch.style.transform = 'rotate(180deg)';
            }
            return;
        }
        // clique fora de qualquer painel fecha tudo (cliques dentro do painel são preservados)
        if (!e.target.closest('[data-pop-panel]')) fecharPops();
    }

    document.addEventListener('click', handleExpandClick);
    document.addEventListener('click', handlePopClick);
    if (form) form.addEventListener('submit', handleFormSubmit);

    if (!window._cleanupFunctions) window._cleanupFunctions = {};
    window._cleanupFunctions.notasFiscais = function() {
        document.removeEventListener('click', handleExpandClick);
        document.removeEventListener('click', handlePopClick);
        if (form) form.removeEventListener('submit', handleFormSubmit);
        if (filtrosToggle) filtrosToggle.removeEventListener('click', handleFiltrosToggle);
    };
})();
</script>
