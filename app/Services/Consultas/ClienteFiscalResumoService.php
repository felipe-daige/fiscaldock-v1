<?php

namespace App\Services\Consultas;

use App\Services\Consultas\Fiscal\AgregacaoFiscalHelpers;
use App\Services\Consultas\Fiscal\TopMovimentacaoQuery;
use Illuminate\Support\Facades\DB;

/**
 * Panorama fiscal do LEDGER PRÓPRIO de um cliente (empresa do contador), a partir
 * das notas EFD onde cliente_id = X. entrada = compra da empresa; saida = venda.
 * Emite o shape único compartilhado com ParticipanteFiscalResumoService.
 */
class ClienteFiscalResumoService
{
    use AgregacaoFiscalHelpers;

    public function __construct(private TopMovimentacaoQuery $top) {}

    /**
     * @param  array<int, int>  $clienteIds
     * @return array<int, array<string, mixed>> keyed por cliente_id
     */
    public function paraClientes(int $userId, array $clienteIds): array
    {
        $ids = array_values(array_unique(array_filter($clienteIds)));
        if ($ids === []) {
            return [];
        }

        $volume = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereIn('cliente_id', $ids)
            ->groupBy('cliente_id', 'tipo_operacao')
            ->selectRaw('cliente_id, tipo_operacao, COUNT(*) as qtd,
                COALESCE(SUM(valor_total), 0) as valor,
                MIN(data_emissao) as primeira, MAX(data_emissao) as ultima')
            ->get();

        if ($volume->isEmpty()) {
            return [];
        }

        $contraRows = DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->whereIn('n.cliente_id', $ids)
            ->whereNotNull('n.participante_id')
            ->groupBy('n.cliente_id', 'n.participante_id', 'p.razao_social', 'n.tipo_operacao')
            ->selectRaw('n.cliente_id, n.participante_id, p.razao_social,
                n.tipo_operacao, COALESCE(SUM(n.valor_total), 0) as valor')
            ->get();

        $cfops = $this->top->cfops($userId, 'cliente_id', $ids, $this->panoramaVisivel());
        $produtos = $this->top->produtos($userId, 'cliente_id', $ids, $this->panoramaMaximo());

        $acc = [];
        foreach ($volume as $v) {
            $cid = (int) $v->cliente_id;
            $acc[$cid] ??= [
                'total_comprado' => 0.0, 'total_vendido' => 0.0,
                'qtd_entrada' => 0, 'qtd_saida' => 0,
                'primeira_nota' => null, 'ultima_nota' => null,
            ];
            if ($v->tipo_operacao === 'entrada') {
                $acc[$cid]['total_comprado'] += (float) $v->valor;
                $acc[$cid]['qtd_entrada'] += (int) $v->qtd;
            } else {
                $acc[$cid]['total_vendido'] += (float) $v->valor;
                $acc[$cid]['qtd_saida'] += (int) $v->qtd;
            }
            $acc[$cid]['primeira_nota'] = $this->menorData($acc[$cid]['primeira_nota'], $v->primeira);
            $acc[$cid]['ultima_nota'] = $this->maiorData($acc[$cid]['ultima_nota'], $v->ultima);
        }

        $contra = [];
        foreach ($contraRows as $r) {
            $cid = (int) $r->cliente_id;
            $pid = (int) $r->participante_id;
            $contra[$cid][$pid] ??= [
                'nome' => $r->razao_social ?? '—', 'is_propria' => false,
                'valor_entrada' => 0.0, 'valor_saida' => 0.0,
            ];
            if ($r->tipo_operacao === 'entrada') {
                $contra[$cid][$pid]['valor_entrada'] += (float) $r->valor;
            } else {
                $contra[$cid][$pid]['valor_saida'] += (float) $r->valor;
            }
        }

        $out = [];
        foreach ($acc as $cid => $a) {
            $rels = collect($contra[$cid] ?? [])
                ->map(fn (array $e) => [
                    'nome' => $e['nome'],
                    'is_propria' => false,
                    'papel' => $this->papelDe($e['valor_entrada'] > 0, $e['valor_saida'] > 0),
                    'valor_entrada' => round($e['valor_entrada'], 2),
                    'valor_saida' => round($e['valor_saida'], 2),
                ])
                ->sortByDesc(fn ($e) => $e['valor_entrada'] + $e['valor_saida'])
                ->take($this->panoramaMaximo())
                ->values()
                ->all();

            $out[$cid] = [
                'perspectiva' => 'cliente',
                'papel' => null,
                'total_comprado' => round($a['total_comprado'], 2),
                'total_vendido' => round($a['total_vendido'], 2),
                'qtd_entrada' => $a['qtd_entrada'],
                'qtd_saida' => $a['qtd_saida'],
                'qtd_notas' => $a['qtd_entrada'] + $a['qtd_saida'],
                'primeira_nota' => $a['primeira_nota'],
                'ultima_nota' => $a['ultima_nota'],
                'top_cfops' => $cfops[$cid] ?? [],
                'top_produtos' => $produtos[$cid] ?? [],
                'relacionamentos' => $rels,
                'relacionamentos_titulo' => 'Principais contrapartes',
                'empresas_count' => count($contra[$cid] ?? []),
            ];
        }

        return $out;
    }
}
