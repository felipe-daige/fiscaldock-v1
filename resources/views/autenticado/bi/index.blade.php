{{-- BI Fiscal - Dashboard --}}
<div class="min-h-screen bg-gray-100" id="bi-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Page Header --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">BI Fiscal</h1>
                    <p class="mt-1 text-xs text-gray-500">Analise o desempenho fiscal e tributário das suas operações.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                    {{-- Filtro de Cliente --}}
                    <select id="filtro-cliente" class="w-full sm:w-auto px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os Clientes</option>
                        @foreach($clientes ?? [] as $cliente)
                            @if($cliente->is_empresa_propria)
                                <option value="{{ $cliente->id }}">★ {{ $cliente->nome }} (Minha Empresa)</option>
                            @else
                                <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                            @endif
                        @endforeach
                    </select>
                    {{-- Filtro de Periodo --}}
                    <select id="filtro-periodo" class="w-full sm:w-auto px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="0">Todos os períodos</option>
                        <option value="12">Últimos 12 meses</option>
                        <option value="6">Últimos 6 meses</option>
                        <option value="3">Últimos 3 meses</option>
                        <option value="1">Este mês</option>
                    </select>
                    {{-- Exports — <x-download-button> (onclick inline cache-robusto + spinner) --}}
                    @php $dataArq = now()->format('Ymd'); @endphp
                    <button type="button"
                            onclick="document.getElementById('modal-export-bi-pdf').classList.remove('hidden')"
                            class="w-full sm:w-auto px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">PDF</button>
                    <button type="button"
                            onclick="document.getElementById('modal-export-bi').classList.remove('hidden')"
                            class="w-full sm:w-auto px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">Planilha</button>
                </div>
            </div>
        </div>

        {{-- Overlay de download (spinner) — compartilhado pelos botões de export --}}
        <x-download-overlay id="download-overlay-bi" texto="Gerando relatório…" />

        {{-- Modal de export de planilha (XLSX completo ou CSV por seção em ZIP) --}}
        <x-modal id="modal-export-bi" titulo="Exportar planilha">
            <p class="text-[13px] text-gray-600 mb-4">Escolha o formato. O período e o cliente seguem os filtros selecionados.</p>
            <div class="space-y-2">
                <x-download-button path="/app/bi/exportar-xlsx" filename="bi-fiscal-{{ $dataArq }}.xlsx"
                                   overlay="download-overlay-bi"
                                   extraOnDone="document.getElementById('modal-export-bi').classList.add('hidden');"
                                   class="block w-full text-left px-4 py-3 rounded border border-gray-300 hover:bg-gray-50">
                    <span class="block text-sm font-semibold text-gray-900">Excel (XLSX)</span>
                    <span class="block text-[12px] text-gray-500">Relatório completo, uma aba por seção (Resumo, Cobertura, Faturamento, Tributos, Declarado×Computado, CFOP).</span>
                </x-download-button>
                <x-download-button path="/app/bi/exportar-csv-zip" filename="bi-fiscal-{{ $dataArq }}.csv.zip"
                                   overlay="download-overlay-bi"
                                   extraOnDone="document.getElementById('modal-export-bi').classList.add('hidden');"
                                   class="block w-full text-left px-4 py-3 rounded border border-gray-300 hover:bg-gray-50">
                    <span class="block text-sm font-semibold text-gray-900">CSV (ZIP)</span>
                    <span class="block text-[12px] text-gray-500">Um arquivo .csv por seção, empacotados num .zip.</span>
                </x-download-button>
            </div>
        </x-modal>

        {{-- Modal de escopo do PDF: carteira inteira ou 1 cliente (empresa própria no topo) --}}
        <x-modal id="modal-export-bi-pdf" titulo="Gerar relatório PDF">
            <p class="text-[13px] text-gray-600 mb-3">Escolha o escopo do relatório. O período segue o filtro selecionado.</p>
            <label class="block text-[11px] text-gray-500 mb-1">Cliente</label>
            <select id="export-pdf-cliente" class="w-full text-[13px] py-2.5 px-3 border border-gray-300 rounded mb-4">
                <option value="">Todos os clientes (carteira)</option>
                @foreach($clientes ?? [] as $cliente)
                    @if($cliente->is_empresa_propria)
                        <option value="{{ $cliente->id }}">★ {{ $cliente->nome }} (Minha Empresa)</option>
                    @else
                        <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                    @endif
                @endforeach
            </select>
            <x-download-button path="/app/bi/exportar-pdf" filename="bi-fiscal-{{ $dataArq }}.pdf"
                               overlay="download-overlay-bi"
                               clienteSelect="export-pdf-cliente"
                               extraOnDone="document.getElementById('modal-export-bi-pdf').classList.add('hidden');"
                               class="block w-full text-center px-4 py-3 rounded bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                Gerar PDF
            </x-download-button>
        </x-modal>

        {{-- Faixa de cobertura de fonte (avisa meses sem EFD ICMS/IPI / PIS-COFINS).
             Sempre começa hidden; renderCobertura() (boot via updateResumoKpis) revela
             só com texto — evita banner âmbar vazio se o AJAX do boot falhar. --}}
        <div id="bi-cobertura-banner" class="hidden rounded border mb-4 px-4 py-2 flex items-start gap-2"
             style="background-color: #fffbeb; border-color: #fde68a;">
            <span style="color: #b45309;" class="text-sm font-bold leading-5">&#9888;</span>
            <p id="bi-cobertura-texto" class="text-[12px] leading-5" style="color: #92400e;"></p>
        </div>

        {{-- Faixa de cobertura de CONSULTA (avisa participantes nunca consultados / sem UF).
             Sempre começa hidden; renderCoberturaConsulta() (boot via updateResumoKpis) revela
             só com texto. --}}
        <div id="bi-cobertura-consulta-banner" class="hidden rounded border mb-3 px-4 py-2 flex items-start gap-2" style="background-color:#fffbeb;border-color:#fde68a;">
            <span style="color:#b45309;" class="text-sm font-bold leading-5">&#9888;</span>
            <p id="bi-cobertura-consulta-texto" class="text-[12px] leading-5" style="color:#92400e;"></p>
        </div>

        {{-- KPIs Consolidados --}}
        @php
        // KPIs exibem o valor exato COM centavos, inclusive grandes (mesmo critério do bi.js).
        $compactBrl = fn (float $value): string => 'R$ ' . number_format($value, 2, ',', '.');
        @endphp
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">

                {{-- Faturamento --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Faturamento</p>
                    <p class="text-lg font-bold text-gray-900" id="kpi-faturamento">{{ $compactBrl($resumo['total_vendas'] ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1" id="kpi-faturamento-notas">{{ number_format($resumo['total_notas'] ?? 0, 0, ',', '.') }} notas emitidas</p>
                    <p class="text-[11px] text-gray-500">Alíquota média: <span id="kpi-faturamento-aliquota" class="font-medium text-gray-700">{{ $resumo['aliquota_media'] ?? 0 }}%</span></p>
                </div>

                {{-- Aquisições --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Aquisições</p>
                    <p class="text-lg font-bold text-gray-900" id="kpi-aquisicoes">{{ $compactBrl($resumo['total_compras'] ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1" id="kpi-aquisicoes-notas">{{ number_format(($resumoEfd['total_entradas_notas'] ?? 0), 0, ',', '.') }} notas recebidas</p>
                    <p class="text-[11px] text-gray-500">Merc.: <span id="kpi-aquisicoes-merc" class="font-medium text-gray-700">{{ $compactBrl($resumo['total_compras_mercadoria'] ?? 0) }}</span> · Frete: <span id="kpi-aquisicoes-frete" class="font-medium text-gray-700">{{ $compactBrl($resumo['total_frete'] ?? 0) }}</span></p>
                    <p id="kpi-aquisicoes-cobertura" class="hidden text-[11px] font-medium mt-1" style="color: #b45309;"></p>
                </div>

                {{-- Tributação --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Tributos (débito s/ saída)</p>
                    <p class="text-lg font-bold text-gray-900" id="kpi-tributacao">{{ $compactBrl($resumo['total_tributos'] ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Carga bruta: <span id="kpi-tributacao-efd" class="font-medium text-gray-700">{{ $compactBrl($resumoEfd['carga_tributaria'] ?? 0) }}</span></p>
                    <p class="text-[11px] text-gray-500">A recolher: <span id="kpi-tributacao-arecolher" class="font-medium text-gray-700">{{ $compactBrl($resumo['total_a_recolher'] ?? 0) }}</span></p>
                </div>

                {{-- Saldo Líquido --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Saldo Líquido</p>
                    @php $saldoLiquido = ($resumo['saldo_liquido'] ?? 0); @endphp
                    <p class="text-lg font-bold {{ $saldoLiquido >= 0 ? 'text-gray-900' : 'text-rose-600' }}" id="kpi-saldo">{{ $compactBrl($saldoLiquido) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Entradas: <span id="kpi-saldo-entradas" class="font-medium text-gray-700">{{ $compactBrl($resumo['total_compras'] ?? 0) }}</span></p>
                    <p class="text-[11px] text-gray-500">Saídas: <span id="kpi-saldo-saidas" class="font-medium text-gray-700">{{ $compactBrl($resumo['total_vendas'] ?? 0) }}</span></p>
                </div>

            </div>
        </div>

        {{-- Barra de Métricas Secundárias --}}
        <div class="bg-white rounded border border-gray-300 p-3 sm:p-4 mb-6 sm:mb-10">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-0 sm:divide-x sm:divide-gray-200">
                <div class="text-center px-2 sm:px-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes ativos</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5" id="kpi-sec-participantes">{{ $resumoEfd['participantes_ativos'] ?? 0 }}</p>
                </div>
                <div class="text-center px-2 sm:px-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas em risco</p>
                    @php $notasRisco = $resumoEfd['notas_em_risco'] ?? 0; @endphp
                    <p class="text-sm font-semibold mt-0.5 {{ $notasRisco > 0 ? 'text-rose-600' : 'text-gray-900' }}" id="kpi-sec-risco">{{ $notasRisco }}</p>
                </div>
                <div class="text-center px-2 sm:px-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas sem itens</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5" id="kpi-sec-sem-itens">{{ $resumoEfd['notas_sem_itens'] ?? 0 }}</p>
                </div>
                <div class="text-center px-2 sm:px-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Fornecedores</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5" id="kpi-sec-fornecedores">{{ $resumo['total_fornecedores'] ?? 0 }}</p>
                </div>
                <div class="text-center px-2 sm:px-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Clientes</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5" id="kpi-sec-clientes">{{ $resumo['total_clientes'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        {{-- Tabs de Navegacao --}}
        @php
            $defaultTab = $defaultTab ?? 'faturamento';
            $tabClassMobile = fn($tab) => $tab === $defaultTab
                ? 'bi-tab active border-gray-800 text-gray-900 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm'
                : 'bi-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-3 sm:px-1 border-b-2 font-medium text-sm';
        @endphp
        <div class="mb-4 sm:mb-6" data-default-tab="{{ $defaultTab }}">
            <div class="border-b border-gray-200 scroll-fade-right sm:after:hidden">
                <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto scrollbar-hide tab-scroll-snap" aria-label="Tabs">
                    <button data-tab="faturamento" class="{{ $tabClassMobile('faturamento') }}">
                        Faturamento
                    </button>
                    <button data-tab="compras" class="{{ $tabClassMobile('compras') }}">
                        Compras
                    </button>
                    <button data-tab="tributos" class="{{ $tabClassMobile('tributos') }}">
                        Tributos
                    </button>
                    <button data-tab="efd" class="{{ $tabClassMobile('efd') }}">
                        EFD
                    </button>
                    <button data-tab="participantes" class="{{ $tabClassMobile('participantes') }}">
                        Participantes
                    </button>
                    <button data-tab="riscos" class="{{ $tabClassMobile('riscos') }}">
                        &#9888; Riscos
                    </button>
                    <button data-tab="tributario-efd" class="{{ $tabClassMobile('tributario-efd') }}">
                        Tributário EFD
                    </button>
                    <button data-tab="apuracao-notas" class="{{ $tabClassMobile('apuracao-notas') }}">
                        Apuração × Notas
                    </button>
                    <button data-tab="cfop" class="{{ $tabClassMobile('cfop') }}">
                        CFOP
                    </button>
                </nav>
            </div>
        </div>

        {{-- Tab Faturamento --}}
        <div id="tab-faturamento" class="bi-tab-content {{ $defaultTab !== 'faturamento' ? 'hidden' : '' }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Grafico Faturamento Mensal --}}
                <div class="bg-white rounded border border-gray-300 lg:col-span-2">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Faturamento Mensal</h3>
                        <button data-export="faturamento" class="text-[11px] font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-2 py-1">Exportar CSV</button>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-faturamento" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Top Clientes --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top 10 Clientes</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-top-clientes" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Faturamento por UF --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Faturamento por UF</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-faturamento-uf" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Compras --}}
        <div id="tab-compras" class="bi-tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Entradas vs Saídas --}}
                <div class="bg-white rounded border border-gray-300 lg:col-span-2">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Entradas vs Saídas</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-entradas-saidas" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Top Fornecedores --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top 10 Fornecedores</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-top-fornecedores" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Devoluções --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Devoluções</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-devolucoes" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Tributos --}}
        <div id="tab-tributos" class="bi-tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Carga Tributária --}}
                <div class="bg-white rounded border border-gray-300 lg:col-span-2">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Carga Tributária Mensal</h3>
                        <button data-export="tributos" class="text-[11px] font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-2 py-1">Exportar CSV</button>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-carga-tributaria" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Tributos por Tipo --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Tributos por Tipo</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-tributos-tipo" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Alíquota Efetiva --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Evolução da Alíquota Efetiva</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-aliquota-efetiva" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab EFD --}}
        <div id="tab-efd" class="bi-tab-content {{ $defaultTab !== 'efd' ? 'hidden' : '' }}">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Fluxo Mensal --}}
                <div class="bg-white rounded border border-gray-300 lg:col-span-2">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Fluxo Mensal Entradas vs Saídas (EFD)</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-efd-fluxo" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Volume por Bloco --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Volume por Bloco EFD</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-efd-blocos" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Tributos por Tipo --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Tributos por Tipo (EFD)</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-efd-tributos" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Top Fornecedores --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top 10 Fornecedores (EFD)</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-efd-fornecedores" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>

                {{-- Top Clientes --}}
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top 10 Clientes (EFD)</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-efd-clientes" class="h-56 sm:h-72 lg:h-80"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Participantes --}}
        <div id="tab-participantes" class="bi-tab-content hidden">
            {{-- Alertas de concentracao --}}
            <div id="concentracao-alertas" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6"></div>

            {{-- Toggle Fornecedores / Clientes --}}
            <div class="flex items-center gap-2 mb-4">
                <button id="btn-fornecedores"
                    class="flex-1 sm:flex-none px-4 py-2 rounded text-sm font-medium bg-gray-800 text-white hover:bg-gray-700">
                    Fornecedores
                </button>
                <button id="btn-clientes"
                    class="flex-1 sm:flex-none px-4 py-2 rounded text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50">
                    Clientes
                </button>
            </div>

            {{-- Tabela de ranking --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="overflow-x-auto scroll-fade-right-white">
                    <table class="min-w-[700px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">#</th>
                                <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>
                                <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</th>
                                <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>
                                <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ticket Médio</th>
                                <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">% do Total</th>
                                <th class="px-2 sm:px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide sticky right-0 bg-gray-50">Ficha</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-participantes" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
                <div id="participantes-empty" class="hidden py-10 text-center text-gray-400 text-sm">
                    Nenhum participante encontrado no período.
                </div>
                <div id="participantes-paginacao" class="hidden border-t border-gray-200 px-4 py-3 flex items-center justify-between gap-3"></div>
            </div>

            {{-- Ficha inline --}}
            <div id="ficha-participante" class="hidden bg-white rounded border border-gray-300 overflow-hidden p-4 sm:p-6 scroll-mt-4">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" id="ficha-nome">—</h3>
                        <p class="text-sm text-gray-500 mt-0.5" id="ficha-cnpj">—</p>
                    </div>
                    <button id="fechar-ficha" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <div id="ficha-loading" class="hidden py-8 text-center text-gray-400 text-sm">Carregando...</div>

                <div id="ficha-content" class="hidden">
                    {{-- KPIs da ficha --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas</p>
                            <p class="text-lg font-bold text-gray-900" id="ficha-total-notas">—</p>
                        </div>
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Entradas</p>
                            <p class="text-lg font-bold text-gray-900" id="ficha-entradas">—</p>
                        </div>
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saídas</p>
                            <p class="text-lg font-bold text-gray-900" id="ficha-saidas">—</p>
                        </div>
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tributos</p>
                            <p class="text-lg font-bold text-gray-900" id="ficha-tributos">—</p>
                        </div>
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Ticket Médio</p>
                            <p class="text-lg font-bold text-gray-900" id="ficha-ticket">—</p>
                        </div>
                        <div class="bg-gray-50 rounded p-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Última Consulta</p>
                            <p class="text-sm font-bold text-gray-900" id="ficha-ultima-consulta">—</p>
                        </div>
                    </div>

                    {{-- Gráfico evolução --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Evolução Mensal</h4>
                        <div id="chart-ficha-evolucao" class="h-48 sm:h-64"></div>
                    </div>

                    {{-- Últimas notas --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Últimas Notas</h4>
                        <div class="overflow-x-auto scroll-fade-right-white">
                            <table class="min-w-[700px] w-full text-xs divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 sm:px-3 py-2 text-left text-gray-500">Nº / Série</th>
                                        <th class="px-2 sm:px-3 py-2 text-left text-gray-500">Data</th>
                                        <th class="px-2 sm:px-3 py-2 text-left text-gray-500">Tipo</th>
                                        <th class="px-2 sm:px-3 py-2 text-left text-gray-500">Modelo</th>
                                        <th class="px-2 sm:px-3 py-2 text-left text-gray-500">CFOP</th>
                                        <th class="px-2 sm:px-3 py-2 text-right text-gray-500">Valor</th>
                                        <th class="px-2 sm:px-3 py-2 text-center text-gray-500">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="ficha-ultimas-notas" class="divide-y divide-gray-50"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Riscos --}}
        <div id="tab-riscos" class="bi-tab-content hidden">
            {{-- Score da Carteira --}}
            <div id="score-carteira" class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-6 mb-6">
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Participantes</p>
                    <p class="text-lg font-bold text-gray-900" id="score-total-participantes">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Irregulares</p>
                    <p class="text-lg font-bold text-gray-900" id="score-irregulares">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">% Regular</p>
                    <p class="text-lg font-bold text-gray-900" id="score-percentual-regular">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Valor em Risco</p>
                    <p class="text-lg font-bold text-gray-900" id="score-valor-risco">—</p>
                </div>
            </div>

            {{-- Gap de Importações --}}
            <div class="bg-white rounded border border-gray-300 mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Gap de Importações (12 meses)</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="gap-importacoes" class="overflow-x-auto scroll-fade-right-white"></div>
                </div>
            </div>

            {{-- Fornecedores Irregulares --}}
            <div class="bg-white rounded border border-gray-300 mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Fornecedores/Participantes Irregulares</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="tabela-irregulares-container" class="overflow-x-auto scroll-fade-right-white"></div>
                    <div id="irregulares-paginacao" class="hidden border-t border-gray-200 mt-3 pt-3 flex items-center justify-between gap-3"></div>
                </div>
            </div>

            {{-- Mudanças Recentes --}}
            <div class="bg-white rounded border border-gray-300 mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Mudanças Recentes de Cadastro (90 dias)</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="tabela-mudancas-container" class="overflow-x-auto scroll-fade-right-white"></div>
                    <div id="mudancas-paginacao" class="hidden border-t border-gray-200 mt-3 pt-3 flex items-center justify-between gap-3"></div>
                </div>
            </div>

            {{-- Notas com Fornecedor Irregular --}}
            <div class="bg-white rounded border border-gray-300 mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Notas com Participante Irregular</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="tabela-notas-risco-container" class="overflow-x-auto scroll-fade-right-white"></div>
                    <div id="notas-risco-paginacao" class="hidden border-t border-gray-200 mt-3 pt-3 flex items-center justify-between gap-3"></div>
                </div>
            </div>
        </div>

        {{-- Tab Tributário EFD --}}
        <div id="tab-tributario-efd" class="bi-tab-content hidden">
            {{-- Consolidado Crédito vs Débito --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consolidado Crédito vs Débito</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="tabela-tributario-consolidado" class="overflow-x-auto scroll-fade-right-white"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {{-- Grafico Mensal --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Evolução Mensal de Tributos</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-trib-mensal" class="h-56 sm:h-64 lg:h-72"></div>
                    </div>
                </div>

                {{-- Alíquota Efetiva --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Alíquota Efetiva Mensal (%)</h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div id="chart-trib-aliquota" class="h-56 sm:h-64 lg:h-72"></div>
                    </div>
                </div>
            </div>

            {{-- Carga por Regime --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Carga Tributária por Regime</h3>
                </div>
                <div class="p-4 sm:p-5">
                    <div id="tabela-trib-regime" class="overflow-x-auto scroll-fade-right-white"></div>
                </div>
            </div>
        </div>

        {{-- Tab Apuração × Notas --}}
        <div id="tab-apuracao-notas" class="bi-tab-content hidden">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6" id="apn-kpis">
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Declarado (devido)</p>
                    <p class="text-lg font-bold text-gray-900" id="apn-declarado">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Computado (notas)</p>
                    <p class="text-lg font-bold text-gray-900" id="apn-computado">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Divergência</p>
                    <p class="text-lg font-bold text-gray-900" id="apn-delta">—</p>
                </div>
                <div class="bg-white rounded border border-gray-300 p-3 sm:p-5 text-center">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Divergência %</p>
                    <p class="text-lg font-bold text-gray-900" id="apn-delta-pct">—</p>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Declarado vs Computado por Mês</h3>
                    <button data-export="apuracao-notas" class="text-[11px] font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-2 py-1">Exportar CSV</button>
                </div>
                <div class="p-4 sm:p-5"><div id="chart-apn-mensal" class="h-56 sm:h-72 lg:h-80"></div></div>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="overflow-x-auto scroll-fade-right-white">
                    <table class="min-w-[760px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase">Mês</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">ICMS decl.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">ICMS comp.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">PIS decl.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">PIS comp.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">COFINS decl.</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase">COFINS comp.</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase">Δ%</th>
                        </tr></thead>
                        <tbody id="tabela-apn" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab CFOP --}}
        <div id="tab-cfop" class="bi-tab-content hidden">
            @php
                $cfopLabels = [
                    1407 => 'Compra p/ uso/consumo c/ ST', 1915 => 'Entrada remetida p/ industrialização/conserto',
                    1916 => 'Retorno remetido p/ industrialização/conserto', 2202 => 'Devolução de compra p/ comercialização',
                    2556 => 'Compra de material p/ uso/consumo', 5411 => 'Devolução de compra c/ ST',
                    5916 => 'Retorno recebido p/ industrialização/conserto', 6915 => 'Remessa p/ conserto/reparo',
                    6916 => 'Retorno recebido p/ conserto',
                ];
                $cfopsForaFaturamento = config('efd.cfops_fora_faturamento', []);
            @endphp
            {{-- Como funciona / regras de negócio do BI Fiscal --}}
            <details class="bg-white rounded border border-gray-300 mb-6 group">
                <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between bg-gray-50 border-b border-gray-200 list-none">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Como funciona — regras de cálculo do BI Fiscal</span>
                    <span class="text-[11px] text-gray-400 group-open:hidden">mostrar</span>
                    <span class="text-[11px] text-gray-400 hidden group-open:inline">ocultar</span>
                </summary>
                <div class="p-4 sm:p-5 space-y-4 text-[13px] text-gray-700 leading-relaxed">
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Faturamento (Saídas) e Aquisições (Entradas)</p>
                        <p>Somam o <strong>valor total</strong> das notas por tipo de operação, deduplicando a base: a mesma NF-e escriturada na EFD ICMS/IPI <em>e</em> na EFD PIS/COFINS conta <strong>uma única vez</strong>. Notas <strong>canceladas</strong> são ignoradas e os <strong>serviços</strong> (NFS-e) entram no faturamento.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Saldo Líquido</p>
                        <p><strong>Saídas − Entradas</strong> (faturamento menos aquisições). Fica <strong>positivo</strong> quando a empresa vende mais do que compra e <strong>negativo</strong> no caso contrário.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">CFOPs que não compõem faturamento</p>
                        <p class="mb-2">Notas de remessa/retorno de conserto, devolução e uso/consumo geram documento fiscal com valor, mas <strong>não são receita de venda nem compra comercial</strong>. A nota <strong>inteira</strong> sai da base comercial — faturamento, aquisições, volume de notas e ticket médio — quando qualquer item carrega um destes CFOPs. A <strong>carga tributária</strong> (imposto a recolher) <strong>não</strong> é afetada: o tributo dessas operações continua real. CFOPs:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($cfopsForaFaturamento as $cfop)
                                <span class="inline-flex items-center gap-1 rounded px-2 py-1 text-[12px]" style="background-color: #f1f5f9; color: #334155;" title="{{ $cfopLabels[$cfop] ?? '' }}">
                                    <strong>{{ $cfop }}</strong><span class="text-gray-500">{{ $cfopLabels[$cfop] ?? '' }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </details>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6">
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200"><h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top CFOPs por Valor</h3></div>
                    <div class="p-4 sm:p-5"><div id="chart-cfop-valor" class="h-56 sm:h-72 lg:h-80"></div></div>
                </div>
                <div class="bg-white rounded border border-gray-300">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200"><h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Tendência Top 5 CFOPs</h3></div>
                    <div class="p-4 sm:p-5"><div id="chart-cfop-tendencia" class="h-56 sm:h-72 lg:h-80"></div></div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ranking por CFOP</h3>
                    <button data-export="cfop" class="text-[11px] font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-2 py-1">Exportar CSV</button>
                </div>
                <div id="tabela-cfop-container" class="overflow-x-auto scroll-fade-right-white"></div>
                <div id="cfop-paginacao" class="hidden border-t border-gray-200 px-4 py-3 flex items-center justify-between gap-3"></div>
            </div>
        </div>

        {{-- Estado vazio --}}
        <div id="bi-empty" class="hidden">
            <div class="bg-white rounded border border-gray-300 p-6 sm:p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhum dado disponível</h3>
                <p class="mt-2 text-sm text-gray-500">Importe notas fiscais para visualizar as análises.</p>
                <a href="/app/importacao/efd" data-link class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Importar EFD
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ApexCharts (local) --}}
<script src="/js/apexcharts.min.js"></script>
<script src="{{ asset('js/bi.js') }}?v={{ filemtime(public_path('js/bi.js')) }}"></script>
