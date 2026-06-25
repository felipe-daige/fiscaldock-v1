{{-- Células de uma linha de CFOP (dentro de <tr>). Espera: $c (cfop, descricao, qtd, valor). --}}
@php($cDesc = (string) ($c['descricao'] ?? $c['cfop']))
@php($cDesc = preg_replace('/^\d+\s*[—-]\s*/u', '', $cDesc))
<td class="py-1 pr-2 font-mono text-slate-500 whitespace-nowrap align-top">{{ $c['cfop'] }}</td>
<td class="py-1 pr-2 text-slate-700 align-top">@if($cDesc !== '' && $cDesc !== (string) $c['cfop']){{ $cDesc }}@endif</td>
<td class="py-1 text-right font-mono text-slate-600 whitespace-nowrap align-top">R$ {{ number_format($c['valor'] ?? 0, 2, ',', '.') }}</td>
<td class="py-1 text-right font-mono text-slate-400 whitespace-nowrap pl-2 align-top">×{{ $c['qtd'] }}</td>
