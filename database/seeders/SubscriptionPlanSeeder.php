<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Fonte da composição comercial (spec 2026-06-08-tiers-entitlements-cfo-design).
     * Editável no futuro painel admin; aqui é só o seed inicial.
     */
    public function run(): void
    {
        foreach ($this->definitions() as $def) {
            SubscriptionPlan::updateOrCreate(['codigo' => $def['codigo']], $def);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function definitions(): array
    {
        return [
            [
                'codigo' => 'free', 'nome' => 'Free', 'ordem' => 1,
                'preco_mensal_centavos' => 0, 'preco_anual_centavos' => 0,
                'creditos_inclusos' => 0, 'faixa_slug' => 'base',
                'limite_clientes' => 1, 'limite_cnpjs_monitorados' => 1,
                'frequencia_padrao_dias' => 30, 'profundidade_auto_monitor' => 'cadastral',
                'assentos_inclusos' => 1, 'rollover_cap_multiplicador' => 1, 'is_active' => true,
                'capabilities' => [
                    'bi' => 'basico', 'export' => [], 'pdf_executivo' => false,
                    'clearance_lote' => false, 'clearance_full' => false,
                    'score_historico' => false, 'retencao_meses' => 6,
                    'frequencia_minima_dias' => 30,
                ],
            ],
            [
                'codigo' => 'essencial', 'nome' => 'Essencial', 'ordem' => 2,
                'preco_mensal_centavos' => 9900, 'preco_anual_centavos' => 99000,
                'creditos_inclusos' => 300, 'faixa_slug' => 'base',
                'limite_clientes' => 15, 'limite_cnpjs_monitorados' => 10,
                'frequencia_padrao_dias' => 30, 'profundidade_auto_monitor' => 'licitacao',
                'assentos_inclusos' => 1, 'rollover_cap_multiplicador' => 1, 'is_active' => true,
                'capabilities' => [
                    'bi' => 'completo', 'export' => ['csv'], 'pdf_executivo' => false,
                    'clearance_lote' => true, 'clearance_full' => false,
                    'score_historico' => false, 'retencao_meses' => null,
                    'frequencia_minima_dias' => 30,
                ],
            ],
            [
                'codigo' => 'profissional', 'nome' => 'Profissional', 'ordem' => 3,
                'preco_mensal_centavos' => 29900, 'preco_anual_centavos' => 299000,
                'creditos_inclusos' => 1100, 'faixa_slug' => 'x',
                'limite_clientes' => 50, 'limite_cnpjs_monitorados' => 40,
                'frequencia_padrao_dias' => 30, 'profundidade_auto_monitor' => 'compliance',
                'assentos_inclusos' => 3, 'rollover_cap_multiplicador' => 1, 'is_active' => true,
                'capabilities' => [
                    'bi' => 'completo', 'export' => ['csv', 'excel'], 'pdf_executivo' => true,
                    'clearance_lote' => true, 'clearance_full' => false,
                    'score_historico' => true, 'retencao_meses' => null,
                    'frequencia_minima_dias' => 15,
                ],
            ],
            [
                'codigo' => 'escritorio', 'nome' => 'Escritório', 'ordem' => 4,
                'preco_mensal_centavos' => 79900, 'preco_anual_centavos' => 799000,
                'creditos_inclusos' => 3000, 'faixa_slug' => 'y',
                'limite_clientes' => 150, 'limite_cnpjs_monitorados' => 120,
                'frequencia_padrao_dias' => 30, 'profundidade_auto_monitor' => 'compliance',
                'assentos_inclusos' => 10, 'rollover_cap_multiplicador' => 1, 'is_active' => true,
                'capabilities' => [
                    'bi' => 'completo', 'export' => ['csv', 'excel'], 'pdf_executivo' => true,
                    'clearance_lote' => true, 'clearance_full' => true,
                    'score_historico' => true, 'retencao_meses' => null,
                    'frequencia_minima_dias' => 7,
                ],
            ],
            [
                'codigo' => 'enterprise', 'nome' => 'Enterprise', 'ordem' => 5,
                'preco_mensal_centavos' => 0, 'preco_anual_centavos' => 0, // sob consulta
                'creditos_inclusos' => 0, 'faixa_slug' => 'z',
                'limite_clientes' => null, 'limite_cnpjs_monitorados' => null,
                'frequencia_padrao_dias' => 1, 'profundidade_auto_monitor' => 'due_diligence',
                'assentos_inclusos' => 9999, 'rollover_cap_multiplicador' => 1, 'is_active' => true,
                'capabilities' => [
                    'bi' => 'completo', 'export' => ['csv', 'excel', 'api'], 'pdf_executivo' => true,
                    'clearance_lote' => true, 'clearance_full' => true,
                    'score_historico' => true, 'retencao_meses' => null,
                    'frequencia_minima_dias' => 1,
                ],
            ],
        ];
    }
}
