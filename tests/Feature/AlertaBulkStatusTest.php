<?php

use App\Models\Alerta;
use App\Models\User;
use App\Services\AlertaCentralService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->outro = User::factory()->create();
    $this->service = app(AlertaCentralService::class);
});

afterEach(function () {
    Alerta::whereIn('user_id', [$this->user->id, $this->outro->id])->delete();
    $this->user->forceDelete();
    $this->outro->forceDelete();
});

function novoAlertaBulk(int $userId, string $status = 'ativo'): Alerta
{
    return Alerta::create([
        'user_id' => $userId,
        'tipo' => 'gap_importacao',
        'categoria' => 'importacao',
        'severidade' => 'media',
        'titulo' => 't',
        'descricao' => 'd',
        'status' => $status,
        'hash' => hash('sha256', uniqid('', true)),
    ]);
}

it('marca status em lote só nos alertas do próprio usuário e grava o motivo', function () {
    $a1 = novoAlertaBulk($this->user->id);
    $a2 = novoAlertaBulk($this->user->id);
    $alheio = novoAlertaBulk($this->outro->id);

    $total = $this->service->marcarStatusEmLote(
        [$a1->id, $a2->id, $alheio->id],
        $this->user->id,
        'ignorado',
        'Falso positivo'
    );

    expect($total)->toBe(2);
    expect($a1->fresh()->status)->toBe('ignorado');
    expect($a1->fresh()->notas)->toBe('Falso positivo');
    expect($a2->fresh()->status)->toBe('ignorado');
    expect($alheio->fresh()->status)->toBe('ativo'); // intocado (outro usuário)
});

it('endpoint status-lote resolve em lote e valida o status', function () {
    $a1 = novoAlertaBulk($this->user->id);

    $this->actingAs($this->user)
        ->postJson('/app/alertas/status-lote', ['ids' => [$a1->id], 'status' => 'resolvido'])
        ->assertOk()
        ->assertJson(['success' => true, 'total' => 1]);

    expect($a1->fresh()->status)->toBe('resolvido');

    $this->actingAs($this->user)
        ->postJson('/app/alertas/status-lote', ['ids' => [$a1->id], 'status' => 'invalido'])
        ->assertStatus(422);
});
