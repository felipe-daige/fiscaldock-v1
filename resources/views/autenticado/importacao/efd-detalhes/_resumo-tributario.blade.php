{{-- Resumo Executivo Tributário --}}
@if($apuracaoIcms || $apuracaoContribuicao || (isset($retencoesFonte) && $retencoesFonte->isNotEmpty()))
@php
 $tribIcms = $apuracaoIcms ? (float) $apuracaoIcms->icms_a_recolher : 0;
 $tribIcmsSt = $apuracaoIcms && $apuracaoIcms->tem_st ? (float) $apuracaoIcms->st_icms_recolher : 0;
 $tribPis = $apuracaoContribuicao ? (float) $apuracaoContribuicao->pis_total_recolher : 0;
 $tribCofins = $apuracaoContribuicao ? (float) $apuracaoContribuicao->cofins_total_recolher : 0;
 $tribRetPis = isset($retencoesFonte) ? (float) $retencoesFonte->sum('valor_pis') : 0;
 $tribRetCof = isset($retencoesFonte) ? (float) $retencoesFonte->sum('valor_cofins') : 0;
 $tribRetTotal = $tribRetPis + $tribRetCof;
 $tribTotal = $tribIcms + $tribIcmsSt + $tribPis + $tribCofins;
@endphp
<div class="bg-white rounded border border-gray-300 mb-6">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Tributário</span>
 </div>

 <div class="p-4">
 <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 mb-6">
 @if($apuracaoIcms)
 <div class="flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">ICMS</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($tribIcms, 2, ',', '.') }}</p>
 </div>
 @if($apuracaoIcms->tem_st)
 <div class="flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">ICMS-ST</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($tribIcmsSt, 2, ',', '.') }}</p>
 </div>
 @endif
 @endif
 @if($apuracaoContribuicao)
 <div class="flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">PIS</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($tribPis, 2, ',', '.') }}</p>
 </div>
 <div class="flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">COFINS</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($tribCofins, 2, ',', '.') }}</p>
 </div>
 @endif
 @if($tribRetTotal > 0)
 <div class="flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Retenções</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($tribRetTotal, 2, ',', '.') }}</p>
 </div>
 @endif
 </div>

 <div class="pt-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center">
 <span class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Total a Recolher</span>
 <span class="text-2xl font-bold text-gray-900 mt-2 sm:mt-0">R$ {{ number_format($tribTotal, 2, ',', '.') }}</span>
 </div>

 @if($tribRetTotal > 0)
 <div class="mt-4 pt-4 border-t border-gray-100 flex items-start gap-2 text-gray-600 text-xs">
 <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
 <p class="leading-relaxed">As retenções na fonte (PIS R$ {{ number_format($tribRetPis, 2, ',', '.') }} + COFINS R$ {{ number_format($tribRetCof, 2, ',', '.') }}) constam no resumo e podem ser compensadas na apuração.</p>
 </div>
 @endif
 </div>
</div>
@endif