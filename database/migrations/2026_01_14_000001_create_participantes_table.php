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
        // Tabela principal de participantes (Monitoramento)
        Schema::create('participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('cnpj', 14)->index();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia')->nullable();
            $table->string('situacao_cadastral')->nullable();
            $table->string('regime_tributario')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cnpj_matriz', 14)->nullable()->index();
            $table->string('inscricao_estadual')->nullable();
            $table->string('suframa')->nullable();
            $table->string('endereco')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('codigo_municipal')->nullable();
            $table->string('cep', 8)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->smallInteger('crt')->nullable(); // 1=Simples, 2=Excesso, 3=Normal
            $table->unsignedBigInteger('importacao_participante_id')->nullable()->index(); // FK sem constraint (criada depois)
            $table->string('origem_tipo')->nullable(); // SPED_EFD_FISCAL, SPED_EFD_CONTRIB, NFE, NFSE, MANUAL
            $table->json('origem_ref')->nullable(); // {"arquivo": "...", "importado_em": "..."}
            $table->timestamp('ultima_consulta_em')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cnpj']); // CNPJ único por usuário
        });

        // Tabela de grupos de participantes
        Schema::create('participantes_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->string('cor', 7)->nullable(); // Cor do badge (hex: #RRGGBB)
            $table->text('descricao')->nullable();
            $table->boolean('is_auto')->default(false); // Grupo criado automaticamente (ex: importação SPED)
            $table->timestamps();

            $table->index(['user_id', 'nome']);
        });

        // Tabela pivot para relação many-to-many
        Schema::create('participantes_grupos_pivot', function (Blueprint $table) {
            $table->foreignId('participante_id')->constrained('participantes')->onDelete('cascade');
            $table->foreignId('participantes_grupo_id')->constrained('participantes_grupos')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['participante_id', 'participantes_grupo_id'], 'participantes_grupos_pivot_pk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participantes_grupos_pivot');
        Schema::dropIfExists('participantes_grupos');
        Schema::dropIfExists('participantes');
    }
};
