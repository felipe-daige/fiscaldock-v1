@php
    $tipoBadge = $nota->tipo_operacao === 'entrada'
        ? ['label' => 'ENTRADA', 'hex' => '#047857']
        : ['label' => 'SAIDA', 'hex' => '#d97706'];

    $modeloLabel = match($nota->modelo) {
        '00' => 'NFS-e',
        '55' => 'NF-e',
        '65' => 'NFC-e',
        '57' => 'CT-e',
        '67' => 'CT-e OS',
        '01' => 'Nota Fiscal',
        '1B' => 'Nota Fiscal Avulsa',
        '04' => 'Nota Fiscal de Produtor',
        default => $nota->modelo ? 'MODELO ' . $nota->modelo : 'DOCUMENTO FISCAL',
    };

    $origemBadge = match($nota->origem_arquivo ?? '') {
        'fiscal' => ['label' => 'EFD ICMS/IPI', 'hex' => '#4338ca'],
        'contribuicoes' => ['label' => 'EFD PIS/COFINS', 'hex' => '#0f766e'],
        default => ['label' => 'EFD', 'hex' => '#374151'],
    };

    $subtitulo = match($nota->modelo) {
        '55' => 'Nota Fiscal Eletronica',
        '65' => 'Nota Fiscal de Consumidor Eletronica',
        '57' => 'Conhecimento de Transporte Eletronico',
        '67' => 'Conhecimento de Transporte para Outros Servicos',
        '00' => 'Nota Fiscal de Servico Eletronica',
        default => ($nota->origem_arquivo ?? '') === 'contribuicoes' ? 'Documento de servico escriturado via EFD' : 'Documento fiscal escriturado via EFD',
    };

    // ICMS do C190 (consolidados) — no perfil comercial o C170 não carrega ICMS (P2).
    // Fallback aos itens só quando não há C190 (NF-e antiga sem consolidado).
    $totalIcms = $nota->consolidados->sum('valor_icms') ?: $nota->itens->sum('valor_icms');
    $totalPis = $nota->itens->sum('valor_pis');
    $totalCofins = $nota->itens->sum('valor_cofins');
    $totalTributos = $totalIcms + $totalPis + $totalCofins;
    $totalItensValor = $nota->itens->sum('valor_total');
    $temTributos = $totalTributos > 0;
@endphp

