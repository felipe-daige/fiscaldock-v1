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

        // Pagamentos via Mercado Pago (Fase 1 — fundação de pagamentos + pacote avulso).
        // Guarda de idempotência de schema: nunca recriar se já existe em prod.
        if (! Schema::hasTable('mercado_pago_payments')) {
            Schema::create('mercado_pago_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('tipo', 20)->default('avulso'); // avulso | subscription
                $table->foreignId('account_subscription_id')->nullable()->constrained('account_subscriptions')->nullOnDelete();
                $table->string('pacote'); // slug do catálogo (business, enterprise, custom)
                $table->string('mp_payment_id')->nullable()->unique(); // id do pagamento no MP
                $table->string('mp_preference_id')->nullable(); // id de preference (fluxos futuros)
                $table->string('status', 30)->default('pending'); // pending|approved|rejected|cancelled|refunded
                $table->string('status_detail')->nullable();
                $table->string('payment_method', 40)->nullable(); // pix, credit_card, ...
                $table->decimal('valor', 10, 2); // R$ cobrado (fonte: catálogo backend)
                $table->integer('creditos'); // créditos a liberar (fonte: catálogo backend)
                $table->string('idempotency_key')->unique(); // X-Idempotency-Key enviado ao MP
                $table->timestamp('credited_at')->nullable(); // quando os créditos foram liberados (guard)
                $table->jsonb('payload')->nullable(); // resposta/notificação bruta do MP
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        // Recarga automática por tempo (Fase 2): recompra recorrente de um pacote de
        // créditos via preapproval do Mercado Pago. Uma por usuário (unique user_id).
        if (! Schema::hasTable('recarga_automaticas')) {
            Schema::create('recarga_automaticas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $table->string('pacote');                 // slug do catálogo (business, volume, custom)
                $table->integer('creditos');              // créditos liberados a cada cobrança (do catálogo)
                $table->decimal('valor', 10, 2);          // R$ por cobrança (do catálogo backend)
                $table->integer('frequencia_meses')->default(1); // periodicidade da recompra
                $table->string('status')->default('pendente');   // pendente|ativa|inadimplente|cancelada
                $table->string('mp_preapproval_id')->nullable()->unique(); // id do preapproval no MP
                $table->timestamp('ultima_cobranca_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recarga_automaticas');
        Schema::dropIfExists('mercado_pago_payments');
        Schema::dropIfExists('credit_transactions');
    }
};
