@php
    /** @var array $r */
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $sevHex = ['critica' => '#dc2626', 'revisar' => '#d97706', 'ok' => '#047857'];
    $sevLabel = ['critica' => 'Crítica', 'revisar' => 'A revisar', 'ok' => 'Conforme'];
    $vSev = $r['resumo']['veredito']['severidade'] ?? 'ok';
@endphp
@extends('reports.layout')

@section('titulo', 'Clearance DF-e — Lote #'.($r['capa']['lote_id'] ?? ''))
@section('rodape_hash', strtoupper(substr((string) ($r['hash'] ?? ''), 0, 12)))

@section('meta')
    @include('reports.partials._badge', ['hex' => $sevHex[$vSev] ?? '#6b7280', 'label' => $sevLabel[$vSev] ?? ucfirst($vSev)])
    <div>Hash: {{ $r['hash'] }}</div>
@endsection

@push('estilos')
<style>
    .grid2 { width:100%; border-collapse:separate; border-spacing:6px 0; table-layout:fixed; }
    .grid2 > tbody > tr > td { width:50%; vertical-align:top; padding:0; border:none; }
    .kv2 { width:100%; border-collapse:collapse; }
    .kv2 td { padding:1px 0; font-size:8px; vertical-align:top; border:none; }
</style>
@endpush

