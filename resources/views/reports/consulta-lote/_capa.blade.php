{{-- Capa do relatório do lote. Espera $plano, $lote, $resumo, $analise, $gerado_em, $emitente. --}}
@php($cnpjs = $analise['cnpjs'] ?? [])
@php($vereditoCols = [
    ['consultados', (int) ($resumo['total'] ?? 0), '#1f2937'],
    ['regulares', (int) ($cnpjs['regular'] ?? 0), '#047857'],
    ['com pendência', (int) ($cnpjs['pendencia'] ?? 0), '#dc2626'],
    ['indeterminado', (int) ($cnpjs['indeterminado'] ?? 0), '#d97706'],
    ['sem fontes', (int) ($cnpjs['sem_info'] ?? 0), '#9ca3af'],
])
<div class="secao">
    <div class="secao-body" style="padding: 10px 12px;">
        <div style="font-size:16px; font-weight:bold; color:#111827; line-height:1.2;">Relatório de Consulta Fiscal</div>
        <div style="font-size:10px; color:#6b7280; margin-top:1px;">{{ $plano->nome ?? '—' }}</div>
        <div style="font-size:9px; color:#374151; margin-top:6px;">Emitido por <strong>{{ $emitente }}</strong></div>

        <table class="lote-kv" style="margin-top:8px;">
            <tr>
                <td class="k">Plano</td><td class="v">{{ $plano->nome ?? '—' }}</td>
                <td class="k">Lote</td><td class="v mono">#{{ $lote->id }}</td>
                <td class="k">Emitido em</td><td class="v">{{ $gerado_em }}</td>
                <td class="k">CNPJs</td><td class="v">{{ $resumo['total'] ?? 0 }}</td>
            </tr>
        </table>

        <table style="width:100%; table-layout:fixed; border-collapse:collapse; margin-top:10px;">
            <tr>
                @foreach($vereditoCols as $col)
                    <td style="padding:3px;">
                        <div style="background-color:{{ $col[2] }}; padding:5px 4px; text-align:center;">
                            <div style="font-size:14px; font-weight:bold; color:#ffffff;">{{ $col[1] }}</div>
                            <div style="font-size:7px; color:#ffffff; text-transform:uppercase; letter-spacing:.04em;">{{ $col[0] }}</div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>

        <div class="small muted" style="margin-top:8px;">Documento gerado por FiscalDock — uso restrito.</div>
    </div>
</div>
