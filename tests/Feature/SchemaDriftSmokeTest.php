<?php

/**
 * Smoke test contra o drift de schema do "Plano B" (xml_notas renames):
 * nfe_id -> chave_acesso, emit_cnpj -> emit_documento, dest_cnpj -> dest_documento.
 *
 * Garante 200 nos endpoints autenticados que regrediam pra 500 com
 * "column nfe_id does not exist" e similares.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    DB::table('xml_notas')->insert([
        'user_id' => $this->user->id,
        'chave_acesso' => str_repeat('1', 44),
        'tipo_documento' => 'nfe',
        'numero_documento' => 1,
        'serie' => '1',
        'data_emissao' => '2024-03-15',
        'valor_total' => 100.00,
        'tipo_nota' => 1,
        'finalidade' => 1,
        'emit_documento' => '12345678000199',
        'dest_documento' => '98765432000188',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('GET /app/clearance/dashboard responde 200', function () {
    actingAs($this->user)
        ->get('/app/clearance/dashboard')
        ->assertOk();
});

it('GET /app/clearance/notas responde 200', function () {
    actingAs($this->user)
        ->get('/app/clearance/notas')
        ->assertOk();
});

it('GET /app/clearance/buscar responde 200', function () {
    actingAs($this->user)
        ->get('/app/clearance/buscar')
        ->assertOk();
});

it('GET /app/bi/dashboard responde 200', function () {
    actingAs($this->user)
        ->get('/app/bi/dashboard')
        ->assertOk();
});

it('GET /app/notas/dashboard/visao-geral responde 200', function () {
    actingAs($this->user)
        ->getJson('/app/notas/dashboard/visao-geral?periodo_inicio=2024-02&periodo_fim=2024-05')
        ->assertOk();
});
