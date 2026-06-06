<?php

namespace App\Services\Consultas\Fontes;

class CndMunicipalFonte extends FonteCertidaoInfoSimples
{
    public function chave(): string
    {
        return 'cnd_municipal';
    }

    public function slug(): string
    {
        return ''; // dinâmico por UF/cidade — ver slugPara()
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cnd_municipal', 2);
    }

    public function slugPara(array $alvo): string
    {
        return $this->resolverSlug($alvo) ?? '';
    }

    public function aplicavelPara(array $alvo): bool
    {
        // Cobertura InfoSimples por município é parcial — só aplica quando há slug mapeado
        // para a (UF, cidade) do alvo. A cidade vem do cadastro (minhareceita).
        return $this->resolverSlug($alvo) !== null;
    }

    public function motivoIndisponivel(array $alvo): string
    {
        $cidade = trim((string) ($alvo['municipio'] ?? ''));
        $uf = strtoupper((string) ($alvo['uf'] ?? ''));

        if ($cidade === '' || $uf === '') {
            return 'CND Municipal não consultada: município/UF do contribuinte não identificado.';
        }

        return "CND Municipal não disponível para {$cidade}/{$uf} no provedor (InfoSimples).";
    }

    public function params(array $alvo): array
    {
        // UF/cidade já estão na URL (slug); o provider só precisa do CNPJ.
        return parent::params($alvo) + [
            'uf' => strtoupper((string) ($alvo['uf'] ?? '')),
            'municipio' => (string) ($alvo['municipio'] ?? ''),
        ];
    }

    protected function mapearSucesso(array $data): array
    {
        return [
            'uf' => $data['uf'] ?? null,
            'municipio' => $data['municipio'] ?? ($data['cidade'] ?? null),
            'status' => $this->statusCertidao($data),
            'certidao_codigo' => $data['certidao_codigo'] ?? null,
            'emissao_data' => $data['emissao_data'] ?? null,
            'data_validade' => $data['validade_data'] ?? ($data['validade'] ?? null),
            'conseguiu_emitir' => (bool) ($data['conseguiu_emitir_certidao_negativa'] ?? false),
            'mensagem' => $data['mensagem'] ?? null,
        ];
    }

    private function resolverSlug(array $alvo): ?string
    {
        $uf = strtolower(trim((string) ($alvo['uf'] ?? '')));
        $cidade = static::normalizarCidade((string) ($alvo['municipio'] ?? ''));

        if ($uf === '' || $cidade === '') {
            return null;
        }

        return config('consultas.cnd_municipal.slugs')[$uf.':'.$cidade] ?? null;
    }

    /** "RIO DE JANEIRO" → "rio-de-janeiro" (lowercase, sem acento, espaços→hífen). */
    public static function normalizarCidade(string $cidade): string
    {
        $cidade = trim($cidade);
        if ($cidade === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $cidade);
        $cidade = $ascii !== false ? $ascii : $cidade;
        $cidade = strtolower($cidade);
        $cidade = preg_replace('/[^a-z0-9]+/', '-', $cidade);

        return trim((string) $cidade, '-');
    }
}
