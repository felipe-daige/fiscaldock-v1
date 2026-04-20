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
        Schema::create('xml_chaves_processadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chave_acesso', 44); // Chave de acesso NF-e/NFS-e/CT-e
            $table->string('tipo_documento', 10); // NFE, NFSE, CTE
            $table->foreignId('importacao_xml_id')->nullable()->constrained('importacoes_xml')->nullOnDelete();
            $table->foreignId('emit_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->foreignId('dest_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            // Chave única por usuário
            $table->unique(['user_id', 'chave_acesso']);
            $table->index(['user_id', 'tipo_documento']);
            $table->index('importacao_xml_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_chaves_processadas');
    }
};
