<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Dados complementares
            $table->string('nome_fantasia', 255)->nullable()->after('razao_social');
            $table->string('inscricao_estadual', 20)->nullable()->after('nome_fantasia');
            $table->smallInteger('crt')->nullable()->after('inscricao_estadual');

            // Endereco expandido
            $table->string('endereco', 255)->nullable()->after('municipio');
            $table->string('numero', 20)->nullable()->after('endereco');
            $table->string('complemento', 100)->nullable()->after('numero');
            $table->string('bairro', 100)->nullable()->after('complemento');

            // Dados Receita Federal
            $table->string('situacao_cadastral', 50)->nullable()->after('bairro');
            $table->string('regime_tributario', 50)->nullable()->after('situacao_cadastral');
            $table->string('cnpj_matriz', 14)->nullable()->after('regime_tributario');
            $table->string('suframa', 20)->nullable()->after('cnpj_matriz');
            $table->string('codigo_municipal', 10)->nullable()->after('suframa');
            $table->decimal('capital_social', 15, 2)->nullable()->after('codigo_municipal');
            $table->string('natureza_juridica', 100)->nullable()->after('capital_social');
            $table->string('porte', 50)->nullable()->after('natureza_juridica');
            $table->date('data_inicio_atividade')->nullable()->after('porte');
            $table->string('cnae_principal', 10)->nullable()->after('data_inicio_atividade');
            $table->string('cnae_principal_descricao', 255)->nullable()->after('cnae_principal');
            $table->jsonb('cnaes_secundarios')->nullable()->after('cnae_principal_descricao');
            $table->jsonb('qsa')->nullable()->after('cnaes_secundarios');
            $table->jsonb('origem_ref')->nullable()->after('qsa');
        });

        // Indexes (separate call)
        Schema::table('clientes', function (Blueprint $table) {
            $table->index('cnpj_matriz');
            $table->index('cnae_principal');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['cnpj_matriz']);
            $table->dropIndex(['cnae_principal']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'nome_fantasia',
                'inscricao_estadual',
                'crt',
                'endereco',
                'numero',
                'complemento',
                'bairro',
                'situacao_cadastral',
                'regime_tributario',
                'cnpj_matriz',
                'suframa',
                'codigo_municipal',
                'capital_social',
                'natureza_juridica',
                'porte',
                'data_inicio_atividade',
                'cnae_principal',
                'cnae_principal_descricao',
                'cnaes_secundarios',
                'qsa',
                'origem_ref',
            ]);
        });
    }
};
