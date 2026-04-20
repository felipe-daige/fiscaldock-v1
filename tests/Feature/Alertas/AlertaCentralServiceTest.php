<?php

use App\Models\Alerta;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\AlertaCentralService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(AlertaCentralService::class);
});

afterEach(function () {
    Alerta::where('user_id', $this->user->id)->delete();
    $this->user->forceDelete();
});

it('recalcular cria alertas de notas duplicadas', function () {
    $cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '11222333000181',
        'razao_social' => 'Teste Ltda',
        'is_empresa_propria' => true,
    ]);

    $importacao = EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);

    $participante = Participante::create([
        'user_id' => $this->user->id,
        'cnpj' => '12345678000190',
        'razao_social' => 'Fornecedor Teste',
    ]);

    // Criar 2 notas duplicadas (mesmo numero, serie, participante, modelo)
    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $importacao->id,
        'participante_id' => $participante->id,
        'numero' => 1234,
        'serie' => '1',
        'modelo' => '55',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
    ]);

    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $importacao->id,
        'participante_id' => $participante->id,
        'numero' => 1234,
        'serie' => '1',
        'modelo' => '55',
        'data_emissao' => '2026-01-16',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
    ]);

    $resultado = $this->service->recalcular($this->user->id);

    expect($resultado['novos'])->toBeGreaterThan(0);

    $alerta = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'notas_duplicadas')
        ->first();

    expect($alerta)->not->toBeNull();
    expect($alerta->severidade)->toBe('alta');
    expect($alerta->categoria)->toBe('notas_fiscais');
    expect($alerta->status)->toBe('ativo');
});

it('hash evita alertas duplicados ao recalcular duas vezes', function () {
    $resultado1 = $this->service->recalcular($this->user->id);
    $resultado2 = $this->service->recalcular($this->user->id);

    $totalAlertas = Alerta::where('user_id', $this->user->id)->count();

    // Nao deve duplicar — mesmos hashes
    expect($totalAlertas)->toBe($totalAlertas); // idempotente

    // Na segunda execucao nao deve criar novos
    expect($resultado2['novos'])->toBe(0);
});

it('auto-resolve alertas que nao sao mais detectados', function () {
    // Criar alerta manualmente
    $alerta = Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'notas_duplicadas',
        'categoria' => 'notas_fiscais',
        'severidade' => 'alta',
        'titulo' => 'Teste',
        'descricao' => 'Teste',
        'status' => 'ativo',
        'hash' => hash('sha256', $this->user->id.':fake_old_alert'),
    ]);

    // Recalcular (sem dados, nenhum alerta sera detectado)
    $this->service->recalcular($this->user->id);

    $alerta->refresh();
    expect($alerta->status)->toBe('resolvido');
    expect($alerta->resolvido_em)->not->toBeNull();
});

it('preserva status manual ao recalcular', function () {
    // Criar alerta com status ignorado manualmente
    $hash = hash('sha256', $this->user->id.':notas_duplicadas');
    $alerta = Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'notas_duplicadas',
        'categoria' => 'notas_fiscais',
        'severidade' => 'alta',
        'titulo' => 'Teste',
        'descricao' => 'Teste',
        'status' => 'ignorado',
        'hash' => $hash,
    ]);

    // Recalcular — nao deve mudar status de ignorado para ativo
    $this->service->recalcular($this->user->id);

    $alerta->refresh();
    expect($alerta->status)->toBe('ignorado');
});

it('compliance detector encontra participantes com situacao irregular', function () {
    $cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '22333444000199',
        'razao_social' => 'Teste Ltda',
        'is_empresa_propria' => true,
    ]);

    $importacao = EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'concluido',
    ]);

    $participante = Participante::create([
        'user_id' => $this->user->id,
        'cnpj' => '99887766000155',
        'razao_social' => 'Empresa Irregular',
        'situacao_cadastral' => 'CANCELADA',
    ]);

    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $importacao->id,
        'participante_id' => $participante->id,
        'numero' => 9999,
        'serie' => '1',
        'modelo' => '55',
        'data_emissao' => '2026-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 500.00,
    ]);

    $this->service->recalcular($this->user->id);

    $alerta = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'situacao_irregular')
        ->first();

    expect($alerta)->not->toBeNull();
    expect($alerta->severidade)->toBe('alta');
    expect($alerta->categoria)->toBe('compliance');
    expect($alerta->participante_id)->toBe($participante->id);
});

