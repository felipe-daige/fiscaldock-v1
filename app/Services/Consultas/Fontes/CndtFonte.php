<?php

namespace App\Services\Consultas\Fontes;

class CndtFonte extends FonteCertidaoInfoSimples
{
    public function chave(): string
    {
        return 'cndt';
    }

    public function slug(): string
    {
        return 'tribunal/tst/cndt';
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cndt', 2);
    }

    protected function mapearSucesso(array $data): array
    {
        return [
            'status' => $this->statusCertidao($data), // Negativa / Positiva
            'certidao_codigo' => $data['certidao_codigo'] ?? ($data['numero_certidao'] ?? null),
            'emissao_data' => $data['emissao_data'] ?? null,
            'data_validade' => $data['validade_data'] ?? null,
            'conseguiu_emitir' => (bool) ($data['conseguiu_emitir_certidao_negativa'] ?? false),
            'mensagem' => $data['mensagem'] ?? null,
        ];
    }
}
