<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Participante;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertaCentralService
{
    public function __construct(
        private NotasFiscaisAlertService $notasFiscaisAlertService,
    ) {}

    /**
     * Recalcula todos os alertas para o usuário.
     */
    public function recalcular(int $userId, ?int $clienteId = null): array
    {
        $novos = 0;
        $atualizados = 0;
        $allHashes = [];

        // 1. Alertas de notas fiscais (7 detectores do NotasFiscaisAlertService)
        $resultado = $this->notasFiscaisAlertService->detectar($userId, []);

        foreach ($resultado['alertas'] as $alerta) {
            if (($alerta['tipo'] ?? '') === 'paid' || ($alerta['total_afetados'] ?? 0) <= 0) {
                continue;
            }

            $hash = hash('sha256', "$userId:{$alerta['id']}");
            $allHashes[] = $hash;

            $data = [
                'tipo' => $alerta['id'],
                'titulo' => $alerta['titulo'],
                'descricao' => $alerta['descricao'],
                'severidade' => $alerta['severidade'],
                'total_afetados' => $alerta['total_afetados'],
                'detalhes' => $alerta['detalhes'],
                'categoria' => 'notas_fiscais',
            ];

            $existing = Alerta::where('user_id', $userId)->where('hash', $hash)->first();

            if ($existing) {
                $updateData = $data;
                if (! in_array($existing->status, ['resolvido', 'ignorado'])) {
                    $updateData['status'] = 'ativo';
                }
                $existing->update($updateData);
                $atualizados++;
            } else {
                Alerta::create(array_merge($data, [
                    'user_id' => $userId,
                    'hash' => $hash,
                    'status' => 'ativo',
                ]));
                $novos++;
            }
        }

        // 2. Alertas de compliance (3 detectores)
        $complianceDetectors = [
            'situacao_irregular' => 'detectarSituacaoIrregular',
            'consulta_vencida' => 'detectarConsultaVencida',
            'nunca_consultado' => 'detectarNuncaConsultado',
        ];

        foreach ($complianceDetectors as $tipo => $method) {
            $participantes = $this->$method($userId);

            foreach ($participantes as $p) {
                $hash = hash('sha256', "$userId:$tipo:{$p->id}");
                $allHashes[] = $hash;

                $data = $this->buildComplianceAlertData($tipo, $p);

                $existing = Alerta::where('user_id', $userId)->where('hash', $hash)->first();

                if ($existing) {
                    $updateData = $data;
                    if (! in_array($existing->status, ['resolvido', 'ignorado'])) {
                        $updateData['status'] = 'ativo';
                    }
                    $existing->update($updateData);
                    $atualizados++;
                } else {
                    Alerta::create(array_merge($data, [
                        'user_id' => $userId,
                        'hash' => $hash,
                        'status' => 'ativo',
                    ]));
                    $novos++;
                }
            }
        }

        // 3. Alertas de risco BI (fornecedores irregulares com notas, gap de importações)
        $fornecedoresIrregulares = $this->detectarFornecedoresIrregularesComNotas($userId);
        foreach ($fornecedoresIrregulares as $f) {
            $hash = hash('sha256', "$userId:fornecedor_irregular:{$f->participante_id}");
            $allHashes[] = $hash;

            $valorFormatado = number_format((float) $f->valor_em_risco, 2, ',', '.');

            $data = [
                'tipo' => 'fornecedor_irregular',
                'categoria' => 'compliance',
                'severidade' => 'alta',
                'participante_id' => $f->participante_id,
                'titulo' => "Fornecedor irregular com {$f->total_notas} nota(s) — R$ {$valorFormatado} em risco",
                'descricao' => "{$f->razao_social} ({$f->documento}) esta com situacao {$f->situacao_cadastral} e possui {$f->total_notas} nota(s) fiscal(is) vinculadas totalizando R$ {$valorFormatado}.",
                'total_afetados' => (int) $f->total_notas,
                'detalhes' => [
                    'participante_id' => $f->participante_id,
                    'razao_social' => $f->razao_social,
                    'documento' => $f->documento,
                    'situacao_cadastral' => $f->situacao_cadastral,
                    'total_notas' => (int) $f->total_notas,
                    'valor_em_risco' => (float) $f->valor_em_risco,
                ],
            ];

            $existing = Alerta::where('user_id', $userId)->where('hash', $hash)->first();

            if ($existing) {
                $updateData = $data;
                if (! in_array($existing->status, ['resolvido', 'ignorado'])) {
                    $updateData['status'] = 'ativo';
                }
                $existing->update($updateData);
                $atualizados++;
            } else {
                Alerta::create(array_merge($data, [
                    'user_id' => $userId,
                    'hash' => $hash,
                    'status' => 'ativo',
                ]));
                $novos++;
            }
        }

        $gapImportacoes = $this->detectarGapImportacoes($userId);
        if ($gapImportacoes) {
            $hash = hash('sha256', "$userId:gap_importacao");
            $allHashes[] = $hash;

            $totalMeses = count($gapImportacoes);
            $data = [
                'tipo' => 'gap_importacao',
                'categoria' => 'importacao',
                'severidade' => 'media',
                'titulo' => "{$totalMeses} mês(es) sem importação EFD nos últimos 12 meses",
                'descricao' => "Foram detectados {$totalMeses} meses sem nenhuma importação EFD (Fiscal ou Contribuições). Meses faltantes podem indicar obrigações acessórias não entregues.",
                'total_afetados' => $totalMeses,
                'detalhes' => [
                    'meses_faltantes' => $gapImportacoes,
                    'total_meses' => $totalMeses,
                ],
            ];

            $existing = Alerta::where('user_id', $userId)->where('hash', $hash)->first();

            if ($existing) {
                $updateData = $data;
                if (! in_array($existing->status, ['resolvido', 'ignorado'])) {
                    $updateData['status'] = 'ativo';
                }
                $existing->update($updateData);
                $atualizados++;
            } else {
                Alerta::create(array_merge($data, [
                    'user_id' => $userId,
                    'hash' => $hash,
                    'status' => 'ativo',
                ]));
                $novos++;
            }
        }

        // 4. Auto-resolver alertas que não foram mais detectados
        $resolvidos = Alerta::where('user_id', $userId)
            ->where('status', 'ativo')
            ->whereNotIn('hash', $allHashes)
            ->update([
                'status' => 'resolvido',
                'resolvido_em' => now(),
            ]);

        return [
            'novos' => $novos,
            'atualizados' => $atualizados,
            'resolvidos' => $resolvidos,
        ];
    }

    /**
     * Obtém alertas paginados com filtros.
     */
    public function obterAlertas(int $userId, array $filtros): LengthAwarePaginator
    {
        $query = Alerta::doUsuario($userId);

        // Filtro de status (default: ativo)
        $status = $filtros['status'] ?? 'ativo';
        $query->where('status', $status);

        if (! empty($filtros['severidade'])) {
            $query->where('severidade', $filtros['severidade']);
        }

        if (! empty($filtros['categoria'])) {
            $query->where('categoria', $filtros['categoria']);
        }

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        $query->with([
            'participante:id,razao_social,documento',
            'cliente:id,razao_social',
        ]);

        $query->orderByDesc('prioridade')
            ->orderByRaw("CASE severidade WHEN 'alta' THEN 3 WHEN 'media' THEN 2 WHEN 'baixa' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('created_at');

        return $query->paginate(50);
    }

    /**
     * Retorna resumo dos alertas do usuário.
     */
    public function obterResumo(int $userId): array
    {
        $base = Alerta::doUsuario($userId)->ativos();

        $porSeveridade = (clone $base)
            ->selectRaw('severidade, COUNT(*) as total')
            ->groupBy('severidade')
            ->pluck('total', 'severidade')
            ->toArray();

        $porCategoria = (clone $base)
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();

        $totalAtivos = array_sum($porSeveridade);

        $novosHoje = Alerta::doUsuario($userId)
            ->ativos()
            ->whereDate('created_at', today())
            ->count();

        $ultimaAtualizacao = Alerta::doUsuario($userId)
            ->max('updated_at');

        return [
            'total_ativos' => $totalAtivos,
            'por_severidade' => [
                'alta' => $porSeveridade['alta'] ?? 0,
                'media' => $porSeveridade['media'] ?? 0,
                'baixa' => $porSeveridade['baixa'] ?? 0,
            ],
            'por_categoria' => [
                'notas_fiscais' => $porCategoria['notas_fiscais'] ?? 0,
                'compliance' => $porCategoria['compliance'] ?? 0,
                'importacao' => $porCategoria['importacao'] ?? 0,
            ],
            'novos_hoje' => $novosHoje,
            'ultima_atualizacao' => $ultimaAtualizacao,
        ];
    }

    /**
     * Marca o status de um alerta.
     */
    public function marcarStatus(int $alertaId, int $userId, string $status, ?string $notas = null): Alerta
    {
        $alerta = Alerta::where('id', $alertaId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $alerta->status = $status;

        if ($status === 'visto' && $alerta->visto_em === null) {
            $alerta->visto_em = now();
        }

        if ($status === 'resolvido') {
            $alerta->resolvido_em = now();
        }

        if ($notas !== null) {
            $alerta->notas = $notas;
        }

        $alerta->save();

        return $alerta;
    }

    /**
     * Retorna dados de evolução semanal para gráfico ApexCharts.
     */
    public function obterEvolucao(int $userId): array
    {
        $inicioSemanas = now()->subWeeks(11)->startOfWeek();

        $dados = Alerta::doUsuario($userId)
            ->where('created_at', '>=', $inicioSemanas)
            ->selectRaw("DATE_TRUNC('week', created_at) as semana, severidade, COUNT(*) as total")
            ->groupBy('semana', 'severidade')
            ->orderBy('semana')
            ->get();

        // Gerar todas as 12 semanas
        $semanas = [];
        $cursor = $inicioSemanas->copy();
        for ($i = 0; $i < 12; $i++) {
            $semanas[] = $cursor->copy();
            $cursor->addWeek();
        }

        $categorias = [];
        $seriesData = [
            'alta' => [],
            'media' => [],
            'baixa' => [],
        ];

        foreach ($semanas as $index => $semana) {
            $categorias[] = 'Sem '.($index + 1);
            $semanaStr = $semana->format('Y-m-d');

            foreach (['alta', 'media', 'baixa'] as $severidade) {
                $count = $dados
                    ->where('severidade', $severidade)
                    ->first(function ($item) use ($semanaStr) {
                        return Carbon::parse($item->semana)->format('Y-m-d') === $semanaStr;
                    });

                $seriesData[$severidade][] = $count ? (int) $count->total : 0;
            }
        }

        return [
            'categorias' => $categorias,
            'series' => [
                ['name' => 'Alta', 'data' => $seriesData['alta'], 'color' => '#EF4444'],
                ['name' => 'Media', 'data' => $seriesData['media'], 'color' => '#F59E0B'],
                ['name' => 'Baixa', 'data' => $seriesData['baixa'], 'color' => '#6B7280'],
            ],
        ];
    }

    // -------------------------------------------------------
    // Compliance detectors (private)
    // -------------------------------------------------------

    /**
     * Participantes com situação cadastral diferente de ATIVA.
     */
    private function detectarSituacaoIrregular(int $userId): Collection
    {
        return Participante::where('user_id', $userId)
            ->whereNotNull('situacao_cadastral')
            ->where('situacao_cadastral', '!=', '')
            ->where('situacao_cadastral', '!=', 'ATIVA')
            ->whereHas('efdNotas')
            ->get(['id', 'razao_social', 'documento as documento', 'situacao_cadastral', 'cliente_id']);
    }

    /**
     * Participantes com última consulta há mais de 90 dias.
     */
    private function detectarConsultaVencida(int $userId): Collection
    {
        return Participante::where('user_id', $userId)
            ->whereNotNull('ultima_consulta_em')
            ->where('ultima_consulta_em', '<', now()->subDays(90))
            ->whereHas('efdNotas')
            ->get(['id', 'razao_social', 'documento as documento', 'ultima_consulta_em', 'cliente_id']);
    }

    /**
     * Participantes que nunca foram consultados.
     */
    private function detectarNuncaConsultado(int $userId): Collection
    {
        return Participante::where('user_id', $userId)
            ->whereNull('ultima_consulta_em')
            ->whereHas('efdNotas')
            ->excludingEmpresaPropria()
            ->get(['id', 'razao_social', 'documento as documento', 'cliente_id']);
    }

    /**
     * Monta os dados do alerta de compliance a partir do tipo e participante.
     */
    private function buildComplianceAlertData(string $tipo, Participante $p): array
    {
        $base = [
            'categoria' => 'compliance',
            'cliente_id' => $p->cliente_id,
            'participante_id' => $p->id,
        ];

        return match ($tipo) {
            'situacao_irregular' => array_merge($base, [
                'tipo' => 'situacao_irregular',
                'severidade' => 'alta',
                'titulo' => "Participante com situacao cadastral {$p->situacao_cadastral}",
                'descricao' => "{$p->razao_social} ({$p->documento_formatado}) esta com situacao cadastral {$p->situacao_cadastral} na Receita Federal.",
                'total_afetados' => 1,
                'detalhes' => [
                    'participante_id' => $p->id,
                    'razao_social' => $p->razao_social,
                    'documento' => $p->documento,
                    'situacao_cadastral' => $p->situacao_cadastral,
                ],
            ]),
            'consulta_vencida' => array_merge($base, [
                'tipo' => 'consulta_vencida',
                'severidade' => 'media',
                'titulo' => "Consulta vencida — {$p->razao_social}",
                'descricao' => "Ultima consulta realizada ha mais de 90 dias ({$p->ultima_consulta_em->format('d/m/Y')}). Recomendamos atualizar os dados cadastrais.",
                'total_afetados' => 1,
                'detalhes' => [
                    'participante_id' => $p->id,
                    'razao_social' => $p->razao_social,
                    'documento' => $p->documento,
                    'ultima_consulta_em' => $p->ultima_consulta_em->toIso8601String(),
                ],
            ]),
            'nunca_consultado' => array_merge($base, [
                'tipo' => 'nunca_consultado',
                'severidade' => 'baixa',
                'titulo' => "Participante nunca consultado — {$p->razao_social}",
                'descricao' => "{$p->razao_social} ({$p->documento}) possui notas fiscais mas nunca teve seus dados cadastrais verificados.",
                'total_afetados' => 1,
                'detalhes' => [
                    'participante_id' => $p->id,
                    'razao_social' => $p->razao_social,
                    'documento' => $p->documento,
                ],
            ]),
        };
    }

    // -------------------------------------------------------
    // BI risk detectors (private)
    // -------------------------------------------------------

    /**
     * Fornecedores com situação irregular que possuem notas EFD vinculadas.
     */
    private function detectarFornecedoresIrregularesComNotas(int $userId): Collection
    {
        return DB::table('efd_notas as n')
            ->join('participantes as p', 'p.id', '=', 'n.participante_id')
            ->where('n.user_id', $userId)
            ->whereNotNull('p.situacao_cadastral')
            ->whereRaw("UPPER(p.situacao_cadastral) NOT IN ('02', 'ATIVA')")
            ->select([
                'p.id as participante_id',
                'p.documento',
                'p.razao_social',
                'p.situacao_cadastral',
                DB::raw('COUNT(n.id) as total_notas'),
                DB::raw('SUM(n.valor_total) as valor_em_risco'),
            ])
            ->groupBy('p.id', 'p.documento', 'p.razao_social', 'p.situacao_cadastral')
            ->get();
    }

    /**
     * Meses sem importação EFD nos últimos 12 meses.
     * Retorna array de labels (ex: ["jan/26", "fev/26"]) ou null se não houver gaps.
     */
    private function detectarGapImportacoes(int $userId): ?array
    {
        $inicio = Carbon::now()->subMonths(11)->startOfMonth();
        $fim = Carbon::now()->startOfMonth();
        $mesesFaltantes = [];

        foreach (CarbonPeriod::create($inicio, '1 month', $fim) as $mes) {
            $temImportacao = DB::table('efd_importacoes')
                ->where('user_id', $userId)
                ->where('status', 'concluido')
                ->whereRaw("DATE_TRUNC('month', created_at) = ?", [$mes->startOfMonth()->toDateString()])
                ->exists();

            if (! $temImportacao) {
                $mesesFaltantes[] = $mes->locale('pt_BR')->isoFormat('MMM/YY');
            }
        }

        return count($mesesFaltantes) > 0 ? $mesesFaltantes : null;
    }
}
