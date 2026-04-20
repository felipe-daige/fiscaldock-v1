<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('priv_cpf_relacionamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpf_id')->constrained('priv_cpf_cadastro')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('cnpj', 14);
            $table->string('razao_social', 255)->nullable();
            $table->string('tipo_relacao', 20);
            $table->integer('total_operacoes')->default(0);
            $table->decimal('valor_total', 18, 2)->default(0);
            $table->date('primeira_operacao')->nullable();
            $table->date('ultima_operacao')->nullable();
            $table->timestamps();

            // Constraint única composta
            $table->unique(['cpf_id', 'cnpj', 'tipo_relacao'], 'idx_rel_unique');

            // Índices
            $table->index('cpf_id');
            $table->index('cnpj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priv_cpf_relacionamentos');
    }
};



