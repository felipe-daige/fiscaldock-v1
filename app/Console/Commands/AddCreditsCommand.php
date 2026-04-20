<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CreditService;
use Illuminate\Console\Command;

class AddCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:add {user_id : ID do usuário} {amount : Quantidade de créditos a adicionar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adiciona créditos a um usuário';

    /**
     * Execute the console command.
     */
    public function handle(CreditService $creditService)
    {
        $userId = $this->argument('user_id');
        $amount = (int) $this->argument('amount');

        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuário com ID {$userId} não encontrado.");
            return Command::FAILURE;
        }

        $saldoAnterior = $creditService->getBalance($user);

        $this->info("Usuário: {$user->name} ({$user->email})");
        $this->info("Saldo anterior: {$saldoAnterior} créditos");
        $this->info("Adicionando: {$amount} créditos");

        $creditService->add($user, $amount);

        $saldoAtual = $creditService->getBalance($user);

        $this->info("Saldo atual: {$saldoAtual} créditos");
        $this->newLine();
        $this->info("✓ Créditos adicionados com sucesso!");

        return Command::SUCCESS;
    }
}