@section('conteudo')

    {{-- ===== ACERVO AUDITADO ===== --}}
    <div class="secao">
        <div class="secao-header">Acervo Auditado</div>
        <div class="secao-body">
            <table class="grid2">
                <tr>
                    <td>
                        <div class="card-slate">
                            <div class="small muted" style="text-transform:uppercase;">Escritório responsável</div>
                            <div style="font-weight:bold; color:#111827;">{{ $r['capa']['escritorio']['razao_social'] }}</div>
                            <div class="muted mono" style="font-size:7.5px;">CNPJ {{ $r['capa']['escritorio']['cnpj'] }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="card-slate">
                            <div class="small muted" style="text-transform:uppercase;">Acervo auditado</div>
                            <div style="font-weight:bold; color:#111827;">{{ $r['capa']['cliente_auditado']['razao_social'] }}</div>
                            <div class="muted" style="font-size:7.5px;">Período: {{ $r['capa']['periodo']['label'] }} · Lote #{{ $r['capa']['lote_id'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ===== RESUMO EXECUTIVO ===== --}}
    <div class="secao">
        <div class="secao-header">Resumo Executivo</div>
        <div class="secao-body">
            <div class="card-slate" style="background:#f9fafb;">{{ $r['resumo']['veredito']['mensagem'] ?? '—' }}</div>
        </div>
        @include('reports.partials._kpi-strip', ['itens' => [
            ['label' => 'Documentos auditados', 'valor' => $r['resumo']['total_documentos']],
            ['label' => 'Divergências', 'valor' => $r['resumo']['total_divergencias']],
            ['label' => 'Críticas', 'valor' => $r['resumo']['total_criticas']],
            ['label' => 'Exposição total', 'valor' => $r['exposicao']['total_label']],
        ]])
    </div>

    {{-- ===== EXPOSIÇÃO FISCAL ===== --}}
    <div class="secao">
        <div class="secao-header">Exposição Fiscal Estimada</div>
        <div class="secao-body">
            <table class="table">
                <thead><tr><th>Componente</th><th class="right">Valor</th></tr></thead>
                <tbody>
                    <tr><td>Crédito / imposto exposto (divergências críticas)</td><td class="right">{{ $r['exposicao']['base_label'] }}</td></tr>
                    <tr><td>Multa de ofício (75% — art. 44, I, Lei 9.430/96)</td><td class="right">{{ $r['exposicao']['multa_label'] }}</td></tr>
                    <tr><td style="font-weight:bold; color:#111827;">Exposição total estimada</td><td class="right" style="font-weight:bold; color:#dc2626;">{{ $r['exposicao']['total_label'] }}</td></tr>
                </tbody>
            </table>
            <div class="muted small" style="margin-top:4px;">
                Estimativa. Não inclui juros de mora (Selic acumulada). Prazo decadencial: 5 anos da emissão (art. 173, I, CTN).
            </div>
        </div>
    </div>

    {{-- ===== CONCENTRAÇÃO DE RISCO ===== --}}
    @if ($r['concentracao']->isNotEmpty())
        <div class="secao">
            <div class="secao-header">Concentração de Risco — Principais Emitentes</div>
            <div class="secao-body">
                <table class="table">
                    <thead><tr><th style="width:5%;">#</th><th>Emitente</th><th>CNPJ</th><th class="center">Divergências</th><th class="right">Valor exposto</th></tr></thead>
                    <tbody>
                        @foreach ($r['concentracao'] as $i => $emit)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $emit['emit_nome'] }}</td>
                                <td class="mono">{{ $emit['emit_cnpj'] }}</td>
                                <td class="center">{{ $emit['qtd'] }}</td>
                                <td class="right">{{ $emit['valor_exposto_label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== DIVERGÊNCIAS POR DOCUMENTO ===== --}}
    <div class="secao" style="page-break-before: always;">
        <div class="secao-header">Divergências por Documento</div>
        <div class="secao-body">
            @if ($r['documentos']->isEmpty())
                <div class="muted">Nenhuma divergência crítica ou a revisar neste lote.</div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Severidade</th><th>Documento</th><th>Emitente</th>
                            <th class="right">Declarado</th><th class="right">SEFAZ</th><th class="right">Δ</th>
                            <th>Decadência</th><th class="right">Exposição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($r['documentos'] as $doc)
                            @php $s = $doc->severidade ?? 'ok'; @endphp
                            <tr>
                                <td><span class="badge" style="background-color: {{ $sevHex[$s] ?? '#6b7280' }};">{{ $sevLabel[$s] ?? ucfirst($s) }}</span></td>
                                <td>
                                    {{ ($doc->tipo_documento ?? 'NFE') }} {{ $doc->numero ?? '' }}/{{ $doc->serie ?? '' }}<br>
                                    <span class="muted mono" style="font-size:7px;">{{ $doc->chave_acesso ?? '—' }}</span>
                                </td>
                                <td>{{ $doc->emit_nome ?? '—' }}<br><span class="muted mono" style="font-size:7px;">{{ $doc->emit_cnpj ?? '' }}</span></td>
                                <td class="right">{{ $doc->declarado_valor_label ?? '—' }}</td>
                                <td class="right">{{ $doc->valor_total_label ?? '—' }}</td>
                                <td class="right">{{ $doc->delta_valor_label ?? '—' }}</td>
                                <td>{{ $doc->decadencia_label ?? '—' }}</td>
                                <td class="right">{{ $fmt($doc->exposicao_base ?? 0) }}</td>
                            </tr>
                            @if (! empty($doc->motivos))
                                <tr><td></td><td colspan="7" class="muted" style="font-size:7.5px;">{{ implode(' · ', (array) $doc->motivos) }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- ===== ANEXO: SEM DIVERGÊNCIA ===== --}}
    @if ($r['sem_divergencia']->isNotEmpty())
        <div class="secao">
            <div class="secao-header">Anexo — Documentos sem Divergência <span class="meta">{{ $r['sem_divergencia']->count() }}</span></div>
            <div class="secao-body">
                <div class="muted small" style="margin-bottom:4px;">Evidência de cobertura: documentos auditados que conferem com a SEFAZ dentro da tolerância de ruído (R$ {{ number_format($r['metodologia']['tolerancia_absoluta'], 2, ',', '.') }} ou {{ $r['metodologia']['tolerancia_percentual'] }}%).</div>
                <table class="table">
                    <thead><tr><th>Documento</th><th>Emitente</th><th class="right">Valor</th></tr></thead>
                    <tbody>
                        @foreach ($r['sem_divergencia'] as $doc)
                            <tr>
                                <td>{{ ($doc->tipo_documento ?? 'NFE') }} {{ $doc->numero ?? '' }}/{{ $doc->serie ?? '' }}</td>
                                <td>{{ $doc->emit_nome ?? '—' }}</td>
                                <td class="right">{{ $doc->valor_total_label ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== METODOLOGIA / NOTA LEGAL ===== --}}
    <div class="secao">
        <div class="secao-header">Metodologia e Nota Legal</div>
        <div class="secao-body">
            <div class="muted" style="font-size:8px;">
                <strong>Fonte da verdade:</strong> consulta à Receita Federal via InfoSimples (Declarado × SEFAZ por chave de acesso).
                <strong>Tolerância de ruído:</strong> R$ {{ number_format($r['metodologia']['tolerancia_absoluta'], 2, ',', '.') }} ou {{ $r['metodologia']['tolerancia_percentual'] }}%.
                <strong>Severidade:</strong> crítica (ação imediata) / a revisar (análise recomendada).
                A exposição fiscal é <strong>estimativa</strong> e não substitui parecer formal; a multa de ofício segue o art. 44, I da Lei 9.430/96 e o prazo decadencial o art. 173, I do CTN.
                Integridade verificável pelo hash SHA-256 no cabeçalho.
            </div>
        </div>
    </div>

@endsection
