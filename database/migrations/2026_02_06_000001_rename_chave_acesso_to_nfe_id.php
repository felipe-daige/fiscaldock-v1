<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plano B fase 1 (consolidado 2026-05-05; migration realinhada 2026-05-21).
 *
 * Histórico: este arquivo era apenas rename `chave_acesso → nfe_id` em
 * xml_notas e priv_cpf_operacoes. O rename de xml_notas foi descartado e a
 * coluna voltou a se chamar `chave_acesso`. Os demais ALTERs do Plano B
 * (renames `emit_cnpj/dest_cnpj/numero_nota`, novas colunas tipadas) foram
 * aplicados via ALTER TABLE manual em produção e estavam ausentes desta
 * migration — banco de teste ficou divergente. Aqui consolidamos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // priv_cpf_operacoes: mantém rename original
        if (Schema::hasColumn('priv_cpf_operacoes', 'chave_acesso')) {
            Schema::table('priv_cpf_operacoes', fn (Blueprint $t) => $t->renameColumn('chave_acesso', 'nfe_id'));
        }

        // xml_notas — Sub-fase 1a: renames mecânicos
        Schema::table('xml_notas', function (Blueprint $t) {
            $t->renameColumn('emit_cnpj', 'emit_documento');
            $t->renameColumn('dest_cnpj', 'dest_documento');
            $t->renameColumn('numero_nota', 'numero_documento');
        });

        // xml_notas — Sub-fase 1a: type changes
        Schema::table('xml_notas', function (Blueprint $t) {
            $t->bigInteger('numero_documento')->change();
            $t->string('serie', 5)->nullable()->change();
        });

        // xml_notas — Sub-fase 1b: colunas novas (aditivas, nullable)
        Schema::table('xml_notas', function (Blueprint $t) {
            $t->string('id_alternativo', 60)->nullable();
            $t->string('modelo', 8)->nullable();
            $t->string('ambiente', 1)->nullable();
            $t->string('versao_layout', 10)->nullable();
            $t->date('data_competencia')->nullable();
            $t->string('municipio_fato_gerador_ibge', 7)->nullable();
            $t->decimal('valor_desconto', 15, 2)->nullable();
            $t->string('emit_municipio_ibge', 7)->nullable();
            $t->string('emit_ie', 20)->nullable();
            $t->string('emit_im', 20)->nullable();
            $t->string('dest_municipio_ibge', 7)->nullable();
            $t->string('dest_ie', 20)->nullable();
            $t->string('dest_im', 20)->nullable();
            $t->decimal('iss_valor', 15, 2)->nullable();
            $t->decimal('iss_retido_valor', 15, 2)->nullable();
            $t->string('protocolo_autorizacao', 20)->nullable();
            $t->timestamp('data_autorizacao')->nullable();
            $t->string('status_autorizacao', 4)->nullable();
            $t->string('motivo_autorizacao', 255)->nullable();

            $t->index('modelo');
            $t->index('status_autorizacao');
        });
    }

    public function down(): void
    {
        Schema::table('xml_notas', function (Blueprint $t) {
            $t->dropIndex(['status_autorizacao']);
            $t->dropIndex(['modelo']);
            $t->dropColumn([
                'id_alternativo', 'modelo', 'ambiente', 'versao_layout', 'data_competencia',
                'municipio_fato_gerador_ibge', 'valor_desconto',
                'emit_municipio_ibge', 'emit_ie', 'emit_im',
                'dest_municipio_ibge', 'dest_ie', 'dest_im',
                'iss_valor', 'iss_retido_valor',
                'protocolo_autorizacao', 'data_autorizacao', 'status_autorizacao', 'motivo_autorizacao',
            ]);
        });

        Schema::table('xml_notas', function (Blueprint $t) {
            $t->renameColumn('emit_documento', 'emit_cnpj');
            $t->renameColumn('dest_documento', 'dest_cnpj');
            $t->renameColumn('numero_documento', 'numero_nota');
        });

        if (Schema::hasColumn('priv_cpf_operacoes', 'nfe_id')) {
            Schema::table('priv_cpf_operacoes', fn (Blueprint $t) => $t->renameColumn('nfe_id', 'chave_acesso'));
        }
    }
};
