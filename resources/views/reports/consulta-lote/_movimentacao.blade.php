{{-- Totais Entradas/Saídas (2 col uniformes). Espera $fiscal. --}}
<div class="secao">
    <div class="secao-header">Movimentação fiscal</div>
    <div class="secao-body">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
            <tr>
                <td style="width:50%; padding:4px 8px; border-right:1px solid #e5e7eb; vertical-align:top;">
                    <div class="list-title">Entradas (comprado)</div>
                    <div style="font-size:11px; font-weight:bold; color:#111827;">R$ {{ number_format((float) ($fiscal['total_comprado'] ?? 0), 2, ',', '.') }}</div>
                    <div class="small muted">{{ (int) ($fiscal['qtd_entrada'] ?? 0) }} nota(s)</div>
                </td>
                <td style="width:50%; padding:4px 8px; vertical-align:top;">
                    <div class="list-title">Saídas (vendido)</div>
                    <div style="font-size:11px; font-weight:bold; color:#111827;">R$ {{ number_format((float) ($fiscal['total_vendido'] ?? 0), 2, ',', '.') }}</div>
                    <div class="small muted">{{ (int) ($fiscal['qtd_saida'] ?? 0) }} nota(s)</div>
                </td>
            </tr>
        </table>
    </div>
</div>
