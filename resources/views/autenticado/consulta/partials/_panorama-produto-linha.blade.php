{{-- Uma linha de produto do panorama fiscal. Espera: $p (cod_item, descricao, ncm, valor, qtd). --}}
<div class="flex items-center justify-between gap-2 text-[11px]">
    <span class="truncate text-gray-700" title="{{ $p['descricao'] }}">
        {{ $p['descricao'] }}@if(!empty($p['ncm'])) <span class="font-mono text-gray-400">NCM {{ $p['ncm'] }}</span>@endif
    </span>
    <span class="whitespace-nowrap font-mono text-gray-600">
        R$ {{ number_format($p['valor'], 2, ',', '.') }} <span class="text-gray-400">×{{ $p['qtd'] }}</span>
    </span>
</div>
