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
        Schema::create('priv_cpf_cadastro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('cpf', 11)->unique();
            $table->string('nome', 255)->nullable();
            $table->string('cod_pais', 5)->default('1058')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('codigo_municipal', 7)->nullable();
            $table->string('municipio_nome', 100)->nullable();
            $table->string('cep', 8)->nullable();
            $table->string('bairro', 60)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('complemento', 60)->nullable();
            $table->string('inscricao_estadual', 20)->nullable();
            $table->string('suframa', 9)->nullable();
            $table->timestamps();

            // Índices
            $table->index('uf');
            $table->index('codigo_municipal');
            $table->index('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priv_cpf_cadastro');
    }
};

