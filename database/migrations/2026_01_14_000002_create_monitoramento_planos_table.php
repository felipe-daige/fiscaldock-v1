<?php

use App\Support\Monitoramento\PlanoCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoramento_planos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // basico, cadastral, fiscal_federal, fiscal_completo, due_diligence
            $table->string('nome');
            $table->text('descricao');
            $table->json('consultas_incluidas'); // ["cnpj", "simples", "sintegra", "pgfn", "fgts", ...]
            $table->jsonb('etapas')->nullable(); // [{numero, chave, label}] — granularidade do progresso da consulta
            $table->integer('custo_creditos');
            $table->boolean('is_gratuito')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        DB::table('monitoramento_planos')->insertOrIgnore(
            array_map(function (array $plano) {
                return array_merge($plano, [
                    'consultas_incluidas' => json_encode($plano['consultas_incluidas'], JSON_UNESCAPED_UNICODE),
                    'etapas' => json_encode($plano['etapas'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, PlanoCatalog::definitions())
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoramento_planos');
    }
};
