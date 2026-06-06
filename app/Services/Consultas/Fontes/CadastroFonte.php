<?php

namespace App\Services\Consultas\Fontes;

use App\Services\Consultas\Contracts\Fonte;

class CadastroFonte implements Fonte
{
    public function chave(): string
    {
        return 'cadastro';
    }

    public function fornece(): array
    {
        // Sub-atributos de consultas_incluidas cobertos pelos dados da minhareceita.
        return [
            'situacao_cadastral',
            'dados_cadastrais',
            'endereco',
            'cnaes',
            'cnaes_secundarios',
            'qsa',
            'qsa_detalhado',
            'simples_nacional',
            'mei',
            'capital_social',
            'natureza_juridica',
            'porte',
            'data_inicio_atividade',
            'regime_tributario',
            'historico_simples',
        ];
    }

    public function provider(): string
    {
        return 'minhareceita';
    }

    public function slug(): string
    {
        return ''; // minhareceita monta a URL pelo CNPJ
    }

    public function slugPara(array $alvo): string
    {
        return '';
    }

    public function params(array $alvo): array
    {
        return ['cnpj' => preg_replace('/[^0-9]/', '', (string) ($alvo['cnpj'] ?? ''))];
    }

    public function custoCreditos(): int
    {
        return 0;
    }

    public function pronta(): bool
    {
        return true; // minhareceita: grátis e sempre disponível
    }

    public function aplicavelPara(array $alvo): bool
    {
        return true; // cadastral é nacional
    }

    public function motivoIndisponivel(array $alvo): string
    {
        return 'Cadastro indisponível.';
    }

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        // Cadastro (minhareceita) é sucesso-ou-nada: sem dado em qualquer não-sucesso.
        if ($status !== 'sucesso') {
            return [];
        }

        $qsa = array_map(fn ($s) => [
            'nome' => $s['nome_socio'] ?? null,
            'cpf_cnpj' => $s['cnpj_cpf_do_socio'] ?? null,
            'data_entrada' => $s['data_entrada_sociedade'] ?? null,
            'qualificacao' => $s['qualificacao_socio'] ?? null,
        ], $raw['qsa'] ?? []);

        $cnaes = array_merge(
            isset($raw['cnae_fiscal']) ? [[
                'codigo' => $raw['cnae_fiscal'] ?? null,
                'descricao' => $raw['cnae_fiscal_descricao'] ?? null,
                'principal' => true,
            ]] : [],
            array_map(fn ($c) => [
                'codigo' => $c['codigo'] ?? null,
                'descricao' => $c['descricao'] ?? null,
                'principal' => false,
            ], $raw['cnaes_secundarios'] ?? []),
        );

        return [
            'razao_social' => $raw['razao_social'] ?? null,
            'nome_fantasia' => $raw['nome_fantasia'] ?? null,
            'situacao_cadastral' => $raw['descricao_situacao_cadastral'] ?? null,
            'situacao_cadastral_codigo' => $raw['situacao_cadastral'] ?? null,
            'motivo_situacao_cadastral' => $raw['descricao_motivo_situacao_cadastral'] ?? null,
            'porte' => $raw['porte'] ?? ($raw['descricao_porte'] ?? null),
            'natureza_juridica' => $raw['natureza_juridica'] ?? null,
            'capital_social' => $raw['capital_social'] ?? null,
            'matriz_filial' => ($raw['identificador_matriz_filial'] ?? null) == 1 ? 'matriz' : 'filial',
            'data_inicio_atividade' => $raw['data_inicio_atividade'] ?? null,
            'telefone_1' => $raw['ddd_telefone_1'] ?? null,
            'telefone_2' => $raw['ddd_telefone_2'] ?? null,
            'endereco' => [
                'uf' => $raw['uf'] ?? null,
                'cep' => $raw['cep'] ?? null,
                'bairro' => $raw['bairro'] ?? null,
                'numero' => $raw['numero'] ?? null,
                'municipio' => $raw['municipio'] ?? null,
                'logradouro' => $raw['logradouro'] ?? null,
                'complemento' => $raw['complemento'] ?? null,
                'tipo_logradouro' => $raw['descricao_tipo_de_logradouro'] ?? null,
                'codigo_municipio' => $raw['codigo_municipio_ibge'] ?? null,
            ],
            'cnaes' => $cnaes,
            'qsa' => $qsa,
            'simples_nacional' => (bool) ($raw['opcao_pelo_simples'] ?? false),
            'data_opcao_simples' => $raw['data_opcao_pelo_simples'] ?? null,
            'data_exclusao_simples' => $raw['data_exclusao_do_simples'] ?? null,
            'mei' => (bool) ($raw['opcao_pelo_mei'] ?? false),
            // Derivados do cadastro (minhareceita) — usados pelo plano Validação.
            'regime_tributario' => $this->regimeTributario($raw),
            'historico_simples' => [
                'optante' => (bool) ($raw['opcao_pelo_simples'] ?? false),
                'data_opcao' => $raw['data_opcao_pelo_simples'] ?? null,
                'data_exclusao' => $raw['data_exclusao_do_simples'] ?? null,
                'mei_optante' => (bool) ($raw['opcao_pelo_mei'] ?? false),
                'mei_data_opcao' => $raw['data_opcao_pelo_mei'] ?? null,
                'mei_data_exclusao' => $raw['data_exclusao_do_mei'] ?? null,
            ],
            // Histórico de regime (forma de tributação por ano) que a RFB publica.
            'regime_tributario_historico' => array_map(fn ($r) => [
                'ano' => $r['ano'] ?? null,
                'forma' => $this->humanizarRegime((string) ($r['forma_de_tributacao'] ?? '')),
            ], is_array($raw['regime_tributario'] ?? null) ? $raw['regime_tributario'] : []),
            'consultas_realizadas' => ['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei', 'regime_tributario', 'historico_simples'],
        ];
    }

    /**
     * Regime tributário real (minhareceita):
     * MEI > Simples Nacional > forma de tributação do ano mais recente (Lucro Real/Presumido/
     * Arbitrado, da RFB) > "Não informado" quando a RFB não publica.
     */
    private function regimeTributario(array $raw): string
    {
        if ((bool) ($raw['opcao_pelo_mei'] ?? false)) {
            return 'MEI';
        }
        if ((bool) ($raw['opcao_pelo_simples'] ?? false)) {
            return 'Simples Nacional';
        }

        $hist = is_array($raw['regime_tributario'] ?? null) ? $raw['regime_tributario'] : [];
        if ($hist) {
            usort($hist, fn ($a, $b) => ($b['ano'] ?? 0) <=> ($a['ano'] ?? 0));
            $forma = (string) ($hist[0]['forma_de_tributacao'] ?? '');
            if ($forma !== '') {
                return $this->humanizarRegime($forma);
            }
        }

        return 'Não informado'; // RFB não publica regime p/ este CNPJ (nem Simples/MEI)
    }

    /** "LUCRO REAL" → "Lucro Real". */
    private function humanizarRegime(string $forma): string
    {
        $forma = trim($forma);

        return $forma === '' ? '' : ucwords(mb_strtolower($forma));
    }
}
