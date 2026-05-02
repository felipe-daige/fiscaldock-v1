<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_notas', function (Blueprint $table) {
            $table->foreignId('emit_cliente_id')
                ->nullable()
                ->after('emit_participante_id')
                ->constrained('clientes')
                ->nullOnDelete();

            $table->foreignId('dest_cliente_id')
                ->nullable()
                ->after('dest_participante_id')
                ->constrained('clientes')
                ->nullOnDelete();

            // Snapshot SEFAZ (acervo único xml_notas — busca avulsa e clearance em lote
            // gravam aqui via upsert por (user_id, nfe_id), preservando dados do contador).
            $table->foreignId('consulta_lote_id')
                ->nullable()
                ->constrained('consulta_lotes')
                ->nullOnDelete();
            $table->string('situacao_sefaz', 30)->nullable();
            $table->timestamp('verificado_sefaz_em')->nullable();

            // Resumo persistido da comparação declarado vs SEFAZ.
            // Populado por XmlNotaSefazSyncObserver quando situacao_sefaz/verificado_sefaz_em mudam.
            // Permite listagem/dashboard sem rodar ComparacaoNotaService linha a linha.
            $table->string('divergencia_severidade', 16)->nullable();   // OK | REVISAR | CRITICA
            $table->unsignedSmallInteger('divergencia_count')->nullable();
            $table->jsonb('divergencia_resumo')->nullable();
            $table->timestamp('comparado_em')->nullable();

            $table->index('situacao_sefaz');
            $table->index('consulta_lote_id');
            $table->index('divergencia_severidade');
        });

        // Backfill from participantes.cliente_id
        DB::statement('
            UPDATE xml_notas SET emit_cliente_id = p.cliente_id
            FROM participantes p
            WHERE xml_notas.emit_participante_id = p.id
              AND p.cliente_id IS NOT NULL
        ');

        DB::statement('
            UPDATE xml_notas SET dest_cliente_id = p.cliente_id
            FROM participantes p
            WHERE xml_notas.dest_participante_id = p.id
              AND p.cliente_id IS NOT NULL
        ');

        // Itens tipados das notas XML — espelha efd_notas_itens pra permitir
        // catálogo unificado (UNION com dedup por chave de acesso, EFD vence).
        // Campos exclusivos do XML (cest, ean, comb, med, infAdProd, etc.) vão
        // pra colunas próprias ou pra metadados jsonb.
        Schema::create('xml_notas_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xml_nota_id')->constrained('xml_notas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->smallInteger('numero_item');               // nItem
            $table->string('codigo_item', 60);                 // cProd
            $table->string('ean', 14)->nullable();             // cEAN ('SEM' → null)
            $table->text('descricao');                         // xProd

            $table->string('ncm', 10)->nullable();
            $table->string('cest', 10)->nullable();            // exclusivo XML
            $table->string('cfop', 4);
            $table->string('unidade_medida', 6)->nullable();   // uCom

            $table->decimal('quantidade', 15, 4);              // qCom
            $table->decimal('valor_unitario', 15, 10)->nullable(); // vUnCom (10 casas)
            $table->decimal('valor_total', 15, 2);             // vProd

            $table->string('cst_icms', 3)->nullable();         // aceita CST (regime normal) ou CSOSN (Simples)
            $table->decimal('aliquota_icms', 5, 2)->nullable();
            $table->decimal('valor_icms', 15, 2)->nullable();
            $table->decimal('aliquota_icms_st', 5, 2)->nullable();
            $table->decimal('valor_icms_st', 15, 2)->nullable();

            $table->string('cst_pis', 2)->nullable();
            $table->decimal('aliquota_pis', 7, 4)->nullable();
            $table->decimal('valor_pis', 15, 2)->nullable();

            $table->string('cst_cofins', 2)->nullable();
            $table->decimal('aliquota_cofins', 7, 4)->nullable();
            $table->decimal('valor_cofins', 15, 2)->nullable();

            $table->string('cst_ipi', 2)->nullable();
            $table->decimal('aliquota_ipi', 5, 2)->nullable();
            $table->decimal('valor_ipi', 15, 2)->nullable();

            // paraquedas pra exóticos do <det>: comb, med, arma, infAdProd, DI, rastro, ICMSST por UF
            $table->jsonb('metadados')->nullable();

            $table->timestamps();

            $table->unique(['xml_nota_id', 'numero_item']);
            $table->index(['user_id', 'codigo_item']);
            $table->index('ncm');
            $table->index('cfop');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xml_notas_itens');

        Schema::table('xml_notas', function (Blueprint $table) {
            $table->dropIndex(['situacao_sefaz']);
            $table->dropIndex(['consulta_lote_id']);
            $table->dropIndex(['divergencia_severidade']);
            $table->dropColumn([
                'situacao_sefaz',
                'verificado_sefaz_em',
                'divergencia_severidade',
                'divergencia_count',
                'divergencia_resumo',
                'comparado_em',
            ]);
            $table->dropConstrainedForeignId('consulta_lote_id');
            $table->dropConstrainedForeignId('emit_cliente_id');
            $table->dropConstrainedForeignId('dest_cliente_id');
        });
    }
};
