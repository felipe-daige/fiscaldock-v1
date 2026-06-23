(function () {
    const data = window.movimentacaoData;
    const el = document.getElementById('chart-mov-competencia');
    if (!data || !el || typeof ApexCharts === 'undefined') {
        return;
    }

    const comp = data.por_competencia || [];
    const categorias = comp.map((c) => c.competencia);
    const entrada = comp.map((c) => Number(c.entrada));
    const saida = comp.map((c) => Number(c.saida));

    const chart = new ApexCharts(el, {
        chart: { type: 'bar', height: 220, toolbar: { show: false } },
        series: [
            { name: 'Entrada', data: entrada },
            { name: 'Saída', data: saida },
        ],
        xaxis: { categories: categorias },
        colors: ['#047857', '#dc2626'],
        plotOptions: { bar: { columnWidth: '55%' } },
        dataLabels: { enabled: false },
        legend: { position: 'top' },
    });
    chart.render();

    window._cleanupFunctions = window._cleanupFunctions || [];
    window._cleanupFunctions.push(() => chart.destroy());
})();
