<?php

namespace App\Services\Participantes;

use App\Models\EfdNota;
use App\Models\Participante;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agrega movimentações fiscais (EFD) de UM participante, escopado por user_id.
 * Exclui notas canceladas. XML fora do MVP (depende de xml_notas_itens).
 */
final class ParticipanteMovimentacaoService
{
    /** Notas EFD do participante, não canceladas. */
    private function notasQuery(Participante $p): \Illuminate\Database\Eloquent\Builder
    {
        return EfdNota::query()
            ->where('user_id', $p->user_id)
            ->where('participante_id', $p->id)
            ->where(fn ($q) => $q->whereNull('cancelada')->orWhere('cancelada', false));
    }

    /** Itens das notas EFD do participante, não canceladas (join defensivo). */
    private function itensQuery(Participante $p): Builder
    {
        return DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $p->user_id)
            ->where('n.participante_id', $p->id)
            ->where(fn ($q) => $q->whereNull('n.cancelada')->orWhere('n.cancelada', false));
    }

    public function kpis(Participante $p): array
    {
        $rows = $this->notasQuery($p)
            ->selectRaw('tipo_operacao, count(*) as qtd, coalesce(sum(valor_total),0) as valor')
            ->groupBy('tipo_operacao')
            ->get()
            ->keyBy('tipo_operacao');

        $entQtd = (int) ($rows['entrada']->qtd ?? 0);
        $entVal = (float) ($rows['entrada']->valor ?? 0);
        $saiQtd = (int) ($rows['saida']->qtd ?? 0);
        $saiVal = (float) ($rows['saida']->valor ?? 0);

        $periodo = $this->notasQuery($p)
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

    public function porCompetencia(Participante $p): array
    {
        $rows = $this->notasQuery($p)
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

    public function porCfop(Participante $p, int $limite = 10): array
    {
        return $this->itensQuery($p)
            ->selectRaw('i.cfop as cfop, count(*) as qtd, coalesce(sum(i.valor_total),0) as valor')
            ->groupBy('i.cfop')
            ->orderByDesc('valor')
            ->limit($limite)
            ->get()
            ->map(fn ($r) => ['cfop' => (string) $r->cfop, 'qtd' => (int) $r->qtd, 'valor' => (float) $r->valor])
            ->all();
    }

    public function kpisEResumoParaPreview(Participante $p): array
    {
        return [
            'kpis' => $this->kpis($p),
            'por_competencia' => $this->porCompetencia($p),
            'por_cfop' => $this->porCfop($p, 5),
        ];
    }

    public function porCst(Participante $p): array
    {
        return $this->itensQuery($p)
            ->selectRaw('i.cst_icms as cst, count(*) as qtd, coalesce(sum(i.valor_total),0) as valor')
            ->groupBy('i.cst_icms')
            ->orderByDesc('valor')
            ->get()
            ->map(fn ($r) => ['cst' => (string) $r->cst, 'qtd' => (int) $r->qtd, 'valor' => (float) $r->valor])
            ->all();
    }

    public function impostos(Participante $p): array
    {
        $r = $this->itensQuery($p)
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
