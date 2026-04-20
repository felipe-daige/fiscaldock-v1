<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Consulta Fiscal - Lote #{{ $lote->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
            line-height: 1.35;
        }
        .page { padding: 14px; }
        .section {
            border: 1px solid #d1d5db;
            margin-bottom: 10px;
        }
        .section-header {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 8px;
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .header-grid, .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .header-grid td, .summary-grid td {
            border-right: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }
        .header-grid td:last-child, .summary-grid td:last-child {
            border-right: none;
        }
        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .muted { color: #6b7280; }
        .meta-label {
            font-size: 8px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 3px;
        }
        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
            border-radius: 3px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: #f9fafb;
            border-bottom: 1px solid #d1d5db;
            padding: 6px 5px;
            text-align: left;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 5px;
            vertical-align: top;
            font-size: 8px;
            color: #374151;
        }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .right { text-align: right; }
        .center { text-align: center; }
        .small { font-size: 7px; }
        .footer {
            margin-top: 8px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>
<body>
@php
    $statusHex = fn ($status) => match (strtoupper((string) $status)) {
        'ATIVA', 'REGULAR', 'NEGATIVA', 'OK', 'HABILITADO', 'SUCESSO' => '#047857',
        'SUSPENSA', 'EM ANALISE', 'PROCESSANDO' => '#d97706',
        'BAIXADA', 'INAPTA', 'IRREGULAR', 'POSITIVA', 'ERRO', 'TIMEOUT', 'RESTRITO' => '#dc2626',
        default => '#9ca3af',
    };
@endphp

<div class="page">
    <div class="section">
        <div class="section-header">Identificação do Relatório</div>
        <table class="header-grid">
            <tr>
                <td style="width: 34%">
                    <div class="brand">FiscalDock</div>
                    <div class="muted">Relatório consolidado de consulta fiscal em lote</div>
                </td>
                <td style="width: 22%">
                    <div class="meta-label">Lote</div>
                    <div class="meta-value">#{{ $lote->id }}</div>
                </td>
                <td style="width: 22%">
                    <div class="meta-label">Plano</div>
                    <div class="meta-value">{{ $plano->nome ?? 'N/A' }}</div>
                </td>
                <td style="width: 22%">
                    <div class="meta-label">Gerado em</div>
                    <div class="meta-value">{{ $gerado_em }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-header">Resumo Operacional</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="meta-label">Total Consultado</div>
                    <div class="meta-value">{{ $resumo['total'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Sucesso</div>
                    <div class="meta-value">{{ $resumo['sucesso'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Erros</div>
                    <div class="meta-value">{{ $resumo['erro'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Score Médio</div>
                    <div class="meta-value">{{ $resumo['score_medio'] }}</div>
                </td>
                <td>
                    <div class="meta-label">CND Federal OK</div>
                    <div class="meta-value">{{ $resumo['cnd_federal']['negativa'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="meta-label">CND Federal Restrita</div>
                    <div class="meta-value">{{ $resumo['cnd_federal']['positiva'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-header">Resultados</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%">CNPJ</th>
                    <th style="width: 19%">Razão Social</th>
                    <th style="width: 4%">UF</th>
                    <th style="width: 8%">Situação</th>
                    <th style="width: 7%">Simples</th>
                    @if(in_array('sintegra', $plano->consultas_incluidas ?? []))
                        <th style="width: 8%">SINTEGRA</th>
                    @endif
                    @if(in_array('cnd_federal', $plano->consultas_incluidas ?? []))
                        <th style="width: 10%">CND Federal</th>
                    @endif
                    @if(in_array('crf_fgts', $plano->consultas_incluidas ?? []))
                        <th style="width: 8%">FGTS</th>
                    @endif
                    @if(in_array('cndt', $plano->consultas_incluidas ?? []))
                        <th style="width: 8%">CNDT</th>
                    @endif
                    @if(in_array('tcu_consolidada', $plano->consultas_incluidas ?? []))
                        <th style="width: 8%">Compliance</th>
                    @endif
                    <th style="width: 6%" class="center">Score</th>
                    <th style="width: 10%">Classificação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultados as $r)
                    @php
                        $situacao = $r['status_consulta'] === 'sucesso' ? ($r['situacao_cadastral'] ?? '-') : 'ERRO';
                        $complianceLabel = '-';
                        if ($r['ceis'] === 'Sim' || $r['cnep'] === 'Sim') {
                            $complianceLabel = 'RESTRITO';
                        } elseif ($r['tcu_situacao'] || $r['ceis'] === 'Nao') {
                            $complianceLabel = 'OK';
                        }
                    @endphp
                    <tr>
                        <td class="mono">{{ $r['documento'] }}</td>
                        <td>
                            <strong style="color: #111827">{{ \Illuminate\Support\Str::limit($r['razao_social'], 38) }}</strong>
                            @if($r['nome_fantasia'])
                                <div class="small muted">{{ \Illuminate\Support\Str::limit($r['nome_fantasia'], 36) }}</div>
                            @endif
                        </td>
                        <td>{{ $r['uf'] ?: '-' }}</td>
                        <td>
                            <span class="badge" style="background-color: {{ $statusHex($situacao) }}">{{ $situacao }}</span>
                        </td>
                        <td>{{ $r['simples_nacional'] ?: '-' }}</td>
                        @if(in_array('sintegra', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['sintegra_situacao'])
                                    <span class="badge" style="background-color: {{ $statusHex($r['sintegra_situacao']) }}">{{ $r['sintegra_situacao'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('cnd_federal', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['cnd_federal_status'])
                                    <span class="badge" style="background-color: {{ $statusHex($r['cnd_federal_status']) }}">{{ $r['cnd_federal_status'] }}</span>
                                    @if($r['cnd_federal_validade'])
                                        <div class="small muted">Val. {{ $r['cnd_federal_validade'] }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('crf_fgts', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['crf_fgts_status'])
                                    <span class="badge" style="background-color: {{ $statusHex($r['crf_fgts_status']) }}">{{ $r['crf_fgts_status'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('cndt', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['cndt_status'])
                                    <span class="badge" style="background-color: {{ $statusHex($r['cndt_status']) }}">{{ $r['cndt_status'] }}</span>
                                    @if($r['cndt_validade'])
                                        <div class="small muted">Val. {{ $r['cndt_validade'] }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('tcu_consolidada', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($complianceLabel !== '-')
                                    <span class="badge" style="background-color: {{ $statusHex($complianceLabel) }}">{{ $complianceLabel }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        <td class="center"><strong style="color: #111827">{{ $r['score_total'] }}</strong></td>
                        <td>
                            <span class="badge" style="background-color: {{ $statusHex($r['classificacao']) }}">{{ strtoupper($r['classificacao']) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Relatório gerado automaticamente pelo FiscalDock com base em consultas oficiais. Lote #{{ $lote->id }} | Usuário {{ $lote->user_id }} | {{ $gerado_em }}
    </div>
</div>
</body>
</html>
