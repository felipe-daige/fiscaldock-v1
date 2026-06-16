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
        Schema::create('monitoramento_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('participante_id')->nullable()->constrained('participantes')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->foreignId('plano_id')->constrained('monitoramento_planos');
            $table->enum('status', ['ativo', 'pausado', 'cancelado'])->default('ativo');
            $table->integer('frequencia_dias')->default(30); // 30 = mensal
            $table->timestamp('proxima_execucao_em')->nullable();
            $table->timestamp('ultima_execucao_em')->nullable();
            $table->timestamps();

            $table->unique(['participante_id', 'plano_id']); // Um participante só pode ter uma assinatura por plano
            $table->unique(['cliente_id', 'plano_id']);       // Um cliente só pode ter uma assinatura por plano
        });

        if (! Schema::hasTable('account_subscriptions')) {
            Schema::create('account_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
                // sem cascade: arquivar plano via is_active, nunca hard-delete (RESTRICT protege assinaturas)
                $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
                // string (não enum) de propósito: estados de cobrança podem crescer sem ALTER de constraint
                $table->string('status')->default('ativa'); // pendente, ativa, trial, cancelada, inadimplente
                $table->string('ciclo')->default('mensal');  // mensal, anual
                $table->timestamp('iniciada_em')->nullable();
                $table->timestamp('renova_em')->nullable();
                $table->integer('creditos_inclusos_saldo')->default(0);
                $table->integer('limite_consumo_automatico')->nullable(); // cap do cliente; null = default
                $table->integer('assentos_extras')->default(0);
                $table->string('mp_preapproval_id')->nullable()->unique(); // id do preapproval (assinatura) no MP
                $table->timestamp('proximo_grant_em')->nullable();         // quando o scheduler concede o próximo mês
                $table->timestamp('ultimo_grant_em')->nullable();          // última concessão (guard de idempotência)
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_subscriptions');
        Schema::dropIfExists('monitoramento_assinaturas');
    }
};
