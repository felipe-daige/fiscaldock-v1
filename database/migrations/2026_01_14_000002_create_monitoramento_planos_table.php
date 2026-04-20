<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoramento_planos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // basico, cadastral, fiscal_federal, fiscal_completo, due_diligence
            $table->string('nome');
            $table->text('descricao');
            $table->json('consultas_incluidas'); // ["cnpj", "simples", "sintegra", "pgfn", "fgts", ...]
            $table->jsonb('etapas')->nullable(); // [{numero, chave, label}] — granularidade do progresso da consulta
            $table->integer('custo_creditos');
            $table->boolean('is_gratuito')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        DB::table('monitoramento_planos')->insertOrIgnore([
            [
                'codigo' => 'gratuito',
                'nome' => 'Gratuito',
                'descricao' => 'Consulta instantânea de situação cadastral, dados completos e quadro societário',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                ]),
                'custo_creditos' => 0,
                'is_gratuito' => true,
                'is_active' => true,
                'ordem' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'validacao',
                'nome' => 'Validação',
                'descricao' => 'Dados cadastrais completos com verificação de Simples Nacional e MEI',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                ]),
                'custo_creditos' => 2,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'licitacao',
                'nome' => 'Licitação',
                'descricao' => 'Validação completa com CND Federal para editais e contratos públicos',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei', 'cnd_federal']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                    ['numero' => 2, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                ]),
                'custo_creditos' => 3,
                'is_gratuito' => false,
                'is_active' => true,
                'ordem' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'compliance',
                'nome' => 'Compliance',
                'descricao' => 'Análise completa de risco financeiro com protestos e dívida ativa na PGFN',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei', 'sintegra', 'tcu_consolidada', 'cnd_federal', 'crf_fgts', 'cnd_estadual', 'cndt', 'protestos', 'lista_devedores_pgfn']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                    ['numero' => 2, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 3, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                ]),
                'custo_creditos' => 9,
                'is_gratuito' => false,
                'is_active' => false,
                'ordem' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'due_diligence',
                'nome' => 'Due Diligence',
                'descricao' => 'Investigação aprofundada com compliance trabalhista e ambiental (ESG)',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei', 'sintegra', 'tcu_consolidada', 'cnd_federal', 'crf_fgts', 'cnd_estadual', 'cndt', 'protestos', 'lista_devedores_pgfn', 'trabalho_escravo', 'ibama_autuacoes']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                    ['numero' => 2, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 3, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                    ['numero' => 4, 'chave' => 'sancoes', 'label' => 'Sanções e Processos'],
                ]),
                'custo_creditos' => 11,
                'is_gratuito' => false,
                'is_active' => false,
                'ordem' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'enterprise',
                'nome' => 'Enterprise',
                'descricao' => 'Raio-X completo do CNPJ, incluindo processos judiciais no CNJ',
                'consultas_incluidas' => json_encode(['situacao_cadastral', 'dados_cadastrais', 'endereco', 'cnaes', 'qsa', 'simples_nacional', 'mei', 'sintegra', 'tcu_consolidada', 'cnd_federal', 'crf_fgts', 'cnd_estadual', 'cndt', 'protestos', 'lista_devedores_pgfn', 'trabalho_escravo', 'ibama_autuacoes', 'processos_cnj']),
                'etapas' => json_encode([
                    ['numero' => 1, 'chave' => 'cadastrais', 'label' => 'Cadastrais'],
                    ['numero' => 2, 'chave' => 'certidoes_federais', 'label' => 'Certidões Federais'],
                    ['numero' => 3, 'chave' => 'certidoes_estaduais', 'label' => 'Certidões Estaduais/Municipais'],
                    ['numero' => 4, 'chave' => 'sancoes', 'label' => 'Sanções e Processos'],
                ]),
                'custo_creditos' => 12,
                'is_gratuito' => false,
                'is_active' => false,
                'ordem' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoramento_planos');
    }
};
