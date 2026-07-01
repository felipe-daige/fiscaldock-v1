<?php

namespace App\Services\Consultas;

use App\Models\ConsultaResultado;
use App\Services\Consultas\Fiscal\AgregacaoFiscalHelpers;
use App\Services\Consultas\Fiscal\TopMovimentacaoQuery;
use App\Support\CertidaoBadge;
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
    use AgregacaoFiscalHelpers;

    public function __construct(private TopMovimentacaoQuery $top) {}

    /**
     * @param  array<int, int>  $participanteIds
     * @return array<int, array<string, mixed>> keyed por participante_id
     */
    public function paraParticipantes(int $userId, array $participanteIds, bool $comCfops = false, bool $comProdutos = false, bool $comNotas = false): array
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

        $cfopsPorParticipante = $comCfops ? $this->top->cfops($userId, 'participante_id', $ids, $this->panoramaMaximo()) : [];
        $cfopsContraPorParticipante = $comCfops ? $this->top->cfopsPorContraparte($userId, 'participante_id', $ids, $this->cfopsPorContraparteNum()) : [];
        $produtosPorParticipante = $comProdutos ? $this->top->produtos($userId, 'participante_id', $ids, $this->panoramaMaximo()) : [];
        $notasPorParticipante = $comNotas ? $this->top->notas($userId, 'participante_id', $ids, $this->panoramaMaximo()) : [];

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
            $relacionamentos = array_map(function (array $e) use ($pid, $cfopsContraPorParticipante) {
                $e['papel'] = $this->papelDe($e['valor_entrada'] > 0, $e['valor_saida'] > 0);
                $e['nome'] = $e['empresa_nome'];
                $e['is_propria'] = $e['is_empresa_propria'];
                $e['top_cfops'] = $cfopsContraPorParticipante[$pid][(int) $e['empresa_id']] ?? [];

                return $e;
            }, array_values($a['empresas']));

            $out[$pid] = [
                'perspectiva' => 'participante',
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
                'relacionamentos_titulo' => 'Por empresa',
                'top_cfops' => $cfopsPorParticipante[$pid] ?? [],
                'top_produtos' => $produtosPorParticipante[$pid] ?? [],
                'top_notas_entrada' => $notasPorParticipante[$pid]['entrada'] ?? [],
                'top_notas_saida' => $notasPorParticipante[$pid]['saida'] ?? [],
            ];
        }

        return $out;
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
     * Valor total movimentado (compras + vendas) por participante, 1 query,
     * para filtrar a lista por faixa de valor. Participantes sem nota ficam
     * ausentes do retorno (tratados como "sem movimentação", fora do filtro).
     *
     * @return array<int, float> [participante_id => valor_total]
     */
    public function valorMovimentadoPorParticipante(int $userId): array
    {
        return DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereNotNull('participante_id')
            ->groupBy('participante_id')
            ->selectRaw('participante_id, COALESCE(SUM(valor_total), 0) as valor')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->participante_id => (float) $r->valor])
            ->all();
    }

    /**
     * Quantidade de notas (entradas + saídas) por participante, 1 query, para
     * filtrar a lista por volume. Participantes sem nota ficam ausentes do
     * retorno (tratados como "sem movimentação", fora do filtro).
     *
     * @return array<int, int> [participante_id => qtd_notas]
     */
    public function qtdNotasPorParticipante(int $userId): array
    {
        return DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereNotNull('participante_id')
            ->groupBy('participante_id')
            ->selectRaw('participante_id, COUNT(*) as qtd')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->participante_id => (int) $r->qtd])
            ->all();
    }

    /**
     * Papel + valor + qtd por participante numa única query, para servir os
     * filtros de relação/valor/qtd e a ordenação por valor/qtd sem 3 scans
     * separados de efd_notas. Participantes sem nota ficam ausentes.
     *
     * @return array<int, array{papel: string, valor: float, qtd: int}>
     */
    public function resumoMovimentacao(int $userId): array
    {
        return DB::table('efd_notas')
            ->where('user_id', $userId)
            ->where('origem_arquivo', 'fiscal')
            ->where('cancelada', false)
            ->whereNotNull('participante_id')
            ->groupBy('participante_id')
            ->selectRaw("participante_id,
                bool_or(tipo_operacao = 'entrada') as tem_entrada,
                bool_or(tipo_operacao = 'saida') as tem_saida,
                COALESCE(SUM(valor_total), 0) as valor,
                COUNT(*) as qtd")
            ->get()
            ->mapWithKeys(fn ($r) => [
                (int) $r->participante_id => [
                    'papel' => $this->papelDe((bool) $r->tem_entrada, (bool) $r->tem_saida),
                    'valor' => (float) $r->valor,
                    'qtd' => (int) $r->qtd,
                ],
            ])
            ->all();
    }

    /**
     * Classificação de regularidade da ÚLTIMA consulta sucesso por participante,
     * pra filtrar a lista. Base = CND Federal via CertidaoBadge (canônico:
     * 611/indeterminado tem precedência). Participante sem consulta sucesso fica
     * ausente do retorno (o caller trata como "não consultado").
     *
     * @return array<int, string> [participante_id => 'regular'|'irregular'|'indeterminada']
     */
    public function regularidadePorParticipante(int $userId): array
    {
        $ultimos = ConsultaResultado::query()
            ->whereHas('participante', fn ($q) => $q->where('user_id', $userId))
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderBy('consultado_em', 'desc')
            ->get()
            ->unique('participante_id');

        $out = [];
        foreach ($ultimos as $resultado) {
            $cnd = $resultado->getCndFederal();
            if ($cnd === null) {
                continue;
            }
            $classe = CertidaoBadge::classificar($cnd, true);
            // Regular/Irregular explícitos; qualquer outro rótulo (Indeterminada,
            // Indisponível, Não encontrada, 611) cai em "indeterminada" — bucket
            // de triagem "precisa olhar".
            $out[(int) $resultado->participante_id] = match ($classe['label']) {
                'Regular' => 'regular',
                'Irregular' => 'irregular',
                default => 'indeterminada',
            };
        }

        return $out;
    }
}
