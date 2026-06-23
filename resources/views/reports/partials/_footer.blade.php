<div style="position:fixed; bottom:14px; left:0; width:100%; border-top:1px solid #e5e7eb; padding-top:4px;">
    <table style="width:100%; border-collapse:collapse; color:#9ca3af; font-size:8px;">
        <tr>
            <td style="border:none;">FiscalDock · Monitoramento Fiscal Inteligente</td>
            <td style="border:none;"></td>{{-- nº da página é desenhado pelo script php abaixo (dompdf não resolve {PAGE_NUM} em HTML) --}}
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
        $largura = $fontMetrics->getTextWidth("Página 99 de 99", $font, $size);
        $pdf->page_text($w - 32 - $largura, $h - 26, $texto, $font, $size, array(0.42, 0.45, 0.50));
    }
</script>
