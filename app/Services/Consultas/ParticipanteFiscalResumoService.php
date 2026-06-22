<?php

namespace App\Services\Consultas;

use Illuminate\Support\Facades\DB;

/**
 * Resumo fiscal/relacional de um participante (contraparte) a partir das notas EFD.
 *
 * Fonte: efd_notas com origem_arquivo='fiscal' (evita a dobra fiscal × contribuições)
 * e cancelada=false. tipo_operacao='entrada' ⇒ o CNPJ é fornecedor da empresa
 * (cliente_id); 'saida' ⇒ é cliente da empresa.
 */
class ParticipanteFiscalResumoService
{
    /**
     * @param  array<int, int>  $participanteIds
     * @return array<int, array<string, mixed>> keyed por participante_id
     */
    public function paraParticipantes(int $userId, array $participanteIds, bool $comCfops = false): array
    {
        $ids = array_values(array_unique(array_filter($participanteIds)));
        if ($ids === []) {
            return [];
        }

        $linhas = DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereIn('participante_id', $ids)
            ->groupBy('participante_id', 'cliente_id', 'tipo_operacao')
            ->selectRaw('participante_id, cliente_id, tipo_operacao,
                COUNT(*) as qtd, SUM(valor_total) as valor,
                MIN(data_emissao) as primeira, MAX(data_emissao) as ultima')
            ->get();

        if ($linhas->isEmpty()) {
            return [];
        }

        $empresaIds = $linhas->pluck('cliente_id')->unique()->all();
        $empresas = DB::table('clientes')
            ->where('user_id', $userId)
            ->whereIn('id', $empresaIds)
            ->get(['id', 'razao_social', 'is_empresa_propria'])
            ->keyBy('id');

        $cfopsPorParticipante = $comCfops ? $this->topCfops($userId, $ids) : [];

        $acc = [];
        foreach ($linhas as $l) {
            $pid = (int) $l->participante_id;
            $eid = (int) $l->cliente_id;
            $acc[$pid] ??= [
                'total_comprado' => 0.0, 'total_vendido' => 0.0,
                'qtd_entrada' => 0, 'qtd_saida' => 0,
                'primeira_nota' => null, 'ultima_nota' => null,
                'empresas' => [],
            ];
            $acc[$pid]['empresas'][$eid] ??= [
                'empresa_id' => $eid,
                'empresa_nome' => $empresas[$eid]->razao_social ?? '—',
                'is_empresa_propria' => (bool) ($empresas[$eid]->is_empresa_propria ?? false),
                'valor_entrada' => 0.0, 'valor_saida' => 0.0, 'qtd' => 0,
            ];

            $valor = (float) $l->valor;
            $qtd = (int) $l->qtd;
            if ($l->tipo_operacao === 'entrada') {
                $acc[$pid]['total_comprado'] += $valor;
                $acc[$pid]['qtd_entrada'] += $qtd;
                $acc[$pid]['empresas'][$eid]['valor_entrada'] += $valor;
            } else {
                $acc[$pid]['total_vendido'] += $valor;
                $acc[$pid]['qtd_saida'] += $qtd;
                $acc[$pid]['empresas'][$eid]['valor_saida'] += $valor;
            }
            $acc[$pid]['empresas'][$eid]['qtd'] += $qtd;
            $acc[$pid]['primeira_nota'] = $this->menorData($acc[$pid]['primeira_nota'], $l->primeira);
            $acc[$pid]['ultima_nota'] = $this->maiorData($acc[$pid]['ultima_nota'], $l->ultima);
        }

        $out = [];
        foreach ($acc as $pid => $a) {
            $relacionamentos = array_map(function (array $e) {
                $e['papel'] = $this->papelDe($e['valor_entrada'] > 0, $e['valor_saida'] > 0);

                return $e;
            }, array_values($a['empresas']));

            $out[$pid] = [
                'papel' => $this->papelDe($a['total_comprado'] > 0, $a['total_vendido'] > 0),
                'total_comprado' => round($a['total_comprado'], 2),
                'total_vendido' => round($a['total_vendido'], 2),
                'qtd_entrada' => $a['qtd_entrada'],
                'qtd_saida' => $a['qtd_saida'],
                'qtd_notas' => $a['qtd_entrada'] + $a['qtd_saida'],
                'primeira_nota' => $a['primeira_nota'],
                'ultima_nota' => $a['ultima_nota'],
                'empresas_count' => count($a['empresas']),
                'relacionamentos' => $relacionamentos,
                'top_cfops' => $cfopsPorParticipante[$pid] ?? [],
            ];
        }

        return $out;
    }

    private function papelDe(bool $temEntrada, bool $temSaida): string
    {
        return match (true) {
            $temEntrada && $temSaida => 'ambos',
            $temEntrada => 'fornecedor',
            default => 'cliente',
        };
    }

    /**
     * Papel fiscal de TODOS os participantes do usuário com movimentação (1 query),
     * para filtrar a lista por relação. Participantes sem nota fiscal ficam ausentes
     * do retorno (o caller trata como "sem movimentação").
     *
     * @return array<int, string> [participante_id => 'fornecedor'|'cliente'|'ambos']
     */
    public function papelPorParticipante(int $userId): array
    {
        return DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereNotNull('participante_id')
            ->groupBy('participante_id')
            ->selectRaw("participante_id,
                bool_or(tipo_operacao = 'entrada') as tem_entrada,
                bool_or(tipo_operacao = 'saida') as tem_saida")
            ->get()
            ->mapWithKeys(fn ($r) => [
                (int) $r->participante_id => $this->papelDe((bool) $r->tem_entrada, (bool) $r->tem_saida),
            ])
            ->all();
    }

    /**
     * Top 3 CFOPs por participante a partir do C190 consolidado (mais leve que itens).
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, array{cfop:int, qtd:int}>> keyed por participante_id
     */
    private function topCfops(int $userId, array $ids): array
    {
        $linhas = DB::table('efd_notas_consolidados as c')
            ->join('efd_notas as n', 'n.id', '=', 'c.efd_nota_id')
            ->where('n.user_id', $userId)
            ->where('n.origem_arquivo', 'fiscal')
            ->where('n.cancelada', false)
            ->whereIn('n.participante_id', $ids)
            ->whereNotNull('c.cfop')
            ->groupBy('n.participante_id', 'c.cfop')
            ->selectRaw('n.participante_id, c.cfop, COUNT(*) as qtd')
            ->get();

        return $linhas
            ->groupBy('participante_id')
            ->map(fn ($g) => $g->sortByDesc('qtd')->take(3)
                ->map(fn ($r) => ['cfop' => (int) $r->cfop, 'qtd' => (int) $r->qtd])
                ->values()->all())
            ->all();
    }

    private function menorData(?string $atual, ?string $nova): ?string
    {
        $nova = $nova ? substr((string) $nova, 0, 10) : null;
        if ($nova === null) {
            return $atual;
        }

        return $atual === null || $nova < $atual ? $nova : $atual;
    }

    private function maiorData(?string $atual, ?string $nova): ?string
    {
        $nova = $nova ? substr((string) $nova, 0, 10) : null;
        if ($nova === null) {
            return $atual;
        }

        return $atual === null || $nova > $atual ? $nova : $atual;
    }
}
