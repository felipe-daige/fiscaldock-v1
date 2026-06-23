<?php
// tests/Feature/Admin/AdminAuditoriaViewTest.php
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lista a trilha global para admin e barra não-admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $alvo = User::factory()->create(['name' => 'Fulano Alvo']);
    AdminActionLog::create([
        'admin_user_id' => $admin->id, 'target_user_id' => $alvo->id,
        'acao' => 'creditar', 'motivo' => 'teste trilha', 'created_at' => now(),
    ]);

    actingAs($admin)->get('/app/admin/auditoria')
        ->assertOk()->assertSee('teste trilha')->assertSee('Fulano Alvo');

    actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/app/admin/auditoria')->assertForbidden();
});
