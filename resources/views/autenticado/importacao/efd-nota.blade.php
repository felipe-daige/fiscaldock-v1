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

    $totalIcms = $nota->itens->sum('valor_icms');
    $totalPis = $nota->itens->sum('valor_pis');
    $totalCofins = $nota->itens->sum('valor_cofins');
    $totalTributos = $totalIcms + $totalPis + $totalCofins;
    $totalItensValor = $nota->itens->sum('valor_total');
    $temTributos = $totalTributos > 0;
@endphp

<div class="bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <a href="/app/notas-fiscais" data-link class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-gray-900 hover:underline">
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
                    <p class="text-[11px] text-gray-500">Soma de ICMS, PIS e COFINS dos itens</p>
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

            @if($nota->itens->isNotEmpty())
                <div class="md:hidden divide-y divide-gray-100">
                    @foreach($nota->itens as $item)
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900">{{ $item->descricao ?? '—' }}</p>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Item {{ $item->numero_item ?? '—' }} · Cod. {{ $item->codigo_item ?? '—' }}
                                    </p>
                                </div>
                                @if($item->cfop)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">{{ $item->cfop }}</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 mt-3">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Quantidade</p>
                                    <p class="text-sm text-gray-700">{{ $item->quantidade !== null ? number_format($item->quantidade, 2, ',', '.') : '—' }} {{ $item->unidade_medida ?? '' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Unitario</p>
                                    <p class="text-sm text-gray-700">R$ {{ $item->valor_unitario !== null ? number_format($item->valor_unitario, 2, ',', '.') : '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Total</p>
                                    <p class="text-sm font-mono font-semibold text-gray-900">{{ $item->valor_total !== null ? number_format($item->valor_total, 2, ',', '.') : '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Tributos</p>
                                    <p class="text-sm text-gray-700">
                                        ICMS {{ $item->valor_icms !== null ? number_format($item->valor_icms, 2, ',', '.') : '—' }}
                                        / PIS {{ $item->valor_pis !== null ? number_format($item->valor_pis, 2, ',', '.') : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">N</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Codigo</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descricao</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Qtd</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UN</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Vlr Unit.</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Vlr Total</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CFOP</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CST ICMS</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">ICMS</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">PIS</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">COFINS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($nota->itens as $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-2 text-sm text-gray-700">{{ $item->numero_item ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm font-mono text-gray-700">{{ $item->codigo_item ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700 max-w-xs truncate">{{ $item->descricao ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right text-gray-700">{{ $item->quantidade !== null ? number_format($item->quantidade, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-700">{{ $item->unidade_medida ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_unitario !== null ? number_format($item->valor_unitario, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm font-semibold text-gray-900 text-right font-mono">{{ $item->valor_total !== null ? number_format($item->valor_total, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-center font-mono text-gray-700">{{ $item->cfop ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-center text-gray-700">{{ $item->cst_icms ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_icms !== null ? number_format($item->valor_icms, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_pis !== null ? number_format($item->valor_pis, 2, ',', '.') : '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ $item->valor_cofins !== null ? number_format($item->valor_cofins, 2, ',', '.') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 bg-gray-50">
                                <td class="px-3 py-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wide" colspan="6">Total</td>
                                <td class="px-3 py-2 text-sm font-semibold text-gray-900 text-right font-mono">{{ number_format($totalItensValor, 2, ',', '.') }}</td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalIcms, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalPis, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-right font-mono text-gray-700">{{ number_format($totalCofins, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="px-4 py-6 text-sm text-gray-500">
                    Nenhum item registrado para esta nota.
                </div>
            @endif
        </div>
    </div>
</div>
