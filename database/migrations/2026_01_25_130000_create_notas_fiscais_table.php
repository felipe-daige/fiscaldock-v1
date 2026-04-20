<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('importacao_xml_id')->nullable()->constrained('importacoes_xml')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();

            // Identificação da nota
            $table->string('chave_acesso', 44);
            $table->string('tipo_documento', 10); // NFE, NFSE, CTE
            $table->string('origem', 20)->default('xml_upload'); // xml_upload | busca_avulsa
            $table->integer('numero_nota');
            $table->smallInteger('serie');
            $table->timestamp('data_emissao');
            $table->string('natureza_operacao', 120)->nullable();
            $table->decimal('valor_total', 15, 2);
            $table->smallInteger('tipo_nota'); // 0=entrada, 1=saída
            $table->smallInteger('finalidade')->nullable(); // 1=normal, 2=complementar, 3=ajuste, 4=devolução
            $table->string('chave_referenciada', 44)->nullable(); // Para devoluções

            // Emitente (quem vendeu)
            $table->string('emit_cnpj', 14);
            $table->string('emit_razao_social', 255)->nullable();
            $table->string('emit_uf', 2)->nullable();
            $table->foreignId('emit_participante_id')->nullable()->constrained('participantes')->nullOnDelete();

            // Destinatário (quem comprou)
            $table->string('dest_cnpj', 14);
            $table->string('dest_razao_social', 255)->nullable();
            $table->string('dest_uf', 2)->nullable();
            $table->foreignId('dest_participante_id')->nullable()->constrained('participantes')->nullOnDelete();

            // Tributos resumidos
            $table->decimal('icms_valor', 15, 2)->nullable();
            $table->decimal('icms_st_valor', 15, 2)->nullable();
            $table->decimal('pis_valor', 15, 2)->nullable();
            $table->decimal('cofins_valor', 15, 2)->nullable();
            $table->decimal('ipi_valor', 15, 2)->nullable();
            $table->decimal('tributos_total', 15, 2)->nullable();

            // Payload completo (todo o JSON extraído do XML)
            $table->jsonb('payload')->nullable();

            $table->timestamps();

            // Índices
            $table->unique(['user_id', 'chave_acesso']);
            $table->index('emit_cnpj');
            $table->index('dest_cnpj');
            $table->index('data_emissao');
            $table->index('emit_participante_id');
            $table->index('dest_participante_id');
            $table->index('finalidade'); // Para filtrar devoluções
            $table->index('cliente_id'); // Para filtrar por cliente
            $table->index('origem'); // Para distinguir acervo importado de busca avulsa
            $table->index('tipo_documento'); // Para filtrar NFE/CTE/NFSE
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
    }
};
