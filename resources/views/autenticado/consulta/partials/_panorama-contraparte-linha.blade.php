{{-- Células de uma linha de relacionamento/contraparte (dentro de <tr>).
     Espera: $rel, $papelHex, $papelLabel. --}}
@php($relNome = $rel['nome'] ?? $rel['empresa_nome'] ?? '—')
@php($relPropria = $rel['is_propria'] ?? $rel['is_empresa_propria'] ?? false)
<td class="py-1 pr-2 text-slate-700 align-top">
    <span title="{{ $relNome }}">{{ $relNome }}</span>@if($relPropria) <span class="text-slate-400">(própria)</span>@endif
</td>
<td class="py-1 pr-2 whitespace-nowrap align-top">
    <span style="color: {{ $papelHex[$rel['papel']] ?? '#374151' }}" class="font-semibold">{{ $papelLabel[$rel['papel']] ?? '—' }}</span>
</td>
<td class="py-1 text-right font-mono text-slate-600 whitespace-nowrap align-top">R$ {{ number_format(($rel['valor_entrada'] ?? 0) + ($rel['valor_saida'] ?? 0), 2, ',', '.') }}</td>
