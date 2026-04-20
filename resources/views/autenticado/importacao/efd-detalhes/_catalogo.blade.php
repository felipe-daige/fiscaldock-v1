{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- Catalogo de Itens — Registro 0200 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(isset($catalogoItens) && ($catalogoItens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $catalogoItens->total() > 0 : $catalogoItens->count() > 0))
@php $totalCatalogo = $catalogoItens instanceof \Illuminate\Pagination\LengthAwarePaginator ? $catalogoItens->total() : $catalogoItens->count(); @endphp
<div class="bg-white rounded border border-gray-300 mt-6" id="catalogo-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-4 flex-wrap">
 <div class="flex items-center gap-2">
<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Catálogo de Produtos/Serviços</span>
 <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $totalCatalogo }}</span>
 </div>
 <div class="flex items-center gap-3">
 <div class="relative">
 <select class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white" onchange="let u = new URL(window.location.href); u.searchParams.set('per_page_catalogo', this.value); u.searchParams.delete('catalogo_page'); window.asyncLoadEFD(u.toString(), ['catalogo-section']);">
 <option value="10" {{ request('per_page_catalogo', 10) == 10 ? 'selected' : '' }}>10 por pág.</option>
 <option value="25" {{ request('per_page_catalogo') == 25 ? 'selected' : '' }}>25 por pág.</option>
 <option value="50" {{ request('per_page_catalogo') == 50 ? 'selected' : '' }}>50 por pág.</option>
 <option value="100" {{ request('per_page_catalogo') == 100 ? 'selected' : '' }}>100 por pág.</option>
 </select>
 </div>
 <div class="relative">
 <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
 </svg>
 <input type="text" id="busca-catalogo" placeholder="Buscar por código, descrição ou NCM..." class="pl-9 pr-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-72">
 </div>
 </div>
 </div>

 <div class="hidden md:block overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 text-sm" id="tabela-catalogo">
 <thead>
 <tr>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">NCM</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Unidade</th>
 <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Aliq. ICMS</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-100" id="tbody-catalogo">
 @foreach($catalogoItens as $item)
 <tr class="hover:bg-gray-50 transition-colors" data-cod="{{ strtolower($item->cod_item ?? '') }}" data-desc="{{ strtolower($item->descr_item ?? '') }}" data-ncm="{{ $item->cod_ncm ?? '' }}">
 <td class="px-4 py-2.5 text-xs font-mono text-gray-900 whitespace-nowrap">{{ $item->cod_item ?? '—' }}</td>
 <td class="px-4 py-2.5 text-sm text-gray-900 max-w-[320px] truncate" title="{{ $item->descr_item ?? '' }}">{{ $item->descr_item ?? '—' }}</td>
 <td class="px-4 py-2.5 text-xs font-mono text-gray-700">{{ $item->cod_ncm ?? '—' }}</td>
 <td class="px-4 py-2.5 text-xs text-gray-600">{{ $item->tipo_item ?? '—' }}</td>
 <td class="px-4 py-2.5 text-xs text-gray-600">{{ $item->unid_inv ?? '—' }}</td>
 <td class="px-4 py-2.5 text-xs text-right font-mono text-gray-700">{{ $item->aliq_icms ? number_format($item->aliq_icms, 2, ',', '.') . '%' : '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>

 {{-- Mobile cards --}}
 <div class="md:hidden divide-y divide-gray-100" id="mobile-catalogo">
 @foreach($catalogoItens as $item)
 <div class="px-4 py-3" data-cod="{{ strtolower($item->cod_item ?? '') }}" data-desc="{{ strtolower($item->descr_item ?? '') }}" data-ncm="{{ $item->cod_ncm ?? '' }}">
 <p class="text-sm font-medium text-gray-900">{{ $item->descr_item ?? '—' }}</p>
 <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
 <span class="font-mono">{{ $item->cod_item ?? '—' }}</span>
 @if($item->cod_ncm)<span>&middot;</span><span>NCM: {{ $item->cod_ncm }}</span>@endif
 @if($item->aliq_icms)<span>&middot;</span><span>ICMS: {{ number_format($item->aliq_icms, 2, ',', '.') }}%</span>@endif
 </div>
 </div>
 @endforeach
 </div>

 {{-- Paginacao --}}
 @if($catalogoItens instanceof \Illuminate\Pagination\LengthAwarePaginator && $catalogoItens->hasPages())
 <div class="px-6 py-4 flex items-center justify-between gap-4 text-sm border-t border-gray-100">
 <span class="text-gray-500 text-xs">Mostrando {{ $catalogoItens->firstItem() }}–{{ $catalogoItens->lastItem() }} de {{ $catalogoItens->total() }} itens</span>
 <div class="flex items-center gap-1">
 @if($catalogoItens->onFirstPage())
 <span class="px-3 py-1.5 rounded border border-gray-300 text-gray-300 text-xs cursor-not-allowed">Anterior</span>
 @else
 <a href="{{ $catalogoItens->previousPageUrl() }}" data-link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">Anterior</a>
 @endif
 <span class="px-3 py-1.5 text-xs text-gray-500">{{ $catalogoItens->currentPage() }} / {{ $catalogoItens->lastPage() }}</span>
 @if($catalogoItens->hasMorePages())
 <a href="{{ $catalogoItens->nextPageUrl() }}" data-link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">Próxima</a>
@else
 <span class="px-3 py-1.5 rounded border border-gray-300 text-gray-300 text-xs cursor-not-allowed">Próxima</span>
@endif
 </div>
 </div>
 @endif

 {{-- Zero-state busca --}}
 <div id="zero-state-catalogo" class="hidden px-6 py-8 text-center">
 <p class="text-sm text-gray-500">Nenhum item encontrado para esta busca.</p>
 </div>
</div>
@endif
