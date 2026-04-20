<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove tabela redundante xml_chaves_processadas.
     *
     * Motivos da eliminação:
     * - 100% dos dados são duplicados com xml_notas
     * - Deduplicação pode ser feita diretamente em xml_notas via chave_acesso
     * - Simplifica n8n workflows (1 INSERT ao invés de 2)
     * - Menos manutenção no código
     *
     * Campos que existiam em xml_chaves_processadas (todos em xml_notas):
     * - user_id ✓
     * - chave_acesso ✓
     * - tipo_documento ✓
     * - importacao_xml_id ✓
     * - emit_participante_id ✓
     * - dest_participante_id ✓
     * - processado_em → equivale a created_at
     */
    public function up(): void
    {
        Schema::dropIfExists('xml_chaves_processadas');
    }

    /**
     * Reverse the migrations.
     *
     * Recria a tabela se necessário para rollback.
     * Dados não serão restaurados - use backup se necessário.
     */
    public function down(): void
    {
        Schema::create('xml_chaves_processadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('chave_acesso', 44);
            $table->string('tipo_documento', 10)->default('NFE');
            $table->foreignId('importacao_xml_id')->nullable()->constrained('xml_importacoes')->nullOnDelete();
            $table->foreignId('emit_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->foreignId('dest_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            // Unique constraint para deduplicação
            $table->unique(['user_id', 'chave_acesso']);
        });
    }
};
