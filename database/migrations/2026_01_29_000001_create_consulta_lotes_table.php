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
        Schema::create('consulta_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('plano_id')->nullable()->constrained('monitoramento_planos')->onDelete('restrict');
            $table->string('status', 20)->default('pendente'); // pendente, processando, concluido, erro
            $table->integer('total_participantes');
            $table->integer('creditos_cobrados')->default(0);
            $table->string('tab_id', 36)->nullable();
            $table->jsonb('resultado_resumo')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('tab_id');
        });

        Schema::create('consulta_lote_participantes', function (Blueprint $table) {
            $table->foreignId('consulta_lote_id')->constrained('consulta_lotes')->onDelete('cascade');
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['consulta_lote_id', 'participante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulta_lote_participantes');
        Schema::dropIfExists('consulta_lotes');
    }
};
