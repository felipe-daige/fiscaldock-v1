{{-- Participantes --}}
<div class="bg-white rounded border border-gray-300" id="participantes-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-4 flex-wrap">
 <div class="flex items-center gap-2">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participantes</span>
 @if($participantes->total() > 0)
 <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $participantes->total() }}</span>
 @endif
 </div>
 @if($participantes->total() > 0)
 <div class="flex items-center gap-3">
 <div class="relative">
 <select class="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white" onchange="let u = new URL(window.location.href); u.searchParams.set('per_page_participantes', this.value); u.searchParams.delete('page'); window.asyncLoadEFD(u.toString(), ['participantes-section', 'resumo-final-section']);">
 <option value="10" {{ request('per_page_participantes', 10) == 10 ? 'selected' : '' }}>10 por pág.</option>
 <option value="25" {{ request('per_page_participantes') == 25 ? 'selected' : '' }}>25 por pág.</option>
 <option value="50" {{ request('per_page_participantes') == 50 ? 'selected' : '' }}>50 por pág.</option>
 <option value="100" {{ request('per_page_participantes') == 100 ? 'selected' : '' }}>100 por pág.</option>
 </select>
 </div>
 <div class="relative">
 <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
 </svg>
 <input
 type="text"
 id="busca-participantes-efd"
 placeholder="Buscar participante..."
 class="pl-9 pr-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-64"
 >
 </div>
 </div>
 @endif
 </div>

 @if($participantes->total() > 0)
 {{-- Desktop: Table --}}
 <div class="hidden md:block overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100" id="tabela-participantes-efd">
 <thead>
 <tr>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ/CPF</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razão Social</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Endereço</th>
 <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Inscrição Estadual</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-100" id="tbody-participantes-efd">
 @foreach($participantes as $part)
 <tr
 class="hover:bg-gray-50 cursor-pointer transition-colors"
 data-href="/app/participante/{{ $part->id }}"
 data-razao="{{ strtolower($part->razao_social ?: '') }}"
 data-doc="{{ $part->cnpj_formatado ?: $part->cpf ?: '' }}"
 >
 <td class="px-6 py-4 text-sm font-mono text-gray-900 whitespace-nowrap">{{ $part->cnpj_formatado ?: $part->cpf ?: '—' }}</td>
 <td class="px-6 py-4 text-sm text-gray-900 max-w-[280px] truncate" title="{{ $part->razao_social ?: 'Razão social não informada' }}">{{ $part->razao_social ?: '—' }}</td>
 <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $part->uf ?: '—' }}</td>
 <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $part->endereco ?: '—' }}</td>
 <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $part->inscricao_estadual ?: '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>

 {{-- Mobile: Cards --}}
 <div class="md:hidden divide-y divide-gray-100" id="mobile-participantes-efd">
 @foreach($participantes as $part)
 <div
 class="px-4 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
 data-href="/app/participante/{{ $part->id }}"
 data-razao="{{ strtolower($part->razao_social ?: '') }}"
 data-doc="{{ $part->cnpj_formatado ?: $part->cpf ?: '' }}"
 >
 <p class="text-sm font-medium text-gray-900">{{ $part->razao_social ?: '—' }}</p>
 <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $part->cnpj_formatado ?: $part->cpf ?: '—' }}</p>
 <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
 @if($part->uf) <span>{{ $part->uf }}</span> @endif
 @if($part->endereco) <span>&middot;</span><span>{{ $part->endereco }}</span> @endif
 @if($part->inscricao_estadual) <span>&middot;</span><span>IE: {{ $part->inscricao_estadual }}</span> @endif
 </div>
 </div>
 @endforeach
 </div>

 {{-- Paginacao --}}
 @if($participantes->hasPages())
 <div class="px-6 py-4 flex items-center justify-between gap-4 text-sm border-t border-gray-100">
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
 <a href="{{ $participantes->nextPageUrl() }}" data-link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">Próxima</a>
@else
 <span class="px-3 py-1.5 rounded border border-gray-300 text-gray-300 text-xs cursor-not-allowed">Próxima</span>
@endif
 </div>
 </div>
 @endif

 {{-- Zero-state de busca --}}
 <div id="zero-state-busca" class="hidden px-6 py-12 text-center">
 <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
 </svg>
 <p class="text-sm text-gray-500">Nenhum participante encontrado para esta busca.</p>
 </div>

 @else
 {{-- Zero-state --}}
 <div class="px-6 py-12 text-center">
 @if($importacao->status === 'processando' || $importacao->status === 'pendente')
 <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
 </svg>
 <p class="text-sm font-medium text-gray-700">Importação em andamento</p>
 <p class="text-xs text-gray-500 mt-1">Os participantes aparecerão aqui quando o processamento for concluído.</p>
 @elseif($importacao->status === 'erro')
 <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
 </svg>
 <p class="text-sm font-medium text-gray-700">Nenhum participante extraído</p>
 <p class="text-xs text-gray-500 mt-1">A importação terminou com erro. Nenhum participante foi extraído.</p>
 @else
 <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
 </svg>
 <p class="text-sm font-medium text-gray-700">Nenhum participante encontrado</p>
 <p class="text-xs text-gray-500 mt-1">Esta importação não gerou participantes.</p>
 @endif
 </div>
 @endif
</div>
