<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use App\Models\EfdNota;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agrega movimentações fiscais (EFD) de UM cliente, escopado por user_id + cliente_id.
 * Exclui notas canceladas. Espelha ParticipanteMovimentacaoService (que é participante_id).
 * Dívida futura: extrair core MovimentacaoEfdQuery(userId, coluna, id) quando houver 3º consumidor.
 */
final class ClienteMovimentacaoService
{
    private function notasQuery(Cliente $c): \Illuminate\Database\Eloquent\Builder
    {
        return EfdNota::query()
            ->where('user_id', $c->user_id)
            ->where('cliente_id', $c->id)
            ->where(fn ($q) => $q->whereNull('cancelada')->orWhere('cancelada', false));
    }

    private function itensQuery(Cliente $c): Builder
    {
        return DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $c->user_id)
            ->where('n.cliente_id', $c->id)
            ->where(fn ($q) => $q->whereNull('n.cancelada')->orWhere('n.cancelada', false));
    }

    public function kpis(Cliente $c): array
    {
        $rows = $this->notasQuery($c)
            ->selectRaw('tipo_operacao, count(*) as qtd, coalesce(sum(valor_total),0) as valor')
            ->groupBy('tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        $entQtd = (int) ($rows['entrada']->qtd ?? 0);
        $entVal = (float) ($rows['entrada']->valor ?? 0);
        $saiQtd = (int) ($rows['saida']->qtd ?? 0);
        $saiVal = (float) ($rows['saida']->valor ?? 0);

        $periodo = $this->notasQuery($c)
            ->whereNotNull('data_emissao')
            ->selectRaw("min(to_char(data_emissao,'YYYY-MM')) as ini, max(to_char(data_emissao,'YYYY-MM')) as fim")
            ->first();

        return [
            'total_notas' => $entQtd + $saiQtd,
            'valor_movimentado' => $entVal + $saiVal,
            'entradas_qtd' => $entQtd,
            'entradas_valor' => $entVal,
            'saidas_qtd' => $saiQtd,
            'saidas_valor' => $saiVal,
            'periodo_inicio' => $periodo->ini ?? null,
            'periodo_fim' => $periodo->fim ?? null,
        ];
    }

    public function porCompetencia(Cliente $c): array
    {
        $rows = $this->notasQuery($c)
            ->whereNotNull('data_emissao')
            ->selectRaw("to_char(data_emissao,'YYYY-MM') as comp, tipo_operacao, coalesce(sum(valor_total),0) as v")
            ->groupBy('comp', 'tipo_operacao')
            ->orderBy('comp')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->comp] ??= ['competencia' => $r->comp, 'entrada' => 0.0, 'saida' => 0.0];
            if ($r->tipo_operacao === 'entrada') {
                $map[$r->comp]['entrada'] = (float) $r->v;
            } elseif ($r->tipo_operacao === 'saida') {
                $map[$r->comp]['saida'] = (float) $r->v;
            }
        }

        return array_values($map);
    }

    public function porCfop(Cliente $c, int $limite = 10): array
    {
        return $this->itensQuery($c)
            ->selectRaw('i.cfop as cfop, count(*) as qtd, coalesce(sum(i.valor_total),0) as valor')
            ->groupBy('i.cfop')
            ->orderByDesc('valor')
            ->limit($limite)
            ->get()
            ->map(fn ($r) => ['cfop' => (string) $r->cfop, 'qtd' => (int) $r->qtd, 'valor' => (float) $r->valor])
            ->all();
    }

    public function kpisEResumoParaPreview(Cliente $c): array
    {
        return [
            'kpis' => $this->kpis($c),
            'por_competencia' => $this->porCompetencia($c),
            'por_cfop' => $this->porCfop($c, 5),
        ];
    }

    public function porCst(Cliente $c): array
    {
        return $this->itensQuery($c)
            ->selectRaw('i.cst_icms as cst, count(*) as qtd, coalesce(sum(i.valor_total),0) as valor')
            ->groupBy('i.cst_icms')
            ->orderByDesc('valor')
            ->get()
            ->map(fn ($r) => ['cst' => (string) $r->cst, 'qtd' => (int) $r->qtd, 'valor' => (float) $r->valor])
            ->all();
    }

    public function impostos(Cliente $c): array
    {
        $r = $this->itensQuery($c)
            ->selectRaw('
                coalesce(sum(i.valor_icms),0) as icms,
                coalesce(sum(i.valor_pis),0) as pis,
                coalesce(sum(i.valor_cofins),0) as cofins,
                coalesce(sum(i.aliquota_icms * i.valor_total),0) as aliq_peso,
                coalesce(sum(i.valor_total),0) as base
            ')
            ->first();

        $base = (float) ($r->base ?? 0);

        return [
            'icms' => (float) ($r->icms ?? 0),
            'pis' => (float) ($r->pis ?? 0),
            'cofins' => (float) ($r->cofins ?? 0),
            'aliquota_icms_media' => $base > 0 ? round(((float) $r->aliq_peso) / $base, 2) : 0.0,
        ];
    }
}
