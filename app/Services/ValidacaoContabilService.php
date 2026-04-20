<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\ParticipanteScore;
use App\Models\XmlNota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidacaoContabilService
{
    // Pesos para cada categoria de validacao (soma = 1.0)
    private array $pesos = [
        'cadastral' => 0.20,
        'tributacao' => 0.25,
        'cfop_cst' => 0.20,
        'integridade' => 0.15,
        'ncm' => 0.10,
        'operacoes' => 0.10,
    ];

    // NCMs invalidos ou genericos
    private array $ncmsProblematicos = [
        '00000000',
        '99999999',
        '00000001',
        '99990000',
    ];

    // CFOPs de entrada (iniciam com 1, 2 ou 3)
    private array $cfopEntrada = ['1', '2', '3'];

    // CFOPs de saida (iniciam com 5, 6 ou 7)
    private array $cfopSaida = ['5', '6', '7'];

    public function __construct(
        protected ?RiskScoreService $riskScoreService = null
    ) {}

    /**
     * Valida uma nota (XML ou EFD).
     */
    public function validarNota(Model $nota, bool $incluirOperacoes = true): array
    {
        $ctx = $this->contextoValidacao($nota);

        $alertas = [];
        $scores = [];

        $scores['cadastral'] = $this->validarCadastral($ctx, $alertas);
        $scores['tributacao'] = $this->validarTributacao($ctx, $alertas);
        $scores['cfop_cst'] = $this->validarCfopCst($ctx, $alertas);
        $scores['integridade'] = $this->validarIntegridade($ctx, $alertas);
        $scores['ncm'] = $this->validarNcm($ctx, $alertas);
        $scores['operacoes'] = $incluirOperacoes ? $this->validarOperacoes($ctx, $alertas) : 0;

        $scoreTotal = $this->calcularScoreTotal($scores);

        return [
            'score_total' => $scoreTotal,
            'classificacao' => $this->classificar($scoreTotal, $alertas),
            'scores' => $scores,
            'alertas' => $alertas,
            'origem' => $ctx['origem'],
            'validado_em' => now()->toISOString(),
        ];
    }

    /**
     * Valida todas as notas de uma importacao XML.
     */
    public function validarImportacao(int $importacaoId, int $userId, string $tipo = 'basico'): array
    {
        $notas = XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->get();

        if ($notas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Nenhuma nota encontrada para esta importacao',
                'total' => 0,
            ];
        }

        return $this->processarNotas($notas, $tipo);
    }

    /**
     * Valida notas por IDs especificos, aceitando mapa de origens XML/EFD.
     *
     * @param  array<int,string>  $origens  Mapa id => 'xml'|'efd'
     */
    public function validarNotas(array $notaIds, array $origens, int $userId, string $tipo = 'basico'): array
    {
        $notas = $this->carregarNotasPorOrigem($notaIds, $origens, $userId);

        if ($notas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Nenhuma nota encontrada',
                'total' => 0,
            ];
        }

        return $this->processarNotas($notas, $tipo);
    }

    /**
     * Calcula o custo de clearance por tier, com cobranca por nota.
     *
     * @param  array<int,string>  $origens  Mapa id => 'xml'|'efd'
     */
    public function calcularCusto(array $notaIds, array $origens, int $userId, string $tipo = 'basico'): array
    {
        $notas = $this->carregarNotasPorOrigem($notaIds, $origens, $userId);
        $totalNotas = $notas->count();

        $custoUnitario = self::custoUnitarioPorTier($tipo);
        $custoTotal = $totalNotas * $custoUnitario;

        return [
            'notas' => $totalNotas,
            'tipo' => $tipo,
            'custo_unitario' => $custoUnitario,
            'custo_total' => $custoTotal,
            'custo_reais' => number_format($custoTotal * 0.20, 2, ',', '.'),
        ];
    }

    /**
     * Custo unitario por nota conforme tier de clearance.
     */
    public static function custoUnitarioPorTier(string $tipo): int
    {
        return match ($tipo) {
            'full' => 20,
            default => 10,
        };
    }

    /**
     * Obtem estatisticas de validacao para um usuario (XML + EFD).
     */
    public function getEstatisticas(int $userId): array
    {
        $xml = XmlNota::where('user_id', $userId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN validacao IS NOT NULL THEN 1 END) as validadas'),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'conforme' THEN 1 END) as conforme"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'atencao' THEN 1 END) as atencao"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'irregular' THEN 1 END) as irregular"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'critico' THEN 1 END) as critico"),
                DB::raw("AVG((validacao->>'score_total')::int) as media_score")
            )
            ->first();

        $efd = EfdNota::where('user_id', $userId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN validacao IS NOT NULL THEN 1 END) as validadas'),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'conforme' THEN 1 END) as conforme"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'atencao' THEN 1 END) as atencao"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'irregular' THEN 1 END) as irregular"),
                DB::raw("COUNT(CASE WHEN validacao->>'classificacao' = 'critico' THEN 1 END) as critico"),
                DB::raw("AVG((validacao->>'score_total')::int) as media_score")
            )
            ->first();

        $total = (int) ($xml->total ?? 0) + (int) ($efd->total ?? 0);
        $validadas = (int) ($xml->validadas ?? 0) + (int) ($efd->validadas ?? 0);

        return [
            'total_notas' => $total,
            'total_validadas' => $validadas,
            'conforme' => (int) ($xml->conforme ?? 0) + (int) ($efd->conforme ?? 0),
            'atencao' => (int) ($xml->atencao ?? 0) + (int) ($efd->atencao ?? 0),
            'irregular' => (int) ($xml->irregular ?? 0) + (int) ($efd->irregular ?? 0),
            'critico' => (int) ($xml->critico ?? 0) + (int) ($efd->critico ?? 0),
            'media_score' => $this->mediaPonderada($xml, $efd),
            'percentual_validado' => $total > 0 ? round(($validadas / $total) * 100, 1) : 0,
        ];
    }

    /**
     * KPIs de clearance baseados em validacao->>'situacao' (Receita Federal/InfoSimples).
     * Une XML + EFD deduplicando por chave de acesso (preferindo XML).
     */
    public function getKpisStatusReceita(int $userId): array
    {
        $row = DB::selectOne("
            WITH xml AS (
                SELECT nfe_id AS chave, validacao->>'situacao' AS situacao
                FROM xml_notas WHERE user_id = ?
            ),
            efd AS (
                SELECT chave_acesso AS chave, validacao->>'situacao' AS situacao
                FROM efd_notas WHERE user_id = ?
                  AND NOT EXISTS (
                    SELECT 1 FROM xml_notas xn
                    WHERE xn.user_id = ? AND xn.nfe_id = efd_notas.chave_acesso
                  )
            ),
            u AS (SELECT * FROM xml UNION ALL SELECT * FROM efd)
            SELECT
                COUNT(*)                                                    AS total,
                COUNT(situacao)                                             AS verificadas,
                COUNT(*) FILTER (WHERE situacao = 'AUTORIZADA')             AS autorizadas,
                COUNT(*) FILTER (WHERE situacao = 'CANCELADA')              AS canceladas,
                COUNT(*) FILTER (WHERE situacao = 'DENEGADA')               AS denegadas,
                COUNT(*) FILTER (WHERE situacao = 'INUTILIZADA')            AS inutilizadas,
                COUNT(*) FILTER (WHERE situacao = 'NAO_ENCONTRADA')         AS nao_encontradas,
                COUNT(*) FILTER (WHERE situacao = 'INDETERMINADO')          AS indeterminadas,
                COUNT(*) FILTER (WHERE situacao IS NULL)                    AS nao_verificadas
            FROM u
        ", [$userId, $userId, $userId]);

        return [
            'total' => (int) ($row->total ?? 0),
            'verificadas' => (int) ($row->verificadas ?? 0),
            'nao_verificadas' => (int) ($row->nao_verificadas ?? 0),
            'autorizadas' => (int) ($row->autorizadas ?? 0),
            'canceladas' => (int) ($row->canceladas ?? 0),
            'denegadas' => (int) ($row->denegadas ?? 0),
            'inutilizadas' => (int) ($row->inutilizadas ?? 0),
            'nao_encontradas' => (int) ($row->nao_encontradas ?? 0),
            'indeterminadas' => (int) ($row->indeterminadas ?? 0),
        ];
    }

    /**
     * Notas com situação bloqueante (CANCELADA/DENEGADA/INUTILIZADA), ordenadas por verificação mais recente.
     */
    public function getNotasComSituacaoBloqueante(int $userId, int $limit = 5): array
    {
        return $this->queryNotasUnificadasComSituacao($userId)
            ->whereIn(DB::raw("validacao->>'situacao'"), ['CANCELADA', 'DENEGADA', 'INUTILIZADA'])
            ->orderByDesc(DB::raw("validacao->>'consultado_em'"))
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Últimas notas verificadas (qualquer situação não-nula).
     */
    public function getUltimasVerificacoes(int $userId, int $limit = 10): array
    {
        return $this->queryNotasUnificadasComSituacao($userId)
            ->whereNotNull(DB::raw("validacao->>'situacao'"))
            ->orderByDesc(DB::raw("validacao->>'consultado_em'"))
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Subquery unificada XML+EFD com campos para cards de status.
     * Dedupe: EFD só entra se não houver XML com mesma chave.
     */
    private function queryNotasUnificadasComSituacao(int $userId): \Illuminate\Database\Query\Builder
    {
        $xml = DB::table('xml_notas')
            ->selectRaw("
                'xml'::text AS origem,
                xml_notas.id AS id,
                xml_notas.nfe_id AS chave,
                xml_notas.numero_nota::text AS numero,
                xml_notas.serie::text AS serie,
                xml_notas.emit_razao_social AS emit_razao_social,
                xml_notas.validacao AS validacao
            ")
            ->where('user_id', $userId);

        $efd = DB::table('efd_notas')
            ->leftJoin('participantes', 'participantes.id', '=', 'efd_notas.participante_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'efd_notas.cliente_id')
            ->selectRaw("
                'efd'::text AS origem,
                efd_notas.id AS id,
                efd_notas.chave_acesso AS chave,
                efd_notas.numero::text AS numero,
                efd_notas.serie AS serie,
                CASE WHEN efd_notas.tipo_operacao = 'entrada'
                     THEN participantes.razao_social
                     ELSE clientes.razao_social END AS emit_razao_social,
                efd_notas.validacao AS validacao
            ")
            ->where('efd_notas.user_id', $userId)
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('xml_notas')
                    ->whereColumn('xml_notas.nfe_id', 'efd_notas.chave_acesso')
                    ->where('xml_notas.user_id', $userId);
            });

        return DB::query()->fromSub($xml->unionAll($efd), 'u');
    }

    public function getPesos(): array
    {
        return $this->pesos;
    }

    public function getCategorias(): array
    {
        return [
            'cadastral' => [
                'nome' => 'Consistencia Cadastral',
                'peso' => $this->pesos['cadastral'],
                'descricao' => 'CRT declarado vs situacao real na Receita Federal',
            ],
            'tributacao' => [
                'nome' => 'Tributacao',
                'peso' => $this->pesos['tributacao'],
                'descricao' => 'Aliquotas compativeis com regime tributario',
            ],
            'cfop_cst' => [
                'nome' => 'CFOP/CST',
                'peso' => $this->pesos['cfop_cst'],
                'descricao' => 'Combinacoes validas de CFOP e CST/CSOSN',
            ],
            'integridade' => [
                'nome' => 'Integridade de Valores',
                'peso' => $this->pesos['integridade'],
                'descricao' => 'Soma dos tributos vs total declarado',
            ],
            'ncm' => [
                'nome' => 'NCM',
                'peso' => $this->pesos['ncm'],
                'descricao' => 'NCMs validos e compativeis com operacao',
            ],
            'operacoes' => [
                'nome' => 'Operacoes com Risco',
                'peso' => $this->pesos['operacoes'],
                'descricao' => 'Participantes em listas restritivas',
            ],
        ];
    }

    // ============ Adapter ============

    /**
     * Normaliza XmlNota ou EfdNota em contexto uniforme para as validacoes.
     */
    private function contextoValidacao(Model $nota): array
    {
        if ($nota instanceof XmlNota) {
            return $this->contextoXml($nota);
        }

        if ($nota instanceof EfdNota) {
            return $this->contextoEfd($nota);
        }

        throw new \InvalidArgumentException('Tipo de nota nao suportado: '.get_class($nota));
    }

    private function contextoXml(XmlNota $nota): array
    {
        $payload = $nota->payload ?? [];
        $itensRaw = $payload['det'] ?? [];
        if (! is_array($itensRaw)) {
            $itensRaw = [];
        }
        if (isset($itensRaw['prod'])) {
            $itensRaw = [$itensRaw];
        }

        $itens = array_map(function ($item, $index) {
            $icms = $item['imposto']['ICMS'] ?? [];
            $cst = null;
            foreach ($icms as $dados) {
                if (isset($dados['CST'])) {
                    $cst = $dados['CST'];
                    break;
                }
                if (isset($dados['CSOSN'])) {
                    $cst = 'CSOSN_'.$dados['CSOSN'];
                    break;
                }
            }

            return [
                'numero_item' => $index + 1,
                'cfop' => $item['prod']['CFOP'] ?? null,
                'ncm' => $item['prod']['NCM'] ?? null,
                'cst_icms' => $cst,
            ];
        }, $itensRaw, array_keys($itensRaw));

        return [
            'origem' => 'xml',
            'modelo_nota' => $nota,
            'tipo_nota' => $nota->tipo_nota,
            'tipo_documento' => $nota->tipo_documento,
            'payload' => $payload,
            'emit_participante_id' => $nota->emit_participante_id,
            'dest_participante_id' => $nota->dest_participante_id,
            'emit_cnpj' => $nota->emit_cnpj,
            'dest_cnpj' => $nota->dest_cnpj,
            'emit_uf' => $nota->emit_uf,
            'dest_uf' => $nota->dest_uf,
            'natureza_operacao' => $nota->natureza_operacao,
            'valor_total' => (float) ($nota->valor_total ?? 0),
            'icms_valor' => (float) ($nota->icms_valor ?? 0),
            'icms_st_valor' => (float) ($nota->icms_st_valor ?? 0),
            'pis_valor' => (float) ($nota->pis_valor ?? 0),
            'cofins_valor' => (float) ($nota->cofins_valor ?? 0),
            'ipi_valor' => (float) ($nota->ipi_valor ?? 0),
            'tributos_total' => (float) ($nota->tributos_total ?? 0),
            'vTotTrib' => (float) ($payload['total']['ICMSTot']['vTotTrib'] ?? 0),
            'vNF' => (float) ($payload['total']['ICMSTot']['vNF'] ?? 0),
            'crt' => $payload['emit']['CRT'] ?? null,
            'itens' => $itens,
        ];
    }

    private function contextoEfd(EfdNota $nota): array
    {
        $nota->loadMissing(['itens', 'cliente', 'participante']);

        // Mapear emit/dest a partir de tipo_operacao e participante_id + cliente (empresa propria)
        $empresaPropriaId = $this->participanteDaEmpresaPropriaId($nota->user_id, $nota->cliente);
        $partnerId = $nota->participante_id;

        if ($nota->tipo_operacao === 'entrada') {
            // Emit = participante externo; Dest = empresa propria
            $emitId = $partnerId;
            $destId = $empresaPropriaId;
            $emitCnpj = $nota->participante?->documento;
            $destCnpj = $nota->cliente?->documento;
            $tipoNota = 0;
        } else {
            // Saida: Emit = empresa propria; Dest = participante externo
            $emitId = $empresaPropriaId;
            $destId = $partnerId;
            $emitCnpj = $nota->cliente?->documento;
            $destCnpj = $nota->participante?->documento;
            $tipoNota = 1;
        }

        $itens = $nota->itens->map(fn ($item) => [
            'numero_item' => $item->numero_item,
            'cfop' => $item->cfop,
            'ncm' => null, // vira via catalogo no passo 4 se precisar
            'cst_icms' => $item->cst_icms,
        ])->all();

        return [
            'origem' => 'efd',
            'modelo_nota' => $nota,
            'tipo_nota' => $tipoNota,
            'tipo_documento' => null,
            'payload' => null,
            'emit_participante_id' => $emitId,
            'dest_participante_id' => $destId,
            'emit_cnpj' => $emitCnpj,
            'dest_cnpj' => $destCnpj,
            'emit_uf' => null,
            'dest_uf' => null,
            'natureza_operacao' => null,
            'valor_total' => (float) ($nota->valor_total ?? 0),
            'icms_valor' => null,
            'icms_st_valor' => null,
            'pis_valor' => null,
            'cofins_valor' => null,
            'ipi_valor' => null,
            'tributos_total' => null,
            'vTotTrib' => null,
            'vNF' => null,
            'crt' => null,
            'itens' => $itens,
        ];
    }

    private function participanteDaEmpresaPropriaId(int $userId, ?Cliente $cliente): ?int
    {
        if (! $cliente?->documento) {
            return null;
        }

        return Participante::where('user_id', $userId)
            ->where('documento', $cliente->documento)
            ->value('id');
    }

    /**
     * Carrega notas XML e EFD baseado no mapa de origens.
     */
    private function carregarNotasPorOrigem(array $notaIds, array $origens, int $userId)
    {
        $xmlIds = [];
        $efdIds = [];
        foreach ($notaIds as $id) {
            $origem = $origens[$id] ?? $origens[(string) $id] ?? 'xml';
            if ($origem === 'efd') {
                $efdIds[] = (int) $id;
            } else {
                $xmlIds[] = (int) $id;
            }
        }

        $xml = $xmlIds
            ? XmlNota::whereIn('id', $xmlIds)->where('user_id', $userId)->get()
            : collect();

        $efd = $efdIds
            ? EfdNota::with('itens')->whereIn('id', $efdIds)->where('user_id', $userId)->get()
            : collect();

        return $xml->merge($efd);
    }

    private function processarNotas($notas, string $tipo): array
    {
        $resultados = [];
        $totais = [
            'total' => $notas->count(),
            'validadas' => 0,
            'conforme' => 0,
            'atencao' => 0,
            'irregular' => 0,
            'critico' => 0,
            'alertas_bloqueantes' => 0,
            'alertas_atencao' => 0,
            'alertas_info' => 0,
        ];

        DB::beginTransaction();
        try {
            // TODO: Full dispara CND Federal + CNDT do emitente via n8n em plano futuro. Hoje roda so Basico.
            foreach ($notas as $nota) {
                $resultado = $this->validarNota($nota, true);
                $nota->update(['validacao' => $resultado]);
                $resultados[] = $resultado;

                $totais['validadas']++;
                if (isset($totais[$resultado['classificacao']])) {
                    $totais[$resultado['classificacao']]++;
                }

                foreach ($resultado['alertas'] as $alerta) {
                    $key = 'alertas_'.strtolower($alerta['nivel']);
                    if (isset($totais[$key])) {
                        $totais[$key]++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "{$totais['validadas']} nota(s) validada(s)",
                'totais' => $totais,
                'score_medio' => $this->calcularScoreMedio($resultados),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao validar notas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao validar notas: '.$e->getMessage(),
            ];
        }
    }

    private function mediaPonderada($xml, $efd): float
    {
        $xmlTotal = (int) ($xml->validadas ?? 0);
        $efdTotal = (int) ($efd->validadas ?? 0);
        $total = $xmlTotal + $efdTotal;
        if ($total === 0) {
            return 0;
        }
        $soma = (float) ($xml->media_score ?? 0) * $xmlTotal + (float) ($efd->media_score ?? 0) * $efdTotal;

        return round($soma / $total, 1);
    }

    // ============ Metodos de validacao individual ============

    private function validarCadastral(array $ctx, array &$alertas): int
    {
        $score = 0;
        $crtXml = $ctx['crt'];

        $participante = null;
        if ($ctx['emit_participante_id']) {
            $participante = Participante::find($ctx['emit_participante_id']);
        }

        if ($participante) {
            $situacao = strtoupper($participante->situacao_cadastral ?? 'ATIVA');

            if (in_array($situacao, ['BAIXADA', 'INAPTA', 'NULA'])) {
                $alertas[] = [
                    'categoria' => 'cadastral',
                    'nivel' => 'bloqueante',
                    'codigo' => 'EMIT_BAIXADA',
                    'mensagem' => "Emitente com situacao cadastral: {$situacao}",
                    'detalhe' => "CNPJ {$ctx['emit_cnpj']} esta {$situacao} na Receita Federal",
                ];
                $score += 100;
            } elseif ($situacao === 'SUSPENSA') {
                $alertas[] = [
                    'categoria' => 'cadastral',
                    'nivel' => 'atencao',
                    'codigo' => 'EMIT_SUSPENSA',
                    'mensagem' => 'Emitente com situacao SUSPENSA',
                    'detalhe' => "CNPJ {$ctx['emit_cnpj']} esta suspenso",
                ];
                $score += 50;
            }

            if ($crtXml && $participante->crt && (int) $crtXml !== (int) $participante->crt) {
                $alertas[] = [
                    'categoria' => 'cadastral',
                    'nivel' => 'atencao',
                    'codigo' => 'CRT_DIVERGENTE',
                    'mensagem' => 'CRT no XML diverge do cadastro',
                    'detalhe' => "XML: CRT={$crtXml}, Cadastro: CRT={$participante->crt}",
                ];
                $score += 30;
            }
        }

        if ($ctx['dest_participante_id']) {
            $destParticipante = Participante::find($ctx['dest_participante_id']);
            if ($destParticipante) {
                $situacaoDest = strtoupper($destParticipante->situacao_cadastral ?? 'ATIVA');

                if (in_array($situacaoDest, ['BAIXADA', 'INAPTA', 'NULA'])) {
                    $alertas[] = [
                        'categoria' => 'cadastral',
                        'nivel' => 'atencao',
                        'codigo' => 'DEST_BAIXADA',
                        'mensagem' => "Destinatario com situacao cadastral: {$situacaoDest}",
                        'detalhe' => "CNPJ {$ctx['dest_cnpj']} esta {$situacaoDest}",
                    ];
                    $score += 40;
                }
            }
        }

        return min(100, $score);
    }

    private function validarTributacao(array $ctx, array &$alertas): int
    {
        // EFD: tributos consolidados indisponiveis
        if ($ctx['origem'] === 'efd') {
            $alertas[] = [
                'categoria' => 'tributacao',
                'nivel' => 'info',
                'codigo' => 'TRIBUTACAO_EFD_PARCIAL',
                'mensagem' => 'Tributos consolidados nao disponiveis em EFD',
                'detalhe' => 'Para cobertura tributaria completa, importe o XML da nota.',
            ];

            return 0;
        }

        $score = 0;
        $crt = (int) ($ctx['crt'] ?? 3);

        $valorTotal = $ctx['valor_total'];
        $icmsValor = (float) $ctx['icms_valor'];
        $pisValor = (float) $ctx['pis_valor'];
        $cofinsValor = (float) $ctx['cofins_valor'];
        $ipiValor = (float) $ctx['ipi_valor'];

        if ($valorTotal <= 0) {
            return 50;
        }

        $aliquotaIcms = ($icmsValor / $valorTotal) * 100;
        $aliquotaPis = ($pisValor / $valorTotal) * 100;
        $aliquotaCofins = ($cofinsValor / $valorTotal) * 100;

        if ($crt === 1) {
            if ($aliquotaIcms > 5) {
                $alertas[] = [
                    'categoria' => 'tributacao',
                    'nivel' => 'atencao',
                    'codigo' => 'SIMPLES_ICMS_ALTO',
                    'mensagem' => 'Simples Nacional com ICMS destacado acima de 5%',
                    'detalhe' => 'Aliquota efetiva: '.number_format($aliquotaIcms, 2).'%',
                ];
                $score += 40;
            }

            if ($aliquotaPis > 0 || $aliquotaCofins > 0) {
                $alertas[] = [
                    'categoria' => 'tributacao',
                    'nivel' => 'info',
                    'codigo' => 'SIMPLES_PIS_COFINS',
                    'mensagem' => 'Simples Nacional com PIS/COFINS destacado',
                    'detalhe' => 'Verificar se operacao permite destaque',
                ];
                $score += 10;
            }
        }

        if ($crt === 3 && $aliquotaPis == 0 && $aliquotaCofins == 0 && $valorTotal > 1000) {
            $alertas[] = [
                'categoria' => 'tributacao',
                'nivel' => 'info',
                'codigo' => 'PIS_COFINS_ZERO',
                'mensagem' => 'PIS/COFINS zerados em nota de Lucro Real/Presumido',
                'detalhe' => 'Verificar CST de PIS/COFINS (pode ser isento/suspenso)',
            ];
            $score += 10;
        }

        $natureza = strtoupper($ctx['natureza_operacao'] ?? '');
        if ($ipiValor > 0 && (str_contains($natureza, 'SERVICO') || str_contains($natureza, 'SERVIÇO'))) {
            $alertas[] = [
                'categoria' => 'tributacao',
                'nivel' => 'bloqueante',
                'codigo' => 'IPI_EM_SERVICO',
                'mensagem' => 'IPI destacado em operacao de servico',
                'detalhe' => 'IPI: R$ '.number_format($ipiValor, 2, ',', '.'),
            ];
            $score += 80;
        }

        return min(100, $score);
    }

    private function validarCfopCst(array $ctx, array &$alertas): int
    {
        $score = 0;
        $itens = $ctx['itens'];

        foreach ($itens as $item) {
            $cfop = $item['cfop'] ?? null;
            $itemNum = $item['numero_item'];
            if (! $cfop) {
                continue;
            }

            $cfopStr = (string) $cfop;
            $primeiroDig = substr($cfopStr, 0, 1);
            $tipoNota = $ctx['tipo_nota'];

            if ($tipoNota === 0 && in_array($primeiroDig, $this->cfopSaida)) {
                $alertas[] = [
                    'categoria' => 'cfop_cst',
                    'nivel' => 'bloqueante',
                    'codigo' => 'CFOP_TIPO_INCONSISTENTE',
                    'mensagem' => "Item {$itemNum}: CFOP de saida em nota de entrada",
                    'detalhe' => "CFOP {$cfop} nao e compativel com nota de entrada",
                ];
                $score += 50;
            }

            if ($tipoNota === 1 && in_array($primeiroDig, $this->cfopEntrada)) {
                $alertas[] = [
                    'categoria' => 'cfop_cst',
                    'nivel' => 'bloqueante',
                    'codigo' => 'CFOP_TIPO_INCONSISTENTE',
                    'mensagem' => "Item {$itemNum}: CFOP de entrada em nota de saida",
                    'detalhe' => "CFOP {$cfop} nao e compativel com nota de saida",
                ];
                $score += 50;
            }

            $cfopInterestadual = in_array($primeiroDig, ['2', '6']);
            $emitUf = $ctx['emit_uf'];
            $destUf = $ctx['dest_uf'];
            if ($emitUf && $destUf) {
                $mesmoEstado = strtoupper($emitUf) === strtoupper($destUf);
                if ($cfopInterestadual && $mesmoEstado) {
                    $alertas[] = [
                        'categoria' => 'cfop_cst',
                        'nivel' => 'atencao',
                        'codigo' => 'CFOP_UF_INCONSISTENTE',
                        'mensagem' => "Item {$itemNum}: CFOP interestadual em operacao interna",
                        'detalhe' => "CFOP {$cfop} indica interestadual, mas emit/dest sao do mesmo estado ({$emitUf})",
                    ];
                    $score += 30;
                }

                $cfopInterno = in_array($primeiroDig, ['1', '5']);
                if ($cfopInterno && ! $mesmoEstado) {
                    $alertas[] = [
                        'categoria' => 'cfop_cst',
                        'nivel' => 'atencao',
                        'codigo' => 'CFOP_UF_INCONSISTENTE',
                        'mensagem' => "Item {$itemNum}: CFOP interno em operacao interestadual",
                        'detalhe' => "CFOP {$cfop} indica interno, mas emit ({$emitUf}) != dest ({$destUf})",
                    ];
                    $score += 30;
                }
            }

            $cst = $item['cst_icms'] ?? null;
            if (in_array((string) $cfop, ['5102', '6102']) && $cst === '60') {
                $alertas[] = [
                    'categoria' => 'cfop_cst',
                    'nivel' => 'info',
                    'codigo' => 'CFOP_CST_ATIPICO',
                    'mensagem' => "Item {$itemNum}: Venda com ICMS-ST",
                    'detalhe' => "CFOP {$cfop} com CST 60 - verificar se produto e ST",
                ];
                $score += 10;
            }
        }

        return min(100, $score);
    }

    private function validarIntegridade(array $ctx, array &$alertas): int
    {
        if ($ctx['origem'] === 'efd') {
            $alertas[] = [
                'categoria' => 'integridade',
                'nivel' => 'info',
                'codigo' => 'INTEGRIDADE_EFD_PARCIAL',
                'mensagem' => 'Totais tributarios (vTotTrib) nao disponiveis em EFD',
                'detalhe' => 'Para cobertura de integridade completa, importe o XML da nota.',
            ];

            return 0;
        }

        $score = 0;

        $valorTotal = $ctx['valor_total'];
        $icmsValor = (float) $ctx['icms_valor'];
        $icmsStValor = (float) $ctx['icms_st_valor'];
        $pisValor = (float) $ctx['pis_valor'];
        $cofinsValor = (float) $ctx['cofins_valor'];
        $ipiValor = (float) $ctx['ipi_valor'];
        $tributosTotal = (float) $ctx['tributos_total'];
        $vTotTrib = (float) $ctx['vTotTrib'];

        $somaCalculada = $icmsValor + $icmsStValor + $pisValor + $cofinsValor + $ipiValor;

        if ($tributosTotal > 0 && $vTotTrib > 0) {
            $diferencaPerc = abs($tributosTotal - $vTotTrib) / max($vTotTrib, 0.01) * 100;
            if ($diferencaPerc > 5) {
                $alertas[] = [
                    'categoria' => 'integridade',
                    'nivel' => 'bloqueante',
                    'codigo' => 'TRIBUTOS_DIVERGENTES',
                    'mensagem' => 'Divergencia superior a 5% nos tributos',
                    'detalhe' => 'Campo: R$ '.number_format($tributosTotal, 2, ',', '.').
                        ' | XML vTotTrib: R$ '.number_format($vTotTrib, 2, ',', '.'),
                ];
                $score += 70;
            } elseif ($diferencaPerc > 1) {
                $alertas[] = [
                    'categoria' => 'integridade',
                    'nivel' => 'atencao',
                    'codigo' => 'TRIBUTOS_DIVERGENTES_LEVE',
                    'mensagem' => 'Divergencia entre 1% e 5% nos tributos',
                    'detalhe' => 'Diferenca de '.number_format($diferencaPerc, 2).'%',
                ];
                $score += 30;
            }
        }

        if ($somaCalculada > $valorTotal && $valorTotal > 0) {
            $alertas[] = [
                'categoria' => 'integridade',
                'nivel' => 'bloqueante',
                'codigo' => 'TRIBUTOS_MAIOR_TOTAL',
                'mensagem' => 'Soma dos tributos maior que valor total',
                'detalhe' => 'Tributos: R$ '.number_format($somaCalculada, 2, ',', '.').
                    ' | Total: R$ '.number_format($valorTotal, 2, ',', '.'),
            ];
            $score += 100;
        }

        $vNF = (float) $ctx['vNF'];
        if ($vNF > 0 && $valorTotal > 0) {
            $diferencaVNF = abs($vNF - $valorTotal) / max($valorTotal, 0.01) * 100;
            if ($diferencaVNF > 1) {
                $alertas[] = [
                    'categoria' => 'integridade',
                    'nivel' => 'atencao',
                    'codigo' => 'VALOR_TOTAL_DIVERGENTE',
                    'mensagem' => 'Valor total diverge do XML',
                    'detalhe' => 'Campo: R$ '.number_format($valorTotal, 2, ',', '.').
                        ' | XML vNF: R$ '.number_format($vNF, 2, ',', '.'),
                ];
                $score += 20;
            }
        }

        return min(100, $score);
    }

    private function validarNcm(array $ctx, array &$alertas): int
    {
        $score = 0;
        $itens = $ctx['itens'];

        foreach ($itens as $item) {
            $ncm = $item['ncm'] ?? null;
            $itemNum = $item['numero_item'];

            if (! $ncm) {
                // Em EFD, NCM pode vir do catalogo — nao penaliza sem esta informacao aqui.
                if ($ctx['origem'] === 'xml') {
                    $alertas[] = [
                        'categoria' => 'ncm',
                        'nivel' => 'atencao',
                        'codigo' => 'NCM_AUSENTE',
                        'mensagem' => "Item {$itemNum}: NCM nao informado",
                        'detalhe' => 'NCM e obrigatorio para NF-e',
                    ];
                    $score += 20;
                }

                continue;
            }

            $ncmStr = preg_replace('/[^0-9]/', '', (string) $ncm);

            if (in_array($ncmStr, $this->ncmsProblematicos)) {
                $alertas[] = [
                    'categoria' => 'ncm',
                    'nivel' => 'atencao',
                    'codigo' => 'NCM_GENERICO',
                    'mensagem' => "Item {$itemNum}: NCM generico ou invalido",
                    'detalhe' => "NCM {$ncm} e considerado generico",
                ];
                $score += 30;
            }

            if (strlen($ncmStr) < 8) {
                $alertas[] = [
                    'categoria' => 'ncm',
                    'nivel' => 'atencao',
                    'codigo' => 'NCM_INCOMPLETO',
                    'mensagem' => "Item {$itemNum}: NCM incompleto",
                    'detalhe' => "NCM {$ncm} deve ter 8 digitos",
                ];
                $score += 20;
            }

            if (substr($ncmStr, 0, 2) === '99' && $ctx['tipo_documento'] === 'NFE') {
                $alertas[] = [
                    'categoria' => 'ncm',
                    'nivel' => 'info',
                    'codigo' => 'NCM_SERVICO_NFE',
                    'mensagem' => "Item {$itemNum}: NCM de servico em NF-e",
                    'detalhe' => "NCM {$ncm} e tipicamente de servicos",
                ];
                $score += 15;
            }
        }

        return min(100, $score);
    }

    private function validarOperacoes(array $ctx, array &$alertas): int
    {
        $score = 0;

        if ($ctx['emit_participante_id']) {
            $scoreEmit = ParticipanteScore::where('participante_id', $ctx['emit_participante_id'])->first();

            if ($scoreEmit) {
                if ($scoreEmit->score_compliance >= 100) {
                    $alertas[] = [
                        'categoria' => 'operacoes',
                        'nivel' => 'bloqueante',
                        'codigo' => 'EMIT_LISTA_RESTRITIVA',
                        'mensagem' => 'Emitente em lista restritiva (CEIS/CNEP/TCU)',
                        'detalhe' => "CNPJ {$ctx['emit_cnpj']} consta em cadastro de empresas inidoneas",
                    ];
                    $score += 100;
                }

                if ($scoreEmit->score_esg >= 100) {
                    $alertas[] = [
                        'categoria' => 'operacoes',
                        'nivel' => 'bloqueante',
                        'codigo' => 'EMIT_TRABALHO_ESCRAVO',
                        'mensagem' => 'Emitente em lista de trabalho escravo',
                        'detalhe' => "CNPJ {$ctx['emit_cnpj']} consta na lista suja",
                    ];
                    $score += 100;
                }

                if ($scoreEmit->classificacao === 'critico') {
                    $alertas[] = [
                        'categoria' => 'operacoes',
                        'nivel' => 'atencao',
                        'codigo' => 'EMIT_RISCO_CRITICO',
                        'mensagem' => 'Emitente classificado como risco critico',
                        'detalhe' => "Score de risco: {$scoreEmit->score_total}/100",
                    ];
                    $score += 50;
                }
            }
        }

        if ($ctx['dest_participante_id'] && $ctx['tipo_nota'] === 0) {
            $scoreDest = ParticipanteScore::where('participante_id', $ctx['dest_participante_id'])->first();
            if ($scoreDest && $scoreDest->score_compliance >= 100) {
                $alertas[] = [
                    'categoria' => 'operacoes',
                    'nivel' => 'atencao',
                    'codigo' => 'DEST_LISTA_RESTRITIVA',
                    'mensagem' => 'Destinatario em lista restritiva',
                    'detalhe' => "CNPJ {$ctx['dest_cnpj']} em cadastro restritivo",
                ];
                $score += 40;
            }
        }

        return min(100, $score);
    }

    private function calcularScoreTotal(array $scores): int
    {
        $total = 0;
        foreach ($this->pesos as $key => $peso) {
            $total += ($scores[$key] ?? 0) * $peso;
        }

        return (int) round($total);
    }

    private function classificar(int $scoreTotal, array $alertas): string
    {
        $temBloqueante = collect($alertas)->contains('nivel', 'bloqueante');

        if ($temBloqueante) {
            return $scoreTotal >= 50 ? 'critico' : 'irregular';
        }

        return match (true) {
            $scoreTotal <= 10 => 'conforme',
            $scoreTotal <= 30 => 'atencao',
            $scoreTotal <= 60 => 'irregular',
            default => 'critico',
        };
    }

    private function calcularScoreMedio(array $resultados): float
    {
        if (empty($resultados)) {
            return 0;
        }
        $soma = array_sum(array_column($resultados, 'score_total'));

        return round($soma / count($resultados), 1);
    }
}
