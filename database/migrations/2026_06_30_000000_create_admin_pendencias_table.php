<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_pendencias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('nota')->nullable();
            $table->date('lembrar_em')->nullable();
            $table->string('status', 20)->default('aberta');
            $table->foreignId('criado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('lembrar_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_pendencias');
    }
};
