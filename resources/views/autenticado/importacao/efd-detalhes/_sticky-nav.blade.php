{{-- Barra de navegação ancorada (sticky) --}}
@if($importacao->status === 'concluido')
<nav class="sticky top-0 z-20 bg-white/95 backdrop-blur border border-gray-300 rounded mb-6 px-4 py-2 flex items-center justify-between gap-4" id="efd-sticky-nav">
 <div class="flex items-center gap-1 overflow-x-auto" style="scrollbar-width: thin;">
 <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide mr-2 flex-shrink-0">Ir para:</span>
 <a href="#info-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Info</a>
 <a href="#participantes-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Participantes</a>
 @if(!empty($resumoFinal))
 <a href="#resumo-final-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Notas</a>
 @endif
 @if(isset($catalogoItens) && ($catalogoItens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $catalogoItens->total() > 0 : $catalogoItens->count() > 0))
 <a href="#catalogo-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Catálogo</a>
 @endif
 @if($apuracaoIcms)
 <a href="#apuracao-icms-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">ICMS/IPI</a>
 @endif
 @if(isset($retencoesFonte) && $retencoesFonte->isNotEmpty())
 <a href="#retencoes-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Retenções</a>
 @endif
 @if($apuracaoContribuicao)
 <a href="#apuracao-pis-cofins-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">PIS/COFINS</a>
 @endif
 @if(!empty($resumoFinal['analise_fiscal']) || !empty($resumoFinal['alertas']))
 <a href="#analise-fiscal-section" class="efd-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Análise Fiscal</a>
 @endif
 </div>

 <a href="/app/importacao/efd" id="btn-voltar-sticky" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold transition-all duration-300 hover:bg-gray-50 flex-shrink-0 opacity-0 pointer-events-none translate-x-4">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
 </svg>
 Voltar
 </a>
</nav>
@endif
