<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy tables
        Schema::dropIfExists('clientes_solicitacoes');
        Schema::dropIfExists('clientes_funcionarios');
        Schema::dropIfExists('clientes_enderecos');

        // Add new columns and drop legacy columns on clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('uf', 2)->nullable()->after('email');
            $table->string('cep', 9)->nullable()->after('uf');
            $table->string('municipio', 255)->nullable()->after('cep');
            $table->string('origem_tipo', 30)->nullable()->after('municipio');
        });

        // Drop legacy columns (separate call to avoid issues)
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['faturamento_anual', 'preparacao_reforma']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('faturamento_anual')->nullable();
            $table->string('preparacao_reforma')->nullable();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['uf', 'cep', 'municipio', 'origem_tipo']);
        });

        // Recreate legacy tables
        Schema::create('clientes_enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('tipo')->default('principal');
            $table->string('cep', 10);
            $table->string('logradouro');
            $table->string('numero', 20);
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado', 2);
            $table->string('pais', 100)->default('Brasil');
            $table->timestamps();
        });

        Schema::create('clientes_funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nome');
            $table->string('sobrenome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('cargo');
            $table->string('departamento')->nullable();
            $table->enum('nivel_acesso', ['funcionario', 'admin'])->default('funcionario');
            $table->foreignId('criado_por')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('clientes_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo');
            $table->text('descricao')->nullable();
            $table->string('status')->default('pendente');
            $table->timestamps();
        });
    }
};
