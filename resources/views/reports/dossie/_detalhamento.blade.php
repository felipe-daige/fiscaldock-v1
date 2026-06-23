@php $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.'); $imp = $movimentacao['impostos']; @endphp

@if($consulta['tem'] && !empty($consulta['blocos']))
<div class="secao">
    <div class="secao-header">Detalhamento das Certidões / Fontes</div>
    <div class="secao-body">
        @foreach($consulta['blocos'] as $bloco)
            <div class="card">
                <div class="card-h">{{ $bloco['titulo'] }} @if(!empty($bloco['badge']))<span class="badge" style="background-color: {{ $bloco['badge']['hex'] }}">{{ $bloco['badge']['label'] }}</span>@endif</div>
                <div class="card-b">
                    @if(!empty($bloco['itens']))
                        <table class="kv">
                            @foreach($bloco['itens'] as $it)<tr><td class="k">{{ $it['label'] }}</td><td>{{ $it['valor'] }}</td></tr>@endforeach
                        </table>
                    @endif
                    @if(!empty($bloco['comprovante_url']))
                        <div class="comprovante"><a href="{{ $bloco['comprovante_url'] }}">Baixar comprovante (PDF)</a></div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="secao">
    <div class="secao-header">Detalhamento — Impostos (EFD)</div>
    @include('reports.partials._kpi-strip', ['itens' => [
        ['label' => 'ICMS', 'valor' => $fmt($imp['icms'])],
        ['label' => 'PIS', 'valor' => $fmt($imp['pis'])],
        ['label' => 'COFINS', 'valor' => $fmt($imp['cofins'])],
        ['label' => 'Alíq. ICMS média', 'valor' => number_format($imp['aliquota_icms_media'], 2, ',', '.').'%'],
    ]])
</div>

<div class="secao">
    <div class="secao-header">Detalhamento — Por CST (ICMS)</div>
    <div class="secao-body">
        <table class="tab">
            <thead><tr><th>CST</th><th class="right">Qtd</th><th class="right">Valor</th></tr></thead>
            <tbody>
            @forelse($movimentacao['por_cst'] as $c)
                <tr><td class="mono">{{ $c['cst'] ?: '—' }}</td><td class="right">{{ $c['qtd'] }}</td><td class="right">{{ $fmt($c['valor']) }}</td></tr>
            @empty
                <tr><td colspan="3" style="color:#9ca3af;">Sem itens EFD.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
