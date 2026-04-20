<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            if (! Schema::hasColumn('participantes', 'tipo_documento')) {
                $table->string('tipo_documento', 2)->default('PJ')->after('cnpj');
            }
        });

        // Alinha o schema com o model (fillable usa "documento", não "cnpj").
        // Em produção o rename já foi feito manualmente; em bancos novos/teste precisamos aplicar.
        if (Schema::hasColumn('participantes', 'cnpj') && ! Schema::hasColumn('participantes', 'documento')) {
            Schema::table('participantes', function (Blueprint $table) {
                $table->renameColumn('cnpj', 'documento');
            });
        }
    }

    public function down(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            if (Schema::hasColumn('participantes', 'tipo_documento')) {
                $table->dropColumn('tipo_documento');
            }
        });
    }
};
