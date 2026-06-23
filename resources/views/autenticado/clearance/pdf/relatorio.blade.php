@php
    /** @var array $r */
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $sevHex = ['critica' => '#b91c1c', 'revisar' => '#b45309', 'ok' => '#15803d'];
    $sevLabel = ['critica' => 'Crítica', 'revisar' => 'A revisar', 'ok' => 'Conforme'];
    $vSev = $r['resumo']['veredito']['severidade'] ?? 'ok';
@endphp
@extends('reports.layout')

@section('titulo', 'Clearance DF-e — Lote #'.($r['capa']['lote_id'] ?? ''))

@section('meta')
    @include('reports.partials._badge', ['hex' => $sevHex[$vSev] ?? '#6b7280', 'label' => $sevLabel[$vSev] ?? ucfirst($vSev)])
    <div>Hash: {{ $r['hash'] }}</div>
@endsection

@push('estilos')
    <style>
        h1, h2, h3 { margin: 0; color: #0b1f3a; }
        .muted { color: #6b7280; }
        .sec-title { font-size: 12px; color: #0b1f3a; border-bottom: 2px solid #0b1f3a; padding-bottom: 3px; margin: 18px 0 8px 0; }
        .kv td { padding: 2px 0; vertical-align: top; }
        .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; }
        .grid td { vertical-align: top; padding: 0 6px; }
        .data-table th { background-color: #0b1f3a; color: #ffffff; font-size: 8.5px; text-align: left; padding: 5px 6px; }
        .data-table td { font-size: 8.5px; padding: 4px 6px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
    </style>
@endpush

@section('conteudo')

    {{-- ===== ACERVO AUDITADO ===== --}}
    <table class="grid" style="margin-bottom:14px;">
        <tr>
            <td style="width:50%;">
                <div class="box">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Escritório responsável</div>
                    <div style="font-weight:bold; color:#0b1f3a;">{{ $r['capa']['escritorio']['razao_social'] }}</div>
                    <div class="muted">CNPJ {{ $r['capa']['escritorio']['cnpj'] }}</div>
                </div>
            </td>
            <td style="width:50%;">
                <div class="box">
                    <div class="muted" style="font-size:8px; text-transform:uppercase;">Acervo auditado</div>
                    <div style="font-weight:bold; color:#0b1f3a;">{{ $r['capa']['cliente_auditado']['razao_social'] }}</div>
                    <div class="muted">Período: {{ $r['capa']['periodo']['label'] }} · Lote #{{ $r['capa']['lote_id'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== RESUMO EXECUTIVO ===== --}}
    <div class="sec-title">Resumo executivo</div>
    <div class="box" style="background-color:#f8fafc;">
        {{ $r['resumo']['veredito']['mensagem'] ?? '—' }}
    </div>
    <table class="grid" style="margin-top:10px; text-align:center;">
        <tr>
            @php
                $cards = [
                    ['Documentos auditados', $r['resumo']['total_documentos'], '#0b1f3a'],
                    ['Divergências', $r['resumo']['total_divergencias'], '#b45309'],
                    ['Críticas', $r['resumo']['total_criticas'], '#b91c1c'],
                    ['Exposição total', $r['exposicao']['total_label'], '#b91c1c'],
                ];
            @endphp
            @foreach ($cards as [$rotulo, $valor, $cor])
                <td style="width:25%;">
                    <div class="box">
                        <div style="font-size:16px; font-weight:bold; color:{{ $cor }};">{{ $valor }}</div>
                        <div class="muted" style="font-size:8px;">{{ $rotulo }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- ===== EXPOSIÇÃO FISCAL ===== --}}
    <div class="sec-title">Exposição fiscal estimada</div>
    <table class="data-table">
        <tr><th>Componente</th><th style="text-align:right;">Valor</th></tr>
        <tr><td>Crédito / imposto exposto (divergências críticas)</td><td style="text-align:right;">{{ $r['exposicao']['base_label'] }}</td></tr>
        <tr><td>Multa de ofício (75% — art. 44, I, Lei 9.430/96)</td><td style="text-align:right;">{{ $r['exposicao']['multa_label'] }}</td></tr>
        <tr><td style="font-weight:bold; color:#0b1f3a;">Exposição total estimada</td><td style="text-align:right; font-weight:bold; color:#b91c1c;">{{ $r['exposicao']['total_label'] }}</td></tr>
    </table>
    <div class="muted" style="font-size:7.5px; margin-top:4px;">
        Estimativa. Não inclui juros de mora (Selic acumulada). Prazo decadencial: 5 anos da emissão (art. 173, I, CTN).
    </div>

    {{-- ===== CONCENTRAÇÃO DE RISCO ===== --}}
    @if ($r['concentracao']->isNotEmpty())
        <div class="sec-title">Concentração de risco — principais emitentes</div>
        <table class="data-table">
            <tr><th>#</th><th>Emitente</th><th>CNPJ</th><th style="text-align:center;">Divergências</th><th style="text-align:right;">Valor exposto</th></tr>
            @foreach ($r['concentracao'] as $i => $emit)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $emit['emit_nome'] }}</td>
                    <td>{{ $emit['emit_cnpj'] }}</td>
                    <td style="text-align:center;">{{ $emit['qtd'] }}</td>
                    <td style="text-align:right;">{{ $emit['valor_exposto_label'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- ===== DIVERGÊNCIAS POR DOCUMENTO ===== --}}
    <div class="sec-title" style="page-break-before: always;">Divergências por documento</div>
    @if ($r['documentos']->isEmpty())
        <div class="muted">Nenhuma divergência crítica ou a revisar neste lote.</div>
    @else
        <table class="data-table">
            <tr>
                <th>Severidade</th><th>Documento</th><th>Emitente</th>
                <th style="text-align:right;">Declarado</th><th style="text-align:right;">SEFAZ</th><th style="text-align:right;">Δ</th>
                <th>Decadência</th><th style="text-align:right;">Exposição</th>
            </tr>
            @foreach ($r['documentos'] as $doc)
                @php $s = $doc->severidade ?? 'ok'; @endphp
                <tr>
                    <td><span class="badge" style="background-color: {{ $sevHex[$s] ?? '#6b7280' }};">{{ $sevLabel[$s] ?? ucfirst($s) }}</span></td>
                    <td>
                        {{ ($doc->tipo_documento ?? 'NFE') }} {{ $doc->numero ?? '' }}/{{ $doc->serie ?? '' }}<br>
                        <span class="muted" style="font-size:7px;">{{ $doc->chave_acesso ?? '—' }}</span>
                    </td>
                    <td>{{ $doc->emit_nome ?? '—' }}<br><span class="muted" style="font-size:7px;">{{ $doc->emit_cnpj ?? '' }}</span></td>
                    <td style="text-align:right;">{{ $doc->declarado_valor_label ?? '—' }}</td>
                    <td style="text-align:right;">{{ $doc->valor_total_label ?? '—' }}</td>
                    <td style="text-align:right;">{{ $doc->delta_valor_label ?? '—' }}</td>
                    <td>{{ $doc->decadencia_label ?? '—' }}</td>
                    <td style="text-align:right;">{{ $fmt($doc->exposicao_base ?? 0) }}</td>
                </tr>
                @if (! empty($doc->motivos))
                    <tr><td></td><td colspan="7" class="muted" style="font-size:7.5px; border-bottom:1px solid #eef2f7;">{{ implode(' · ', (array) $doc->motivos) }}</td></tr>
                @endif
            @endforeach
        </table>
    @endif

    {{-- ===== ANEXO: SEM DIVERGÊNCIA ===== --}}
    @if ($r['sem_divergencia']->isNotEmpty())
        <div class="sec-title">Anexo — documentos sem divergência ({{ $r['sem_divergencia']->count() }})</div>
        <div class="muted" style="font-size:7.5px; margin-bottom:4px;">Evidência de cobertura: documentos auditados que conferem com a SEFAZ dentro da tolerância de ruído (R$ {{ number_format($r['metodologia']['tolerancia_absoluta'], 2, ',', '.') }} ou {{ $r['metodologia']['tolerancia_percentual'] }}%).</div>
        <table class="data-table">
            <tr><th>Documento</th><th>Emitente</th><th style="text-align:right;">Valor</th></tr>
            @foreach ($r['sem_divergencia'] as $doc)
                <tr>
                    <td>{{ ($doc->tipo_documento ?? 'NFE') }} {{ $doc->numero ?? '' }}/{{ $doc->serie ?? '' }}</td>
                    <td>{{ $doc->emit_nome ?? '—' }}</td>
                    <td style="text-align:right;">{{ $doc->valor_total_label ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- ===== METODOLOGIA / NOTA LEGAL ===== --}}
    <div class="sec-title">Metodologia e nota legal</div>
    <div class="muted" style="font-size:8px;">
        <strong>Fonte da verdade:</strong> consulta à Receita Federal via InfoSimples (Declarado × SEFAZ por chave de acesso).
        <strong>Tolerância de ruído:</strong> R$ {{ number_format($r['metodologia']['tolerancia_absoluta'], 2, ',', '.') }} ou {{ $r['metodologia']['tolerancia_percentual'] }}%.
        <strong>Severidade:</strong> crítica (ação imediata) / a revisar (análise recomendada).
        A exposição fiscal é <strong>estimativa</strong> e não substitui parecer formal; a multa de ofício segue o art. 44, I da Lei 9.430/96 e o prazo decadencial o art. 173, I do CTN.
        Integridade verificável pelo hash SHA-256 no rodapé.
    </div>

@endsection
