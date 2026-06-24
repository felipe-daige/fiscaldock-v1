@php
    /** @var \Illuminate\Support\Collection $itens */
    /** @var array $resumoFiltros */
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $origemHex = ['efd' => '#1d4ed8', 'xml' => '#7c3aed', 'ambas' => '#047857'];
@endphp

@extends('reports.layout')

@section('titulo', 'Catálogo de Itens')
@section('rodape_hash', \App\Support\PdfReport::hashDocumento('cat', json_encode($resumoFiltros ?? []), $itens->count()))

@section('meta')
    <div>{{ number_format($itens->count(), 0, ',', '.') }} itens</div>
    <div>Total movimentado: {{ $fmt($totalValor) }}</div>
@endsection

@push('estilos')
<style>
    .kv2 { width:100%; border-collapse:collapse; }
    .kv2 td { padding:1px 0; vertical-align:top; font-size:8px; border:none; }
    .num { text-align:right; }
</style>
@endpush

@section('conteudo')

    {{-- ===== FILTROS APLICADOS ===== --}}
    <div class="secao">
        <div class="secao-header">Filtros Aplicados</div>
        <div class="secao-body">
            @if(count($resumoFiltros))
                <table class="kv2">
                    @foreach($resumoFiltros as $f)
                        <tr>
                            <td style="width:120px; color:#9ca3af; text-transform:uppercase; font-size:7px;">{{ $f['rotulo'] }}</td>
                            <td style="font-weight:bold; color:#374151;">{{ $f['valor'] }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="muted" style="font-size:8.5px;">Nenhum filtro — todos os itens movimentados.</div>
            @endif
        </div>
    </div>

    {{-- ===== ITENS ===== --}}
    <div class="secao">
        <div class="secao-header">Itens <span class="meta">{{ number_format($itens->count(), 0, ',', '.') }}</span></div>
        <div class="secao-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Origem</th>
                        <th>NCM</th>
                        <th>CFOPs</th>
                        <th>CSTs</th>
                        <th class="num">Qtd</th>
                        <th class="num">Ocorr.</th>
                        <th class="num">Alíq. méd.</th>
                        <th class="num">Valor mov.</th>
                        <th>Catálogo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itens as $i)
                        <tr>
                            <td class="mono" style="font-weight:bold;">{{ $i['codigo_item'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($i['descricao'] ?: '—', 60) }}</td>
                            <td><span class="badge" style="background-color: {{ $origemHex[$i['fontes']] ?? '#334155' }};">{{ $i['fontes'] }}</span></td>
                            <td class="mono">{{ $i['ncm'] ?: '—' }}</td>
                            <td>{{ $i['cfops'] ?: '—' }}</td>
                            <td>{{ $i['csts'] ?: '—' }}</td>
                            <td class="num">{{ number_format((float) $i['quantidade'], 0, ',', '.') }}</td>
                            <td class="num">{{ $i['ocorrencias'] }}</td>
                            <td class="num">{{ $i['aliquota_media'] !== null ? number_format((float) $i['aliquota_media'], 2, ',', '.').'%' : '—' }}</td>
                            <td class="num" style="font-weight:bold; color:#111827;">{{ $fmt($i['valor_total']) }}</td>
                            <td>{{ $i['tem_catalogo'] ? \Illuminate\Support\Str::limit($i['catalogo']['descr_item'] ?? 'Sim', 40) : 'Sem catálogo' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="center muted" style="padding:14px;">Nenhum item movimentado no período/filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
