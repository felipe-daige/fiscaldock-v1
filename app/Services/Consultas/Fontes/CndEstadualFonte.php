<?php

namespace App\Services\Consultas\Fontes;

class CndEstadualFonte extends FonteCertidaoInfoSimples
{
    public function chave(): string
    {
        return 'cnd_estadual';
    }

    public function slug(): string
    {
        return 'sefaz/certidao-debitos'; // fallback; o slug real é por-UF (slugPara)
    }

    public function slugPara(array $alvo): string
    {
        // Doc InfoSimples: endpoint por UF (sefaz/{uf}/certidao-debitos) p/ as 27 UFs.
        $uf = strtolower((string) ($alvo['uf'] ?? ''));

        return $uf !== '' ? "sefaz/{$uf}/certidao-debitos" : $this->slug();
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cnd_estadual', 2);
    }

    public function aplicavelPara(array $alvo): bool
    {
        // Cobertura InfoSimples por UF é parcial — só aplica quando a UF do alvo está
        // na lista coberta (config/consultas.php → cnd_estadual.ufs_cobertas). UF vazia
        // ou fora da lista → pula (sem chamar/cobrar).
        $uf = strtoupper((string) ($alvo['uf'] ?? ''));
        $cobertas = (array) config('consultas.cnd_estadual.ufs_cobertas', []);

        return $uf !== '' && in_array($uf, $cobertas, true);
    }

    public function motivoIndisponivel(array $alvo): string
    {
        $uf = strtoupper((string) ($alvo['uf'] ?? ''));

        return $uf === ''
            ? 'CND Estadual não consultada: UF do contribuinte não identificada.'
            : "CND Estadual não disponível para a UF {$uf} no provedor (InfoSimples).";
    }

    public function params(array $alvo): array
    {
        // SEFAZ exige a UF do domicílio do participante.
        return parent::params($alvo) + ['uf' => strtoupper((string) ($alvo['uf'] ?? ''))];
    }

    protected function mapearSucesso(array $data): array
    {
        return [
            'uf' => $data['uf'] ?? null,
            'status' => $data['tipo'] ?? null, // Negativa / Positiva com efeitos / Positiva
            'certidao_codigo' => $data['certidao_codigo'] ?? null,
            'emissao_data' => $data['emissao_data'] ?? null,
            'data_validade' => $data['validade_data'] ?? ($data['validade'] ?? null),
            'conseguiu_emitir' => (bool) ($data['conseguiu_emitir_certidao_negativa'] ?? false),
            'mensagem' => $data['mensagem'] ?? null,
        ];
    }
}
