<?php

namespace App\Services\Consultas\Fiscal;

use App\Support\Cfop;
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
    public function produtos(int $userId, string $coluna, array $ids, int $limite = 10): array
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
     * @return array<int, array<int, array{cfop:int, descricao:string, qtd:int, valor:float}>>
     */
    public function cfops(int $userId, string $coluna, array $ids, int $limite = 10): array
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
            ->selectRaw("n.{$coluna} as escopo_id, c.cfop, COUNT(*) as qtd,
                COALESCE(SUM(c.valor_operacao), 0) as valor")
            ->get();

        return $linhas
            ->groupBy('escopo_id')
            ->map(fn ($g) => $g->sortByDesc('valor')->take($limite)
                ->map(fn ($r) => [
                    'cfop' => (int) $r->cfop,
                    'descricao' => Cfop::descricao((string) $r->cfop),
                    'qtd' => (int) $r->qtd,
                    'valor' => round((float) $r->valor, 2),
                ])
                ->values()->all())
            ->all();
    }

    /**
     * Maiores notas (por valor) de cada escopo, separadas por tipo_operacao.
     * Top-N por (escopo, tipo) via window function — não puxa todas as notas pro PHP.
     *
     * @param  'participante_id'|'cliente_id'  $coluna
     * @param  array<int, int>  $ids
     * @return array<int, array{entrada: array<int, array{modelo:?string, numero:?string, serie:?string, data:?string, chave:?string, valor:float}>, saida: array<int, array{modelo:?string, numero:?string, serie:?string, data:?string, chave:?string, valor:float}>}>
     */
    public function notas(int $userId, string $coluna, array $ids, int $limite = 30): array
    {
        $this->assertColuna($coluna);
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $sub = DB::table('efd_notas as n')
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->whereIn("n.{$coluna}", $ids)
            ->selectRaw("n.{$coluna} as escopo_id, n.tipo_operacao, n.modelo, n.numero,
                n.serie, n.data_emissao, n.chave_acesso, n.valor_total,
                ROW_NUMBER() OVER (PARTITION BY n.{$coluna}, n.tipo_operacao
                    ORDER BY n.valor_total DESC NULLS LAST, n.id) as rn");

        $linhas = DB::query()->fromSub($sub, 't')->where('rn', '<=', $limite)->get();

        return $linhas
            ->groupBy('escopo_id')
            ->map(function ($g) {
                $porTipo = $g->groupBy('tipo_operacao')
                    ->map(fn ($notas) => $notas->sortByDesc('valor_total')->map(fn ($r) => [
                        'modelo' => $r->modelo !== null ? (string) $r->modelo : null,
                        'numero' => $r->numero !== null ? (string) $r->numero : null,
                        'serie' => $r->serie !== null ? (string) $r->serie : null,
                        'data' => $r->data_emissao ? substr((string) $r->data_emissao, 0, 10) : null,
                        'chave' => $r->chave_acesso !== null && $r->chave_acesso !== '' ? (string) $r->chave_acesso : null,
                        'valor' => round((float) $r->valor_total, 2),
                    ])->values()->all());

                return [
                    'entrada' => $porTipo->get('entrada', []),
                    'saida' => $porTipo->get('saida', []),
                ];
            })
            ->all();
    }

    /**
     * Crédito DESTACADO (regime atual) nas ENTRADAS de cada escopo, somando
     * ICMS+IPI (consolidado C190) e PIS+COFINS (itens). Gate: só conta entradas
     * cujo comprador (clientes.crt = 3, Regime Normal) credita — Simples/MEI ficam de fora.
     * "Destacado" ≠ "aproveitado": não aplica CST/CFOP por item (decisão de escopo).
     *
     * @param  'participante_id'|'cliente_id'  $coluna
     * @param  array<int, int>  $ids
     * @return array<int, float> [escopo_id => destacado], só com total > 0
     */
    public function creditosDestacados(int $userId, string $coluna, array $ids): array
    {
        $this->assertColuna($coluna);
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $base = fn () => DB::table('efd_notas as n')
            ->join('clientes as cli', 'cli.id', '=', 'n.cliente_id')
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->where('n.tipo_operacao', 'entrada')
            ->where('cli.crt', 3)
            ->whereIn("n.{$coluna}", $ids);

        $icmsIpi = (clone $base())
            ->join('efd_notas_consolidados as c', 'c.efd_nota_id', '=', 'n.id')
            ->groupBy("n.{$coluna}")
            ->selectRaw("n.{$coluna} as escopo_id, COALESCE(SUM(c.valor_icms + c.valor_ipi), 0) as v")
            ->pluck('v', 'escopo_id');

        $pisCofins = (clone $base())
            ->join('efd_notas_itens as i', 'i.efd_nota_id', '=', 'n.id')
            ->groupBy("n.{$coluna}")
            ->selectRaw("n.{$coluna} as escopo_id, COALESCE(SUM(COALESCE(i.valor_pis,0) + COALESCE(i.valor_cofins,0)), 0) as v")
            ->pluck('v', 'escopo_id');

        $out = [];
        foreach ($ids as $id) {
            $total = round((float) ($icmsIpi[$id] ?? 0) + (float) ($pisCofins[$id] ?? 0), 2);
            if ($total > 0) {
                $out[$id] = $total;
            }
        }

        return $out;
    }

    private function assertColuna(string $coluna): void
    {
        if (! in_array($coluna, self::COLUNAS, true)) {
            throw new \InvalidArgumentException("Coluna de escopo inválida: {$coluna}");
        }
    }
}
