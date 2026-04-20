<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar usuario principal (idempotente)
        $user = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Admin',
                'sobrenome' => 'FiscalDock',
                'email' => 'admin@fiscaldock.com.br',
                'telefone' => '67999990000',
                'password' => '12312312',
                'empresa' => 'F. DEVECCHI DAIGE E CIA LTDA',
                'cargo' => 'Administrador',
                'cnpj' => '63112970000107',
                'credits' => 10000,
            ]
        );

        // Popular planos de monitoramento
        $this->call(MonitoramentoPlanoSeeder::class);

        // Popular dados mock de monitoramento (participantes, assinaturas, consultas)
        $this->call(MonitoramentoMockDataSeeder::class);

    }
}
