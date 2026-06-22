{{-- Relacionamento comercial + movimentação fiscal do CNPJ no acervo EFD.
     Espera: $fiscal (estrutura de ParticipanteFiscalResumoService) ou null. --}}
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
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                      style="background-color: {{ $papelHex[$fiscal['papel']] ?? '#374151' }}">{{ $papelLabel[$fiscal['papel']] ?? '—' }}</span>
                @if($fiscal['primeira_nota'] && $fiscal['ultima_nota'])
                    <span class="text-[11px] text-gray-500">
                        {{ \Carbon\Carbon::parse($fiscal['primeira_nota'])->format('m/Y') }}
                        – {{ \Carbon\Carbon::parse($fiscal['ultima_nota'])->format('m/Y') }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="rounded border border-gray-100 bg-gray-50 px-2.5 py-1.5">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Comprado (entradas)</p>
                    <p class="text-sm font-bold text-gray-900">R$ {{ number_format($fiscal['total_comprado'], 2, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400">{{ $fiscal['qtd_entrada'] }} nota(s)</p>
                </div>
                <div class="rounded border border-gray-100 bg-gray-50 px-2.5 py-1.5">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Vendido (saídas)</p>
                    <p class="text-sm font-bold text-gray-900">R$ {{ number_format($fiscal['total_vendido'], 2, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400">{{ $fiscal['qtd_saida'] }} nota(s)</p>
                </div>
            </div>

            @if(!empty($fiscal['relacionamentos']))
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Por empresa</p>
                    <div class="space-y-1">
                        @foreach($fiscal['relacionamentos'] as $rel)
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span class="truncate text-gray-700" title="{{ $rel['empresa_nome'] }}">
                                    {{ $rel['empresa_nome'] }}@if($rel['is_empresa_propria']) <span class="text-gray-400">(própria)</span>@endif
                                </span>
                                <span class="whitespace-nowrap">
                                    <span style="color: {{ $papelHex[$rel['papel']] ?? '#374151' }}" class="font-semibold">{{ $papelLabel[$rel['papel']] ?? '—' }}</span>
                                    · R$ {{ number_format($rel['valor_entrada'] + $rel['valor_saida'], 2, ',', '.') }}
                                </span>
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
