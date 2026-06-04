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
            $table->boolean('cancelada')->default(false);
            $table->timestamps();

            $table->index(['cliente_id', 'data_emissao', 'tipo_operacao'], 'efd_notas_cliente_data_tipo_idx');
        });

        // Índice único parcial: NULL em chave_acesso = NFS-e sem chave (não conta para unicidade).
        // origem_arquivo faz parte da chave: a MESMA NF-e é escriturada tanto no EFD ICMS/IPI
        // ('fiscal', com ICMS) quanto no EFD PIS/COFINS ('contribuicoes', com PIS/COFINS) da
        // mesma empresa/período. Sem origem_arquivo aqui, a 2ª importação perdia as notas por
        // colisão de chave (ver docs/n8n/extracao-efd-icms-ipi/auditoria-2026-06-01.md).
        DB::statement('
            CREATE UNIQUE INDEX efd_notas_unique_nota
            ON efd_notas (cliente_id, chave_acesso, modelo, numero, serie, origem_arquivo)
            WHERE chave_acesso IS NOT NULL
        ');

        // Índice único parcial para NFS-e (modelo 00) e demais sem chave: dedup por
        // (cliente, modelo, numero, serie, participante) + origem_arquivo. Mesmo motivo do
        // unique_nota — a NF-e/NFS-e pode aparecer em escriturações diferentes (fiscal/contrib).
        DB::statement('
            CREATE UNIQUE INDEX efd_notas_unique_nfse
            ON efd_notas (cliente_id, modelo, numero, serie, participante_id, origem_arquivo)
            WHERE chave_acesso IS NULL
        ');

        // Index parcial pra filtrar canceladas rapidamente (poucas linhas vs total)
        DB::statement('CREATE INDEX efd_notas_cancelada_idx ON efd_notas (cancelada) WHERE cancelada = true');

        // Itens/produtos (Registros A170, C170)
        Schema::create('efd_notas_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('efd_nota_id')->constrained('efd_notas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('numero_item');
            $table->string('codigo_item')->index();
            $table->text('descricao')->nullable();
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

        // Change-log do catálogo (0200): registra mudança de NCM/alíquota/unidade/descrição
        // de um item entre importações. Capturado por trigger no UPDATE de efd_catalogo_itens
        // (re-importação com ON CONFLICT DO UPDATE). Mantém efd_catalogo_itens com 1 versão por
        // item (sem fan-out no consumo); o histórico vive aqui. Não recalculável após sobrescrita.
        Schema::create('efd_catalogo_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('cod_item', 60);
            $table->string('campo', 20); // cod_ncm | aliq_icms | unid_inv | descr_item
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->foreignId('importacao_id')->nullable()->constrained('efd_importacoes')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['user_id', 'cod_item', 'changed_at'], 'efd_catalogo_historico_item_idx');
        });

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION efd_catalogo_log_mudanca() RETURNS trigger AS $$
            BEGIN
                IF NEW.cod_ncm IS DISTINCT FROM OLD.cod_ncm THEN
                    INSERT INTO efd_catalogo_historico (user_id, cliente_id, cod_item, campo, valor_anterior, valor_novo, importacao_id, changed_at)
                    VALUES (NEW.user_id, NEW.cliente_id, NEW.cod_item, 'cod_ncm', OLD.cod_ncm, NEW.cod_ncm, NEW.importacao_id, (NOW() AT TIME ZONE 'UTC'));
                END IF;
                IF NEW.aliq_icms IS DISTINCT FROM OLD.aliq_icms THEN
                    INSERT INTO efd_catalogo_historico (user_id, cliente_id, cod_item, campo, valor_anterior, valor_novo, importacao_id, changed_at)
                    VALUES (NEW.user_id, NEW.cliente_id, NEW.cod_item, 'aliq_icms', OLD.aliq_icms::text, NEW.aliq_icms::text, NEW.importacao_id, (NOW() AT TIME ZONE 'UTC'));
                END IF;
                IF NEW.unid_inv IS DISTINCT FROM OLD.unid_inv THEN
                    INSERT INTO efd_catalogo_historico (user_id, cliente_id, cod_item, campo, valor_anterior, valor_novo, importacao_id, changed_at)
                    VALUES (NEW.user_id, NEW.cliente_id, NEW.cod_item, 'unid_inv', OLD.unid_inv, NEW.unid_inv, NEW.importacao_id, (NOW() AT TIME ZONE 'UTC'));
                END IF;
                IF NEW.descr_item IS DISTINCT FROM OLD.descr_item THEN
                    INSERT INTO efd_catalogo_historico (user_id, cliente_id, cod_item, campo, valor_anterior, valor_novo, importacao_id, changed_at)
                    VALUES (NEW.user_id, NEW.cliente_id, NEW.cod_item, 'descr_item', OLD.descr_item, NEW.descr_item, NEW.importacao_id, (NOW() AT TIME ZONE 'UTC'));
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement('DROP TRIGGER IF EXISTS efd_catalogo_historico_trg ON efd_catalogo_itens');
        DB::statement('CREATE TRIGGER efd_catalogo_historico_trg AFTER UPDATE ON efd_catalogo_itens FOR EACH ROW EXECUTE FUNCTION efd_catalogo_log_mudanca()');

        // Analítico consolidado de ICMS (C190/D190): agregado por CST+CFOP+alíquota.
        // SPED-Fiscal permite escriturar saídas via C190 sem C170 — esta tabela
        // captura esse nível. Filho de efd_notas (1 nota → N consolidados).
        Schema::create('efd_notas_consolidados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('efd_nota_id')->constrained('efd_notas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('cst_icms', 3);
            $table->integer('cfop');
            $table->decimal('aliquota_icms', 6, 2)->nullable();
            $table->decimal('valor_operacao', 19, 2)->default(0);
            $table->decimal('valor_bc_icms', 19, 2)->default(0);
            $table->decimal('valor_icms', 19, 2)->default(0);
            $table->decimal('valor_bc_icms_st', 19, 2)->default(0);
            $table->decimal('valor_icms_st', 19, 2)->default(0);
            $table->decimal('valor_reducao_bc', 19, 2)->default(0);
            $table->decimal('valor_ipi', 19, 2)->default(0);
            $table->string('cod_obs', 6)->nullable();
            $table->timestamps();

            $table->index('user_id', 'efd_notas_consolidados_user_idx');
            $table->index('cfop', 'efd_notas_consolidados_cfop_idx');
        });

        // Unique com NULLS NOT DISTINCT (PG15+) — aliquota_icms pode ser NULL e
        // ainda assim deduplicar. Permite ON CONFLICT com colunas simples no n8n.
        DB::statement('CREATE UNIQUE INDEX efd_notas_consolidados_unique ON efd_notas_consolidados (efd_nota_id, cst_icms, cfop, aliquota_icms) NULLS NOT DISTINCT');

        // Divergências detectadas entre SPED bruto e estado persistido.
        // Cobre canceladas descartadas, duplicações de pipeline, constraints,
        // órfãos do Merge, parse inconsistente e reconciliação de valor.
        Schema::create('efd_divergencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacao_id')->constrained('efd_importacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bloco', 8);                  // 'C100', 'C170', 'C190', 'D100', etc.
            $table->string('motivo', 40);                // ver enum no Model
            $table->string('severidade', 10);            // 'info' | 'aviso' | 'erro'
            $table->string('chave_acesso', 44)->nullable();
            $table->bigInteger('numero_documento')->nullable();
            $table->integer('numero_item')->nullable();
            $table->jsonb('payload_descartado');
            $table->text('mensagem')->nullable();
            $table->timestamp('detectado_em')->useCurrent();
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();

            $table->index('importacao_id', 'efd_divergencias_importacao_idx');
            $table->index('chave_acesso', 'efd_divergencias_chave_idx');
            $table->index(['user_id', 'motivo'], 'efd_divergencias_user_motivo_idx');
        });

        // Dedup idempotente pro INSERT direto do n8n (Auditor). NULLS NOT DISTINCT
        // garante que chave_acesso/numero_item nulos colidam (PG15+).
        DB::statement('CREATE UNIQUE INDEX efd_divergencias_dedup ON efd_divergencias (importacao_id, bloco, motivo, chave_acesso, numero_item) NULLS NOT DISTINCT');
    }

    public function down(): void
    {
        Schema::dropIfExists('efd_divergencias');
        Schema::dropIfExists('efd_notas_consolidados');
        DB::statement('DROP TRIGGER IF EXISTS efd_catalogo_historico_trg ON efd_catalogo_itens');
        DB::statement('DROP FUNCTION IF EXISTS efd_catalogo_log_mudanca()');
        Schema::dropIfExists('efd_catalogo_historico');
        Schema::dropIfExists('efd_catalogo_itens');
        Schema::dropIfExists('efd_retencoes_fonte');
        Schema::dropIfExists('efd_apuracoes_icms');
        Schema::dropIfExists('efd_apuracoes_contribuicoes');
        DB::statement('DROP INDEX IF EXISTS efd_notas_unique_nfse');
        DB::statement('DROP INDEX IF EXISTS efd_notas_unique_nota');
        Schema::dropIfExists('efd_notas_itens');
        Schema::dropIfExists('efd_notas');
    }
};
