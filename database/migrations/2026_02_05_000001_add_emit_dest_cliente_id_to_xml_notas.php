<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_notas', function (Blueprint $table) {
            $table->foreignId('emit_cliente_id')
                ->nullable()
                ->after('emit_participante_id')
                ->constrained('clientes')
                ->nullOnDelete();

            $table->foreignId('dest_cliente_id')
                ->nullable()
                ->after('dest_participante_id')
                ->constrained('clientes')
                ->nullOnDelete();

            // Snapshot SEFAZ (acervo único xml_notas — busca avulsa e clearance em lote
            // gravam aqui via upsert por (user_id, nfe_id), preservando dados do contador).
            $table->foreignId('consulta_lote_id')
                ->nullable()
                ->constrained('consulta_lotes')
                ->nullOnDelete();
            $table->string('situacao_sefaz', 30)->nullable();
            $table->timestamp('verificado_sefaz_em')->nullable();

            // Resumo persistido da comparação declarado vs SEFAZ.
            // Populado por XmlNotaSefazSyncObserver quando situacao_sefaz/verificado_sefaz_em mudam.
            // Permite listagem/dashboard sem rodar ComparacaoNotaService linha a linha.
            $table->string('divergencia_severidade', 16)->nullable();   // OK | REVISAR | CRITICA
            $table->unsignedSmallInteger('divergencia_count')->nullable();
            $table->jsonb('divergencia_resumo')->nullable();
            $table->timestamp('comparado_em')->nullable();

            $table->index('situacao_sefaz');
            $table->index('consulta_lote_id');
            $table->index('divergencia_severidade');
        });

        // Backfill from participantes.cliente_id
        DB::statement('
            UPDATE xml_notas SET emit_cliente_id = p.cliente_id
            FROM participantes p
            WHERE xml_notas.emit_participante_id = p.id
              AND p.cliente_id IS NOT NULL
        ');

        DB::statement('
            UPDATE xml_notas SET dest_cliente_id = p.cliente_id
            FROM participantes p
            WHERE xml_notas.dest_participante_id = p.id
              AND p.cliente_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('xml_notas', function (Blueprint $table) {
            $table->dropIndex(['situacao_sefaz']);
            $table->dropIndex(['consulta_lote_id']);
            $table->dropIndex(['divergencia_severidade']);
            $table->dropColumn([
                'situacao_sefaz',
                'verificado_sefaz_em',
                'divergencia_severidade',
                'divergencia_count',
                'divergencia_resumo',
                'comparado_em',
            ]);
            $table->dropConstrainedForeignId('consulta_lote_id');
            $table->dropConstrainedForeignId('emit_cliente_id');
            $table->dropConstrainedForeignId('dest_cliente_id');
        });
    }
};
