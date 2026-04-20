<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Receita Federal fields to participantes table for storing
     * data from Minha Receita API (capital social, CNAEs, QSA, etc.).
     */
    public function up(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            // Scalar fields (indexable)
            $table->decimal('capital_social', 15, 2)->nullable()->after('crt');
            $table->string('natureza_juridica', 100)->nullable()->after('capital_social');
            $table->string('porte', 50)->nullable()->after('natureza_juridica');
            $table->date('data_inicio_atividade')->nullable()->after('porte');
            $table->string('cnae_principal', 10)->nullable()->after('data_inicio_atividade');
            $table->string('cnae_principal_descricao', 255)->nullable()->after('cnae_principal');

            // jsonb: n8n passa arrays nativos para colunas jsonb sem stringify
            $table->jsonb('cnaes_secundarios')->nullable()->after('cnae_principal_descricao');
            $table->jsonb('qsa')->nullable()->after('cnaes_secundarios');

            // Indexes for common query patterns
            $table->index('cnae_principal');
            $table->index('porte');
        });

}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            $table->dropIndex(['cnae_principal']);
            $table->dropIndex(['porte']);

            $table->dropColumn([
                'capital_social',
                'natureza_juridica',
                'porte',
                'data_inicio_atividade',
                'cnae_principal',
                'cnae_principal_descricao',
                'cnaes_secundarios',
                'qsa',
            ]);
        });
    }
};
