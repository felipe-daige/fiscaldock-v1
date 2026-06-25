{{-- Linha de barra do panorama. Espera $label, $n, $pct, $hex. --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:2px;">
    <tr>
        <td style="width:36%; font-size:8px; color:#374151; padding:1px 0;">{{ $label }}</td>
        <td style="width:8%; font-size:8px; text-align:right; padding:1px 6px 1px 0;"><strong style="color:#111827;">{{ $n }}</strong></td>
        <td style="width:56%; padding:1px 0;">
            <div style="height:7px; background:#f3f4f6;">
                <div style="height:7px; width:{{ $pct }}%; background-color:{{ $hex }};"></div>
            </div>
        </td>
    </tr>
</table>
