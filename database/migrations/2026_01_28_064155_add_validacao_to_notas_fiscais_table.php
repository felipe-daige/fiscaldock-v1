<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table) {
            // JSONB field for storing validation results from VCI (Validacao Contabil Inteligente)
            $table->jsonb('validacao')->nullable()->after('payload');
        });

        // Create partial index for querying validated notes (PostgreSQL)
        DB::statement('CREATE INDEX idx_notas_fiscais_validadas ON notas_fiscais (id) WHERE validacao IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_notas_fiscais_validadas');

        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->dropColumn('validacao');
        });
    }
};
