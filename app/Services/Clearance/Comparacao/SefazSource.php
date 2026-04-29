<?php

namespace App\Services\Clearance\Comparacao;

interface SefazSource
{
    public function carregar(): NotaNormalizada;

    public function origemLabel(): string;
}
