<?php

namespace App\Console\Commands;

use App\Services\RegimeTributarioService;
use Illuminate\Console\Command;

class ConsultarRegimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regime:consultar {cnpj : CNPJ para consultar (com ou sem formatação)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta o regime tributário de um CNPJ';

    /**
     * Execute the console command.
     */
    public function handle(RegimeTributarioService $regimeService)
    {
        $cnpj = $this->argument('cnpj');

        $this->info("Consultando regime tributário para CNPJ: {$cnpj}...");

        $regime = $regimeService->consultarRegimeTributario($cnpj);

        if ($regime) {
            $this->info("Regime tributário encontrado: {$regime}");
        } else {
            $this->warn("Regime tributário não encontrado para este CNPJ.");
        }

        return Command::SUCCESS;
    }
}













