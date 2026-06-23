<table style="width:100%;border-collapse:collapse;">
    <tr>
        @foreach($itens as $kpi)
            <td style="border-right:1px solid #e5e7eb;padding:8px;vertical-align:top;{{ $loop->last ? 'border-right:none;' : '' }}">
                <div style="font-size:8px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px;">{{ $kpi['label'] }}</div>
                <div style="font-size:11px;font-weight:bold;color:#111827;">{{ $kpi['valor'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
