<?php

namespace App\Services\Efd;

use App\Models\EfdApuracaoIcms;
use App\Services\EfdAgregadorService;
use Illuminate\Support\Carbon;

/**
 * Fonte ÚNICA de "declarado × notas" no sistema. Consumido pelo Resumo Fiscal
 * (por competência) e pelo BI (série mensal) — evita que os dois calculem a
 * mesma divergência por caminhos diferentes e gerem números distintos.
 *
 * Limites de flag espelham BiService::FLAG_* (canônico): verde <=2%, amarelo
 * <=10%, vermelho >10%, sem_dado quando o arquivo de apuração não foi importado.
 */
class CruzamentoApuracaoService
{
    public const FLAG_VERDE = 2.0;    // |Δ%| <= 2
    public const FLAG_AMARELO = 10.0; // <= 10

    public function __construct(private EfdAgregadorService $efd) {}

    /**
     * Classifica uma comparação declarado×computado.
     * $temFonte=false → o arquivo daquele imposto não existe no mês: 'sem_dado'
     * (não finge conformidade verde/neutra).
     */
    public function classificarFlag(float $declarado, float $computado, bool $temFonte = true): array
    {
        $delta = $computado - $declarado;
        $pct = $declarado != 0.0
            ? round(($delta / $declarado) * 100, 2)
            : ($computado != 0.0 ? 100.0 : 0.0);
        $abs = abs($pct);

        $flag = ! $temFonte ? 'sem_dado'
            : (($declarado == 0.0 && $computado == 0.0) ? 'neutro'
                : ($abs <= self::FLAG_VERDE ? 'verde'
                    : ($abs <= self::FLAG_AMARELO ? 'amarelo' : 'vermelho')));

        return [
            'declarado' => $declarado,
            'computado' => $computado,
            'delta' => $delta,
            'delta_pct' => $pct,
            'flag' => $flag,
        ];
    }

    /**
     * Cruzamento por competência única (uma empresa, um mês). Reusa os MESMOS
     * builders que o BI (cargaDeclaradaBrutaMensal / tributarioCreditoDebito),
     * garantindo paridade de números. ICMS crédito declarado vem da apuração
     * (icms_tot_creditos), não coberto pelos builders mensais do BI.
     */
    public function paraCompetencia(int $userId, int $clienteId, string $competencia): array
    {
        $inicio = Carbon::parse($competencia.'-01')->startOfMonth()->toDateString();
        $fim = Carbon::parse($competencia.'-01')->endOfMonth()->toDateString();

        $declarado = collect($this->efd->cargaDeclaradaBrutaMensal($userId, $inicio, $fim, $clienteId))->first();
        $temIcms = (bool) ($declarado['fonte_icms'] ?? false);
        $temContrib = (bool) ($declarado['fonte_contribuicoes'] ?? false);

        $notas = $this->efd->tributarioCreditoDebito($userId, $inicio, $fim, $clienteId);

        $icmsCredDecl = (float) EfdApuracaoIcms::doUsuario($userId)->doCliente($clienteId)
            ->periodo($inicio, $fim)->value('icms_tot_creditos');

        $icmsDeb = $this->classificarFlag((float) ($declarado['icms'] ?? 0), (float) $notas['icms']['debito'], $temIcms);
        $icmsCred = $this->classificarFlag($icmsCredDecl, (float) $notas['icms']['credito'], $temIcms);
        $pis = $this->classificarFlag((float) ($declarado['pis'] ?? 0), (float) $notas['pis']['debito'], $temContrib);
        $cofins = $this->classificarFlag((float) ($declarado['cofins'] ?? 0), (float) $notas['cofins']['debito'], $temContrib);

        return [
            'icms' => [
                'tem_dados' => $temIcms,
                'declarado_debito' => $icmsDeb['declarado'], 'notas_debito' => $icmsDeb['computado'],
                'divergencia_debito_pct' => abs($icmsDeb['delta_pct']), 'status_debito' => $icmsDeb['flag'],
                'declarado_credito' => $icmsCred['declarado'], 'notas_credito' => $icmsCred['computado'],
                'divergencia_credito_pct' => abs($icmsCred['delta_pct']), 'status_credito' => $icmsCred['flag'],
            ],
            'pis_cofins' => [
                'tem_dados' => $temContrib,
                'pis_declarado' => $pis['declarado'], 'pis_notas' => $pis['computado'],
                'pis_divergencia_pct' => abs($pis['delta_pct']), 'pis_status' => $pis['flag'],
                'cofins_declarado' => $cofins['declarado'], 'cofins_notas' => $cofins['computado'],
                'cofins_divergencia_pct' => abs($cofins['delta_pct']), 'cofins_status' => $cofins['flag'],
            ],
        ];
    }
}
