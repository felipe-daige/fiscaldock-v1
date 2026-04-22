<?php

namespace App\Support\Monitoramento;

final class PlanoCatalog
{
    /**
     * Catálogo canônico dos planos de consulta CNPJ.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'codigo' => 'gratuito',
                'nome' => 'Gratuito',
                'descricao' => 'Cartão de visita do CNPJ: confirma que a empresa existe, está ativa e tem endereço válido',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 0,
                'is_gratuito' => true,
                'is_active' => true,
                'ordem' => 1,
            ],
            [
                'codigo' => 'validacao',
                'nome' => 'Validação',
                'descricao' => 'Raio-X cadastral completo com parecer fiscal automático: regime tributário, histórico no Simples, QSA detalhado, CNAEs secundários e sinalizações para o contador',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                    'cnaes',
                    'cnaes_secundarios',
                    'qsa',
                    'qsa_detalhado',
                    'simples_nacional',
                    'mei',
                    'regime_tributario',
                    'historico_simples',
                    'capital_social',
                    'natureza_juridica',
                    'porte',
                    'data_inicio_atividade',
                    'parecer_fiscal',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 5,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 2,
            ],
            [
                'codigo' => 'licitacao',
                'nome' => 'Licitação',
                'descricao' => 'Validação com CND Federal, CNDT e FGTS para editais e contratos',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                    'cnaes',
                    'qsa',
                    'simples_nacional',
                    'mei',
                    'cnd_federal',
                    'cndt',
                    'crf_fgts',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 3, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 10,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 3,
            ],
            [
                'codigo' => 'compliance',
                'nome' => 'Compliance',
                'descricao' => 'Regularidade fiscal e trabalhista completa',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                    'cnaes',
                    'qsa',
                    'simples_nacional',
                    'mei',
                    'sintegra',
                    'cnd_federal',
                    'crf_fgts',
                    'cndt',
                    'cnd_estadual',
                    'cnd_municipal',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 3, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 4, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 18,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 4,
            ],
            [
                'codigo' => 'due_diligence',
                'nome' => 'Due Diligence',
                'descricao' => 'Compliance ampliado com sanções, CNJ, protestos e processos',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                    'cnaes',
                    'qsa',
                    'simples_nacional',
                    'mei',
                    'sintegra',
                    'cnd_federal',
                    'crf_fgts',
                    'cndt',
                    'cnd_estadual',
                    'cnd_municipal',
                    'cgu_cnc',
                    'cnj_improbidade',
                    'protestos',
                    'processos',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 3, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 4, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                    ['numero' => 5, 'chave' => 'sancoes', 'label' => 'Sanções e Processos'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 35,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 5,
            ],
            [
                'codigo' => 'enterprise',
                'nome' => 'Enterprise',
                'descricao' => 'Raio-X completo do CNPJ, incluindo processos judiciais no CNJ',
                'consultas_incluidas' => [
                    'situacao_cadastral',
                    'dados_cadastrais',
                    'endereco',
                    'cnaes',
                    'qsa',
                    'simples_nacional',
                    'mei',
                    'sintegra',
                    'tcu_consolidada',
                    'cnd_federal',
                    'crf_fgts',
                    'cnd_estadual',
                    'cndt',
                    'protestos',
                    'lista_devedores_pgfn',
                    'trabalho_escravo',
                    'ibama_autuacoes',
                    'processos_cnj',
                ],
                'etapas' => [
                    ['numero' => 1, 'chave' => 'inicializacao', 'label' => 'Preparando consulta'],
                    ['numero' => 2, 'chave' => 'cadastrais', 'label' => 'Dados cadastrais'],
                    ['numero' => 3, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 4, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                    ['numero' => 5, 'chave' => 'sancoes', 'label' => 'Sanções e Processos'],
                    ['numero' => 0, 'chave' => 'finalizacao', 'label' => 'Salvando resultados'],
                ],
                'custo_creditos' => 12,
                'is_gratuito' => false,
                'is_active' => false,
                'ordem' => 99,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forCodigo(?string $codigo): ?array
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }

        foreach (self::definitions() as $definition) {
            if ($definition['codigo'] === $codigo) {
                return $definition;
            }
        }

        return null;
    }
}
