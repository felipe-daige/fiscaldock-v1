<?php

namespace App\Services;

use App\Models\EfdNota;
use App\Models\Participante;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotasFiscaisAlertService
{
    public function detectar(int $userId, array $filtros): array
    {
        $baseNotas = EfdNota::where('user_id', $userId);
        $this->aplicarFiltros($baseNotas, $filtros);

        $alertas = [];

        // Alertas gratuitos
        $detectors = [
            'detectarDuplicadas',
            'detectarValorZerado',
            'detectarSemCnpj',
            'detectarCfopsInconsistentes',
            'detectarSemItens',
            'detectarGapTemporal',
            'detectarPisCofinsIncompleto',
        ];

        foreach ($detectors as $method) {
            try {
                $alerta = $this->$method(clone $baseNotas, $filtros);
                if ($alerta !== null) {
                    $alertas[] = $alerta;
                }
            } catch (\Throwable $e) {
                // Skip failed alert, log for debugging
                \Log::warning("Alerta {$method} falhou: " . $e->getMessage());
            }
        }

        // Alertas pagos (stubs)
        $alertas = array_merge($alertas, $this->alertasPagos(clone $baseNotas));

        $resumo = ['alta' => 0, 'media' => 0, 'baixa' => 0, 'total' => 0];
        foreach ($alertas as $a) {
            if ($a['total_afetados'] > 0 || ($a['tipo'] === 'paid' && ! $a['disponivel'])) {
                $resumo[$a['severidade']]++;
                $resumo['total']++;
            }
        }

        // Remove alertas gratuitos com zero afetados
        $alertas = array_values(array_filter($alertas, function ($a) {
            return $a['total_afetados'] > 0 || ($a['tipo'] === 'paid' && ! $a['disponivel']);
        }));

        return [
            'resumo' => $resumo,
            'alertas' => $alertas,
        ];
    }

    private function detectarDuplicadas($base): ?array
    {
        $grupos = (clone $base)
            ->select('numero', 'serie', 'participante_id', 'modelo')
            ->selectRaw('COUNT(*) as qtd, MIN(id) as nota_id')
            ->whereNotNull('numero')
            ->groupBy('numero', 'serie', 'participante_id', 'modelo')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('qtd')
            ->limit(50)
            ->get();

        if ($grupos->isEmpty()) {
            return null;
        }

        $totalAfetados = (clone $base)
            ->select('numero', 'serie', 'participante_id', 'modelo')
            ->whereNotNull('numero')
            ->groupBy('numero', 'serie', 'participante_id', 'modelo')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Resolver nomes dos participantes
        $partIds = $grupos->pluck('participante_id')->filter()->unique()->values();
        $participantes = Participante::whereIn('id', $partIds)
            ->select('id', 'razao_social', 'documento as documento')
            ->get()
            ->keyBy('id');

        $detalhes = $grupos->map(function ($g) use ($participantes) {
            $part = $participantes->get($g->participante_id);

            return [
                'nota_id' => $g->nota_id,
                'numero' => $g->numero,
                'serie' => $g->serie,
                'modelo' => $g->modelo,
                'participante' => $part ? $part->razao_social : 'N/A',
                'participante_id' => $g->participante_id,
                'qtd' => (int) $g->qtd,
            ];
        })->values()->all();

        return [
            'id' => 'notas_duplicadas',
            'titulo' => 'Notas duplicadas',
            'severidade' => 'alta',
            'descricao' => $totalAfetados . ' grupo(s) de notas com mesma combinacao de numero, serie, participante e modelo.',
            'total_afetados' => $totalAfetados,
            'detalhes' => $detalhes,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarValorZerado($base): ?array
    {
        $totalAfetados = (clone $base)
            ->where(function ($q) {
                $q->where('valor_total', 0)->orWhereNull('valor_total');
            })
            ->count();

        if ($totalAfetados === 0) {
            return null;
        }

        $notas = (clone $base)
            ->where(function ($q) {
                $q->where('valor_total', 0)->orWhereNull('valor_total');
            })
            ->select('id', 'numero', 'serie', 'modelo', 'data_emissao', 'valor_total', 'participante_id')
            ->orderByDesc('data_emissao')
            ->limit(50)
            ->get();

        $partIds = $notas->pluck('participante_id')->filter()->unique()->values();
        $participantes = Participante::whereIn('id', $partIds)
            ->select('id', 'razao_social')
            ->get()
            ->keyBy('id');

        $detalhes = $notas->map(fn ($n) => [
                'nota_id' => $n->id,
                'numero' => $n->numero,
                'serie' => $n->serie,
                'modelo' => $n->modelo,
                'data_emissao' => $n->data_emissao?->format('d/m/Y'),
                'valor_total' => (float) $n->valor_total,
                'participante_id' => $n->participante_id,
                'participante' => $participantes->get($n->participante_id)?->razao_social ?? 'N/A',
            ])
            ->values()
            ->all();

        return [
            'id' => 'notas_valor_zerado',
            'titulo' => 'Notas com valor zerado',
            'severidade' => 'media',
            'descricao' => $totalAfetados . ' nota(s) com valor total igual a zero ou nao informado.',
            'total_afetados' => $totalAfetados,
            'detalhes' => $detalhes,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarSemCnpj($base): ?array
    {
        $grupos = (clone $base)
            ->whereNotNull('participante_id')
            ->whereHas('participante', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('documento')->orWhere('documento', '');
                });
            })
            ->select('participante_id')
            ->selectRaw('COUNT(*) as total_notas')
            ->groupBy('participante_id')
            ->limit(50)
            ->get();

        if ($grupos->isEmpty()) {
            return null;
        }

        $totalAfetados = $grupos->sum('total_notas');

        $partIds = $grupos->pluck('participante_id')->filter()->unique();
        $participantes = Participante::whereIn('id', $partIds)
            ->select('id', 'razao_social', 'documento as documento')
            ->get()
            ->keyBy('id');

        $detalhes = $grupos->map(function ($g) use ($participantes) {
            $part = $participantes->get($g->participante_id);

            return [
                'participante_id' => $g->participante_id,
                'razao_social' => $part ? $part->razao_social : 'N/A',
                'documento' => $part ? ($part->documento ?: 'Vazio') : 'N/A',
                'total_notas' => (int) $g->total_notas,
            ];
        })->values()->all();

        return [
            'id' => 'participantes_sem_cnpj',
            'titulo' => 'Participantes sem CNPJ/CPF',
            'severidade' => 'media',
            'descricao' => count($detalhes) . ' participante(s) sem documento fiscal vinculado a ' . $totalAfetados . ' nota(s).',
            'total_afetados' => $totalAfetados,
            'detalhes' => $detalhes,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarCfopsInconsistentes($base): ?array
    {
        $notaIdsSub = (clone $base)->select('id');

        $inconsistentes = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', $notaIdsSub)
            ->whereNotNull('i.cfop')
            ->where('i.cfop', '!=', 0)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('n.tipo_operacao', 'entrada')
                        ->whereRaw("LEFT(CAST(i.cfop AS TEXT), 1) IN ('5','6','7')");
                })->orWhere(function ($qq) {
                    $qq->where('n.tipo_operacao', 'saida')
                        ->whereRaw("LEFT(CAST(i.cfop AS TEXT), 1) IN ('1','2','3')");
                });
            })
            ->select('n.id as nota_id', 'n.numero', 'n.serie', 'n.tipo_operacao', 'i.cfop')
            ->distinct()
            ->limit(50)
            ->get();

        if ($inconsistentes->isEmpty()) {
            return null;
        }

        $totalAfetados = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->whereIn('i.efd_nota_id', $notaIdsSub)
            ->whereNotNull('i.cfop')
            ->where('i.cfop', '!=', 0)
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('n.tipo_operacao', 'entrada')
                        ->whereRaw("LEFT(CAST(i.cfop AS TEXT), 1) IN ('5','6','7')");
                })->orWhere(function ($qq) {
                    $qq->where('n.tipo_operacao', 'saida')
                        ->whereRaw("LEFT(CAST(i.cfop AS TEXT), 1) IN ('1','2','3')");
                });
            })
            ->selectRaw('COUNT(DISTINCT n.id) as total')
            ->value('total');

        $detalhes = $inconsistentes->map(fn ($r) => [
            'nota_id' => $r->nota_id,
            'numero' => $r->numero,
            'serie' => $r->serie,
            'tipo_operacao' => $r->tipo_operacao === 'entrada' ? 'Entrada' : 'Saída',
            'cfop' => $r->cfop,
        ])->values()->all();

        return [
            'id' => 'cfops_inconsistentes',
            'titulo' => 'CFOPs inconsistentes com operacao',
            'severidade' => 'media',
            'descricao' => $totalAfetados . ' nota(s) com CFOP incompativel com o tipo de operacao (entrada/saida).',
            'total_afetados' => (int) $totalAfetados,
            'detalhes' => $detalhes,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarSemItens($base): ?array
    {
        // CT-e (modelo 57) não possui itens detalhados no SPED (usa D190 totalizador, não D170)
        $totalAfetados = (clone $base)->whereDoesntHave('itens')->where('modelo', '!=', '57')->count();

        if ($totalAfetados === 0) {
            return null;
        }

        $notas = (clone $base)
            ->whereDoesntHave('itens')
            ->where('modelo', '!=', '57')
            ->select('id', 'numero', 'serie', 'modelo', 'data_emissao', 'valor_total', 'participante_id')
            ->orderByDesc('data_emissao')
            ->limit(50)
            ->get();

        $partIds = $notas->pluck('participante_id')->filter()->unique()->values();
        $participantes = Participante::whereIn('id', $partIds)
            ->select('id', 'razao_social')
            ->get()
            ->keyBy('id');

        $detalhes = $notas->map(fn ($n) => [
                'nota_id' => $n->id,
                'numero' => $n->numero,
                'serie' => $n->serie,
                'modelo' => $n->modelo,
                'data_emissao' => $n->data_emissao?->format('d/m/Y'),
                'valor_total' => (float) $n->valor_total,
                'participante_id' => $n->participante_id,
                'participante' => $participantes->get($n->participante_id)?->razao_social ?? 'N/A',
            ])
            ->values()
            ->all();

        return [
            'id' => 'notas_sem_itens',
            'titulo' => 'Notas sem itens',
            'severidade' => 'baixa',
            'descricao' => $totalAfetados . ' nota(s) sem nenhum item registrado. Pode indicar importacao incompleta.',
            'total_afetados' => $totalAfetados,
            'detalhes' => $detalhes,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarGapTemporal($base, array $filtros): ?array
    {
        $mesesComNotas = (clone $base)
            ->selectRaw("DISTINCT TO_CHAR(data_emissao, 'YYYY-MM') as mes")
            ->pluck('mes')
            ->toArray();

        if (empty($mesesComNotas)) {
            return null;
        }

        $range = $this->gerarRangeMeses($filtros, $mesesComNotas);

        if (empty($range)) {
            return null;
        }

        $gaps = array_values(array_diff($range, $mesesComNotas));

        if (empty($gaps)) {
            return null;
        }

        return [
            'id' => 'gap_temporal',
            'titulo' => 'Lacunas temporais',
            'severidade' => 'media',
            'descricao' => count($gaps) . ' mes(es) sem nenhuma nota no periodo. Pode indicar importacao incompleta.',
            'total_afetados' => count($gaps),
            'detalhes' => $gaps,
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function detectarPisCofinsIncompleto($base): ?array
    {
        $notaIdsContrib = (clone $base)->where('origem_arquivo', 'contribuicoes')->select('id');

        $stats = DB::table('efd_notas_itens')
            ->whereIn('efd_nota_id', $notaIdsContrib)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN (valor_pis IS NULL OR valor_pis = 0) AND (valor_cofins IS NULL OR valor_cofins = 0) THEN 1 ELSE 0 END) as sem_tributo
            ")
            ->first();

        if (! $stats || $stats->total == 0) {
            return null;
        }

        $percentual = round(($stats->sem_tributo / $stats->total) * 100, 1);

        if ($percentual <= 50) {
            return null;
        }

        return [
            'id' => 'pis_cofins_incompleto',
            'titulo' => 'Dados PIS/COFINS incompletos',
            'severidade' => 'media',
            'descricao' => $percentual . '% dos itens de notas EFD PIS/COFINS estao sem valores de PIS e COFINS. '
                . 'Isso pode ser causado por um deslocamento de campos no registro C170 do arquivo SPED.',
            'total_afetados' => (int) $stats->sem_tributo,
            'detalhes' => [
                'percentual' => $percentual,
                'total_itens' => (int) $stats->total,
                'itens_sem_tributo' => (int) $stats->sem_tributo,
            ],
            'tipo' => 'free',
            'disponivel' => true,
        ];
    }

    private function alertasPagos($base): array
    {
        // Alerta 8: Situação cadastral irregular (query real, mas marcado como paid)
        $irregulares = Participante::whereIn('id', (clone $base)->whereNotNull('participante_id')->select('participante_id'))
            ->whereNotNull('situacao_cadastral')
            ->where('situacao_cadastral', '!=', '')
            ->where('situacao_cadastral', '!=', 'ATIVA')
            ->select('id', 'razao_social', 'documento as documento', 'situacao_cadastral')
            ->limit(50)
            ->get();

        $totalIrregulares = $irregulares->count();

        return [
            [
                'id' => 'situacao_cadastral_irregular',
                'titulo' => 'Participantes com situacao cadastral irregular',
                'severidade' => 'alta',
                'descricao' => $totalIrregulares > 0
                    ? $totalIrregulares . ' participante(s) com situacao cadastral diferente de ATIVA.'
                    : 'Verifique a situacao cadastral dos seus parceiros comerciais.',
                'total_afetados' => $totalIrregulares,
                'detalhes' => [],
                'tipo' => 'paid',
                'disponivel' => false,
            ],
            [
                'id' => 'ceis',
                'titulo' => 'Participantes no CEIS',
                'severidade' => 'alta',
                'descricao' => 'Verifique se seus parceiros comerciais constam no Cadastro de Empresas Inidôneas e Suspensas.',
                'total_afetados' => 0,
                'detalhes' => [],
                'tipo' => 'paid',
                'disponivel' => false,
            ],
            [
                'id' => 'ie_irregular',
                'titulo' => 'Inscricao Estadual irregular',
                'severidade' => 'alta',
                'descricao' => 'Verifique a regularidade da Inscricao Estadual dos participantes via SINTEGRA.',
                'total_afetados' => 0,
                'detalhes' => [],
                'tipo' => 'paid',
                'disponivel' => false,
            ],
        ];
    }

    private function aplicarFiltros($query, array $filtros): void
    {
        if (! empty($filtros['periodo_inicio'])) {
            $query->where('data_emissao', '>=', $filtros['periodo_inicio'] . '-01');
        }
        if (! empty($filtros['periodo_fim'])) {
            $fim = Carbon::parse($filtros['periodo_fim'] . '-01')->endOfMonth();
            $query->where('data_emissao', '<=', $fim);
        }
        if (! empty($filtros['tipo_efd']) && $filtros['tipo_efd'] !== 'todos') {
            $origemMap = [
                'EFD ICMS/IPI' => 'fiscal',
                'EFD PIS/COFINS' => 'contribuicoes',
            ];
            if (isset($origemMap[$filtros['tipo_efd']])) {
                $query->where('origem_arquivo', $origemMap[$filtros['tipo_efd']]);
            }
        }
        if (! empty($filtros['importacao_id'])) {
            $query->where('importacao_id', $filtros['importacao_id']);
        }
    }

    private function gerarRangeMeses(array $filtros, array $mesesComNotas): array
    {
        if (! empty($filtros['periodo_inicio']) && ! empty($filtros['periodo_fim'])) {
            $inicio = Carbon::parse($filtros['periodo_inicio'] . '-01');
            $fim = Carbon::parse($filtros['periodo_fim'] . '-01');
        } else {
            sort($mesesComNotas);
            $inicio = Carbon::parse(reset($mesesComNotas) . '-01');
            $fim = Carbon::parse(end($mesesComNotas) . '-01');
        }

        $meses = [];
        $cursor = $inicio->copy()->startOfMonth();

        while ($cursor->lte($fim)) {
            $meses[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $meses;
    }
}
