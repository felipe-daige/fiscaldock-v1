{{-- Detalhe expansível por CNPJ — layout "DANFE profissional".
     Estrutura:
       1. Parecer da análise (faixa com acento slate)
       2. Bloco identidade (Dados cadastrais) — largura total, como a caixa do emitente
       3. Fontes/certidões — masonry (CSS columns) p/ empacotar cartões de alturas diferentes
          sem deixar o "um grande, outro pequeno" lado a lado. Cada cartão leva o acento da cor
          do status na borda esquerda (sinal fiscal de carimbo).
     Espera: $blocos (ResultadoDetalhePresenter::blocos), $resumo (texto). --}}
@php($blocos = $blocos ?? [])
@php($resumo = $resumo ?? null)
@php($certidoes = $certidoes ?? [])
@php($cabecalho = $cabecalho ?? [])
@php($cadastro = collect($blocos)->firstWhere('chave', 'cadastro'))
@php($fontes = collect($blocos)->reject(fn ($b) => ($b['chave'] ?? null) === 'cadastro')->values())

@if(!empty($resumo))
    <div class="mb-3 rounded border border-gray-200 bg-white px-3 py-2.5" style="border-left: 3px solid #1f2937">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Parecer da análise</p>
        <p class="text-xs text-gray-700 leading-relaxed mt-1">{{ $resumo }}</p>
    </div>
@endif

@if(!empty($certidoes))
    <div class="mb-3 flex flex-wrap gap-1.5">
        @foreach($certidoes as $cert)
            <span class="cert-chip inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                  style="background-color: {{ $cert['hex'] }}">
                {{ $cert['sigla'] }} {{ $cert['glyph'] }}
                <span class="cert-tip">
                    <strong>{{ $cert['titulo'] }} · {{ $cert['label'] }}</strong>
                    @if(!empty($cert['descricao'])){{ $cert['descricao'] }}@endif
                </span>
            </span>
        @endforeach
    </div>
@endif

@if(empty($blocos))
    <p class="text-xs text-gray-500">Sem detalhes adicionais para esta consulta.</p>
@else
    {{-- ── Identidade (cadastro): largura total ───────────────────────────── --}}
    @if($cadastro)
        <div class="mb-3 rounded border border-gray-300 bg-white overflow-hidden" style="border-top: 2px solid #1e4679">
            <div class="px-3 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-widest">{{ $cadastro['titulo'] }}</span>
            </div>
            <div class="px-3 py-3 space-y-3">
                @if(!empty($cadastro['itens']))
                    <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2">
                        @foreach($cadastro['itens'] as $item)
                            <div class="min-w-0">
                                <dt class="text-[9px] text-gray-400 uppercase tracking-wider">{{ $item['label'] }}</dt>
                                <dd class="text-[12px] text-gray-800 font-medium break-words mt-0.5">
                                    @if(!empty($item['tooltip']))
                                        <span class="underline decoration-dotted cursor-help" title="{{ $item['tooltip'] }}">{{ $item['valor'] }}</span>
                                    @else
                                        {{ $item['valor'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @foreach($cadastro['listas'] as $lista)
                    <div class="rounded border border-gray-100 bg-gray-50 px-3 py-2.5">
                        <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-1.5">{{ $lista['titulo'] }}</p>
                        <ul class="space-y-1">
                            @foreach($lista['linhas'] as $linha)
                                <li class="text-[11px] text-gray-700 leading-snug flex gap-1.5">
                                    <span class="text-gray-300 select-none">▪</span>
                                    <span>{{ $linha }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Fontes / certidões: masonry (columns) p/ alturas desiguais ──────── --}}
    @if($fontes->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-start">
            @foreach($fontes as $bloco)
                @php($acento = $bloco['badge']['hex'] ?? '#9ca3af')
                <div class="rounded border border-gray-300 bg-white overflow-hidden" style="border-left: 3px solid {{ $acento }}">
                    <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 border-b border-gray-200">
                        <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide truncate">{{ $bloco['titulo'] }}</span>
                        @if(!empty($bloco['badge']))
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap shrink-0"
                                  style="background-color: {{ $bloco['badge']['hex'] }}"
                                  @if(!empty($bloco['mensagem'])) title="{{ $bloco['mensagem'] }}" @endif>
                                {{ $bloco['badge']['label'] }}
                            </span>
                        @endif
                    </div>
                    <div class="px-3 py-2.5 space-y-2.5">
                        @if(!empty($bloco['itens']))
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                                @foreach($bloco['itens'] as $item)
                                    <div class="min-w-0">
                                        <dt class="text-[9px] text-gray-400 uppercase tracking-wider">{{ $item['label'] }}</dt>
                                        <dd class="text-[12px] text-gray-800 font-medium break-words mt-0.5">
                                            @if(!empty($item['tooltip']))
                                                <span class="underline decoration-dotted cursor-help" title="{{ $item['tooltip'] }}">{{ $item['valor'] }}</span>
                                            @else
                                                {{ $item['valor'] }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @foreach($bloco['listas'] as $lista)
                            <div>
                                <p class="text-[9px] text-gray-400 uppercase tracking-wider mb-1">{{ $lista['titulo'] }}</p>
                                <ul class="space-y-0.5">
                                    @foreach($lista['linhas'] as $linha)
                                        <li class="text-[11px] text-gray-700 leading-snug flex gap-1.5">
                                            <span class="text-gray-300 select-none">▪</span>
                                            <span>{{ $linha }}</span>
                                        </li>
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
@endif
