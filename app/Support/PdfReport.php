<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

final class PdfReport
{
    public static function render(string $view, array $dados = [], string $orientacao = 'portrait'): DomPDF
    {
        return Pdf::loadView($view, $dados)
            ->setPaper('a4', $orientacao)
            ->setOptions([
                'isPhpEnabled' => true,          // habilita o script de numeração de página
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,      // logo é base64, sem remoto
                'defaultFont' => 'DejaVu Sans',
            ]);
    }

    /**
     * Hash curto de integridade sobre os identificadores do documento (não os bytes do PDF
     * nem o timestamp de emissão) → mesmo documento ⇒ mesmo hash. Prova anti-adulteração no rodapé.
     */
    public static function hashDocumento(int|string|null ...$partes): string
    {
        $base = implode('|', array_map(static fn ($p) => (string) $p, $partes));

        return strtoupper(substr(hash('sha256', $base), 0, 12));
    }

    /**
     * Identificador de origem do relatório no rodapé: o domínio da marca (não a razão social
     * do escritório). Views podem sobrescrever via @yield('rodape_emissor').
     */
    public static function emissor(): string
    {
        return 'fiscaldock.com.br';
    }

    public static function logoDataUri(): string
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $path = public_path('binary_files/logo/logo-fiscaldock_whitebg-removebg.png');
        $bin = is_file($path) ? (string) file_get_contents($path) : '';

        return $cache = 'data:image/png;base64,'.base64_encode($bin);
    }
}
