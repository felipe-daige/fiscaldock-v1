@php
    $notas = $notas ?? collect();
    $clientes = $clientes ?? collect();
    $filtros = $filtros ?? [];
    $escopoNotas = $escopoNotas ?? [];
    $saldoAtual = (int) ($saldoAtual ?? 0);
    $custosTiers = $custosTiers ?? ['basico' => 10, 'full' => 20];
    $sort = $sort ?? 'data_emissao';
    $dir = $dir ?? 'desc';

    $buildSortUrl = function (string $col) use ($filtros, $sort, $dir) {
        $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
        $params = array_filter(array_merge($filtros, ['sort' => $col, 'dir' => $nextDir]), fn ($v) => $v !== null && $v !== '');
        return '/app/clearance/notas?'.http_build_query($params);
    };

    $sortArrow = function (string $col) use ($sort, $dir) {
        if ($sort !== $col) {
            return '<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>';
        }
        return $dir === 'asc'
            ? '<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>'
            : '<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>';
    };

    $sortCustom = $sort !== 'data_emissao' || $dir !== 'desc';
    $resetSortUrl = '/app/clearance/notas?'.http_build_query(array_filter($filtros, fn ($v) => $v !== null && $v !== ''));
    $sortLabels = [
        'origem' => 'Origem',
        'numero' => 'Nota',
        'data_emissao' => 'Emissão',
        'emit_razao_social' => 'Emitente',
        'dest_razao_social' => 'Destinatário',
        'valor_total' => 'Valor',
        'tipo_nota' => 'Tipo',
        'modelo' => 'Modelo',
        'status' => 'Status',
    ];

    $statusOptions = [
        'todos' => 'Todos',
        'nao_validadas' => 'Não validadas',
        'validadas' => 'Validadas',
        'com_alertas' => 'Com alertas bloqueantes',
    ];

    $statusBadge = function ($nota) {
        if (is_null($nota->validacao)) {
            return ['label' => 'Não validada', 'hex' => '#6b7280'];
        }
        $alertas = $nota->validacao['alertas'] ?? [];
        foreach ($alertas as $a) {
            if (($a['nivel'] ?? null) === 'bloqueante') {
                return ['label' => 'Bloqueante', 'hex' => '#b91c1c'];
            }
        }
        foreach ($alertas as $a) {
            if (($a['nivel'] ?? null) === 'atencao') {
                return ['label' => 'Atenção', 'hex' => '#d97706'];
            }
        }
        return ['label' => 'Validada', 'hex' => '#047857'];
    };
@endphp