<div class="bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <a href="/app/notas" data-link class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-gray-900 hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Voltar para Notas Fiscais
            </a>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">
                        Nota Fiscal {{ $nota->numero ?? 'Sem numero' }}{{ $nota->serie ? ' / Serie ' . $nota->serie : '' }}
                    </h1>
                    <p class="text-xs text-gray-500">{{ $subtitulo }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoBadge['hex'] }}">{{ $tipoBadge['label'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ strtoupper($modeloLabel) }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo da Nota</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emissao</p>
                    <p class="text-lg font-bold text-gray-900">{{ $nota->data_emissao ? \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') : '—' }}</p>
                    <p class="text-[11px] text-gray-500">{{ $nota->data_emissao ? 'Documento escriturado no periodo' : 'Data nao informada' }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</p>
                    <p class="text-lg font-bold text-gray-900">R$ {{ $nota->valor_total !== null ? number_format($nota->valor_total, 2, ',', '.') : '—' }}</p>
                    <p class="text-[11px] text-gray-500">Total contabilizado da nota</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Itens</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($nota->itens->count(), 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500">{{ $nota->itens->count() === 1 ? 'Item escriturado' : 'Itens escriturados' }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Desconto</p>
                    <p class="text-lg font-bold text-gray-900">R$ {{ number_format((float) ($nota->valor_desconto ?? 0), 2, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500">Valor total de descontos</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tributos</p>
                    <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalTributos, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500">ICMS do consolidado (C190) + PIS/COFINS dos itens</p>
                </div>
            </div>
            @if($nota->chave_acesso)
                <div class="px-4 py-3 border-t border-gray-200">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Chave de Acesso</p>
                    <p class="text-xs font-mono text-gray-700 break-all">{{ implode(' ', str_split($nota->chave_acesso, 4)) }}</p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 {{ $nota->participante && $nota->cliente ? 'xl:grid-cols-3' : 'lg:grid-cols-2' }} gap-4 mb-4">
            @if($nota->participante)
                @php
                    $p = $nota->participante;
                    $situacaoBadge = null;
                    if ($p->situacao_cadastral) {
                        $situacaoBadge = strtolower((string) $p->situacao_cadastral) === 'ativa'
                            ? ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#047857']
                            : ['label' => strtoupper($p->situacao_cadastral), 'hex' => '#dc2626'];
                    }
                    $regimeBadge = $p->regime_tributario
                        ? ['label' => strtoupper($p->regime_tributario), 'hex' => '#0f766e']
                        : null;
                    $municipioUf = collect([$p->municipio, $p->uf])->filter()->implode(' / ');
                    $endereco = collect([$p->endereco, $p->numero, $p->bairro])->filter()->implode(', ');
                @endphp
                <div class="{{ $nota->cliente ? 'xl:col-span-2' : '' }} bg-white rounded border border-gray-300 overflow-hidden">
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
                                @if($regimeBadge)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $regimeBadge['hex'] }}">{{ $regimeBadge['label'] }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4">
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJ / CPF</p>
                                <p class="text-sm font-mono text-gray-700">{{ $p->cnpj_formatado ?? '—' }}</p>
                            </div>
                            @if($p->inscricao_estadual)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Inscricao Estadual</p>
                                    <p class="text-sm font-mono text-gray-700">{{ $p->inscricao_estadual }}</p>
                                </div>
                            @endif
                            @if($municipioUf)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Municipio / UF</p>
                                    <p class="text-sm text-gray-700">{{ $municipioUf }}</p>
                                </div>
                            @endif
                            @if($p->cep)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CEP</p>
                                    <p class="text-sm font-mono text-gray-700">{{ preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', preg_replace('/\D/', '', $p->cep)) }}</p>
                                </div>
                            @endif
                            @if($p->cnae_principal)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNAE Principal</p>
                                    <p class="text-sm text-gray-700">{{ $p->cnae_principal }}</p>
                                </div>
                            @endif
                            @if($p->porte)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Porte</p>
                                    <p class="text-sm text-gray-700">{{ $p->porte }}</p>
                                </div>
                            @endif
                        </div>

                        @if($endereco || $p->capital_social || $p->data_inicio_atividade)
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200">
                                @if($endereco)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Endereco</p>
                                        <p class="text-sm text-gray-700">{{ $endereco }}</p>
                                    </div>
                                @endif
                                @if($p->capital_social)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Capital Social</p>
                                        <p class="text-sm text-gray-700">R$ {{ number_format((float) $p->capital_social, 2, ',', '.') }}</p>
                                    </div>
                                @endif
                                @if($p->data_inicio_atividade)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Inicio de Atividade</p>
                                        <p class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($p->data_inicio_atividade)->format('d/m/Y') }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($nota->cliente)
                @php
                    $clienteSituacaoHex = match (strtolower((string) ($nota->cliente->situacao_cadastral ?? ''))) {
                        'ativa' => '#047857',
                        '' => null,
                        default => '#dc2626',
                    };
                    $clienteMunicipioUf = collect([$nota->cliente->municipio, $nota->cliente->uf])->filter()->implode(' / ');
                @endphp
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cliente</span>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    <a href="/app/cliente/{{ $nota->cliente->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">{{ $nota->cliente->razao_social ?? '—' }}</a>
                                </p>
                                @if($nota->cliente->documento_formatado)
                                    <p class="text-[11px] font-mono text-gray-500 mt-1">{{ $nota->cliente->documento_formatado }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($clienteSituacaoHex)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $clienteSituacaoHex }}">
                                        {{ strtoupper($nota->cliente->situacao_cadastral) }}
                                    </span>
                                @endif
                                @if($nota->cliente->regime_tributario)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">
                                        {{ strtoupper($nota->cliente->regime_tributario) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-4">
                            @if($clienteMunicipioUf)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Municipio / UF</p>
                                    <p class="text-sm text-gray-700">{{ $clienteMunicipioUf }}</p>
                                </div>
                            @endif
                            @if($nota->cliente->email)
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Email</p>
                                    <p class="text-sm text-gray-700">{{ $nota->cliente->email }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if($temTributos)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Tributario</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-200">
                    <div class="p-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">ICMS</p>
                        <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalIcms, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">PIS</p>
                        <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalPis, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">COFINS</p>
                        <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalCofins, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</p>
                        <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalTributos, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Itens</span>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $nota->itens->count() }} {{ $nota->itens->count() === 1 ? 'item registrado' : 'itens registrados' }}</p>
                </div>
            </div>

            @php
                $itensExibir = $nota->itensDetalhe();
                $viaTwin = $nota->itensViaTwin();
                $catalogoMap = $nota->catalogoPorItem();
                $tabValor = $itensExibir->sum('valor_total');
                $tabIcms = $itensExibir->sum('valor_icms');
                $tabPis = $itensExibir->sum('valor_pis');
                $tabCofins = $itensExibir->sum('valor_cofins');
                $aliqDiv = fn ($cat, $item) => $cat && $cat->aliq_icms !== null && $item->aliquota_icms !== null && (float) $item->aliquota_icms > 0 && abs((float) $cat->aliq_icms - (float) $item->aliquota_icms) > 0.01;
            @endphp
            @if($itensExibir->isNotEmpty())
                @if($viaTwin)<div class="px-4 py-2 bg-amber-50 border-b border-amber-200 text-[11px] text-amber-700">Itens detalhados via EFD PIS/COFINS (a saída fiscal foi escriturada por C190).</div>@endif
                <div class="p-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5">
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
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 mt-2 text-[11px]">
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
            @elseif($nota->consolidados->isNotEmpty())
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-[11px] text-gray-500">Escriturada por C190 (consolidado) — sem detalhe por item / catálogo.</div>
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
            @else
                <div class="px-4 py-6 text-sm text-gray-500">
                    Nenhum item registrado para esta nota.
                </div>
            @endif
        </div>
    </div>
</div>
