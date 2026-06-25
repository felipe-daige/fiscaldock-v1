@php
    $avaliadas = collect($detalhamento ?? [])->filter(fn ($l) => $l['avaliado'] ?? false);
@endphp
@if($avaliadas->isEmpty())
    <div class="muted small" style="padding:4px 2px;">Score não avaliado nesta consulta (nenhuma fonte de risco retornou).</div>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Categoria</th>
                <th class="right" style="width:48px;">Peso</th>
                <th style="width:46%;">Subscore</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalhamento as $linha)
                <tr>
                    <td>{{ $linha['label'] }}</td>
                    <td class="right" style="white-space:nowrap;">{{ $linha['peso_pct'] }}%</td>
                    <td>
                        @if($linha['avaliado'])
                            <table style="width:100%;"><tr>
                                <td style="padding:0;">
                                    <div style="background:#f3f4f6;height:8px;width:100%;">
                                        <div style="background-color:{{ $linha['hex'] }};height:8px;width:{{ max(0, min(100, (int) $linha['score'])) }}%;"></div>
                                    </div>
                                </td>
                                <td style="padding:0 0 0 6px;width:26px;white-space:nowrap;text-align:right;color:{{ $linha['hex'] }};font-weight:bold;">{{ $linha['score'] }}</td>
                            </tr></table>
                        @else
                            <span class="muted small">Não avaliado</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
