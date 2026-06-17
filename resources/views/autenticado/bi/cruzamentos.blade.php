@php
    $fmtMoeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
@endphp
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Cruzamentos — Consultas × Clearance</h1>
            <p class="text-xs text-gray-500 mt-0.5">Risco do fornecedor (regularidade e sanções das consultas de CNPJ) cruzado com o quanto você comprou dele nas notas.</p>
        </div>

        {{-- Diagnóstico de cobertura: explica quando o cruzamento aparece (e por que pode estar vazio) --}}
        <div class="bg-white rounded border border-gray-300 border-l-4 p-3 mb-5" style="border-left-color: #0b1f3a">
            <div class="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                <span class="inline-flex items-baseline gap-1.5 text-gray-700"><strong class="text-base text-gray-900">{{ number_format($diagnostico['consultados_qtd'], 0, ',', '.') }}</strong><span>CNPJs consultados</span></span>
                <span class="inline-flex items-baseline gap-1.5 text-gray-700"><strong class="text-base text-gray-900">{{ number_format($diagnostico['fornecedores_entrada_qtd'], 0, ',', '.') }}</strong><span>fornecedores nas notas de entrada</span></span>
                <span class="inline-flex items-baseline gap-1.5 text-gray-700"><strong class="text-base text-gray-900">{{ number_format($diagnostico['fornecedores_consultados_qtd'], 0, ',', '.') }}</strong><span>consultados que são fornecedores</span></span>
            </div>
            @if($diagnostico['fornecedores_consultados_qtd'] === 0)
                <p class="text-[12px] text-gray-500 mt-2">
                    Os cruzamentos aparecem quando um CNPJ que você <strong>consultou</strong> também é <strong>fornecedor</strong> nas suas notas de entrada. Hoje não há sobreposição — não é erro, é cobertura de dado.
                    @if($diagnostico['fornecedores_entrada_qtd'] > 0)
                        Para alimentar esta tela, consulte os CNPJs dos seus fornecedores em <a href="{{ route('app.consulta.nova') }}" data-link class="text-blue-600 hover:underline">Consulta CNPJ</a>.
                    @endif
                </p>
            @endif
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
            <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: #dc2626">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Fornecedores irregulares</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($resumo['irregulares_qtd'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: #dc2626">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Compras de irregulares</p>
                <p class="text-lg font-bold text-gray-900">{{ $fmtMoeda($resumo['irregulares_valor']) }}</p>
            </div>
            <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: #b45309">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Fornecedores sancionados</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($resumo['sancionados_qtd'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: #b45309">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Compras de sancionados</p>
                <p class="text-lg font-bold text-gray-900">{{ $fmtMoeda($resumo['sancionados_valor']) }}</p>
            </div>
            <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: #6b7280">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas canceladas (SEFAZ)</p>
                <p class="text-lg font-bold text-gray-900">{{ number_format($resumo['canceladas_qtd'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Filtros (padrão /app/clientes) --}}
        <form method="GET" class="bg-white rounded border border-gray-300 p-3 mb-4 flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1 sm:flex-none">
                <label class="block text-[11px] text-gray-500 mb-1">Cliente</label>
                <select name="cliente_id" class="w-full text-[13px] py-2.5 px-3 border border-gray-300 rounded">
                    <option value="">Todos</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" @selected(($filtros['cliente_id'] ?? null) == $c->id)>{{ $c->razao_social }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #0b1f3a">Aplicar filtro</button>
            @if(! empty($filtros['cliente_id']))
                <a href="{{ route('app.bi.cruzamentos') }}" data-link class="text-[12px] text-gray-500 hover:underline self-center">Limpar</a>
            @endif
        </form>

        {{-- 1. Fornecedor irregular × compras --}}
        <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #dc2626"></span>
                <h2 class="text-sm font-bold text-gray-900">Fornecedor com certidão/situação irregular × compras</h2>
            </div>
            @if($irregulares->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-500">Nenhum fornecedor irregular com compras no acervo. 👍</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="text-left px-4 py-2">Fornecedor</th>
                                <th class="text-left px-4 py-2">Motivo</th>
                                <th class="text-right px-4 py-2">Comprado</th>
                                <th class="text-right px-4 py-2">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($irregulares as $f)
                                <tr>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-gray-900">{{ $f['razao_social'] }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $f['documento'] }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @foreach($f['motivos'] as $m)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold text-white mb-0.5" style="background-color: #dc2626">{{ $m }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ $fmtMoeda($f['valor_comprado']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-600">{{ $f['qtd_notas'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 2. Fornecedor sancionado × compras --}}
        <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #b45309"></span>
                <h2 class="text-sm font-bold text-gray-900">Fornecedor sancionado (CEIS/CGU) × compras</h2>
            </div>
            @if($sancionados->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-500">Nenhum fornecedor sancionado com compras no acervo. 👍</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="text-left px-4 py-2">Fornecedor</th>
                                <th class="text-left px-4 py-2">Bases</th>
                                <th class="text-right px-4 py-2">Comprado</th>
                                <th class="text-right px-4 py-2">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($sancionados as $f)
                                <tr>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-gray-900">{{ $f['razao_social'] }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $f['documento'] }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @forelse($f['bases'] as $b)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold text-white mb-0.5" style="background-color: #b45309">{{ $b }}</span>
                                        @empty
                                            <span class="text-gray-400 text-[11px]">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ $fmtMoeda($f['valor_comprado']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-600">{{ $f['qtd_notas'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 3. Nota cancelada SEFAZ × emitente --}}
        <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #6b7280"></span>
                <h2 class="text-sm font-bold text-gray-900">Nota cancelada na SEFAZ × situação do emitente</h2>
            </div>
            @if($canceladas->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-500">Nenhuma nota cancelada na SEFAZ no acervo verificado. Este cruzamento depende do clearance de notas (verificação SEFAZ).</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                            <tr>
                                <th class="text-left px-4 py-2">Documento</th>
                                <th class="text-left px-4 py-2">Emitente</th>
                                <th class="text-left px-4 py-2">Situação do emitente</th>
                                <th class="text-right px-4 py-2">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($canceladas as $n)
                                <tr>
                                    <td class="px-4 py-2.5 text-[11px] text-gray-600">{{ $n['numero'] }}<br><span class="text-gray-400">{{ $n['chave_acesso'] }}</span></td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-gray-900">{{ $n['emit_nome'] }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $n['emit_cnpj'] }}</div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-700">{{ $n['situacao_emitente'] ?? 'Não consultado' }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-900">{{ $n['valor'] !== null ? $fmtMoeda($n['valor']) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>
