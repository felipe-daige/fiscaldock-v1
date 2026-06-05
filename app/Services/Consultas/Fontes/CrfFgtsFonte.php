<?php

namespace App\Services\Consultas\Fontes;

class CrfFgtsFonte extends FonteCertidaoInfoSimples
{
    public function chave(): string
    {
        return 'crf_fgts';
    }

    public function slug(): string
    {
        return 'caixa/regularidade';
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.crf_fgts', 2);
    }

    protected function mapearSucesso(array $data): array
    {
        return [
            'status' => $data['situacao'] ?? ($data['tipo'] ?? null), // Regular / Irregular
            'certidao_codigo' => $data['numero_certificado'] ?? null,
            'emissao_data' => $data['emissao_data'] ?? null,
            'data_validade' => $data['validade_data'] ?? null,
            'conseguiu_emitir' => (bool) ($data['conseguiu_emitir_certidao_negativa'] ?? false),
            'mensagem' => $data['mensagem'] ?? null,
        ];
    }
}
