{{-- Panorama de risco do lote. Espera $resumo e $analise. Table-based (dompdf sem flex/grid). --}}
@php($cls = $resumo['por_classificacao'] ?? [])
@php($cnpjs = $analise['cnpjs'] ?? [])
@php($sit = $resumo['por_situacao'] ?? [])

@include('reports.partials._kpi-strip', ['itens' => [
    ['label' => 'Total Consultado', 'valor' => $resumo['total'] ?? 0],
    ['label' => 'Concluídos', 'valor' => $resumo['sucesso'] ?? 0],
    ['label' => 'Falhas', 'valor' => $resumo['erro'] ?? 0],
    ['label' => 'Score Médio', 'valor' => $resumo['score_medio'] ?? 0],
]])

@php($riscoRows = [['Baixo', (int) ($cls['baixo'] ?? 0), 'baixo'], ['Médio', (int) ($cls['medio'] ?? 0), 'medio'], ['Alto', (int) ($cls['alto'] ?? 0), 'alto'], ['Crítico', (int) ($cls['critico'] ?? 0), 'critico']])
@php($riscoBase = array_sum(array_map(fn ($r) => $r[1], $riscoRows)))
<div class="panorama-bloco">
    <div class="list-title">Distribuição de risco</div>
    @foreach($riscoRows as $row)
        @include('reports.consulta-lote._barra-linha', ['label' => $row[0], 'n' => $row[1], 'pct' => $riscoBase > 0 ? round(100 * $row[1] / $riscoBase, 1) : 0, 'hex' => \App\Support\Reports\ReportTheme::riscoHex($row[2])])
    @endforeach
</div>

@php($regRows = [['Regulares', (int) ($cnpjs['regular'] ?? 0), '#047857'], ['Com pendência', (int) ($cnpjs['pendencia'] ?? 0), '#dc2626'], ['Indeterminado', (int) ($cnpjs['indeterminado'] ?? 0), '#d97706'], ['Sem fontes de regularidade', (int) ($cnpjs['sem_info'] ?? 0), '#9ca3af']])
@php($regBase = array_sum(array_map(fn ($r) => $r[1], $regRows)))
<div class="panorama-bloco">
    <div class="list-title">Regularidade fiscal</div>
    @foreach($regRows as $row)
        @include('reports.consulta-lote._barra-linha', ['label' => $row[0], 'n' => $row[1], 'pct' => $regBase > 0 ? round(100 * $row[1] / $regBase, 1) : 0, 'hex' => $row[2]])
    @endforeach
</div>

<div class="panorama-bloco">
    <div class="list-title">Situação cadastral</div>
    @if(empty($sit))
        <div class="msg">—</div>
    @else
        @php($sitBase = array_sum($sit))
        @foreach(collect($sit)->sortDesc()->all() as $situacao => $n)
            @include('reports.consulta-lote._barra-linha', ['label' => $situacao ?: '—', 'n' => (int) $n, 'pct' => $sitBase > 0 ? round(100 * $n / $sitBase, 1) : 0, 'hex' => \App\Support\Reports\ReportTheme::statusHex((string) $situacao)])
        @endforeach
    @endif
</div>
