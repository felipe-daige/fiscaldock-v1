{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Retenções na Fonte PIS/COFINS — F600 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(isset($retencoesFonte) && $retencoesFonte->isNotEmpty())
<div class="bg-white rounded border border-gray-300 mt-6" id="retencoes-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Retenções na Fonte PIS/COFINS — F600</span>
 <p class="text-[10px] text-gray-400 mt-0.5">{{ $retencoesFonte->count() }} retenções encontradas</p>
 </div>
 <div class="p-6">
 {{-- Resumo --}}
 <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
 <div class="bg-gray-50 rounded px-4 py-3 text-center">
 <p class="text-xs text-gray-500">Total Retenções</p>
 <p class="text-lg font-bold text-gray-900">{{ $retencoesFonte->count() }}</p>
 </div>
 <div class="bg-gray-50 rounded px-4 py-3 text-center">
 <p class="text-xs text-gray-500">Base de Cálculo</p>
 <p class="text-sm font-bold text-gray-900">R$ {{ number_format($retencoesFonte->sum('base_calculo'), 2, ',', '.') }}</p>
 </div>
 <div class="bg-gray-50 rounded px-4 py-3 text-center">
 <p class="text-xs text-gray-600">PIS Retido</p>
 <p class="text-sm font-bold text-gray-700">R$ {{ number_format($retencoesFonte->sum('valor_pis'), 2, ',', '.') }}</p>
 </div>
 <div class="bg-gray-50 rounded px-4 py-3 text-center">
 <p class="text-xs text-gray-600">COFINS Retido</p>
 <p class="text-sm font-bold text-gray-700">R$ {{ number_format($retencoesFonte->sum('valor_cofins'), 2, ',', '.') }}</p>
 </div>
 </div>

 {{-- Tabela Desktop --}}
 <div class="hidden sm:block overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-sm">
 <thead>
 <tr>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Natureza</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Base Cálculo</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">PIS</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">COFINS</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Total</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-100">
 @foreach($retencoesFonte as $ret)
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-4 py-2.5 text-xs font-mono text-gray-900 whitespace-nowrap">{{ $ret->cnpj_formatado }}</td>
 <td class="px-4 py-2.5 text-xs text-gray-700">{{ $ret->data_retencao?->format('d/m/Y') ?? '—' }}</td>
 <td class="px-4 py-2.5 text-xs text-gray-700">{{ $ret->natureza_formatada }}</td>
 <td class="px-4 py-2.5 text-xs text-right text-gray-700 font-mono">R$ {{ number_format($ret->base_calculo ?? 0, 2, ',', '.') }}</td>
 <td class="px-4 py-2.5 text-xs text-right text-gray-700 font-mono">R$ {{ number_format($ret->valor_pis ?? 0, 2, ',', '.') }}</td>
 <td class="px-4 py-2.5 text-xs text-right text-gray-700 font-mono">R$ {{ number_format($ret->valor_cofins ?? 0, 2, ',', '.') }}</td>
 <td class="px-4 py-2.5 text-xs text-right text-gray-900 font-semibold font-mono">R$ {{ number_format($ret->valor_total ?? 0, 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>

 {{-- Cards Mobile --}}
 <div class="sm:hidden space-y-3">
 @foreach($retencoesFonte as $ret)
 <div class="bg-gray-50 rounded p-4 space-y-2">
 <div class="flex justify-between items-start">
 <span class="text-xs font-mono text-gray-900">{{ $ret->cnpj_formatado }}</span>
 <span class="text-xs text-gray-500">{{ $ret->data_retencao?->format('d/m/Y') ?? '—' }}</span>
 </div>
 <div class="flex justify-between items-center text-xs">
 <span class="text-gray-600">{{ $ret->natureza_formatada }}</span>
 <span class="font-semibold text-gray-900">R$ {{ number_format($ret->valor_total ?? 0, 2, ',', '.') }}</span>
 </div>
 <div class="flex gap-4 text-xs">
 <span class="text-gray-700">PIS: R$ {{ number_format($ret->valor_pis ?? 0, 2, ',', '.') }}</span>
 <span class="text-gray-700">COFINS: R$ {{ number_format($ret->valor_cofins ?? 0, 2, ',', '.') }}</span>
 </div>
 </div>
 @endforeach
 </div>
 </div>
</div>
@endif
