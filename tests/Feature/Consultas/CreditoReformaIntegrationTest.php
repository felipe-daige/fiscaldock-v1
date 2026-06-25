<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Testa a wiring real: controller → buildConsultaLoteResultadosDetalhe → fiscal_resumo
 * inclui credito_reforma → partial renderiza "Crédito tributário".
 *
 * Deve FALHAR antes do Fix 1 (credito_reforma era irmão de fiscal_resumo, não aninhado)
 * e PASSAR depois.
 */
it('renderiza bloco Crédito tributário no detalhe do lote via rota real', function () {
    $user = User::factory()->create();

    // Empresa própria do usuário (necessária p/ ParticipanteFiscalResumoService calcular entradas)
    $empresa = Cliente::create([
        'user_id' => $user->id,
        'documento' => '11222333000181',
        'razao_social' => 'Minha Empresa Própria',
        'is_empresa_propria' => true,
    ]);

    // Participante (fornecedor MEI → regime Simples, gera crédito parcial)
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '99887766000155',
        'razao_social' => 'Fornecedor MEI Reforma',
        'uf' => 'SP',
        'crt' => '1', // Simples Nacional
    ]);

    // Importação EFD necessária como FK (efd_notas.importacao_id NOT NULL)
    $importacao = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $empresa->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'cnpj' => '11222333000181',
        'periodo_inicio' => now()->subDays(60)->toDateString(),
        'periodo_fim' => now()->subDays(30)->toDateString(),
        'arquivo_hash' => sha1('fake-integration-test'),
        'filename' => 'teste.txt',
        'status' => 'concluido',
    ]);

    // Nota de entrada (this empresa bought from participante) — alimenta qtd_entrada > 0
    EfdNota::create([
        'user_id' => $user->id,
        'cliente_id' => $empresa->id,
        'participante_id' => $participante->id,
        'importacao_id' => $importacao->id,
        'chave_acesso' => '35260111222333000181550010000001001234567890',
        'modelo' => '55',
        'numero' => '1',
        'serie' => '1',
        'data_emissao' => now()->subDays(30)->toDateString(),
        'tipo_operacao' => 'entrada',
        'valor_total' => 50000.00,
        'origem_arquivo' => 'fiscal',
        'cancelada' => false,
    ]);

    $plano = MonitoramentoPlano::porCodigo('licitacao') ?? MonitoramentoPlano::firstOrFail();

    $lote = ConsultaLote::create([
        'user_id' => $user->id,
        'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-reforma-integ',
        'processado_em' => now(),
    ]);

    $lote->participantes()->attach([$participante->id]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'cnd_federal' => ['status' => 'regular'],
        ],
        'consultado_em' => now(),
    ]);

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}")
        ->assertOk()
        ->assertSee('Crédito tributário');
});
