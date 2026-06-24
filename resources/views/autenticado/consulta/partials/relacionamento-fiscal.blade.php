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

            @if(!empty($fiscal['top_produtos']))
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Principais produtos negociados</p>
                    <div class="space-y-1">
                        @foreach($fiscal['top_produtos'] as $p)
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span class="truncate text-gray-700" title="{{ $p['descricao'] }}">
                                    {{ $p['descricao'] }}@if(!empty($p['ncm'])) <span class="font-mono text-gray-400">NCM {{ $p['ncm'] }}</span>@endif
                                </span>
                                <span class="whitespace-nowrap font-mono text-gray-600">
                                    R$ {{ number_format($p['valor'], 2, ',', '.') }} <span class="text-gray-400">×{{ $p['qtd'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($fiscal['relacionamentos']))
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">{{ $fiscal['relacionamentos_titulo'] ?? 'Por empresa' }}</p>
                    <div class="space-y-1">
                        @foreach($fiscal['relacionamentos'] as $rel)
                            @php($relNome = $rel['nome'] ?? $rel['empresa_nome'] ?? '—')
                            @php($relPropria = $rel['is_propria'] ?? $rel['is_empresa_propria'] ?? false)
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span class="truncate text-gray-700" title="{{ $relNome }}">
                                    {{ $relNome }}@if($relPropria) <span class="text-gray-400">(própria)</span>@endif
                                </span>
                                <span class="whitespace-nowrap">
                                    <span style="color: {{ $papelHex[$rel['papel']] ?? '#374151' }}" class="font-semibold">{{ $papelLabel[$rel['papel']] ?? '—' }}</span>
                                    · <span class="font-mono">R$ {{ number_format(($rel['valor_entrada'] ?? 0) + ($rel['valor_saida'] ?? 0), 2, ',', '.') }}</span>
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
