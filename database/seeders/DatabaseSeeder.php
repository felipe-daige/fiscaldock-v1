<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\CreditTransaction;
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
        $creditosIniciais = 10000;

        // Criar usuario principal (idempotente)
        $user = User::updateOrCreate(
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
                'credits' => $creditosIniciais,
            ]
        );

        Cliente::updateOrCreate(
            ['documento' => '63112970000107'],
            [
                'user_id' => $user->id,
                'tipo_pessoa' => 'PJ',
                'documento' => '63112970000107',
                'nome' => 'F. DEVECCHI DAIGE E CIA LTDA',
                'razao_social' => 'F. DEVECCHI DAIGE E CIA LTDA',
                'telefone' => '67999990000',
                'email' => $user->email,
                'uf' => 'MS',
                'cep' => '79833-001',
                'municipio' => 'Dourados',
                'endereco' => 'Avenida Marcelino Pires',
                'numero' => '6385',
                'complemento' => 'Sala 7',
                'bairro' => 'Vila Sao Francisco',
                'situacao_cadastral' => 'Ativa',
                'cnpj_matriz' => '63112970000107',
                'capital_social' => 1000.00,
                'natureza_juridica' => 'Sociedade Empresaria Limitada',
                'porte' => 'Micro Empresa',
                'data_inicio_atividade' => '2025-10-09',
                'cnae_principal' => '62.03-1-00',
                'cnae_principal_descricao' => 'Desenvolvimento e licenciamento de programas de computador nao-customizaveis',
                'cnaes_secundarios' => [
                    [
                        'codigo' => '62.09-1-00',
                        'descricao' => 'Suporte tecnico, manutencao e outros servicos em tecnologia da informacao',
                    ],
                    [
                        'codigo' => '62.01-5-02',
                        'descricao' => 'Web design',
                    ],
                ],
                'qsa' => [
                    [
                        'nome' => 'Administrador FiscalDock',
                        'qualificacao' => 'Socio-Administrador',
                    ],
                ],
                'origem_tipo' => 'SEED',
                'origem_ref' => [
                    'fonte' => 'https://cnpj.biz/63112970000107',
                    'consultado_em' => '2026-05-04',
                ],
                'ativo' => true,
                'is_empresa_propria' => true,
            ]
        );

        CreditTransaction::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'purchase',
                'description' => 'Seed inicial de creditos',
            ],
            [
                'amount' => $creditosIniciais,
                'balance_after' => $creditosIniciais,
            ]
        );

        // Popular planos de monitoramento
        $this->call(MonitoramentoPlanoSeeder::class);

        // Popular dados mock de monitoramento (participantes, assinaturas, consultas)
        $this->call(MonitoramentoMockDataSeeder::class);

    }
}
