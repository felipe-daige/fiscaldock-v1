<?php
// tests/Feature/Admin/AdminUsuarioAcaoControllerTest.php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->alvo = User::factory()->create(['credits' => 10, 'is_admin' => false]);
});

it('não-admin recebe 403', function () {
    $naoAdmin = User::factory()->create(['is_admin' => false]);
    actingAs($naoAdmin)
        ->post("/app/admin/usuarios/{$this->alvo->id}/creditar", ['valor' => 5, 'motivo' => 'fraude'])
        ->assertForbidden();
});

it('admin credita e gera flash + audit', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->alvo->id}/creditar", ['valor' => 40, 'motivo' => 'cortesia'])
        ->assertRedirect("/app/admin/usuarios/{$this->alvo->id}")
        ->assertSessionHas('status');

    expect($this->alvo->fresh()->credits)->toBe(50);
});

it('motivo vazio é rejeitado', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->alvo->id}/creditar", ['valor' => 40, 'motivo' => ''])
        ->assertSessionHasErrors('motivo');
});

it('admin bloqueia e promove', function () {
    actingAs($this->admin)->post("/app/admin/usuarios/{$this->alvo->id}/bloquear", ['motivo' => 'fraude']);
    expect($this->alvo->fresh()->bloqueado_em)->not->toBeNull();

    actingAs($this->admin)->post("/app/admin/usuarios/{$this->alvo->id}/admin", ['motivo' => 'operador']);
    expect($this->alvo->fresh()->is_admin)->toBeTrue();
});

it('creditar débito acima do saldo vira erro em valor, sem 500', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->alvo->id}/creditar", ['valor' => -9999, 'motivo' => 'estorno grande'])
        ->assertSessionHasErrors('valor');
    expect($this->alvo->fresh()->credits)->toBe(10);
});

it('creditar valor zero é rejeitado pela validação', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->alvo->id}/creditar", ['valor' => 0, 'motivo' => 'teste'])
        ->assertSessionHasErrors('valor');
});

it('bloquear a si mesmo vira erro (guard do service), sem 500', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->admin->id}/bloquear", ['motivo' => 'tentativa'])
        ->assertSessionHasErrors('motivo');
    expect($this->admin->fresh()->bloqueado_em)->toBeNull();
});

it('rebaixar a si mesmo vira erro, sem 500', function () {
    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->admin->id}/admin", ['motivo' => 'tentativa'])
        ->assertSessionHasErrors('motivo');
    expect($this->admin->fresh()->is_admin)->toBeTrue();
});
