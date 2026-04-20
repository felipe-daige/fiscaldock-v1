// public/js/bi/graficos-home.js
(function () {
    'use strict';

    var chartFluxo = null;
    var chartBlocos = null;

    function formatBRL(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2
        }).format(value);
    }

    function initFluxoMensal() {
        var canvas = document.getElementById('grafico-fluxo-mensal');
        if (!canvas) return;

        var dados = window.biFluxoMensal || [];
        if (!dados.length) return;

        var labels    = dados.map(function (d) { return d.label; });
        var entradas  = dados.map(function (d) { return d.entradas; });
        var saidas    = dados.map(function (d) { return d.saidas; });
        var saldo     = dados.map(function (d) { return d.saldo; });

        if (chartFluxo) { chartFluxo.destroy(); }

        chartFluxo = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entradas',
                        data: entradas,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Saídas',
                        data: saidas,
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244,63,94,0.08)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Saldo',
                        data: saldo,
                        borderColor: '#3b82f6',
                        borderDash: [6, 3],
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        fill: false,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + formatBRL(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function (value) { return formatBRL(value); }
                        }
                    }
                }
            }
        });
    }

    function initVolumeBlocos() {
        var canvas = document.getElementById('grafico-volume-blocos');
        var semDados = document.getElementById('grafico-blocos-sem-dados');
        if (!canvas) return;

        var dados = window.biVolumeBlocos || {};
        var valorA = dados.A ? dados.A.valor : 0;
        var valorC = dados.C ? dados.C.valor : 0;
        var valorD = dados.D ? dados.D.valor : 0;
        var total  = valorA + valorC + valorD;

        if (total === 0) {
            canvas.style.display = 'none';
            if (semDados) semDados.style.display = 'flex';
            return;
        }
        if (semDados) semDados.style.display = 'none';
        canvas.style.display = 'block';

        if (chartBlocos) { chartBlocos.destroy(); }

        chartBlocos = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Bloco A — PIS/COFINS', 'Bloco C — ICMS/IPI', 'Bloco D — Transporte'],
                datasets: [{
                    data: [valorA, valorC, valorD],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + formatBRL(ctx.parsed) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function init() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js não carregado.');
            return;
        }
        initFluxoMensal();
        initVolumeBlocos();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
