<?php

use App\Models\User;
use App\Services\PricingCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('redireciona visitante não autenticado', function () {
    $this->get('/app/admin/comercial')->assertRedirect();
});

it('nega acesso a usuário não-admin (403)', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)->get('/app/admin/comercial')->assertForbidden();
});

it('permite acesso ao admin e lista os parâmetros', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get('/app/admin/comercial')
        ->assertOk()
        ->assertSee('Parâmetros comerciais')
        ->assertSee('Preço por crédito');
});

it('admin grava override e o PricingCatalogService passa a lê-lo', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->post('/app/admin/comercial/credit_unit_price', ['valor' => '0.25'])
        ->assertRedirect();

    $this->assertDatabaseHas('comercial_parametros', ['chave' => 'credit_unit_price', 'valor' => '0.25']);
    expect((new PricingCatalogService)->creditUnitPrice())->toBe(0.25);
});

it('admin reseta o override e volta ao padrão', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    (new \App\Services\Admin\ComercialParametroService)->definir('minimum_deposit', 90.00, $admin->id);

    actingAs($admin)
        ->post('/app/admin/comercial/minimum_deposit/reset')
        ->assertRedirect();

    $this->assertDatabaseMissing('comercial_parametros', ['chave' => 'minimum_deposit']);
    expect((new PricingCatalogService)->getMinimumDeposit())->toBe(100.00);
});

it('404 para chave fora do registro', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->post('/app/admin/comercial/chave_inexistente', ['valor' => '1'])
        ->assertNotFound();
});

it('valida que o valor é numérico', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->post('/app/admin/comercial/credit_unit_price', ['valor' => 'abc'])
        ->assertSessionHasErrors('valor');
});
