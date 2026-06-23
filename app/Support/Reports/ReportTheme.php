<?php

namespace App\Support\Reports;

use App\Support\CertidaoBadge;

/**
 * Tokens de identidade visual dos relatórios (PDF + XLSX).
 * Fonte única de cores/marca — alinhada ao design system DANFE e ao CertidaoBadge.
 */
final class ReportTheme
{
    public const OK = '#047857';

    public const ALERTA = '#d97706';

    public const IRREGULAR = '#dc2626';

    public const NEUTRO = '#9ca3af';

    public const NAO_ENCONTRADA = '#6b7280';

    public const OUTRO = '#374151';

    /** Caminho do logo embutido no header (PNG, fundo branco removido). */
    private const LOGO_PATH = 'binary_files/logo/logo-fiscaldock_whitebg-removebg.png';

    public static function brandName(): string
    {
        return 'FiscalDock';
    }

    /**
     * Resolve status operacional/cadastral → hex. Reproduz fielmente o mapeamento
     * que vivia inline no blade (sem regressão) e delega o desconhecido ao
     * CertidaoBadge (semântica de certidão).
     */
    public static function statusHex(?string $status): string
    {
        $s = mb_strtoupper(trim((string) $status));

        return match ($s) {
            'ATIVA', 'REGULAR', 'NEGATIVA', 'OK', 'HABILITADO', 'SUCESSO' => self::OK,
            'SUSPENSA', 'EM ANALISE', 'EM ANÁLISE', 'PROCESSANDO' => self::ALERTA,
            'BAIXADA', 'INAPTA', 'IRREGULAR', 'POSITIVA', 'ERRO', 'TIMEOUT', 'RESTRITO' => self::IRREGULAR,
            '' => self::NEUTRO,
            default => CertidaoBadge::classificar($status)['hex'],
        };
    }

    /** Cor da classificação de risco (baixo→crítico). */
    public static function riscoHex(?string $classificacao): string
    {
        return match (mb_strtolower(trim((string) $classificacao))) {
            'baixo' => self::OK,
            'medio', 'médio' => self::ALERTA,
            'alto' => '#ea580c',
            'critico', 'crítico' => self::IRREGULAR,
            default => self::NEUTRO,
        };
    }

    /** Logo como data-URI base64 para embed no dompdf (isRemoteEnabled=false). */
    public static function logoBase64(): ?string
    {
        $path = public_path(self::LOGO_PATH);

        if (! is_file($path)) {
            return null;
        }

        $bin = @file_get_contents($path);

        if ($bin === false) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($bin);
    }
}
