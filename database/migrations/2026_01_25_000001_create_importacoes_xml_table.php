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
        Schema::create('importacoes_xml', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('tipo_documento', 10); // NFE, NFSE, CTE
            $table->string('modo_envio', 10)->default('xml'); // zip, xml
            $table->integer('total_arquivos')->default(0);
            $table->integer('total_xmls')->default(0);
            $table->bigInteger('tamanho_total_bytes')->default(0);
            $table->integer('xmls_processados')->default(0);
            $table->integer('xmls_novos')->default(0);
            $table->integer('xmls_duplicados_processados')->default(0);
            $table->integer('xmls_com_erro')->default(0);
            $table->integer('participantes_novos')->default(0);
            $table->integer('participantes_atualizados')->default(0);
            $table->integer('participantes_ignorados')->default(0);
            $table->decimal('valor_total', 15, 2)->default(0); // Soma dos valores das notas
            $table->string('status', 20)->default('pendente'); // pendente, processando, concluido, erro
            $table->text('erro_mensagem')->nullable();
            $table->jsonb('participante_ids')->nullable();
            $table->jsonb('erros_detalhados')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->integer('tempo_processamento_segundos')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacoes_xml');
    }
};
