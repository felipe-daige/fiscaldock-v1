<?php

namespace App\Services;

use App\Models\EfdApuracaoContribuicao;
use App\Models\EfdApuracaoIcms;
use App\Models\EfdRetencaoFonte;
use Illuminate\Support\Carbon;

class ResumoFiscalService
{
    public function __construct(
        protected EfdAgregadorService $efd,
        protected \App\Services\Efd\CruzamentoApuracaoService $cruzamento,
    ) {}

    private function periodoDates(string $competencia): array
    {
        $inicio = Carbon::parse($competencia.'-01')->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        return [$inicio, $fim];
    }

    private function getApuracaoIcms(int $userId, int $clienteId, string $competencia): ?EfdApuracaoIcms
    {
        [$inicio, $fim] = $this->periodoDates($competencia);

        return EfdApuracaoIcms::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($inicio, $fim)
            ->first();
    }

    private function getApuracaoContrib(int $userId, int $clienteId, string $competencia): ?EfdApuracaoContribuicao
    {
        [$inicio, $fim] = $this->periodoDates($competencia);

        return EfdApuracaoContribuicao::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($inicio, $fim)
            ->first();
    }

    // ─── Seção 1: Resumo Executivo ───

    public function getResumoExecutivo(int $userId, int $clienteId, string $competencia): array
    {
        $icms = $this->getApuracaoIcms($userId, $clienteId, $competencia);
        $contrib = $this->getApuracaoContrib($userId, $clienteId, $competencia);
        [$inicio, $fim] = $this->periodoDates($competencia);

        $retencoes = EfdRetencaoFonte::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($inicio, $fim)
            ->selectRaw('COALESCE(SUM(valor_pis), 0) as pis, COALESCE(SUM(valor_cofins), 0) as cofins')
            ->first();

        $retencoesVal = (float) ($retencoes->pis ?? 0) + (float) ($retencoes->cofins ?? 0);

        // Valores a recolher vêm do quadro ÚNICO (E116/E250 + M200/M600). A família
        // ICMS já inclui débito especial (350) e ST (333) — antes o KPI usava só
        // icms_a_recolher (310) e subestimava (auditoria A3). saldo = total das guias.
        $aRecolher = $this->getARecolherData($userId, $clienteId, $competencia);
        $agg = $this->agregarPorFamilia($aRecolher['linhas']);
        $saldoLiquido = $aRecolher['total'];

        // Mês anterior para deltas
        $prevComp = Carbon::parse($competencia.'-01')->subMonth()->format('Y-m');
        [$prevInicio, $prevFim] = $this->periodoDates($prevComp);

        $prevRetencoes = EfdRetencaoFonte::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($prevInicio, $prevFim)
            ->selectRaw('COALESCE(SUM(valor_pis), 0) as pis, COALESCE(SUM(valor_cofins), 0) as cofins')
            ->first();

        $prevRetencoesVal = (float) ($prevRetencoes->pis ?? 0) + (float) ($prevRetencoes->cofins ?? 0);
        $prevARecolher = $this->getARecolherData($userId, $clienteId, $prevComp);
        $prevAgg = $this->agregarPorFamilia($prevARecolher['linhas']);
        $prevSaldoLiquido = $prevARecolher['total'];

        return [
            'tem_dados' => $icms !== null || $contrib !== null,
            'tem_icms' => $icms !== null,
            'tem_contribuicoes' => $contrib !== null,
            'kpis' => [
                'icms_a_recolher' => [
                    'valor' => $agg['icms'],
                    'delta' => $this->calcularDelta($agg['icms'], $prevAgg['icms']),
                ],
                'pis_a_recolher' => [
                    'valor' => $agg['pis'],
                    'delta' => $this->calcularDelta($agg['pis'], $prevAgg['pis']),
                ],
                'cofins_a_recolher' => [
                    'valor' => $agg['cofins'],
                    'delta' => $this->calcularDelta($agg['cofins'], $prevAgg['cofins']),
                ],
                'retencoes_compensaveis' => [
                    'valor' => $retencoesVal,
                    'delta' => $this->calcularDelta($retencoesVal, $prevRetencoesVal),
                ],
                'saldo_liquido' => [
                    'valor' => $saldoLiquido,
                    'delta' => $this->calcularDelta($saldoLiquido, $prevSaldoLiquido),
                    // parcial = só uma das duas EFDs presente (não somar a outra como 0).
                    'parcial' => ($icms === null) !== ($contrib === null),
                ],
            ],
        ];
    }

