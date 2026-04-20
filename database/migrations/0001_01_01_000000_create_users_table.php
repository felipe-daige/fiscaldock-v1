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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sobrenome');
            $table->string('telefone');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->decimal('credits', 12, 2)->default(0);
            $table->string('empresa')->nullable();
            $table->string('cargo')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('faturamento_anual')->nullable();
            $table->string('desafio_principal')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamp('marketing_opt_in_at')->nullable();
            $table->boolean('trial_used')->default(false);
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_expires_at')->nullable();
            $table->unsignedInteger('trial_credits_granted')->default(0);
            $table->unsignedInteger('trial_credits_remaining')->default(0);
            $table->unsignedInteger('trial_credits_expired')->default(0);
            $table->string('trial_source')->nullable();
            $table->boolean('alertas_operacionais')->default(true);
            $table->boolean('alertas_monitoramento')->default(true);
            $table->boolean('resumo_periodico')->default(true);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('landing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('origem')->default('banner_contato');
            $table->string('user_agent', 500)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_leads');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
