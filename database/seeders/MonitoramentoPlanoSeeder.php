<?php

namespace Database\Seeders;

use App\Models\MonitoramentoPlano;
use App\Support\Monitoramento\PlanoCatalog;
use Illuminate\Database\Seeder;

class MonitoramentoPlanoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PlanoCatalog::definitions() as $plano) {
            MonitoramentoPlano::updateOrCreate(
                ['codigo' => $plano['codigo']],
                $plano
            );
        }
    }
}