    /** Severidade do alerta a partir do flag canônico: vermelho→alta, amarelo→media. */
    private function severidadePorFlag(?string $flag): ?string
    {
        return match ($flag) {
            'vermelho' => 'alta',
            'amarelo' => 'media',
            default => null,
        };
    }

    /** Agrega as linhas de getARecolherData por família tributária (KPIs do topo). */
    private function agregarPorFamilia(array $linhas): array
    {
        $out = ['icms' => 0.0, 'pis' => 0.0, 'cofins' => 0.0];
        foreach ($linhas as $l) {
            if (in_array($l['fonte'], ['E116', 'E250'], true)) {
                $out['icms'] += $l['valor'];
            } elseif ($l['fonte'] === 'M200') {
                $out['pis'] += $l['valor'];
            } elseif ($l['fonte'] === 'M600') {
                $out['cofins'] += $l['valor'];
            }
        }

        return array_map(fn ($v) => round($v, 2), $out);
    }

    // ─── Seção 2: Apuração ICMS/IPI ───

    public function getApuracaoIcmsData(int $userId, int $clienteId, string $competencia): array
    {
        $icms = $this->getApuracaoIcms($userId, $clienteId, $competencia);

        if (! $icms) {
            return ['tem_dados' => false];
        }

        $result = [
            'tem_dados' => true,
            'periodo_inicio' => $icms->periodo_inicio?->format('d/m/Y'),
            'periodo_fim' => $icms->periodo_fim?->format('d/m/Y'),
            'icms_proprio' => [
                'tot_debitos' => (float) $icms->icms_tot_debitos,
                'aj_debitos' => (float) $icms->icms_aj_debitos,
                'tot_aj_debitos' => (float) $icms->icms_tot_aj_debitos,
                'estornos_credito' => (float) $icms->icms_estornos_credito,
                'tot_creditos' => (float) $icms->icms_tot_creditos,
                'aj_creditos' => (float) $icms->icms_aj_creditos,
                'tot_aj_creditos' => (float) $icms->icms_tot_aj_creditos,
                'estornos_debito' => (float) $icms->icms_estornos_debito,
                'sld_credor_ant' => (float) $icms->icms_sld_credor_ant,
                'sld_apurado' => (float) $icms->icms_sld_apurado,
                'tot_deducoes' => (float) $icms->icms_tot_deducoes,
                'a_recolher' => (float) $icms->icms_a_recolher,
                'sld_credor_transportar' => (float) $icms->icms_sld_credor_transportar,
                'deb_especiais' => (float) $icms->icms_deb_especiais,
            ],
            'tem_st' => $icms->tem_st,
            'tem_difal' => $icms->tem_difal,
            'tem_ipi' => $icms->tem_ipi,
        ];

        if ($icms->tem_st) {
            $result['icms_st'] = [
                'uf' => $icms->st_uf,
                'sld_credor_ant' => (float) $icms->st_sld_credor_ant,
                'devolucoes' => (float) $icms->st_devolucoes,
                'ressarcimentos' => (float) $icms->st_ressarcimentos,
                'outros_creditos' => (float) $icms->st_outros_creditos,
                'aj_creditos' => (float) $icms->st_aj_creditos,
                'retencao' => (float) $icms->st_retencao,
                'outros_debitos' => (float) $icms->st_outros_debitos,
                'aj_debitos' => (float) $icms->st_aj_debitos,
                'sld_devedor_ant' => (float) $icms->st_sld_devedor_ant,
                'deducoes' => (float) $icms->st_deducoes,
                'icms_recolher' => (float) $icms->st_icms_recolher,
                'sld_credor_transportar' => (float) $icms->st_sld_credor_transportar,
                'deb_especiais' => (float) $icms->st_deb_especiais,
            ];
        }

        if ($icms->tem_difal) {
            $result['difal_fcp'] = $icms->difal_fcp;
        }

        $result['icms_obrigacoes'] = $icms->icms_obrigacoes['items'] ?? $icms->icms_obrigacoes ?? [];
        $result['st_obrigacoes'] = $icms->st_obrigacoes['items'] ?? $icms->st_obrigacoes ?? [];

        return $result;
    }

