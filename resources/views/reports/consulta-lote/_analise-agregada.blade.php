{{-- Análise da Consulta (agregada por fonte). Espera $analise. --}}
@if(!empty($analise['por_fonte']))
    <div class="secao">
        <div class="secao-header">Análise da Consulta</div>
        <div class="secao-body">
            <table class="table" style="width:100%; table-layout:fixed;">
                <thead>
                    <tr>
                        <th>Fonte</th><th class="center">Regular</th><th class="center">Atenção</th>
                        <th class="center">Indeterm.</th><th class="center">N/Consult.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analise['por_fonte'] as $f)
                        <tr>
                            <td>{{ $f['titulo'] }}</td>
                            <td class="center">{{ (int) ($f['regular'] ?? 0) }}</td>
                            <td class="center">{{ (int) ($f['atencao'] ?? 0) }}</td>
                            <td class="center">{{ (int) ($f['indeterminado'] ?? 0) }}</td>
                            <td class="center">{{ (int) ($f['neutro'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
