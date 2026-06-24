<?php

namespace App\Services\Consultas\Fiscal;

use Illuminate\Support\Facades\DB;

/**
 * Agrega top produtos (itens EFD ⨝ catálogo 0200) e top CFOPs (C190 consolidado)
 * de notas FISCAIS não canceladas, escopadas por participante_id OU cliente_id.
 * Resultado keyed pelo id de escopo, para uso em lote.
 */
class TopMovimentacaoQuery
{
    private const COLUNAS = ['participante_id', 'cliente_id'];

    /**
     * @param  'participante_id'|'cliente_id'  $coluna
     * @param  array<int, int>  $ids
     * @return array<int, array<int, array{cod_item:string, descricao:string, ncm:?string, valor:float, qtd:int}>>
     */
    public function produtos(int $userId, string $coluna, array $ids, int $limite = 5): array
    {
        $this->assertColuna($coluna);
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        // Join no catálogo por (user_id, cliente_id, cod_item): o catálogo é UNIQUE
        // (cliente_id, cod_item), então casar pelo cliente da NOTA dá no máx. 1 linha
        // e evita multiplicar COUNT/SUM quando o mesmo cod_item existe em 2 clientes.
        $linhas = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->leftJoin('efd_catalogo_itens as c', function ($j) {
                $j->on('c.user_id', '=', 'i.user_id')
                    ->on('c.cliente_id', '=', 'n.cliente_id')
                    ->on('c.cod_item', '=', 'i.codigo_item');
            })
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->whereIn("n.{$coluna}", $ids)
            ->whereNotNull('i.codigo_item')
            ->groupBy("n.{$coluna}", 'i.codigo_item')
            ->selectRaw("n.{$coluna} as escopo_id, i.codigo_item as cod_item,
                MAX(c.descr_item) as descr_item, MAX(c.cod_ncm) as cod_ncm,
                MAX(i.descricao) as descricao_item,
                COUNT(*) as qtd, COALESCE(SUM(i.valor_total), 0) as valor")
            ->get();

        return $linhas
            ->groupBy('escopo_id')
            ->map(fn ($g) => $g->sortByDesc('valor')->take($limite)
                ->map(fn ($r) => [
                    'cod_item' => (string) $r->cod_item,
                    'descricao' => (string) ($r->descr_item ?: $r->descricao_item ?: $r->cod_item),
                    'ncm' => $r->cod_ncm !== null && $r->cod_ncm !== '' ? (string) $r->cod_ncm : null,
                    'valor' => round((float) $r->valor, 2),
                    'qtd' => (int) $r->qtd,
                ])->values()->all())
            ->all();
    }

    /**
     * @param  'participante_id'|'cliente_id'  $coluna
     * @param  array<int, int>  $ids
     * @return array<int, array<int, array{cfop:int, qtd:int}>>
     */
    public function cfops(int $userId, string $coluna, array $ids, int $limite = 5): array
    {
        $this->assertColuna($coluna);
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $linhas = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->whereIn("n.{$coluna}", $ids)
            ->whereNotNull('c.cfop')
            ->groupBy("n.{$coluna}", 'c.cfop')
            ->selectRaw("n.{$coluna} as escopo_id, c.cfop, COUNT(*) as qtd")
            ->get();

        return $linhas
            ->groupBy('escopo_id')
            ->map(fn ($g) => $g->sortByDesc('qtd')->take($limite)
                ->map(fn ($r) => ['cfop' => (int) $r->cfop, 'qtd' => (int) $r->qtd])
                ->values()->all())
            ->all();
    }

    private function assertColuna(string $coluna): void
    {
        if (! in_array($coluna, self::COLUNAS, true)) {
            throw new \InvalidArgumentException("Coluna de escopo inválida: {$coluna}");
        }
    }
}
