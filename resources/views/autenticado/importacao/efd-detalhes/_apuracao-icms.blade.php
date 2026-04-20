{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Apuração ICMS/IPI — Bloco E (só EFD ICMS/IPI) --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if($apuracaoIcms)
@php $ai = $apuracaoIcms; @endphp
<div class="bg-white rounded border border-gray-300 mt-6" id="apuracao-icms-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
 <div>
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Apuração ICMS/IPI — Bloco E</span>
 @if($ai->periodo_inicio && $ai->periodo_fim)
 <p class="text-[10px] text-gray-400 mt-0.5">Período: {{ $ai->periodo_inicio->format('d/m/Y') }} a {{ $ai->periodo_fim->format('d/m/Y') }}</p>
 @endif
 </div>
 <div class="flex items-center gap-2">
 @if($ai->tem_st)<span class="px-2 py-0.5 text-xs font-medium rounded text-white" style="background-color: #d97706">ICMS-ST</span>@endif
 @if($ai->tem_difal)<span class="px-2 py-0.5 text-xs font-medium rounded text-white" style="background-color: #0891b2">DIFAL/FCP</span>@endif
 @if($ai->tem_ipi)<span class="px-2 py-0.5 text-xs font-medium rounded text-white" style="background-color: #047857">IPI</span>@endif
 </div>
 </div>
 <div class="p-6">
 {{-- ICMS Próprio (E110) --}}
 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> ICMS Próprio
 </h3>
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2 text-sm">
 @php
 $icmsCampos = [
 ['Total de Débitos', $ai->icms_tot_debitos],
 ['Ajustes a Débito', $ai->icms_aj_debitos],
 ['Total Ajustes Débito', $ai->icms_tot_aj_debitos],
 ['Estornos de Crédito', $ai->icms_estornos_credito],
 ['Total de Créditos', $ai->icms_tot_creditos],
 ['Ajustes a Crédito', $ai->icms_aj_creditos],
 ['Total Ajustes Crédito', $ai->icms_tot_aj_creditos],
 ['Estornos de Débito', $ai->icms_estornos_debito],
 ['Saldo Credor Anterior', $ai->icms_sld_credor_ant],
 ['Saldo Apurado', $ai->icms_sld_apurado],
 ['Total Deduções', $ai->icms_tot_deducoes],
 ['Débitos Especiais', $ai->icms_deb_especiais],
 ];
 @endphp
 @foreach($icmsCampos as $campo)
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">{{ $campo[0] }}</span>
 <span class="text-gray-700 font-mono text-xs">R$ {{ number_format($campo[1] ?? 0, 2, ',', '.') }}</span>
 </div>
 @endforeach
 </div>
 <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
 <div class="flex justify-between items-center bg-gray-50 rounded px-4 py-2">
 <span class="text-sm font-semibold text-gray-900">ICMS a Recolher</span>
 <span class="text-sm font-bold text-gray-700">R$ {{ number_format($ai->icms_a_recolher ?? 0, 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center bg-gray-50 rounded px-4 py-2">
 <span class="text-sm font-medium text-gray-700">Saldo Credor a Transportar</span>
 <span class="text-sm font-bold text-gray-900">R$ {{ number_format($ai->icms_sld_credor_transportar ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>

 {{-- Obrigações ICMS (E116) --}}
 @if(!empty($ai->icms_obrigacoes['items']))
 <div class="mt-3">
 <button type="button" class="efd-collapse-toggle text-xs font-medium text-gray-600 hover:text-gray-900 flex items-center gap-1" data-target="obrigacoes-icms">
 <svg class="w-3.5 h-3.5 transition-transform efd-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 Obrigações a Recolher ({{ count($ai->icms_obrigacoes['items']) }})
 </button>
 <div id="obrigacoes-icms" class="hidden mt-2 overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Cód. Obrigação</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Cód. Receita</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ai->icms_obrigacoes['items'] as $obr)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono">{{ $obr['cod_obrigacao'] ?? $obr['COD_OR'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($obr['valor'] ?? $obr['VL_OR'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5">{{ $obr['data_vencimento'] ?? $obr['DT_VCTO'] ?? '—' }}</td>
 <td class="px-3 py-1.5 font-mono">{{ $obr['cod_receita'] ?? $obr['COD_REC'] ?? '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif
 </div>

 {{-- ICMS-ST (E210) — condicional --}}
 @if($ai->tem_st)
 <div class="mt-6 pt-6 border-t border-gray-200">
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> ICMS Substituição Tributária
 @if($ai->st_uf)<span class="text-xs text-gray-500 font-normal ml-1">UF: {{ $ai->st_uf }}</span>@endif
 </h3>
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2 text-sm">
 @php
 $stCampos = [
 ['Saldo Credor Anterior', $ai->st_sld_credor_ant],
 ['Devoluções', $ai->st_devolucoes],
 ['Ressarcimentos', $ai->st_ressarcimentos],
 ['Outros Créditos', $ai->st_outros_creditos],
 ['Ajustes a Crédito', $ai->st_aj_creditos],
 ['Retenção', $ai->st_retencao],
 ['Outros Débitos', $ai->st_outros_debitos],
 ['Ajustes a Débito', $ai->st_aj_debitos],
 ['Saldo Devedor Anterior', $ai->st_sld_devedor_ant],
 ['Deduções', $ai->st_deducoes],
 ['Débitos Especiais', $ai->st_deb_especiais],
 ];
 @endphp
 @foreach($stCampos as $campo)
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">{{ $campo[0] }}</span>
 <span class="text-gray-700 font-mono text-xs">R$ {{ number_format($campo[1] ?? 0, 2, ',', '.') }}</span>
 </div>
 @endforeach
 </div>
 <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
 <div class="flex justify-between items-center bg-gray-50 rounded px-4 py-2">
 <span class="text-sm font-semibold text-gray-900">ICMS-ST a Recolher</span>
 <span class="text-sm font-bold text-gray-700">R$ {{ number_format($ai->st_icms_recolher ?? 0, 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center bg-gray-50 rounded px-4 py-2">
 <span class="text-sm font-medium text-gray-700">Saldo Credor a Transportar</span>
 <span class="text-sm font-bold text-gray-900">R$ {{ number_format($ai->st_sld_credor_transportar ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>

 {{-- Obrigações ICMS-ST (E250) --}}
 @if(!empty($ai->st_obrigacoes['items']))
 <div class="mt-3">
 <button type="button" class="efd-collapse-toggle text-xs font-medium text-gray-600 hover:text-gray-900 flex items-center gap-1" data-target="obrigacoes-st">
 <svg class="w-3.5 h-3.5 transition-transform efd-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 Obrigações ST a Recolher ({{ count($ai->st_obrigacoes['items']) }})
 </button>
 <div id="obrigacoes-st" class="hidden mt-2 overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-xs">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Cód. Obrigação</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Cód. Receita</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ai->st_obrigacoes['items'] as $obr)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 font-mono">{{ $obr['cod_obrigacao'] ?? $obr['COD_OR'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-right font-mono">R$ {{ number_format((float)($obr['valor'] ?? $obr['VL_OR'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5">{{ $obr['data_vencimento'] ?? $obr['DT_VCTO'] ?? '—' }}</td>
 <td class="px-3 py-1.5 font-mono">{{ $obr['cod_receita'] ?? $obr['COD_REC'] ?? '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 @endif
 </div>
 @endif

 {{-- DIFAL/FCP (E300/E310) — condicional --}}
 @if($ai->tem_difal && !empty($ai->difal_fcp))
 <div class="mt-6 pt-6 border-t border-gray-200">
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> DIFAL/FCP — Diferencial de Alíquota
 </h3>
 @php $difal = $ai->difal_fcp; @endphp
 @if(!empty($difal['items']))
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-sm">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">UF</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">DIFAL Origem</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">DIFAL Destino</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">FCP</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($difal['items'] as $d)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 text-xs font-medium">{{ $d['UF'] ?? $d['uf'] ?? '—' }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($d['VL_SLD_DEV_ANT_DIFAL'] ?? $d['difal_origem'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($d['VL_ICMS_RECOLHER_DIFAL'] ?? $d['difal_destino'] ?? $d['icms_recolher'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($d['VL_FCP_RECOLHER'] ?? $d['fcp'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @else
 <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">UF</span>
 <span class="text-gray-700">{{ $difal['UF'] ?? $difal['uf'] ?? '—' }}</span>
 </div>
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">DIFAL a Recolher</span>
 <span class="text-gray-700 font-mono">R$ {{ number_format((float)($difal['VL_ICMS_RECOLHER_DIFAL'] ?? $difal['icms_recolher'] ?? 0), 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">FCP a Recolher</span>
 <span class="text-gray-700 font-mono">R$ {{ number_format((float)($difal['VL_FCP_RECOLHER'] ?? $difal['fcp'] ?? 0), 2, ',', '.') }}</span>
 </div>
 </div>
 @endif
 </div>
 @endif

 {{-- IPI (E500/E520) — condicional --}}
 @if($ai->tem_ipi && !empty($ai->ipi))
 <div class="mt-6 pt-6 border-t border-gray-200">
 <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
 <span class="w-2 h-2 rounded-full bg-gray-500"></span> IPI — Imposto sobre Produtos Industrializados
 </h3>
 @php $ipiData = $ai->ipi; @endphp
 @if(!empty($ipiData['items']))
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-sm">
 <thead>
 <tr>
 <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">Débitos</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">Créditos</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
 <th class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase">A Recolher</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ipiData['items'] as $ip)
 <tr class="hover:bg-gray-50">
 <td class="px-3 py-1.5 text-xs">{{ ($ip['DT_INI'] ?? $ip['periodo_inicio'] ?? '—') }} a {{ ($ip['DT_FIN'] ?? $ip['periodo_fim'] ?? '') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($ip['VL_TOT_DEBITOS'] ?? $ip['debitos'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($ip['VL_TOT_CREDITOS'] ?? $ip['creditos'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono">R$ {{ number_format((float)($ip['VL_SLD_APURADO'] ?? $ip['saldo'] ?? 0), 2, ',', '.') }}</td>
 <td class="px-3 py-1.5 text-xs text-right font-mono font-semibold">R$ {{ number_format((float)($ip['VL_IPI_RECOLHER'] ?? $ip['a_recolher'] ?? 0), 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @else
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">Débitos</span>
 <span class="text-gray-700 font-mono">R$ {{ number_format((float)($ipiData['VL_TOT_DEBITOS'] ?? $ipiData['debitos'] ?? 0), 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">Créditos</span>
 <span class="text-gray-700 font-mono">R$ {{ number_format((float)($ipiData['VL_TOT_CREDITOS'] ?? $ipiData['creditos'] ?? 0), 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center py-1">
 <span class="text-gray-600">Saldo Apurado</span>
 <span class="text-gray-700 font-mono">R$ {{ number_format((float)($ipiData['VL_SLD_APURADO'] ?? $ipiData['saldo'] ?? 0), 2, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center py-1 font-semibold">
 <span class="text-gray-900">IPI a Recolher</span>
 <span class="text-gray-900 font-mono">R$ {{ number_format((float)($ipiData['VL_IPI_RECOLHER'] ?? $ipiData['a_recolher'] ?? 0), 2, ',', '.') }}</span>
 </div>
 </div>
 @endif
 </div>
 @endif

 {{-- Total Geral --}}
 <div class="mt-6 pt-4 border-t-2 border-gray-300 flex justify-between items-center">
 <span class="text-base font-bold text-gray-900">Total a Recolher (ICMS + ST)</span>
 <span class="text-lg font-bold text-gray-900">R$ {{ number_format($ai->total_recolher ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>
</div>
@endif
