{{-- Central de Alertas --}}
<div class="min-h-screen bg-gray-100" id="alertas-central-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <style>
            .alerta-skeleton {
                background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
                background-size: 200% 100%;
                animation: alerta-shimmer 1.5s infinite;
                border-radius: 0.25rem;
            }
            @keyframes alerta-shimmer {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
        </style>

        {{-- Page Header --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Central de Alertas</h1>
                    <p class="mt-1 text-xs text-gray-500">Monitoramento fiscal e cadastral consolidado por cliente e participante.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                    <span id="alerta-ultima-atualizacao" class="text-[10px] text-gray-500 uppercase tracking-wide hidden sm:inline"></span>
                    <button id="btn-recalcular" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium transition-colors">
                        <svg id="recalcular-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg id="recalcular-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Recalcular
                    </button>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo de Alertas</span>
                    <span id="kpi-novos-hoje" class="hidden px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151"></span>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div id="kpi-total" class="p-4 sm:p-6 cursor-pointer hover:bg-gray-50/50 transition-colors" data-filtro-severidade="">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Total de Alertas</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900" id="kpi-total-valor">
                        <span class="alerta-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Todos os alertas ativos no monitoramento.</p>
                </div>
                <div id="kpi-alta" class="p-4 sm:p-6 cursor-pointer hover:bg-gray-50/50 transition-colors" data-filtro-severidade="alta">
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: #dc2626"></span>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Alta Severidade</p>
                    </div>
                    <p class="text-lg sm:text-xl font-bold text-gray-900" id="kpi-alta-valor">
                        <span class="alerta-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Pendências com maior risco operacional.</p>
                </div>
                <div id="kpi-media" class="p-4 sm:p-6 cursor-pointer hover:bg-gray-50/50 transition-colors" data-filtro-severidade="media">
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: #d97706"></span>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Média Severidade</p>
                    </div>
                    <p class="text-lg sm:text-xl font-bold text-gray-900" id="kpi-media-valor">
                        <span class="alerta-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Exigem revisão e acompanhamento fiscal.</p>
                </div>
                <div id="kpi-baixa" class="p-4 sm:p-6 cursor-pointer hover:bg-gray-50/50 transition-colors" data-filtro-severidade="baixa">
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: #9ca3af"></span>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Baixa Severidade</p>
                    </div>
                    <p class="text-lg sm:text-xl font-bold text-gray-900" id="kpi-baixa-valor">
                        <span class="alerta-skeleton inline-block w-12 h-7 sm:h-9">&nbsp;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 mt-1">Ocorrências informativas ou preventivas.</p>
                </div>
            </div>
        </div>

        {{-- Evolution Chart --}}
        <div id="alertas-evolucao-wrapper" class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h3 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Evolução de Alertas</h3>
                <p class="text-[11px] text-gray-400 mt-1">Últimas 12 semanas</p>
            </div>
            <div class="p-4 sm:p-5">
                <div id="alertas-evolucao-chart" class="h-64">
                <div class="flex items-center justify-center h-full text-gray-400">
                    <svg class="animate-spin h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Carregando...
                </div>
                </div>
            </div>
        </div>

        {{-- Category Tabs --}}
        <div class="mb-4 sm:mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 sm:space-x-8 overflow-x-auto scrollbar-hide" aria-label="Categorias de Alertas" id="alertas-tabs-nav">
                    <button data-alerta-tab="todos" class="alerta-tab active border-gray-800 text-gray-900 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        Todos
                        <span id="tab-badge-todos" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151"></span>
                    </button>
                    <button data-alerta-tab="notas_fiscais" class="alerta-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        Notas Fiscais
                        <span id="tab-badge-notas_fiscais" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"></span>
                    </button>
                    <button data-alerta-tab="pis_cofins" class="alerta-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        PIS/COFINS
                        <span id="tab-badge-pis_cofins" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"></span>
                    </button>
                    <button data-alerta-tab="compliance" class="alerta-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        Compliance
                        <span id="tab-badge-compliance" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"></span>
                    </button>
                    <button data-alerta-tab="fornecedores" class="alerta-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        Fornecedores
                        <span id="tab-badge-fornecedores" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"></span>
                    </button>
                    <button data-alerta-tab="importacao" class="alerta-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                        Importação
                        <span id="tab-badge-importacao" class="hidden inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"></span>
                    </button>
                </nav>
            </div>
        </div>

        {{-- Filters (simplified) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3">
                {{-- Severidade --}}
                <div class="flex-1 min-w-[120px]">
                    <label for="alerta-filtro-severidade" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Severidade</label>
                    <select id="alerta-filtro-severidade" class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todas</option>
                        <option value="alta">Alta</option>
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                    </select>
                </div>

                {{-- Cliente --}}
                <div class="flex-1 min-w-[160px]">
                    <label for="alerta-filtro-cliente" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                    <select id="alerta-filtro-cliente" class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos os Clientes</option>
                        @foreach($clientes ?? [] as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->razao_social }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="flex-1 min-w-[120px]">
                    <label for="alerta-filtro-status" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status</label>
                    <select id="alerta-filtro-status" class="w-full px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="ativo">Ativos</option>
                        <option value="visto">Vistos</option>
                        <option value="resolvido">Resolvidos</option>
                        <option value="ignorado">Ignorados</option>
                        <option value="">Todos</option>
                    </select>
                </div>

                {{-- Botao Filtrar --}}
                <div class="flex-shrink-0">
                    <button id="btn-filtrar-alertas" class="w-full sm:w-auto px-5 py-2 bg-gray-800 text-white text-sm font-medium rounded hover:bg-gray-700 transition-colors">
                        Filtrar
                    </button>
                </div>
            </div>
            </div>
        </div>

        {{-- Alert List --}}
        <div id="alertas-lista" class="">
            {{-- Skeleton loading --}}
            <div class="space-y-2" id="alertas-skeleton">
                @for($i = 0; $i < 5; $i++)
                <div class="bg-white rounded border border-gray-300 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="alerta-skeleton w-2.5 h-2.5 rounded-full">&nbsp;</div>
                        <div class="alerta-skeleton h-4 w-48">&nbsp;</div>
                        <div class="ml-auto alerta-skeleton h-4 w-20">&nbsp;</div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        {{-- Pagination --}}
        <div id="alertas-paginacao" class="mt-4 sm:mt-6 hidden border-t border-gray-300 px-4 py-3"></div>

    </div>
</div>

<script src="/js/apexcharts.min.js"></script>
<script>
(function() {
    'use strict';

    // ─── State ────────────────────────────────────────────────
    var resumoData = @json($resumo ?? []);
    var alertasData = null;
    var evolucaoChart = null;
    var filtros = { severidade: '', cliente_id: '', status: 'ativo' };
    var tabAtual = 'todos';
    var paginaAtual = 1;
    var isRecalculando = false;
    var expandedAlerts = {};

    // Mapeamento de tabs para tipos de alertas
    var tabTipos = {
        todos: null,
        notas_fiscais: ['notas_duplicadas', 'notas_sem_participante', 'notas_valor_zerado', 'notas_sem_itens', 'notas_data_futura'],
        pis_cofins: ['pis_cofins_incompleto'],
        compliance: ['situacao_irregular', 'consulta_vencida', 'nunca_consultado', 'cnpj_situacao_irregular', 'participante_inativo', 'participante_sem_ie'],
        fornecedores: ['fornecedor_irregular'],
        importacao: ['gap_importacao', 'gap_temporal']
    };

    // ─── Helpers ──────────────────────────────────────────────

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        var s = String(str);
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return s.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatarMoeda(val) {
        if (val === null || val === undefined) return 'R$ 0,00';
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    }

    function formatarData(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return escapeHtml(dateStr);
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatCnpj(cnpj) {
        if (!cnpj) return '-';
        var c = String(cnpj).replace(/\D/g, '');
        if (c.length === 14) {
            return c.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        if (c.length === 11) {
            return c.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        return escapeHtml(cnpj);
    }

    function profileLink(type, id, label) {
        if (!id) return escapeHtml(label || '-');
        var url = type === 'cliente' ? '/app/cliente/' + id : '/app/participante/' + id;
        return '<a href="' + url + '" data-link class="text-gray-900 hover:text-gray-600 hover:underline font-medium">' + escapeHtml(label || 'Ver perfil') + '</a>';
    }

    function formatarDataHora(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return escapeHtml(dateStr);
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function severidadeBadge(sev) {
        var colors = {
            alta: '#dc2626',
            media: '#d97706',
            baixa: '#9ca3af'
        };
        var label = { alta: 'Alta', media: 'Média', baixa: 'Baixa' };
        var color = colors[sev] || '#9ca3af';
        var lbl = label[sev] || escapeHtml(sev);
        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + color + '">' + escapeHtml(lbl) + '</span>';
    }

    function severidadeDot(sev) {
        var colors = { alta: '#dc2626', media: '#d97706', baixa: '#9ca3af' };
        var color = colors[sev] || '#9ca3af';
        return '<span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: ' + color + '"></span>';
    }

    function categoriaBadge(cat) {
        var labels = { notas_fiscais: 'Notas Fiscais', compliance: 'Compliance', importacao: 'Importação' };
        var colors = { notas_fiscais: '#374151', compliance: '#4338ca', importacao: '#0f766e' };
        var color = colors[cat] || '#374151';
        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + color + '">' + escapeHtml(labels[cat] || cat) + '</span>';
    }

    async function fetchJson(url, options) {
        options = options || {};
        var headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }, options.headers || {});
        var r = await fetch(url, Object.assign({}, options, { headers: headers }));
        if (!r.ok) throw new Error('Erro na requisição: ' + r.status);
        return r.json();
    }

    async function postJson(url, body) {
        return fetchJson(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        });
    }

    // ─── Render KPIs ──────────────────────────────────────────

    function renderKpis(resumo) {
        if (!resumo) return;

        var totalEl = document.getElementById('kpi-total-valor');
        var altaEl = document.getElementById('kpi-alta-valor');
        var mediaEl = document.getElementById('kpi-media-valor');
        var baixaEl = document.getElementById('kpi-baixa-valor');
        var novosEl = document.getElementById('kpi-novos-hoje');
        var atualizacaoEl = document.getElementById('alerta-ultima-atualizacao');

        if (totalEl) totalEl.textContent = resumo.total_ativos || 0;
        if (altaEl) altaEl.textContent = (resumo.por_severidade && resumo.por_severidade.alta) || 0;
        if (mediaEl) mediaEl.textContent = (resumo.por_severidade && resumo.por_severidade.media) || 0;
        if (baixaEl) baixaEl.textContent = (resumo.por_severidade && resumo.por_severidade.baixa) || 0;

        if (novosEl) {
            var novos = resumo.novos_hoje || 0;
            if (novos > 0) {
                novosEl.textContent = '+' + novos + ' novos hoje';
                novosEl.classList.remove('hidden');
            } else {
                novosEl.classList.add('hidden');
            }
        }

        if (atualizacaoEl && resumo.ultima_atualizacao) {
            atualizacaoEl.textContent = 'Última atualização ' + formatarDataHora(resumo.ultima_atualizacao);
            atualizacaoEl.classList.remove('hidden');
        }
    }

    // ─── Evolution Chart ──────────────────────────────────────

    async function loadEvolucao() {
        var container = document.getElementById('alertas-evolucao-chart');
        if (!container) return;

        // Aguardar a animação do wrapper terminar antes de renderizar
        var wrapper = document.getElementById('alertas-evolucao-wrapper');
        if (wrapper) {
            await new Promise(function(resolve) {
                // Se a animação já terminou (elemento visível e sem animação pendente), seguir
                var animations = wrapper.getAnimations ? wrapper.getAnimations() : [];
                if (animations.length === 0) {
                    resolve();
                } else {
                    Promise.all(animations.map(function(a) { return a.finished; })).then(resolve).catch(resolve);
                }
            });
        }

        // Aguardar ApexCharts carregar (SPA carrega scripts externos de forma assíncrona)
        var tentativas = 0;
        while (typeof ApexCharts === 'undefined' && tentativas < 50) {
            await new Promise(function(r) { setTimeout(r, 100); });
            tentativas++;
        }
        if (typeof ApexCharts === 'undefined') {
            container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Erro ao carregar gráfico</div>';
            return;
        }

        try {
            var data = await fetchJson('/app/alertas/evolucao');
            if (!data || !data.categorias || !data.series || data.categorias.length === 0) {
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem dados para exibir</div>';
                return;
            }

            // Verificar se todas as séries têm apenas zeros
            var temDados = data.series.some(function(s) {
                return s.data.some(function(v) { return v > 0; });
            });
            if (!temDados) {
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem alertas nas últimas 12 semanas</div>';
                return;
            }

            if (evolucaoChart) {
                evolucaoChart.destroy();
                evolucaoChart = null;
            }

            var options = {
                chart: {
                    type: 'bar',
                    height: 256,
                    stacked: true,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    animations: { enabled: true, easing: 'easeinout', speed: 600 }
                },
                plotOptions: {
                    bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 }
                },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: data.categorias,
                    labels: { style: { fontSize: '11px', colors: '#6b7280' } }
                },
                yaxis: {
                    labels: { style: { fontSize: '11px', colors: '#6b7280' } }
                },
                fill: { opacity: 1 },
                tooltip: {
                    y: { formatter: function(val) { return val + ' alertas'; } }
                },
                colors: data.series.map(function(s) { return s.color || '#374151'; }),
                series: data.series.map(function(s) {
                    return { name: s.name, data: s.data };
                }),
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    labels: { colors: '#6b7280' },
                    offsetY: -4,
                    itemMargin: { horizontal: 12, vertical: 8 }
                },
                grid: {
                    borderColor: '#e5e7eb',
                    strokeDashArray: 4
                },
                responsive: [{
                    breakpoint: 640,
                    options: {
                        chart: { height: 200 },
                        plotOptions: { bar: { columnWidth: '70%' } },
                        legend: { position: 'bottom', horizontalAlign: 'center' }
                    }
                }]
            };

            evolucaoChart = new ApexCharts(container, options);
            evolucaoChart.render().then(function() {
                // Forçar recalculo de dimensões após render completo
                requestAnimationFrame(function() {
                    window.dispatchEvent(new Event('resize'));
                });
            });
        } catch (e) {
            container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Erro ao carregar gráfico</div>';
        }
    }

    // ─── Load Alertas ─────────────────────────────────────────

    async function loadAlertas(page) {
        page = page || 1;
        paginaAtual = page;
        var listaEl = document.getElementById('alertas-lista');
        if (!listaEl) return;

        listaEl.innerHTML = renderSkeleton();

        var params = new URLSearchParams();
        if (filtros.severidade) params.append('severidade', filtros.severidade);
        if (filtros.cliente_id) params.append('cliente_id', filtros.cliente_id);
        if (filtros.status) params.append('status', filtros.status);
        params.append('page', page);

        try {
            var data = await fetchJson('/app/alertas/dados?' + params.toString());
            alertasData = data;

            if (!data || !data.data || data.data.length === 0) {
                listaEl.innerHTML = renderEmptyState();
                var pagEl = document.getElementById('alertas-paginacao');
                if (pagEl) { pagEl.innerHTML = ''; pagEl.classList.add('hidden'); }
                updateTabBadges([]);
                return;
            }

            expandedAlerts = {};

            // Atualizar badges das tabs com contagem
            updateTabBadges(data.data);

            // Filtrar por tab ativa
            var filtered = filterByTab(data.data);

            if (filtered.length === 0) {
                listaEl.innerHTML = renderEmptyStateTab();
            } else {
                listaEl.innerHTML = renderAlertasList(filtered);
            }

            renderPaginacao(data);
            setupAlertaActions();
            setupExpandToggle();
        } catch (e) {
            listaEl.innerHTML = '<div class="bg-white rounded border border-gray-300 p-6 text-center text-sm text-gray-500">Erro ao carregar alertas. Tente novamente.</div>';
        }
    }

    // ─── Tab Helpers ───────────────────────────────────────────

    function filterByTab(alertas) {
        if (tabAtual === 'todos' || !tabTipos[tabAtual]) return alertas;
        var tipos = tabTipos[tabAtual];
        return alertas.filter(function(a) {
            return tipos.indexOf(a.tipo) !== -1;
        });
    }

    function updateTabBadges(alertas) {
        var counts = {};
        var totalCount = alertas.length;

        Object.keys(tabTipos).forEach(function(tab) {
            if (tab === 'todos') {
                counts[tab] = totalCount;
            } else {
                var tipos = tabTipos[tab];
                counts[tab] = alertas.filter(function(a) {
                    return tipos.indexOf(a.tipo) !== -1;
                }).length;
            }
        });

        Object.keys(counts).forEach(function(tab) {
            var badge = document.getElementById('tab-badge-' + tab);
            if (!badge) return;

            var count = counts[tab];
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
                // Cor do badge baseada na tab
                badge.className = 'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white';
                if (tab === 'todos') {
                    badge.style.backgroundColor = '#374151';
                } else if (count > 0) {
                    // Verificar severidade máxima dos alertas desta tab
                    var tabAlertas = tab === 'todos' ? alertas : alertas.filter(function(a) { return tabTipos[tab].indexOf(a.tipo) !== -1; });
                    var temAlta = tabAlertas.some(function(a) { return a.severidade === 'alta'; });
                    var temMedia = tabAlertas.some(function(a) { return a.severidade === 'media'; });
                    if (temAlta) {
                        badge.style.backgroundColor = '#dc2626';
                    } else if (temMedia) {
                        badge.style.backgroundColor = '#d97706';
                    } else {
                        badge.style.backgroundColor = '#9ca3af';
                    }
                }
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    function renderAlertasList(alertas) {
        // Agrupar alertas pelo tipo
        var grouped = {};
        var groupOrder = [];
        alertas.forEach(function(a) {
            var tipo = a.tipo || 'outros';
            if (!grouped[tipo]) {
                grouped[tipo] = [];
                groupOrder.push(tipo);
            }
            grouped[tipo].push(a);
        });

        var html = '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
        groupOrder.forEach(function(tipo) {
            var items = grouped[tipo];
            var primeiro = items[0];
            var maxSev = getMaxSeveridade(items);
            var totalAfetados = items.reduce(function(sum, a) { return sum + (a.total_afetados || 0); }, 0);

            html += '<div class="border-b border-gray-100 last:border-b-0">';
            html += '<div class="px-4 py-3 cursor-pointer hover:bg-gray-50/50 transition-colors alerta-grupo-header" data-tipo="' + escapeHtml(tipo) + '">';
            html += '<div class="flex items-center justify-between">';
            html += '<div class="flex items-center gap-2 min-w-0">';
            html += severidadeDot(maxSev);
            html += '<h3 class="text-sm font-medium text-gray-900 truncate">' + escapeHtml(formatTipoLabel(tipo)) + '</h3>';
            html += '<span class="hidden sm:inline-flex">' + categoriaBadge(primeiro.categoria) + '</span>';
            if (items.length === 1 && primeiro.cliente && primeiro.cliente.razao_social) {
                html += '<span class="hidden sm:inline text-xs text-gray-400">— ' + escapeHtml(primeiro.cliente.razao_social) + '</span>';
            }
            html += '</div>';
            html += '<div class="flex items-center gap-2 flex-shrink-0">';
            if (items.length > 1) {
                html += '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">' + items.length + '</span>';
            }
            if (totalAfetados) {
                html += '<span class="text-xs text-gray-500">' + totalAfetados + ' afetados</span>';
            }
            html += severidadeBadge(maxSev);
            html += '<svg class="w-4 h-4 text-gray-400 transition-transform alerta-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            html += '<div class="alerta-grupo-conteudo hidden border-t border-gray-100">';
            items.forEach(function(a) {
                html += renderAlertaCard(a);
            });
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    function renderEmptyStateTab() {
        var tabLabels = {
            todos: 'nenhuma categoria',
            notas_fiscais: 'Notas Fiscais',
            pis_cofins: 'PIS/COFINS',
            compliance: 'Compliance Cadastral',
            fornecedores: 'Fornecedores de Risco',
            importacao: 'Importação EFD'
        };
        var label = tabLabels[tabAtual] || tabAtual;
        var html = '<div class="bg-white rounded border border-gray-300">';
        html += '<div class="flex flex-col items-center justify-center py-16 text-gray-400">';
        html += '<div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center mb-3">';
        html += '<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        html += '</div>';
        html += '<p class="text-sm font-medium text-gray-500 mb-1">Nenhum alerta em ' + escapeHtml(label) + '</p>';
        html += '<p class="text-xs text-gray-400">Tudo certo nesta categoria.</p>';
        html += '</div></div>';
        return html;
    }

    // ─── Render: Por Tipo (legacy, kept for reference) ────────

    function renderAlertasPorTipo(alertas) {
        // 5 categorias fixas que sempre aparecem
        var categorias = [
            {
                key: 'notas_fiscais',
                label: 'Notas Fiscais',
                categoria: 'notas_fiscais',
                tipos: ['notas_duplicadas', 'notas_sem_participante', 'notas_valor_zerado', 'notas_sem_itens', 'notas_data_futura'],
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
            },
            {
                key: 'pis_cofins',
                label: 'PIS/COFINS',
                categoria: 'notas_fiscais',
                tipos: ['pis_cofins_incompleto'],
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'
            },
            {
                key: 'compliance',
                label: 'Compliance Cadastral',
                categoria: 'compliance',
                tipos: ['situacao_irregular', 'consulta_vencida', 'nunca_consultado', 'cnpj_situacao_irregular', 'participante_inativo', 'participante_sem_ie'],
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
            },
            {
                key: 'fornecedores',
                label: 'Fornecedores de Risco',
                categoria: 'compliance',
                tipos: ['fornecedor_irregular'],
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
            },
            {
                key: 'importacao',
                label: 'Importação EFD',
                categoria: 'importacao',
                tipos: ['gap_importacao', 'gap_temporal'],
                icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>'
            }
        ];

        // Agrupar alertas por tipo
        var alertasPorTipo = {};
        alertas.forEach(function(a) {
            var tipo = a.tipo || 'outros';
            if (!alertasPorTipo[tipo]) alertasPorTipo[tipo] = [];
            alertasPorTipo[tipo].push(a);
        });

        var html = '<div class="space-y-2">';

        categorias.forEach(function(cat) {
            // Coletar alertas que pertencem a esta categoria
            var items = [];
            cat.tipos.forEach(function(t) {
                if (alertasPorTipo[t]) {
                    items = items.concat(alertasPorTipo[t]);
                }
            });

            var temAlertas = items.length > 0;
            var totalAfetados = items.reduce(function(sum, a) { return sum + (a.total_afetados || 0); }, 0);
            var maxSev = temAlertas ? getMaxSeveridade(items) : null;

            html += '<div class="bg-white rounded border border-gray-300 overflow-hidden">';

            if (temAlertas) {
                // Wrapper COM alertas — expansível
                html += '<div class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors alerta-grupo-header" data-tipo="' + escapeHtml(cat.key) + '">';
                html += '<div class="flex items-center justify-between">';
                html += '<div class="flex items-center gap-2 min-w-0">';
                html += severidadeDot(maxSev);
                html += '<span class="text-gray-500">' + cat.icon + '</span>';
                html += '<h3 class="text-sm font-medium text-gray-900 truncate">' + escapeHtml(cat.label) + '</h3>';
                html += '<span class="hidden sm:inline-flex">' + categoriaBadge(cat.categoria) + '</span>';
                html += '</div>';
                html += '<div class="flex items-center gap-2 flex-shrink-0">';
                html += '<span class="text-xs text-gray-500">' + totalAfetados + ' afetados</span>';
                html += severidadeBadge(maxSev);
                html += '<svg class="w-4 h-4 text-gray-400 transition-transform alerta-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                html += '</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="alerta-grupo-conteudo hidden border-t border-gray-100">';
                items.forEach(function(a) {
                    html += renderAlertaCard(a);
                });
                html += '</div>';
            } else {
                // Wrapper SEM alertas — estado OK
                html += '<div class="px-4 py-3">';
                html += '<div class="flex items-center justify-between">';
                html += '<div class="flex items-center gap-2 min-w-0">';
                html += '<span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: #047857"></span>';
                html += '<span class="text-gray-400">' + cat.icon + '</span>';
                html += '<h3 class="text-sm font-medium text-gray-500">' + escapeHtml(cat.label) + '</h3>';
                html += '</div>';
                html += '<div class="flex items-center gap-1.5 flex-shrink-0">';
                html += '<svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                html += '<span class="text-xs text-gray-600 font-medium">Sem alertas</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
        });

        html += '</div>';
        return html;
    }

    // ─── Render: Por Cliente ──────────────────────────────────

    function renderAlertasPorCliente(alertas) {
        var grouped = {};
        alertas.forEach(function(a) {
            var key = a.cliente_id ? String(a.cliente_id) : 'sem_cliente';
            if (!grouped[key]) {
                grouped[key] = {
                    nome: (a.cliente && a.cliente.razao_social) || 'Sem cliente',
                    cnpj: (a.cliente && a.cliente.cnpj) || '',
                    alertas: []
                };
            }
            grouped[key].alertas.push(a);
        });

        var html = '<div class="space-y-2">';
        Object.keys(grouped).forEach(function(key) {
            var grupo = grouped[key];
            var items = grupo.alertas;
            var contagem = contarSeveridades(items);

            html += '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
            html += '<div class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors alerta-grupo-header" data-cliente="' + escapeHtml(key) + '">';
            html += '<div class="flex items-center justify-between gap-2">';
            html += '<div class="flex items-center gap-2 min-w-0">';
            html += '<h3 class="text-sm font-medium text-gray-900 truncate">' + escapeHtml(grupo.nome) + '</h3>';
            if (grupo.cnpj) {
                html += '<span class="text-xs text-gray-400 hidden sm:inline">' + escapeHtml(grupo.cnpj) + '</span>';
            }
            html += '</div>';
            html += '<div class="flex items-center gap-2 flex-shrink-0">';
            if (contagem.alta > 0) {
                html += '<span class="flex items-center gap-1 text-xs"><span class="w-2 h-2 bg-red-500 rounded-full"></span>' + contagem.alta + '</span>';
            }
            if (contagem.media > 0) {
                html += '<span class="flex items-center gap-1 text-xs"><span class="w-2 h-2 bg-yellow-500 rounded-full"></span>' + contagem.media + '</span>';
            }
            if (contagem.baixa > 0) {
                html += '<span class="flex items-center gap-1 text-xs"><span class="w-2 h-2 bg-gray-400 rounded-full"></span>' + contagem.baixa + '</span>';
            }
            html += '<svg class="w-4 h-4 text-gray-400 transition-transform alerta-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            html += '<div class="alerta-grupo-conteudo hidden border-t border-gray-100">';
            items.forEach(function(a) {
                html += '<div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-50 last:border-b-0 hover:bg-gray-50 cursor-pointer alerta-item-expand" data-alerta-id="' + a.id + '">';
                html += '<div class="flex items-center gap-2 min-w-0">';
                html += severidadeDot(a.severidade);
                html += '<span class="text-sm text-gray-900 truncate">' + escapeHtml(formatTipoLabel(a.tipo)) + '</span>';
                if (a.total_afetados) {
                    html += '<span class="text-xs text-gray-400">(' + a.total_afetados + ')</span>';
                }
                html += '</div>';
                html += '<div class="flex items-center gap-2 flex-shrink-0">';
                html += severidadeBadge(a.severidade);
                html += '</div>';
                html += '</div>';
                html += '<div class="alerta-detalhe-inline hidden" id="alerta-detalhe-' + a.id + '">';
                html += renderAlertaCard(a);
                html += '</div>';
            });
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    // ─── Render: Alert Card ───────────────────────────────────

    function renderAlertaCard(alerta) {
        var html = '<div class="px-4 sm:px-5 py-4 border-b border-gray-100 last:border-b-0">';
        html += '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">';
        html += '<div class="min-w-0">';
        html += '<div class="flex items-center gap-2 mb-1">';
        html += severidadeDot(alerta.severidade);
        html += '<span class="text-sm font-medium text-gray-900">' + escapeHtml(formatTipoLabel(alerta.tipo)) + '</span>';
        html += severidadeBadge(alerta.severidade);
        html += '</div>';
        html += '<p class="text-sm text-gray-600">' + escapeHtml(alerta.descricao || '') + '</p>';

        // Links para perfil do cliente e participante
        var links = [];
        var clienteNome = (alerta.cliente && alerta.cliente.razao_social) || null;
        if (alerta.cliente_id && clienteNome) {
            links.push('<span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' + profileLink('cliente', alerta.cliente_id, clienteNome) + '</span>');
        } else if (clienteNome) {
            links.push('Cliente: ' + escapeHtml(clienteNome));
        }
        if (alerta.participante_id) {
            var pNome = (alerta.participante && alerta.participante.razao_social) || (alerta.detalhes && alerta.detalhes.razao_social) || 'Ver participante';
            links.push('<span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>' + profileLink('participante', alerta.participante_id, pNome) + '</span>');
        }
        var notaId = (alerta.detalhes && alerta.detalhes.nota_id) || alerta.nota_id || null;
        if (notaId) {
            links.push('<span class="inline-flex items-center gap-1"><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><a href="' + getNotaUrl(notaId) + '" data-link class="text-gray-600 hover:text-gray-900 hover:underline">Abrir nota</a></span>');
        }
        if (links.length > 0) {
            html += '<div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-gray-500">' + links.join('') + '</div>';
        }

        html += '</div>';
        html += '<div class="flex items-center gap-1 flex-shrink-0">';
        html += renderActionButtons(alerta);
        html += '</div>';
        html += '</div>';

        // Detail section
        html += renderDetalhes(alerta);

        html += '</div>';
        return html;
    }

    // ─── Render: Action Buttons ───────────────────────────────

    function renderActionButtons(alerta) {
        var html = '';
        if (alerta.status !== 'resolvido') {
        html += '<a href="/app/alertas/' + alerta.id + '" data-link class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors" title="Ver detalhes e resolver">';
            html += '<span class="hidden sm:inline">Saiba Mais</span>';
            html += '<svg class="w-3.5 h-3.5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
            html += '</a>';
        }
        if (alerta.status !== 'ignorado') {
            html += '<button class="alerta-action-btn inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors" data-alerta-id="' + alerta.id + '" data-action="ignorado" title="Ignorar alerta">';
            html += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            html += '<span class="hidden sm:inline">Ignorar</span>';
            html += '</button>';
        }
        html += '<button class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-400 bg-gray-100 rounded cursor-not-allowed opacity-60" disabled title="Em breve — integracao WhatsApp">';
        html += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>';
        html += '<svg class="w-3 h-3 -ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>';
        html += '</button>';
        return html;
    }

    function getNotaUrl(notaId) {
        if (!notaId) return null;
        return '/app/notas-fiscais/efd/' + notaId;
    }

    function renderNotaAction(notaId, label, compact) {
        var url = getNotaUrl(notaId);
        if (!url) return '';

        if (compact) {
            return '<a href="' + url + '" data-link class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium">' +
                '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' +
                escapeHtml(label || 'Abrir Nota') +
                '</a>';
        }

        return '<a href="' + url + '" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors flex-shrink-0">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' +
            escapeHtml(label || 'Abrir Nota') +
            '</a>';
    }

    // ─── Render: Detail Tables ────────────────────────────────

    function renderDetalhes(alerta) {
        var detalhes = alerta.detalhes;
        if (!detalhes) return '';

        var tipo = alerta.tipo || '';
        var html = '<div class="mt-3">';

        if (tipo === 'notas_duplicadas' || tipo === 'notas_sem_participante' || tipo === 'notas_valor_zerado' || tipo === 'notas_sem_itens' || tipo === 'notas_data_futura') {
            html += renderTabelaNotas(detalhes);
        } else if (tipo === 'participante_inativo' || tipo === 'participante_sem_ie' || tipo === 'cnpj_situacao_irregular' || tipo === 'situacao_irregular' || tipo === 'consulta_vencida' || tipo === 'nunca_consultado') {
            html += renderTabelaCompliance(detalhes);
        } else if (tipo === 'fornecedor_irregular') {
            html += renderFornecedorIrregular(detalhes);
        } else if (tipo === 'gap_importacao') {
            html += renderGapTemporal(detalhes);
        } else if (tipo === 'gap_temporal') {
            html += renderGapTemporal(detalhes);
        } else if (tipo === 'pis_cofins_incompleto') {
            html += renderPisCofins(detalhes);
        } else {
            html += renderDetalhesGenerico(detalhes);
        }

        html += '</div>';
        return html;
    }

    function renderTabelaNotas(detalhes) {
        var itens = detalhes.itens || detalhes.notas || [];
        if (!Array.isArray(itens) || itens.length === 0) {
            return renderDetalhesGenerico(detalhes);
        }

        var html = '<div class="overflow-x-auto border border-gray-200">';
        html += '<table class="min-w-full">';
        html += '<thead><tr class="border-b border-gray-300">';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Número</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Série</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Modelo</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>';
        html += '<th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>';
        html += '<th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>';
        html += '</tr></thead><tbody>';

        itens.forEach(function(item) {
            var pId = item.participante_id || item.cod_part;
            html += '<tr class="hover:bg-gray-50/50 transition-colors">';
            html += '<td class="px-3 py-3 text-sm text-gray-700">' + escapeHtml(item.numero || item.num_doc || '-') + '</td>';
            html += '<td class="px-3 py-3 text-sm text-gray-700">' + escapeHtml(item.serie || '-') + '</td>';
            html += '<td class="px-3 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">' + escapeHtml(item.modelo || item.cod_mod || '-') + '</span></td>';
            html += '<td class="px-3 py-3 text-sm text-gray-700 max-w-[150px] truncate">' + escapeHtml(item.participante || item.participante_nome || '-') + '</td>';
            html += '<td class="px-3 py-3 text-sm text-gray-700">' + formatarData(item.data || item.dt_doc) + '</td>';
            html += '<td class="px-3 py-3 text-sm font-semibold text-gray-900 text-right font-mono">' + formatarMoeda(item.valor || item.vl_doc) + '</td>';
            html += '<td class="px-3 py-3 text-center">';
            html += '<div class="flex items-center justify-center gap-3">';
            if (item.nota_id) {
                html += renderNotaAction(item.nota_id, 'Abrir Nota', true);
            }
            if (pId) {
                html += '<a href="/app/participante/' + pId + '" data-link class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium">';
                html += '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
                html += 'Ver Participante</a>';
            }
            if (!item.nota_id && !pId) {
                html += '-';
            }
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        return html;
    }

    function renderTabelaCompliance(detalhes) {
        var itens = detalhes.itens || detalhes.participantes || [];
        if (!Array.isArray(itens) || itens.length === 0) {
            // Single participante in detalhes (not array)
            if (detalhes.participante_id || detalhes.cnpj || detalhes.razao_social) {
                return renderParticipanteCard(detalhes);
            }
            return renderDetalhesGenerico(detalhes);
        }

        var html = '<div class="overflow-x-auto border border-gray-200">';
        html += '<table class="min-w-full">';
        html += '<thead><tr class="border-b border-gray-300">';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>';
        html += '<th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status / Info</th>';
        html += '<th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Perfil</th>';
        html += '</tr></thead><tbody>';

        itens.forEach(function(item) {
            var pId = item.participante_id || item.id;
            html += '<tr class="hover:bg-gray-50/50 transition-colors">';
            html += '<td class="px-3 py-3 text-sm text-gray-700 max-w-[200px] truncate">' + escapeHtml(item.razao_social || item.nome || item.participante || '-') + '</td>';
            html += '<td class="px-3 py-3 text-sm text-gray-700 font-mono">' + formatCnpj(item.cnpj) + '</td>';
            html += '<td class="px-3 py-3 text-sm text-gray-700">' + escapeHtml(item.status || item.situacao || item.info || '-') + '</td>';
            html += '<td class="px-3 py-3 text-center">';
            if (pId) {
                html += '<a href="/app/participante/' + pId + '" data-link class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium">';
                html += '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
                html += 'Ver</a>';
            } else {
                html += '-';
            }
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        return html;
    }

    function renderParticipanteCard(detalhes) {
        var pId = detalhes.participante_id || detalhes.id;
        var html = '<div class="bg-white rounded border border-gray-300 p-4">';
        html += '<div class="flex items-start justify-between gap-3">';
        html += '<div class="min-w-0">';
        if (detalhes.razao_social) {
            html += '<p class="text-sm font-medium text-gray-900">' + escapeHtml(detalhes.razao_social) + '</p>';
        }
        if (detalhes.cnpj) {
            html += '<p class="text-xs text-gray-500 mt-0.5">CNPJ: ' + formatCnpj(detalhes.cnpj) + '</p>';
        }
        if (detalhes.situacao_cadastral) {
            var sitStyle = detalhes.situacao_cadastral === 'ATIVA' ? '#047857' : '#dc2626';
            html += '<p class="mt-1.5"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + sitStyle + '">' + escapeHtml(detalhes.situacao_cadastral) + '</span></p>';
        }
        if (detalhes.ultima_consulta_em) {
            html += '<p class="text-xs text-gray-400 mt-1">Última consulta: ' + formatarData(detalhes.ultima_consulta_em) + '</p>';
        }
        html += '</div>';
        html += '<div class="flex items-center gap-2 flex-shrink-0">';
        if (detalhes.nota_id) {
            html += renderNotaAction(detalhes.nota_id, 'Abrir Nota');
        }
        if (pId) {
            html += '<a href="/app/participante/' + pId + '" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors flex-shrink-0">';
            html += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
            html += 'Ver Participante</a>';
        }
        html += '</div>';
        html += '</div>';
        html += '</div>';
        return html;
    }

    function renderGapTemporal(detalhes) {
        var meses = detalhes.meses_faltantes || detalhes.meses || [];
        if (!Array.isArray(meses) || meses.length === 0) {
            return renderDetalhesGenerico(detalhes);
        }

        var html = '<div class="flex flex-wrap gap-2">';
        meses.forEach(function(mes) {
            html += '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">' + escapeHtml(mes) + '</span>';
        });
        html += '</div>';
        if (detalhes.mensagem) {
            html += '<p class="text-xs text-gray-500 mt-2">' + escapeHtml(detalhes.mensagem) + '</p>';
        }
        return html;
    }

    function renderPisCofins(detalhes) {
        var stats = detalhes.stats || detalhes;
        var html = '<div class="grid grid-cols-3 gap-3">';

        var items = [
            { label: 'Total de Notas', value: stats.total_notas || stats.total || 0 },
            { label: 'Com PIS/COFINS', value: stats.com_pis_cofins || stats.completas || 0 },
            { label: 'Sem PIS/COFINS', value: stats.sem_pis_cofins || stats.incompletas || 0 }
        ];

        items.forEach(function(item) {
            html += '<div class="bg-white rounded border border-gray-300 p-3 text-center">';
            html += '<p class="text-lg font-semibold text-gray-900">' + escapeHtml(String(item.value)) + '</p>';
            html += '<p class="text-xs text-gray-500">' + escapeHtml(item.label) + '</p>';
            html += '</div>';
        });

        html += '</div>';
        if (detalhes.mensagem) {
            html += '<p class="text-xs text-gray-500 mt-2">' + escapeHtml(detalhes.mensagem) + '</p>';
        }
        return html;
    }

    function renderFornecedorIrregular(detalhes) {
        var pId = detalhes.participante_id;
        var html = '<div class="bg-white rounded border border-gray-300 p-4">';
        html += '<div class="flex items-start justify-between gap-3 mb-3">';
        html += '<div class="min-w-0">';
        if (detalhes.razao_social) {
            html += '<p class="text-sm font-medium text-gray-900">' + escapeHtml(detalhes.razao_social) + '</p>';
        }
        if (detalhes.cnpj) {
            html += '<p class="text-xs text-gray-500 mt-0.5">CNPJ: ' + formatCnpj(detalhes.cnpj) + '</p>';
        }
        html += '</div>';
        html += '<div class="flex items-center gap-2 flex-shrink-0">';
        if (detalhes.nota_id) {
            html += renderNotaAction(detalhes.nota_id, 'Abrir Nota');
        }
        if (pId) {
            html += '<a href="/app/participante/' + pId + '" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors flex-shrink-0">';
            html += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
            html += 'Ver Participante</a>';
        }
        html += '</div>';
        html += '</div>';

        html += '<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">';
        var sitClass = (detalhes.situacao_cadastral && detalhes.situacao_cadastral !== 'ATIVA') ? 'text-red-600' : 'text-gray-900';
        var items = [
            { label: 'Situação', value: detalhes.situacao_cadastral || '-', cls: sitClass },
            { label: 'Notas vinculadas', value: detalhes.total_notas || 0, cls: 'text-gray-900' },
            { label: 'Valor em risco', value: formatarMoeda(detalhes.valor_em_risco), cls: 'text-red-600' }
        ];
        items.forEach(function(item) {
            html += '<div class="bg-gray-50 rounded border border-gray-200 p-3 text-center">';
            html += '<p class="text-sm font-semibold ' + item.cls + '">' + escapeHtml(String(item.value)) + '</p>';
            html += '<p class="text-xs text-gray-500 mt-0.5">' + escapeHtml(item.label) + '</p>';
            html += '</div>';
        });
        html += '</div>';

        html += '</div>';
        return html;
    }

    function renderDetalhesGenerico(detalhes) {
        if (typeof detalhes === 'string') {
            return '<p class="text-sm text-gray-600">' + escapeHtml(detalhes) + '</p>';
        }

        // Campos a ocultar (IDs internos)
        var hiddenKeys = ['participante_id', 'id', 'cliente_id', 'hash'];
        // Labels legíveis
        var keyLabels = {
            razao_social: 'Razão Social',
            cnpj: 'CNPJ',
            situacao_cadastral: 'Situação Cadastral',
            ultima_consulta_em: 'Última Consulta',
            total_notas: 'Total de Notas',
            valor_em_risco: 'Valor em Risco',
            total_meses: 'Total de Meses',
            meses_faltantes: 'Meses Faltantes',
            mensagem: 'Observação'
        };

        var entries = [];
        Object.keys(detalhes).forEach(function(key) {
            if (hiddenKeys.indexOf(key) !== -1) return;
            var val = detalhes[key];
            if (typeof val === 'object' && val !== null && !Array.isArray(val)) return;

            var label = keyLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            var displayVal;
            if (key === 'cnpj') {
                displayVal = formatCnpj(val);
            } else if (key === 'ultima_consulta_em') {
                displayVal = formatarData(val);
            } else if (key === 'valor_em_risco') {
                displayVal = formatarMoeda(val);
            } else if (Array.isArray(val)) {
                displayVal = escapeHtml(val.join(', '));
            } else {
                displayVal = escapeHtml(String(val));
            }
            entries.push({ label: label, value: displayVal });
        });

        if (entries.length === 0) return '';

        var html = '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
        html += '<table class="min-w-full text-xs">';
        entries.forEach(function(entry, i) {
            var bgClass = i % 2 === 0 ? 'bg-gray-50/50' : 'bg-white';
            html += '<tr class="' + bgClass + '">';
            html += '<td class="py-2 px-3 font-medium text-gray-500 whitespace-nowrap w-1/3">' + escapeHtml(entry.label) + '</td>';
            html += '<td class="py-2 px-3 text-gray-900">' + entry.value + '</td>';
            html += '</tr>';
        });
        html += '</table>';

        // Link para participante se disponível
        if (detalhes.participante_id) {
            html += '<div class="px-3 py-2 border-t border-gray-100 bg-gray-50/50">';
            if (detalhes.nota_id) {
                html += renderNotaAction(detalhes.nota_id, 'Abrir Nota', true);
                html += '<span class="mx-2 text-gray-300">|</span>';
            }
            html += '<a href="/app/participante/' + detalhes.participante_id + '" data-link class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 hover:text-gray-900 hover:underline">';
            html += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
            html += 'Ver perfil do participante</a>';
            html += '</div>';
        } else if (detalhes.nota_id) {
            html += '<div class="px-3 py-2 border-t border-gray-100 bg-gray-50/50">';
            html += renderNotaAction(detalhes.nota_id, 'Abrir Nota', true);
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    // ─── Render: Empty State ──────────────────────────────────

    function renderEmptyState() {
        var html = '<div class="bg-white rounded border border-gray-300">';
        html += '<div class="flex flex-col items-center justify-center py-20 sm:py-28 text-gray-400">';
        html += '<div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center mb-4">';
        html += '<svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        html += '</div>';
        html += '<p class="text-base font-medium text-gray-500 mb-1">Nenhum alerta encontrado</p>';
        html += '<p class="text-sm text-gray-400">Ajuste os filtros ou aguarde o próximo cálculo de alertas.</p>';
        html += '</div></div>';
        return html;
    }

    // ─── Render: Skeleton ─────────────────────────────────────

    function renderSkeleton() {
        var html = '<div class="space-y-2">';
        for (var i = 0; i < 5; i++) {
            html += '<div class="bg-white rounded border border-gray-300 px-4 py-3">';
            html += '<div class="flex items-center gap-2">';
            html += '<div class="alerta-skeleton w-2.5 h-2.5 rounded-full">&nbsp;</div>';
            html += '<div class="alerta-skeleton h-4 w-48">&nbsp;</div>';
            html += '<div class="ml-auto alerta-skeleton h-4 w-20">&nbsp;</div>';
            html += '</div>';
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    // ─── Render: Pagination ───────────────────────────────────

    function renderPaginacao(data) {
        var pagEl = document.getElementById('alertas-paginacao');
        if (!pagEl) return;

        var lastPage = data.last_page || 1;
        var currentPage = data.current_page || 1;

        if (lastPage <= 1) {
            pagEl.innerHTML = '';
            pagEl.classList.add('hidden');
            return;
        }

        pagEl.classList.remove('hidden');
        var html = '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">';
        html += '<span class="text-[10px] text-gray-500 uppercase tracking-wide">Página ' + currentPage + ' de ' + lastPage + '</span>';
        html += '<div class="flex items-center justify-center gap-1">';

        // Previous
        html += '<button class="alerta-page-btn px-3 py-1.5 text-[10px] border rounded ' + (currentPage <= 1 ? 'border-gray-200 text-gray-300 cursor-not-allowed' : 'border-gray-300 text-gray-700 hover:bg-gray-50') + '" data-page="' + (currentPage - 1) + '" ' + (currentPage <= 1 ? 'disabled' : '') + '>';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>';
        html += '</button>';

        // Page numbers
        var start = Math.max(1, currentPage - 2);
        var end = Math.min(lastPage, currentPage + 2);

        if (start > 1) {
            html += '<button class="alerta-page-btn px-3 py-1.5 text-[10px] border border-gray-300 text-gray-700 rounded hover:bg-gray-50" data-page="1">1</button>';
            if (start > 2) {
                html += '<span class="px-2 text-gray-400">...</span>';
            }
        }

        for (var p = start; p <= end; p++) {
            if (p === currentPage) {
                html += '<button class="px-3 py-1.5 text-[10px] font-bold text-white rounded" style="background-color: #1f2937" disabled>' + p + '</button>';
            } else {
                html += '<button class="alerta-page-btn px-3 py-1.5 text-[10px] border border-gray-300 text-gray-700 rounded hover:bg-gray-50" data-page="' + p + '">' + p + '</button>';
            }
        }

        if (end < lastPage) {
            if (end < lastPage - 1) {
                html += '<span class="px-2 text-gray-400">...</span>';
            }
            html += '<button class="alerta-page-btn px-3 py-1.5 text-[10px] border border-gray-300 text-gray-700 rounded hover:bg-gray-50" data-page="' + lastPage + '">' + lastPage + '</button>';
        }

        // Next
        html += '<button class="alerta-page-btn px-3 py-1.5 text-[10px] border rounded ' + (currentPage >= lastPage ? 'border-gray-200 text-gray-300 cursor-not-allowed' : 'border-gray-300 text-gray-700 hover:bg-gray-50') + '" data-page="' + (currentPage + 1) + '" ' + (currentPage >= lastPage ? 'disabled' : '') + '>';
        html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
        html += '</button>';

        html += '</div>';
        html += '</div>';
        pagEl.innerHTML = html;

        // Bind page buttons
        pagEl.querySelectorAll('.alerta-page-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page) && page >= 1) {
                    loadAlertas(page);
                    var container = document.getElementById('alertas-central-container');
                    if (container) container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // ─── Helpers: Grouping ────────────────────────────────────

    function getMaxSeveridade(items) {
        var order = { alta: 3, media: 2, baixa: 1 };
        var max = 0;
        var maxSev = 'baixa';
        items.forEach(function(a) {
            var val = order[a.severidade] || 0;
            if (val > max) { max = val; maxSev = a.severidade; }
        });
        return maxSev;
    }

    function contarSeveridades(items) {
        var r = { alta: 0, media: 0, baixa: 0 };
        items.forEach(function(a) {
            if (r.hasOwnProperty(a.severidade)) r[a.severidade]++;
        });
        return r;
    }

    function formatTipoLabel(tipo) {
        var labels = {
            notas_duplicadas: 'Notas Duplicadas',
            notas_sem_participante: 'Notas sem Participante',
            notas_valor_zerado: 'Notas com Valor Zerado',
            notas_sem_itens: 'Notas sem Itens',
            notas_data_futura: 'Notas com Data Futura',
            participante_inativo: 'Participante Inativo',
            participante_sem_ie: 'Participante sem IE',
            cnpj_situacao_irregular: 'CNPJ com Situação Irregular',
            gap_importacao: 'Gap de Importação',
            gap_temporal: 'Gap Temporal de Importação',
            pis_cofins_incompleto: 'PIS/COFINS Incompleto',
            situacao_irregular: 'Situação Cadastral Irregular',
            consulta_vencida: 'Consulta Vencida',
            nunca_consultado: 'Nunca Consultado'
        };
        return labels[tipo] || tipo.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    // ─── Actions ──────────────────────────────────────────────

    async function marcarStatus(id, status, btn) {
        if (!id || !status) return;

        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';

        try {
            await postJson('/app/alertas/' + id + '/status', { status: status });

            // Refresh KPIs and list
            try {
                var novoResumo = await fetchJson('/app/alertas/resumo');
                resumoData = novoResumo;
                renderKpis(resumoData);
            } catch (e) { /* ignore KPI refresh error */ }

            loadAlertas(paginaAtual);
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Erro ao atualizar status do alerta. Tente novamente.');
        }
    }

    async function recalcularAlertas() {
        if (isRecalculando) return;
        isRecalculando = true;

        var btnIcon = document.getElementById('recalcular-icon');
        var btnSpinner = document.getElementById('recalcular-spinner');
        var btn = document.getElementById('btn-recalcular');

        if (btnIcon) btnIcon.classList.add('hidden');
        if (btnSpinner) btnSpinner.classList.remove('hidden');
        if (btn) btn.disabled = true;

        try {
            var result = await postJson('/app/alertas/recalcular', {});

            if (result.resumo) {
                resumoData = result.resumo;
                renderKpis(resumoData);
            }

            loadAlertas(1);
            loadEvolucao();
        } catch (e) {
            alert('Erro ao recalcular alertas. Tente novamente.');
        } finally {
            isRecalculando = false;
            if (btnIcon) btnIcon.classList.remove('hidden');
            if (btnSpinner) btnSpinner.classList.add('hidden');
            if (btn) btn.disabled = false;
        }
    }

    // ─── Setup: Event Listeners ───────────────────────────────

    function setupAlertaActions() {
        document.querySelectorAll('.alerta-action-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var id = this.getAttribute('data-alerta-id');
                var action = this.getAttribute('data-action');
                marcarStatus(id, action, this);
            });
        });
    }

    function setupExpandToggle() {
        document.querySelectorAll('.alerta-grupo-header').forEach(function(header) {
            header.addEventListener('click', function() {
                var conteudo = this.nextElementSibling;
                var chevron = this.querySelector('.alerta-chevron');
                if (conteudo && conteudo.classList.contains('alerta-grupo-conteudo')) {
                    conteudo.classList.toggle('hidden');
                    if (chevron) {
                        chevron.style.transform = conteudo.classList.contains('hidden') ? '' : 'rotate(180deg)';
                    }
                }
            });
        });

        document.querySelectorAll('.alerta-item-expand').forEach(function(item) {
            item.addEventListener('click', function() {
                var id = this.getAttribute('data-alerta-id');
                var detalhe = document.getElementById('alerta-detalhe-' + id);
                if (detalhe) {
                    detalhe.classList.toggle('hidden');
                }
            });
        });
    }

    function setupFiltros() {
        var btnFiltrar = document.getElementById('btn-filtrar-alertas');
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', function() {
                filtros.severidade = document.getElementById('alerta-filtro-severidade').value;
                filtros.cliente_id = document.getElementById('alerta-filtro-cliente').value;
                filtros.status = document.getElementById('alerta-filtro-status').value;
                loadAlertas(1);
            });
        }
    }

    function setupTabs() {
        var tabs = document.querySelectorAll('.alerta-tab');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var newTab = this.getAttribute('data-alerta-tab');
                if (newTab === tabAtual) return;
                tabAtual = newTab;

                // Update active tab styles
                tabs.forEach(function(t) {
                    t.classList.remove('active', 'border-gray-800', 'text-gray-900');
                    t.classList.add('border-transparent', 'text-gray-500');
                });
                this.classList.remove('border-transparent', 'text-gray-500');
                this.classList.add('active', 'border-gray-800', 'text-gray-900');

                // Re-render with current data (no new fetch needed)
                if (alertasData && alertasData.data) {
                    var listaEl = document.getElementById('alertas-lista');
                    var filtered = filterByTab(alertasData.data);
                    if (filtered.length === 0) {
                        listaEl.innerHTML = renderEmptyStateTab();
                    } else {
                        listaEl.innerHTML = renderAlertasList(filtered);
                    }
                    setupAlertaActions();
                    setupExpandToggle();
                }
            });
        });
    }

    function setupKpiClicks() {
        var kpiCards = document.querySelectorAll('[data-filtro-severidade]');
        kpiCards.forEach(function(card) {
            card.addEventListener('click', function() {
                var sev = this.getAttribute('data-filtro-severidade');
                filtros.severidade = sev;
                var selectEl = document.getElementById('alerta-filtro-severidade');
                if (selectEl) selectEl.value = sev;

                // Visual feedback
                kpiCards.forEach(function(c) { c.classList.remove('ring-2', 'ring-gray-400'); });
                this.classList.add('ring-2', 'ring-gray-400');

                loadAlertas(1);
            });
        });
    }

    function setupRecalcular() {
        var btn = document.getElementById('btn-recalcular');
        if (btn) {
            btn.addEventListener('click', function() {
                recalcularAlertas();
            });
        }
    }

    // ─── Cleanup ──────────────────────────────────────────────

    function cleanup() {
        if (evolucaoChart) {
            evolucaoChart.destroy();
            evolucaoChart = null;
        }
        alertasData = null;
        resumoData = null;
        expandedAlerts = {};
    }

    // ─── Init ─────────────────────────────────────────────────

    renderKpis(resumoData);
    loadEvolucao();
    loadAlertas();
    setupFiltros();
    setupTabs();
    setupKpiClicks();
    setupRecalcular();

    // Register cleanup for SPA navigation
    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.alertasCentral = cleanup;
})();
</script>
