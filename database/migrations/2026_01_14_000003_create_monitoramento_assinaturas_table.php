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
        Schema::create('monitoramento_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->foreignId('plano_id')->constrained('monitoramento_planos');
            $table->enum('status', ['ativo', 'pausado', 'cancelado'])->default('ativo');
            $table->integer('frequencia_dias')->default(30); // 30 = mensal
            $table->timestamp('proxima_execucao_em')->nullable();
            $table->timestamp('ultima_execucao_em')->nullable();
            $table->timestamps();

            $table->unique(['participante_id', 'plano_id']); // Um participante só pode ter uma assinatura por plano
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoramento_assinaturas');
    }
};
