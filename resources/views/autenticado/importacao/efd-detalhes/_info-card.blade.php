{{-- Info Card --}}
<div class="bg-white rounded border border-gray-300 mb-6" id="info-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Informações da Importação</span>
 </div>
 <div class="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6">
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo EFD</p>
 <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="{{ $tipoStyle }}">
 {{ $importacao->tipo_efd === 'efd-contrib' ? 'EFD PIS/COFINS' : 'EFD ICMS/IPI' }}
 </span>
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Enviado em</p>
 <p class="text-sm font-medium text-gray-900">{{ $importacao->created_at->format('d/m/Y') }}</p>
 <p class="text-xs text-gray-500">{{ $importacao->created_at->format('H:i') }}</p>
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Concluído em</p>
 @if($importacao->concluido_em)
 <p class="text-sm font-medium text-gray-900">{{ $importacao->concluido_em->format('d/m/Y') }}</p>
 <p class="text-xs text-gray-500">{{ $importacao->concluido_em->format('H:i') }}</p>
 @else
 <p class="text-sm text-gray-400">—</p>
 @endif
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tempo</p>
 <p class="text-sm font-medium text-gray-900">{{ $importacao->tempo_processamento }}</p>
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Créditos cobrados</p>
 <p class="text-sm font-medium text-gray-900">{{ $importacao->creditos_cobrados ?? 0 }}</p>
 </div>
 </div>
</div>