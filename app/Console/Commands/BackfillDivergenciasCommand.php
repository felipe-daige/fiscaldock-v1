<?php

namespace App\Console\Commands;

use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\DivergenciaSnapshotService;
use Illuminate\Console\Command;
use Throwable;

class BackfillDivergenciasCommand extends Command
{
    protected $signature = 'clearance:backfill-divergencias
                            {--user= : ID do usuario para limitar o backfill}
                            {--force : Reprocessa tambem notas com comparado_em ja preenchido}';

    protected $description = 'Recalcula divergencia_severidade/count/resumo das xml_notas com snapshot SEFAZ';

    public function handle(DivergenciaSnapshotService $snapshot): int
    {
        $query = XmlNota::query()
            ->whereNotNull('situacao_sefaz');

        if ($userId = $this->option('user')) {
            $query->where('user_id', (int) $userId);
        }

        if (! $this->option('force')) {
            $query->whereNull('comparado_em');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nada a processar.');

            return 0;
        }

        $this->info("Processando {$total} nota(s)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $totais = ['atualizadas' => 0, 'sem_fontes' => 0, 'erros' => 0];

        $query->orderBy('id')->chunkById(200, function ($notas) use ($snapshot, $bar, &$totais): void {
            foreach ($notas as $nota) {
                try {
                    if ($snapshot->sincronizar($nota)) {
                        $totais['atualizadas']++;
                    } else {
                        $totais['sem_fontes']++;
                    }
                } catch (Throwable $e) {
                    $totais['erros']++;
                    $this->warn("Erro id={$nota->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Atualizadas: {$totais['atualizadas']} | Sem fontes: {$totais['sem_fontes']} | Erros: {$totais['erros']}");

        return 0;
    }
}
