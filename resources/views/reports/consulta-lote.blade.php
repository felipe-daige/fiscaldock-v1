@extends('reports.layout')

@section('titulo', 'Consulta Fiscal · Lote #'.$lote->id)
@section('rodape_hash', \App\Support\PdfReport::hashDocumento('lote', $lote->id, optional($lote->updated_at)->timestamp))

@section('meta')
    <div>Plano: {{ $plano->nome ?? 'N/A' }}</div>
    <div>Lote #{{ $lote->id }}</div>
@endsection

@push('estilos')
<style>
    .muted { color: #6b7280; }
    .table th {
        background: #f9fafb;
        border-bottom: 1.5px solid #1f2937;
        padding: 6px 5px;
        text-align: left;
        font-size: 7.5px;
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
    .table tbody tr:nth-child(even) td { background: #fbfbfc; }
    .right { text-align: right; }
    .center { text-align: center; }
    .small { font-size: 7px; }
    /* ── Resumo do lote — kv horizontal ──────────────────────── */
    .lote-kv { width: 100%; border-collapse: collapse; }
    .lote-kv td { border: none; padding: 2px 6px; font-size: 8px; vertical-align: top; }
    .lote-kv .k { color: #9ca3af; text-transform: uppercase; font-size: 7px; }
    .lote-kv .v { color: #111827; font-weight: bold; }
    /* ── Detalhamento por CNPJ ───────────────────────────────── */
    .cnpj-block { margin-bottom: 12px; page-break-inside: avoid; }
    .cnpj-head { background: #1f2937; color: #fff; padding: 5px 8px; }
    .cnpj-head .doc { font-family: DejaVu Sans Mono, monospace; font-size: 10px; font-weight: bold; }
    .cnpj-head .nome { font-size: 9px; color: #e5e7eb; }
    .cnpj-resumo {
        padding: 5px 8px; font-size: 8px; color: #374151;
        background: #f9fafb; border: 1px solid #e5e7eb; border-top: none;
    }
    .cards { width: 100%; border-collapse: separate; border-spacing: 6px 6px; table-layout: fixed; }
    .cards > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; border: none; word-wrap: break-word; }
    .card { border: 1px solid #e5e7eb; border-top: 2px solid #1f2937; }
    .card-head { background: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 4px 6px; }
    .card-head td { border: none; padding: 0; }
    .card-title { font-size: 8px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; }
    .card-body { padding: 5px 6px; }
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 1px 3px; font-size: 8px; vertical-align: top; border: none; }
    .kv .k { color: #9ca3af; text-transform: uppercase; font-size: 7px; width: 40%; }
    .kv .v { color: #374151; }
    .list-title { font-size: 7px; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-top: 5px; }
    .list-item { font-size: 8px; color: #374151; line-height: 1.3; }
    .msg { font-size: 7px; color: #6b7280; font-style: italic; border-left: 2px solid #e5e7eb; padding-left: 5px; margin-top: 5px; }
    .comprovante { margin-top: 6px; font-size: 8px; }
    .comprovante a { color: #1d4ed8; text-decoration: underline; font-weight: bold; }
    .comprovante .url { font-family: DejaVu Sans Mono, monospace; font-size: 6px; color: #9ca3af; word-break: break-all; margin-top: 1px; }
</style>
@endpush

@section('conteudo')
    {{-- Resumo do Lote --}}
    <div class="secao">
        <div class="secao-header">Resumo do Lote</div>
        <div class="secao-body">
            <table class="lote-kv">
                <tr>
                    <td class="k">Lote</td>
                    <td class="v mono">#{{ $lote->id }}</td>
                    <td class="k">Plano</td>
                    <td class="v">{{ $plano->nome ?? 'N/A' }}</td>
                    <td class="k">Total de CNPJs</td>
                    <td class="v">{{ $resumo['total'] }}</td>
                    <td class="k">Gerado em</td>
                    <td class="v">{{ $gerado_em }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Resumo Operacional --}}
    <div class="secao">
        <div class="secao-header">Resumo Operacional</div>
        @include('reports.partials._kpi-strip', ['itens' => [
            ['label' => 'Total Consultado', 'valor' => $resumo['total']],
            ['label' => 'Sucesso', 'valor' => $resumo['sucesso']],
            ['label' => 'Erros', 'valor' => $resumo['erro']],
            ['label' => 'Score Médio', 'valor' => $resumo['score_medio']],
            ['label' => 'CND Federal OK', 'valor' => $resumo['cnd_federal']['negativa'] ?? 0],
            ['label' => 'CND Federal Restrita', 'valor' => $resumo['cnd_federal']['positiva'] ?? 0],
        ]])
    </div>

    {{-- Resultados --}}
    <div class="secao">
        <div class="secao-header">Resultados</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%">CNPJ</th>
                    <th style="width: 15%">Razão Social</th>
                    <th style="width: 4%">UF</th>
                    <th style="width: 8%">Situação</th>
                    <th style="width: 11%">Regime Tributário</th>
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
                            <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($situacao) }}">{{ $situacao }}</span>
                        </td>
                        <td>{{ $r['regime_tributario'] ?: '-' }}</td>
                        @if(in_array('sintegra', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['sintegra_situacao'])
                                    <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($r['sintegra_situacao']) }}">{{ $r['sintegra_situacao'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('cnd_federal', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['cnd_federal_status'])
                                    <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($r['cnd_federal_status']) }}">{{ $r['cnd_federal_status'] }}</span>
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
                                    <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($r['crf_fgts_status']) }}">{{ $r['crf_fgts_status'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        @if(in_array('cndt', $plano->consultas_incluidas ?? []))
                            <td>
                                @if($r['cndt_status'])
                                    <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($r['cndt_status']) }}">{{ $r['cndt_status'] }}</span>
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
                                    <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($complianceLabel) }}">{{ $complianceLabel }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                        <td class="center"><strong style="color: #111827">{{ $r['score_total'] }}</strong></td>
                        <td>
                            <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::riscoHex($r['classificacao']) }}">{{ strtoupper($r['classificacao']) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($analise) && !empty($analise['por_fonte']))
        @include('reports.consulta-lote._analise-agregada', ['analise' => $analise])
    @endif

    @if(!empty($detalhes) && $detalhes->count())
        @foreach($detalhes as $d)
            @include('reports.consulta-lote._cnpj', ['d' => $d])
        @endforeach
    @endif
@endsection
