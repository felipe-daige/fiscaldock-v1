@extends('reports.layout')

@section('titulo', 'BI Fiscal — Relatório Executivo')
@section('rodape_hash', \App\Support\PdfReport::hashDocumento('bi', json_encode($relatorio['periodo'] ?? [])))

@php
    $p = $relatorio['periodo'];
    $modo = $relatorio['modo'] ?? 'portfolio';
@endphp

@section('meta')
    <div>{{ $modo === 'cliente' ? 'Cliente #'.$p['cliente_id'] : 'Carteira (todos os clientes)' }}</div>
    <div>Período: {{ $p['inicio'] ?? 'Todos' }} a {{ $p['fim'] ?? 'Todos' }}</div>
@endsection

@section('conteudo')
    @php
        $k = $relatorio['kpis'];
        $cob = $relatorio['cobertura'] ?? ['parcial' => false];
        $svc = app(\App\Services\BiExportService::class);
        // Seções que ganham barras CSS (idxLabel, idxValorBrl, hex) — casados às colunas atuais
        $barras = [
            'faturamento' => [0, 1, '#2563eb'],
            'tributos' => [0, 5, '#b45309'],
            'cfop' => [0, 2, '#7c3aed'],
            'uf' => [0, 1, '#0891b2'],
            'catalogo' => [1, 3, '#0d9488'],
            'devolucoes' => [0, 1, '#b45309'],
        ];
    @endphp

    {{-- Indicadores do período (KPIs) --}}
    <div class="secao">
        <div class="secao-header">Indicadores do período</div>
        <div class="secao-body">
            @include('reports.partials._kpi-strip', ['itens' => [
                ['label' => 'Faturamento', 'valor' => 'R$ '.$k['faturamento']],
                ['label' => 'Aquisições', 'valor' => 'R$ '.$k['aquisicoes']],
                ['label' => 'Tributos', 'valor' => 'R$ '.$k['tributos']],
                ['label' => 'Saldo líquido', 'valor' => 'R$ '.$k['saldo_liquido']],
            ]])
        </div>
    </div>

    {{-- Cobertura --}}
    @if (! empty($cob['parcial']))
        @php
            $semFiscal = collect($cob['meses_sem_fiscal'] ?? []);
            $semContrib = collect($cob['meses_sem_contrib'] ?? []);
        @endphp
        <div style="background-color:#fffbeb;border:1px solid #fde68a;padding:8px;margin-bottom:12px;">
            @if ($semFiscal->isNotEmpty())
                <span style="color:#92400e;font-size:9px;display:block;">
                    &#9888; {{ $semFiscal->count() }} {{ $semFiscal->count() === 1 ? 'mês' : 'meses' }} sem EFD ICMS/IPI — entradas incompletas: {{ $semFiscal->pluck('label')->implode(', ') }}
                </span>
            @endif

            @if ($semContrib->isNotEmpty())
                <span style="color:#92400e;font-size:9px;display:block;">
                    &#9888; {{ $semContrib->count() }} {{ $semContrib->count() === 1 ? 'mês' : 'meses' }} sem EFD PIS/COFINS — receita/tributos incompletos: {{ $semContrib->pluck('label')->implode(', ') }}
                </span>
            @endif
        </div>
    @endif

    {{-- Seções na ordem definida pelo service --}}
    @foreach ($relatorio['ordem_secoes'] as $chave)
        @if ($chave === 'score-carteira')
            @php $sc = $relatorio['score_carteira'] ?? null; @endphp
            @if ($sc)
                <div class="secao">
                    <div class="secao-header">Score da carteira</div>
                    <div class="secao-body">
                        @include('reports.partials._kpi-strip', ['itens' => [
                            ['label' => '% Regular', 'valor' => $sc['percentual_regular'].'%'],
                            ['label' => 'Irregulares', 'valor' => $sc['irregulares'].' / '.$sc['participantes_ativos']],
                            ['label' => '% Em risco', 'valor' => $sc['percentual_em_risco'].'%'],
                            ['label' => 'Valor em risco', 'valor' => 'R$ '.$sc['valor_total_em_risco_brl']],
                        ]])
                    </div>
                </div>
            @endif
        @else
            @php $sec = $relatorio['secoes'][$chave] ?? null; @endphp
            @if ($sec)
                <div class="secao">
                    <div class="secao-header">{{ $sec['titulo'] }}</div>
                    <div class="secao-body">
                        @if (isset($barras[$chave]) && ! empty($sec['linhas']))
                            @include('reports.partials._bar-chart', ['itens' => $svc->barChartItens($sec['linhas'], $barras[$chave][0], $barras[$chave][1], $barras[$chave][2])])
                        @endif
                        @include('reports.bi-executivo-tabela', ['sec' => $sec])
                    </div>
                </div>
            @endif
        @endif
    @endforeach
@endsection
