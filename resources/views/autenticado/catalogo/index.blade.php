@php
    $kpis = $kpis ?? [];
    $itens = $itens ?? collect();
    $clientes = $clientes ?? collect();
    $filtros = $filtros ?? [];
    $cfops = $cfops ?? [];
    $csts_icms = $csts_icms ?? [];
    $paginacao = $paginacao ?? ['total' => 0, 'page' => 1, 'per_page' => 25, 'total_pages' => 1];

    $tipoLabels = [
        '00' => 'Mercadoria p/ Revenda',
        '01' => 'Matéria-Prima',
        '02' => 'Embalagem',
        '03' => 'Produto em Processo',
        '04' => 'Produto Acabado',
        '05' => 'Subproduto',
        '06' => 'Produto Intermediário',
        '07' => 'Material de Uso e Consumo',
        '08' => 'Ativo Imobilizado',
        '09' => 'Serviços',
        '10' => 'Outros Insumos',
        '99' => 'Outras',
    ];
@endphp

<div class="min-h-screen bg-gray-100">
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6" id="catalogo-page">
    {{-- Header --}}
    <div class="mb-4 sm:mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Catálogo de Produtos</h1>
                <p class="text-xs text-gray-500 mt-0.5">Registro 0200 do SPED &mdash; painel fiscal consolidado</p>
            </div>
            <a href="/app/notas-fiscais/dashboard" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline hidden sm:inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    {{-- ═══ BLOCO 1: Resumo Fiscal ═══ --}}
    <div class="border border-gray-300 rounded overflow-hidden mb-4">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Fiscal do Catálogo</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-x divide-gray-200 bg-white">
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Total Produtos</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($kpis['total_produtos'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Com Movimentação</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($kpis['com_movimentacao'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Sem Movimentação</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($kpis['sem_movimentacao'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Valor Movimentado</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">R$ {{ number_format($kpis['valor_movimentado'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Alíq. Divergente</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($kpis['aliq_divergente'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 sm:p-4">
                <p class="text-[10px] uppercase text-gray-400 font-semibold tracking-wide">Sem NCM</p>
                <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($kpis['sem_ncm'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- ═══ Filtros ═══ --}}
    <div class="border border-gray-300 rounded overflow-hidden mb-4">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
        </div>
        <div class="bg-white p-3 sm:p-4">
            <form method="GET" action="/app/catalogo" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                    <select name="cliente_id" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ ($filtros['cliente_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo</label>
                    <select name="tipo_item" class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        @foreach($tipoLabels as $cod => $label)
                        <option value="{{ $cod }}" {{ ($filtros['tipo_item'] ?? '') == $cod ? 'selected' : '' }}>{{ $cod }} - {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">NCM</label>
                    <input type="text" name="ncm" value="{{ $filtros['ncm'] ?? '' }}" placeholder="Ex: 39269090"
                        class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Busca</label>
                    <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="Código, descrição..."
                        class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700 font-medium transition-colors">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ BLOCO 2: Análise Fiscal (gráficos) ═══ --}}
    @php $temCfops = !empty($cfops); $temCsts = !empty($csts_icms); @endphp
    @if($temCfops || $temCsts)
    <div class="border border-gray-300 rounded overflow-hidden mb-4">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Análise Fiscal</span>
        </div>
        <div class="bg-white grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
            <div class="p-4 flex flex-col">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Top 10 CFOPs por Frequência</p>
                <div id="chart-cfops"></div>
                @if($temCfops)
                @php
                    $cfopDict = [
                        '1101' => 'Compra para industrialização',
                        '1102' => 'Compra para comercialização',
                        '1111' => 'Compra p/ industrialização de merc. sujeita a ST',
                        '1113' => 'Compra p/ comercialização de merc. sujeita a ST',
                        '1116' => 'Compra p/ industrialização originada no Mercosul',
                        '1117' => 'Compra p/ comercialização originada no Mercosul',
                        '1120' => 'Compra p/ industrialização originada da Zona Franca',
                        '1121' => 'Compra p/ comercialização originada da Zona Franca',
                        '1124' => 'Industrialização efetuada por outra empresa',
                        '1125' => 'Industrialização efetuada por outra empresa (quando merc. sob ST)',
                        '1126' => 'Compra p/ utilização na prestação de serviço',
                        '1128' => 'Compra p/ utilização na prestação de serviço (merc. sujeita a ST)',
                        '1151' => 'Transferência p/ industrialização',
                        '1152' => 'Transferência p/ comercialização',
                        '1201' => 'Devolução de venda de prod. industrializado',
                        '1202' => 'Devolução de venda de merc. adquirida de terceiros',
                        '1252' => 'Compra de energia elétrica por estab. comercial',
                        '1253' => 'Compra de energia elétrica por estab. prestador de serviço',
                        '1352' => 'Aquisição de serviço de comunicação por estab. comercial',
                        '1353' => 'Aquisição de serviço de comunicação por estab. prestador de serviço',
                        '1401' => 'Compra p/ industrialização em operação c/ merc. sujeita a ST',
                        '1403' => 'Compra de merc. sujeita a ST',
                        '1407' => 'Compra de merc. p/ uso ou consumo c/ ST',
                        '1501' => 'Entrada de merc. recebida com fim específico de exportação',
                        '1551' => 'Compra de bem p/ o ativo imobilizado',
                        '1556' => 'Compra de material p/ uso ou consumo',
                        '1651' => 'Compra de combustível p/ industrialização subsequente',
                        '1652' => 'Compra de combustível p/ comercialização',
                        '1653' => 'Compra de combustível p/ uso ou consumo',
                        '1901' => 'Entrada p/ industrialização por encomenda',
                        '1902' => 'Retorno de merc. remetida p/ industrialização por encomenda',
                        '1903' => 'Entrada de merc. remetida p/ industrialização e não aplicada',
                        '1904' => 'Retorno de remessa p/ venda fora do estabelecimento',
                        '1905' => 'Entrada de merc. recebida p/ depósito em depósito fechado',
                        '1906' => 'Retorno de merc. remetida p/ depósito fechado',
                        '1907' => 'Retorno simbólico de merc. remetida p/ depósito fechado',
                        '1908' => 'Entrada de bem por conta de contrato de comodato',
                        '1909' => 'Retorno de bem remetido por conta de contrato de comodato',
                        '1910' => 'Entrada de bonificação, doação ou brinde',
                        '1911' => 'Entrada de amostra grátis',
                        '1912' => 'Entrada de merc. ou bem recebido p/ demonstração',
                        '1913' => 'Retorno de merc. ou bem remetido p/ demonstração',
                        '1914' => 'Retorno de merc. ou bem remetido p/ exposição ou feira',
                        '1916' => 'Retorno de merc. ou bem remetido p/ conserto ou reparo',
                        '1917' => 'Entrada de merc. recebida em consignação mercantil',
                        '1918' => 'Devolução de merc. remetida em consignação mercantil',
                        '1919' => 'Devolução simbólica em consignação mercantil',
                        '1921' => 'Entrada de merc. recebida p/ consignação industrial',
                        '1922' => 'Devolução de merc. remetida p/ consignação industrial',
                        '1923' => 'Devolução simbólica em consignação industrial',
                        '1924' => 'Entrada p/ industrialização por conta e ordem',
                        '1925' => 'Retorno de merc. remetida p/ industrialização por conta e ordem',
                        '1926' => 'Lançamento efetuado a título de reclassificação de merc.',
                        '1949' => 'Outra entrada de merc. ou prestação de serviço não especificada',
                        '2101' => 'Compra p/ industrialização (interestadual)',
                        '2102' => 'Compra p/ comercialização (interestadual)',
                        '2111' => 'Compra p/ industrialização de merc. sujeita a ST (interestadual)',
                        '2113' => 'Compra p/ comercialização de merc. sujeita a ST (interestadual)',
                        '2116' => 'Compra p/ industrialização originada no Mercosul (interestadual)',
                        '2117' => 'Compra p/ comercialização originada no Mercosul (interestadual)',
                        '2120' => 'Compra p/ industrialização originada da Zona Franca (interestadual)',
                        '2121' => 'Compra p/ comercialização originada da Zona Franca (interestadual)',
                        '2124' => 'Industrialização efetuada por outra empresa (interestadual)',
                        '2125' => 'Industrialização efetuada por outra empresa c/ ST (interestadual)',
                        '2126' => 'Compra p/ utilização na prestação de serviço (interestadual)',
                        '2128' => 'Compra p/ utilização na prestação de serviço c/ ST (interestadual)',
                        '2151' => 'Transferência p/ industrialização (interestadual)',
                        '2152' => 'Transferência p/ comercialização (interestadual)',
                        '2201' => 'Devolução de venda de prod. industrializado (interestadual)',
                        '2202' => 'Devolução de venda de merc. adquirida de terceiros (interestadual)',
                        '2403' => 'Compra de merc. sujeita a ST (interestadual)',
                        '2551' => 'Compra de bem p/ o ativo imobilizado (interestadual)',
                        '2556' => 'Compra de material p/ uso ou consumo (interestadual)',
                        '2651' => 'Compra de combustível p/ industrialização (interestadual)',
                        '2652' => 'Compra de combustível p/ comercialização (interestadual)',
                        '2653' => 'Compra de combustível p/ uso ou consumo (interestadual)',
                        '2910' => 'Entrada de bonificação, doação ou brinde (interestadual)',
                        '2949' => 'Outra entrada de merc. ou prestação de serviço não especificada (interestadual)',
                        '3101' => 'Compra p/ industrialização (exterior)',
                        '3102' => 'Compra p/ comercialização (exterior)',
                        '3127' => 'Compra p/ industrialização sob regime de drawback',
                        '3201' => 'Devolução de venda de prod. industrializado (exterior)',
                        '3202' => 'Devolução de venda de merc. adquirida de terceiros (exterior)',
                        '3551' => 'Compra de bem p/ o ativo imobilizado (exterior)',
                        '3556' => 'Compra de material p/ uso ou consumo (exterior)',
                        '3949' => 'Outra entrada de merc. ou prestação de serviço (exterior)',
                        '5101' => 'Venda de produção do estabelecimento',
                        '5102' => 'Venda de merc. adquirida de terceiros',
                        '5103' => 'Venda de produção a não contribuinte',
                        '5104' => 'Venda de merc. adquirida de terceiros a não contribuinte',
                        '5109' => 'Venda de produção a ordem de terceiros',
                        '5110' => 'Venda de merc. adquirida de terceiros a ordem de terceiros',
                        '5111' => 'Venda de prod. industrializado p/ Zona Franca de Manaus',
                        '5112' => 'Venda de merc. adquirida de terceiros p/ Zona Franca de Manaus',
                        '5113' => 'Venda de produção a não contribuinte p/ Zona Franca de Manaus',
                        '5114' => 'Venda de merc. adquirida de terceiros a não contribuinte p/ ZFM',
                        '5116' => 'Venda de produção originada do Mercosul',
                        '5117' => 'Venda de merc. adquirida de terceiros originada do Mercosul',
                        '5118' => 'Venda de produção a não contribuinte originada do Mercosul',
                        '5119' => 'Venda de merc. adquirida de terceiros a não contribuinte (Mercosul)',
                        '5120' => 'Venda de produção originada da Zona Franca de Manaus',
                        '5122' => 'Venda de produção remetida p/ industrialização',
                        '5124' => 'Industrialização efetuada p/ outra empresa',
                        '5125' => 'Industrialização efetuada p/ outra empresa (merc. sob ST)',
                        '5151' => 'Transferência de produção',
                        '5152' => 'Transferência de merc. adquirida de terceiros',
                        '5201' => 'Devolução de compra p/ industrialização',
                        '5202' => 'Devolução de compra p/ comercialização',
                        '5210' => 'Devolução de compra p/ utilização na prestação de serviço',
                        '5251' => 'Venda de energia elétrica p/ distribuição',
                        '5252' => 'Venda de energia elétrica p/ estab. comercial',
                        '5253' => 'Venda de energia elétrica p/ estab. prestador de serviço',
                        '5401' => 'Venda de produção sujeita a ST',
                        '5402' => 'Venda de produção sujeita a ST p/ comercialização',
                        '5403' => 'Venda de merc. adquirida de terceiros sujeita a ST',
                        '5405' => 'Venda de merc. adquirida de terceiros c/ ICMS retido por ST',
                        '5501' => 'Remessa de prod. industrializado c/ fim específico de exportação',
                        '5502' => 'Remessa de merc. adquirida de terceiros c/ fim de exportação',
                        '5551' => 'Venda de bem do ativo imobilizado',
                        '5556' => 'Devolução de compra de material p/ uso ou consumo',
                        '5651' => 'Venda de combustível p/ industrialização',
                        '5652' => 'Venda de combustível p/ comercialização',
                        '5653' => 'Venda de combustível a consumidor ou usuário final',
                        '5655' => 'Venda de combustível p/ outro estab. do mesmo titular',
                        '5901' => 'Remessa p/ industrialização por encomenda',
                        '5902' => 'Retorno de merc. usada na industrialização por encomenda',
                        '5903' => 'Retorno de merc. recebida p/ industrialização e não aplicada',
                        '5904' => 'Remessa p/ venda fora do estabelecimento',
                        '5905' => 'Remessa p/ depósito fechado ou armazém geral',
                        '5906' => 'Retorno de merc. depositada em depósito fechado',
                        '5907' => 'Retorno simbólico de merc. depositada em depósito fechado',
                        '5908' => 'Remessa de bem por conta de contrato de comodato',
                        '5909' => 'Retorno de bem recebido por conta de contrato de comodato',
                        '5910' => 'Remessa em bonificação, doação ou brinde',
                        '5911' => 'Remessa de amostra grátis',
                        '5912' => 'Remessa de merc. ou bem p/ demonstração',
                        '5913' => 'Retorno de merc. ou bem recebido p/ demonstração',
                        '5914' => 'Remessa de merc. ou bem p/ exposição ou feira',
                        '5916' => 'Remessa de merc. ou bem p/ conserto ou reparo',
                        '5917' => 'Remessa de merc. em consignação mercantil',
                        '5918' => 'Devolução de merc. recebida em consignação mercantil',
                        '5919' => 'Devolução simbólica em consignação mercantil',
                        '5921' => 'Remessa de merc. em consignação industrial',
                        '5922' => 'Devolução de merc. recebida p/ consignação industrial',
                        '5923' => 'Devolução simbólica em consignação industrial',
                        '5924' => 'Remessa p/ industrialização por conta e ordem',
                        '5925' => 'Retorno de merc. recebida p/ industrialização por conta e ordem',
                        '5926' => 'Lançamento a título de reclassificação de merc.',
                        '5929' => 'Lançamento efetuado em decorrência de emissão de doc. fiscal',
                        '5949' => 'Outra saída de merc. ou prestação de serviço não especificada',
                        '6101' => 'Venda de produção (interestadual)',
                        '6102' => 'Venda de merc. adquirida de terceiros (interestadual)',
                        '6103' => 'Venda de produção a não contribuinte (interestadual)',
                        '6104' => 'Venda de merc. adquirida de terceiros a não contribuinte (interestadual)',
                        '6107' => 'Venda de produção p/ não contribuinte (interestadual)',
                        '6108' => 'Venda de merc. adquirida de terceiros p/ não contribuinte (interestadual)',
                        '6109' => 'Venda de produção a ordem de terceiros (interestadual)',
                        '6110' => 'Venda de merc. adquirida a ordem de terceiros (interestadual)',
                        '6116' => 'Venda de produção originada do Mercosul (interestadual)',
                        '6117' => 'Venda de merc. adquirida de terceiros do Mercosul (interestadual)',
                        '6118' => 'Venda de produção a não contribuinte do Mercosul (interestadual)',
                        '6119' => 'Venda de merc. a não contribuinte do Mercosul (interestadual)',
                        '6122' => 'Venda de produção remetida p/ industrialização (interestadual)',
                        '6124' => 'Industrialização efetuada p/ outra empresa (interestadual)',
                        '6151' => 'Transferência de produção (interestadual)',
                        '6152' => 'Transferência de merc. adquirida de terceiros (interestadual)',
                        '6201' => 'Devolução de compra p/ industrialização (interestadual)',
                        '6202' => 'Devolução de compra p/ comercialização (interestadual)',
                        '6401' => 'Venda de produção sujeita a ST (interestadual)',
                        '6403' => 'Venda de merc. sujeita a ST (interestadual)',
                        '6501' => 'Remessa c/ fim específico de exportação (interestadual)',
                        '6551' => 'Venda de bem do ativo imobilizado (interestadual)',
                        '6910' => 'Remessa em bonificação, doação ou brinde (interestadual)',
                        '6929' => 'Lançamento efetuado em decorrência de emissão de doc. fiscal (interestadual)',
                        '6949' => 'Outra saída de merc. ou prestação de serviço (interestadual)',
                        '7101' => 'Venda de produção (exterior)',
                        '7102' => 'Venda de merc. adquirida de terceiros (exterior)',
                        '7501' => 'Exportação de merc. recebida c/ fim específico',
                        '7551' => 'Venda de bem do ativo imobilizado (exterior)',
                        '7949' => 'Outra saída de merc. ou prestação de serviço (exterior)',
                    ];
                @endphp
                <div class="mt-auto pt-3 border-t border-gray-100 space-y-0.5">
                    @foreach($cfops as $cfop)
                    <p class="text-[11px] text-gray-500">
                        <span class="font-semibold text-gray-700">{{ $cfop->cfop }}</span>
                        <span class="mx-0.5">&bull;</span>
                        {{ $cfopDict[$cfop->cfop] ?? 'Código fiscal de operação' }}
                    </p>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="p-4 flex flex-col">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Distribuição CSTs ICMS</p>
                <div id="chart-csts"></div>
                @if($temCsts)
                @php
                    $cstDict = [
                        '00' => 'Tributada integralmente',
                        '10' => 'Tributada e com cobrança do ICMS por ST',
                        '20' => 'Com redução de base de cálculo',
                        '30' => 'Isenta/não tributada e com cobrança do ICMS por ST',
                        '40' => 'Isenta',
                        '41' => 'Não tributada',
                        '50' => 'Suspensão',
                        '51' => 'Diferimento',
                        '60' => 'ICMS cobrado anteriormente por ST',
                        '70' => 'Com redução de BC e cobrança do ICMS por ST',
                        '90' => 'Outras',
                        '101' => 'SN — Tributada com permissão de crédito',
                        '102' => 'SN — Tributada sem permissão de crédito',
                        '103' => 'SN — Isenção do ICMS para faixa de receita bruta',
                        '201' => 'SN — Tributada com permissão de crédito e cobrança por ST',
                        '202' => 'SN — Tributada sem permissão de crédito e cobrança por ST',
                        '300' => 'SN — Imune',
                        '400' => 'SN — Não tributada',
                        '500' => 'SN — ICMS cobrado anteriormente por ST',
                        '900' => 'SN — Outros',
                    ];
                @endphp
                <div class="mt-auto pt-3 border-t border-gray-100 space-y-0.5">
                    @foreach($csts_icms as $cst)
                    <p class="text-[11px] text-gray-500">
                        <span class="font-semibold text-gray-700">CST {{ $cst->cst_icms }}</span>
                        <span class="mx-0.5">&bull;</span>
                        {{ $cstDict[$cst->cst_icms] ?? 'Código de Situação Tributária' }}
                    </p>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ BLOCO 3: Catálogo de Itens ═══ --}}
    @if($itens->isEmpty())
    <div class="border border-gray-300 rounded overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Catálogo de Itens &mdash; Registro 0200</span>
        </div>
        <div class="bg-white py-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-700">Nenhum item encontrado</p>
            <p class="text-xs text-gray-500 mt-1">Ajuste os filtros ou importe arquivos EFD para popular o catálogo</p>
        </div>
    </div>
    @else

    <div class="border border-gray-300 rounded overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Catálogo de Itens &mdash; Registro 0200</span>
            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $paginacao['total'] }}</span>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-0 divide-y divide-gray-100 bg-white">
            @foreach($itens as $item)
            @php
                $aliqCat = $item->aliq_icms !== null ? (float) $item->aliq_icms : null;
                $aliqNotas = $item->aliq_icms_media_notas !== null ? (float) $item->aliq_icms_media_notas : null;
                $divergente = $aliqCat !== null && $aliqNotas !== null && abs($aliqCat - $aliqNotas) > 0.01;
                $semMov = ((int) ($item->total_movimentacoes ?? 0)) === 0;
            @endphp
            <div class="p-3 {{ $divergente ? 'border-l-2 border-l-amber-400' : '' }}">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-mono text-[10px] text-gray-500 uppercase">{{ $item->cod_item }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ Str::limit($item->descr_item, 60) }}</p>
                    </div>
                    @if($divergente)
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">Divergente</span>
                    @elseif($semMov)
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #9ca3af">Sem Mov.</span>
                    @else
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #047857">OK</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div><span class="text-[10px] text-gray-400 uppercase">NCM:</span> <span class="font-mono text-gray-700">{{ $item->cod_ncm ?: '—' }}</span></div>
                    <div><span class="text-[10px] text-gray-400 uppercase">Tipo:</span> <span class="text-gray-700">{{ $tipoLabels[$item->tipo_item] ?? ($item->tipo_item ?: '—') }}</span></div>
                    <div><span class="text-[10px] text-gray-400 uppercase">ICMS Cat.:</span> <span class="text-gray-700">{{ $aliqCat !== null ? number_format($aliqCat, 2, ',', '.') . '%' : '—' }}</span></div>
                    <div><span class="text-[10px] text-gray-400 uppercase">ICMS Notas:</span> <span class="{{ $divergente ? 'text-amber-600 font-semibold' : 'text-gray-700' }}">{{ $aliqNotas !== null ? number_format($aliqNotas, 2, ',', '.') . '%' : '—' }}</span></div>
                    <div><span class="text-[10px] text-gray-400 uppercase">Movim.:</span> <span class="text-gray-700">{{ $item->total_movimentacoes ?? 0 }}</span></div>
                    <div><span class="text-[10px] text-gray-400 uppercase">Valor:</span> <span class="font-mono text-gray-700">{{ !$semMov ? 'R$ ' . number_format((float)($item->valor_movimentado ?? 0), 2, ',', '.') : '—' }}</span></div>
                </div>
                <button onclick="toggleHistorico(this, '{{ $item->cod_item }}', {{ $item->cliente_id }})"
                    class="mt-2 text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver detalhes</button>
                <div class="catalogo-historico hidden mt-2 border-t border-gray-100 pt-2 overflow-x-auto"></div>
            </div>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">NCM</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Alíq. Catálogo</th>
                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Alíq. Notas</th>
                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Movim.</th>
                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor Movim.</th>
                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($itens as $item)
                    @php
                        $aliqCat = $item->aliq_icms !== null ? (float) $item->aliq_icms : null;
                        $aliqNotas = $item->aliq_icms_media_notas !== null ? (float) $item->aliq_icms_media_notas : null;
                        $divergente = $aliqCat !== null && $aliqNotas !== null && abs($aliqCat - $aliqNotas) > 0.01;
                        $semMov = ((int) ($item->total_movimentacoes ?? 0)) === 0;
                    @endphp
                    <tr class="hover:bg-gray-50/50 cursor-pointer catalogo-row transition-colors"
                        onclick="toggleHistorico(this, '{{ $item->cod_item }}', {{ $item->cliente_id }})">
                        <td class="px-3 py-2.5 font-mono text-xs text-gray-700">{{ $item->cod_item }}</td>
                        <td class="px-3 py-2.5 text-gray-700 truncate max-w-[250px]" title="{{ $item->descr_item }}">{{ $item->descr_item }}</td>
                        <td class="px-3 py-2.5 font-mono text-xs">
                            @if($item->cod_ncm)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #4338ca">{{ $item->cod_ncm }}</span>
                            @else
                            <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-xs">
                            @if($item->tipo_item)
                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px]">{{ $tipoLabels[$item->tipo_item] ?? 'Tipo ' . $item->tipo_item }}</span>
                            @else
                            <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs {{ $divergente ? 'text-amber-600 font-semibold' : 'text-gray-700' }}">
                            {{ $aliqCat !== null ? number_format($aliqCat, 2, ',', '.') . '%' : '—' }}
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs {{ $divergente ? 'text-amber-600 font-semibold' : 'text-gray-700' }}">
                            {{ $aliqNotas !== null ? number_format($aliqNotas, 2, ',', '.') . '%' : '—' }}
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            @if($semMov)
                            <span class="text-xs text-gray-400">&mdash;</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #374151">{{ $item->total_movimentacoes }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs font-mono text-gray-700">
                            @if(!$semMov)
                            R$ {{ number_format((float)($item->valor_movimentado ?? 0), 2, ',', '.') }}
                            @else
                            <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            @if($divergente)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">Divergente</span>
                            @elseif($semMov)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #9ca3af">Sem Mov.</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #047857">OK</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="catalogo-historico-row hidden">
                        <td colspan="9" class="p-0 bg-gray-50">
                            <div class="catalogo-historico overflow-x-auto"></div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if($paginacao['total_pages'] > 1)
        <div class="px-4 py-3 border-t border-gray-300 bg-white flex items-center justify-between">
            <span class="text-[10px] text-gray-500 uppercase tracking-wide">Mostrando {{ ($paginacao['page'] - 1) * $paginacao['per_page'] + 1 }}–{{ min($paginacao['page'] * $paginacao['per_page'], $paginacao['total']) }} de {{ $paginacao['total'] }}</span>
            <div class="flex gap-1">
                @if($paginacao['page'] > 1)
                <a href="/app/catalogo?{{ http_build_query(array_merge($filtros, ['page' => $paginacao['page'] - 1])) }}" data-link
                    class="px-2 py-1 text-[10px] border border-gray-300 rounded hover:bg-gray-50 text-gray-700">&laquo;</a>
                @endif
                @for($p = max(1, $paginacao['page'] - 2); $p <= min($paginacao['total_pages'], $paginacao['page'] + 2); $p++)
                @if($p == $paginacao['page'])
                <span class="px-2 py-1 text-[10px] font-bold rounded text-white" style="background-color: #1f2937">{{ $p }}</span>
                @else
                <a href="/app/catalogo?{{ http_build_query(array_merge($filtros, ['page' => $p])) }}" data-link
                    class="px-2 py-1 text-[10px] border border-gray-300 rounded hover:bg-gray-50 text-gray-700">{{ $p }}</a>
                @endif
                @endfor
                @if($paginacao['page'] < $paginacao['total_pages'])
                <a href="/app/catalogo?{{ http_build_query(array_merge($filtros, ['page' => $paginacao['page'] + 1])) }}" data-link
                    class="px-2 py-1 text-[10px] border border-gray-300 rounded hover:bg-gray-50 text-gray-700">&raquo;</a>
                @endif
            </div>
        </div>
        @else
        <div class="px-4 py-3 border-t border-gray-300 bg-white">
            <span class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $paginacao['total'] }} item(ns)</span>
        </div>
        @endif
    </div>
    @endif
</div>
</div>

<script>
(function() {
    function initCatalogo() {
    var historicoCache = {};

    window.toggleHistorico = function(el, codItem, clienteId) {
        var row, histDiv;

        if (el.tagName === 'TR') {
            row = el.nextElementSibling;
            histDiv = row ? row.querySelector('.catalogo-historico') : null;
        } else {
            histDiv = el.parentElement.querySelector('.catalogo-historico');
            row = histDiv;
        }

        if (!row || !histDiv) return;

        if (!row.classList.contains('hidden')) {
            row.classList.add('hidden');
            return;
        }
        row.classList.remove('hidden');

        var cacheKey = codItem + '_' + clienteId;
        if (historicoCache[cacheKey]) {
            histDiv.innerHTML = historicoCache[cacheKey];
            return;
        }

        histDiv.innerHTML = '<p class="p-4 text-xs text-gray-400">Carregando...</p>';

        fetch('/app/catalogo/historico/' + encodeURIComponent(codItem) + '?cliente_id=' + clienteId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            historicoCache[cacheKey] = html;
            histDiv.innerHTML = html;
        })
        .catch(function() {
            histDiv.innerHTML = '<p class="p-4 text-xs text-red-400">Erro ao carregar</p>';
        });
    };

    // SPA form submit
    var form = document.querySelector('#catalogo-page form');
    if (form && window.navegar) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var params = new URLSearchParams(new FormData(form)).toString();
            window.navegar('/app/catalogo?' + params);
        });
    }

    // Charts — guard contra execução dupla
    var cfopsEl = document.getElementById('chart-cfops');
    var cstsEl = document.getElementById('chart-csts');
    if ((cfopsEl && cfopsEl.dataset.rendered) || (cstsEl && cstsEl.dataset.rendered)) return;

    var cfopsData = @json($cfops ?? []);
    var cstsData = @json($csts_icms ?? []);

    if (cfopsData.length && cfopsEl && typeof ApexCharts !== 'undefined') {
        cfopsEl.dataset.rendered = '1';
        new ApexCharts(cfopsEl, {
            chart: { type: 'bar', height: Math.max(200, cfopsData.length * 30), toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Itens', data: cfopsData.map(function(c) { return Number(c.total); }) }],
            xaxis: { categories: cfopsData.map(function(c) { return c.cfop; }), labels: { style: { fontSize: '11px' } } },
            plotOptions: { bar: { horizontal: true, borderRadius: 2, barHeight: '60%' } },
            colors: ['#374151'],
            dataLabels: { enabled: false },
            tooltip: {
                y: { formatter: function(v, opts) {
                    var item = cfopsData[opts.dataPointIndex];
                    return v + ' itens — R$ ' + Number(item.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                }}
            },
            grid: { borderColor: '#e5e7eb' }
        }).render();
    }

    if (cstsData.length && cstsEl && typeof ApexCharts !== 'undefined') {
        cstsEl.dataset.rendered = '1';
        new ApexCharts(cstsEl, {
            chart: { type: 'donut', height: 230, fontFamily: 'inherit' },
            series: cstsData.map(function(c) { return Number(c.total); }),
            labels: cstsData.map(function(c) { return 'CST ' + c.cst_icms; }),
            colors: ['#374151','#047857','#d97706','#dc2626','#7c3aed','#0891b2','#ea580c','#65a30d','#db2777','#4f46e5'],
            legend: { show: false },
            dataLabels: { enabled: true, formatter: function(v) { return v.toFixed(0) + '%'; }, style: { fontSize: '10px' } },
            tooltip: { y: { formatter: function(v) { return v + ' itens'; } } },
            plotOptions: { pie: { donut: { size: '55%' } } }
        }).render();
    }
    } // end initCatalogo

    if (typeof ApexCharts !== 'undefined') {
        initCatalogo();
    } else {
        var s = document.createElement('script');
        s.src = '/js/apexcharts.min.js';
        s.onload = initCatalogo;
        document.head.appendChild(s);
    }
})();
</script>
