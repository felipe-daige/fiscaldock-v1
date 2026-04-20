<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cte_consultas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('consulta_lote_id')->nullable()->constrained('consulta_lotes')->nullOnDelete();
            $table->foreignId('credit_transaction_id')->nullable()->constrained('credit_transactions')->nullOnDelete();
            $table->string('correlation_id', 60)->nullable();

            $table->string('chave_acesso', 44);
            $table->string('tipo_documento', 10)->default('CTE');
            $table->string('modelo', 2)->nullable();
            $table->string('numero', 20)->nullable();
            $table->smallInteger('serie')->nullable();
            $table->string('data_emissao', 40)->nullable();
            $table->string('status', 20);

            $table->string('natureza_operacao', 200)->nullable();
            $table->string('tipo_servico', 40)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('modal', 20)->nullable();
            $table->string('uf_inicio', 2)->nullable();
            $table->string('uf_fim', 2)->nullable();
            $table->decimal('valor_prestacao', 15, 2)->nullable();
            $table->decimal('valor_carga', 15, 2)->nullable();

            $table->string('emit_cnpj', 14)->nullable();
            $table->string('emit_nome', 255)->nullable();
            $table->string('emit_ie', 50)->nullable();
            $table->string('emit_uf', 2)->nullable();
            $table->string('emit_municipio', 120)->nullable();

            $table->string('tomador_cnpj', 14)->nullable();
            $table->string('tomador_cpf', 11)->nullable();
            $table->string('tomador_nome', 255)->nullable();
            $table->string('tomador_uf', 2)->nullable();
            $table->string('tomador_municipio', 120)->nullable();

            $table->string('remet_cnpj', 14)->nullable();
            $table->string('remet_cpf', 11)->nullable();
            $table->string('remet_nome', 255)->nullable();
            $table->string('remet_uf', 2)->nullable();

            $table->string('dest_cnpj', 14)->nullable();
            $table->string('dest_cpf', 11)->nullable();
            $table->string('dest_nome', 255)->nullable();
            $table->string('dest_uf', 2)->nullable();

            $table->string('expedidor_cnpj', 14)->nullable();
            $table->string('recebedor_cnpj', 14)->nullable();

            $table->integer('nfes_referenciadas_count')->default(0);

            $table->boolean('cte_completa')->default(false);
            $table->boolean('consulta_sem_certificado')->default(false);
            $table->boolean('xml_completo')->default(false);
            $table->string('versao_xml', 10)->nullable();

            $table->text('url_html')->nullable();
            $table->text('url_xml')->nullable();
            $table->text('url_site_receipt')->nullable();

            $table->smallInteger('infosimples_code')->nullable();
            $table->text('infosimples_code_message')->nullable();
            $table->decimal('custo', 10, 4)->default(0);

            $table->string('error_code', 40)->nullable();
            $table->text('error_message')->nullable();

            $table->jsonb('eventos')->nullable();
            $table->jsonb('componentes')->nullable();
            $table->jsonb('nfes_referenciadas')->nullable();
            $table->jsonb('totais')->nullable();
            $table->jsonb('rodoviario')->nullable();
            $table->jsonb('aquaviario')->nullable();
            $table->jsonb('payload')->nullable();

            $table->timestamp('consultado_em')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chave_acesso']);
            $table->index('chave_acesso');
            $table->index('status');
            $table->index('emit_cnpj');
            $table->index('tomador_cnpj');
            $table->index('remet_cnpj');
            $table->index('dest_cnpj');
            $table->index('consultado_em');
            $table->index('uf_inicio');
            $table->index('uf_fim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cte_consultas');
    }
};
