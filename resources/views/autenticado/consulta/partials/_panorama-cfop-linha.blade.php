{{-- Uma linha de CFOP do panorama fiscal. Espera: $c (cfop, descricao, qtd, valor). --}}
@php($cDesc = (string) ($c['descricao'] ?? $c['cfop']))
@php($cDesc = preg_replace('/^\d+\s*[—-]\s*/u', '', $cDesc))
<div class="flex items-center justify-between gap-2 text-[11px]">
    <span class="truncate text-gray-700" title="{{ $c['descricao'] ?? $c['cfop'] }}">
        <span class="font-mono text-gray-500">{{ $c['cfop'] }}</span>@if($cDesc !== '' && $cDesc !== (string) $c['cfop']) {{ $cDesc }}@endif
    </span>
    <span class="whitespace-nowrap font-mono text-gray-600">
        R$ {{ number_format($c['valor'] ?? 0, 2, ',', '.') }} <span class="text-gray-400">×{{ $c['qtd'] }}</span>
    </span>
</div>
