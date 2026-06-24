{{-- Uma linha de relacionamento/contraparte do panorama fiscal.
     Espera: $rel, $papelHex, $papelLabel. --}}
@php($relNome = $rel['nome'] ?? $rel['empresa_nome'] ?? '—')
@php($relPropria = $rel['is_propria'] ?? $rel['is_empresa_propria'] ?? false)
<div class="flex items-center justify-between gap-2 text-[11px]">
    <span class="truncate text-gray-700" title="{{ $relNome }}">
        {{ $relNome }}@if($relPropria) <span class="text-gray-400">(própria)</span>@endif
    </span>
    <span class="whitespace-nowrap">
        <span style="color: {{ $papelHex[$rel['papel']] ?? '#374151' }}" class="font-semibold">{{ $papelLabel[$rel['papel']] ?? '—' }}</span>
        · <span class="font-mono">R$ {{ number_format(($rel['valor_entrada'] ?? 0) + ($rel['valor_saida'] ?? 0), 2, ',', '.') }}</span>
    </span>
</div>
