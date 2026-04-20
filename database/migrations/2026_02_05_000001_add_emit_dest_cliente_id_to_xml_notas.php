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
            $table->dropConstrainedForeignId('emit_cliente_id');
            $table->dropConstrainedForeignId('dest_cliente_id');
        });
    }
};
