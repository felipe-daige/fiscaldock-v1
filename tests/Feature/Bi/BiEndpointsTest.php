<?php

use App\Http\Middleware\EnsureEmpresaPropriaExists;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Bypass do middleware que cria empresa propria — evita writes em tabela clientes
    // com dados nulos de factories; os endpoints testados nao dependem desse middleware
    $this->withoutMiddleware(EnsureEmpresaPropriaExists::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('faturamento endpoint returns json structure', function () {
    $response = $this->getJson('/app/bi/faturamento');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'faturamento_mensal',
            'top_clientes',
            'faturamento_por_uf',
        ]);
});

test('efd endpoint returns json structure', function () {
    $response = $this->getJson('/app/bi/efd');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'kpis' => [
                'total_entradas_valor',
                'total_entradas_notas',
                'total_saidas_valor',
                'total_saidas_notas',
                'saldo_liquido',
                'participantes_ativos',
                'notas_em_risco',
            ],
            'fluxo_mensal',
            'volume_blocos',
            'top_fornecedores',
            'top_clientes',
            'tributos_por_tipo',
        ]);
});

test('participantes endpoint returns json structure', function () {
    $response = $this->getJson('/app/bi/participantes');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'fornecedores',
            'clientes',
            'concentracao' => [
                'fornecedores',
                'clientes',
            ],
        ]);
});

test('riscos endpoint returns json structure', function () {
    $response = $this->getJson('/app/bi/riscos');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'score_carteira',
            'fornecedores_irregulares',
            'notas_em_risco',
            'mudancas_regime',
            'gap_importacoes',
        ]);
});

test('tributario efd endpoint returns json structure', function () {
    $response = $this->getJson('/app/bi/tributario-efd');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'consolidado',
            'mensal',
            'aliquota',
            'por_regime',
        ]);
});

test('bi page renders for authenticated user', function () {
    $response = $this->get('/app/bi/dashboard');

    $response->assertStatus(200);
    $response->assertSee('BI Fiscal');
    $response->assertSee('bi.js');
    $response->assertSee('apexcharts.min.js', false);
});

test('bi page does not have version querystring on bi js', function () {
    $response = $this->get('/app/bi/dashboard');

    $response->assertStatus(200);
    // @todo Bug 2 fix (Task 2): this test is a regression pin intentionally failing until
    // the ?v= version querystring is removed from the bi.js <script> tag in the view.
    // Bug 2: script tag deve ser /js/bi.js sem ?v= para evitar double-load no SPA
    $response->assertSee('src="/js/bi.js"', false);
    $response->assertDontSee('bi.js?v=', false);
});