<div class="min-h-screen bg-gray-100" id="validacao-notas-container"
    data-ids-url="{{ route('app.clearance.todos-ids') }}"
    data-validar-url="{{ route('app.clearance.validar') }}"
    data-tem-mais-pagina="{{ $notas->lastPage() > 1 ? '1' : '0' }}"
    data-saldo-atual="{{ $saldoAtual }}"
    data-custo-basico="{{ $custosTiers['basico'] }}"
    data-custo-full="{{ $custosTiers['full'] }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Verificar Notas</h1>
                <p class="text-xs text-gray-500 mt-1">Selecione notas (XML ou EFD) e dispare a validação contábil em lote.</p>
            </div>
            <a href="/app/clearance/dashboard" data-link class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-sm font-medium self-start">
                Voltar ao Painel
            </a>
        </div>

        <details class="bg-white rounded border border-gray-300 border-l-4 mb-4 group" style="border-left-color: #2563eb;">
            <summary class="cursor-pointer px-4 py-3 flex items-center justify-between list-none hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900">Como funciona a verificação de notas</span>
                </div>
                <span class="text-[11px] font-semibold text-gray-500 group-open:hidden">Abrir</span>
                <span class="text-[11px] font-semibold text-gray-500 hidden group-open:inline">Fechar</span>
            </summary>

            <div class="border-t border-gray-200">
                <div class="px-4 py-4">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Fluxo em 3 etapas</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">1</span>
                            <p class="text-sm font-semibold text-gray-900">Seleção</p>
                            <p class="text-xs text-gray-600 mt-0.5">Você escolhe notas por filtro, importação ou seleção manual — e confirma o tier de validação.</p>
                        </div>
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">2</span>
                            <p class="text-sm font-semibold text-gray-900">Consulta oficial</p>
                            <p class="text-xs text-gray-600 mt-0.5">O FiscalDock envia as chaves ao n8n, que consulta a Receita Federal via InfoSimples e normaliza o retorno.</p>
                        </div>
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">3</span>
                            <p class="text-sm font-semibold text-gray-900">Resultado</p>
                            <p class="text-xs text-gray-600 mt-0.5">Você recebe a situação real, eventos de correção/cancelamento e o valor confrontado com a fonte.</p>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Matriz de suporte</p>
                        <span class="text-[10px] font-semibold text-gray-400">Quem pode ser verificado hoje</span>
                    </div>
                    <div class="overflow-x-auto border border-gray-200 rounded">
                        <table class="w-full text-xs">
                            <thead style="background-color: #f9fafb;">
                                <tr class="text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="py-2 px-3">Documento</th>
                                    <th class="py-2 px-3">Modelo</th>
                                    <th class="py-2 px-3">Chave</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3">Observação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NF-e</td>
                                    <td class="py-2 px-3">55</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Fonte nacional unificada.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NFC-e</td>
                                    <td class="py-2 px-3">65</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Mesma base da NF-e.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">CT-e / CT-e OS</td>
                                    <td class="py-2 px-3">57 / 67</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Transportes e serviços de transporte.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NFS-e</td>
                                    <td class="py-2 px-3">—</td>
                                    <td class="py-2 px-3">Municipal / 50 dig. nacional</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #6b7280;">Fora de escopo</span></td>
                                    <td class="py-2 px-3 text-gray-500">Sem fonte nacional unificada — ver nota abaixo.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 rounded border border-gray-200 p-3" style="background-color: #fffbeb;">
                        <p class="text-[11px] font-semibold text-gray-700 mb-1">Por que NFS-e fica de fora?</p>
                        <p class="text-xs text-gray-600 leading-relaxed">Cada município emite NFS-e com código de verificação próprio (normalmente 8–9 caracteres alfanuméricos) e expõe APIs diferentes. A única fonte nacional disponível hoje exige <strong>NFS-e Nacional</strong> (chave de 50 dígitos), padrão que a maioria das prefeituras ainda não adotou. Por isso o Clearance não lista NFS-e nesta tela — elas continuam visíveis em Notas Fiscais e no Dashboard, sem cruzamento externo.</p>
                    </div>
                </div>

                <div class="px-4 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Como é cobrado</p>
                        <span class="text-[10px] font-semibold text-gray-400">Por nota verificada</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="border border-gray-300 rounded p-3">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-gray-900">Básico</p>
                                <span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #6b7280;">Essencial</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">{{ $custosTiers['basico'] }} <span class="text-xs font-medium text-gray-500">créditos/nota</span></p>
                            <p class="text-[11px] text-gray-500 mt-1">Situação oficial + eventos de cancelamento.</p>
                        </div>
                        <div class="border border-gray-300 rounded p-3">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-gray-900">Full</p>
                                <span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #2563eb;">Completo</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">{{ $custosTiers['full'] }} <span class="text-xs font-medium text-gray-500">créditos/nota</span></p>
                            <p class="text-[11px] text-gray-500 mt-1">Situação + eventos + confronto de valores e alertas contábeis.</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-3">A cobrança acontece na hora da confirmação. <strong>Falhas do provedor estornam os créditos</strong> automaticamente.</p>
                </div>
            </div>
        </details>

        <div id="clearance-notas-error" class="mb-4"></div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">XML + EFD unificadas por chave</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas XML</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($escopoNotas['total_xml'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Importadas pelo usuário</p>
                </div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas EFD</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($escopoNotas['total_efd'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Extraídas do SPED</p>
                </div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Base Unificada</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($escopoNotas['total_unificado'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Deduplicadas por chave</p>
                </div>
                <div class="px-4 py-4" style="background-color: #ecfdf5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: #047857">Créditos</p>
                        <span class="text-[9px] font-bold uppercase tracking-wide text-white px-1.5 py-0.5 rounded" style="background-color: #047857">Saldo</span>
                    </div>
                    <p class="text-xl font-bold mt-0.5" style="color: #047857">{{ number_format($saldoAtual, 0, ',', '.') }}</p>
                    <p class="text-[11px] mt-1" style="color: #065f46">Disponível para validações</p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="/app/clearance/notas" class="bg-white rounded border border-gray-300 overflow-hidden mb-4" id="validacao-filtros-form">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">De</label>
                    <input type="date" name="periodo_de" value="{{ $filtros['periodo_de'] ?? '' }}" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Até</label>
                    <input type="date" name="periodo_ate" value="{{ $filtros['periodo_ate'] ?? '' }}" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Cliente</label>
                    <select name="cliente_id" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                        <option value="">Todos</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ ($filtros['cliente_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">CNPJ Participante</label>
                    <input type="text" name="participante_cnpj" value="{{ $filtros['participante_cnpj'] ?? '' }}" placeholder="00.000.000/0000-00" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Tipo</label>
                    <select name="tipo_nota" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                        <option value="">Todos</option>
                        <option value="entrada" {{ ($filtros['tipo_nota'] ?? '') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                        <option value="saida" {{ ($filtros['tipo_nota'] ?? '') === 'saida' ? 'selected' : '' }}>Saída</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Status</label>
                    <select name="status_validacao" class="mt-1 w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ ($filtros['status_validacao'] ?? 'todos') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 flex gap-2">
                <button type="submit" class="px-3 py-1.5 rounded text-[11px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Aplicar</button>
                <a href="/app/clearance/notas" data-link class="px-3 py-1.5 rounded text-[11px] font-bold uppercase tracking-wide border border-gray-300 text-gray-700">Limpar</a>
            </div>
        </form>

        {{-- Status da seleção --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 text-sm text-gray-700">
                    <button type="button" id="btn-selecionar-todas" class="px-3 py-1.5 rounded text-[11px] font-bold uppercase tracking-wide border border-gray-300 text-gray-700{{ $notas->lastPage() > 1 ? '' : ' hidden' }}">Selecionar Todos ({{ number_format($notas->total(), 0, ',', '.') }})</button>
                    <span id="selecao-label">Nenhuma nota selecionada</span>
                </div>
                <div class="flex items-center gap-3">
                    @if ($sortCustom)
                        <a href="{{ $resetSortUrl }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 text-[10px] font-semibold text-gray-500 hover:text-gray-900 uppercase tracking-wide" title="Limpar ordenação">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Limpar ordem · {{ $sortLabels[$sort] ?? $sort }}
                            @if ($dir === 'asc')
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            @endif
                        </a>
                    @endif
                    <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded uppercase tracking-wide">{{ number_format($notas->total(), 0, ',', '.') }} resultado(s)</span>
                </div>
            </div>
        </div>

        {{-- Plan cards + CTA (escondido sem seleção) --}}
        <div id="clearance-planos" class="mb-4 hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div id="plan-card-basico" class="bg-white rounded border p-4 cursor-pointer transition" role="radio" aria-checked="true" tabindex="0" data-tier="basico" style="border-color: #1f2937">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Clearance</p>
                            <p class="text-base font-bold text-gray-900">Básico</p>
                        </div>
                        <span class="plan-chip px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #1f2937">Selecionado</span>
                    </div>
                    <ul class="space-y-1.5 text-xs text-gray-700 mb-3">
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Status SEFAZ (NF-e)</li>
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Validação contábil local</li>
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Cruzamento com EFD</li>
                    </ul>
                    <div class="border-t border-gray-200 pt-3 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded uppercase tracking-wide">{{ $custosTiers['basico'] }} créditos / nota</span>
                        <span class="flex items-center gap-1.5">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</span>
                            <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded uppercase tracking-wide" style="background-color: #374151"><span class="plan-total" data-tier="basico">0</span> créditos</span>
                        </span>
                    </div>
                </div>
                <div id="plan-card-full" class="bg-white rounded border p-4 cursor-pointer transition" role="radio" aria-checked="false" tabindex="0" data-tier="full" style="border-color: #e5e7eb">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Clearance</p>
                            <p class="text-base font-bold text-gray-900">Full</p>
                        </div>
                        <span class="plan-chip hidden px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #1f2937">Selecionado</span>
                    </div>
                    <ul class="space-y-1.5 text-xs text-gray-700 mb-3">
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Tudo do Básico</li>
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>CND Federal do emitente na data</li>
                        <li class="flex items-start gap-2"><svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>CNDT do emitente na data</li>
                    </ul>
                    <div class="border-t border-gray-200 pt-3 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded uppercase tracking-wide">{{ $custosTiers['full'] }} créditos / nota</span>
                        <span class="flex items-center gap-1.5">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</span>
                            <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded uppercase tracking-wide" style="background-color: #374151"><span class="plan-total" data-tier="full">0</span> créditos</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white rounded border border-gray-300 px-4 py-3">
                <span class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo após</span>
                    <span id="saldo-apos-label" class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded">—</span>
                </span>
                <button type="button" id="btn-validar" class="px-4 py-2 rounded text-[11px] font-bold uppercase tracking-wide text-white disabled:opacity-40" style="background-color: #047857" disabled>Validar</button>
            </div>
        </div>

        {{-- Progresso SSE do clearance externo --}}
        <div id="clearance-progresso" class="mb-4 hidden bg-white rounded border border-gray-300 px-4 py-3">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Clearance externo em andamento</p>
                <p id="clearance-progresso-percent" class="text-[10px] text-gray-500 font-mono">0%</p>
            </div>
            <div style="width: 100%; height: 6px; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden">
                <div id="clearance-progresso-bar" style="height: 100%; background-color: #1f2937; width: 8%; transition: width 350ms ease-out"></div>
            </div>
            <p id="clearance-progresso-etapa" class="text-xs text-gray-600 mt-2">Iniciando clearance...</p>
        </div>

        {{-- Tabela --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden relative" id="clearance-listagem-card" data-spa-list>
            <div id="clearance-sort-loading" class="hidden absolute inset-0 z-10 bg-white/75 backdrop-blur-[1px] flex items-center justify-center pointer-events-none">
                <div class="bg-white rounded border border-gray-300 shadow-sm px-4 py-3 flex items-center gap-3">
                    <svg class="w-4 h-4 animate-spin text-gray-700" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ordenação</p>
                        <p class="text-sm text-gray-700">Ordenando notas...</p>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left"><input type="checkbox" id="chk-master" class="w-4 h-4"></th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('origem') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Origem {!! $sortArrow('origem') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('numero') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Nota {!! $sortArrow('numero') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('data_emissao') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Emissão {!! $sortArrow('data_emissao') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('emit_razao_social') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Emitente {!! $sortArrow('emit_razao_social') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('dest_razao_social') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Destinatário {!! $sortArrow('dest_razao_social') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('valor_total') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700 justify-end w-full">Valor {!! $sortArrow('valor_total') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('modelo') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Modelo {!! $sortArrow('modelo') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('tipo_nota') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Tipo {!! $sortArrow('tipo_nota') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                <a href="{{ $buildSortUrl('status') }}" data-link data-clearance-preserve-scroll class="inline-flex items-center gap-1 hover:text-gray-700">Status {!! $sortArrow('status') !!}</a>
                            </th>
                            <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="tbody-notas">
                        @forelse($notas as $n)
                            @php
                                $s = $statusBadge($n);
                                $isXml = $n->origem === 'xml';
                                $origemHex = $isXml ? '#374151' : '#9ca3af';
                                $dataEmissao = $n->data_emissao ? \Illuminate\Support\Carbon::parse($n->data_emissao) : null;
                                $detalheUrl = $isXml
                                    ? "/app/clearance/nota/{$n->id}"
                                    : "/app/notas?chave={$n->chave}";
                                $modeloLabel = $n->modelo_label ?? 'N/D';
                                $modeloHex = $n->modelo_hex ?? '#9ca3af';
                                $participanteCnpjFmt = null;
                                if (! empty($n->participante_cnpj)) {
                                    $raw = preg_replace('/\D/', '', (string) $n->participante_cnpj);
                                    if (strlen($raw) === 14) {
                                        $participanteCnpjFmt = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $raw);
                                    } elseif (strlen($raw) === 11) {
                                        $participanteCnpjFmt = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $raw);
                                    } else {
                                        $participanteCnpjFmt = $raw;
                                    }
                                }
                                $participanteRazao = $n->tipo_nota === 'entrada' ? $n->emit_razao_social : $n->dest_razao_social;
                                $motivo = null;
                                if (is_array($n->validacao ?? null)) {
                                    $alertas = $n->validacao['alertas'] ?? [];
                                    foreach ($alertas as $a) {
                                        if (in_array($a['nivel'] ?? null, ['bloqueante', 'atencao'], true)) {
                                            $motivo = $a['mensagem'] ?? $a['codigo'] ?? null;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <tr data-nota-id="{{ $n->id }}" data-origem="{{ $n->origem }}" class="hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="w-4 h-4 chk-nota" value="{{ $n->id }}">
                                </td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemHex }}">
                                        {{ strtoupper($n->origem) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $n->numero }}/{{ $n->serie }}</td>
                                <td class="px-3 py-2 text-xs">{{ $dataEmissao?->format('d/m/Y') }}</td>
                                <td class="px-3 py-2 text-xs text-gray-700 truncate max-w-[180px]">{{ $n->emit_razao_social }}</td>
                                <td class="px-3 py-2 text-xs text-gray-700 truncate max-w-[180px]">{{ $n->dest_razao_social }}</td>
                                <td class="px-3 py-2 text-xs text-right font-mono">R$ {{ number_format((float) $n->valor_total, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $modeloHex }}">
                                        {{ $modeloLabel }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $n->tipo_nota === 'entrada' ? '#047857' : '#d97706' }}">
                                        {{ ucfirst($n->tipo_nota) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white td-status" style="background-color: {{ $s['hex'] }}">{{ $s['label'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" class="nota-details-toggle inline-flex items-center gap-1 text-xs text-gray-600 hover:text-gray-900" data-nota-id="{{ $n->id }}" aria-expanded="false">
                                        <span>Detalhes</span>
                                        <svg class="w-3.5 h-3.5 nota-details-chevron transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr class="nota-expand-row hidden" data-expand-for="{{ $n->id }}">
                                <td colspan="11" class="px-4 py-3 bg-gray-50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div class="rounded border border-gray-200 bg-white px-3 py-2.5">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Participante</p>
                                            <p class="text-xs text-gray-900 mt-0.5">{{ $participanteRazao ?? '—' }}</p>
                                            <p class="text-[11px] font-mono text-gray-600 mt-0.5">{{ $participanteCnpjFmt ?? '—' }}</p>
                                            @if (! empty($n->situacao_cadastral))
                                                <p class="text-[10px] text-gray-500 mt-0.5">Situação: {{ $n->situacao_cadastral }}</p>
                                            @endif
                                        </div>
                                        <div class="rounded border border-gray-200 bg-white px-3 py-2.5">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Datas</p>
                                            <p class="text-xs text-gray-900 mt-0.5">Emissão: {{ $dataEmissao?->format('d/m/Y') ?? '—' }}</p>
                                        </div>
                                        <div class="rounded border border-gray-200 bg-white px-3 py-2.5 md:col-span-2">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Chave de acesso</p>
                                            <p class="text-[11px] font-mono text-gray-900 mt-0.5 break-all">{{ $n->chave ?? '—' }}</p>
                                        </div>
                                        <div class="rounded border border-gray-200 bg-white px-3 py-2.5">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Status clearance</p>
                                            <p class="mt-1">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $s['hex'] }}">{{ $s['label'] }}</span>
                                            </p>
                                            @if ($motivo)
                                                <p class="text-[11px] text-gray-600 mt-1">{{ $motivo }}</p>
                                            @endif
                                        </div>
                                        <div class="rounded border border-gray-200 bg-white px-3 py-2.5">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Valores</p>
                                            <div class="mt-1 text-[11px] leading-tight space-y-1">
                                                <div class="flex items-baseline gap-1.5">
                                                    <span class="text-gray-500">Total:</span>
                                                    <span class="font-mono font-semibold text-gray-900">R$ {{ number_format((float) $n->valor_total, 2, ',', '.') }}</span>
                                                </div>
                                                <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 pt-1 border-t border-gray-100">
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span class="text-gray-500">ICMS:</span>
                                                        <span class="font-mono text-gray-900">{{ isset($n->icms_valor) ? 'R$ '.number_format((float) $n->icms_valor, 2, ',', '.') : '—' }}</span>
                                                    </div>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span class="text-gray-500">PIS:</span>
                                                        <span class="font-mono text-gray-900">{{ isset($n->pis_valor) ? 'R$ '.number_format((float) $n->pis_valor, 2, ',', '.') : '—' }}</span>
                                                    </div>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span class="text-gray-500">IPI:</span>
                                                        <span class="font-mono text-gray-900">{{ isset($n->ipi_valor) ? 'R$ '.number_format((float) $n->ipi_valor, 2, ',', '.') : '—' }}</span>
                                                    </div>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span class="text-gray-500">COFINS:</span>
                                                        <span class="font-mono text-gray-900">{{ isset($n->cofins_valor) ? 'R$ '.number_format((float) $n->cofins_valor, 2, ',', '.') : '—' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2 flex justify-end">
                                            <a href="{{ $detalheUrl }}" data-link class="text-xs font-medium text-gray-700 hover:text-gray-900 hover:underline">Ver nota completa →</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-8 text-center">
                                    <p class="text-sm text-gray-500">Nenhuma nota encontrada com os filtros atuais.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $notas->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar Clearance --}}
<div id="modal-confirmar-validacao" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 shadow-lg max-w-md w-full">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Confirmar Clearance</span>
            <span id="modal-confirm-tier-chip" class="text-[9px] font-bold uppercase tracking-wide text-white px-2 py-0.5 rounded" style="background-color: #1f2937">Básico</span>
        </div>
        <div class="p-5 space-y-4">
            <p class="text-sm text-gray-700">
                Será executada a validação <strong id="modal-confirm-tier-label">Clearance Básico</strong>
                em <strong id="modal-confirm-qtd">0</strong> nota(s).
            </p>
            <div class="grid grid-cols-2 divide-x divide-gray-200 border border-gray-200 rounded overflow-hidden">
                <div class="px-3 py-3">
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Custo total</p>
                    <p class="text-lg font-bold text-gray-900 mt-0.5"><span id="modal-confirm-custo">0</span> créditos</p>
                </div>
                <div class="px-3 py-3">
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Saldo após</p>
                    <p class="text-lg font-bold mt-0.5" id="modal-confirm-saldo-apos">0 créditos</p>
                </div>
            </div>
        </div>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-2">
            <button type="button" id="modal-confirm-cancelar" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
            <button type="button" id="modal-confirm-ok" class="px-4 py-2 text-xs font-semibold text-white rounded" style="background-color: #1f2937">Confirmar validação</button>
        </div>
    </div>
</div>

{{-- Modal: Sucesso --}}
<div id="modal-sucesso-validacao" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 shadow-lg max-w-md w-full">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Validação concluída</span>
            <span class="text-[9px] font-bold uppercase tracking-wide text-white px-2 py-0.5 rounded" style="background-color: #047857">OK</span>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" stroke="#047857" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-gray-700">Todas as notas selecionadas foram processadas.</p>
            </div>
            <div class="border border-gray-200 rounded px-3 py-3" style="background-color: #ecfdf5">
                <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: #047857">Créditos debitados</p>
                <p class="text-lg font-bold mt-0.5" style="color: #047857"><span id="modal-sucesso-creditos">0</span> créditos</p>
            </div>
        </div>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-end">
            <button type="button" id="modal-sucesso-ok" class="px-4 py-2 text-xs font-semibold text-white rounded" style="background-color: #1f2937">OK</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/clearance-notas.js') }}?v={{ @filemtime(public_path('js/clearance-notas.js')) ?: time() }}" defer></script>
