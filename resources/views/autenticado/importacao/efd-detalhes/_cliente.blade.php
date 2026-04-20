{{-- Card Cliente --}}
<div class="bg-white rounded border border-gray-300 mb-6">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cliente Associado</span>
 </div>
 <div class="p-4">
 @if($importacao->cliente)
 <div class="flex items-center gap-4 flex-wrap">
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Razão Social</p>
 <p class="text-sm font-semibold text-gray-900">{{ $importacao->cliente->razao_social }}</p>
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">{{ $importacao->cliente->tipo_pessoa === 'PJ' ? 'CNPJ' : 'CPF' }}</p>
 <p class="text-sm font-mono text-gray-900">{{ $importacao->cliente->documento_formatado ?? $importacao->cliente->documento ?? '—' }}</p>
 </div>
 <div>
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Tipo</p>
 <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="background-color: #374151">
 {{ $importacao->cliente->tipo_pessoa === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
 </span>
 </div>
 <div class="ml-auto">
 @php
 $docBusca = $importacao->cliente->documento ?? '';
 @endphp
 <a
 href="/app/clientes?search={{ urlencode($docBusca) }}"
 data-link
 class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition"
 >
 Ver no cadastro
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
 </svg>
 </a>
 </div>
 </div>
 @else
 <p class="text-sm text-gray-400 italic">Nenhum cliente associado a esta importação.</p>
 @endif
 </div>
</div>