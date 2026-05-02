@php
    $clientes = $clientes ?? collect();
    $filtros = $filtros ?? [];
    $topNcms = $topNcms ?? [];
    $cfopsPorNcm = $cfopsPorNcm ?? [];
    $dispersaoAliquota = $dispersaoAliquota ?? [];
    $itensSaidaSemCatalogo = $itensSaidaSemCatalogo ?? [];

    $temDados = ! empty($topNcms) || ! empty($cfopsPorNcm) || ! empty($dispersaoAliquota) || ! empty($itensSaidaSemCatalogo);

    $maxValorNcm = ! empty($topNcms) ? max(array_column($topNcms, 'valor_total')) : 0;
@endphp

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <a href="/app/bi/dashboard" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar para BI Fiscal
            </a>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Catálogo × Itens</h1>
            <p class="text-xs text-gray-500 mt-1">Cruzamentos entre catálogo (registro 0200) e itens declarados em notas (XML + EFD com dedup por chave).</p>
        </div>

        <form method="GET" action="/app/bi/catalogo-itens" class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Data início</label>
                    <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-gray-500">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Data fim</label>
                    <input type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-gray-500">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Cliente</label>
                    <select name="cliente_id" class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-gray-500">
                        <option value="">Todos</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ ($filtros['cliente_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->nome ?? $c->documento }}{{ $c->is_empresa_propria ? ' (própria)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-1.5 bg-gray-900 text-white rounded text-sm font-medium hover:bg-gray-700 transition-colors">Aplicar</button>
                    <a href="/app/bi/catalogo-itens" class="px-4 py-1.5 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium">Limpar</a>
                </div>
            </div>
        </form>

        @if(! $temDados)
            <div class="bg-white rounded border border-gray-300 p-6 text-center">
                <p class="text-sm text-gray-500">Nenhum dado de itens encontrado para o período/cliente selecionado. Importe XML/EFD com itens tipados ou rode <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">php artisan xml:backfill-itens</code>.</p>
            </div>
        @endif

        @if(! empty($topNcms))
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top NCMs por valor movimentado</span>
                    <span class="text-[10px] font-semibold text-gray-400">XML + EFD dedup</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($topNcms as $linha)
                        @php $pct = $maxValorNcm > 0 ? min(100, ($linha['valor_total'] / $maxValorNcm) * 100) : 0; @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between gap-4 mb-1.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-mono text-sm text-gray-900">{{ $linha['ncm'] }}</span>
                                    <span class="text-[11px] text-gray-500">· {{ $linha['total_itens'] }} {{ $linha['total_itens'] === 1 ? 'item' : 'itens' }}</span>
                                    <span class="text-[11px] text-gray-500">· {{ $linha['notas_distintas'] }} {{ $linha['notas_distintas'] === 1 ? 'nota' : 'notas' }}</span>
                                </div>
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <span class="text-sm font-mono font-semibold text-gray-900">R$ {{ number_format($linha['valor_total'], 2, ',', '.') }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">{{ number_format($linha['percentual'], 1, ',', '.') }}%</span>
                                </div>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background-color: #f3f4f6">
                                <div class="h-full" style="background-color: #4338ca; width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(! empty($cfopsPorNcm))
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">CFOPs por NCM — entrada × saída</span>
                    <span class="text-[10px] font-semibold text-gray-400">Top {{ count($cfopsPorNcm) }}</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($cfopsPorNcm as $bloco)
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-sm text-gray-900">{{ $bloco['ncm'] }}</span>
                                <span class="text-xs font-mono text-gray-700">R$ {{ number_format($bloco['valor_total'], 2, ',', '.') }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Entradas</p>
                                    @if(empty($bloco['entradas']))
                                        <p class="text-[11px] text-gray-400">Nenhuma</p>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($bloco['entradas'] as $cfop)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857" title="{{ $cfop['count'] }} itens · R$ {{ number_format($cfop['valor'], 2, ',', '.') }}">{{ $cfop['cfop'] }} <span class="opacity-80 font-normal">({{ $cfop['count'] }})</span></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saídas</p>
                                    @if(empty($bloco['saidas']))
                                        <p class="text-[11px] text-gray-400">Nenhuma</p>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($bloco['saidas'] as $cfop)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706" title="{{ $cfop['count'] }} itens · R$ {{ number_format($cfop['valor'], 2, ',', '.') }}">{{ $cfop['cfop'] }} <span class="opacity-80 font-normal">({{ $cfop['count'] }})</span></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(! empty($dispersaoAliquota))
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dispersão de alíquota ICMS por item</span>
                    <span class="text-[10px] font-semibold text-gray-400" title="Sinal de erro de tributação: mesmo código de produto está sendo declarado com alíquotas diferentes">Sinal de erro</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Mín.</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Máx.</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Δ (pp)</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Linhas</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dispersaoAliquota as $linha)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-3 py-2 font-mono text-sm text-gray-700">{{ $linha['codigo_item'] }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 max-w-xs truncate">{{ $linha['descricao'] }}</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">{{ number_format($linha['aliq_min'], 2, ',', '.') }}%</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">{{ number_format($linha['aliq_max'], 2, ',', '.') }}%</td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b45309">{{ number_format($linha['dispersao'], 2, ',', '.') }} pp</span>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">{{ $linha['total_linhas'] }}</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">R$ {{ number_format($linha['valor_total'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(! empty($itensSaidaSemCatalogo))
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Itens vendidos sem catálogo</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #dc2626" title="Auditoria fiscal urgente: itens emitidos em saída que não constam no cadastro 0200">Auditoria urgente</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
                                <th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Linhas</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Notas</th>
                                <th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($itensSaidaSemCatalogo as $linha)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-3 py-2 font-mono text-sm text-gray-700">{{ $linha['codigo_item'] }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 max-w-xs truncate">{{ $linha['descricao'] }}</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">{{ $linha['total_linhas'] }}</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right text-gray-700">{{ $linha['notas_distintas'] }}</td>
                                    <td class="px-3 py-2 font-mono text-sm text-right font-semibold text-gray-900">R$ {{ number_format($linha['valor_total'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
