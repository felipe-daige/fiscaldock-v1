<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AlertaCentralService;
use Illuminate\Console\Command;

class RecalcularAlertasCommand extends Command
{
    protected $signature = 'alertas:recalcular {user? : ID do usuario especifico}';

    protected $description = 'Recalcula alertas para todos os usuarios ou um especifico';

    public function handle(AlertaCentralService $service): int
    {
        $userId = $this->argument('user');

        if ($userId) {
            $resultado = $service->recalcular((int) $userId);
            $this->info("Alertas recalculados para usuario {$userId}: {$resultado['novos']} novos, {$resultado['atualizados']} atualizados, {$resultado['resolvidos']} resolvidos.");

            return 0;
        }

        $users = User::pluck('id');
        $bar = $this->output->createProgressBar($users->count());

        $totais = ['novos' => 0, 'atualizados' => 0, 'resolvidos' => 0];

        foreach ($users as $id) {
            try {
                $resultado = $service->recalcular($id);
                $totais['novos'] += $resultado['novos'];
                $totais['atualizados'] += $resultado['atualizados'];
                $totais['resolvidos'] += $resultado['resolvidos'];
            } catch (\Throwable $e) {
                $this->warn("Erro ao recalcular para usuario {$id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Alertas recalculados para {$users->count()} usuarios: {$totais['novos']} novos, {$totais['atualizados']} atualizados, {$totais['resolvidos']} resolvidos.");

        return 0;
    }
}
