<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eixo CRÉDITO IBS/CBS (Reforma Tributária), ortogonal ao score de conformidade.
     * 0 = fornecedor gera crédito integral; 100 = não gera; null = regime não identificado.
     * Aditiva e nullable — não toca dados existentes. Ver docs/score-fiscal/credito-reforma.md.
     */
    public function up(): void
    {
        Schema::table('participante_scores', function (Blueprint $table) {
            $table->smallInteger('score_credito_reforma')->nullable()->after('score_total');
        });
    }

    public function down(): void
    {
        Schema::table('participante_scores', function (Blueprint $table) {
            $table->dropColumn('score_credito_reforma');
        });
    }
};
