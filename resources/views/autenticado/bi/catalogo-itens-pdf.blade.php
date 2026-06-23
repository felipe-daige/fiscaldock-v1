@php
    /** @var \Illuminate\Support\Collection $itens */
    /** @var array $resumoFiltros */
    $fmt = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $origemHex = ['efd' => '#1d4ed8', 'xml' => '#7c3aed', 'ambas' => '#047857'];
@endphp

@extends('reports.layout')

@section('titulo', 'Catálogo de Itens')

@section('meta')
    <div>{{ number_format($itens->count(), 0, ',', '.') }} itens</div>
    <div>Total movimentado: {{ $fmt($totalValor) }}</div>
@endsection

@push('estilos')
<style>
    .muted { color: #6b7280; }
    .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; margin-top: 10px; }
    .kv td { padding: 1px 0; vertical-align: top; font-size: 8.5px; }
    .data-table { margin-top: 10px; }
    .data-table th { background-color: #1f2937; color: #fff; font-size: 8px; text-align: left; padding: 5px; }
    .data-table td { font-size: 8px; padding: 4px 5px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
    .data-table tr:nth-child(even) td { background-color: #f8fafc; }
    .num { text-align: right; }
</style>
@endpush

@section('conteudo')

    {{-- Filtros aplicados --}}
    <div class="box">
        <div style="font-size:8px; text-transform:uppercase; color:#6b7280; margin-bottom:3px;">Filtros aplicados</div>
        @if(count($resumoFiltros))
            <table class="kv">
                @foreach($resumoFiltros as $f)
                    <tr>
                        <td style="width:90px; color:#6b7280;">{{ $f['rotulo'] }}</td>
                        <td style="font-weight:bold;">{{ $f['valor'] }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <div style="font-size:8.5px;">Nenhum filtro — todos os itens movimentados.</div>
        @endif
    </div>

    {{-- Tabela de itens --}}
    <table class="data-table">
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
                    <td style="font-weight:bold;">{{ $i['codigo_item'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($i['descricao'] ?: '—', 60) }}</td>
                    <td>
                        <span class="badge" style="background-color: {{ $origemHex[$i['fontes']] ?? '#334155' }};">
                            {{ $i['fontes'] }}
                        </span>
                    </td>
                    <td>{{ $i['ncm'] ?: '—' }}</td>
                    <td>{{ $i['cfops'] ?: '—' }}</td>
                    <td>{{ $i['csts'] ?: '—' }}</td>
                    <td class="num">{{ number_format((float) $i['quantidade'], 0, ',', '.') }}</td>
                    <td class="num">{{ $i['ocorrencias'] }}</td>
                    <td class="num">
                        {{ $i['aliquota_media'] !== null ? number_format((float) $i['aliquota_media'], 2, ',', '.').'%' : '—' }}
                    </td>
                    <td class="num" style="font-weight:bold;">{{ $fmt($i['valor_total']) }}</td>
                    <td>
                        {{ $i['tem_catalogo'] ? \Illuminate\Support\Str::limit($i['catalogo']['descr_item'] ?? 'Sim', 40) : 'Sem catálogo' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; padding:14px; color:#9ca3af;">
                        Nenhum item movimentado no período/filtro.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

@endsection
