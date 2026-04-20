<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabeçalhos de NF-e, NFS-e, CT-e (Registros A100, C100, D100)
        Schema::create('efd_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->string('chave_acesso', 44)->nullable();
            $table->string('modelo', 2)->index();
            $table->bigInteger('numero');
            $table->string('serie', 10)->nullable();
            $table->date('data_emissao')->index();
            $table->enum('tipo_operacao', ['entrada', 'saida'])->index();
            $table->string('origem_arquivo')->nullable();
            $table->decimal('valor_total', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->jsonb('metadados')->nullable();
            $table->jsonb('validacao')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'data_emissao', 'tipo_operacao'], 'efd_notas_cliente_data_tipo_idx');
        });

        // Índice único parcial: NULL em chave_acesso = NFS-e sem chave (não conta para unicidade)
        DB::statement('
            CREATE UNIQUE INDEX efd_notas_unique_nota
            ON efd_notas (cliente_id, chave_acesso, modelo, numero, serie)
            WHERE chave_acesso IS NOT NULL
        ');

        // Itens/produtos (Registros A170, C170)
        Schema::create('efd_notas_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('efd_nota_id')->constrained('efd_notas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('numero_item');
            $table->string('codigo_item')->index();
            $table->text('descricao');
            $table->decimal('quantidade', 15, 4)->nullable();
            $table->string('unidade_medida', 6)->nullable();
            $table->decimal('valor_unitario', 15, 4)->nullable();
            $table->decimal('valor_total', 15, 2);
            $table->integer('cfop')->nullable()->index();
            $table->string('cst_icms', 10)->nullable();
            $table->decimal('aliquota_icms', 10, 4)->nullable();
            $table->decimal('valor_icms', 15, 2)->nullable();
            $table->string('cst_pis', 10)->nullable();
            $table->decimal('aliquota_pis', 10, 4)->nullable();
            $table->decimal('valor_pis', 15, 2)->nullable();
            $table->string('cst_cofins', 10)->nullable();
            $table->decimal('aliquota_cofins', 10, 4)->nullable();
            $table->decimal('valor_cofins', 15, 2)->nullable();
            $table->jsonb('metadados')->nullable();
            $table->timestamps();
        });

        // Apuração PIS/COFINS — Bloco M (EFD Contribuições)
        Schema::create('efd_apuracoes_contribuicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // ── M200 — Consolidação PIS do Período ──
            $table->decimal('pis_nao_cumulativo', 15, 2)->default(0);
            $table->decimal('pis_credito_descontado', 15, 2)->default(0);
            $table->decimal('pis_credito_desc_ant', 15, 2)->default(0);
            $table->decimal('pis_nc_devida', 15, 2)->default(0);
            $table->decimal('pis_retencao_nc', 15, 2)->default(0);
            $table->decimal('pis_outras_deducoes_nc', 15, 2)->default(0);
            $table->decimal('pis_nc_recolher', 15, 2)->default(0);
            $table->decimal('pis_cumulativo', 15, 2)->default(0);
            $table->decimal('pis_retencao_cum', 15, 2)->default(0);
            $table->decimal('pis_outras_deducoes_cum', 15, 2)->default(0);
            $table->decimal('pis_cum_recolher', 15, 2)->default(0);
            $table->decimal('pis_total_recolher', 15, 2)->default(0);

            // ── M600 — Consolidação COFINS do Período ──
            $table->decimal('cofins_nao_cumulativo', 15, 2)->default(0);
            $table->decimal('cofins_credito_descontado', 15, 2)->default(0);
            $table->decimal('cofins_credito_desc_ant', 15, 2)->default(0);
            $table->decimal('cofins_nc_devida', 15, 2)->default(0);
            $table->decimal('cofins_retencao_nc', 15, 2)->default(0);
            $table->decimal('cofins_outras_deducoes_nc', 15, 2)->default(0);
            $table->decimal('cofins_nc_recolher', 15, 2)->default(0);
            $table->decimal('cofins_cumulativo', 15, 2)->default(0);
            $table->decimal('cofins_retencao_cum', 15, 2)->default(0);
            $table->decimal('cofins_outras_deducoes_cum', 15, 2)->default(0);
            $table->decimal('cofins_cum_recolher', 15, 2)->default(0);
            $table->decimal('cofins_total_recolher', 15, 2)->default(0);

            // ── M210 — Detalhamento PIS por CST (múltiplos registros) ──
            $table->jsonb('pis_detalhes')->nullable();

            // ── M400/M410 — Receitas não tributadas PIS ──
            $table->jsonb('pis_nao_tributado')->nullable();

            // ── M610 — Detalhamento COFINS por CST (múltiplos registros) ──
            $table->jsonb('cofins_detalhes')->nullable();

            // ── M605 — COFINS a recolher por código de receita ──
            $table->jsonb('cofins_recolher_detalhe')->nullable();

            // ── M100/M105/M110 — Créditos PIS não-cumulativo (Lucro Real) ──
            $table->jsonb('pis_creditos_nc')->nullable();

            // ── M500/M505/M510 — Créditos COFINS não-cumulativo (Lucro Real) ──
            $table->jsonb('cofins_creditos_nc')->nullable();

            // ── Backup do payload completo do n8n ──
            $table->jsonb('dados_brutos')->nullable();

            $table->timestamps();

            $table->unique(['importacao_id'], 'efd_apuracoes_contrib_importacao_unique');
            $table->index(['cliente_id', 'created_at'], 'efd_apuracoes_contrib_cliente_idx');
            $table->index(['user_id'], 'efd_apuracoes_contrib_user_idx');
        });

        // Apuração ICMS/IPI — Bloco E (EFD Fiscal)
        Schema::create('efd_apuracoes_icms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // ── E100 — Período ──
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fim')->nullable();

            // ── E110 — Apuração ICMS Próprio (14 campos) ──
            $table->decimal('icms_tot_debitos', 15, 2)->default(0);
            $table->decimal('icms_aj_debitos', 15, 2)->default(0);
            $table->decimal('icms_tot_aj_debitos', 15, 2)->default(0);
            $table->decimal('icms_estornos_credito', 15, 2)->default(0);
            $table->decimal('icms_tot_creditos', 15, 2)->default(0);
            $table->decimal('icms_aj_creditos', 15, 2)->default(0);
            $table->decimal('icms_tot_aj_creditos', 15, 2)->default(0);
            $table->decimal('icms_estornos_debito', 15, 2)->default(0);
            $table->decimal('icms_sld_credor_ant', 15, 2)->default(0);
            $table->decimal('icms_sld_apurado', 15, 2)->default(0);
            $table->decimal('icms_tot_deducoes', 15, 2)->default(0);
            $table->decimal('icms_a_recolher', 15, 2)->default(0);
            $table->decimal('icms_sld_credor_transportar', 15, 2)->default(0);
            $table->decimal('icms_deb_especiais', 15, 2)->default(0);

            // ── E210 — Apuração ICMS-ST (14 campos) ──
            $table->string('st_uf', 2)->nullable();
            $table->string('st_ind_movimentacao', 1)->nullable();
            $table->decimal('st_sld_credor_ant', 15, 2)->default(0);
            $table->decimal('st_devolucoes', 15, 2)->default(0);
            $table->decimal('st_ressarcimentos', 15, 2)->default(0);
            $table->decimal('st_outros_creditos', 15, 2)->default(0);
            $table->decimal('st_aj_creditos', 15, 2)->default(0);
            $table->decimal('st_retencao', 15, 2)->default(0);
            $table->decimal('st_outros_debitos', 15, 2)->default(0);
            $table->decimal('st_aj_debitos', 15, 2)->default(0);
            $table->decimal('st_sld_devedor_ant', 15, 2)->default(0);
            $table->decimal('st_deducoes', 15, 2)->default(0);
            $table->decimal('st_icms_recolher', 15, 2)->default(0);
            $table->decimal('st_sld_credor_transportar', 15, 2)->default(0);
            $table->decimal('st_deb_especiais', 15, 2)->default(0);

            // ── E116 — Obrigações ICMS a Recolher (múltiplos) ──
            $table->jsonb('icms_obrigacoes')->nullable();

            // ── E250 — Obrigações ICMS-ST a Recolher (múltiplos) ──
            $table->jsonb('st_obrigacoes')->nullable();

            // ── E310 — Apuração DIFAL + FCP (21 campos, opcional) ──
            $table->jsonb('difal_fcp')->nullable();

            // ── E520 — Apuração IPI (opcional) ──
            $table->jsonb('ipi')->nullable();

            // ── Backup do payload completo do n8n ──
            $table->jsonb('dados_brutos')->nullable();

            $table->timestamps();

            $table->unique(['importacao_id'], 'efd_apuracoes_icms_importacao_unique');
            $table->index(['cliente_id', 'periodo_inicio'], 'efd_apuracoes_icms_cliente_idx');
            $table->index(['user_id'], 'efd_apuracoes_icms_user_idx');
        });

        // Retenções na Fonte PIS/COFINS — Bloco F, Registro F600
        Schema::create('efd_retencoes_fonte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('natureza', 2);
            $table->date('data_retencao');
            $table->decimal('base_calculo', 15, 2);
            $table->decimal('valor_total', 15, 2);
            $table->string('cod_receita', 10);
            $table->string('natureza_receita', 2);
            $table->string('cnpj', 14)->index();
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->string('ind_declarante', 1)->default('0');
            $table->jsonb('dados_brutos')->nullable();
            $table->timestamps();

            $table->index(['importacao_id'], 'efd_retencoes_fonte_importacao_idx');
            $table->index(['cliente_id', 'data_retencao'], 'efd_retencoes_fonte_cliente_data_idx');
            $table->index(['user_id'], 'efd_retencoes_fonte_user_idx');
        });

        // Catálogo de Produtos e Serviços (Registro 0200)
        Schema::create('efd_catalogo_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->string('cod_item', 60);
            $table->text('descr_item');
            $table->string('cod_barra')->nullable();
            $table->string('tipo_item', 2);
            $table->string('cod_ncm', 8)->nullable();
            $table->string('cod_gen', 2)->nullable();
            $table->decimal('aliq_icms', 10, 4)->nullable();
            $table->string('unid_inv', 20)->nullable();
            $table->jsonb('dados_brutos')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'cod_item'], 'efd_catalogo_itens_unique');
            $table->index(['cliente_id', 'cod_ncm'], 'efd_catalogo_itens_ncm_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efd_catalogo_itens');
        Schema::dropIfExists('efd_retencoes_fonte');
        Schema::dropIfExists('efd_apuracoes_icms');
        Schema::dropIfExists('efd_apuracoes_contribuicoes');
        DB::statement('DROP INDEX IF EXISTS efd_notas_unique_nota');
        Schema::dropIfExists('efd_notas_itens');
        Schema::dropIfExists('efd_notas');
    }
};
