/**
 * BI Dashboard - JavaScript
 * Gerencia os gráficos e filtros do BI Fiscal
 */

(function() {
    'use strict';

    // Estado global
    let charts = {};
    let currentTab = 'faturamento';
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let participantesData = null;
    let tipoAtivo = 'fornecedores';
    let participanteAberto = null;
    let menuUltimaNotaAberto = null;
    let _initRetries = 0;

    /**
     * Injeta opções responsive do ApexCharts para mobile/tablet.
     * Chamada por renderChart() antes de criar o gráfico.
     */
    function mobileChartOptions(options) {
        if (!options.responsive) {
            options.responsive = [];
        }
        // Tablet breakpoint (<=1024px)
        options.responsive.push({
            breakpoint: 1024,
            options: {
                chart: { height: 288 },
                xaxis: { labels: { style: { fontSize: '11px' } } },
                legend: { fontSize: '12px' }
            }
        });
        // Mobile breakpoint (<=640px)
        options.responsive.push({
            breakpoint: 640,
            options: {
                chart: { height: 224 },
                xaxis: {
                    labels: {
                        style: { fontSize: '10px' },
                        rotate: -60,
                        maxHeight: 60
                    }
                },
                legend: { position: 'bottom', fontSize: '11px' },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: '60%' } } },
                tooltip: { style: { fontSize: '11px' } }
            }
        });
        return options;
    }

    /**
     * Configura o comportamento scroll-fade: esconde gradiente quando scroll atinge o fim.
     */
    function setupScrollFade(container) {
        if (!container) return;
        function checkScroll() {
            const atEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
            container.classList.toggle('scrolled-end', atEnd);
        }
        container.addEventListener('scroll', checkScroll, { passive: true });
        // Check inicial (pode já estar sem overflow)
        checkScroll();
    }

    // Inicialização
    function init() {
        // Ler tab padrão definida pelo servidor
        const tabNav = document.querySelector('[data-default-tab]');
        const defaultTab = (tabNav && tabNav.dataset.defaultTab) ? tabNav.dataset.defaultTab : 'faturamento';

        currentTab = defaultTab;
        _initRetries = 0;

        // Tabs e filtros não dependem de ApexCharts — configurar imediatamente
        setupTabs();
        setupFilters();

        setupParticipantes();

        // Setup scroll fade para containers com scroll horizontal
        document.querySelectorAll('.scroll-fade-right, .scroll-fade-right-white').forEach(setupScrollFade);

        // Carregar dados da tab padrão; renderChart() já lida com retry interno se ApexCharts ainda não estiver pronto
        switchTab(defaultTab);
    }

    // Configura as tabs
    function setupTabs() {
        document.querySelectorAll('.bi-tab').forEach(tab => {
            if (tab._biListenerAdded) return;
            tab._biListenerAdded = true;
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                switchTab(tabName);
            });
        });
    }

    // Troca de tab
    function switchTab(tabName) {
        // Atualiza tabs
        document.querySelectorAll('.bi-tab').forEach(tab => {
            if (tab.dataset.tab === tabName) {
                tab.classList.add('active', 'border-gray-800', 'text-gray-900');
                tab.classList.remove('border-transparent', 'text-gray-500');
            } else {
                tab.classList.remove('active', 'border-gray-800', 'text-gray-900');
                tab.classList.add('border-transparent', 'text-gray-500');
            }
        });

        // Atualiza conteúdo
        document.querySelectorAll('.bi-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        const targetContent = document.getElementById('tab-' + tabName);
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }

        currentTab = tabName;
        loadData(tabName);
    }

    // Configura filtros
    function setupFilters() {
        const filtroCliente = document.getElementById('filtro-cliente');
        const filtroPeriodo = document.getElementById('filtro-periodo');

        if (filtroCliente && !filtroCliente._biListenerAdded) {
            filtroCliente._biListenerAdded = true;
            filtroCliente.addEventListener('change', () => { loadData(currentTab); updateResumoKpis(); });
        }

        if (filtroPeriodo && !filtroPeriodo._biListenerAdded) {
            filtroPeriodo._biListenerAdded = true;
            filtroPeriodo.addEventListener('change', () => { loadData(currentTab); updateResumoKpis(); });
        }
    }

    // Atualiza KPIs do resumo geral via AJAX
    async function updateResumoKpis() {
        const params = getFilterParams();
        try {
            const response = await fetch(`/app/bi/resumo?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!response.ok) return;
            const resumo = await response.json();
            setKpi('kpi-faturamento', formatCompactCurrency(resumo.total_vendas || 0));
            setKpi('kpi-faturamento-notas', (resumo.total_notas || 0).toLocaleString('pt-BR') + ' notas emitidas');
            setKpi('kpi-faturamento-aliquota', (resumo.aliquota_media || 0) + '%');
            setKpi('kpi-aquisicoes', formatCompactCurrency(resumo.total_compras || 0));
            setKpi('kpi-tributacao', formatCompactCurrency(resumo.total_tributos || 0));
            setKpi('kpi-sec-fornecedores', resumo.total_fornecedores || 0);
        } catch (e) {
            console.error('Erro ao atualizar resumo KPIs:', e);
        }
    }

    // Obtém parâmetros de filtro
    function getFilterParams() {
        const clienteId = document.getElementById('filtro-cliente')?.value;
        const meses = parseInt(document.getElementById('filtro-periodo')?.value || 0);

        const params = new URLSearchParams();
        if (clienteId) params.append('cliente_id', clienteId);

        if (meses > 0) {
            const dataFim = new Date();
            const dataInicio = new Date();
            dataInicio.setMonth(dataInicio.getMonth() - meses);
            params.append('data_inicio', dataInicio.toISOString().split('T')[0]);
            params.append('data_fim', dataFim.toISOString().split('T')[0]);
        }

        return params.toString();
    }

    // Carrega dados da API
    async function loadData(tabName) {
        const params = getFilterParams();
        let endpoint = '';

        switch (tabName) {
            case 'faturamento':
                endpoint = '/app/bi/faturamento';
                break;
            case 'compras':
                endpoint = '/app/bi/compras';
                break;
            case 'tributos':
                endpoint = '/app/bi/tributos';
                break;
            case 'efd':
                endpoint = '/app/bi/efd';
                break;
            case 'participantes':
                endpoint = '/app/bi/participantes';
                break;
            case 'riscos':
                endpoint = '/app/bi/riscos';
                break;
            case 'tributario-efd':
                endpoint = '/app/bi/tributario-efd';
                break;
        }

        try {
            const response = await fetch(`${endpoint}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Erro ao carregar dados');

            const data = await response.json();
            renderCharts(tabName, data);
            hideEmptyState(tabName);
        } catch (error) {
            console.error('Erro ao carregar tab ' + tabName + ':', error);
            showTabError(tabName);
        }
    }

    // Renderiza gráficos baseado na tab
    function renderCharts(tabName, data) {
        switch (tabName) {
            case 'faturamento':
                renderFaturamentoCharts(data);
                break;
            case 'compras':
                renderComprasCharts(data);
                break;
            case 'tributos':
                renderTributosCharts(data);
                break;
            case 'efd':
                renderEfdCharts(data);
                break;
            case 'participantes':
                renderParticipantesCharts(data);
                break;
            case 'riscos':
                renderRiscosCharts(data);
                break;
            case 'tributario-efd':
                renderTributarioEfdCharts(data);
                break;
        }
    }

    // Gráficos de Faturamento
    function renderFaturamentoCharts(data) {
        // Faturamento Mensal
        if (data.faturamento_mensal && data.faturamento_mensal.length > 0) {
            renderChart('chart-faturamento', {
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Faturamento',
                    data: data.faturamento_mensal.map(d => d.faturamento)
                }],
                xaxis: {
                    categories: data.faturamento_mensal.map(d => d.mes_formatado)
                },
                yaxis: {
                    labels: {
                        formatter: (val) => formatCurrency(val)
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
                colors: ['#374151'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-faturamento'); }

        // Top Clientes
        if (data.top_clientes && data.top_clientes.length > 0) {
            renderChart('chart-top-clientes', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Valor',
                    data: data.top_clientes.map(d => d.total)
                }],
                xaxis: {
                    categories: data.top_clientes.map(d => truncate(d.razao_social || d.cnpj, 20)),
                    labels: { rotate: -45, style: { fontSize: '10px' } }
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                dataLabels: { enabled: false },
                colors: ['#047857'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-top-clientes'); }

        // Faturamento por UF
        if (data.faturamento_por_uf && data.faturamento_por_uf.length > 0) {
            renderChart('chart-faturamento-uf', {
                chart: { type: 'donut', height: 320 },
                series: data.faturamento_por_uf.map(d => d.total),
                labels: data.faturamento_por_uf.map(d => d.uf),
                legend: { position: 'bottom' },
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-faturamento-uf'); }
    }

    // Gráficos de Compras
    function renderComprasCharts(data) {
        // Entradas vs Saídas
        if (data.entradas_vs_saidas && data.entradas_vs_saidas.length > 0) {
            renderChart('chart-entradas-saidas', {
                chart: { type: 'bar', height: 320, stacked: false, toolbar: { show: false } },
                series: [
                    { name: 'Entradas', data: data.entradas_vs_saidas.map(d => d.entradas) },
                    { name: 'Saídas', data: data.entradas_vs_saidas.map(d => d.saidas) }
                ],
                xaxis: {
                    categories: data.entradas_vs_saidas.map(d => d.mes_formatado)
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                colors: ['#047857', '#d97706'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-entradas-saidas'); }

        // Top Fornecedores
        if (data.top_fornecedores && data.top_fornecedores.length > 0) {
            renderChart('chart-top-fornecedores', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Valor',
                    data: data.top_fornecedores.map(d => d.total)
                }],
                xaxis: {
                    categories: data.top_fornecedores.map(d => truncate(d.razao_social || d.cnpj, 20)),
                    labels: { rotate: -45, style: { fontSize: '10px' } }
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                dataLabels: { enabled: false },
                colors: ['#d97706'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-top-fornecedores'); }

        // Devoluções
        if (data.devolucoes && data.devolucoes.length > 0) {
            renderChart('chart-devolucoes', {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Devoluções',
                    data: data.devolucoes.map(d => d.valor_devolucoes)
                }],
                xaxis: {
                    categories: data.devolucoes.map(d => d.mes_formatado)
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                stroke: { curve: 'smooth', width: 2 },
                markers: { size: 4 },
                colors: ['#dc2626'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-devolucoes'); }
    }

    // Gráficos de Tributos
    function renderTributosCharts(data) {
        // Carga Tributária Mensal
        if (data.carga_tributaria && data.carga_tributaria.length > 0) {
            renderChart('chart-carga-tributaria', {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Faturamento', data: data.carga_tributaria.map(d => d.faturamento), type: 'column' },
                    { name: 'Tributos', data: data.carga_tributaria.map(d => d.tributos_total), type: 'column' }
                ],
                xaxis: {
                    categories: data.carga_tributaria.map(d => d.mes_formatado)
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { columnWidth: '50%' } },
                dataLabels: { enabled: false },
                colors: ['#374151', '#dc2626'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-carga-tributaria'); }

        // Tributos por Tipo
        if (data.tributos_por_tipo && data.tributos_por_tipo.length > 0) {
            const tributosFiltrados = data.tributos_por_tipo.filter(d => d.valor > 0);
            if (tributosFiltrados.length > 0) {
                renderChart('chart-tributos-tipo', {
                    chart: { type: 'pie', height: 320 },
                    series: tributosFiltrados.map(d => d.valor),
                    labels: tributosFiltrados.map(d => d.tipo),
                    legend: { position: 'bottom' },
                    colors: ['#374151', '#047857', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#65a30d', '#db2777', '#4f46e5'],
                    tooltip: {
                        y: { formatter: (val) => formatCurrency(val) }
                    }
                });
            } else { setEmptyChart('chart-tributos-tipo'); }
        } else { setEmptyChart('chart-tributos-tipo'); }

        // Alíquota Efetiva
        if (data.carga_tributaria && data.carga_tributaria.length > 0) {
            renderChart('chart-aliquota-efetiva', {
                chart: { type: 'area', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Alíquota Efetiva',
                    data: data.carga_tributaria.map(d => d.aliquota_efetiva)
                }],
                xaxis: {
                    categories: data.carga_tributaria.map(d => d.mes_formatado)
                },
                yaxis: {
                    labels: { formatter: (val) => val.toFixed(2) + '%' },
                    min: 0,
                    max: 50
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
                colors: ['#374151'],
                tooltip: {
                    y: { formatter: (val) => val.toFixed(2) + '%' }
                }
            });
        } else { setEmptyChart('chart-aliquota-efetiva'); }
    }

    // Gráficos EFD
    function renderEfdCharts(data) {
        const kpis = data.kpis || {};

        // Atualiza KPIs consolidados (dados EFD)
        setKpi('kpi-aquisicoes-notas', (kpis.total_entradas_notas || 0).toLocaleString('pt-BR') + ' notas recebidas');
        setKpi('kpi-aquisicoes-ticket', formatCompactCurrency(kpis.ticket_medio || 0));
        setKpi('kpi-tributacao-efd', formatCompactCurrency(kpis.carga_tributaria || 0));
        setKpi('kpi-saldo-entradas', formatCompactCurrency(kpis.total_entradas_valor || 0));
        setKpi('kpi-saldo-saidas', formatCompactCurrency(kpis.total_saidas_valor || 0));

        const saldo = kpis.saldo_liquido || 0;
        const saldoEl = document.getElementById('kpi-saldo');
        if (saldoEl) {
            saldoEl.textContent = formatCompactCurrency(saldo);
            saldoEl.className = saldoEl.className.replace(/text-(gray|rose)-\d+/g, '');
            saldoEl.classList.add(saldo >= 0 ? 'text-gray-900' : 'text-rose-600');
        }

        // Barra de métricas secundárias
        setKpi('kpi-sec-participantes', kpis.participantes_ativos || 0);
        setKpi('kpi-sec-sem-itens', kpis.notas_sem_itens || 0);

        const riscoVal = kpis.notas_em_risco || 0;
        const riscoEl = document.getElementById('kpi-sec-risco');
        if (riscoEl) {
            riscoEl.textContent = riscoVal;
            riscoEl.className = riscoEl.className.replace(/text-(rose|gray)-\d+/g, '');
            riscoEl.classList.add(riscoVal > 0 ? 'text-rose-600' : 'text-gray-900');
        }

        // Fluxo Mensal
        if (data.fluxo_mensal && data.fluxo_mensal.length > 0) {
            renderChart('chart-efd-fluxo', {
                chart: { type: 'bar', height: 320, stacked: false, toolbar: { show: false } },
                series: [
                    { name: 'Entradas', data: data.fluxo_mensal.map(d => d.entradas) },
                    { name: 'Saídas', data: data.fluxo_mensal.map(d => d.saidas) }
                ],
                xaxis: {
                    categories: data.fluxo_mensal.map(d => d.label)
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                colors: ['#047857', '#d97706'],
                tooltip: {
                    y: { formatter: (val) => formatCurrency(val) }
                }
            });
        } else { setEmptyChart('chart-efd-fluxo'); }

        // Volume por Bloco
        const blocos = data.volume_blocos || {};
        const ns = blocos.notas_servicos || blocos.A || { notas: 0, valor: 0 };
        const nm = blocos.notas_mercadorias || blocos.C || { notas: 0, valor: 0 };
        const nt = blocos.notas_transportes || blocos.D || { notas: 0, valor: 0 };
        const totalBlocos = (ns.notas || 0) + (nm.notas || 0) + (nt.notas || 0);
        const blcoEl = document.getElementById('chart-efd-blocos');
        if (blcoEl) {
            if (totalBlocos === 0) {
                blcoEl.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem dados</div>';
            } else {
                renderChart('chart-efd-blocos', {
                    chart: { type: 'donut', height: 320 },
                    series: [ns.valor, nm.valor, nt.valor],
                    labels: ['Serviços', 'Mercadorias', 'Transportes'],
                    legend: { position: 'bottom' },
                    tooltip: {
                        y: { formatter: (val) => formatCurrency(val) }
                    }
                });
            }
        }

        // Tributos por Tipo EFD
        if (data.tributos_por_tipo) {
            const tributosEfd = data.tributos_por_tipo.filter(d => d.valor > 0);
            if (tributosEfd.length > 0) {
                renderChart('chart-efd-tributos', {
                    chart: { type: 'pie', height: 320 },
                    series: tributosEfd.map(d => d.valor),
                    labels: tributosEfd.map(d => d.tipo),
                    legend: { position: 'bottom' },
                    colors: ['#374151', '#d97706', '#dc2626'],
                    tooltip: {
                        y: { formatter: (val) => formatCurrency(val) }
                    }
                });
            } else {
                const tributosEl = document.getElementById('chart-efd-tributos');
                if (tributosEl) {
                    tributosEl.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem dados tributários</div>';
                }
            }
        }

        // Top Fornecedores EFD
        if (data.top_fornecedores && data.top_fornecedores.length > 0) {
            renderChart('chart-efd-fornecedores', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Valor',
                    data: data.top_fornecedores.map(d => d.total_valor)
                }],
                xaxis: {
                    categories: data.top_fornecedores.map(d => truncate(d.razao_social || d.cnpj_cpf, 20)),
                    labels: { rotate: -45, style: { fontSize: '10px' } }
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                dataLabels: { enabled: false },
                colors: ['#d97706'],
                tooltip: {
                    custom: function({ series, seriesIndex, dataPointIndex }) {
                        const d = data.top_fornecedores[dataPointIndex];
                        const irregularBadge = d.irregular ? ' <span style="color:#ef4444">[IRREGULAR]</span>' : '';
                        return '<div class="p-2 text-xs">'
                            + '<b>' + (d.razao_social || d.cnpj_cpf) + '</b>' + irregularBadge
                            + '<br>' + formatCurrency(d.total_valor) + ' (' + d.total_notas + ' notas)'
                            + '</div>';
                    }
                }
            });
        } else { setEmptyChart('chart-efd-fornecedores'); }

        // Top Clientes EFD
        if (data.top_clientes && data.top_clientes.length > 0) {
            renderChart('chart-efd-clientes', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{
                    name: 'Valor',
                    data: data.top_clientes.map(d => d.total_valor)
                }],
                xaxis: {
                    categories: data.top_clientes.map(d => truncate(d.razao_social || d.cnpj_cpf, 20)),
                    labels: { rotate: -45, style: { fontSize: '10px' } }
                },
                yaxis: {
                    labels: { formatter: (val) => formatCurrency(val) }
                },
                plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                dataLabels: { enabled: false },
                colors: ['#047857'],
                tooltip: {
                    custom: function({ series, seriesIndex, dataPointIndex }) {
                        const d = data.top_clientes[dataPointIndex];
                        const irregularBadge = d.irregular ? ' <span style="color:#ef4444">[IRREGULAR]</span>' : '';
                        return '<div class="p-2 text-xs">'
                            + '<b>' + (d.razao_social || d.cnpj_cpf) + '</b>' + irregularBadge
                            + '<br>' + formatCurrency(d.total_valor) + ' (' + d.total_notas + ' notas)'
                            + '</div>';
                    }
                }
            });
        } else { setEmptyChart('chart-efd-clientes'); }
    }

    // Gráficos e lógica da tab Participantes
    function renderParticipantesCharts(data) {
        participantesData = data;
        tipoAtivo = 'fornecedores';
        participanteAberto = null;

        renderConcentracaoAlertas(data.concentracao || {});
        renderTabelaParticipantes(data.fornecedores || []);
    }

    function setupParticipantes() {
        const btnF = document.getElementById('btn-fornecedores');
        const btnC = document.getElementById('btn-clientes');
        const fechar = document.getElementById('fechar-ficha');
        const tbody = document.getElementById('tabela-participantes');

        if (btnF) btnF.onclick = () => setTipoParticipante('fornecedores');
        if (btnC) btnC.onclick = () => setTipoParticipante('clientes');
        if (fechar) fechar.onclick = fecharFicha;

        if (tbody) {
            tbody.onclick = (e) => {
                const btn = e.target.closest('.btn-ficha');
                if (!btn) return;
                const row = btn.closest('.participante-row');
                if (row) abrirFicha(parseInt(row.dataset.id, 10));
            };
        }

        const fichaTbody = document.getElementById('ficha-ultimas-notas');
        if (fichaTbody) {
            fichaTbody.onclick = (e) => {
                const menuBtn = e.target.closest('.btn-acoes-nota');
                if (menuBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    alternarMenuUltimaNota(menuBtn);
                    return;
                }

                const actionLink = e.target.closest('.nota-acao-link');
                if (!actionLink) return;

                e.preventDefault();
                e.stopPropagation();

                const action = actionLink.dataset.action;
                const participanteId = parseInt(actionLink.dataset.participanteId, 10);
                const sefazUrl = actionLink.dataset.sefazUrl;

                fecharMenuUltimaNota();

                const notaId = parseInt(actionLink.dataset.notaId, 10);

                if (action === 'nota' && notaId) {
                    window.location.href = `/app/notas-fiscais/efd/${notaId}`;
                    return;
                }

                if (action === 'sefaz' && sefazUrl) {
                    window.open(sefazUrl, '_blank', 'noopener,noreferrer');
                }
            };
        }

        if (!document.body.dataset.biNotaMenuBound) {
            document.body.dataset.biNotaMenuBound = '1';
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.nota-acoes-menu')) {
                    fecharMenuUltimaNota();
                }
            });
        }
    }

    function setTipoParticipante(tipo) {
        tipoAtivo = tipo;
        fecharMenuUltimaNota();
        const btnF = document.getElementById('btn-fornecedores');
        const btnC = document.getElementById('btn-clientes');
        if (btnF && btnC) {
            if (tipo === 'fornecedores') {
                btnF.className = 'px-4 py-2 rounded text-sm font-medium bg-gray-800 text-white hover:bg-gray-700';
                btnC.className = 'px-4 py-2 rounded text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50';
            } else {
                btnC.className = 'px-4 py-2 rounded text-sm font-medium bg-gray-800 text-white hover:bg-gray-700';
                btnF.className = 'px-4 py-2 rounded text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50';
            }
        }
        fecharFicha();
        const lista = participantesData ? (participantesData[tipo] || []) : [];
        renderTabelaParticipantes(lista);
    }

    function alternarMenuUltimaNota(button) {
        const menu = button.closest('.nota-acoes-menu');
        if (!menu) return;

        if (menuUltimaNotaAberto && menuUltimaNotaAberto !== menu) {
            menuUltimaNotaAberto.classList.remove('is-open');
            const previousPanel = menuUltimaNotaAberto.querySelector('.nota-acoes-panel');
            if (previousPanel) previousPanel.classList.add('hidden');
        }

        const panel = menu.querySelector('.nota-acoes-panel');
        if (!panel) return;

        const isOpen = menu.classList.contains('is-open');
        if (isOpen) {
            menu.classList.remove('is-open');
            panel.classList.add('hidden');
            menuUltimaNotaAberto = null;
            return;
        }

        menu.classList.add('is-open');
        panel.classList.remove('hidden');
        menuUltimaNotaAberto = menu;
    }

    function fecharMenuUltimaNota() {
        if (!menuUltimaNotaAberto) return;

        menuUltimaNotaAberto.classList.remove('is-open');
        const panel = menuUltimaNotaAberto.querySelector('.nota-acoes-panel');
        if (panel) panel.classList.add('hidden');
        menuUltimaNotaAberto = null;
    }

    function renderConcentracaoAlertas(concentracao) {
        const container = document.getElementById('concentracao-alertas');
        if (!container) return;

        const tipos = [
            { key: 'fornecedores', label: 'Fornecedores' },
            { key: 'clientes', label: 'Clientes' },
        ];

        container.innerHTML = tipos.map(({ key, label }) => {
            const c = concentracao[key] || { top5_percentual: 0, top5_valor: 0, total_valor: 0 };
            const pct = c.top5_percentual || 0;
            let corClasse = 'bg-white border border-gray-300 border-l-4 border-l-green-500 text-gray-700';
            let corBarra = 'bg-green-500';
            if (pct >= 80) {
                corClasse = 'bg-white border border-gray-300 border-l-4 border-l-red-500 text-gray-700';
                corBarra = 'bg-red-500';
            } else if (pct >= 50) {
                corClasse = 'bg-white border border-gray-300 border-l-4 border-l-amber-500 text-gray-700';
                corBarra = 'bg-amber-500';
            }

            return `<div class="rounded p-4 ${corClasse}">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Concentração ${label}</p>
                <p class="text-lg font-bold text-gray-900 mb-1">${pct}%</p>
                <p class="text-xs text-gray-500 mb-2">Top 5 respondem por ${pct}% do total (${formatCurrency(c.top5_valor)} de ${formatCurrency(c.total_valor)})</p>
                <div class="h-2 rounded-full bg-gray-200">
                    <div class="h-2 rounded-full ${corBarra}" style="width:${Math.min(pct, 100)}%"></div>
                </div>
            </div>`;
        }).join('');
    }

    function renderTabelaParticipantes(lista) {
        const tbody = document.getElementById('tabela-participantes');
        const empty = document.getElementById('participantes-empty');
        if (!tbody) return;

        if (!lista || lista.length === 0) {
            tbody.innerHTML = '';
            if (empty) empty.classList.remove('hidden');
            return;
        }
        if (empty) empty.classList.add('hidden');

        const maxValor = lista.length > 0 ? lista[0].total_valor : 1;

        tbody.innerHTML = lista.map((p, i) => {
            const pctBarra = maxValor > 0 ? Math.round((p.total_valor / maxValor) * 100) : 0;
            const irregularBadge = p.irregular
                ? '<span class="ml-1 px-1.5 py-0.5 rounded text-xs text-white" style="background-color: #dc2626">Irregular</span>'
                : '';
            const regimeBadge = p.regime
                ? `<span class="ml-1 px-1.5 py-0.5 rounded text-xs text-white" style="background-color: #374151">${p.regime}</span>`
                : '';

            return `<tr class="hover:bg-gray-50 transition-colors participante-row" data-id="${p.participante_id}">
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-gray-400 font-mono text-xs">${i + 1}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3">
                    <div class="font-medium text-gray-900">${p.razao_social || p.cnpj_cpf || '—'}${irregularBadge}${regimeBadge}</div>
                    <div class="text-xs text-gray-400 mt-0.5">${p.cnpj_cpf || ''}</div>
                    <div class="mt-1 h-1.5 rounded-full bg-gray-100 w-full max-w-xs">
                        <div class="h-1.5 rounded-full bg-gray-400" style="width:${pctBarra}%"></div>
                    </div>
                </td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-gray-900">${formatCurrency(p.total_valor)}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${p.total_notas}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${formatCurrency(p.ticket_medio)}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${p.percentual}%</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-center sticky right-0 bg-white">
                    <button class="btn-ficha px-2 py-1 rounded text-xs text-white" style="background-color: #374151">
                        Ver ficha &#9658;
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    async function abrirFicha(participanteId) {
        const painel = document.getElementById('ficha-participante');
        const loading = document.getElementById('ficha-loading');
        const content = document.getElementById('ficha-content');
        if (!painel) return;

        // Toggle: clicar 2x no mesmo fecha
        if (participanteAberto === participanteId) {
            fecharFicha();
            return;
        }
        participanteAberto = participanteId;

        // Destaca linha
        document.querySelectorAll('.participante-row').forEach(r => r.classList.remove('bg-gray-50'));
        const linha = document.querySelector(`.participante-row[data-id="${participanteId}"]`);
        if (linha) linha.classList.add('bg-gray-50');

        painel.classList.remove('hidden');
        if (loading) loading.classList.remove('hidden');
        if (content) content.classList.add('hidden');

        painel.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const params = getFilterParams();
        try {
            const resp = await fetch(`/app/bi/participantes/${participanteId}/ficha?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Erro ao carregar ficha');
            const data = await resp.json();
            if (loading) loading.classList.add('hidden');
            renderFichaParticipante(data);
            if (content) content.classList.remove('hidden');
        } catch (e) {
            if (loading) loading.classList.add('hidden');
            if (painel) painel.innerHTML += '<p class="text-red-500 text-sm mt-2">Erro ao carregar ficha.</p>';
        }
    }

    function fecharFicha() {
        participanteAberto = null;
        fecharMenuUltimaNota();
        const painel = document.getElementById('ficha-participante');
        if (painel) painel.classList.add('hidden');
        document.querySelectorAll('.participante-row').forEach(r => r.classList.remove('bg-gray-50'));
    }

    function renderFichaParticipante(data) {
        const p = data.participante || {};
        const r = data.resumo || {};

        setKpi('ficha-nome', p.razao_social || p.cnpj_cpf || '—');
        setKpi('ficha-cnpj', p.cnpj_cpf || '—');
        setKpi('ficha-total-notas', r.total_notas || 0);
        setKpi('ficha-entradas', formatCompactCurrency(r.total_entradas || 0));
        setKpi('ficha-saidas', formatCompactCurrency(r.total_saidas || 0));
        setKpi('ficha-tributos', formatCompactCurrency(r.carga_tributaria || 0));
        setKpi('ficha-ticket', formatCompactCurrency(r.ticket_medio || 0));
        setKpi('ficha-ultima-consulta', p.ultima_consulta || 'Nunca');

        // Gráfico de evolução
        const evolucao = data.evolucao_mensal || [];
        if (evolucao.length > 0) {
            renderChart('chart-ficha-evolucao', {
                chart: { type: 'bar', height: 256, stacked: false, toolbar: { show: false } },
                series: [
                    { name: 'Entradas', data: evolucao.map(d => d.entradas) },
                    { name: 'Saídas',   data: evolucao.map(d => d.saidas) },
                ],
                xaxis: { categories: evolucao.map(d => d.label) },
                yaxis: { labels: { formatter: (val) => formatCurrency(val) } },
                plotOptions: { bar: { horizontal: false, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                colors: ['#047857', '#d97706'],
                tooltip: { y: { formatter: (val) => formatCurrency(val) } },
            });
        }

        // Tabela últimas notas
        const tbody = document.getElementById('ficha-ultimas-notas');
        if (tbody) {
            fecharMenuUltimaNota();
            const notas = data.ultimas_notas || [];
            if (notas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-gray-400">Nenhuma nota</td></tr>';
            } else {
                tbody.innerHTML = notas.map(n => {
                    const corTipoStyle = n.tipo_nota === 'E' ? 'background-color: #047857' : 'background-color: #d97706';
                    const corTipo = 'text-white';
                    const numSerie = (n.numero || '—') + (n.serie ? ' / ' + n.serie : '');
                    const sefazUrl = n.chave_acesso
                        ? `https://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx?tipoConsulta=completa&tipoConteudo=XbSeqxE8pl8=&nfe=${n.chave_acesso}`
                        : null;
                    const sefazItem = sefazUrl
                        ? `<a href="${sefazUrl}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="nota-acao-link block px-3 py-2 text-xs text-gray-900 hover:bg-gray-50 hover:text-gray-600 hover:underline text-left"
                               data-action="sefaz"
                               data-participante-id="${n.participante_id}"
                               data-sefaz-url="${sefazUrl}">
                                SEFAZ
                           </a>`
                        : `<span class="block px-3 py-2 text-xs text-gray-300 text-left cursor-not-allowed">SEFAZ</span>`;
                    const acaoBtn = `<div class="nota-acoes-menu relative inline-block text-left">
                        <button type="button"
                            class="btn-acoes-nota inline-flex items-center justify-center w-8 h-8 text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 hover:text-gray-900"
                            title="Ações"
                            aria-label="Abrir ações da nota">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="5" cy="12" r="1.75"></circle>
                                <circle cx="12" cy="12" r="1.75"></circle>
                                <circle cx="19" cy="12" r="1.75"></circle>
                            </svg>
                        </button>
                        <div class="nota-acoes-panel hidden absolute right-0 z-20 mt-1 min-w-[160px] bg-white border border-gray-300 rounded shadow-sm overflow-hidden">
                            <a href="/app/participante/${n.participante_id}#notas-fiscais"
                               class="nota-acao-link block px-3 py-2 text-xs text-gray-900 hover:bg-gray-50 hover:text-gray-600 hover:underline text-left"
                               data-action="nota"
                               data-participante-id="${n.participante_id}"
                               data-nota-id="${n.id}">
                                Ver Nota
                            </a>
                            ${sefazItem}
                        </div>
                    </div>`;
                    return `<tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-900 font-medium whitespace-nowrap">${numSerie}</td>
                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">${n.data_emissao}</td>
                        <td class="px-3 py-2"><span class="px-1.5 py-0.5 rounded text-xs font-medium ${corTipo}" style="${corTipoStyle}">${n.tipo_nota}</span></td>
                        <td class="px-3 py-2 text-gray-500 whitespace-nowrap">${n.modelo || '—'}</td>
                        <td class="px-3 py-2 text-gray-500">${n.cfop || '—'}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-800 whitespace-nowrap">${formatCurrency(n.vl_doc)}</td>
                        <td class="px-3 py-2 text-center">${acaoBtn}</td>
                    </tr>`;
                }).join('');
            }
        }
    }

    // =========================================================================
    // Módulo Riscos
    // =========================================================================

    function renderRiscosCharts(data) {
        renderScoreCarteira(data.score_carteira || {});
        renderGapImportacoes(data.gap_importacoes || []);
        renderTabelaIrregulares(data.fornecedores_irregulares || []);
        renderTabelaMudancas(data.mudancas_regime || []);
        renderTabelaNotasRisco(data.notas_em_risco || []);
    }

    function renderScoreCarteira(score) {
        const pctRegular = score.percentual_regular || 0;
        const irregulares = score.irregulares || 0;

        const corPctEl = document.getElementById('score-percentual-regular');
        if (corPctEl) {
            corPctEl.textContent = pctRegular + '%';
            corPctEl.className = corPctEl.className.replace(/text-(green|amber|red)-\d+/, '');
            corPctEl.classList.add(pctRegular >= 90 ? 'text-green-600' : pctRegular >= 70 ? 'text-amber-600' : 'text-red-600');
        }

        const irregEl = document.getElementById('score-irregulares');
        if (irregEl) {
            irregEl.textContent = irregulares;
            irregEl.className = irregEl.className.replace(/text-(gray|red)-\d+/, '');
            irregEl.classList.add(irregulares === 0 ? 'text-gray-900' : 'text-red-600');
        }

        setKpi('score-total-participantes', score.participantes_ativos || 0);
        setKpi('score-valor-risco', formatCompactCurrency(score.valor_total_em_risco || 0));
    }

    function renderGapImportacoes(gaps) {
        const el = document.getElementById('gap-importacoes');
        if (!el || !gaps.length) return;

        const linhas = gaps.map(g => {
            const fiscal = g.tem_fiscal
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-white" style="background-color: #047857">&#10003; Fiscal</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-white" style="background-color: #9ca3af">&mdash;</span>';
            const contrib = g.tem_contrib
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-white" style="background-color: #047857">&#10003; Contrib.</span>'
                : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-white" style="background-color: #9ca3af">&mdash;</span>';
            const gapBadge = g.gap
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-white" style="background-color: #dc2626">GAP</span>'
                : '';
            return `<td class="px-3 py-2 text-center align-top">
                <p class="text-xs font-medium text-gray-600 mb-1">${g.label}</p>
                <div class="flex flex-col gap-1 items-center">${fiscal}${contrib}${gapBadge}</div>
            </td>`;
        });

        el.innerHTML = `<table class="min-w-[900px] w-full text-xs"><tbody><tr>${linhas.join('')}</tr></tbody></table>`;
        setupScrollFade(el);
    }

    function renderTabelaIrregulares(lista) {
        const el = document.getElementById('tabela-irregulares-container');
        if (!el) return;

        if (!lista.length) {
            el.innerHTML = '<p class="text-sm text-gray-500 py-4">Nenhum participante irregular encontrado no período.</p>';
            return;
        }

        const linhas = lista.map(p => `<tr class="hover:bg-gray-50">
            <td class="px-2 sm:px-4 py-2 sm:py-3">
                <div class="font-medium text-gray-900">${p.razao_social || p.cnpj_cpf || '—'}</div>
                <div class="text-xs text-gray-400">${p.cnpj_cpf || ''}</div>
            </td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-center">
                <span class="px-2 py-0.5 rounded text-xs text-white" style="background-color: #dc2626">${p.situacao || '—'}</span>
            </td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-xs text-gray-500">${p.regime || '—'}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-red-700">${formatCurrency(p.valor_em_risco)}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${p.total_notas}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-400 text-xs">${p.ultima_nota || '—'}</td>
        </tr>`).join('');

        el.innerHTML = `<table class="min-w-[650px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>
                        <th class="px-2 sm:px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor em Risco</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Última Nota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${linhas}</tbody>
            </table>`;
        setupScrollFade(el);
    }

    function renderTabelaMudancas(lista) {
        const el = document.getElementById('tabela-mudancas-container');
        if (!el) return;

        if (!lista.length) {
            el.innerHTML = '<p class="text-sm text-gray-400 py-4">Nenhuma atualização de cadastro nos últimos 90 dias.</p>';
            return;
        }

        const linhas = lista.map(p => {
            const situacaoUpper = (p.situacao_atual || '').toUpperCase();
            const irregular = p.situacao_atual && !['02', 'ATIVA'].includes(situacaoUpper);
            const badge = irregular
                ? `<span class="ml-1 px-1.5 py-0.5 rounded text-xs text-white" style="background-color: #dc2626">Irregular</span>`
                : '';
            return `<tr class="hover:bg-gray-50">
                <td class="px-2 sm:px-4 py-2 sm:py-3">
                    <div class="font-medium text-gray-900">${p.razao_social || p.cnpj_cpf || '—'}${badge}</div>
                    <div class="text-xs text-gray-400">${p.cnpj_cpf || ''}</div>
                </td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-xs text-gray-600">${p.regime_atual || '—'}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-xs text-gray-600">${p.situacao_atual || '—'}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-400 text-xs">${p.ultima_atualizacao || '—'}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${p.total_notas}</td>
            </tr>`;
        }).join('');

        el.innerHTML = `<table class="min-w-[600px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Atualizado em</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${linhas}</tbody>
            </table>`;
        setupScrollFade(el);
    }

    function renderTabelaNotasRisco(lista) {
        const el = document.getElementById('tabela-notas-risco-container');
        if (!el) return;

        if (!lista.length) {
            el.innerHTML = '<p class="text-sm text-gray-500 py-4">Nenhuma nota com participante irregular no período.</p>';
            return;
        }

        const linhas = lista.map(n => {
            const corTipoStyle = n.tipo_nota === 'E' ? 'background-color: #047857' : 'background-color: #d97706';
            return `<tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-gray-400 text-xs">${n.data_emissao || '—'}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3"><span class="px-1.5 py-0.5 rounded text-xs font-medium text-white" style="${corTipoStyle}">${n.tipo_nota}</span></td>
                <td class="px-2 sm:px-4 py-2 sm:py-3">
                    <div class="font-medium text-gray-900 text-sm">${n.razao_social || n.cnpj_cpf || '—'}</div>
                    <div class="text-xs text-gray-400">${n.cnpj_cpf || ''}</div>
                </td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-center">
                    <span class="px-2 py-0.5 rounded text-xs text-white" style="background-color: #dc2626">${n.situacao || '—'}</span>
                </td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-xs text-gray-500">${{notas_servicos:'Serviços',notas_mercadorias:'Mercadorias',notas_transportes:'Transportes'}[n.bloco] || n.bloco}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-gray-900">${formatCurrency(n.vl_doc)}</td>
            </tr>`;
        }).join('');

        el.innerHTML = `<table class="min-w-[650px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Data</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participante</th>
                        <th class="px-2 sm:px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</th>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Bloco</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${linhas}</tbody>
            </table>`;
        setupScrollFade(el);
    }

    // =========================================================================
    // Módulo Tributário EFD
    // =========================================================================

    function renderTributarioEfdCharts(data) {
        renderTabelaTributarioConsolidado(data.consolidado || {});
        renderGraficoTributarioMensal(data.mensal || []);
        renderGraficoAliquotaEfd(data.aliquota || []);
        renderTabelaTribRegime(data.por_regime || []);
    }

    function renderTabelaTributarioConsolidado(consolidado) {
        const el = document.getElementById('tabela-tributario-consolidado');
        if (!el) return;

        const tributos = [
            { label: 'ICMS',   dados: consolidado.icms   || { credito: 0, debito: 0, saldo: 0 } },
            { label: 'PIS',    dados: consolidado.pis    || { credito: 0, debito: 0, saldo: 0 } },
            { label: 'COFINS', dados: consolidado.cofins || { credito: 0, debito: 0, saldo: 0 } },
            { label: 'Total',  dados: consolidado.totais || { credito: 0, debito: 0, saldo: 0 }, bold: true },
        ];

        const linhas = tributos.map(t => {
            const saldo = t.dados.saldo || 0;
            const corSaldo = saldo >= 0 ? 'text-green-700' : 'text-red-700';
            const trClass = t.bold ? 'bg-gray-50 font-semibold' : 'hover:bg-gray-50';
            return `<tr class="${trClass}">
                <td class="px-2 sm:px-4 py-2 sm:py-3 font-medium text-gray-900">${t.label}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-green-700">${formatCurrency(t.dados.credito)}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-red-700">${formatCurrency(t.dados.debito)}</td>
                <td class="px-2 sm:px-4 py-2 sm:py-3 text-right ${corSaldo}">${formatCurrency(saldo)}</td>
            </tr>`;
        }).join('');

        el.innerHTML = `<table class="min-w-[600px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tributo</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-green-600">Crédito (Entradas)</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-red-600">Débito (Saídas)</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${linhas}</tbody>
            </table>`;
        setupScrollFade(el);
    }

    function renderGraficoTributarioMensal(mensal) {
        const el = document.getElementById('chart-trib-mensal');
        if (!el) return;

        if (!mensal.length) {
            el.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem dados</div>';
            return;
        }

        renderChart('chart-trib-mensal', {
            chart: { type: 'bar', height: 288, stacked: true, toolbar: { show: false } },
            series: [
                { name: 'ICMS',   data: mensal.map(d => d.icms) },
                { name: 'PIS',    data: mensal.map(d => d.pis) },
                { name: 'COFINS', data: mensal.map(d => d.cofins) },
            ],
            xaxis: { categories: mensal.map(d => d.label) },
            yaxis: { labels: { formatter: (val) => formatCurrency(val) } },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            colors: ['#374151', '#d97706', '#dc2626'],
            tooltip: { y: { formatter: (val) => formatCurrency(val) } },
        });
    }

    function renderGraficoAliquotaEfd(aliquota) {
        const el = document.getElementById('chart-trib-aliquota');
        if (!el) return;

        if (!aliquota.length) {
            el.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Sem dados</div>';
            return;
        }

        renderChart('chart-trib-aliquota', {
            chart: { type: 'area', height: 288, toolbar: { show: false } },
            series: [{ name: 'Alíquota Efetiva', data: aliquota.map(d => d.aliquota_efetiva) }],
            xaxis: { categories: aliquota.map(d => d.label) },
            yaxis: { labels: { formatter: (val) => val.toFixed(2) + '%' }, min: 0 },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
            colors: ['#374151'],
            tooltip: { y: { formatter: (val) => val.toFixed(2) + '%' } },
        });
    }

    function renderTabelaTribRegime(lista) {
        const el = document.getElementById('tabela-trib-regime');
        if (!el) return;

        if (!lista.length) {
            el.innerHTML = '<p class="text-sm text-gray-400 py-4">Sem dados de notas EFD no período.</p>';
            return;
        }

        const linhas = lista.map(r => `<tr class="hover:bg-gray-50">
            <td class="px-2 sm:px-4 py-2 sm:py-3 font-medium text-gray-900">${r.regime}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-600">${r.total_notas}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-gray-900">${formatCurrency(r.vl_total)}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-blue-700">${formatCurrency(r.icms_total)}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-amber-700">${formatCurrency(r.pis_total)}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-red-700">${formatCurrency(r.cofins_total)}</td>
            <td class="px-2 sm:px-4 py-2 sm:py-3 text-right text-gray-500">${r.aliquota_media.toFixed(2)}%</td>
        </tr>`).join('');

        el.innerHTML = `<table class="min-w-[650px] w-full divide-y divide-gray-200 text-xs sm:text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 sm:px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-gray-900">ICMS</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-amber-600">PIS</th>
                        <th class="px-2 sm:px-4 py-2 sm:py-3 text-right font-semibold text-red-600">COFINS</th>
                        <th class="px-2 sm:px-4 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Alíquota Média</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${linhas}</tbody>
            </table>`;
        setupScrollFade(el);
    }

    // Atualiza elemento KPI pelo ID
    function setKpi(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    // Renderiza um gráfico (cria ou atualiza)
    function renderChart(elementId, options, _retries) {
        const element = document.getElementById(elementId);
        if (!element) return;

        // Guard: ApexCharts CDN ainda não carregou, tentar novamente em 300ms (max 10x)
        if (typeof ApexCharts === 'undefined') {
            _retries = (_retries || 0) + 1;
            if (_retries <= 10) {
                setTimeout(() => renderChart(elementId, options, _retries), 300);
            } else {
                setEmptyChart(elementId, 'Erro ao carregar gráfico');
            }
            return;
        }

        // Destroi gráfico existente
        if (charts[elementId]) {
            try { charts[elementId].destroy(); } catch (e) { /* ignore */ }
            delete charts[elementId];
        }

        // Injeta responsive options para mobile/tablet
        options = mobileChartOptions(options);

        // Cria novo gráfico
        charts[elementId] = new ApexCharts(element, options);
        charts[elementId].render();
    }

    // Utilitários
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    function formatCompactCurrency(value) {
        const abs = Math.abs(value);
        const sign = value < 0 ? '-' : '';
        if (abs >= 1e9) {
            return sign + 'R$ ' + (abs / 1e9).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' bi';
        }
        if (abs >= 1e6) {
            return sign + 'R$ ' + (abs / 1e6).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' mi';
        }
        if (abs >= 1e4) {
            return sign + 'R$ ' + (abs / 1e3).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' mil';
        }
        return formatCurrency(value);
    }

    function truncate(str, length) {
        if (!str) return '';
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    function setEmptyChart(elementId, message) {
        message = message || 'Sem dados para o período selecionado';
        const el = document.getElementById(elementId);
        if (el) {
            el.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">' + message + '</div>';
        }
    }

    function showTabError(tabName) {
        const tabEl = document.getElementById('tab-' + tabName);
        if (tabEl) {
            tabEl.innerHTML = `<div class="bg-white rounded border border-gray-300 p-6 sm:p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="mt-3 text-sm text-gray-500">Erro ao carregar dados desta seção.</p>
                <button onclick="document.querySelector('[data-tab=&quot;${tabName}&quot;]')?.click()" class="mt-3 px-4 py-1.5 rounded bg-gray-800 text-white text-xs font-medium hover:bg-gray-700 transition">Tentar novamente</button>
            </div>`;
        }
    }

    function showEmptyState() {
        const emptyState = document.getElementById('bi-empty');
        if (emptyState) emptyState.classList.remove('hidden');
        document.querySelectorAll('.bi-tab-content').forEach(el => el.classList.add('hidden'));
    }

    function hideEmptyState(tabName) {
        const emptyState = document.getElementById('bi-empty');
        if (emptyState) emptyState.classList.add('hidden');
        // Restaurar conteúdo da tab ativa (pode ter sido ocultado por showEmptyState)
        const activeContent = document.getElementById('tab-' + tabName);
        if (activeContent) activeContent.classList.remove('hidden');
    }

    // Limpa todas as instâncias de gráficos e reseta estado do módulo
    function cleanup() {
        Object.keys(charts).forEach(id => {
            try {
                if (charts[id] && typeof charts[id].destroy === 'function') {
                    charts[id].destroy();
                }
            } catch (e) {
                // Ignorar erros ao destruir gráfico com elemento removido
            }
        });
        charts = {};
        currentTab = 'faturamento';
        tipoAtivo = 'fornecedores';
        participantesData = null;
        participanteAberto = null;
        menuUltimaNotaAberto = null;
        _initRetries = 0;
    }

    // Expõe função de inicialização para SPA (chamada pelo spa.js via tentarExecutarFuncao)
    window.initBi = init;
    window.cleanupBi = cleanup;
})();
