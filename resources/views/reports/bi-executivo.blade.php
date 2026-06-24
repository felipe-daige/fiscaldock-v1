@extends('reports.layout')

@section('titulo', 'BI Fiscal — Relatório Executivo')
@section('rodape_hash', \App\Support\PdfReport::hashDocumento('bi', json_encode($relatorio['periodo'] ?? [])))

@section('conteudo')
    @php
        $p = $relatorio['periodo'];
        $k = $relatorio['kpis'];
        $cob = $relatorio['cobertura'] ?? ['parcial' => false];
        $svc = app(\App\Services\BiExportService::class);
    @endphp

    <h1 style="font-size:16px;font-weight:bold;color:#111827;margin:0 0 2px;">BI Fiscal — Relatório Executivo</h1>
    <p style="font-size:9px;color:#6b7280;margin:0 0 12px;">
        Período: {{ $p['inicio'] ?? 'Todos' }} a {{ $p['fim'] ?? 'Todos' }}
        · Cliente: {{ $p['cliente_id'] ? '#'.$p['cliente_id'] : 'Todos' }}
        · Gerado em {{ now()->format('d/m/Y H:i') }}
    </p>

    {{-- KPIs --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
        <tr>
            @foreach ([
                ['Faturamento', $k['faturamento']], ['Aquisições', $k['aquisicoes']],
                ['Tributos', $k['tributos']], ['Saldo líquido', $k['saldo_liquido']],
            ] as $kpi)
                <td style="width:25%;border:1px solid #e5e7eb;padding:8px;vertical-align:top;">
                    <div style="font-size:8px;color:#9ca3af;text-transform:uppercase;">{{ $kpi[0] }}</div>
                    <div style="font-size:12px;font-weight:bold;color:#111827;">R$ {{ $kpi[1] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Faixa de cobertura (espelha o banner da tela: sem ICMS/IPI E sem PIS/COFINS) --}}
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

    {{-- Faturamento mensal: barras + tabela --}}
    @php $secF = $relatorio['secoes']['faturamento']; @endphp
    <h2 style="font-size:11px;font-weight:bold;color:#374151;margin:10px 0 4px;">{{ $secF['titulo'] }}</h2>
    @include('reports.partials._bar-chart', ['itens' => $svc->barChartItens($secF['linhas'], 0, 1, '#2563eb')])
    @include('reports.bi-executivo-tabela', ['sec' => $secF])

    {{-- Tributos por mês: barras (Total Tributos = última coluna antes da alíquota) + tabela --}}
    @php $secT = $relatorio['secoes']['tributos']; @endphp
    <h2 style="font-size:11px;font-weight:bold;color:#374151;margin:14px 0 4px;">{{ $secT['titulo'] }}</h2>
    @include('reports.partials._bar-chart', ['itens' => $svc->barChartItens($secT['linhas'], 0, 5, '#b45309')])
    @include('reports.bi-executivo-tabela', ['sec' => $secT])

    {{-- Declarado x Computado: só tabela --}}
    @php $secA = $relatorio['secoes']['apuracao-notas']; @endphp
    <h2 style="font-size:11px;font-weight:bold;color:#374151;margin:14px 0 4px;">{{ $secA['titulo'] }}</h2>
    @include('reports.bi-executivo-tabela', ['sec' => $secA])

    {{-- CFOP: só tabela --}}
    @php $secC = $relatorio['secoes']['cfop']; @endphp
    <h2 style="font-size:11px;font-weight:bold;color:#374151;margin:14px 0 4px;">{{ $secC['titulo'] }}</h2>
    @include('reports.bi-executivo-tabela', ['sec' => $secC])
@endsection
