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
        Schema::create('participante_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participante_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Scores individuais (0-100)
            $table->smallInteger('score_cadastral')->default(0);
            $table->smallInteger('score_cnd_federal')->default(0);
            $table->smallInteger('score_cnd_estadual')->default(0);
            $table->smallInteger('score_fgts')->default(0);
            $table->smallInteger('score_trabalhista')->default(0);
            $table->smallInteger('score_compliance')->default(0);  // CEIS/CNEP/TCU
            $table->smallInteger('score_esg')->default(0);         // trabalho escravo, IBAMA
            $table->smallInteger('score_protestos')->default(0);

            // Score consolidado
            $table->smallInteger('score_total')->default(0);
            $table->string('classificacao', 20)->nullable();  // baixo, medio, alto, critico

            // Metadata
            $table->timestamp('ultima_consulta_em')->nullable();
            $table->timestamp('proxima_consulta_em')->nullable();
            $table->jsonb('dados_consultados')->nullable();

            $table->timestamps();

            // Indices
            $table->unique('participante_id');
            $table->index('user_id');
            $table->index('score_total');
            $table->index('classificacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participante_scores');
    }
};
