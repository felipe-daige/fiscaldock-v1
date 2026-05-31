<?php

namespace App\Support;

class CndFederal
{
    public const LABEL_INDETERMINADO = 'Indeterminada';
    public const HEX_INDETERMINADO = '#d97706';

    /**
     * Analisa o retorno de CND Federal (PGFN/RFB) e isola o caso INDETERMINADO.
     *
     * Regra canônica: INDETERMINADO nunca é irregular — significa que a fonte
     * oficial não conseguiu emitir a certidão pela internet. A mensagem de
     * origem é preservada, apenas normalizada na formatação.
     *
     * Para qualquer outro status retorna indeterminado=false e campos nulos,
     * deixando a classificação (Negativa/Positiva/etc.) a cargo de quem chamou.
     *
     * @return array{indeterminado: bool, label: ?string, hex: ?string, motivo: ?string}
     */
    public static function analisar(mixed $cnd): array
    {
        $vazio = ['indeterminado' => false, 'label' => null, 'hex' => null, 'motivo' => null];

        if (! is_array($cnd)) {
            return $vazio;
        }

        $status = strtoupper(trim((string) ($cnd['status'] ?? '')));
        $conseguiuEmitir = $cnd['conseguiu_emitir'] ?? null;

        $indeterminado = $status === 'INDETERMINADO' || $conseguiuEmitir === false;

        if (! $indeterminado) {
            return $vazio;
        }

        return [
            'indeterminado' => true,
            'label' => self::LABEL_INDETERMINADO,
            'hex' => self::HEX_INDETERMINADO,
            'motivo' => self::normalizarMotivo($cnd),
        ];
    }

    private static function normalizarMotivo(array $cnd): ?string
    {
        $bruto = $cnd['mensagem'] ?? null;

        if (! is_string($bruto) || trim($bruto) === '') {
            $errors = $cnd['errors'] ?? null;
            $bruto = is_array($errors) ? ($errors[0] ?? null) : null;
        }

        if (! is_string($bruto) || trim($bruto) === '') {
            return null;
        }

        $texto = trim($bruto);
        $texto = preg_replace('/\s+/u', ' ', $texto);             // colapsa brancos múltiplos
        $texto = preg_replace('/\s+([,.;:!?])/u', '$1', $texto);    // remove espaço antes de pontuação

        return $texto;
    }
}
