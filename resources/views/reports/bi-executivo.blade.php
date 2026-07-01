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

@push('estilos')
    @include('reports.dossie._estilos')
@endpush

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
            'devolucoes' => [0, 1, '#be185d'],
        ];
    @endphp

    {{-- Indicadores do período (KPIs) --}}
    <div class="secao">
        <div class="secao-header">Indicadores do período</div>
        <div class="secao-body">
            @include('reports.partials._kpi-strip', ['itens' => [
                ['label' => 'Faturamento', 'valor' => 'R$ '.$k['faturamento']],
                ['label' => 'Aquisições', 'valor' => 'R$ '.$k['aquisicoes']],
                ['label' => 'Tributos (débito s/ saída)', 'valor' => 'R$ '.$k['tributos']],
                ['label' => 'A recolher (apurado)', 'valor' => 'R$ '.($relatorio['a_recolher_brl'] ?? '0,00')],
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
        @elseif ($chave === 'contrapartes')
            @php $sec = $relatorio['secoes']['contrapartes'] ?? null; @endphp
            @if ($sec && ! empty($sec['itens']))
                @php
                    $itens = $sec['itens'];
                    $maxVol = collect($itens)->max('volume') ?: 0;
                    $temPapel = ($sec['modo'] ?? '') === 'cliente';
                    $badgeHex = ['baixo' => '#16a34a', 'medio' => '#f59e0b', 'alto' => '#ea580c', 'critico' => '#dc2626'];
                @endphp
                <div class="secao">
                    <div class="secao-header">{{ $sec['titulo'] }}</div>
                    <div class="secao-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    @if ($temPapel)<th>Papel</th>@endif
                                    <th>CNPJ</th>
                                    <th>Razão social</th>
                                    <th class="center">Score</th>
                                    <th class="right">Volume</th>
                                    <th class="right">Notas</th>
                                    <th>Principais CFOPs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($itens as $it)
                                    @php $hex = $badgeHex[$it['classificacao']] ?? '#9ca3af'; @endphp
                                    <tr>
                                        @if ($temPapel)<td>{{ $it['papel'] }}</td>@endif
                                        <td class="mono">{{ $it['cnpj'] }}</td>
                                        <td>{{ $it['razao'] }}</td>
                                        <td class="center">
                                            @if ($it['classificacao'])
                                                <span class="badge" style="background-color:{{ $hex }}">{{ $it['classificacao'] }}{{ $it['score_total'] !== null ? ' '.$it['score_total'] : '' }}</span>
                                            @else
                                                <span class="badge" style="background-color:#9ca3af">nunca consultado</span>
                                            @endif
                                        </td>
                                        <td class="right">
                                            <div style="font-weight:bold;">R$ {{ $it['volume_brl'] }}</div>
                                            <div style="background:#f3f4f6;height:5px;width:100%;">
                                                <div style="background-color:#2563eb;height:5px;width:{{ $maxVol > 0 ? (int) round($it['volume'] / $maxVol * 100) : 0 }}%;"></div>
                                            </div>
                                        </td>
                                        <td class="right">{{ $it['notas'] }}</td>
                                        <td class="small">{{ count($it['cfops']) ? implode(' · ', $it['cfops']) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            @php
                $sec = $relatorio['secoes'][$chave] ?? null;
                $cc = $relatorio['cobertura_consulta'] ?? ['total' => 0, 'sem_consulta' => 0, 'sem_uf' => 0];
            @endphp
            @if ($sec)
                <div class="secao">
                    <div class="secao-header">{{ $sec['titulo'] }}</div>
                    <div class="secao-body">
                        @if (in_array($chave, ['riscos-notas', 'riscos-fornecedores'], true) && empty($sec['linhas']) && ($cc['sem_consulta'] ?? 0) > 0)
                            <div style="background-color:#fffbeb;border:1px solid #fde68a;padding:6px;font-size:9px;color:#92400e;">
                                &#9888; {{ $cc['sem_consulta'] }} de {{ $cc['total'] }} participantes nunca consultados — risco não avaliado (sem dado de certidão/cadastro).
                            </div>
                        @endif

                        @if ($chave === 'uf' && $modo === 'portfolio' && ($cc['sem_uf'] ?? 0) > 0)
                            <div style="background-color:#fffbeb;border:1px solid #fde68a;padding:6px;font-size:9px;color:#92400e;margin-bottom:6px;">
                                &#9888; {{ $cc['sem_uf'] }} participantes sem UF ({{ $cc['sem_uf_cnpj'] ?? 0 }} CNPJ, {{ $cc['sem_uf_cpf'] ?? 0 }} CPF) — distribuição geográfica incompleta. CPF não tem UF de estabelecimento (esperado); consulte os {{ $cc['sem_uf_cnpj'] ?? 0 }} CNPJ para enriquecer.
                            </div>
                        @endif

                        @if (isset($barras[$chave]) && ! empty($sec['linhas']))
                            @include('reports.partials._bar-chart', ['itens' => $svc->barChartItens($sec['linhas'], $barras[$chave][0], $barras[$chave][1], $barras[$chave][2])])
                        @endif
                        @include('reports.bi-executivo-tabela', ['sec' => $sec])
                    </div>
                </div>
            @endif
        @endif
    @endforeach

    @if (! empty($dossies))
        <div class="secao" style="page-break-before:always;">
            <div class="secao-header">Dossiês</div>
        </div>

        @if (! empty($dossies['clientes']))
            <div class="secao-header" style="background:#374151;letter-spacing:.06em;">Clientes</div>
            @foreach ($dossies['clientes'] as $d)
                <div style="{{ $loop->first ? '' : 'page-break-before:always;' }}">
                    @include('reports.dossie._bloco', array_merge($d, ['participante' => $d['cliente']]))
                </div>
            @endforeach
        @endif

        @if (! empty($dossies['participantes']))
            <div class="secao-header" style="background:#374151;letter-spacing:.06em;page-break-before:always;">Participantes</div>
            @foreach ($dossies['participantes'] as $d)
                <div style="{{ $loop->first ? '' : 'page-break-before:always;' }}">
                    @include('reports.dossie._bloco', $d)
                </div>
            @endforeach
        @endif
    @endif
@endsection
