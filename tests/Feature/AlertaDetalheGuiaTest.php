<?php

use App\Models\Alerta;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    Alerta::where('user_id', $this->user->id)->delete();
    $this->user->forceDelete();
});

it('detalhe do alerta agregado nunca_consultado mostra CTA pra participantes', function () {
    $alerta = Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'nunca_consultado',
        'categoria' => 'compliance',
        'severidade' => 'baixa',
        'titulo' => '42 participante(s) nunca consultado(s)',
        'descricao' => 'Participantes sem verificação cadastral.',
        'status' => 'ativo',
        'participante_id' => null,
        'total_afetados' => 42,
        'detalhes' => [['razao_social' => 'Fornecedor X', 'documento' => '12345678000190']],
        'hash' => hash('sha256', 'nc'.uniqid('', true)),
    ]);

    $this->actingAs($this->user)
        ->get('/app/alertas/'.$alerta->id)
        ->assertOk()
        ->assertSee('Ir para Consulta')
        ->assertSee('/app/participantes');
});

it('a listagem anexa o cta do guia em cada alerta', function () {
    Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'gap_importacao',
        'categoria' => 'importacao',
        'severidade' => 'media',
        'titulo' => 't',
        'descricao' => 'd',
        'status' => 'ativo',
        'hash' => hash('sha256', 'lst'.uniqid('', true)),
    ]);

    $alertas = app(App\Services\AlertaCentralService::class)
        ->obterAlertas($this->user->id, ['status' => 'ativo']);

    $item = collect($alertas->items())->first();

    expect($item->guia['cta_url'])->toBe('/app/importacao/efd');
    expect($item->guia['cta_text'])->toBe('Ir para Importações SPED');
});

it('detalhe exibe o motivo quando o alerta foi ignorado com nota', function () {
    $alerta = Alerta::create([
        'user_id' => $this->user->id,
        'tipo' => 'gap_importacao',
        'categoria' => 'importacao',
        'severidade' => 'media',
        'titulo' => '2 meses sem importação',
        'descricao' => 'desc',
        'status' => 'ignorado',
        'notas' => 'Cliente entrou no Simples, sem EFD nesses meses',
        'hash' => hash('sha256', 'gap'.uniqid('', true)),
    ]);

    $this->actingAs($this->user)
        ->get('/app/alertas/'.$alerta->id)
        ->assertOk()
        ->assertSee('Motivo:')
        ->assertSee('Cliente entrou no Simples, sem EFD nesses meses');
});
