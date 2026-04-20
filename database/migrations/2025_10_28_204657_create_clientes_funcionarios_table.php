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
            $table->foreignId('criado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes_funcionarios');
    }
};
