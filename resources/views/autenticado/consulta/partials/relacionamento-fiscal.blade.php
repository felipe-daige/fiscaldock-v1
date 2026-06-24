{{-- Panorama fiscal do CNPJ (cliente OU participante) no acervo EFD.
     Shape único de ParticipanteFiscalResumoService / ClienteFiscalResumoService, ou null. --}}
@php($fiscal = $fiscal ?? null)
@php($papelHex = ['fornecedor' => '#2563eb', 'cliente' => '#0f766e', 'ambos' => '#7c3aed'])
@php($papelLabel = ['fornecedor' => 'Fornecedor', 'cliente' => 'Cliente', 'ambos' => 'Fornecedor e cliente'])

<div class="mt-3 border border-gray-200 rounded bg-white overflow-hidden">
    <div class="px-3 py-2 bg-gray-50 border-b border-gray-200">
        <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">Relacionamento &amp; Movimentação Fiscal</span>
    </div>

    @if(empty($fiscal))
        <div class="px-3 py-2.5">
            <p class="text-xs text-gray-500">Sem movimentação no acervo fiscal (EFD) deste CNPJ.</p>
        </div>
    @else
        <div class="px-3 py-2.5 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                @if(!empty($fiscal['papel']))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                          style="background-color: {{ $papelHex[$fiscal['papel']] ?? '#374151' }}">{{ $papelLabel[$fiscal['papel']] ?? '—' }}</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                          style="background-color: #475569">Acervo próprio</span>
                @endif
                @if(!empty($fiscal['primeira_nota']) && !empty($fiscal['ultima_nota']))
                    <span class="text-[11px] text-gray-500 font-mono">
                        {{ \Carbon\Carbon::parse($fiscal['primeira_nota'])->format('m/Y') }}
                        – {{ \Carbon\Carbon::parse($fiscal['ultima_nota'])->format('m/Y') }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="rounded border border-gray-100 bg-gray-50 px-2.5 py-1.5">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Comprado (entradas)</p>
                    <p class="text-sm font-bold text-gray-900 font-mono">R$ {{ number_format($fiscal['total_comprado'], 2, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400">{{ $fiscal['qtd_entrada'] }} nota(s)</p>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 px-2.5 py-1.5">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Vendido (saídas)</p>
                    <p class="text-sm font-bold text-gray-900 font-mono">R$ {{ number_format($fiscal['total_vendido'], 2, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400">{{ $fiscal['qtd_saida'] }} nota(s)</p>
                </div>
            </div>

            @php($pfVisivel = (int) config('consultas.panorama_fiscal.visivel', 10))

            @if(!empty($fiscal['top_produtos']))
                @php($pfProds = collect($fiscal['top_produtos']))
                <div data-pf-list>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">Principais produtos negociados</p>
                        @include('autenticado.consulta.partials._panorama-seletor', ['count' => $pfProds->count(), 'default' => $pfVisivel])
                    </div>
                    <div class="space-y-1">
                        @foreach($pfProds as $i => $p)
                            <div data-pf-row @class(['hidden' => $i >= $pfVisivel])>
                                @include('autenticado.consulta.partials._panorama-produto-linha', ['p' => $p])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($fiscal['relacionamentos']))
                @php($pfRels = collect($fiscal['relacionamentos']))
                <div data-pf-list>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $fiscal['relacionamentos_titulo'] ?? 'Por empresa' }}</p>
                        @include('autenticado.consulta.partials._panorama-seletor', ['count' => $pfRels->count(), 'default' => $pfVisivel])
                    </div>
                    <div class="space-y-1">
                        @foreach($pfRels as $i => $rel)
                            <div data-pf-row @class(['hidden' => $i >= $pfVisivel])>
                                @include('autenticado.consulta.partials._panorama-contraparte-linha', ['rel' => $rel, 'papelHex' => $papelHex, 'papelLabel' => $papelLabel])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($fiscal['top_cfops']))
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[10px] text-gray-400 uppercase tracking-wide">Top CFOPs:</span>
                    @foreach($fiscal['top_cfops'] as $c)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono text-gray-700" style="background-color:#f3f4f6">{{ $c['cfop'] }} <span class="text-gray-400 ml-1">×{{ $c['qtd'] }}</span></span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
