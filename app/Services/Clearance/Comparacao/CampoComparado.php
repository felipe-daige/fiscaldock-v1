<?php

namespace App\Services\Clearance\Comparacao;

final class CampoComparado
{
    public function __construct(
        public readonly string $chave,
        public readonly string $label,
        public readonly mixed $declarado,
        public readonly mixed $sefaz,
        public readonly bool $divergente,
        public readonly ?string $tolerancia = null,
        public readonly bool $naoComparavel = false,
    ) {}
}
