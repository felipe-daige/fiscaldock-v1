// public/js/dashboard.js
// Cockpit do dashboard. Exposto como window.initDashboard() — o spa.js chama essa
// função a cada navegação para /app/dashboard (e na carga inicial), depois de garantir
// que apexcharts.min.js carregou. NÃO usar IIFE auto-executável: arquivos externos são
// deduplicados pelo spa e não re-rodam nas voltas via SPA.
(function () {
    function initDashboardCockpit() {
        const root = document.getElementById('dashboard-cockpit');
        if (!root) return;

        // Idempotência: se um init anterior não foi limpo, limpa antes de re-vincular.
        if (window._cleanupFunctions && window._cleanupFunctions.dashboardCockpit) {
            try { window._cleanupFunctions.dashboardCockpit(); } catch (_) {}
        }

        const $ = (sel) => root.querySelector(sel);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const fmtN = (v) => Number(v || 0).toLocaleString('pt-BR');
        const fmtR = (v) => 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtCompact = (v) => {
            v = Number(v || 0);
            if (Math.abs(v) >= 1e6) return 'R$ ' + (v / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + 'M';
            if (Math.abs(v) >= 1e3) return 'R$ ' + (v / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 0 }) + 'k';
            return fmtR(v);
        };

        const charts = { tendencia: null, risco: null, fornecedores: null };
        let metrica = 'valor';
        let estado = null;
        try { estado = JSON.parse(document.getElementById('cockpit-initial')?.textContent || 'null'); } catch (_) {}

        const apex = () => typeof ApexCharts !== 'undefined';

        function renderTendencia(t) {
            const el = $('#chartTendencia');
            if (!el || !apex() || !t) return;
            const saida = metrica === 'qtd' ? t.saida_qtd : t.saida_valor;
            const entrada = metrica === 'qtd' ? t.entrada_qtd : t.entrada_valor;
            const opts = {
                chart: { type: 'area', height: 260, toolbar: { show: false } },
                series: [{ name: 'Saída', data: saida || [] }, { name: 'Entrada', data: entrada || [] }],
                xaxis: { categories: t.meses || [] },
                colors: ['#1d4ed8', '#d97706'],
                dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 2 },
                legend: { position: 'top', fontSize: '11px' },
                yaxis: { labels: { formatter: (v) => metrica === 'qtd' ? fmtN(v) : fmtCompact(v) } },
            };
            if (charts.tendencia) { charts.tendencia.updateOptions(opts); } else { charts.tendencia = new ApexCharts(el, opts); charts.tendencia.render(); }
        }

        function renderRisco(dist) {
            const el = $('#chartRisco'); const vazio = $('#risco-vazio');
            if (!el || !apex()) return;
            const has = dist && dist.length;
            if (vazio) vazio.classList.toggle('hidden', !!has);
            el.classList.toggle('hidden', !has);
            if (!has) { if (charts.risco) { charts.risco.destroy(); charts.risco = null; } return; }
            const opts = {
                chart: { type: 'donut', height: 240 },
                series: dist.map((d) => d.valor),
                labels: dist.map((d) => d.label),
                colors: dist.map((d) => d.hex),
                legend: { position: 'bottom', fontSize: '11px' },
                dataLabels: { enabled: true },
            };
            if (charts.risco) { charts.risco.updateOptions(opts); } else { charts.risco = new ApexCharts(el, opts); charts.risco.render(); }
        }

        function renderFornecedores(rows) {
            const el = $('#chartFornecedores'); const vazio = $('#fornecedores-vazio');
            if (!el || !apex()) return;
            const has = rows && rows.length;
            if (vazio) vazio.classList.toggle('hidden', !!has);
            el.classList.toggle('hidden', !has);
            if (!has) { if (charts.fornecedores) { charts.fornecedores.destroy(); charts.fornecedores = null; } return; }
            const nomes = rows.map((r) => (r.razao_social || r.cnpj || '—').slice(0, 24));
            const opts = {
                chart: { type: 'bar', height: 240, toolbar: { show: false } },
                series: [{ name: 'Volume', data: rows.map((r) => Number(r.total || 0)) }],
                xaxis: { categories: nomes, labels: { formatter: (v) => fmtCompact(v) } },
                plotOptions: { bar: { horizontal: true, borderRadius: 3, distributed: true } },
                colors: ['#1d4ed8', '#7c3aed', '#0891b2', '#047857', '#d97706'],
                dataLabels: { enabled: false }, legend: { show: false },
                tooltip: { y: { formatter: (v) => fmtR(v) } },
            };
            if (charts.fornecedores) { charts.fornecedores.updateOptions(opts); } else { charts.fornecedores = new ApexCharts(el, opts); charts.fornecedores.render(); }
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
            renderTendencia(dados.tendencia);
            renderRisco(dados.risco_distribuicao);
            renderFornecedores(dados.top_fornecedores);
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

        const onChange = (e) => {
            if (e.target.matches('[data-control="cliente"], [data-control="periodo"]')) pivotar();
            if (e.target.matches('[data-control="metrica"]')) { metrica = e.target.value; renderTendencia(estado && estado.tendencia); }
            if (e.target.matches('[data-pref-card]')) aplicarVisibilidade();
            if (e.target.matches('[data-pref-card], [data-pref-atalho]')) {
                clearTimeout(prefsTimer);
                prefsTimer = setTimeout(salvarPrefs, 600);
            }
        };
        const onClick = (e) => {
            if (e.target.closest('[data-personalizar-toggle]')) { root.querySelector('[data-personalizar-panel]')?.classList.toggle('hidden'); }
        };
        root.addEventListener('change', onChange);
        root.addEventListener('click', onClick);

        // Primeira pintura usa o estado embutido (sem refetch).
        render(estado);

        window._cleanupFunctions = window._cleanupFunctions || {};
        window._cleanupFunctions.dashboardCockpit = function () {
            root.removeEventListener('change', onChange);
            root.removeEventListener('click', onClick);
            ['tendencia', 'risco', 'fornecedores'].forEach((k) => { if (charts[k]) { try { charts[k].destroy(); } catch (_) {} charts[k] = null; } });
            clearTimeout(prefsTimer);
        };
    }

    window.initDashboard = initDashboardCockpit;
})();
