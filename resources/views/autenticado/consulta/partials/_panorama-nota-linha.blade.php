{{-- Uma linha de nota no bloco "Maiores notas" do panorama fiscal. Espera $n com
     modelo/numero/serie/data/chave/valor. title da <tr> (no include) = chave quando houver. --}}
@php($modelos = ['55' => 'NF-e', '65' => 'NFC-e', '57' => 'CT-e', '67' => 'CT-e OS', '59' => 'SAT', '01' => 'NF', '1B' => 'NF'])
@php($modeloLabel = $modelos[$n['modelo'] ?? ''] ?? ('Mod ' . ($n['modelo'] ?? '—')))
<td class="py-0.5 pr-2 whitespace-nowrap">
    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #475569">{{ $modeloLabel }}</span>
</td>
<td class="py-0.5 pr-2 font-mono text-slate-700 whitespace-nowrap">{{ $n['numero'] ?? '—' }}@if(!empty($n['serie']))/{{ $n['serie'] }}@endif
@if(!empty($n['cfop']))<span class="block text-[9px] text-slate-400 font-sans">CFOP {{ $n['cfop'] }}</span>@endif</td>
<td class="py-0.5 pr-2 text-slate-500 whitespace-nowrap">{{ !empty($n['data']) ? \Carbon\Carbon::parse($n['data'])->format('d/m/Y') : '—' }}</td>
<td class="py-0.5 text-right font-mono text-slate-900 whitespace-nowrap">R$ {{ number_format((float) ($n['valor'] ?? 0), 2, ',', '.') }}</td>