    // ─── Seção 3: Apuração PIS/COFINS ───

    public function getApuracaoPisCofinsData(int $userId, int $clienteId, string $competencia): array
    {
        $contrib = $this->getApuracaoContrib($userId, $clienteId, $competencia);

        if (! $contrib) {
            return ['tem_dados' => false];
        }

        return [
            'tem_dados' => true,
            'regime' => $contrib->regime,
            'pis' => [
                'nao_cumulativo' => (float) $contrib->pis_nao_cumulativo,
                'credito_descontado' => (float) $contrib->pis_credito_descontado,
                'credito_desc_ant' => (float) $contrib->pis_credito_desc_ant,
                'nc_devida' => (float) $contrib->pis_nc_devida,
                'retencao_nc' => (float) $contrib->pis_retencao_nc,
                'outras_deducoes_nc' => (float) $contrib->pis_outras_deducoes_nc,
                'nc_recolher' => (float) $contrib->pis_nc_recolher,
                'cumulativo' => (float) $contrib->pis_cumulativo,
                'retencao_cum' => (float) $contrib->pis_retencao_cum,
                'outras_deducoes_cum' => (float) $contrib->pis_outras_deducoes_cum,
                'cum_recolher' => (float) $contrib->pis_cum_recolher,
                'total_recolher' => (float) $contrib->pis_total_recolher,
            ],
            'cofins' => [
                'nao_cumulativo' => (float) $contrib->cofins_nao_cumulativo,
                'credito_descontado' => (float) $contrib->cofins_credito_descontado,
                'credito_desc_ant' => (float) $contrib->cofins_credito_desc_ant,
                'nc_devida' => (float) $contrib->cofins_nc_devida,
                'retencao_nc' => (float) $contrib->cofins_retencao_nc,
                'outras_deducoes_nc' => (float) $contrib->cofins_outras_deducoes_nc,
                'nc_recolher' => (float) $contrib->cofins_nc_recolher,
                'cumulativo' => (float) $contrib->cofins_cumulativo,
                'retencao_cum' => (float) $contrib->cofins_retencao_cum,
                'outras_deducoes_cum' => (float) $contrib->cofins_outras_deducoes_cum,
                'cum_recolher' => (float) $contrib->cofins_cum_recolher,
                'total_recolher' => (float) $contrib->cofins_total_recolher,
            ],
            'tem_creditos_nc' => $contrib->tem_creditos_nc,
            'pis_creditos_nc' => $contrib->pis_creditos_nc['items'] ?? $contrib->pis_creditos_nc ?? [],
            'cofins_creditos_nc' => $contrib->cofins_creditos_nc['items'] ?? $contrib->cofins_creditos_nc ?? [],
            'pis_detalhes' => $contrib->pis_detalhes['items'] ?? $contrib->pis_detalhes ?? [],
            'cofins_detalhes' => $contrib->cofins_detalhes['items'] ?? $contrib->cofins_detalhes ?? [],
            'pis_nao_tributado' => $contrib->pis_nao_tributado['items'] ?? $contrib->pis_nao_tributado ?? [],
            'cofins_recolher_detalhe' => $contrib->cofins_recolher_detalhe['items'] ?? $contrib->cofins_recolher_detalhe ?? [],
        ];
    }

    // ─── Seção 4: Retenções na Fonte ───

    public function getRetencoesData(int $userId, int $clienteId, string $competencia): array
    {
        [$inicio, $fim] = $this->periodoDates($competencia);

        $retencoes = EfdRetencaoFonte::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($inicio, $fim)
            ->orderBy('data_retencao')
            ->get();

        if ($retencoes->isEmpty()) {
            return ['tem_dados' => false, 'kpis' => ['total_retido' => 0, 'qtd_retencoes' => 0, 'cnpjs_unicos' => 0], 'retencoes' => [], 'por_natureza' => []];
        }

        $items = $retencoes->map(fn ($r) => [
            'data' => $r->data_retencao->format('d/m/Y'),
            'documento' => $r->documento_formatado,
            'cnpj_raw' => $r->documento,
            'natureza' => $r->natureza_formatada,
            'natureza_raw' => $r->natureza,
            'base_calculo' => (float) $r->base_calculo,
            'valor_pis' => (float) $r->valor_pis,
            'valor_cofins' => (float) $r->valor_cofins,
            'total' => (float) $r->valor_pis + (float) $r->valor_cofins,
            'cod_receita' => $r->cod_receita,
        ]);

        $totalRetido = $items->sum('total');

        $porNatureza = $items->groupBy('natureza_raw')->map(fn ($group) => [
            'natureza' => $group->first()['natureza'],
            'quantidade' => $group->count(),
            'total' => $group->sum('total'),
        ])->values();

        return [
            'tem_dados' => true,
            'kpis' => [
                'total_retido' => $totalRetido,
                'qtd_retencoes' => $retencoes->count(),
                'cnpjs_unicos' => $retencoes->pluck('documento')->unique()->count(),
            ],
            'retencoes' => $items->values()->toArray(),
            'por_natureza' => $porNatureza->toArray(),
        ];
    }

