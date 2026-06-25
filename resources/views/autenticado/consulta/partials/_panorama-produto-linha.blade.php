{{-- Células de uma linha de produto do panorama fiscal (dentro de <tr>).
     Espera: $p (cod_item, descricao, ncm, valor, qtd). --}}
<td class="py-1 pr-2 text-slate-700 align-top">
    <span title="{{ $p['descricao'] }}">{{ $p['descricao'] }}</span>
    @if(!empty($p['ncm'])) <span class="font-mono text-[10px] text-slate-400">NCM {{ $p['ncm'] }}</span>@endif
</td>
<td class="py-1 text-right font-mono text-slate-600 whitespace-nowrap align-top">R$ {{ number_format($p['valor'], 2, ',', '.') }}</td>
<td class="py-1 text-right font-mono text-slate-400 whitespace-nowrap pl-2 align-top">×{{ $p['qtd'] }}</td>
