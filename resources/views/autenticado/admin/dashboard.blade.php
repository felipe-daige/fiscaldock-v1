@php
    $fmtR = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $fmtN = fn ($v) => number_format((float) $v, 0, ',', '.');
    $periodos = ['30' => '30 dias', '90' => '90 dias', '365' => '12 meses', 'tudo' => 'Tudo'];
@endphp
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Admin — Visão Geral</h1>
            <p class="text-xs text-gray-500 mt-0.5">Analytics do negócio. Somente o operador FiscalDock vê esta área.</p>
        </div>

        @include('autenticado.admin.partials.nav', ['tab' => 'visao'])

        {{-- Filtro de período --}}
        <form method="GET" class="mb-4 flex items-center gap-2 text-[13px]">
            <label class="text-[11px] text-gray-500">Período</label>
            <select name="periodo" onchange="this.form.submit()" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded">
                @foreach($periodos as $k => $label)
                    <option value="{{ $k }}" @selected($m['periodo'] === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        {{-- Headline (4 números-chave; o resto vira gráfico ou stat inline) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            @foreach([
                ['Usuários', $fmtN($m['crescimento']['total_usuarios']), $fmtN($m['crescimento']['novos']).' novos · '.$fmtN($m['crescimento']['ativos']).' ativos (30d)', '#1d4ed8'],
                ['Receita total', $fmtR($m['receita']['aprovada_total']), $fmtR($m['receita']['aprovada_periodo']).' no período', '#047857'],
                ['MRR estimado', $fmtR($m['receita']['mrr']), $fmtN($m['receita']['assinaturas_ativas']).' assinatura(s) · '.$fmtN($m['receita']['recargas_ativas']).' recarga(s)', '#047857'],
                ['Saldo de créditos', $fmtN($m['creditos']['saldo_base']), $fmtN($m['creditos']['vendidos']).' vendidos · '.$fmtN($m['creditos']['consumidos']).' consumidos', '#334155'],
            ] as [$label, $valor, $sub, $cor])
                <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: {{ $cor }}">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                    <p class="text-xl font-bold text-gray-900">{{ $valor }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        {{-- Tendências --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded border border-gray-300 p-4">
                <div class="flex items-baseline justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Novos usuários</p>
                    <p class="text-[11px] text-gray-500"><span class="font-bold" style="color:#047857">{{ $fmtN($m['trial']['convertidos']) }}</span> trial convertido</p>
                </div>
                <div id="chartSignups"></div>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4">
                <div class="flex items-baseline justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Receita aprovada</p>
                    <p class="text-[11px] text-gray-500"><span class="font-bold text-gray-900">{{ $fmtR($m['receita']['aprovada_periodo']) }}</span> no período</p>
                </div>
                <div id="chartReceita"></div>
            </div>
        </div>

        {{-- Uso do produto + Consumo de créditos --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded border border-gray-300 p-4">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Uso do produto (período)</p>
                <div id="chartUso"></div>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Consumo de créditos por tipo</p>
                @if(empty($m['creditos']['consumo_por_tipo']))
                    <p class="text-center text-gray-400 text-sm py-10">Sem consumo no período.</p>
                @else
                    <div id="chartCreditos"></div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="/js/apexcharts.min.js"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    const signups = @json($m['crescimento']['serie_signups']);
    const receita = @json($m['receita']['serie_receita']);
    const uso = @json($m['uso']);
    const consumo = @json($m['creditos']['consumo_por_tipo']);

    const area = (sel, dados, nome, cor) => {
        const el = document.querySelector(sel);
        if (!el) return;
        new ApexCharts(el, {
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            series: [{ name: nome, data: dados.map(d => d.total) }],
            xaxis: { categories: dados.map(d => d.data) },
            colors: [cor], dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 2 },
        }).render();
    };
    area('#chartSignups', signups, 'Novos', '#1d4ed8');
    area('#chartReceita', receita, 'Receita', '#047857');

    const usoEl = document.querySelector('#chartUso');
    if (usoEl) {
        new ApexCharts(usoEl, {
            chart: { type: 'bar', height: 240, toolbar: { show: false } },
            series: [{ name: 'Volume', data: [uso.consultas, uso.importacoes, uso.clearance, uso.monitoramentos_ativos] }],
            xaxis: { categories: ['Consultas', 'Importações', 'Clearance', 'Monit. ativos'] },
            plotOptions: { bar: { horizontal: true, borderRadius: 3, distributed: true } },
            colors: ['#1d4ed8', '#7c3aed', '#0891b2', '#047857'],
            dataLabels: { enabled: true }, legend: { show: false },
        }).render();
    }

    const credEl = document.querySelector('#chartCreditos');
    if (credEl && consumo.length) {
        new ApexCharts(credEl, {
            chart: { type: 'donut', height: 240 },
            series: consumo.map(c => Math.abs(c.total)),
            labels: consumo.map(c => c.type),
            colors: ['#1d4ed8', '#b45309', '#047857', '#7c3aed', '#0891b2', '#dc2626', '#334155'],
            dataLabels: { enabled: false }, legend: { position: 'bottom', fontSize: '11px' },
        }).render();
    }
})();
</script>
