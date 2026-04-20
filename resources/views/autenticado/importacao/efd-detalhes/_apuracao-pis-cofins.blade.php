{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Apuração PIS/COFINS — Bloco M (só EFD Contribuições) --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if($apuracaoContribuicao)
@php
 $ac = $apuracaoContribuicao;
 $regimeBadge = match($ac->regime) {
 'nao_cumulativo' => ['background-color: #374151', 'Não Cumulativo (Lucro Real)'],
 'misto' => ['background-color: #d97706', 'Misto'],
 default => ['background-color: #9ca3af', 'Cumulativo (Lucro Presumido)'],
 };
@endphp
<div class="bg-white rounded border border-gray-300 mt-6" id="apuracao-pis-cofins-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Apuração PIS/COFINS — Bloco M</span>
 <span class="px-2.5 py-1 text-xs font-semibold rounded text-white" style="{{ $regimeBadge[0] }}">{{ $regimeBadge[1] }}</span>
 </div>
 <div class="p-6">
 <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 {{-- PIS --}}
 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> PIS
 </h3>
 <div class="space-y-2 text-sm">
 @php
 $pisCampos = [
 ['Contribuição Não Cumulativa', $ac->pis_nao_cumulativo],
 ['(-) Crédito Descontado', $ac->pis_credito_descontado],
 ['(-) Crédito Descontado Anterior', $ac->pis_credito_desc_ant],
 ['(=) Devida NC', $ac->pis_nc_devida],
 ['(-) Retenção NC', $ac->pis_retencao_nc],
 ['(-) Outras Deduções NC', $ac->pis_outras_deducoes_nc],
 ['(=) PIS NC a Recolher', $ac->pis_nc_recolher, true],
 ['Contribuição Cumulativa', $ac->pis_cumulativo],
 ['(-) Retenção Cumulativa', $ac->pis_retencao_cum],
 ['(-) Outras Deduções Cum.', $ac->pis_outras_deducoes_cum],
 ['(=) PIS Cum. a Recolher', $ac->pis_cum_recolher, true],
 ];
 @endphp
 @foreach($pisCampos as $campo)
 <div class="flex justify-between items-center {{ ($campo[2] ?? false) ? 'pt-2 border-t border-gray-200 font-semibold' : '' }}">
 <span class="text-gray-600">{{ $campo[0] }}</span>
 <span class="{{ ($campo[2] ?? false) ? 'text-gray-900' : 'text-gray-700' }}">R$ {{ number_format($campo[1] ?? 0, 2, ',', '.') }}</span>
 </div>
 @endforeach
 <div class="flex justify-between items-center pt-2 border-t-2 border-gray-300 font-bold text-gray-900">
 <span>Total PIS a Recolher</span>
 <span>R$ {{ number_format($ac->pis_total_recolher ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>
 </div>

 {{-- COFINS --}}
 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> COFINS
 </h3>
 <div class="space-y-2 text-sm">
 @php
 $cofinsCampos = [
 ['Contribuição Não Cumulativa', $ac->cofins_nao_cumulativo],
 ['(-) Crédito Descontado', $ac->cofins_credito_descontado],
 ['(-) Crédito Descontado Anterior', $ac->cofins_credito_desc_ant],
 ['(=) Devida NC', $ac->cofins_nc_devida],
 ['(-) Retenção NC', $ac->cofins_retencao_nc],
 ['(-) Outras Deduções NC', $ac->cofins_outras_deducoes_nc],
 ['(=) COFINS NC a Recolher', $ac->cofins_nc_recolher, true],
 ['Contribuição Cumulativa', $ac->cofins_cumulativo],
 ['(-) Retenção Cumulativa', $ac->cofins_retencao_cum],
 ['(-) Outras Deduções Cum.', $ac->cofins_outras_deducoes_cum],
 ['(=) COFINS Cum. a Recolher', $ac->cofins_cum_recolher, true],
 ];
 @endphp
 @foreach($cofinsCampos as $campo)
 <div class="flex justify-between items-center {{ ($campo[2] ?? false) ? 'pt-2 border-t border-gray-200 font-semibold' : '' }}">
 <span class="text-gray-600">{{ $campo[0] }}</span>
 <span class="{{ ($campo[2] ?? false) ? 'text-gray-900' : 'text-gray-700' }}">R$ {{ number_format($campo[1] ?? 0, 2, ',', '.') }}</span>
 </div>
 @endforeach
 <div class="flex justify-between items-center pt-2 border-t-2 border-gray-300 font-bold text-gray-900">
 <span>Total COFINS a Recolher</span>
 <span>R$ {{ number_format($ac->cofins_total_recolher ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>
 </div>
 </div>

 {{-- Total Geral --}}
 <div class="mt-6 pt-4 border-t-2 border-gray-300 flex justify-between items-center">
 <span class="text-base font-bold text-gray-900">Total PIS + COFINS a Recolher</span>
 <span class="text-lg font-bold text-gray-900">R$ {{ number_format($ac->total_recolher ?? 0, 2, ',', '.') }}</span>
 </div>

 {{-- Indicadores de créditos NC --}}
 @if($ac->tem_creditos_nc)
 <div class="mt-3">
 <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded text-white" style="background-color: #047857">Créditos Não Cumulativos Apurados</span>
 </div>
 @endif

 {{-- ── Detalhes por CST — M210 (PIS) e M610 (COFINS) ── --}}
 @if(!empty($ac->pis_detalhes['items']) || !empty($ac->cofins_detalhes['items']))
 <div class="mt-6 pt-6 border-t border-gray-200">
 <button type="button" class="efd-collapse-toggle text-sm font-semibold text-gray-700 flex items-center gap-2 mb-3" data-target="detalhes-cst">
 <svg class="w-4 h-4 transition-transform efd-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 Detalhes por CST
 </button>
 <div id="detalhes-cst" class="hidden">
 <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 {{-- PIS por CST (M210) --}}
 @if(!empty($ac->pis_detalhes['items']))
 <div>
 <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-1.5">
 <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> PIS por CST (M210)
 </h4>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">CST</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Base Cálc.</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Alíquota</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Contribuição</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ac->pis_detalhes['items'] as $cst)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono font-medium">{{ $cst['COD_CONT'] ?? $cst['cst'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($cst['VL_BC_CONT'] ?? $cst['base_calculo'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float)($cst['ALIQ_PIS'] ?? $cst['aliquota'] ?? 0), 4, ',', '.') }}%</td>
 <td class="px-3 py-1.5 text-right font-mono font-semibold">R$ {{ number_format((float)($cst['VL_CONT_APUR'] ?? $cst['valor_contribuicao'] ?? $cst['VL_CONT'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif

 {{-- COFINS por CST (M610) --}}
 @if(!empty($ac->cofins_detalhes['items']))
 <div>
 <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-1.5">
 <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> COFINS por CST (M610)
 </h4>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">CST</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Base Cálc.</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Alíquota</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Contribuição</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ac->cofins_detalhes['items'] as $cst)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono font-medium">{{ $cst['COD_CONT'] ?? $cst['cst'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($cst['VL_BC_CONT'] ?? $cst['base_calculo'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float)($cst['ALIQ_COFINS'] ?? $cst['aliquota'] ?? 0), 4, ',', '.') }}%</td>
 <td class="px-3 py-1.5 text-right font-mono font-semibold">R$ {{ number_format((float)($cst['VL_CONT_APUR'] ?? $cst['valor_contribuicao'] ?? $cst['VL_CONT'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif
 </div>
 </div>
 </div>
 @endif

 {{-- ── Receitas Não Tributadas — M400/M410 ── --}}
 @if(!empty($ac->pis_nao_tributado['items']))
 <div class="mt-6 pt-6 border-t border-gray-200">
 <button type="button" class="efd-collapse-toggle text-sm font-semibold text-gray-700 flex items-center gap-2 mb-3" data-target="receitas-nao-tributadas">
 <svg class="w-4 h-4 transition-transform efd-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 Receitas Não Tributadas / Isentas ({{ count($ac->pis_nao_tributado['items']) }})
 </button>
 <div id="receitas-nao-tributadas" class="hidden">
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">CST</th>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">Nat. Receita</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Valor PIS</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Valor COFINS</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ac->pis_nao_tributado['items'] as $nt)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono font-medium">{{ $nt['CST_PIS'] ?? $nt['cst'] ?? '—' }}</td>
 <td class="px-3 py-1.5">{{ $nt['NAT_REC'] ?? $nt['natureza_receita'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($nt['VL_REC'] ?? $nt['valor'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($nt['VL_REC_COFINS'] ?? $nt['valor_cofins'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 </div>
 @endif

 {{-- ── Créditos Não Cumulativos — M100/M110 (PIS) e M500/M510 (COFINS) ── --}}
 @if($ac->tem_creditos_nc)
 <div class="mt-6 pt-6 border-t border-gray-200">
 <button type="button" class="efd-collapse-toggle text-sm font-semibold text-gray-700 flex items-center gap-2 mb-3" data-target="creditos-nc">
 <svg class="w-4 h-4 transition-transform efd-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 Créditos Não Cumulativos (Lucro Real)
 </button>
 <div id="creditos-nc" class="hidden">
 <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 {{-- PIS NC (M100/M105/M110) --}}
 @if(!empty($ac->pis_creditos_nc['items']))
 <div>
 <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-1.5">
 <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Créditos PIS (M100/M110)
 </h4>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">Tipo</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Base Cálc.</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Alíquota</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Crédito</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ac->pis_creditos_nc['items'] as $cr)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono">{{ $cr['COD_CRED'] ?? $cr['tipo'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($cr['VL_BC_PIS'] ?? $cr['base_calculo'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float)($cr['ALIQ_PIS'] ?? $cr['aliquota'] ?? 0), 4, ',', '.') }}%</td>
 <td class="px-3 py-1.5 text-right font-mono font-semibold">R$ {{ number_format((float)($cr['VL_CRED'] ?? $cr['valor'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif

 {{-- COFINS NC (M500/M505/M510) --}}
 @if(!empty($ac->cofins_creditos_nc['items']))
 <div>
 <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-1.5">
 <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Créditos COFINS (M500/M510)
 </h4>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left font-medium text-gray-500">Tipo</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Base Cálc.</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Alíquota</th>
 <th class="px-3 py-1.5 text-right font-medium text-gray-500">Crédito</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ac->cofins_creditos_nc['items'] as $cr)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono">{{ $cr['COD_CRED'] ?? $cr['tipo'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($cr['VL_BC_COFINS'] ?? $cr['base_calculo'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float)($cr['ALIQ_COFINS'] ?? $cr['aliquota'] ?? 0), 4, ',', '.') }}%</td>
 <td class="px-3 py-1.5 text-right font-mono font-semibold">R$ {{ number_format((float)($cr['VL_CRED'] ?? $cr['valor'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif
 </div>
 </div>
 </div>
 @endif
 </div>
</div>
@endif
