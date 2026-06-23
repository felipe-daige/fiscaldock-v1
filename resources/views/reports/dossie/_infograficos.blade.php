@php
    use App\Support\Reports\ReportTheme;
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $comp = $movimentacao['por_competencia'];
    $maxComp = collect($comp)->flatMap(fn ($c) => [$c['entrada'], $c['saida']])->max() ?: 1;
    $barsComp = [];
    foreach ($comp as $c) {
        $barsComp[] = ['label' => $c['competencia'].' ent', 'valor' => $fmt($c['entrada']), 'pct' => (int) round($c['entrada'] / $maxComp * 100), 'hex' => ReportTheme::OK];
        $barsComp[] = ['label' => $c['competencia'].' sai', 'valor' => $fmt($c['saida']), 'pct' => (int) round($c['saida'] / $maxComp * 100), 'hex' => ReportTheme::IRREGULAR];
    }
    $cfop = $movimentacao['por_cfop'];
    $maxCfop = collect($cfop)->max('valor') ?: 1;
    $barsCfop = array_map(fn ($c) => ['label' => $c['cfop'], 'valor' => $fmt($c['valor']), 'pct' => (int) round($c['valor'] / $maxCfop * 100), 'hex' => '#4338ca'], $cfop);
@endphp
<div class="secao">
    <div class="secao-header">Infográficos — Movimentação por Competência</div>
    <div class="secao-body">
        @if(!empty($barsComp))
            @include('reports.partials._bar-chart', ['itens' => $barsComp])
        @else
            <span style="font-size:8px;color:#9ca3af;">Sem movimentação EFD registrada.</span>
        @endif
    </div>
</div>
<div class="secao">
    <div class="secao-header">Infográficos — Top CFOPs</div>
    <div class="secao-body">
        @if(!empty($barsCfop))
            @include('reports.partials._bar-chart', ['itens' => $barsCfop])
        @else
            <span style="font-size:8px;color:#9ca3af;">Sem itens EFD.</span>
        @endif
    </div>
</div>
