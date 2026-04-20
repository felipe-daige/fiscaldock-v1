<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'empresa')) {
                $table->string('empresa', 255)->nullable()->after('telefone');
            }
            if (! Schema::hasColumn('users', 'cargo')) {
                $table->string('cargo', 255)->nullable()->after('empresa');
            }
            if (! Schema::hasColumn('users', 'cnpj')) {
                $table->string('cnpj', 18)->nullable()->after('cargo');
            }
            if (! Schema::hasColumn('users', 'faturamento_anual')) {
                $table->string('faturamento_anual', 50)->nullable()->after('cnpj');
            }
            if (! Schema::hasColumn('users', 'desafio_principal')) {
                $table->string('desafio_principal', 100)->nullable()->after('faturamento_anual');
            }
        });

        // Backfill apenas no Postgres (UPDATE...FROM não é suportado em SQLite)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE users
                SET empresa = c.nome,
                    cnpj = c.documento,
                    faturamento_anual = c.faturamento_anual
                FROM clientes c
                WHERE c.user_id = users.id
                  AND c.is_empresa_propria = true
            ");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['empresa', 'cargo', 'cnpj', 'faturamento_anual', 'desafio_principal']);
        });
    }
};
