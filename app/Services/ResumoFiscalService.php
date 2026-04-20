<?php

namespace App\Services;

use App\Models\EfdApuracaoContribuicao;
use App\Models\EfdApuracaoIcms;
use App\Models\EfdNota;
use App\Models\EfdRetencaoFonte;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResumoFiscalService
{
    private function periodoDates(string $competencia): array
    {
        $inicio = Carbon::parse($competencia . '-01')->startOfMonth();
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

        $icmsRecolher = (float) ($icms->icms_a_recolher ?? 0);
        $pisRecolher = (float) ($contrib->pis_total_recolher ?? 0);
        $cofinsRecolher = (float) ($contrib->cofins_total_recolher ?? 0);
        $retencoesVal = (float) ($retencoes->pis ?? 0) + (float) ($retencoes->cofins ?? 0);
        $saldoLiquido = $icmsRecolher + $pisRecolher + $cofinsRecolher - $retencoesVal;

        // Mês anterior para deltas
        $prevComp = Carbon::parse($competencia . '-01')->subMonth()->format('Y-m');
        $prevIcms = $this->getApuracaoIcms($userId, $clienteId, $prevComp);
        $prevContrib = $this->getApuracaoContrib($userId, $clienteId, $prevComp);
        [$prevInicio, $prevFim] = $this->periodoDates($prevComp);

        $prevRetencoes = EfdRetencaoFonte::doUsuario($userId)
            ->doCliente($clienteId)
            ->periodo($prevInicio, $prevFim)
            ->selectRaw('COALESCE(SUM(valor_pis), 0) as pis, COALESCE(SUM(valor_cofins), 0) as cofins')
            ->first();

        $prevIcmsRecolher = (float) ($prevIcms->icms_a_recolher ?? 0);
        $prevPisRecolher = (float) ($prevContrib->pis_total_recolher ?? 0);
        $prevCofinsRecolher = (float) ($prevContrib->cofins_total_recolher ?? 0);
        $prevRetencoesVal = (float) ($prevRetencoes->pis ?? 0) + (float) ($prevRetencoes->cofins ?? 0);
        $prevSaldoLiquido = $prevIcmsRecolher + $prevPisRecolher + $prevCofinsRecolher - $prevRetencoesVal;

        return [
            'tem_dados' => $icms !== null || $contrib !== null,
            'kpis' => [
                'icms_a_recolher' => [
                    'valor' => $icmsRecolher,
                    'delta' => $this->calcularDelta($icmsRecolher, $prevIcmsRecolher),
                ],
                'pis_a_recolher' => [
                    'valor' => $pisRecolher,
                    'delta' => $this->calcularDelta($pisRecolher, $prevPisRecolher),
                ],
                'cofins_a_recolher' => [
                    'valor' => $cofinsRecolher,
                    'delta' => $this->calcularDelta($cofinsRecolher, $prevCofinsRecolher),
                ],
                'retencoes_compensaveis' => [
                    'valor' => $retencoesVal,
                    'delta' => $this->calcularDelta($retencoesVal, $prevRetencoesVal),
                ],
                'saldo_liquido' => [
                    'valor' => $saldoLiquido,
                    'delta' => $this->calcularDelta($saldoLiquido, $prevSaldoLiquido),
                ],
            ],
        ];
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
        [$inicio, $fim] = $this->periodoDates($competencia);

        // 5a. ICMS declarado vs soma notas
        $icms = $this->getApuracaoIcms($userId, $clienteId, $competencia);

        $notasIcms = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->where('n.cliente_id', $clienteId)
            ->where('n.origem_arquivo', 'fiscal')
            ->whereBetween('n.data_emissao', [$inicio, $fim])
            ->selectRaw("
                SUM(CASE WHEN n.tipo_operacao = 'saida' THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as debito_notas,
                SUM(CASE WHEN n.tipo_operacao = 'entrada' THEN COALESCE(i.valor_icms, 0) ELSE 0 END) as credito_notas
            ")
            ->first();

        $icmsDecDebito = $icms ? (float) $icms->icms_tot_debitos : null;
        $icmsDecCredito = $icms ? (float) $icms->icms_tot_creditos : null;
        $icmsNotasDebito = (float) ($notasIcms->debito_notas ?? 0);
        $icmsNotasCredito = (float) ($notasIcms->credito_notas ?? 0);

        // 5b. PIS/COFINS declarado vs soma notas
        $contrib = $this->getApuracaoContrib($userId, $clienteId, $competencia);

        $notasPisCofins = DB::table('efd_notas_itens as i')
            ->join('efd_notas as n', 'n.id', '=', 'i.efd_nota_id')
            ->where('n.user_id', $userId)
            ->where('n.cliente_id', $clienteId)
            ->whereBetween('n.data_emissao', [$inicio, $fim])
            ->selectRaw('
                SUM(COALESCE(i.valor_pis, 0)) as total_pis_notas,
                SUM(COALESCE(i.valor_cofins, 0)) as total_cofins_notas
            ')
            ->first();

        $pisDeclarado = $contrib ? (float) $contrib->pis_total_recolher : null;
        $cofinsDeclarado = $contrib ? (float) $contrib->cofins_total_recolher : null;
        $pisNotas = (float) ($notasPisCofins->total_pis_notas ?? 0);
        $cofinsNotas = (float) ($notasPisCofins->total_cofins_notas ?? 0);

        // 5c. Retenções vs deduzido na apuração
        $retencoesTotal = 0;
        $retData = $this->getRetencoesData($userId, $clienteId, $competencia);
        if ($retData['tem_dados']) {
            $retencoesTotal = $retData['kpis']['total_retido'];
        }

        $deduzidoPis = $contrib
            ? (float) $contrib->pis_retencao_nc + (float) $contrib->pis_retencao_cum
            : 0;
        $deduzidoCofins = $contrib
            ? (float) $contrib->cofins_retencao_nc + (float) $contrib->cofins_retencao_cum
            : 0;
        $totalDeduzido = $deduzidoPis + $deduzidoCofins;
        $naoCompensado = $retencoesTotal - $totalDeduzido;

        return [
            'icms' => [
                'tem_dados' => $icms !== null,
                'declarado_debito' => $icmsDecDebito,
                'notas_debito' => $icmsNotasDebito,
                'divergencia_debito' => $icmsDecDebito !== null ? abs($icmsDecDebito - $icmsNotasDebito) : null,
                'divergencia_debito_pct' => $this->calcularDivergencia($icmsDecDebito, $icmsNotasDebito),
                'status_debito' => $this->statusSemaforo($this->calcularDivergencia($icmsDecDebito, $icmsNotasDebito)),
                'declarado_credito' => $icmsDecCredito,
                'notas_credito' => $icmsNotasCredito,
                'divergencia_credito' => $icmsDecCredito !== null ? abs($icmsDecCredito - $icmsNotasCredito) : null,
                'divergencia_credito_pct' => $this->calcularDivergencia($icmsDecCredito, $icmsNotasCredito),
                'status_credito' => $this->statusSemaforo($this->calcularDivergencia($icmsDecCredito, $icmsNotasCredito)),
            ],
            'pis_cofins' => [
                'tem_dados' => $contrib !== null,
                'pis_declarado' => $pisDeclarado,
                'pis_notas' => $pisNotas,
                'pis_divergencia_pct' => $this->calcularDivergencia($pisDeclarado, $pisNotas),
                'pis_status' => $this->statusSemaforo($this->calcularDivergencia($pisDeclarado, $pisNotas)),
                'cofins_declarado' => $cofinsDeclarado,
                'cofins_notas' => $cofinsNotas,
                'cofins_divergencia_pct' => $this->calcularDivergencia($cofinsDeclarado, $cofinsNotas),
                'cofins_status' => $this->statusSemaforo($this->calcularDivergencia($cofinsDeclarado, $cofinsNotas)),
            ],
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

        // Divergência ICMS débitos
        if ($cruzamentos['icms']['tem_dados'] && ($cruzamentos['icms']['divergencia_debito_pct'] ?? 0) > 1) {
            $alertas[] = [
                'severidade' => 'alta',
                'categoria' => 'ICMS',
                'titulo' => 'Divergência ICMS débitos',
                'descricao' => 'Diferença de ' . number_format($cruzamentos['icms']['divergencia_debito_pct'], 1) . '% entre apuração declarada e soma dos itens das notas fiscais.',
                'valor' => $cruzamentos['icms']['divergencia_debito'],
            ];
        }

        // Divergência ICMS créditos
        if ($cruzamentos['icms']['tem_dados'] && ($cruzamentos['icms']['divergencia_credito_pct'] ?? 0) > 1) {
            $alertas[] = [
                'severidade' => 'alta',
                'categoria' => 'ICMS',
                'titulo' => 'Divergência ICMS créditos',
                'descricao' => 'Diferença de ' . number_format($cruzamentos['icms']['divergencia_credito_pct'], 1) . '% entre créditos declarados e soma dos itens das notas de entrada.',
                'valor' => $cruzamentos['icms']['divergencia_credito'],
            ];
        }

        // Divergência PIS
        if ($cruzamentos['pis_cofins']['tem_dados'] && ($cruzamentos['pis_cofins']['pis_divergencia_pct'] ?? 0) > 5) {
            $alertas[] = [
                'severidade' => 'media',
                'categoria' => 'PIS/COFINS',
                'titulo' => 'Divergência PIS a recolher vs notas',
                'descricao' => 'O PIS a recolher na apuração diverge em ' . number_format($cruzamentos['pis_cofins']['pis_divergencia_pct'], 1) . '% do PIS calculado nos itens das notas.',
            ];
        }

        // Divergência COFINS
        if ($cruzamentos['pis_cofins']['tem_dados'] && ($cruzamentos['pis_cofins']['cofins_divergencia_pct'] ?? 0) > 5) {
            $alertas[] = [
                'severidade' => 'media',
                'categoria' => 'PIS/COFINS',
                'titulo' => 'Divergência COFINS a recolher vs notas',
                'descricao' => 'O COFINS a recolher na apuração diverge em ' . number_format($cruzamentos['pis_cofins']['cofins_divergencia_pct'], 1) . '% do COFINS calculado nos itens.',
            ];
        }

        // Retenções não compensadas
        if ($cruzamentos['retencoes']['tem_dados'] && $cruzamentos['retencoes']['nao_compensado'] > 0.01) {
            $alertas[] = [
                'severidade' => 'media',
                'categoria' => 'Retenções',
                'titulo' => 'Retenções na fonte não compensadas',
                'descricao' => 'R$ ' . number_format($cruzamentos['retencoes']['nao_compensado'], 2, ',', '.') . ' em retenções PIS/COFINS que não foram deduzidas na apuração do período.',
                'valor' => $cruzamentos['retencoes']['nao_compensado'],
            ];
        }

        // Obrigações vencidas (E116)
        $icms = $this->getApuracaoIcms($userId, $clienteId, $competencia);
        if ($icms) {
            $obrigacoes = $icms->icms_obrigacoes['items'] ?? $icms->icms_obrigacoes ?? [];
            if (is_array($obrigacoes)) {
                foreach ($obrigacoes as $ob) {
                    $dtVcto = $ob['dt_vcto'] ?? $ob['data_vencimento'] ?? null;
                    if ($dtVcto) {
                        $vencimento = Carbon::parse($dtVcto);
                        if ($vencimento->isPast()) {
                            $alertas[] = [
                                'severidade' => 'alta',
                                'categoria' => 'Obrigações',
                                'titulo' => 'Obrigação ICMS vencida',
                                'descricao' => 'Vencimento em ' . $vencimento->format('d/m/Y') . ' — valor R$ ' . number_format((float) ($ob['vl_or'] ?? $ob['valor_obrigacao'] ?? 0), 2, ',', '.'),
                                'valor' => (float) ($ob['vl_or'] ?? $ob['valor_obrigacao'] ?? 0),
                            ];
                        } elseif ($vencimento->diffInDays(now()) <= 7) {
                            $alertas[] = [
                                'severidade' => 'media',
                                'categoria' => 'Obrigações',
                                'titulo' => 'Obrigação ICMS próxima ao vencimento',
                                'descricao' => 'Vence em ' . $vencimento->format('d/m/Y') . ' (' . $vencimento->diffInDays(now()) . ' dias) — valor R$ ' . number_format((float) ($ob['vl_or'] ?? $ob['valor_obrigacao'] ?? 0), 2, ',', '.'),
                                'valor' => (float) ($ob['vl_or'] ?? $ob['valor_obrigacao'] ?? 0),
                            ];
                        }
                    }
                }
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

    private function calcularDivergencia(?float $declarado, float $notas): ?float
    {
        if ($declarado === null) {
            return null;
        }

        if ($declarado == 0 && $notas == 0) {
            return 0;
        }

        if ($declarado == 0) {
            return 100;
        }

        return round(abs($declarado - $notas) / abs($declarado) * 100, 2);
    }

    private function statusSemaforo(?float $divergenciaPct): string
    {
        if ($divergenciaPct === null) {
            return 'sem_dados';
        }

        if ($divergenciaPct <= 1) {
            return 'verde';
        }

        if ($divergenciaPct <= 5) {
            return 'amarelo';
        }

        return 'vermelho';
    }
}
