@php
    $k = $movimentacao['kpis'];
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $scoreHex = \App\Support\Reports\ReportTheme::riscoHex($score['classificacao']);
@endphp
<div class="secao">
    <div class="secao-header">Identificação</div>
    <div class="secao-body">
        <table class="kv">
            <tr><td class="k">Razão Social</td><td>{{ $participante->razao_social ?: '—' }}</td>
                <td class="k">Situação</td><td>{{ data_get($consulta, 'blocos.0') ? ($participante->situacao_cadastral ?? '—') : ($participante->situacao_cadastral ?? '—') }}</td></tr>
            <tr><td class="k">CNPJ</td><td class="mono">{{ $participante->documento }}</td>
                <td class="k">UF</td><td>{{ $participante->uf ?: '—' }}</td></tr>
        </table>
    </div>
</div>

<div class="secao">
    <div class="secao-header">Regularidade & Score</div>
    <div class="secao-body">
        <table class="grid2"><tr>
            <td>
                @if($consulta['tem'])
                    @foreach(array_slice($consulta['blocos'], 0, 6) as $b)
                        @if(!empty($b['badge']))
                            <span class="badge" style="background-color: {{ $b['badge']['hex'] }}">{{ $b['titulo'] }}: {{ $b['badge']['label'] }}</span>
                        @endif
                    @endforeach
                @else
                    <span style="font-size:8px;color:#9ca3af;">Sem consulta de certidões para este participante.</span>
                @endif
            </td>
            <td>
                <div class="kpi"><table><tr>
                    <td><div class="lbl">Score Fiscal</div><div class="val" style="color: {{ $scoreHex }}">{{ $score['score_total'] }}</div></td>
                    <td><div class="lbl">Classificação</div><div class="val">{{ ucfirst($score['classificacao']) }}</div></td>
                </tr></table></div>
                <div class="score-bar" style="margin-top:4px;"><div style="background-color:{{ $scoreHex }};width:{{ max(0,min(100,(int)$score['score_total'])) }}%;height:14px;"></div></div>
            </td>
        </tr></table>
    </div>
</div>

<div class="secao">
    <div class="secao-header">Movimentações (resumo)</div>
    @include('reports.partials._kpi-strip', ['itens' => [
        ['label' => 'Total Notas', 'valor' => $k['total_notas']],
        ['label' => 'Valor Movimentado', 'valor' => $fmt($k['valor_movimentado'])],
        ['label' => 'Entradas', 'valor' => $k['entradas_qtd'].' · '.$fmt($k['entradas_valor'])],
        ['label' => 'Saídas', 'valor' => $k['saidas_qtd'].' · '.$fmt($k['saidas_valor'])],
        ['label' => 'Período', 'valor' => ($k['periodo_inicio'] ?? '—').' a '.($k['periodo_fim'] ?? '—')],
    ]])
</div>
