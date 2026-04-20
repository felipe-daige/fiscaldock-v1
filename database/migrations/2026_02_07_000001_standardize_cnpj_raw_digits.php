<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill usa REGEXP_REPLACE / operador ~ (PG only)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE clientes
                SET documento = REGEXP_REPLACE(documento, '[^0-9]', '', 'g')
                WHERE documento ~ '[^0-9]'
            ");

            DB::statement("
                UPDATE users
                SET cnpj = REGEXP_REPLACE(cnpj, '[^0-9]', '', 'g')
                WHERE cnpj IS NOT NULL AND cnpj ~ '[^0-9]'
            ");
        }

        // Reduzir coluna de VARCHAR(18) para VARCHAR(14)
        Schema::table('users', function (Blueprint $table) {
            $table->string('cnpj', 14)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cnpj', 18)->nullable()->change();
        });
    }
};
