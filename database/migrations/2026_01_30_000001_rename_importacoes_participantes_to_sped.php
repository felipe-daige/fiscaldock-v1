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
        // Rename table importacoes_participantes -> efd_importacoes
        Schema::rename('importacoes_participantes', 'efd_importacoes');

        // Update FK column name in participantes
        Schema::table('participantes', function (Blueprint $table) {
            $table->renameColumn('importacao_participante_id', 'importacao_sped_id');
        });

        // Add columns for nota extraction
        Schema::table('efd_importacoes', function (Blueprint $table) {
            $table->integer('total_notas')->default(0)->after('duplicados');
            $table->integer('notas_extraidas')->default(0)->after('total_notas');
            $table->integer('creditos_cobrados')->default(0)->after('notas_extraidas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new columns first
        Schema::table('efd_importacoes', function (Blueprint $table) {
            $table->dropColumn(['total_notas', 'notas_extraidas', 'creditos_cobrados']);
        });

        // Rename FK column back in participantes
        Schema::table('participantes', function (Blueprint $table) {
            $table->renameColumn('importacao_sped_id', 'importacao_participante_id');
        });

        // Rename table back
        Schema::rename('efd_importacoes', 'importacoes_participantes');
    }
};
