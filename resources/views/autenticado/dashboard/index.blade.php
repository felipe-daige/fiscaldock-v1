{{-- Dashboard - Cockpit --}}
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
    $atalhosOrdem = array_values(array_unique(array_merge($dashboardPrefs['atalhos_ordem'] ?? [], array_keys($atalhoLabels))));
    $atalhosConfig = [];
    foreach ($atalhosOrdem as $slug) {
        if (isset($atalhoLabels[$slug], $atalhosCatalogo[$slug])) {
            $atalhosConfig[$slug] = ['label' => $atalhoLabels[$slug], 'url' => $atalhosCatalogo[$slug]];
        }
    }
    $periodoAtual = (int) ($cockpit['meta']['periodo'] ?? 6);
@endphp

<div id="dashboard-cockpit" class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Dashboard</h1>
                <p class="text-xs text-gray-500 mt-1">Painel operacional da carteira fiscal</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <span data-dashboard-status class="hidden px-3 py-2 bg-white border border-gray-300 rounded text-xs text-gray-600">Atualizando...</span>
                <button type="button" data-personalizar-toggle aria-expanded="false" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.89 3.31.877 2.42 2.42a1.724 1.724 0 001.065 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.89 1.543-.877 3.31-2.42 2.42a1.724 1.724 0 00-2.573 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.89-3.31-.877-2.42-2.42a1.724 1.724 0 00-1.065-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.89-1.543.877-3.31 2.42-2.42.996.575 2.236.07 2.573-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Personalizar
                </button>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 p-4 sm:p-5 mb-4 sm:mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 lg:items-end">
                <div class="lg:col-span-5">
                    <label for="dashboard-cliente" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                    <select id="dashboard-cliente" data-control="cliente" class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os clientes</option>
                        @foreach($clientesOpcoes as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="dashboard-periodo" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Período</label>
                    <select id="dashboard-periodo" data-control="periodo" class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="3" @selected($periodoAtual === 3)>3 meses</option>
                        <option value="6" @selected($periodoAtual === 6)>6 meses</option>
                        <option value="12" @selected($periodoAtual === 12)>12 meses</option>
                    </select>
                </div>
                <div class="lg:col-span-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Escopo</p>
                    <div class="min-h-[38px] px-3 py-2 rounded border border-gray-200 bg-gray-50 text-xs text-gray-600 flex items-center">
                        <span data-dashboard-scope>Todos os clientes - {{ $periodoAtual }} meses</span>
                    </div>
                </div>
            </div>
        </div>

        <div data-personalizar-panel class="hidden bg-white rounded border border-gray-300 overflow-hidden mb-4 sm:mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Personalização</span>
            </div>
            <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Mostrar cards</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($cardLabels as $k => $label)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" data-pref-card="{{ $k }}" class="rounded border-gray-300 text-gray-800 focus:ring-gray-400" @checked($cardVisivel($k))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Atalhos fixados</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($atalhosConfig as $slug => $atalho)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" data-pref-atalho="{{ $slug }}" class="rounded border-gray-300 text-gray-800 focus:ring-gray-400" @checked(in_array($slug, $atalhosFixos, true))>
                                <span>{{ $atalho['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if(($trialResumo['is_active'] ?? false) || ($trialResumo['is_expired'] ?? false))
            <style>
                .trial-banner { animation: trialIn .5s cubic-bezier(.2,.7,.2,1) both; }
                @keyframes trialIn { from { opacity:0; transform: translateY(-8px); } to { opacity:1; transform:none; } }
                .trial-banner__cta { transition: transform .15s ease, box-shadow .15s ease; }
                .trial-banner__cta:hover { transform: translateY(-1px); box-shadow: 0 10px 22px -8px rgba(0,0,0,.4); }
            </style>
            @php
                $trialOn = $trialResumo['is_active'] ?? false;
                $tIni = $trialResumo['started_at'] ?? null;
                $tExp = $trialResumo['expires_at'] ?? null;
                $tDiasRest = (int) ($trialResumo['days_remaining'] ?? 0);
                $tTotal = ($tIni && $tExp) ? max(1, $tIni->diffInDays($tExp)) : null;
                $tPct = $tTotal ? max(4, min(100, (int) round($tDiasRest / $tTotal * 100))) : 0;
                $tGranted = (int) ($trialResumo['granted'] ?? 0);
                $tRem = (int) ($trialResumo['remaining'] ?? 0);
                $tCredPct = $tGranted > 0 ? max(4, min(100, (int) round($tRem / $tGranted * 100))) : 0;
            @endphp
            <div data-trial-banner class="trial-banner relative overflow-hidden rounded-xl mb-4 sm:mb-6 p-5 sm:p-6"
                 style="{{ $trialOn
                    ? 'background: radial-gradient(120% 140% at 100% 0%, #4f46e5 0%, #2563eb 45%, #1e3a8a 100%); box-shadow: 0 18px 40px -20px rgba(37,99,235,.65);'
                    : 'background: radial-gradient(120% 140% at 100% 0%, #f59e0b 0%, #d97706 50%, #92400e 100%); box-shadow: 0 18px 40px -20px rgba(217,119,6,.55);' }} color:#fff;">

                {{-- glow decorativo --}}
                <div class="pointer-events-none absolute -top-16 -right-10 rounded-full" style="width:220px; height:220px; background:rgba(255,255,255,.14); filter:blur(8px);"></div>
                <div class="pointer-events-none absolute -bottom-20 -left-8 rounded-full" style="width:180px; height:180px; background:rgba(255,255,255,.08); filter:blur(6px);"></div>

                <div class="relative flex flex-col lg:flex-row lg:items-center gap-5">
                    {{-- bloco principal --}}
                    <div class="flex items-start gap-4 flex-1 min-w-0">
                        <span class="inline-flex items-center justify-center rounded-xl shrink-0" style="width:48px; height:48px; background:rgba(255,255,255,.16); backdrop-filter:blur(4px);">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <span class="inline-block text-[10px] font-bold uppercase tracking-[.14em] rounded-full px-2.5 py-1 mb-1.5" style="background:rgba(255,255,255,.18);">
                                {{ $trialOn ? 'Período de teste' : 'Teste encerrado' }}
                            </span>
                            @if($trialOn)
                                <p class="font-extrabold leading-none" style="font-size:30px;">{{ $tDiasRest }} <span style="font-size:15px; font-weight:600; opacity:.85;">dia(s) restantes</span></p>
                                <p class="text-[13px] mt-1.5" style="color:rgba(255,255,255,.82);">Aproveite todos os recursos liberados · expira em {{ optional($tExp)->format('d/m/Y') }}</p>
                            @else
                                <p class="font-extrabold leading-tight" style="font-size:20px;">Seu período de teste terminou</p>
                                <p class="text-[13px] mt-1" style="color:rgba(255,255,255,.85);">Terminou em {{ optional($tExp)->format('d/m/Y') }}. Compre créditos para seguir nas consultas pagas.</p>
                            @endif

                            {{-- barras de progresso (só no ativo) --}}
                            @if($trialOn)
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 max-w-xl">
                                    <div>
                                        <div class="flex justify-between text-[10px] font-semibold uppercase tracking-wide mb-1" style="color:rgba(255,255,255,.75);"><span>Tempo</span><span>{{ $tDiasRest }}/{{ $tTotal }}d</span></div>
                                        <div class="h-1.5 rounded-full overflow-hidden" style="background:rgba(255,255,255,.22);"><div class="h-full rounded-full" style="width:{{ $tPct }}%; background:#fff;"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-[10px] font-semibold uppercase tracking-wide mb-1" style="color:rgba(255,255,255,.75);"><span>Créditos</span><span>{{ $fmtN($tRem) }}/{{ $fmtN($tGranted) }}</span></div>
                                        <div class="h-1.5 rounded-full overflow-hidden" style="background:rgba(255,255,255,.22);"><div class="h-full rounded-full" style="width:{{ $tCredPct }}%; background:#fff;"></div></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="shrink-0">
                        <a href="/app/planos" data-link class="trial-banner__cta inline-flex items-center gap-1.5 text-[13px] font-bold rounded-lg px-5 py-2.5 whitespace-nowrap"
                           style="background:#fff; color:{{ $trialOn ? '#1e3a8a' : '#92400e' }};">
                            {{ $trialOn ? 'Ver planos' : 'Comprar créditos' }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4 sm:mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo operacional</span>
                <span data-dashboard-meta class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $periodoAtual }} meses</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                <a href="/app/notas/dashboard" data-link data-kpi="volume" class="block p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Volume processado</p>
                    <p class="text-lg font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['volume']['notas']) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1" data-kpi-sub>{{ $fmtR($cockpit['kpis']['volume']['valor']) }}</p>
                </a>
                <a href="/app/alertas" data-link data-kpi="saude" class="block p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saúde da carteira</p>
                    <p class="text-lg font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['saude']['total']) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1" data-kpi-sub>{{ $cockpit['kpis']['saude']['total'] > 0 ? 'pontos de atenção' : 'tudo em dia' }}</p>
                </a>
                <a href="/app/creditos" data-link data-kpi="creditos" class="block p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Créditos</p>
                    <p class="text-lg font-bold text-gray-900" data-kpi-valor>{{ $fmtN($cockpit['kpis']['creditos']['saldo']) }}</p>
                    <p class="text-[11px] text-gray-500 mt-1" data-kpi-sub>{{ $fmtN($cockpit['kpis']['creditos']['usados_mes']) }} usados este mês</p>
                </a>
            </div>
        </div>

        <div data-card="atalhos" class="bg-white rounded border border-gray-300 overflow-hidden mb-4 sm:mb-6 {{ $cardVisivel('atalhos') ? '' : 'hidden' }}">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Atalhos</span>
            </div>
            <div class="p-4">
                <div id="atalhos-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($atalhosFixos as $slug)
                        @if(isset($atalhosConfig[$slug]))
                            <a href="{{ $atalhosConfig[$slug]['url'] }}" data-link class="inline-flex items-center justify-center min-h-[40px] text-center px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                {{ $atalhosConfig[$slug]['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-4 sm:mb-6">
            <div data-card="tendencia" class="lg:col-span-2 bg-white rounded border border-gray-300 overflow-hidden {{ $cardVisivel('tendencia') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Tendência - entradas x saídas</span>
                    <select aria-label="Métrica da tendência" data-control="metrica" class="text-[11px] text-gray-600 border border-gray-300 rounded bg-white px-2 py-1 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="valor" selected>Faturamento</option>
                        <option value="qtd">Volume</option>
                    </select>
                </div>
                <div class="p-4">
                    <div id="chartTendencia" class="min-h-[268px]"></div>
                </div>
            </div>

            <div data-card="triagem" class="bg-white rounded border border-gray-300 overflow-hidden flex flex-col {{ $cardVisivel('triagem') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Precisa de atenção</span>
                </div>
                <div id="triagem-lista" class="flex-1 flex flex-col">
                    @include('autenticado.dashboard.partials.triagem', ['triagem' => $cockpit['triagem']])
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-4 sm:mb-6">
            <div data-card="risco" class="bg-white rounded border border-gray-300 overflow-hidden {{ $cardVisivel('risco') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Risco da carteira</span>
                </div>
                <div class="p-4">
                    <div id="chartRisco" class="min-h-[244px]"></div>
                    <p id="risco-vazio" class="hidden text-center text-sm text-gray-500 py-12">Nenhum participante avaliado ainda.</p>
                </div>
            </div>

            <div data-card="fornecedores" class="bg-white rounded border border-gray-300 overflow-hidden {{ $cardVisivel('fornecedores') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top fornecedores</span>
                </div>
                <div class="p-4">
                    <div id="chartFornecedores" class="min-h-[244px]"></div>
                    <p id="fornecedores-vazio" class="hidden text-center text-sm text-gray-500 py-12">Sem compras no período.</p>
                </div>
            </div>

            <div data-card="atividade" class="bg-white rounded border border-gray-300 overflow-hidden {{ $cardVisivel('atividade') ? '' : 'hidden' }}">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Atividade recente</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($atividadeRecente as $atividade)
                        <a href="{{ $atividade['url'] ?? '#' }}" data-link class="group flex items-center justify-between gap-3 px-4 py-3 hover:bg-gray-50/50 transition-colors">
                            <span class="min-w-0 text-sm text-gray-700 truncate group-hover:text-gray-900">{{ $atividade['descricao'] }}</span>
                            <span class="flex-shrink-0 flex items-center gap-1.5 text-[11px] text-gray-400">
                                {{ $atividade['data']->format('d/m H:i') }}
                                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-12 text-center text-sm text-gray-500">Nenhuma atividade recente</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($isUsuarioNovo)
            @include('autenticado.dashboard.partials.primeiros-passos')
        @endif
    </div>

    {{-- Estado inicial pro JS (evita refetch na 1ª pintura) --}}
    <script type="application/json" id="cockpit-initial">{!! json_encode($cockpit) !!}</script>
    <script type="application/json" id="dashboard-atalhos">{!! json_encode($atalhosConfig) !!}</script>
</div>

<script src="/js/apexcharts.min.js"></script>
{{-- dashboard.js versionado por mtime: o SPA carrega esse script por convenção (window.initDashboard),
     mas sem ?v= o browser servia uma cópia velha em cache (max-age=3600) e o gráfico ficava em branco
     após edições. Incluir aqui com cache-bust garante que a versão atual sempre seja buscada. --}}
<script src="/js/dashboard.js?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
