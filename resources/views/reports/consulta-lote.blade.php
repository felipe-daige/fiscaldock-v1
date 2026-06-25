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
    /* dossiê: certidão empilhada full-width (mais espaço lateral); cada bloco não parte entre páginas */
    .cert { border: 1px solid #e5e7eb; border-left: 3px solid #9ca3af; padding: 5px 8px; margin-bottom: 6px; page-break-inside: avoid; }
    .badge { white-space: nowrap; }
    .panorama-bloco { margin-bottom: 8px; page-break-inside: avoid; }
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
        @include('reports.consulta-lote._panorama-lote', ['resumo' => $resumo, 'analise' => $analise ?? []])
    </div>

    {{-- Resultados --}}
    <div class="secao">
        <div class="secao-header">Resultados</div>
        {{-- Visão geral: certidões/comprovantes completos vivem na página de dossiê de cada CNPJ. --}}
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 16%">CNPJ</th>
                    <th style="width: 30%">Razão Social</th>
                    <th style="width: 5%">UF</th>
                    <th style="width: 13%">Situação</th>
                    <th style="width: 16%">Regime Tributário</th>
                    <th style="width: 8%" class="center">Score</th>
                    <th style="width: 12%">Classificação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultados as $r)
                    @php($situacao = $r['status_consulta'] === 'sucesso' ? ($r['situacao_cadastral'] ?? '-') : 'ERRO')
                    <tr>
                        <td class="mono">{{ $r['documento'] }}</td>
                        <td>
                            <strong style="color: #111827">{{ \Illuminate\Support\Str::limit($r['razao_social'], 48) }}</strong>
                            @if($r['nome_fantasia'])
                                <div class="small muted">{{ \Illuminate\Support\Str::limit($r['nome_fantasia'], 46) }}</div>
                            @endif
                        </td>
                        <td>{{ $r['uf'] ?: '-' }}</td>
                        <td>
                            <span class="badge" style="background-color: {{ \App\Support\Reports\ReportTheme::statusHex($situacao) }}">{{ $situacao }}</span>
                        </td>
                        <td>{{ $r['regime_tributario'] ?: '-' }}</td>
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
