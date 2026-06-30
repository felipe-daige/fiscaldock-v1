<?php

namespace Database\Seeders;

use App\Models\AdminPendencia;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminPendenciaSeeder extends Seeder
{
    public function run(): void
    {
        $autor = User::where('is_admin', true)->orderBy('id')->first() ?? User::orderBy('id')->first();

        AdminPendencia::firstOrCreate(
            ['titulo' => 'Revisar premissa do Simples híbrido (Reforma IBS/CBS)'],
            [
                'nota' => "Marcio (contador) confirmou: as alíquotas de IBS/CBS dentro do Simples híbrido só serão publicadas pelo governo a partir de ~nov/2026 (provável pós-eleição). Até lá não há número definido. Hoje o sistema trata TODO Simples como unificado (fator 0,15) — ver config/reforma.php e docs/score-fiscal/credito-reforma.md. Quando sair, avaliar implementar o ramo híbrido (fator ~1,0) e a fonte de dado da opção semestral.",
                'lembrar_em' => '2026-11-03',
                'status' => AdminPendencia::STATUS_ABERTA,
                'criado_por' => $autor?->id,
            ],
        );
    }
}
