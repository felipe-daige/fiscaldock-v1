<?php

use App\Models\User;
use App\Models\XmlNota;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function detalheClearanceUsuario(): User
{
    return User::factory()->create();
}

function detalheClearanceNotaBase(User $user, array $overrides = []): XmlNota
{
    return XmlNota::create(array_merge([
        'user_id' => $user->id,
        'nfe_id' => '35202404123456789012555000001234567890123456',
        'origem' => 'xml_upload',
        'tipo_documento' => 'NFE',
        'numero_nota' => 1234,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 1000.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'emit_razao_social' => 'ACME',
        'dest_cnpj' => '98765432000110',
        'dest_razao_social' => 'XYZ',
        'validacao' => [
            'score_total' => 12,
            'classificacao' => 'atencao',
            'scores' => [
                'cadastral' => 0,
                'tributacao' => 12,
                'cfop_cst' => 0,
                'integridade' => 0,
                'ncm' => 0,
                'operacoes' => 0,
            ],
            'alertas' => [],
        ],
        'payload' => [],
    ], $overrides));
}

it('renderiza painel Declarado x SEFAZ no topo do detalhe da nota', function () {
    $user = detalheClearanceUsuario();
    $nota = detalheClearanceNotaBase($user, [
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => '2026-04-12 14:32:00',
        'payload' => [
            'det' => [['nItem' => '1', 'prod' => ['cProd' => 'A', 'vProd' => '1000']]],
            'ide' => ['mod' => '55', 'natOp' => 'Venda'],
            'total' => ['ICMSTot' => ['vBC' => '1000']],
            'nfe_clearance' => [
                'status' => 'AUTORIZADA',
                'numero' => '1234',
                'modelo' => '55',
                'valor_total' => 1000,
                'totais' => ['base_calculo_icms' => 1000],
            ],
        ],
    ]);

    actingAs($user)
        ->get("/app/clearance/nota/{$nota->id}")
        ->assertOk()
        ->assertSee('Declarado × SEFAZ')
        ->assertSee('Snapshot SEFAZ persistido')
        ->assertSee('Abrir comparação completa')
        ->assertSee('Validação Local')
        ->assertSee('Score de Validação');
});

it('renderiza fallback claro quando a nota ainda nao possui snapshot SEFAZ', function () {
    $user = detalheClearanceUsuario();
    $nota = detalheClearanceNotaBase($user, [
        'nfe_id' => '35202404123456789012555000001234567890123457',
    ]);

    actingAs($user)
        ->get("/app/clearance/nota/{$nota->id}")
        ->assertOk()
        ->assertSee('Declarado × SEFAZ')
        ->assertSee('Sem snapshot SEFAZ persistido')
        ->assertSee('Incluir em lote de clearance')
        ->assertSee('Não verificada');
});

it('renderiza resumo hibrido para CT-e sem assumir semantica de NF-e', function () {
    $user = detalheClearanceUsuario();
    $nota = detalheClearanceNotaBase($user, [
        'nfe_id' => '35202404123456789012575000001234567890123456',
        'tipo_documento' => 'CTE',
        'numero_nota' => 5678,
        'valor_total' => 500.00,
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => '2026-04-12 14:32:00',
        'payload' => [
            'cte_clearance' => [
                'status' => 'AUTORIZADA',
                'numero' => '5678',
                'modelo' => '57',
                'valor_prestacao' => 500.00,
                'componentes' => [
                    ['nome' => 'FRETE PESO', 'valor' => '400'],
                    ['nome' => 'PEDAGIO', 'valor' => '100'],
                ],
                'emitente' => ['cnpj' => '11111111000111', 'nome' => 'TRANSP'],
                'tomador' => ['cnpj' => '22222222000122', 'nome' => 'TOMADOR'],
            ],
        ],
    ]);

    actingAs($user)
        ->get("/app/clearance/nota/{$nota->id}")
        ->assertOk()
        ->assertSee('CT-e confrontada com snapshot persistido')
        ->assertSee('Componentes')
        ->assertSee('Abrir comparação completa');
});