    // ─── Seção 5: Cruzamentos e Divergências ───

    public function getCruzamentosData(int $userId, int $clienteId, string $competencia): array
    {
        // ICMS/PIS/COFINS declarado×notas vêm do service ÚNICO (mesma fonte do BI).
        $cruz = $this->cruzamento->paraCompetencia($userId, $clienteId, $competencia);

        // divergência absoluta (R$) é usada como 'valor' nos alertas — derivar aqui.
        $icms = $cruz['icms'];
        $icms['divergencia_debito'] = $icms['tem_dados'] ? abs($icms['declarado_debito'] - $icms['notas_debito']) : null;
        $icms['divergencia_credito'] = $icms['tem_dados'] ? abs($icms['declarado_credito'] - $icms['notas_credito']) : null;

        // Retenções vs deduzido na apuração (específico do fechamento; F600 × M).
        $contrib = $this->getApuracaoContrib($userId, $clienteId, $competencia);
        $retData = $this->getRetencoesData($userId, $clienteId, $competencia);
        $retencoesTotal = $retData['tem_dados'] ? $retData['kpis']['total_retido'] : 0;
        $totalDeduzido = $contrib
            ? (float) $contrib->pis_retencao_nc + (float) $contrib->pis_retencao_cum
              + (float) $contrib->cofins_retencao_nc + (float) $contrib->cofins_retencao_cum
            : 0;
        $naoCompensado = $retencoesTotal - $totalDeduzido;

        return [
            'icms' => $icms,
            'pis_cofins' => $cruz['pis_cofins'],
            'retencoes' => [
                'tem_dados' => $retencoesTotal > 0 || $totalDeduzido > 0,
                'total_retido' => $retencoesTotal,
                'deduzido_apuracao' => $totalDeduzido,
                'nao_compensado' => $naoCompensado,
                'status' => $naoCompensado > 0.01 ? 'amarelo' : 'verde',
            ],
        ];
    }

    // ─── Seção 6: Alertas Fiscais ───

