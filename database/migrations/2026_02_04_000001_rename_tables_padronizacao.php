<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Padroniza nomes de tabelas usando prefixo por origem (xml_*, efd_*).
     *
     * - importacoes_xml → xml_importacoes
     * - notas_fiscais → xml_notas
     * - importacoes_sped → efd_importacoes
     *
     * Nota: notas_sped foi substituída por efd_notas (create_notas_sped_table.php).
     */
    public function up(): void
    {
        Schema::rename('importacoes_xml', 'xml_importacoes');
        Schema::rename('notas_fiscais', 'xml_notas');
        // efd_importacoes já renomeado na migration 000001

        Schema::table('participantes', function ($table) {
            $table->renameColumn('importacao_sped_id', 'importacao_efd_id');
        });
    }

    public function down(): void
    {
        Schema::table('participantes', function ($table) {
            $table->renameColumn('importacao_efd_id', 'importacao_sped_id');
        });

        Schema::rename('xml_importacoes', 'importacoes_xml');
        Schema::rename('xml_notas', 'notas_fiscais');
        // efd_importacoes revert handled by migration 000001
    }
};
