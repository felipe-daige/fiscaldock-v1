<?php

namespace App\Services\Clearance\Comparacao;

final class ResolverResult
{
    public function __construct(
        public readonly string $tipoDocumento,
        public readonly ?DeclaradoSource $declarado,
        public readonly ?SefazSource $sefaz,
    ) {}
}
