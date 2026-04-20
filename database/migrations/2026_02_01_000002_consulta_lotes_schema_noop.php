<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * No-op: this migration was superseded by the direct creation of
     * consulta_lotes, consulta_lote_participantes, and consulta_resultados
     * with the correct nomenclature in the earlier migrations.
     */
    public function up(): void
    {
        // No-op: tables already created with correct names
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
