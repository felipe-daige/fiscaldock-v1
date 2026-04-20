<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('amount');
            $table->integer('balance_after');
            $table->string('type', 30); // consulta_lote, sped_importacao, manual_add, purchase, refund
            $table->string('description')->nullable();
            $table->string('source_type')->nullable(); // morph type
            $table->unsignedBigInteger('source_id')->nullable(); // morph id
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
