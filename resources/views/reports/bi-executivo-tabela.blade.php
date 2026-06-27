<table class="table">
    <thead>
        <tr>
            @foreach ($sec['colunas'] as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($sec['linhas'] as $linha)
            <tr>
                @foreach (array_values($linha) as $cel)
                    <td>{{ $cel }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ max(1, count($sec['colunas'])) }}" class="muted center">Sem dados no período</td></tr>
        @endforelse
    </tbody>
</table>
