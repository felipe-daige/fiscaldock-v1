{{-- Dashboard — Cockpit --}}
@php
    $fmtN = fn ($v) => number_format((float) $v, 0, ',', '.');
    $fmtR = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $prefsCards = $dashboardPrefs['cards'];
    $cardVisivel = fn ($k) => (bool) ($prefsCards[$k]['visivel'] ?? true);
    $atalhoLabels = [
        'consulta_nova' => 'Nova consulta', 'importar_efd' => 'Importar EFD', 'importar_xml' => 'Importar XML',
        'verificar_notas' => 'Verificar notas', 'bi_dashboard' => 'BI Fiscal', 'resumo_fiscal' => 'Resumo Fiscal',
        'clientes' => 'Clientes', 'score_fiscal' => 'Score Fiscal',
    ];
    $atalhosFixos = $dashboardPrefs['atalhos_fixos'] ?? [];
    $cardLabels = ['tendencia' => 'Tendência', 'risco' => 'Risco da carteira', 'triagem' => 'Precisa de atenção', 'fornecedores' => 'Top fornecedores', 'atividade' => 'Atividade recente', 'atalhos' => 'Atalhos'];
@endphp

<div id="dashboard-cockpit" class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">

        {{-- Header + controles --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Dashboard</h1>
                <p class="text-xs text-gray-500 mt-0.5">Visão geral do seu escritório</p>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex flex-col">
                    <label class="text-[11px] text-gray-500">Cliente</label>
                    <select data-control="cliente" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white">
                        <option value="">Todos</option>
                        @foreach($clientesOpcoes as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-[11px] text-gray-500">Período</label>
                    <select data-control="periodo" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white">
                        <option value="3">3 meses</option>
                        <option value="6" selected>6 meses</option>
                        <option value="12">12 meses</option>
                    </select>
                </div>
                <button type="button" data-personalizar-toggle class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white text-gray-600 hover:bg-gray-50">
                    Personalizar
                </button>
            </div>
        </div>

        {{-- Painel personalizar (oculto por padrão) --}}
        <div data-personalizar-panel class="hidden bg-white rounded border border-gray-300 p-4 mb-4">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Mostrar cards</p>
            <div class="flex flex-wrap gap-4 mb-4">
                @foreach($cardLabels as $k => $label)
                    <label class="inline-flex items-center gap-2 text-[13px] text-gray-700">
                        <input type="checkbox" data-pref-card="{{ $k }}" @checked($cardVisivel($k))> {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Atalhos fixados</p>
            <div class="flex flex-wrap gap-4">
                @foreach($atalhoLabels as $slug => $label)
                    <label class="inline-flex items-center gap-2 text-[13px] text-gray-700">
                        <input type="checkbox" data-pref-atalho="{{ $slug }}" @checked(in_array($slug, $atalhosFixos, true))> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        @if(($trialResumo['is_active'] ?? false) || ($trialResumo['is_expired'] ?? false))
            <div class="bg-white rounded border border-gray-300 p-3 mb-4 border-l-4 {{ ($trialResumo['is_active'] ?? false) ? 'border-l-blue-500' : 'border-l-amber-500' }}">
                @if($trialResumo['is_active'] ?? false)
                    <p class="text-sm text-gray-700">Trial ativo com <strong>{{ $fmtN($trialResumo['remaining'] ?? 0) }} créditos</strong>, expira em <strong>{{ optional($trialResumo['expires_at'])->format('d/m/Y') }}</strong>.</p>
                @else
                    <p class="text-sm text-gray-700">Trial terminou em <strong>{{ optional($trialResumo['expires_at'])->format('d/m/Y') }}</strong>. Compre créditos para seguir nas consultas pagas.</p>
                @endif
            </div>
        @endif

        {{-- KPIs (3, clicáveis) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <a href="/app/notas/dashboard" data-link data-kpi="volume" class="bg-white rounded border border-gray-300 border-l-4 p-4 hover:border-gray-400 transition-all" style="border-left-color:#1d4ed8">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Volume processado</p>
                <p class="text-xl font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['volume']['notas']) }}</p>
                <p class="text-[11px] text-gray-500 mt-0.5" data-kpi-sub>{{ $fmtR($cockpit['kpis']['volume']['valor']) }}</p>
            </a>
            <a href="/app/alertas" data-link data-kpi="saude" class="bg-white rounded border border-gray-300 border-l-4 p-4 hover:border-gray-400 transition-all" style="border-left-color:#dc2626">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saúde da carteira</p>
                <p class="text-xl font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['saude']['total']) }}</p>
                <p class="text-[11px] text-gray-500 mt-0.5" data-kpi-sub>{{ $cockpit['kpis']['saude']['total'] > 0 ? 'pontos de atenção' : 'tudo em dia' }}</p>
            </a>
            <a href="/app/creditos" data-link data-kpi="creditos" class="bg-white rounded border border-gray-300 border-l-4 p-4 hover:border-gray-400 transition-all" style="border-left-color:#047857">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
                <p class="text-xl font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['creditos']['saldo']) }}</p>
                <p class="text-[11px] text-gray-500 mt-0.5" data-kpi-sub>{{ $fmtN($cockpit['kpis']['creditos']['usados_mes']) }} usados este mês</p>
            </a>
        </div>

        {{-- Tendência (2/3) + Risco da carteira (1/3) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <div data-card="tendencia" class="lg:col-span-2 bg-white rounded border border-gray-300 p-4 {{ $cardVisivel('tendencia') ? '' : 'hidden' }}">
                <div class="flex items-baseline justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Tendência — entrada × saída</p>
                    <select data-control="metrica" class="text-[11px] text-gray-500 border-0 bg-transparent focus:ring-0">
                        <option value="valor" selected>Faturamento</option>
                        <option value="qtd">Volume</option>
                    </select>
                </div>
                <div id="chartTendencia"></div>
            </div>
            <div data-card="risco" class="bg-white rounded border border-gray-300 p-4 {{ $cardVisivel('risco') ? '' : 'hidden' }}">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Risco da carteira</p>
                <div id="chartRisco"></div>
                <p id="risco-vazio" class="hidden text-center text-sm text-gray-400 py-10">Nenhum participante avaliado ainda.</p>
            </div>
        </div>

        {{-- Triagem (1/3) + Top fornecedores (1/3) + Atividade (1/3) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <div data-card="triagem" class="bg-white rounded border border-gray-300 p-4 {{ $cardVisivel('triagem') ? '' : 'hidden' }}">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Precisa de atenção</p>
                <div id="triagem-lista" class="divide-y divide-gray-100">
                    @include('autenticado.dashboard.partials.triagem', ['triagem' => $cockpit['triagem']])
                </div>
            </div>
            <div data-card="fornecedores" class="bg-white rounded border border-gray-300 p-4 {{ $cardVisivel('fornecedores') ? '' : 'hidden' }}">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Top fornecedores</p>
                <div id="chartFornecedores"></div>
                <p id="fornecedores-vazio" class="hidden text-center text-sm text-gray-400 py-10">Sem compras no período.</p>
            </div>
            <div data-card="atividade" class="bg-white rounded border border-gray-300 overflow-hidden {{ $cardVisivel('atividade') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h2 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Atividade recente</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($atividadeRecente as $atividade)
                        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
                            <span class="text-sm text-gray-700 truncate">{{ $atividade['descricao'] }}</span>
                            <span class="text-[11px] text-gray-400 flex-shrink-0">{{ $atividade['data']->format('d/m H:i') }}</span>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">Nenhuma atividade recente</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Atalhos --}}
        <div data-card="atalhos" class="bg-white rounded border border-gray-300 p-4 mb-4 {{ $cardVisivel('atalhos') ? '' : 'hidden' }}">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Atalhos</p>
            <div id="atalhos-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($atalhosFixos as $slug)
                    @if(isset($atalhosCatalogo[$slug]))
                        <a href="{{ $atalhosCatalogo[$slug] }}" data-link class="text-[13px] text-center py-2.5 px-3 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all">
                            {{ $atalhoLabels[$slug] ?? $slug }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        @if($isUsuarioNovo)
            @include('autenticado.dashboard.partials.primeiros-passos')
        @endif
    </div>

    {{-- Estado inicial pro JS (evita refetch na 1ª pintura) --}}
    <script type="application/json" id="cockpit-initial">{!! json_encode($cockpit) !!}</script>
</div>

<script src="/js/apexcharts.min.js"></script>
