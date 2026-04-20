@php
    $tipoBadge = $nota->tipo_nota === \App\Models\XmlNota::TIPO_ENTRADA
        ? ['label' => strtoupper($nota->tipo_nota_descricao), 'hex' => '#047857']
        : ['label' => strtoupper($nota->tipo_nota_descricao), 'hex' => '#d97706'];

    $chaveFormatada = $nota->nfe_id ? implode(' ', str_split($nota->nfe_id, 4)) : null;

    $temTributos = ($nota->icms_valor ?? 0) > 0 || ($nota->icms_st_valor ?? 0) > 0 ||
        ($nota->pis_valor ?? 0) > 0 || ($nota->cofins_valor ?? 0) > 0 ||
        ($nota->ipi_valor ?? 0) > 0;
@endphp

<div class="px-3 py-3 sm:px-6 sm:py-4 space-y-4">
    <div class="bg-white rounded border border-gray-300 overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados da Nota</span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Data de Emissão</p>
                    <p class="text-sm text-gray-700">{{ $nota->data_emissao ? $nota->data_emissao->format('d/m/Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Valor Total</p>
                    <p class="text-sm font-mono text-gray-900">{{ $nota->valor_formatado }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo</p>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                </div>
                @if($nota->finalidade)
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Finalidade</p>
                        <p class="text-sm text-gray-700">{{ $nota->finalidade_descricao }}</p>
                    </div>
                @endif
            </div>

            @if($nota->natureza_operacao)
                <div class="mt-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Natureza da Operação</p>
                    <p class="text-sm text-gray-700">{{ $nota->natureza_operacao }}</p>
                </div>
            @endif

            @if($chaveFormatada)
                <div class="mt-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Chave de Acesso</p>
                    <p class="text-xs font-mono text-gray-700 break-all">{{ $chaveFormatada }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Emitente</span>
            </div>
            <div class="p-4">
                @if($nota->emit_participante_id)
                    <a href="/app/participante/{{ $nota->emit_participante_id }}" data-link class="text-sm font-semibold text-gray-900 hover:text-gray-600 hover:underline">{{ $nota->emit_razao_social ?? '—' }}</a>
                @else
                    <p class="text-sm font-semibold text-gray-900">{{ $nota->emit_razao_social ?? '—' }}</p>
                @endif
                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJ</p>
                        <p class="text-sm font-mono text-gray-700">{{ $nota->emit_cnpj_formatado ?? '—' }}</p>
                    </div>
                    @if($nota->emit_uf)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">UF</p>
                            <p class="text-sm text-gray-700">{{ $nota->emit_uf }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Destinatário</span>
            </div>
            <div class="p-4">
                @if($nota->dest_participante_id)
                    <a href="/app/participante/{{ $nota->dest_participante_id }}" data-link class="text-sm font-semibold text-gray-900 hover:text-gray-600 hover:underline">{{ $nota->dest_razao_social ?? '—' }}</a>
                @else
                    <p class="text-sm font-semibold text-gray-900">{{ $nota->dest_razao_social ?? '—' }}</p>
                @endif
                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJ</p>
                        <p class="text-sm font-mono text-gray-700">{{ $nota->dest_cnpj_formatado ?? '—' }}</p>
                    </div>
                    @if($nota->dest_uf)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">UF</p>
                            <p class="text-sm text-gray-700">{{ $nota->dest_uf }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($nota->cliente)
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cliente</span>
            </div>
            <div class="p-4">
                <p class="text-sm font-semibold text-gray-900">
                    <a href="/app/cliente/{{ $nota->cliente->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $nota->cliente->razao_social ?? '—' }}</a>
                </p>
                @if($nota->cliente->documento_formatado)
                    <p class="text-[11px] font-mono text-gray-500 mt-1">{{ $nota->cliente->documento_formatado }}</p>
                @endif
            </div>
        </div>
    @endif

    @if($temTributos)
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Tributário</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 divide-x divide-y md:divide-y-0 divide-gray-200">
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">ICMS</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ number_format((float) ($nota->icms_valor ?? 0), 2, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">ICMS ST</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ number_format((float) ($nota->icms_st_valor ?? 0), 2, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">PIS</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ number_format((float) ($nota->pis_valor ?? 0), 2, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">COFINS</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ number_format((float) ($nota->cofins_valor ?? 0), 2, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">IPI</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ number_format((float) ($nota->ipi_valor ?? 0), 2, ',', '.') }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Tributos</p>
                    <p class="text-sm font-mono font-semibold text-gray-900">R$ {{ number_format($nota->total_tributos_calculado, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($nota->chave_referenciada)
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Nota Referenciada</span>
            </div>
            <div class="p-4">
                <p class="text-xs font-mono text-gray-700 break-all">{{ implode(' ', str_split($nota->chave_referenciada, 4)) }}</p>
                @php $notaRef = $nota->notaReferenciada(); @endphp
                @if($notaRef)
                    <a href="/app/notas-fiscais/xml/{{ $notaRef->id }}" data-link class="inline-flex items-center mt-2 text-xs text-gray-700 hover:text-gray-900 hover:underline">
                        Ver nota original: Nº {{ $notaRef->numero_nota }}{{ $notaRef->serie ? '/' . $notaRef->serie : '' }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($nota->isValidada())
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Validação</span>
            </div>
            <div class="p-4 flex flex-wrap items-center gap-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ strtoupper($nota->validacao_classificacao_label) }}</span>
                @if($nota->validacao_score !== null)
                    <span class="text-sm text-gray-700">Score: <span class="font-mono text-gray-900">{{ $nota->validacao_score }}/100</span></span>
                @endif
            </div>
        </div>
    @endif

    <div class="flex justify-end pt-2 border-t border-gray-200">
        <a href="/app/notas-fiscais/xml/{{ $nota->id }}" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 hover:text-gray-900 hover:underline">
            Ver detalhes completos
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
