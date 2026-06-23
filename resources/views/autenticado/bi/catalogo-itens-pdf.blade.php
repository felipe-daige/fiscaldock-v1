@php
    /** @var \Illuminate\Support\Collection $itens */
    /** @var array $resumoFiltros */
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $origemHex = ['efd' => '#1d4ed8', 'xml' => '#7c3aed', 'ambas' => '#047857'];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 26px 48px 26px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 9px; line-height: 1.4; }
        h1 { margin: 0; color: #0b1f3a; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; margin-top: 10px; }
        .kv td { padding: 1px 0; vertical-align: top; font-size: 8.5px; }
        .data-table { margin-top: 10px; }
        .data-table th { background-color: #0b1f3a; color: #fff; font-size: 8px; text-align: left; padding: 5px 5px; }
        .data-table td { font-size: 8px; padding: 4px 5px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        .num { text-align: right; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; color: #fff; font-size: 7.5px; text-transform: uppercase; }
        .footer { position: fixed; bottom: -32px; left: 0; right: 0; font-size: 7.5px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>

    <div class="footer">
        <table><tr>
            <td style="text-align:left;">FiscalDock — Catálogo × Itens de Nota</td>
            <td style="text-align:right;">Gerado em {{ $geradoEm->format('d/m/Y H:i') }}</td>
        </tr></table>
    </div>

    {{-- Cabeçalho --}}
    <table>
        <tr>
            <td style="vertical-align:middle;">
                <h1 style="font-size:18px;">Fiscal<span style="color:#2563eb;">Dock</span></h1>
                <div class="muted" style="font-size:9px;">Catálogo × Itens de Nota</div>
            </td>
            <td style="text-align:right; vertical-align:middle;">
                <div style="font-size:8px; text-transform:uppercase; color:#6b7280;">Itens no relatório</div>
                <div style="font-size:16px; font-weight:bold; color:#0b1f3a;">{{ number_format($itens->count(), 0, ',', '.') }}</div>
                <div class="muted" style="font-size:8px;">Total movimentado: {{ $fmt($totalValor) }}</div>
            </td>
        </tr>
    </table>

    {{-- Filtros aplicados --}}
    <div class="box">
        <div style="font-size:8px; text-transform:uppercase; color:#6b7280; margin-bottom:3px;">Filtros aplicados</div>
        @if(count($resumoFiltros))
            <table class="kv">
                @foreach($resumoFiltros as $f)
                    <tr><td style="width:90px; color:#6b7280;">{{ $f['rotulo'] }}</td><td style="font-weight:bold;">{{ $f['valor'] }}</td></tr>
                @endforeach
            </table>
        @else
            <div style="font-size:8.5px;">Nenhum filtro — todos os itens movimentados.</div>
        @endif
    </div>

    {{-- Tabela de itens --}}
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Origem</th>
                <th>NCM</th>
                <th>CFOPs</th>
                <th>CSTs</th>
                <th class="num">Qtd</th>
                <th class="num">Ocorr.</th>
                <th class="num">Alíq. méd.</th>
                <th class="num">Valor mov.</th>
                <th>Catálogo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itens as $i)
                <tr>
                    <td style="font-weight:bold;">{{ $i['codigo_item'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($i['descricao'] ?: '—', 60) }}</td>
                    <td><span class="badge" style="background-color: {{ $origemHex[$i['fontes']] ?? '#334155' }};">{{ $i['fontes'] }}</span></td>
                    <td>{{ $i['ncm'] ?: '—' }}</td>
                    <td>{{ $i['cfops'] ?: '—' }}</td>
                    <td>{{ $i['csts'] ?: '—' }}</td>
                    <td class="num">{{ number_format((float) $i['quantidade'], 0, ',', '.') }}</td>
                    <td class="num">{{ $i['ocorrencias'] }}</td>
                    <td class="num">{{ $i['aliquota_media'] !== null ? number_format((float) $i['aliquota_media'], 2, ',', '.').'%' : '—' }}</td>
                    <td class="num" style="font-weight:bold;">{{ $fmt($i['valor_total']) }}</td>
                    <td>{{ $i['tem_catalogo'] ? \Illuminate\Support\Str::limit($i['catalogo']['descr_item'] ?? 'Sim', 40) : 'Sem catálogo' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" style="text-align:center; padding:14px; color:#9ca3af;">Nenhum item movimentado no período/filtro.</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
