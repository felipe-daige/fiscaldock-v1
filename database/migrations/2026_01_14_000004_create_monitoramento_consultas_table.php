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
        Schema::create('monitoramento_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->foreignId('plano_id')->constrained('monitoramento_planos');
            $table->foreignId('assinatura_id')->nullable()->constrained('monitoramento_assinaturas')->onDelete('set null');
            $table->enum('tipo', ['assinatura', 'avulso']);
            $table->enum('status', ['pendente', 'processando', 'sucesso', 'erro'])->default('pendente');
            $table->json('resultado')->nullable(); // Dados retornados pelo n8n
            $table->enum('situacao_geral', ['regular', 'atencao', 'irregular'])->nullable();
            $table->boolean('tem_pendencias')->default(false);
            $table->date('proxima_validade')->nullable();
            $table->integer('creditos_cobrados')->default(0);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executado_em')->nullable();
            $table->timestamps();

            $table->index('situacao_geral');
            $table->index('tem_pendencias');
            $table->index('proxima_validade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoramento_consultas');
    }
};
