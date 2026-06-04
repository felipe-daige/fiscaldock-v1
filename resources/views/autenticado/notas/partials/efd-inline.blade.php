@php
    $tipoBadge = $nota->tipo_operacao === 'entrada'
        ? ['label' => 'ENTRADA', 'hex' => '#047857']
        : ['label' => 'SAÍDA', 'hex' => '#d97706'];

    $origemBadge = match($nota->origem_arquivo ?? '') {
        'fiscal' => ['label' => 'EFD ICMS/IPI', 'hex' => '#4338ca'],
        'contribuicoes' => ['label' => 'EFD PIS/COFINS', 'hex' => '#0f766e'],
        default => null,
    };

    // ICMS do C190 (consolidados) — C170 não carrega ICMS no perfil comercial (P2).
    $totalIcms = $nota->consolidados->sum('valor_icms') ?: $nota->itens->sum('valor_icms');
    $totalPis = $nota->itens->sum('valor_pis');
    $totalCofins = $nota->itens->sum('valor_cofins');
    $totalItensValor = $nota->itens->sum('valor_total');
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
                    <p class="text-sm text-gray-700">{{ $nota->data_emissao ? \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Valor Total</p>
                    <p class="text-sm font-mono text-gray-900">R$ {{ $nota->valor_total !== null ? number_format($nota->valor_total, 2, ',', '.') : '—' }}</p>
                </div>
                @if($nota->valor_desconto)
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Desconto</p>
                        <p class="text-sm font-mono text-gray-900">R$ {{ number_format($nota->valor_desconto, 2, ',', '.') }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Classificação</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                        @if($origemBadge)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($nota->chave_acesso)
                <div class="mt-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Chave de Acesso</p>
                    <p class="text-xs font-mono text-gray-700 break-all">{{ implode(' ', str_split($nota->chave_acesso, 4)) }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($nota->participante)
        @php
            $p = $nota->participante;
            $situacaoBadge = null;
            if ($p->situacao_cadastral) {
                $situacaoBadge = strtolower((string) $p->situacao_cadastral) === 'ativa'
                    ? ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#047857']
                    : ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#dc2626'];
            }
        @endphp
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participante</span>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">
                            <a href="/app/participante/{{ $p->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $p->razao_social ?? '—' }}</a>
                        </p>
                        @if($p->nome_fantasia)
                            <p class="text-[11px] text-gray-500 mt-1">{{ $p->nome_fantasia }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($situacaoBadge)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $situacaoBadge['hex'] }}">{{ $situacaoBadge['label'] }}</span>
                        @endif
                        @if($p->regime_tributario)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">{{ strtoupper($p->regime_tributario) }}</span>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJ / CPF</p>
                        <p class="text-sm font-mono text-gray-700">{{ $p->cnpj_formatado ?? '—' }}</p>
                    </div>
                    @if($p->uf)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">UF</p>
                            <p class="text-sm text-gray-700">{{ $p->uf }}</p>
                        </div>
                    @endif
                    @if($p->municipio)
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Município</p>
                            <p class="text-sm text-gray-700">{{ $p->municipio }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

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

    @php
        $itensExibir = $nota->itensDetalhe();
        $viaTwin = $nota->itensViaTwin();
        $catalogoMap = $nota->catalogoPorItem();
        $tabValor = $itensExibir->sum('valor_total');
        $tabIcms = $itensExibir->sum('valor_icms');
        $tabPis = $itensExibir->sum('valor_pis');
        $tabCofins = $itensExibir->sum('valor_cofins');
        $aliqDiv = fn ($cat, $item) => $cat && $cat->aliq_icms !== null && $item->aliquota_icms !== null
            && (float) $item->aliquota_icms > 0 && abs((float) $cat->aliq_icms - (float) $item->aliquota_icms) > 0.01;
    @endphp
    @if($itensExibir->isNotEmpty())
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-2">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Itens × Catálogo</span>
                @if($viaTwin)<span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded" title="A saída fiscal foi escriturada por C190 (consolidado); o detalhe por produto vem da EFD PIS/COFINS da mesma chave.">itens via EFD PIS/COFINS</span>@endif
            </div>

            <div class="p-3 grid grid-cols-1 lg:grid-cols-2 gap-2.5">
                @foreach($itensExibir as $item)
                    @php $cat = $catalogoMap[$item->codigo_item] ?? null; $div = $aliqDiv($cat, $item); @endphp
                    <div class="border border-gray-200 rounded-md p-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 leading-snug">{{ $item->descricao ?: ($cat?->descr_item ?? '—') }}</p>
                                <p class="text-[10px] text-gray-500 font-mono mt-0.5">#{{ $item->numero_item ?? '—' }} · {{ $item->codigo_item ?? '—' }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                @if($cat && $cat->cod_ncm)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #4338ca" title="NCM do catálogo">{{ $cat->cod_ncm }}</span>
                                @elseif($cat && $cat->exigeNcm())
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #b45309" title="Tipo {{ $cat->tipo_item }} ({{ $cat->tipo_label }}) é mercadoria/produto — NCM deveria estar preenchido no 0200">NCM faltando</span>
                                @elseif($cat)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium text-gray-600" style="background-color: #f3f4f6" title="Tipo {{ $cat->tipo_item }} ({{ $cat->tipo_label }}) — NCM não é exigido p/ este tipo de item">não exige NCM</span>
                                @else
                                    <span class="text-amber-600 text-[10px] font-semibold" title="Sem catálogo 0200">sem cat.</span>
                                @endif
                                @if($div)<span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #b45309" title="Alíquota do item difere do catálogo">alíq ≠</span>@endif
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-3 gap-y-1 mt-2 text-[11px]">
                            <div><span class="text-gray-400">Qtd</span> <span class="text-gray-700">{{ $item->quantidade !== null ? number_format($item->quantidade, 2, ',', '.') : '—' }} {{ $item->unidade_medida ?? '' }}</span></div>
                            <div><span class="text-gray-400">CFOP</span> <span class="font-mono text-gray-700">{{ $item->cfop ?? '—' }}</span></div>
                            <div><span class="text-gray-400">Alíq</span> <span class="font-mono {{ $div ? 'text-amber-700 font-semibold' : 'text-gray-700' }}">{{ $item->aliquota_icms !== null ? number_format((float)$item->aliquota_icms, 1, ',', '.') : '—' }}@if($cat && $cat->aliq_icms !== null)<span class="text-gray-400">/{{ number_format((float)$cat->aliq_icms, 1, ',', '.') }}</span>@endif</span></div>
                            <div><span class="text-gray-400">Total</span> <span class="font-mono font-semibold text-gray-900">{{ $item->valor_total !== null ? number_format($item->valor_total, 2, ',', '.') : '—' }}</span></div>
                            <div><span class="text-gray-400">Unit.</span> <span class="font-mono text-gray-700">{{ $item->valor_unitario !== null ? number_format($item->valor_unitario, 2, ',', '.') : '—' }}</span></div>
                            <div><span class="text-gray-400">ICMS</span> <span class="font-mono text-gray-700">{{ $item->valor_icms !== null ? number_format($item->valor_icms, 2, ',', '.') : '—' }}</span></div>
                            <div><span class="text-gray-400">PIS</span> <span class="font-mono text-gray-700">{{ $item->valor_pis !== null ? number_format($item->valor_pis, 2, ',', '.') : '—' }}</span></div>
                            <div><span class="text-gray-400">COFINS</span> <span class="font-mono text-gray-700">{{ $item->valor_cofins !== null ? number_format($item->valor_cofins, 2, ',', '.') : '—' }}</span></div>
                        </div>
                        @if($cat)
                            <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="mt-2 text-[11px] font-medium text-indigo-600 hover:underline">Ver catálogo ▾</button>
                            <div class="hidden mt-2 pt-2 border-t border-gray-100 text-[11px] space-y-2">
                                <p class="text-gray-700">{{ $cat->descr_item }}</p>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-gray-500">
                                    <span>NCM <span class="font-mono text-gray-700">{{ $cat->cod_ncm ?: '—' }}</span></span>
                                    <span>Tipo <span class="text-gray-700">{{ $cat->tipo_item }} · {{ $cat->tipo_label }}</span></span>
                                    <span>Unidade <span class="text-gray-700">{{ $cat->unid_inv ?: '—' }}</span></span>
                                    <span>Alíq. cat. <span class="text-gray-700">{{ $cat->aliq_icms !== null ? number_format((float)$cat->aliq_icms, 2, ',', '.') . '%' : '—' }}</span></span>
                                    <span>Cód. barras <span class="font-mono text-gray-700">{{ $cat->cod_barra ?: '—' }}</span></span>
                                    <span>Cód. genérico <span class="font-mono text-gray-700">{{ $cat->cod_gen ?: '—' }}</span></span>
                                </div>
                                <button type="button" data-cat-hist="{{ $cat->cod_item }}" data-cat-cliente="{{ $cat->cliente_id }}" class="font-medium text-indigo-600 hover:underline">Histórico, movimentação e drift ▾</button>
                                <div class="cat-hist-panel hidden border border-gray-200 rounded bg-gray-50/50"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-[11px]">
                <span class="text-gray-500">Total itens <span class="font-mono font-semibold text-gray-900">R$ {{ number_format($tabValor, 2, ',', '.') }}</span></span>
                <span class="text-gray-500">ICMS <span class="font-mono text-gray-700">{{ number_format($tabIcms, 2, ',', '.') }}</span></span>
                <span class="text-gray-500">PIS <span class="font-mono text-gray-700">{{ number_format($tabPis, 2, ',', '.') }}</span></span>
                <span class="text-gray-500">COFINS <span class="font-mono text-gray-700">{{ number_format($tabCofins, 2, ',', '.') }}</span></span>
            </div>
        </div>
    @elseif($nota->consolidados->isNotEmpty())
        {{-- Saída escriturada só por C190 (sem C170 e sem gêmea PIS/COFINS): mostra o consolidado --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consolidado (C190)</span>
                <span class="text-[10px] text-gray-400">sem detalhe por item / catálogo</span>
            </div>
            <div class="p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                @foreach($nota->consolidados as $c)
                    <div class="border border-gray-200 rounded-md p-2.5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #4338ca">CFOP {{ $c->cfop ?? '—' }}</span>
                            <span class="text-[11px] text-gray-500">CST {{ $c->cst_icms ?? '—' }} · {{ $c->aliquota_icms !== null ? number_format((float)$c->aliquota_icms, 2, ',', '.') . '%' : '—' }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div><p class="text-[9px] uppercase text-gray-400">Operação</p><p class="text-xs font-mono font-semibold text-gray-900">{{ number_format((float)($c->valor_operacao ?? 0), 2, ',', '.') }}</p></div>
                            <div><p class="text-[9px] uppercase text-gray-400">ICMS</p><p class="text-xs font-mono text-gray-700">{{ number_format((float)($c->valor_icms ?? 0), 2, ',', '.') }}</p></div>
                            <div><p class="text-[9px] uppercase text-gray-400">ICMS ST</p><p class="text-xs font-mono text-gray-700">{{ number_format((float)($c->valor_icms_st ?? 0), 2, ',', '.') }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded border border-gray-300 p-4">
            <p class="text-sm text-gray-500">Nenhum item registrado para esta nota.</p>
        </div>
    @endif

    <div class="flex justify-end pt-2 border-t border-gray-200">
        <a href="/app/notas/efd/{{ $nota->id }}" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 hover:text-gray-900 hover:underline">
            Ver detalhes completos
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
