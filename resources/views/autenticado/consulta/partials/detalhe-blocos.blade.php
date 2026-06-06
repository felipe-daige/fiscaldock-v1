{{-- Detalhe expansível por CNPJ: 1 card por fonte consultada, com badge, itens (com
     tooltips), listas (CNAEs/QSA/bases), mensagem oficial da certidão e link de comprovante.
     Exibe TUDO que a consulta trouxe — inclusive fontes ausentes da tabela resumida.
     Espera: $blocos (array de blocos do ResultadoDetalhePresenter). --}}
@php($blocos = $blocos ?? [])

@if(empty($blocos))
    <p class="text-xs text-gray-500">Sem detalhes adicionais para esta consulta.</p>
@else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        @foreach($blocos as $bloco)
            <div class="border border-gray-200 rounded bg-white overflow-hidden">
                <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 border-b border-gray-200">
                    <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">{{ $bloco['titulo'] }}</span>
                    @if(!empty($bloco['badge']))
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap"
                              style="background-color: {{ $bloco['badge']['hex'] }}"
                              @if(!empty($bloco['mensagem'])) title="{{ $bloco['mensagem'] }}" @endif>
                            {{ $bloco['badge']['label'] }}
                        </span>
                    @endif
                </div>
                <div class="px-3 py-2.5 space-y-2.5">
                    @if(!empty($bloco['itens']))
                        <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
                            @foreach($bloco['itens'] as $item)
                                <div class="min-w-0">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $item['label'] }}</p>
                                    <p class="text-xs text-gray-800 break-words">
                                        @if(!empty($item['tooltip']))
                                            <span class="underline decoration-dotted cursor-help" title="{{ $item['tooltip'] }}">{{ $item['valor'] }}</span>
                                        @else
                                            {{ $item['valor'] }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @foreach($bloco['listas'] as $lista)
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">{{ $lista['titulo'] }}</p>
                            <ul class="space-y-0.5">
                                @foreach($lista['linhas'] as $linha)
                                    <li class="text-xs text-gray-700 leading-snug">• {{ $linha }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    @if(!empty($bloco['mensagem']))
                        <p class="text-[11px] text-gray-500 italic leading-snug border-l-2 border-gray-200 pl-2">{{ $bloco['mensagem'] }}</p>
                    @endif

                    @if(!empty($bloco['comprovante_url']))
                        <a href="{{ $bloco['comprovante_url'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-700 hover:text-gray-900 hover:underline">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Ver comprovante
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
