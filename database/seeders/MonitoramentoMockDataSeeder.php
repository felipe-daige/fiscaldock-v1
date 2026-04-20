<?php

namespace Database\Seeders;

use App\Models\MonitoramentoAssinatura;
use App\Models\MonitoramentoConsulta;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder para criar dados mock do módulo de Monitoramento.
 *
 * Este seeder cria:
 * - Participantes (CNPJs de empresas fictícias)
 * - Assinaturas de monitoramento com diferentes status
 * - Consultas com diferentes resultados
 *
 * Útil para testar:
 * - A automação de assinaturas via n8n
 * - A interface de monitoramento
 * - As queries SQL documentadas no CLAUDE.md
 *
 * Para executar apenas este seeder:
 *   php artisan db:seed --class=MonitoramentoMockDataSeeder
 */
class MonitoramentoMockDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar usuário principal (criado pelo DatabaseSeeder)
        $user = User::find(1);

        if (! $user) {
            $this->command->error('Usuário ID=1 não encontrado. Execute DatabaseSeeder primeiro.');
            return;
        }

        $this->command->info("Usuário de teste: {$user->email} (ID: {$user->id}, Créditos: {$user->credits})");

        // Garantir que os planos existem
        $this->call(MonitoramentoPlanoSeeder::class);

        // Criar participantes
        $participantes = $this->criarParticipantes($user);

        // Criar assinaturas
        $this->criarAssinaturas($user, $participantes);

        // Criar consultas de exemplo
        $this->criarConsultas($user, $participantes);

        $this->command->info('Dados mock do Monitoramento criados com sucesso!');
    }

    /**
     * Cria participantes de teste.
     */
    private function criarParticipantes(User $user): array
    {
        // CNPJs fictícios mas com formato válido (gerados para teste)
        $empresas = [
            [
                'cnpj' => '12345678000195',
                'razao_social' => 'EMPRESA EXEMPLO LTDA',
                'nome_fantasia' => 'EXEMPLO',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Simples Nacional',
                'uf' => 'SP',
            ],
            [
                'cnpj' => '98765432000187',
                'razao_social' => 'COMERCIO TESTE S/A',
                'nome_fantasia' => 'TESTE COMERCIO',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Lucro Presumido',
                'uf' => 'RJ',
            ],
            [
                'cnpj' => '11222333000144',
                'razao_social' => 'INDUSTRIA ABC LTDA',
                'nome_fantasia' => 'ABC INDUSTRIA',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Lucro Real',
                'uf' => 'MG',
            ],
            [
                'cnpj' => '55666777000188',
                'razao_social' => 'SERVICOS XYZ EIRELI',
                'nome_fantasia' => 'XYZ SERVICOS',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Simples Nacional',
                'uf' => 'PR',
            ],
            [
                'cnpj' => '33444555000199',
                'razao_social' => 'DISTRIBUIDORA NORTE LTDA',
                'nome_fantasia' => 'DISTRIB NORTE',
                'situacao_cadastral' => 'Suspensa',
                'regime_tributario' => 'Lucro Presumido',
                'uf' => 'RS',
            ],
            [
                'cnpj' => '77888999000166',
                'razao_social' => 'TECNOLOGIA SUL S/A',
                'nome_fantasia' => 'TECH SUL',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Lucro Real',
                'uf' => 'SC',
            ],
            [
                'cnpj' => '22333444000177',
                'razao_social' => 'CONSULTORIA CENTRO LTDA',
                'nome_fantasia' => 'CONSULT CENTRO',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Simples Nacional',
                'uf' => 'GO',
            ],
            [
                'cnpj' => '66777888000155',
                'razao_social' => 'ATACADO LESTE LTDA',
                'nome_fantasia' => 'ATACADO LESTE',
                'situacao_cadastral' => 'Baixada',
                'regime_tributario' => 'Lucro Presumido',
                'uf' => 'BA',
            ],
            [
                'cnpj' => '44555666000133',
                'razao_social' => 'LOGISTICA OESTE S/A',
                'nome_fantasia' => 'LOG OESTE',
                'situacao_cadastral' => 'Ativa',
                'regime_tributario' => 'Lucro Real',
                'uf' => 'MS',
            ],
            [
                'cnpj' => '88999000000122',
                'razao_social' => 'IMPORTADORA NACIONAL LTDA',
                'nome_fantasia' => 'IMP NACIONAL',
                'situacao_cadastral' => 'Inapta',
                'regime_tributario' => 'Lucro Real',
                'uf' => 'ES',
            ],
        ];

        $participantes = [];

        foreach ($empresas as $empresa) {
            $participante = Participante::updateOrCreate(
                ['user_id' => $user->id, 'cnpj' => $empresa['cnpj']],
                array_merge($empresa, [
                    'user_id' => $user->id,
                    'origem_tipo' => 'MANUAL',
                ])
            );
            $participantes[] = $participante;

            $this->command->info("  - Participante: {$empresa['razao_social']} ({$empresa['cnpj']})");
        }

        return $participantes;
    }

    /**
     * Cria assinaturas de monitoramento para alguns participantes.
     */
    private function criarAssinaturas(User $user, array $participantes): void
    {
        $planos = MonitoramentoPlano::all()->keyBy('codigo');

        // Definir assinaturas para diferentes cenários
        $assinaturasConfig = [
            // Assinatura ativa, próxima execução no passado (pendente de execução)
            [
                'participante_index' => 0,
                'plano_codigo' => 'licitacao',
                'status' => 'ativo',
                'frequencia_dias' => 15,
                'proxima_execucao_em' => Carbon::now()->subHours(2), // Há 2 horas (pendente!)
                'ultima_execucao_em' => Carbon::now()->subDays(15),
            ],
            // Assinatura ativa, próxima execução no passado (pendente de execução)
            [
                'participante_index' => 1,
                'plano_codigo' => 'validacao',
                'status' => 'ativo',
                'frequencia_dias' => 7,
                'proxima_execucao_em' => Carbon::now()->subHours(5), // Há 5 horas (pendente!)
                'ultima_execucao_em' => Carbon::now()->subDays(7),
            ],
            // Assinatura ativa, próxima execução no futuro
            [
                'participante_index' => 2,
                'plano_codigo' => 'compliance',
                'status' => 'ativo',
                'frequencia_dias' => 30,
                'proxima_execucao_em' => Carbon::now()->addDays(10),
                'ultima_execucao_em' => Carbon::now()->subDays(20),
            ],
            // Assinatura ativa, próxima execução no passado (pendente de execução)
            [
                'participante_index' => 3,
                'plano_codigo' => 'due_diligence',
                'status' => 'ativo',
                'frequencia_dias' => 30,
                'proxima_execucao_em' => Carbon::now()->subMinutes(30), // Há 30 min (pendente!)
                'ultima_execucao_em' => Carbon::now()->subDays(30),
            ],
            // Assinatura pausada (não será executada)
            [
                'participante_index' => 4,
                'plano_codigo' => 'licitacao',
                'status' => 'pausado',
                'frequencia_dias' => 15,
                'proxima_execucao_em' => Carbon::now()->subDays(1),
                'ultima_execucao_em' => Carbon::now()->subDays(16),
            ],
            // Assinatura cancelada (não será executada)
            [
                'participante_index' => 5,
                'plano_codigo' => 'gratuito',
                'status' => 'cancelado',
                'frequencia_dias' => 1,
                'proxima_execucao_em' => null,
                'ultima_execucao_em' => Carbon::now()->subDays(5),
            ],
            // Assinatura ativa, próxima execução no futuro (recém criada)
            [
                'participante_index' => 6,
                'plano_codigo' => 'validacao',
                'status' => 'ativo',
                'frequencia_dias' => 7,
                'proxima_execucao_em' => Carbon::now()->addDays(7),
                'ultima_execucao_em' => null,
            ],
        ];

        $this->command->info("\nCriando assinaturas:");

        foreach ($assinaturasConfig as $config) {
            $participante = $participantes[$config['participante_index']];
            $plano = $planos[$config['plano_codigo']];

            $assinatura = MonitoramentoAssinatura::updateOrCreate(
                [
                    'participante_id' => $participante->id,
                    'plano_id' => $plano->id,
                ],
                [
                    'user_id' => $user->id,
                    'status' => $config['status'],
                    'frequencia_dias' => $config['frequencia_dias'],
                    'proxima_execucao_em' => $config['proxima_execucao_em'],
                    'ultima_execucao_em' => $config['ultima_execucao_em'],
                ]
            );

            $statusIcon = match ($config['status']) {
                'ativo' => '✅',
                'pausado' => '⏸️',
                'cancelado' => '❌',
                default => '❓',
            };

            $pendente = ($config['status'] === 'ativo' && $config['proxima_execucao_em'] && $config['proxima_execucao_em'] < now())
                ? ' [PENDENTE EXECUÇÃO]'
                : '';

            $this->command->info("  {$statusIcon} {$participante->razao_social} - {$plano->nome} ({$config['status']}){$pendente}");
        }
    }

    /**
     * Cria consultas de exemplo para demonstrar diferentes resultados.
     */
    private function criarConsultas(User $user, array $participantes): void
    {
        $planos = MonitoramentoPlano::all()->keyBy('codigo');

        $this->command->info("\nCriando consultas de exemplo:");

        // Consulta avulsa com sucesso - situação regular
        $consulta1 = MonitoramentoConsulta::updateOrCreate(
            [
                'user_id' => $user->id,
                'participante_id' => $participantes[0]->id,
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'plano_id' => $planos['licitacao']->id,
                'assinatura_id' => null,
                'tipo' => 'avulso',
                'status' => 'sucesso',
                'creditos_cobrados' => 10,
                'situacao_geral' => 'regular',
                'tem_pendencias' => false,
                'proxima_validade' => Carbon::now()->addMonths(3),
                'resultado' => [
                    'cnpj' => $participantes[0]->cnpj,
                    'razao_social' => $participantes[0]->razao_social,
                    'situacao_cadastral' => 'Ativa',
                    'cnd_federal' => [
                        'situacao' => 'Regular',
                        'validade' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                        'certidao' => 'Positiva com Efeitos de Negativa',
                    ],
                    'fgts' => [
                        'situacao' => 'Regular',
                        'validade' => Carbon::now()->addMonths(3)->format('Y-m-d'),
                    ],
                ],
                'executado_em' => Carbon::now()->subDays(3),
            ]
        );
        $this->command->info("  ✅ Consulta avulsa - {$participantes[0]->razao_social} - Regular");

        // Consulta de assinatura com pendências
        $consulta2 = MonitoramentoConsulta::updateOrCreate(
            [
                'user_id' => $user->id,
                'participante_id' => $participantes[1]->id,
                'created_at' => Carbon::now()->subDays(7),
            ],
            [
                'plano_id' => $planos['validacao']->id,
                'assinatura_id' => MonitoramentoAssinatura::where('participante_id', $participantes[1]->id)->first()?->id,
                'tipo' => 'assinatura',
                'status' => 'sucesso',
                'creditos_cobrados' => 3,
                'situacao_geral' => 'atencao',
                'tem_pendencias' => true,
                'proxima_validade' => Carbon::now()->addDays(15),
                'resultado' => [
                    'cnpj' => $participantes[1]->cnpj,
                    'razao_social' => $participantes[1]->razao_social,
                    'situacao_cadastral' => 'Ativa',
                    'sintegra' => [
                        'situacao' => 'Ativa',
                        'ie' => '123456789',
                        'pendencias' => ['Débito de ICMS em aberto'],
                    ],
                ],
                'executado_em' => Carbon::now()->subDays(7),
            ]
        );
        $this->command->info("  ⚠️ Consulta assinatura - {$participantes[1]->razao_social} - Atenção (pendências)");

        // Consulta com situação irregular
        $consulta3 = MonitoramentoConsulta::updateOrCreate(
            [
                'user_id' => $user->id,
                'participante_id' => $participantes[4]->id,
                'created_at' => Carbon::now()->subDays(16),
            ],
            [
                'plano_id' => $planos['licitacao']->id,
                'assinatura_id' => MonitoramentoAssinatura::where('participante_id', $participantes[4]->id)->first()?->id,
                'tipo' => 'assinatura',
                'status' => 'sucesso',
                'creditos_cobrados' => 6,
                'situacao_geral' => 'irregular',
                'tem_pendencias' => true,
                'proxima_validade' => null,
                'resultado' => [
                    'cnpj' => $participantes[4]->cnpj,
                    'razao_social' => $participantes[4]->razao_social,
                    'situacao_cadastral' => 'Suspensa',
                    'cnd_federal' => [
                        'situacao' => 'Pendente',
                        'certidao' => 'Positiva',
                        'debitos' => [
                            ['tributo' => 'IRPJ', 'valor' => 15000.00],
                            ['tributo' => 'CSLL', 'valor' => 8500.00],
                        ],
                    ],
                    'fgts' => [
                        'situacao' => 'Irregular',
                        'pendencias' => ['FGTS em atraso - 3 competências'],
                    ],
                ],
                'executado_em' => Carbon::now()->subDays(16),
            ]
        );
        $this->command->info("  🔴 Consulta assinatura - {$participantes[4]->razao_social} - Irregular");

        // Consulta com erro
        $consulta4 = MonitoramentoConsulta::updateOrCreate(
            [
                'user_id' => $user->id,
                'participante_id' => $participantes[9]->id,
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'plano_id' => $planos['gratuito']->id,
                'assinatura_id' => null,
                'tipo' => 'avulso',
                'status' => 'erro',
                'creditos_cobrados' => 0, // Gratuito
                'situacao_geral' => null,
                'tem_pendencias' => false,
                'proxima_validade' => null,
                'resultado' => null,
                'error_code' => 'CNPJ_INAPTO',
                'error_message' => 'CNPJ encontra-se em situação INAPTA na Receita Federal. Não foi possível realizar consultas.',
                'executado_em' => Carbon::now()->subDays(1),
            ]
        );
        $this->command->info("  ❌ Consulta avulsa - {$participantes[9]->razao_social} - Erro (CNPJ inapto)");

        // Consulta pendente (ainda processando)
        $consulta5 = MonitoramentoConsulta::updateOrCreate(
            [
                'user_id' => $user->id,
                'participante_id' => $participantes[8]->id,
                'created_at' => Carbon::now()->subMinutes(5),
            ],
            [
                'plano_id' => $planos['enterprise']->id,
                'assinatura_id' => null,
                'tipo' => 'avulso',
                'status' => 'processando',
                'creditos_cobrados' => 12,
                'situacao_geral' => null,
                'tem_pendencias' => false,
                'proxima_validade' => null,
                'resultado' => null,
                'executado_em' => null,
            ]
        );
        $this->command->info("  ⏳ Consulta avulsa - {$participantes[8]->razao_social} - Processando");

        // Atualizar última consulta dos participantes
        $participantes[0]->update(['ultima_consulta_em' => Carbon::now()->subDays(3)]);
        $participantes[1]->update(['ultima_consulta_em' => Carbon::now()->subDays(7)]);
        $participantes[4]->update(['ultima_consulta_em' => Carbon::now()->subDays(16)]);
        $participantes[9]->update(['ultima_consulta_em' => Carbon::now()->subDays(1)]);
    }
}