    public function getAlertasFiscaisData(int $userId, int $clienteId, string $competencia): array
    {
        $alertas = [];
        $cruzamentos = $this->getCruzamentosData($userId, $clienteId, $competencia);

        // Divergências derivam do flag canônico (amarelo→media, vermelho→alta) — um
        // único conjunto de limites no sistema (era >1/>5 hardcoded aqui). Ver dedup.
        if ($sev = $this->severidadePorFlag($cruzamentos['icms']['status_debito'] ?? null)) {
            $alertas[] = [
                'severidade' => $sev,
                'categoria' => 'ICMS',
                'titulo' => 'Divergência ICMS débitos',
                'descricao' => 'Diferença de '.number_format($cruzamentos['icms']['divergencia_debito_pct'], 1).'% entre apuração declarada (E110) e o consolidado das saídas (C190).',
                'valor' => $cruzamentos['icms']['divergencia_debito'],
            ];
        }

        if ($sev = $this->severidadePorFlag($cruzamentos['icms']['status_credito'] ?? null)) {
            $alertas[] = [
                'severidade' => $sev,
                'categoria' => 'ICMS',
                'titulo' => 'Divergência ICMS créditos',
                'descricao' => 'Diferença de '.number_format($cruzamentos['icms']['divergencia_credito_pct'], 1).'% entre créditos declarados (E110) e o consolidado das entradas (C190).',
                'valor' => $cruzamentos['icms']['divergencia_credito'],
            ];
        }

        if ($sev = $this->severidadePorFlag($cruzamentos['pis_cofins']['pis_status'] ?? null)) {
            $alertas[] = [
                'severidade' => $sev,
                'categoria' => 'PIS/COFINS',
                'titulo' => 'Divergência PIS a recolher vs notas',
                'descricao' => 'O PIS devido na apuração (M200) diverge em '.number_format($cruzamentos['pis_cofins']['pis_divergencia_pct'], 1).'% do débito de PIS nas saídas.',
            ];
        }

        if ($sev = $this->severidadePorFlag($cruzamentos['pis_cofins']['cofins_status'] ?? null)) {
            $alertas[] = [
                'severidade' => $sev,
                'categoria' => 'PIS/COFINS',
                'titulo' => 'Divergência COFINS a recolher vs notas',
                'descricao' => 'O COFINS devido na apuração (M600) diverge em '.number_format($cruzamentos['pis_cofins']['cofins_divergencia_pct'], 1).'% do débito de COFINS nas saídas.',
            ];
        }

        // Retenções não compensadas
        if ($cruzamentos['retencoes']['tem_dados'] && $cruzamentos['retencoes']['nao_compensado'] > 0.01) {
            $alertas[] = [
                'severidade' => 'media',
                'categoria' => 'Retenções',
                'titulo' => 'Retenções na fonte não compensadas',
                'descricao' => 'R$ '.number_format($cruzamentos['retencoes']['nao_compensado'], 2, ',', '.').' em retenções PIS/COFINS que não foram deduzidas na apuração do período.',
                'valor' => $cruzamentos['retencoes']['nao_compensado'],
            ];
        }

        // Obrigações vencidas / a vencer (E116 ICMS + E250 ST). A4: as chaves reais do
        // jsonb são ICMS_*/ST_* e a data é DDMMYYYY — antes lia dt_vcto/vl_or + Carbon::parse,
        // então NUNCA disparava. Usa o quadro 'a recolher' já normalizado (parseVencimentoEfd).
        $aRecolher = $this->getARecolherData($userId, $clienteId, $competencia);
        foreach ($aRecolher['linhas'] as $linha) {
            if ($linha['vencimento_estimado'] || ! $linha['vencimento']) {
                continue; // só obrigações com vencimento REAL (E116/E250), não PIS/COFINS estimado
            }
            $vencimento = Carbon::parse($linha['vencimento']);
            $valorFmt = number_format($linha['valor'], 2, ',', '.');
            if ($vencimento->isPast()) {
                $alertas[] = [
                    'severidade' => 'alta',
                    'categoria' => 'Obrigações',
                    'titulo' => 'Obrigação ICMS vencida',
                    'descricao' => $linha['tributo'].' — vencimento em '.$vencimento->format('d/m/Y').' — valor R$ '.$valorFmt,
                    'valor' => $linha['valor'],
                ];
            } elseif ($vencimento->diffInDays(now()) <= 7) {
                $alertas[] = [
                    'severidade' => 'media',
                    'categoria' => 'Obrigações',
                    'titulo' => 'Obrigação ICMS próxima ao vencimento',
                    'descricao' => $linha['tributo'].' vence em '.$vencimento->format('d/m/Y').' ('.$vencimento->diffInDays(now()).' dias) — valor R$ '.$valorFmt,
                    'valor' => $linha['valor'],
                ];
            }
        }

        // Ordenar por severidade
        $ordemSeveridade = ['alta' => 0, 'media' => 1, 'info' => 2];
        usort($alertas, fn ($a, $b) => ($ordemSeveridade[$a['severidade']] ?? 9) <=> ($ordemSeveridade[$b['severidade']] ?? 9));

        return [
            'resumo' => [
                'total' => count($alertas),
                'alta' => collect($alertas)->where('severidade', 'alta')->count(),
                'media' => collect($alertas)->where('severidade', 'media')->count(),
                'info' => collect($alertas)->where('severidade', 'info')->count(),
            ],
            'alertas' => $alertas,
        ];
    }

    // ─── Seção 2b: A Recolher & Vencimentos ───

    /** Rótulo por código de receita do E116 (CONFAZ/SEFAZ-RS na massa auditada). */
    private const COD_RECEITA_ICMS = ['310' => 'ICMS apuração', '350' => 'ICMS débito especial'];

