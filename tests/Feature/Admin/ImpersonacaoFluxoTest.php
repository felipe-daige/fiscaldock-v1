<?php
// tests/Feature/Admin/ImpersonacaoFluxoTest.php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('impersonar usuário comum seta sessão e redireciona pro /app', function () {
    $alvo = User::factory()->create(['is_admin' => false]);

    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$alvo->id}/impersonar", ['motivo' => 'repro bug'])
        ->assertRedirect('/app');

    expect(auth()->id())->toBe($alvo->id);
    expect(session('impersonator_id'))->toBe($this->admin->id);
});

it('não impersona a si mesmo nem outro admin', function () {
    $outroAdmin = User::factory()->create(['is_admin' => true]);

    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$this->admin->id}/impersonar", ['motivo' => 'tentativa self'])
        ->assertSessionHasErrors('motivo');
    expect(session('impersonator_id'))->toBeNull();
    expect(auth()->id())->toBe($this->admin->id);

    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$outroAdmin->id}/impersonar", ['motivo' => 'tentativa admin'])
        ->assertSessionHasErrors('motivo');
    expect(session('impersonator_id'))->toBeNull();
});

it('sair restaura o admin', function () {
    $alvo = User::factory()->create(['is_admin' => false]);

    actingAs($alvo)->withSession(['impersonator_id' => $this->admin->id])
        ->post('/app/admin/impersonar/sair')
        ->assertRedirect("/app/admin/usuarios/{$alvo->id}");

    expect(auth()->id())->toBe($this->admin->id);
    expect(session('impersonator_id'))->toBeNull();
});

it('não impersona usuário bloqueado', function () {
    $bloqueado = User::factory()->create(['is_admin' => false, 'bloqueado_em' => now()]);

    actingAs($this->admin)
        ->post("/app/admin/usuarios/{$bloqueado->id}/impersonar", ['motivo' => 'inspecionar'])
        ->assertSessionHasErrors('motivo');
    expect(session('impersonator_id'))->toBeNull();
});
