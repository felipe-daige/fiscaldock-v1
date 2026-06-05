<?php

namespace App\Services\Consultas\Fontes;

class CndFederalFonte extends FonteCertidaoInfoSimples
{
    public function chave(): string
    {
        return 'cnd_federal';
    }

    public function slug(): string
    {
        return 'receita-federal/pgfn';
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cnd_federal', 2);
    }

    public function params(array $alvo): array
    {
        return parent::params($alvo) + ['preferencia_emissao' => '2via'];
    }

    protected function mapearSucesso(array $data): array
    {
        return [
            // status = `tipo` da certidão (Negativa / Positiva com efeitos de negativa / Positiva).
            // Lido por ConsultaController via strtoupper(). Ver docs/compliance/infosimples/cnd-federal.md.
            'status' => $data['tipo'] ?? null,
            'certidao_codigo' => $data['certidao_codigo'] ?? null,
            'emissao_data' => $data['emissao_data'] ?? null,
            'data_validade' => $data['validade_data'] ?? ($data['validade'] ?? null),
            'conseguiu_emitir' => (bool) ($data['conseguiu_emitir_certidao_negativa'] ?? false),
            'debitos_pgfn' => (bool) ($data['debitos_pgfn'] ?? false),
            'debitos_rfb' => (bool) ($data['debitos_rfb'] ?? false),
            'mensagem' => $data['mensagem'] ?? null,
            'situacao' => $data['situacao'] ?? null,
        ];
    }
}