    /**
     * Consolida o que a empresa recolhe no mês. Fonte das guias ICMS/ST = E116/E250
     * (`icms_obrigacoes`/`st_obrigacoes`) — cada guia traz código de receita, valor e
     * vencimento REAL. NÃO usa `icms_a_recolher` isolado: isso ignoraria o débito
     * especial (350) e o ICMS-ST (333), subestimando o desembolso (auditoria A3).
     * PIS/COFINS não têm E116 → vencimento estimado dia 25 do mês seguinte (rotulado).
     */
    public function getARecolherData(int $userId, int $clienteId, string $competencia): array
    {
        $icms = $this->getApuracaoIcms($userId, $clienteId, $competencia);
        $contrib = $this->getApuracaoContrib($userId, $clienteId, $competencia);
        $venc25 = Carbon::parse($competencia.'-01')->addMonthNoOverflow()->day(25)->toDateString();

        $linhas = [];

        if ($icms) {
            foreach ($this->itensObrigacao($icms->icms_obrigacoes) as $ob) {
                $cod = (string) ($ob['ICMS_COD_RECEITA'] ?? '');
                $linhas[] = [
                    'tributo' => self::COD_RECEITA_ICMS[$cod] ?? ('ICMS (receita '.$cod.')'),
                    'valor' => (float) ($ob['ICMS_VALOR_OBRIGACAO'] ?? 0),
                    'vencimento' => $this->parseVencimentoEfd($ob['ICMS_DATA_VENCIMENTO'] ?? null),
                    'vencimento_estimado' => false,
                    'fonte' => 'E116',
                    'cod_receita' => $cod,
                ];
            }
            foreach ($this->itensObrigacao($icms->st_obrigacoes) as $ob) {
                $linhas[] = [
                    'tributo' => 'ICMS-ST (receita '.($ob['ST_COD_RECEITA'] ?? '').')',
                    'valor' => (float) ($ob['ST_VALOR_OBRIGACAO'] ?? 0),
                    'vencimento' => $this->parseVencimentoEfd($ob['ST_DATA_VENCIMENTO'] ?? null),
                    'vencimento_estimado' => false,
                    'fonte' => 'E250',
                    'cod_receita' => (string) ($ob['ST_COD_RECEITA'] ?? ''),
                ];
            }
        }

        if ($contrib) {
            if ((float) $contrib->pis_total_recolher > 0) {
                $linhas[] = ['tributo' => 'PIS', 'valor' => (float) $contrib->pis_total_recolher, 'vencimento' => $venc25, 'vencimento_estimado' => true, 'fonte' => 'M200', 'cod_receita' => null];
            }
            if ((float) $contrib->cofins_total_recolher > 0) {
                $linhas[] = ['tributo' => 'COFINS', 'valor' => (float) $contrib->cofins_total_recolher, 'vencimento' => $venc25, 'vencimento_estimado' => true, 'fonte' => 'M600', 'cod_receita' => null];
            }
        }

        $linhas = array_values(array_filter($linhas, fn ($l) => $l['valor'] > 0));

        return [
            'tem_icms' => $icms !== null,
            'tem_contribuicoes' => $contrib !== null,
            'linhas' => $linhas,
            'total' => round(array_sum(array_column($linhas, 'valor')), 2),
        ];
    }

    /** Normaliza o jsonb de obrigações (aceita {items:[...]} ou [...]). */
    private function itensObrigacao($obrigacoes): array
    {
        if (is_array($obrigacoes)) {
            return $obrigacoes['items'] ?? $obrigacoes;
        }

        return [];
    }

    /** Vencimento do E116/E250 vem como DDMMYYYY (string). Carbon::parse erra esse formato. */
    private function parseVencimentoEfd(?string $ddmmyyyy): ?string
    {
        if (! $ddmmyyyy || strlen($ddmmyyyy) !== 8) {
            return null;
        }
        try {
            return Carbon::createFromFormat('dmY', $ddmmyyyy)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    // ─── Helpers ───

    private function calcularDelta(float $atual, float $anterior): ?array
    {
        if ($anterior == 0 && $atual == 0) {
            return ['valor' => 0, 'percentual' => 0];
        }

        $diff = $atual - $anterior;

        if ($anterior == 0) {
            return ['valor' => $diff, 'percentual' => 100];
        }

        return [
            'valor' => $diff,
            'percentual' => round(($diff / abs($anterior)) * 100, 1),
        ];
    }
}
