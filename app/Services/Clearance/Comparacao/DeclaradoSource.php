<?php

namespace App\Services\Clearance\Comparacao;

interface DeclaradoSource
{
    public function carregar(): NotaNormalizada;

    public function origemLabel(): string;
}
