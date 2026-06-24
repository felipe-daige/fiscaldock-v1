<div style="position:fixed; top:-74px; left:0; width:100%;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:210px; vertical-align:middle; border:none; white-space:nowrap;">
                <img src="{{ \App\Support\PdfReport::logoDataUri() }}" alt="FiscalDock" style="height:26px; vertical-align:middle;">
                <span style="vertical-align:middle; margin-left:7px; font-size:16px; font-weight:bold; color:#1e4679; letter-spacing:.01em;">FiscalDock</span>
            </td>
            <td style="vertical-align:middle; border:none;">
                <span style="font-size:13px; font-weight:bold; color:#1f2937; text-transform:uppercase; letter-spacing:.04em;">@yield('titulo', 'Relatório')</span>
            </td>
            <td style="vertical-align:middle; text-align:right; border:none; color:#6b7280; font-size:8px;">
                @yield('meta')
                <div>gerado em {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>
    <div style="height:1.5px; background:#1f2937; margin-top:5px;"></div>
</div>
