<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the consulta_resultados table to store individual consultation
     * results per participante. This enables on-demand report generation
     * (CSV/PDF) in Laravel instead of receiving pre-generated CSV from n8n.
     */
    public function up(): void
    {
        Schema::create('consulta_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_lote_id')->constrained('consulta_lotes')->cascadeOnDelete();
            $table->foreignId('participante_id')->constrained('participantes')->cascadeOnDelete();
            $table->jsonb('resultado_dados')->nullable(); // All consultation data unified
            $table->string('status', 20)->default('pendente'); // pendente, sucesso, erro, timeout
            $table->text('error_message')->nullable();
            $table->timestamp('consultado_em')->nullable();
            $table->timestamps();

            // Each participante can only appear once per lote
            $table->unique(['consulta_lote_id', 'participante_id']);

            // Index for filtering by status within a lote
            $table->index(['consulta_lote_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulta_resultados');
    }
};
