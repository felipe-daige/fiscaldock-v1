<?php

namespace App\Console\Commands;

use App\Services\RegimeTributarioService;
use Illuminate\Console\Command;

class AtualizarRegimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regime:atualizar 
                            {cnpj : CNPJ para atualizar (com ou sem formatação)}
                            {regime : Regime tributário (simples_nacional, lucro_real, lucro_presumido, mei)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o regime tributário de um CNPJ manualmente';

    /**
     * Execute the console command.
     */
    public function handle(RegimeTributarioService $regimeService)
    {
        $cnpj = $this->argument('cnpj');
        $regime = $this->argument('regime');

        $regimesValidos = ['simples_nacional', 'lucro_real', 'lucro_presumido', 'mei'];

        if (!in_array($regime, $regimesValidos)) {
            $this->error("Regime inválido. Use um dos seguintes: " . implode(', ', $regimesValidos));
            return Command::FAILURE;
        }

        $this->info("Atualizando regime tributário para CNPJ: {$cnpj}...");

        $resultado = $regimeService->atualizarRegimeTributario($cnpj, $regime);

        if ($resultado) {
            $this->info("Regime tributário atualizado com sucesso para: {$regime}");
        } else {
            $this->error("Erro ao atualizar regime tributário.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}













