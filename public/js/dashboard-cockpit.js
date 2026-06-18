// public/js/dashboard-cockpit.js
// Cockpit do dashboard: pivot client-side (cliente/período) + personalização persistida.
(function () {
    const root = document.getElementById('dashboard-cockpit');
    if (!root) return;

    const $ = (sel) => root.querySelector(sel);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fmtN = (v) => Number(v || 0).toLocaleString('pt-BR');
    const fmtR = (v) => 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let chart = null;
    let metrica = 'valor';
    let estado = null;
    try { estado = JSON.parse(document.getElementById('cockpit-initial')?.textContent || 'null'); } catch (_) {}

    function renderChart(tendencia) {
        const el = $('#chartTendencia');
        if (!el || typeof ApexCharts === 'undefined' || !tendencia) return;
        const serie = metrica === 'qtd' ? tendencia.qtd : tendencia.valor;
        const nome = metrica === 'qtd' ? 'Notas' : 'Faturamento';
        const opts = {
            chart: { type: 'area', height: 240, toolbar: { show: false } },
            series: [{ name: nome, data: serie || [] }],
            xaxis: { categories: tendencia.meses || [] },
            colors: ['#1d4ed8'], dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 2 },
            yaxis: { labels: { formatter: (v) => metrica === 'qtd' ? fmtN(v) : fmtR(v) } },
        };
        if (chart) { chart.updateOptions(opts); } else { chart = new ApexCharts(el, opts); chart.render(); }
    }

    function renderKpis(kpis) {
        if (!kpis) return;
        const set = (kpi, valor, sub) => {
            const card = root.querySelector(`[data-kpi="${kpi}"]`);
            if (!card) return;
            const elValor = card.querySelector('[data-kpi-valor]');
            const elSub = card.querySelector('[data-kpi-sub]');
            if (elValor) elValor.textContent = valor;
            if (elSub && sub !== null) elSub.textContent = sub;
        };
        set('volume', fmtN(kpis.volume.notas), fmtR(kpis.volume.valor));
        set('saude', fmtN(kpis.saude.total), kpis.saude.total > 0 ? 'pontos de atenção' : 'tudo em dia');
        set('creditos', fmtN(kpis.creditos.saldo), fmtN(kpis.creditos.usados_mes) + ' usados este mês');
    }

    function renderTriagem(triagem) {
        const lista = $('#triagem-lista');
        if (!lista) return;
        if (!triagem || !triagem.length) {
            lista.innerHTML = '<p class="py-6 text-center text-sm text-gray-500">Nenhuma pendência — sua carteira está em dia.</p>';
            return;
        }
        lista.innerHTML = triagem.map((i) => `
            <a href="${i.url}" data-link class="flex items-center justify-between py-2.5 hover:bg-gray-50/60 -mx-1 px-1 rounded">
                <span class="flex items-center gap-2 text-sm text-gray-700">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: ${i.hex}"></span>${i.label}
                </span>
                <span class="text-sm font-bold ${i.count > 0 ? 'text-gray-900' : 'text-gray-300'}">${fmtN(i.count)}</span>
            </a>`).join('');
    }

    function render(dados) {
        if (!dados) return;
        estado = dados;
        renderKpis(dados.kpis);
        renderTriagem(dados.triagem);
        renderChart(dados.tendencia);
    }

    async function pivotar() {
        const cliente = $('[data-control="cliente"]')?.value || '';
        const periodo = $('[data-control="periodo"]')?.value || '6';
        try {
            const resp = await fetch(`/app/dashboard/dados?cliente=${encodeURIComponent(cliente)}&periodo=${encodeURIComponent(periodo)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!resp.ok) return;
            render(await resp.json());
        } catch (_) { /* mantém estado atual */ }
    }

    let prefsTimer = null;
    function coletarPrefs() {
        const cards = {};
        root.querySelectorAll('[data-pref-card]').forEach((cb) => { cards[cb.dataset.prefCard] = { visivel: cb.checked }; });
        const atalhos_fixos = Array.from(root.querySelectorAll('[data-pref-atalho]')).filter((cb) => cb.checked).map((cb) => cb.dataset.prefAtalho);
        return { cards, atalhos_fixos };
    }
    function aplicarVisibilidade() {
        root.querySelectorAll('[data-pref-card]').forEach((cb) => {
            const card = root.querySelector(`[data-card="${cb.dataset.prefCard}"]`);
            if (card) card.classList.toggle('hidden', !cb.checked);
        });
    }
    async function salvarPrefs() {
        const payload = coletarPrefs();
        try {
            await fetch('/app/dashboard/prefs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });
        } catch (_) { /* otimista: front mantém estado */ }
    }

    // --- listeners (registrados pra cleanup no SPA) ---
    const onControl = (e) => {
        if (e.target.matches('[data-control="cliente"], [data-control="periodo"]')) pivotar();
        if (e.target.matches('[data-control="metrica"]')) { metrica = e.target.value; renderChart(estado && estado.tendencia); }
    };
    const onClick = (e) => {
        if (e.target.closest('[data-personalizar-toggle]')) { root.querySelector('[data-personalizar-panel]')?.classList.toggle('hidden'); }
    };
    const onPref = (e) => {
        if (e.target.matches('[data-pref-card]')) { aplicarVisibilidade(); }
        if (e.target.matches('[data-pref-card], [data-pref-atalho]')) {
            clearTimeout(prefsTimer);
            prefsTimer = setTimeout(salvarPrefs, 600);
        }
    };
    root.addEventListener('change', onControl);
    root.addEventListener('change', onPref);
    root.addEventListener('click', onClick);

    // primeira pintura usa o estado embutido (sem refetch)
    render(estado);

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.dashboardCockpit = function () {
        root.removeEventListener('change', onControl);
        root.removeEventListener('change', onPref);
        root.removeEventListener('click', onClick);
        if (chart) { try { chart.destroy(); } catch (_) {} chart = null; }
        clearTimeout(prefsTimer);
    };
})();
