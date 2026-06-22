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

        // Parâmetros comerciais globais editáveis por admin (LGPD/CFO §6.1). Tabela vazia por
        // padrão — o PricingCatalogService usa os defaults hardcoded como fallback; um override
        // aqui passa a vencer. Garante zero mudança de preço até um admin editar.
        if (! Schema::hasTable('comercial_parametros')) {
            Schema::create('comercial_parametros', function (Blueprint $table) {
                $table->id();
                $table->string('chave')->unique();
                $table->string('valor'); // serializado como string; o tipo é conhecido pelo default
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // LGPD fase 2.1 — trilha auditável de consentimento (append-only). 1 linha por evento
        // (aceite no signup, revogação de marketing, pedido/cancelamento de exclusão). Carimba a
        // versão do documento (config/legal.php) e IP/UA no momento do ato — prova de consentimento.
        if (! Schema::hasTable('consent_logs')) {
            Schema::create('consent_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('tipo');   // termos|privacidade|marketing|exclusao
                $table->string('acao');   // aceite|revogacao|solicitacao|cancelamento
                $table->boolean('valor')->nullable();  // estado do opt-in (true/false) quando aplicável
                $table->string('versao')->nullable();  // versão do documento aceito (config/legal.php)
                $table->string('ip', 64)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->unique(); // free, essencial, profissional, escritorio, enterprise
                $table->string('nome');
                $table->integer('preco_mensal_centavos')->default(0); // 0 = sob consulta (enterprise)
                $table->integer('preco_anual_centavos')->default(0);
                $table->integer('creditos_inclusos')->default(0);
                $table->string('faixa_slug')->default('base'); // base, x, y, z — faixa comprada pelo tier
                $table->integer('limite_clientes')->nullable();           // null = ilimitado
                $table->integer('limite_cnpjs_monitorados')->nullable();  // null = ilimitado
                $table->integer('frequencia_padrao_dias')->default(30);
                $table->string('profundidade_auto_monitor')->nullable();  // cadastral|licitacao|compliance|due_diligence
                $table->integer('assentos_inclusos')->default(1);
                $table->decimal('rollover_cap_multiplicador', 4, 2)->default(1); // banca 1x o mensal
                $table->jsonb('capabilities')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('ordem')->default(0);
                $table->string('mp_preapproval_plan_id_mensal')->nullable(); // id do preapproval_plan no MP (ciclo mensal)
                $table->string('mp_preapproval_plan_id_anual')->nullable();  // id do preapproval_plan no MP (ciclo anual)
                $table->timestamps();
            });
        }

        // Descartes de alertas de catálogo (NCM a revisar / sem catálogo) por usuário e item.
        if (! Schema::hasTable('catalogo_alerta_descartes')) {
            Schema::create('catalogo_alerta_descartes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('tipo'); // ncm_divergente | sem_catalogo
                $table->string('codigo_item');
                $table->timestamps();
                $table->unique(['user_id', 'tipo', 'codigo_item']);
                $table->index('user_id');
            });
        }

        // --- Admin Console fase 2 (2026-06-22) ---
        if (! Schema::hasTable('admin_action_logs')) {
            Schema::create('admin_action_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('acao');
                $table->text('motivo');
                $table->jsonb('detalhe')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['target_user_id', 'created_at']);
                $table->index('created_at');
            });
        }

        if (! Schema::hasColumn('users', 'bloqueado_em')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('bloqueado_em')->nullable()->after('is_admin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'bloqueado_em')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('bloqueado_em'));
        }
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('catalogo_alerta_descartes');
        Schema::dropIfExists('consent_logs');
        Schema::dropIfExists('comercial_parametros');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('monitoramento_planos');
    }
};
