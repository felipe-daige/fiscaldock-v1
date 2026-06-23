<div style="position:fixed; bottom:14px; left:0; width:100%; border-top:1px solid #d1d5db; padding-top:4px;">
    <table style="width:100%; border-collapse:collapse; color:#6b7280; font-size:8px;">
        <tr>
            <td style="border:none;">FiscalDock · Monitoramento Fiscal Inteligente</td>
            <td style="border:none; text-align:center;">Documento gerado por FiscalDock — uso interno</td>
            <td style="border:none; text-align:right;">Página {PAGE_NUM} de {PAGE_COUNT}</td>
        </tr>
    </table>
</div>
<script type="text/php">
    if (isset($pdf) && isset($fontMetrics)) {
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");
        $size = 8;
        $w = $pdf->get_width();
        $h = $pdf->get_height();
        $texto = "Página {PAGE_NUM} de {PAGE_COUNT}";
        $largura = $fontMetrics->getTextWidth($texto, $font, $size);
        // cobre o placeholder estático com o texto real (já com o n.º de página resolvido)
        $pdf->page_text($w - 28 - $largura, $h - 26, $texto, $font, $size, array(0.42, 0.45, 0.50));
    }
</script>
