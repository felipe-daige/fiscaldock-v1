{{-- Catálogo de Itens do lote XML (agregado de xml_notas_itens). --}}
@if(($catalogoItens ?? collect())->count() > 0)
<div class="bg-white rounded border border-gray-300 mb-4 overflow-hidden" id="catalogo-section">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Catálogo de Itens</span>
            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $catalogoItens->count() }}</span>
        </div>
        <span class="text-[10px] text-gray-400">por código de item</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-300">
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">NCM</th>
                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CFOP</th>
                    <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Qtd</th>
                    <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ocorr.</th>
                    <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($catalogoItens as $item)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-3 py-3 text-sm font-mono text-gray-900 whitespace-nowrap">{{ $item->codigo_item ?: '—' }}</td>
                    <td class="px-3 py-3 text-sm text-gray-900 max-w-[280px] truncate" title="{{ $item->descricao }}">{{ $item->descricao ?: '—' }}</td>
                    <td class="px-3 py-3 text-sm font-mono text-gray-700 whitespace-nowrap">
                        @if($item->ncm)
                            {{ $item->ncm }}
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">sem NCM</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-sm font-mono text-gray-700 whitespace-nowrap">{{ $item->cfop ?: '—' }}</td>
                    <td class="px-3 py-3 text-sm text-gray-700 font-mono text-right whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $item->quantidade, 4, ',', '.'), '0'), ',') ?: '0' }}</td>
                    <td class="px-3 py-3 text-sm text-gray-500 text-center whitespace-nowrap">{{ number_format($item->ocorrencias) }}</td>
                    <td class="px-3 py-3 text-sm text-gray-900 font-mono text-right whitespace-nowrap">R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($catalogoItens->count() >= 100)
    <div class="px-4 py-2 border-t border-gray-200">
        <p class="text-[11px] text-gray-500">Exibindo os 100 itens de maior valor.</p>
    </div>
    @endif
</div>
@endif
