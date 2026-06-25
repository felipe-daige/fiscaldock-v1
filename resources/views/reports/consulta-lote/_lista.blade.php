{{-- Lista Top-N genérica do dossiê (table-based). Espera $titulo, $cabecalho[], $aligns[], $linhas[][], $vazio. --}}
<div class="secao">
    <div class="secao-header">{{ $titulo }}</div>
    <div class="secao-body">
        @if(empty($linhas))
            <div class="msg">{{ $vazio ?? '—' }}</div>
        @else
            <table class="table" style="width:100%; table-layout:fixed;">
                <thead>
                    <tr>
                        @foreach($cabecalho as $i => $col)
                            <th class="{{ ($aligns[$i] ?? 'left') === 'right' ? 'right' : (($aligns[$i] ?? 'left') === 'center' ? 'center' : '') }}">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($linhas as $linha)
                        <tr>
                            @foreach($linha as $i => $cel)
                                <td class="{{ ($aligns[$i] ?? 'left') === 'right' ? 'right' : (($aligns[$i] ?? 'left') === 'center' ? 'center' : '') }}">{{ $cel }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
