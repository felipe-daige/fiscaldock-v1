<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function exportPlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('gratuito') ?? MonitoramentoPlano::firstOrFail();
}

function exportLote(User $user, array $overrides = []): ConsultaLote
{
    return ConsultaLote::create(array_merge([
        'user_id' => $user->id,
        'plano_id' => exportPlano()->id,
        'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1,
        'creditos_cobrados' => 0,
        'tab_id' => 'tab-export-'.uniqid(),
        'processado_em' => now(),
    ], $overrides));
}

function exportResultadoParticipante(ConsultaLote $lote, User $user, string $documento = '12345678000199'): void
{
    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => $documento,
        'razao_social' => 'Fornecedor Export',
        'uf' => 'SP',
        'crt' => '3',
    ]);

    $lote->participantes()->attach([$participante->id]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $participante->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['situacao_cadastral' => 'ATIVA'],
        'consultado_em' => now(),
    ]);
}

it('baixa Excel (csv) de lote com resultados', function () {
    $user = User::factory()->create();
    $lote = exportLote($user);
    exportResultadoParticipante($lote, $user);

    $resp = actingAs($user)->get("/app/consulta/lote/{$lote->id}/baixar?formato=csv");

    $resp->assertOk();
    expect($resp->headers->get('content-type'))->toContain('text/csv');
    expect($resp->headers->get('content-disposition'))->toContain('attachment');
    expect($resp->streamedContent())->toContain('12.345.678/0001-99');
});

it('baixa PDF de lote com resultados', function () {
    $user = User::factory()->create();
    $lote = exportLote($user);
    exportResultadoParticipante($lote, $user);

    $resp = actingAs($user)->get("/app/consulta/lote/{$lote->id}/baixar?formato=pdf");

    $resp->assertOk();
    expect($resp->headers->get('content-type'))->toContain('application/pdf');
    expect($resp->headers->get('content-disposition'))->toContain('attachment');
});

it('nao retorna pagina de erro ao baixar lote finalizado sem resultados', function () {
    $user = User::factory()->create();
    $lote = exportLote($user); // finalizado, zero resultados

    actingAs($user)
        ->get("/app/consulta/lote/{$lote->id}/baixar?formato=csv")
        ->assertRedirect('/app/consulta/historico');
});

it('exporta resultado de escopo CLIENTE (participante nulo) sem corromper o arquivo', function () {
    $user = User::factory()->create();
    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'razao_social' => 'Cliente Export Ltda',
        'uf' => 'MS',
    ]);

    $lote = exportLote($user);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => null,
        'cliente_id' => $cliente->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'razao_social' => 'Cliente Export Ltda',
        ],
        'consultado_em' => now(),
    ]);

    $resp = actingAs($user)->get("/app/consulta/lote/{$lote->id}/baixar?formato=csv");

    $resp->assertOk();
    $body = $resp->streamedContent();
    expect($body)->toContain('97.551.165/0001-93');
    expect($body)->toContain('Cliente Export Ltda');
    // sem warning "Attempt to read property" vazado pro stream
    expect($body)->not->toContain('Attempt to read property');
});

it('CSV preenche SINTEGRA IE e validades das CNDs (chaves normalizadas)', function () {
    $user = User::factory()->create();

    // Plano que inclui sintegra + CNDs no CSV.
    $plano = MonitoramentoPlano::porCodigo('due_diligence')
        ?? MonitoramentoPlano::porCodigo('compliance')
        ?? exportPlano();

    $lote = exportLote($user, ['plano_id' => $plano->id]);

    $p = Participante::create([
        'user_id' => $user->id,
        'documento' => '12345678000199',
        'razao_social' => 'Fornecedor IE',
        'uf' => 'SP',
        'crt' => '3',
    ]);
    $lote->participantes()->attach([$p->id]);

    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id,
        'participante_id' => $p->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => [
            'situacao_cadastral' => 'ATIVA',
            'sintegra' => ['inscricao_estadual' => '111.222.333.444', 'situacao' => 'Habilitado'],
            'cnd_federal' => ['status' => 'Negativa', 'data_validade' => '2026-12-31'],
            'cnd_estadual' => ['status' => 'Negativa', 'data_validade' => '2026-11-30'],
            'cndt' => ['status' => 'Negativa', 'data_validade' => '2026-10-15'],
        ],
        'consultado_em' => now(),
    ]);

    $body = actingAs($user)->get("/app/consulta/lote/{$lote->id}/baixar?formato=csv")->streamedContent();

    expect($body)->toContain('111.222.333.444')   // SINTEGRA IE
        ->toContain('2026-12-31')                  // CND Federal Validade
        ->toContain('2026-11-30')                  // CND Estadual Validade
        ->toContain('2026-10-15');                 // CNDT Validade
});

it('historico mostra botao Excel e PDF para lote com resultados', function () {
    $user = User::factory()->create();
    $lote = exportLote($user);
    exportResultadoParticipante($lote, $user);

    actingAs($user)
        ->get('/app/consulta/historico')
        ->assertOk()
        ->assertSee('Excel (CSV)', false)
        ->assertSee("/app/consulta/lote/{$lote->id}/baixar?formato=csv", false)
        ->assertSee("/app/consulta/lote/{$lote->id}/baixar?formato=pdf", false);
});

it('historico nao mostra link de exportacao para lote finalizado sem resultados', function () {
    $user = User::factory()->create();
    $lote = exportLote($user); // sem resultados

    actingAs($user)
        ->get('/app/consulta/historico')
        ->assertOk()
        ->assertDontSee("/app/consulta/lote/{$lote->id}/baixar?formato=csv", false)
        ->assertDontSee("/app/consulta/lote/{$lote->id}/baixar?formato=pdf", false);
});

it('exporta xlsx quando a lib está disponível', function () {
    if (! \App\Support\Reports\XlsxReport::disponivel()) {
        $this->markTestSkipped('OpenSpout não instalado (rebuild pendente).');
    }

    $user = User::factory()->create();
    $lote = exportLote($user);
    exportResultadoParticipante($lote, $user);

    $resp = actingAs($user)->get("/app/consulta/lote/{$lote->id}/baixar?formato=xlsx");

    $resp->assertOk();
    $resp->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
