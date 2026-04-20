{{-- Indicadores --}}
@php
 $kpiPartNovos = $importacao->novos ?? ($resumoFinal['participantes']['novos'] ?? ($resumoFinal['estatisticas']['participantes_novos'] ?? 0));
 $kpiPartDupl = $importacao->duplicados ?? ($resumoFinal['participantes']['duplicados'] ?? ($resumoFinal['estatisticas']['participantes_repetidos'] ?? 0));
 $totalPart = $kpiPartNovos + $kpiPartDupl;
 if ($totalPart === 0 && isset($participantes) && $participantes->total() > 0) {
 $totalPart = $participantes->total();
 $kpiPartNovos = $totalPart;
 }
 $totalDocs = ($importacao->total_cnpjs_unicos ?? 0) + ($importacao->total_cpfs_unicos ?? 0);
 $notasTotal = $resumoFinal['totais']['notas'] ?? $importacao->notas_extraidas ?? 0;
 $notasValor = $resumoFinal['totais']['valor'] ?? null;
@endphp
<div class="bg-white rounded border border-gray-300 mb-6 overflow-hidden">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Indicadores</span>
 </div>
 <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
 <div class="p-4">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Participantes</p>
 <p class="text-lg font-bold text-gray-900">{{ number_format($totalPart) }}</p>
 <p class="text-[11px] text-gray-500">{{ number_format($kpiPartNovos) }} novos · {{ number_format($kpiPartDupl) }} duplicados</p>
 </div>
 <div class="p-4">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Documentos</p>
 <p class="text-lg font-bold text-gray-900">{{ number_format($totalDocs) }}</p>
 <p class="text-[11px] text-gray-500">{{ number_format($importacao->total_cnpjs_unicos ?? 0) }} CNPJs · {{ number_format($importacao->total_cpfs_unicos ?? 0) }} CPFs</p>
 </div>
 <div class="p-4">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas Fiscais</p>
 <p class="text-lg font-bold text-gray-900">{{ number_format($notasTotal) }}</p>
 <p class="text-[11px] text-gray-500">@if($notasValor)R$ {{ number_format($notasValor, 2, ',', '.') }}@else—@endif</p>
 </div>
 <div class="p-4">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Performance</p>
 <p class="text-lg font-bold text-gray-900">{{ $importacao->tempo_processamento ?? '0s' }}</p>
 <p class="text-[11px] text-gray-500">{{ $importacao->creditos_cobrados ?? 0 }} créditos cobrados</p>
 </div>
 </div>
</div>