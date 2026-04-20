@php
    $tipoBadge = $nota->tipo_operacao === 'entrada'
        ? ['label' => 'ENTRADA', 'hex' => '#047857']
        : ['label' => 'SAÍDA', 'hex' => '#d97706'];

    $origemBadge = match($nota->origem_arquivo ?? '') {
        'fiscal' => ['label' => 'EFD ICMS/IPI', 'hex' => '#4338ca'],
        'contribuicoes' => ['label' => 'EFD PIS/COFINS', 'hex' => '#0f766e'],
        default => null,
    };

    $totalIcms = $nota->itens->sum('valor_icms');
    $totalPis = $nota->itens->sum('valor_pis');
    $totalCofins = $nota->itens->sum('valor_cofins');
    $totalItensValor = $nota->itens->sum('valor_total');
@endphp

<div class="px-3 py-3 sm:px-6 sm:py-4 space-y-4">
    <div class="bg-white rounded border border-gray-300 overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados da Nota</span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Data de Emissão</p>
                    <p class="text-sm text-gray-700">{{ $nota->data_emissao ? \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Valor Total</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ $nota->valor_total !== null ? number_format($nota->valor_total, 2, ',', '.') : '—' }}</p>
                </div>
                @if($nota->valor_desconto)
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Desconto</p>
                        <p class="text-sm font-mono text-gray-900">R$ {{ number_format($nota->valor_desconto, 2, ',', '.') }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Classificação</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                        @if($origemBadge)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($nota->chave_acesso)
                <div class="mt-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Chave de Acesso</p>
                    <p class="text-xs font-mono text-gray-700 break-all">{{ implode(' ', str_split($nota->chave_acesso, 4)) }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($nota->participante)
        @php
            $p = $nota->participante;
            $situacaoBadge = null;
            if ($p->situacao_cadastral) {
                $situacaoBadge = strtolower((string) $p->situacao_cadastral) === 'ativa'
                    ? ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#047857']
                    : ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#dc2626'];
            }
        @endphp
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participante</span>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">
                            <a href="/app/participante/{{ $p->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $p->razao_social ?? '—' }}</a>
                        </p>
                        @if($p->nome_fantasia)
                            <p class="text-[11px] text-gray-500 mt-1">{{ $p->nome_fantasia }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($situacaoBadge)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $situacaoBadge['hex'] }}">{{ $situacaoBadge['label'] }}</span>
                        @endif
                        @if($p->regime_tributario)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">{{ strtoupper($p->regime_tributario) }}</span>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJ / CPF</p>
                        <p class="text-sm font-mono text-gray-700">{{ $p->cnpj_formatado ?? '—' }}</p>
                    </div>
                    @if($p->uf)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">UF</p>
                            <p class="text-sm text-gray-700">{{ $p->uf }}</p>
                        </div>
                    @endif
                    @if($p->municipio)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Município</p>
                            <p class="text-sm text-gray-700">{{ $p->municipio }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($nota->cliente)
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cliente</span>
            </div>
            <div class="p-4">
                <p class="text-sm font-semibold text-gray-900">
                    <a href="/app/cliente/{{ $nota->cliente->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $nota->cliente->razao_social ?? '—' }}</a>
                </p>
                @if($nota->cliente->documento_formatado)
                    <p class="text-[11px] font-mono text-gray-500 mt-1">{{ $nota->cliente->documento_formatado }}</p>
                @endif
            </div>
        </div>
    @endif

    @if($nota->itens->isNotEmpty())
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Itens</span>
            </div>

            <div class="md:hidden divide-y divide-gray-100">
                @foreach($nota->itens as $item)
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-900 mb-2">{{ $item->descricao ?? '—' }}</p>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-gray-500">
                            <span>Qtd: <span class="text-gray-700">{{ $item->quantidade !== null ? number_format($item->quantidade, 2, ',', '.') : '—' }} {{ $item->unidade_medida ?? '' }}</span></span>
                            <span class="text-right">CFOP: <span class="font-mono text-gray-700">{{ $item->cfop ?? '—' }}</span></span>
                            <span>Unit.: <span class="text-gray-700">{{ $item->valor_unitario !== null ? 'R$ ' . number_format($item->valor_unitario, 2, ',', '.') : '—' }}</span></span>
                            <span class="text-right">Total: <span class="font-mono text-gray-900">{{ $item->valor_total !== null ? number_format($item->valor_total, 2, ',', '.') : '—' }}</span></span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Nº</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Qtd</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UN</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Vlr Unit.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Vlr Total</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CFOP</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CST ICMS</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">ICMS</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">PIS</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">COFINS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($nota->itens as $item)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-3 py-2 text-sm text-gray-700">{{ $item->numero_item ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $item->codigo_item ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700 max-w-xs truncate">{{ $item->descricao ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-700">{{ $item->quantidade !== null ? number_format($item->quantidade, 2, ',', '.') : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-700">{{ $item->unidade_medida ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_unitario !== null ? number_format($item->valor_unitario, 2, ',', '.') : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-900">{{ $item->valor_total !== null ? number_format($item->valor_total, 2, ',', '.') : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-center font-mono text-gray-700">{{ $item->cfop ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-center text-gray-700">{{ $item->cst_icms ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_icms !== null ? number_format($item->valor_icms, 2, ',', '.') : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_pis !== null ? number_format($item->valor_pis, 2, ',', '.') : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_cofins !== null ? number_format($item->valor_cofins, 2, ',', '.') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-300 bg-gray-50">
                            <td class="px-3 py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide" colspan="6">Total</td>
                            <td class="px-3 py-2 text-sm text-right font-mono font-semibold text-gray-900">{{ number_format($totalItensValor, 2, ',', '.') }}</td>
                            <td class="px-3 py-2"></td>
                            <td class="px-3 py-2"></td>
                            <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalIcms, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalPis, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalCofins, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded border border-gray-300 p-4">
            <p class="text-sm text-gray-500">Nenhum item registrado para esta nota.</p>
        </div>
    @endif

    <div class="flex justify-end pt-2 border-t border-gray-200">
        <a href="/app/notas-fiscais/efd/{{ $nota->id }}" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 hover:text-gray-900 hover:underline">
            Ver detalhes completos
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