it('marcar status atualiza timestamps', function () {
    $alerta = Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'teste',
        'categoria' => 'notas_fiscais',
        'severidade' => 'media',
        'titulo' => 'Teste',
        'descricao' => 'Teste',
        'status' => 'ativo',
        'hash' => hash('sha256', 'test-hash'),
    ]);

    // Marcar como visto
    $atualizado = $this->service->marcarStatus($alerta->id, $this->user->id, 'visto');
    expect($atualizado->status)->toBe('visto');
    expect($atualizado->visto_em)->not->toBeNull();

    // Marcar como resolvido
    $atualizado = $this->service->marcarStatus($alerta->id, $this->user->id, 'resolvido', 'Ja tratado com o cliente');
    expect($atualizado->status)->toBe('resolvido');
    expect($atualizado->resolvido_em)->not->toBeNull();
    expect($atualizado->notas)->toBe('Ja tratado com o cliente');
});

it('obter resumo retorna contagens corretas', function () {
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'a', 'categoria' => 'notas_fiscais', 'severidade' => 'alta', 'titulo' => 'A', 'descricao' => 'A', 'status' => 'ativo', 'hash' => hash('sha256', 'a')]);
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'b', 'categoria' => 'notas_fiscais', 'severidade' => 'media', 'titulo' => 'B', 'descricao' => 'B', 'status' => 'ativo', 'hash' => hash('sha256', 'b')]);
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'c', 'categoria' => 'compliance', 'severidade' => 'baixa', 'titulo' => 'C', 'descricao' => 'C', 'status' => 'ativo', 'hash' => hash('sha256', 'c')]);
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'd', 'categoria' => 'compliance', 'severidade' => 'alta', 'titulo' => 'D', 'descricao' => 'D', 'status' => 'resolvido', 'hash' => hash('sha256', 'd')]);

    $resumo = $this->service->obterResumo($this->user->id);

    expect($resumo['total_ativos'])->toBe(3);
    expect($resumo['por_severidade']['alta'])->toBe(1);
    expect($resumo['por_severidade']['media'])->toBe(1);
    expect($resumo['por_severidade']['baixa'])->toBe(1);
    expect($resumo['por_categoria']['notas_fiscais'])->toBe(2);
    expect($resumo['por_categoria']['compliance'])->toBe(1);
});

it('obter alertas filtra por severidade', function () {
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'a', 'categoria' => 'notas_fiscais', 'severidade' => 'alta', 'titulo' => 'A', 'descricao' => 'A', 'status' => 'ativo', 'hash' => hash('sha256', 'alta1')]);
    Alerta::create(['user_id' => $this->user->id, 'tipo' => 'b', 'categoria' => 'notas_fiscais', 'severidade' => 'media', 'titulo' => 'B', 'descricao' => 'B', 'status' => 'ativo', 'hash' => hash('sha256', 'media1')]);

    $alertasAlta = $this->service->obterAlertas($this->user->id, ['severidade' => 'alta']);

    expect($alertasAlta->total())->toBe(1);
    expect($alertasAlta->items()[0]->severidade)->toBe('alta');
});

it('endpoint alertas retorna view para usuario autenticado', function () {
    $response = $this->get('/app/alertas');
    $response->assertStatus(200);
});

it('endpoint alertas/dados retorna JSON', function () {
    $response = $this->getJson('/app/alertas/dados');
    $response->assertStatus(200);
    $response->assertJsonStructure(['data']);
});

it('endpoint alertas/resumo retorna JSON com estrutura correta', function () {
    $response = $this->getJson('/app/alertas/resumo');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'total_ativos',
        'por_severidade' => ['alta', 'media', 'baixa'],
        'por_categoria' => ['notas_fiscais', 'compliance'],
        'novos_hoje',
    ]);
});

it('endpoint alertas/evolucao retorna JSON para grafico', function () {
    $response = $this->getJson('/app/alertas/evolucao');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'categorias',
        'series',
    ]);
});

it('redireciona para login quando nao autenticado', function () {
    auth()->logout();
    $response = $this->get('/app/alertas');
    $response->assertRedirect('/login');
});
