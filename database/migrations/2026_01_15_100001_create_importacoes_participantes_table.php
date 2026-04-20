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
        Schema::create('importacoes_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_efd', 30); // 'EFD Fiscal' ou 'EFD Contribuições'
            $table->string('filename')->nullable();
            $table->integer('total_participantes')->default(0); // Total de registros no arquivo
            $table->integer('total_cnpjs_unicos')->default(0);  // CNPJs únicos encontrados
            $table->integer('total_cpfs_unicos')->default(0);   // CPFs únicos encontrados
            $table->integer('novos')->default(0);               // Novos inseridos no banco
            $table->integer('duplicados')->default(0);          // Já existiam no banco
            $table->string('status', 20)->default('pendente'); // pendente, processando, concluido, erro
            $table->jsonb('participante_ids')->nullable(); // Array de IDs dos participantes criados
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->integer('tempo_processamento_segundos')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacoes_participantes');
    }
};
