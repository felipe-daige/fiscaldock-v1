{{-- Resumo Final de Notas EFD --}}
@if(!empty($resumoFinal))
<div class="bg-white rounded border border-gray-300 mt-6" id="resumo-final-section">

 {{-- Mini-painel de totais --}}
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo de Notas Importadas</span>
 </div>
 <div class="px-4 py-4">
 <div class="font-mono text-sm bg-gray-50 rounded p-3 border border-gray-300 space-y-1" id="resumo-final-detalhes-content">
 @php
 $rf = $resumoFinal;
 $nomesBloco = [
 'notas_servicos' => 'Notas de Servico (PIS/COFINS)',
 'notas_mercadorias' => 'NF-e Mercadorias (ICMS/IPI)',
 'notas_transportes' => 'CT-e Transportes',
 'apuracao_icms' => 'Apuracao ICMS/IPI',
 'apuracao_pis_cofins' => 'Apuracao PIS/COFINS',
 'retencoes_fonte' => 'Retencoes na Fonte',
 // Retrocompatibilidade com dados antigos
 'A' => 'Notas de Servico (PIS/COFINS)',
 'C' => 'NF-e Mercadorias (ICMS/IPI)',
 'D' => 'CT-e Transportes',
 ];
 @endphp

 {{-- Participantes — normaliza tanto rf.participantes (spec) quanto rf.estatisticas (n8n atual) --}}
 @php
 $rfParticipantes = $rf['participantes'] ?? null;
 if (!$rfParticipantes && !empty($rf['estatisticas'])) {
 $rfParticipantes = [
 'total' => ($rf['estatisticas']['participantes_novos'] ?? 0)
 + ($rf['estatisticas']['participantes_repetidos'] ?? 0),
 'novos' => $rf['estatisticas']['participantes_novos'] ?? 0,
 'duplicados' => $rf['estatisticas']['participantes_repetidos'] ?? 0,
 ];
 }
 @endphp
 @if(!empty($rfParticipantes))
 <a href="#participantes-section" class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 rounded transition-colors group/link">
 <div class="flex items-center gap-2">
 <span class="text-gray-700 font-bold w-4 text-center">✓</span>
 <span class="text-gray-700 group-hover/link:text-gray-600">Participantes</span>
 </div>
 <div class="text-right flex items-center justify-end flex-wrap gap-1 sm:gap-2">
 <span class="text-gray-900 font-medium">{{ $rfParticipantes['total'] ?? 0 }} registros</span>
 <span class="text-gray-500 text-xs bg-gray-200/50 px-2 py-0.5 rounded">{{ $rfParticipantes['novos'] ?? 0 }} novos · {{ $rfParticipantes['duplicados'] ?? 0 }} exist.</span>
 <svg class="w-3 h-3 text-gray-300 group-hover/link:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </div>
 </a>
 @endif

 {{-- Produtos e Servicos (catalogo 0200) --}}
 @if(!empty($rf['produtos_servicos']))
 @php $ps = $rf['produtos_servicos']; @endphp
 <a href="#catalogo-section" class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 rounded transition-colors group/link">
 <div class="flex items-center gap-2">
 <span class="text-gray-700 font-bold w-4 text-center">✓</span>
 <span class="text-gray-700 group-hover/link:text-gray-600">Produtos e Servicos</span>
 </div>
 <div class="text-right flex items-center justify-end flex-wrap gap-1 sm:gap-2">
 <span class="text-gray-900 font-medium">{{ $ps['total'] ?? 0 }} itens</span>
 <span class="text-gray-500 text-xs bg-gray-200/50 px-2 py-0.5 rounded">{{ $ps['novos'] ?? 0 }} novos · {{ $ps['existentes'] ?? 0 }} exist.</span>
 <svg class="w-3 h-3 text-gray-300 group-hover/link:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </div>
 </a>
 @endif

 {{-- Blocos --}}
 @foreach(['notas_servicos', 'notas_mercadorias', 'notas_transportes', 'apuracao_icms', 'apuracao_pis_cofins', 'retencoes_fonte', 'A', 'C', 'D'] as $bloco)
 @if(isset($rf['blocos'][$bloco]))
 @php
 $bd = $rf['blocos'][$bloco];
 $isSkip = ($bd['total_notas'] ?? 0) == 0 && ($bd['valor_total'] ?? 0) == 0;
 @endphp
 @if($isSkip)
 <div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
 <div class="flex items-center gap-2">
 <span class="text-gray-400 w-4 text-center">—</span>
 <span class="text-gray-700 truncate">{{ $nomesBloco[$bloco] ?? 'Bloco '.$bloco }}</span>
 </div>
 <div class="text-right min-w-[120px]"><span class="text-gray-400 text-xs">Vazio</span></div>
 </div>
 @else
 <a href="/app/notas-fiscais?importacao_id={{ $importacao->id }}" data-link class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 rounded transition-colors group/link">
 <div class="flex items-center gap-2">
 <span class="text-gray-700 font-bold w-4 text-center">✓</span>
 <span class="text-gray-700 truncate group-hover/link:text-gray-600">{{ $nomesBloco[$bloco] ?? 'Bloco '.$bloco }}</span>
 </div>
 <div class="text-right flex flex-col sm:flex-row sm:items-center justify-end sm:gap-2 min-w-[120px]">
 <span class="text-gray-900 font-medium text-right sm:text-left">{{ $bd['total_notas'] ?? 0 }} {{ $bd['label_count'] ?? 'notas' }}</span>
 <span class="text-gray-600 font-mono text-xs sm:ml-2 text-right">R$ {{ number_format($bd['valor_total'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-3 h-3 text-gray-300 group-hover/link:text-gray-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </div>
 </a>
 @endif
 @endif
 @endforeach

 {{-- Total --}}
 @if(!empty($rf['totais']))
 <a href="/app/notas-fiscais?importacao_id={{ $importacao->id }}" data-link class="flex items-center justify-between py-2 border-t border-gray-300 mt-1 hover:bg-gray-50/50 rounded transition-colors group/link">
 <div class="flex items-center gap-2">
 <span class="w-4"></span>
 <span class="text-gray-800 font-bold group-hover/link:text-gray-600">Total</span>
 </div>
 <div class="text-right flex flex-col sm:flex-row sm:items-center justify-end sm:gap-2">
 <span class="text-gray-900 font-bold text-right sm:text-left">{{ $rf['totais']['notas'] ?? 0 }} notas</span>
 <span class="text-gray-800 font-mono text-xs sm:ml-2 text-right border-t sm:border-0 border-gray-200 pt-0.5 sm:pt-0 mt-0.5 sm:mt-0">R$ {{ number_format($rf['totais']['valor'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-3 h-3 text-gray-300 group-hover/link:text-gray-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </div>
 </a>
 @endif
 </div>
 </div>

 {{-- Tabela de participantes enriquecida --}}
 @if(!empty($rf['participantes_resumo']) && $participantes->count() > 0)
 @php
 $resumoIndexado = collect($rf['participantes_resumo'])->keyBy('participante_id');
 @endphp
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participantes — Detalhes de Notas</span>
 </div>
 <div class="px-6 pb-4">
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-sm" id="tabela-notas-participantes-detalhes">
 <thead>
 <tr>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ/CPF</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razao Social</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Notas</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Entradas</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Saidas</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Acoes</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-100" id="tbody-notas-participantes-detalhes">
 @foreach($participantes as $part)
 @php
 $pr = $resumoIndexado->get($part->id);
 $temNotas = $pr && !empty($pr['nota_ids']);
 @endphp
 <tr class="hover:bg-gray-50 transition-colors" data-participante-id="{{ $part->id }}">
 <td class="px-4 py-3 text-xs font-mono text-gray-900 whitespace-nowrap">{{ $part->cnpj_formatado ?: '—' }}</td>
 <td class="px-4 py-3 text-sm text-gray-900 max-w-[240px] truncate" title="{{ $part->razao_social ?: 'Razao social nao informada' }}">{{ $part->razao_social ?: '—' }}</td>
 <td class="px-4 py-3 text-right text-xs">
 @if($pr)
 <span class="font-medium text-gray-900">{{ $pr['total_notas'] ?? 0 }}</span>
 @else
 <span class="text-gray-400">—</span>
 @endif
 </td>
 <td class="px-4 py-3 text-right text-xs">
 @if($pr && isset($pr['entradas']))
 <span class="text-gray-700">{{ $pr['entradas']['count'] ?? 0 }}</span>
 <span class="text-gray-400 ml-1">R$ {{ number_format($pr['entradas']['valor'] ?? 0, 2, ',', '.') }}</span>
 @else
 <span class="text-gray-400">—</span>
 @endif
 </td>
 <td class="px-4 py-3 text-right text-xs">
 @if($pr && isset($pr['saidas']))
 <span class="text-gray-700">{{ $pr['saidas']['count'] ?? 0 }}</span>
 <span class="text-gray-400 ml-1">R$ {{ number_format($pr['saidas']['valor'] ?? 0, 2, ',', '.') }}</span>
 @else
 <span class="text-gray-400">—</span>
 @endif
 </td>
 <td class="px-4 py-3 text-right">
 <div class="flex items-center justify-end gap-2">
 @if($pr)
 <button
 type="button"
 class="btn-expand-notas-detalhes text-gray-600 hover:text-gray-900 text-xs font-medium px-1.5 py-0.5 rounded border border-gray-300 hover:bg-gray-50 transition"
 data-participante-id="{{ $part->id }}"
 data-importacao-id="{{ $importacao->id }}"
 data-nota-ids="{{ json_encode($pr['nota_ids'] ?? []) }}"
 data-bi="{{ json_encode($pr['bi'] ?? []) }}"
 title="Ver notas"
 >▶</button>
 @endif
 <a href="/app/participante/{{ $part->id }}" class="text-xs font-medium text-gray-600 hover:text-gray-900 hover:underline" data-link>Ver</a>
 </div>
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 {{-- Paginacao --}}
 @if($participantes->hasPages())
 <div class="mt-4 flex items-center justify-between gap-4 text-sm">
 <span class="text-gray-500 text-xs">
 Mostrando {{ $participantes->firstItem() }}–{{ $participantes->lastItem() }} de {{ $participantes->total() }} participantes
 </span>
 <div class="flex items-center gap-1">
 @if($participantes->onFirstPage())
 <span class="px-3 py-1.5 rounded border border-gray-300 text-gray-300 text-xs cursor-not-allowed">Anterior</span>
 @else
 <a href="{{ $participantes->previousPageUrl() }}" data-link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">Anterior</a>
 @endif

 <span class="px-3 py-1.5 text-xs text-gray-500">{{ $participantes->currentPage() }} / {{ $participantes->lastPage() }}</span>

 @if($participantes->hasMorePages())
 <a href="{{ $participantes->nextPageUrl() }}" data-link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">Proxima</a>
 @else
 <span class="px-3 py-1.5 rounded border border-gray-300 text-gray-300 text-xs cursor-not-allowed">Proxima</span>
 @endif
 </div>
 </div>
 @endif
 </div>
 </div>
 @endif
</div>
@endif
