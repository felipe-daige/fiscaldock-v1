<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('efd_importacoes', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete()->after('user_id');
            $table->jsonb('resumo_final')->nullable()->after('cliente_id');
        });

        // Adiciona coluna origem_arquivo em efd_notas (idempotente)
        if (!Schema::hasColumn('efd_notas', 'origem_arquivo')) {
            Schema::table('efd_notas', function (Blueprint $table) {
                $table->string('origem_arquivo')->nullable()->after('tipo_operacao');
            });
        }

        // Backfill: determina origem_arquivo a partir do tipo_efd da importação
        // Postgresql syntax
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE efd_notas n
                SET origem_arquivo = CASE
                    WHEN imp.tipo_efd = 'EFD PIS/COFINS' THEN 'contribuicoes'
                    ELSE 'fiscal'
                END
                FROM efd_importacoes imp
                WHERE imp.id = n.importacao_id
                AND n.origem_arquivo IS NULL
            ");
        } else {
            // SQLite fallback: simple update without join
            DB::statement("
                UPDATE efd_notas
                SET origem_arquivo = 'fiscal'
                WHERE origem_arquivo IS NULL
            ");
        }

        // Cria tabela alertas (idempotente)
        if (!Schema::hasTable('alertas')) {
            Schema::create('alertas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
                $table->foreignId('participante_id')->nullable()->constrained('participantes')->nullOnDelete();
                $table->unsignedBigInteger('importacao_id')->nullable();
                $table->string('tipo', 50);
                $table->string('categoria', 30);
                $table->string('severidade', 10);
                $table->string('titulo', 255);
                $table->text('descricao');
                $table->integer('total_afetados')->default(0);
                $table->jsonb('detalhes')->nullable();
                $table->string('status', 20)->default('ativo');
                $table->smallInteger('prioridade')->default(0);
                $table->text('notas')->nullable();
                $table->timestamp('notificado_em')->nullable();
                $table->timestamp('visto_em')->nullable();
                $table->timestamp('resolvido_em')->nullable();
                $table->string('hash', 64);
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'categoria']);
                $table->index(['user_id', 'severidade']);
                $table->index('cliente_id');
                $table->unique(['user_id', 'hash']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
        if (Schema::hasColumn('efd_notas', 'origem_arquivo')) {
            Schema::table('efd_notas', function (Blueprint $table) {
                $table->dropColumn('origem_arquivo');
            });
        }
        if (Schema::hasColumn('efd_importacoes', 'resumo_final')) {
            Schema::table('efd_importacoes', function (Blueprint $table) {
                $table->dropColumn('resumo_final');
            });
        }
        if (Schema::hasColumn('efd_importacoes', 'cliente_id')) {
            Schema::table('efd_importacoes', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
                $table->dropColumn('cliente_id');
            });
        }
    }
};
