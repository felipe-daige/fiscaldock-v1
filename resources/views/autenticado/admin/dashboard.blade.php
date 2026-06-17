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

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            @foreach([
                ['Usuários', $fmtN($m['crescimento']['total_usuarios']), '#1d4ed8'],
                ['Novos no período', $fmtN($m['crescimento']['novos']), '#1d4ed8'],
                ['Ativos (30d)', $fmtN($m['crescimento']['ativos']), '#047857'],
                ['Trial convertido', $fmtN($m['trial']['convertidos']), '#047857'],
                ['Receita aprovada (período)', $fmtR($m['receita']['aprovada_periodo']), '#047857'],
                ['Receita total', $fmtR($m['receita']['aprovada_total']), '#334155'],
                ['Assinaturas ativas', $fmtN($m['receita']['assinaturas_ativas']), '#1d4ed8'],
                ['MRR estimado', $fmtR($m['receita']['mrr']), '#047857'],
                ['Créditos vendidos', $fmtN($m['creditos']['vendidos']), '#1d4ed8'],
                ['Créditos consumidos', $fmtN($m['creditos']['consumidos']), '#b45309'],
                ['Saldo na base', $fmtN($m['creditos']['saldo_base']), '#334155'],
                ['Consultas (período)', $fmtN($m['uso']['consultas']), '#1d4ed8'],
                ['Importações (período)', $fmtN($m['uso']['importacoes']), '#1d4ed8'],
                ['Clearance (período)', $fmtN($m['uso']['clearance']), '#1d4ed8'],
                ['Monitoramentos ativos', $fmtN($m['uso']['monitoramentos_ativos']), '#047857'],
                ['Recargas ativas', $fmtN($m['receita']['recargas_ativas']), '#047857'],
            ] as [$label, $valor, $cor])
                <div class="bg-white rounded border border-gray-300 border-l-4 p-3" style="border-left-color: {{ $cor }}">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ $valor }}</p>
                </div>
            @endforeach
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
            <div class="bg-white rounded border border-gray-300 p-4">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Novos usuários</p>
                <div id="chartSignups"></div>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4">
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Receita aprovada</p>
                <div id="chartReceita"></div>
            </div>
        </div>

        {{-- Consumo de créditos por tipo --}}
        <div class="bg-white rounded border border-gray-300 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                    <tr><th class="text-left px-3 py-2.5">Consumo de créditos por tipo</th><th class="text-right px-3 py-2.5">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($m['creditos']['consumo_por_tipo'] as $linha)
                        <tr><td class="px-3 py-2 text-gray-700">{{ $linha['type'] }}</td><td class="px-3 py-2 text-right text-gray-900 font-semibold">{{ $fmtN($linha['total']) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-3 py-6 text-center text-gray-400 text-sm">Sem consumo no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/js/apexcharts.min.js"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    const signups = @json($m['crescimento']['serie_signups']);
    const receita = @json($m['receita']['serie_receita']);
    const line = (sel, dados, nome, cor) => {
        const el = document.querySelector(sel);
        if (!el) return;
        new ApexCharts(el, {
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            series: [{ name: nome, data: dados.map(d => d.total) }],
            xaxis: { categories: dados.map(d => d.data) },
            colors: [cor], dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 2 },
        }).render();
    };
    line('#chartSignups', signups, 'Novos', '#1d4ed8');
    line('#chartReceita', receita, 'Receita', '#047857');
})();
</script>
