<div style="position:fixed; top:18px; left:0; width:100%;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:150px; vertical-align:middle; border:none;">
                <img src="{{ \App\Support\PdfReport::logoDataUri() }}" alt="FiscalDock" style="height:30px;">
            </td>
            <td style="vertical-align:middle; border:none;">
                <span style="font-size:12px; font-weight:bold; color:#111827; text-transform:uppercase; letter-spacing:.04em;">@yield('titulo', 'Relatório')</span>
            </td>
            <td style="vertical-align:middle; text-align:right; border:none; color:#6b7280; font-size:8px;">
                @yield('meta')
                <div>gerado em {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>
    <div style="height:2px; background:#1f2937; margin-top:4px;"></div>
</div>
