<table style="width:100%;border-collapse:collapse;">
    <tr>
        @foreach($itens as $kpi)
            <td style="padding:6px 10px;vertical-align:top;{{ $loop->last ? '' : 'border-right:1px solid #e5e7eb;' }}">
                <div style="font-size:7px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px;white-space:nowrap;">{{ $kpi['label'] }}</div>
                <div style="font-size:{{ ($compacto ?? false) ? '10px' : '13px' }};font-weight:bold;color:#111827;white-space:nowrap;">{{ $kpi['valor'] }}</div>
            </td>
        @endforeach
    </tr>
</table>
