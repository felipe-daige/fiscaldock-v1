<?php

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
        Schema::create('priv_cpf_operacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpf_id')->constrained('priv_cpf_cadastro')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('cnpj_empresa', 14);
            $table->string('tipo_participacao', 20);
            $table->string('chave_acesso', 44)->nullable();
            $table->string('modelo', 2)->nullable();
            $table->string('serie', 3)->nullable();
            $table->string('numero_doc', 20)->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_operacao')->nullable();
            $table->char('tipo_operacao', 1)->nullable();
            $table->decimal('valor_total', 15, 2)->nullable();
            $table->decimal('valor_mercadorias', 15, 2)->nullable();
            $table->decimal('valor_frete', 15, 2)->nullable();
            $table->decimal('valor_desconto', 15, 2)->nullable();
            $table->string('uf_origem', 2)->nullable();
            $table->string('uf_destino', 2)->nullable();
            $table->string('ncm_principal', 8)->nullable();
            $table->string('descricao_resumo', 255)->nullable();
            $table->string('arquivo_origem', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index('cpf_id');
            $table->index('cnpj_empresa');
            $table->index('data_emissao');
        });

        // Criar índice único parcial usando SQL raw (apenas quando chave_acesso não é nulo)
        DB::statement('CREATE UNIQUE INDEX idx_op_chave ON priv_cpf_operacoes(chave_acesso) WHERE chave_acesso IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priv_cpf_operacoes');
    }
};

