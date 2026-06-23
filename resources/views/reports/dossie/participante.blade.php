@extends('reports.layout')

@section('titulo', 'Dossiê — '.($participante->razao_social ?: $participante->documento))
@section('meta')
    <div class="mono">{{ $participante->documento }}</div>
@endsection

@push('estilos')
<style>
    .grid2 { width:100%; border-collapse:separate; border-spacing:8px 0; }
    .grid2 > tbody > tr > td { width:50%; vertical-align:top; }
    .kpi { width:100%; border-collapse:collapse; }
    .kpi td { border:1px solid #e5e7eb; padding:6px 8px; }
    .kpi .lbl { font-size:7px; color:#9ca3af; text-transform:uppercase; letter-spacing:.06em; }
    .kpi .val { font-size:12px; font-weight:bold; color:#111827; }
    .tab { width:100%; border-collapse:collapse; }
    .tab th { background:#f9fafb; border-bottom:1px solid #d1d5db; padding:4px 5px; text-align:left; font-size:7px; color:#6b7280; text-transform:uppercase; }
    .tab td { border-bottom:1px solid #f3f4f6; padding:4px 5px; font-size:8px; color:#374151; }
    .tab .right { text-align:right; }
    .card { border:1px solid #d1d5db; margin-bottom:8px; page-break-inside:avoid; }
    .card-h { background:#f9fafb; border-bottom:1px solid #e5e7eb; padding:4px 6px; font-size:8px; font-weight:bold; color:#6b7280; text-transform:uppercase; }
    .card-b { padding:5px 6px; }
    .kv td { padding:1px 3px; font-size:8px; border:none; }
    .kv .k { color:#9ca3af; text-transform:uppercase; font-size:7px; width:40%; }
    .score-bar { background:#f3f4f6; height:14px; width:100%; }
    .comprovante a { color:#1d4ed8; font-size:8px; }
    .secao-body { padding:8px; }
</style>
@endpush

@section('conteudo')
    @include('reports.dossie._resumo')
    @include('reports.dossie._infograficos')
    @include('reports.dossie._detalhamento')
@endsection
