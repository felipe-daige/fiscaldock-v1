<style>
    /* dompdf resolve counter(page) por página em elemento position:fixed (repete em todas).
       counter(pages) (total) resolve 0 neste build e {PAGE_COUNT} via script resolve 1 →
       total de páginas indisponível; usar só "Página N". */
    .rodape-paginacao:before { content: "Página " counter(page); }
</style>
<div style="position:fixed; bottom:14px; left:0; width:100%; border-top:1px solid #e5e7eb; padding-top:4px;">
    <table style="width:100%; border-collapse:collapse; color:#9ca3af; font-size:8px;">
        <tr>
            <td style="border:none; width:34%;">FiscalDock · Monitoramento Fiscal Inteligente</td>
            <td style="border:none; text-align:center;">Emitido por @yield('rodape_emissor', \App\Support\PdfReport::emissor())@hasSection('rodape_hash') · Doc @yield('rodape_hash')@endif</td>
            <td style="border:none; width:24%; text-align:right;"><span class="rodape-paginacao"></span></td>
        </tr>
    </table>
</div>
