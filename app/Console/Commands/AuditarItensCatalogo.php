<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audita a associação item-de-nota ↔ catálogo (0200) por código.
 *
 * O vínculo é lógico: `efd_notas_itens.codigo_item = efd_catalogo_itens.cod_item`
 * (mesmo `user_id`, idealmente mesma `importacao_id`). Os dois lados já saem do SPED
 * preenchidos pelo n8n — não há merge a criar; isto só confere por JOIN.
 *
 * CT-e (modelo 57) usa D190 consolidado (sem COD_ITEM) → fica FORA da checagem.
 *  - orfao_estrito: item não acha catálogo na MESMA importação (pode ser só versão de outro mês).
 *  - orfao_real: item não acha em NENHUM catálogo do usuário → furo de extração de verdade.
 */
class AuditarItensCatalogo extends Command
{
    protected $signature = 'efd:auditar-itens-catalogo {--user= : Restringe a um user_id}';

    protected $description = 'Confere a associação item-de-nota ↔ catálogo 0200 (NF-e/NFS-e; CT-e fora). Reporta itens sem código e órfãos (estrito/real).';

    public function handle(): int
    {
        $userOpt = $this->option('user') !== null ? (int) $this->option('user') : null;
        $rows = $this->audit($userOpt);

        if (empty($rows)) {
            $this->info('Nenhum item de NF-e/NFS-e encontrado.');

            return self::SUCCESS;
        }

        $this->table(
            ['Origem', 'Modelo', 'Itens', 'Sem código', 'Órfão estrito (versão?)', 'Órfão real 🔴'],
            array_map(fn ($r) => [$r['origem'], $r['modelo'], $r['itens'], $r['sem_codigo'], $r['orfao_estrito'], $r['orfao_real']], $rows)
        );

        $problemas = array_sum(array_map(fn ($r) => $r['sem_codigo'] + $r['orfao_real'], $rows));
        if ($problemas > 0) {
            $this->warn("⚠️  {$problemas} item(ns) com problema REAL (sem código ou sem catálogo em lugar nenhum) — análogo ao furo de participante. Investigar a extração do item no n8n.");
        } else {
            $this->info('✅ Sem órfãos reais nem itens sem código. (Órfão estrito > 0 é só versão de catálogo de outro período.)');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int,array{origem:string,modelo:string,itens:int,sem_codigo:int,orfao_estrito:int,orfao_real:int}>
     */
    public function audit(?int $userId = null): array
    {
        $rows = DB::select('
            SELECT
              n.origem_arquivo AS origem,
              n.modelo         AS modelo,
              COUNT(*)                                                                        AS itens,
              COUNT(*) FILTER (WHERE i.codigo_item IS NULL OR i.codigo_item = \'\')            AS sem_codigo,
              COUNT(*) FILTER (WHERE cs.id IS NULL
                                 AND i.codigo_item IS NOT NULL AND i.codigo_item <> \'\')     AS orfao_estrito,
              COUNT(*) FILTER (WHERE cl.x IS NULL
                                 AND i.codigo_item IS NOT NULL AND i.codigo_item <> \'\')     AS orfao_real
            FROM efd_notas_itens i
            JOIN efd_notas n ON n.id = i.efd_nota_id
            LEFT JOIN LATERAL (
              SELECT cs.id FROM efd_catalogo_itens cs
              WHERE cs.user_id = i.user_id AND cs.cod_item = i.codigo_item AND cs.importacao_id = n.importacao_id
              LIMIT 1
            ) cs ON true
            LEFT JOIN LATERAL (
              SELECT 1 AS x FROM efd_catalogo_itens c2
              WHERE c2.user_id = i.user_id AND c2.cod_item = i.codigo_item
              LIMIT 1
            ) cl ON true
            WHERE n.modelo <> \'57\'
              '.($userId !== null ? 'AND i.user_id = ?' : '').'
            GROUP BY n.origem_arquivo, n.modelo
            ORDER BY 1, 2
        ', $userId !== null ? [$userId] : []);

        return array_map(fn ($r) => [
            'origem' => $r->origem,
            'modelo' => $r->modelo,
            'itens' => (int) $r->itens,
            'sem_codigo' => (int) $r->sem_codigo,
            'orfao_estrito' => (int) $r->orfao_estrito,
            'orfao_real' => (int) $r->orfao_real,
        ], $rows);
    }
}
