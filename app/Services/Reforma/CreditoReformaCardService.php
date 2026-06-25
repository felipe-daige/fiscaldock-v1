<?php

namespace App\Services\Reforma;

use App\Models\Cliente;
use App\Models\Participante;
use App\Services\Consultas\Fiscal\TopMovimentacaoQuery;

/**
 * Monta o bloco `credito_reforma` do card "Relacionamento & Movimentação Fiscal"
 * (perspectiva contraparte = participante). 3 lentes:
 *   - fornecedor : crédito IBS/CBS em risco nas ENTRADAS (regime do CNPJ consultado)
 *   - cliente_b2b: crédito que MINHAS empresas transferem a este comprador (meu regime)
 *   - legado     : crédito destacado nas entradas no regime atual (gated por regime)
 * Reforma usa SEMPRE a alíquota pleno (2033) — valor de planejamento.
 */
class CreditoReformaCardService
{
    public function __construct(
        private CreditoRiscoReformaService $reforma,
        private TopMovimentacaoQuery $top,
    ) {}

    /**
     * @param  array<string, mixed>  $fiscalResumo  saída de ParticipanteFiscalResumoService
     * @return array<string, mixed>|null
     */
    public function montar(int $userId, Participante $participante, array $fiscalResumo): ?array
    {
        $out = [];

        if ((int) ($fiscalResumo['qtd_entrada'] ?? 0) > 0) {
            $out['fornecedor'] = $this->reforma->creditoParticipante(
                $participante,
                (float) ($fiscalResumo['total_comprado'] ?? 0),
                $participante->score?->score_credito_reforma,
                null, // pleno (2033)
            );
        }

        if ((int) ($fiscalResumo['qtd_saida'] ?? 0) > 0) {
            $b2b = $this->espelhoB2b($userId, $fiscalResumo['relacionamentos'] ?? []);
            if ($b2b !== null) {
                $out['cliente_b2b'] = $b2b;
            }
        }

        $destacado = $this->top->creditosDestacados($userId, 'participante_id', [$participante->id]);
        if (($destacado[$participante->id] ?? 0) > 0) {
            $out['legado'] = ['destacado' => $destacado[$participante->id]];
        }

        return $out === [] ? null : $out;
    }

    /**
     * Crédito que as MINHAS empresas vendedoras transferem a este comprador (pleno).
     * Fator vem do regime das minhas empresas. Regime não identificado conta como 0 transferido.
     *
     * @param  array<int, array<string, mixed>>  $relacionamentos  empresas (empresa_id) com valor_saida
     * @return array{volume:float,credito_potencial:float,credito_transferido:float,fator:float,flag:string}|null
     */
    private function espelhoB2b(int $userId, array $relacionamentos): ?array
    {
        $linhas = array_values(array_filter($relacionamentos, fn ($e) => ($e['valor_saida'] ?? 0) > 0));
        if ($linhas === []) {
            return null;
        }

        $empresas = Cliente::with('score')
            ->where('user_id', $userId)
            ->whereIn('id', array_map(fn ($e) => $e['empresa_id'], $linhas))
            ->get()
            ->keyBy('id');

        $volume = $potencial = $transferido = 0.0;

        foreach ($linhas as $e) {
            $empresa = $empresas[$e['empresa_id']] ?? null;
            if ($empresa === null) {
                continue;
            }
            $valor = (float) $e['valor_saida'];
            $r = $this->reforma->creditoParticipante($empresa, $valor, $empresa->score?->score_credito_reforma, null);
            $volume += $valor;
            $potencial += $r['credito_potencial'];
            $transferido += $r['credito_em_risco'] === null ? 0.0 : ($r['credito_potencial'] - $r['credito_em_risco']);
        }

        if ($volume <= 0) {
            return null;
        }

        $fator = $potencial > 0 ? round($transferido / $potencial, 4) : 0.0;

        return [
            'volume' => round($volume, 2),
            'credito_potencial' => round($potencial, 2),
            'credito_transferido' => round($transferido, 2),
            'fator' => $fator,
            'flag' => match (true) {
                $fator >= 1.0 => 'verde',
                $fator <= 0.0 => 'vermelho',
                default => 'amarelo',
            },
        ];
    }
}
