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

        // xml_notas_itens — espelha efd_notas_itens (+ extras NF-e) para UNION read-side.
        // Tabela nova consolidada nesta migration de Plano B (regra: não criar migration nova).
        // ATENÇÃO: em prod, aplicar CREATE TABLE manualmente (editar migration rodada não aplica delta).
        if (! Schema::hasTable('xml_notas_itens')) {
            Schema::create('xml_notas_itens', function (Blueprint $t) {
                $t->id();
                $t->foreignId('xml_nota_id')->constrained('xml_notas')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->integer('numero_item');                 // det@nItem
                $t->string('codigo_item', 60)->nullable();  // cProd
                $t->string('descricao', 255)->nullable();   // xProd
                $t->decimal('quantidade', 15, 4)->nullable();
                $t->string('unidade_medida', 10)->nullable();
                $t->decimal('valor_unitario', 21, 10)->nullable();
                $t->decimal('valor_total', 15, 2)->nullable();
                $t->string('cfop', 5)->nullable();
                $t->string('cst_icms', 4)->nullable();
                $t->decimal('aliquota_icms', 7, 4)->nullable();
                $t->decimal('valor_icms', 15, 2)->nullable();
                $t->string('cst_pis', 4)->nullable();
                $t->decimal('aliquota_pis', 9, 4)->nullable();
                $t->decimal('valor_pis', 15, 2)->nullable();
                $t->string('cst_cofins', 4)->nullable();
                $t->decimal('aliquota_cofins', 9, 4)->nullable();
                $t->decimal('valor_cofins', 15, 2)->nullable();
                // Extras NF-e (no EFD vêm do catálogo 0200)
                $t->string('ncm', 8)->nullable();
                $t->string('cest', 7)->nullable();
                $t->string('ean', 14)->nullable();
                $t->string('origem_mercadoria', 1)->nullable(); // ICMS/orig
                $t->string('cst_ipi', 2)->nullable();
                $t->decimal('valor_ipi', 15, 2)->nullable();
                $t->jsonb('metadados')->nullable();
                $t->timestamps();

                $t->index(['user_id', 'codigo_item']);
                $t->index('xml_nota_id');
                $t->index('ncm');
                $t->index('cfop');
            });
        }
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
