<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit/BI');

pest()->extend(Tests\TestCase::class)
    ->in('Unit/Efd');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Massa canônica de "fechamento mensal" (1 empresa, competência 2024-01) usada
 * pelos testes de cruzamento/a-recolher. Espelha o cenário F2:
 *  - ICMS: débito 100 / crédito 20 no C190 (itens fiscais são lixo, não devem ser lidos)
 *  - PIS/COFINS: débito-saída 30/90 nos itens 'contribuicoes'
 *  - Apuração: ICMS deb 100/cred 20/recolher 80 (+ obrigações E116 reais);
 *    PIS cum 30/recolher 28; COFINS cum 90/recolher 88 (recolher já líquido de retenção)
 *  - F600: retenções pis 2 + cofins 2 (= as deduzidas na apuração)
 *
 * @return array{0:int,1:int} [userId, clienteId]
 */
function montarMassaFechamento(): array
{
    $user = \App\Models\User::factory()->create();
    $clienteId = \Illuminate\Support\Facades\DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100', 'is_empresa_propria' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $impFiscal = \App\Models\EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $clienteId,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'icms.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $impContrib = \App\Models\EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $clienteId,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'pc.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);

    $mk = fn (array $a) => \App\Models\EfdNota::create(array_merge([
        'user_id' => $user->id, 'cliente_id' => $clienteId,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15',
        'valor_total' => 1000, 'valor_desconto' => 0, 'cancelada' => false,
    ], $a));

    $fSaida = $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal']);
    $fEntrada = $mk(['importacao_id' => $impFiscal->id, 'chave_acesso' => str_pad('C', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'entrada', 'origem_arquivo' => 'fiscal']);
    $cSaida = $mk(['importacao_id' => $impContrib->id, 'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes']);

    $cons = fn (\App\Models\EfdNota $n, float $icms, int $cfop) => \Illuminate\Support\Facades\DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $user->id, 'cfop' => $cfop, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => $icms, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cons($fSaida, 100, 5102);   // débito declarado = 100
    $cons($fEntrada, 20, 1102);  // crédito declarado = 20

    $item = fn (\App\Models\EfdNota $n, array $v) => \Illuminate\Support\Facades\DB::table('efd_notas_itens')->insert(array_merge([
        'efd_nota_id' => $n->id, 'user_id' => $user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'quantidade' => 1, 'valor_total' => 1000, 'cfop' => 5102, 'valor_icms' => 0, 'valor_pis' => 0,
        'valor_cofins' => 0, 'created_at' => now(), 'updated_at' => now(),
    ], $v));
    $item($fSaida, ['valor_icms' => 0.01, 'valor_pis' => 999, 'valor_cofins' => 999]); // lixo: não pode ser lido
    $item($cSaida, ['valor_pis' => 30, 'valor_cofins' => 90]);                          // débito-saída real

    \Illuminate\Support\Facades\DB::table('efd_apuracoes_icms')->insert([
        'importacao_id' => $impFiscal->id, 'user_id' => $user->id, 'cliente_id' => $clienteId,
        'periodo_inicio' => '2024-01-01', 'periodo_fim' => '2024-01-31',
        'icms_tot_debitos' => 100, 'icms_tot_creditos' => 20, 'icms_a_recolher' => 80, 'st_icms_recolher' => 0,
        // E116 real: guia 310 (ICMS apuração) com vencimento DDMMYYYY
        'icms_obrigacoes' => json_encode(['items' => [[
            'ICMS_COD_RECEITA' => '310', 'ICMS_VALOR_OBRIGACAO' => 80, 'ICMS_DATA_VENCIMENTO' => '16022024',
        ]]]),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('efd_apuracoes_contribuicoes')->insert([
        'importacao_id' => $impContrib->id, 'user_id' => $user->id, 'cliente_id' => $clienteId,
        'pis_nao_cumulativo' => 0, 'pis_cumulativo' => 30, 'pis_total_recolher' => 28,
        'pis_retencao_cum' => 2, 'cofins_retencao_cum' => 2,
        'cofins_nao_cumulativo' => 0, 'cofins_cumulativo' => 90, 'cofins_total_recolher' => 88,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('efd_retencoes_fonte')->insert([
        'importacao_id' => $impContrib->id, 'user_id' => $user->id, 'cliente_id' => $clienteId,
        'natureza' => '01', 'natureza_receita' => '01', 'data_retencao' => '2024-01-15',
        'base_calculo' => 100, 'cod_receita' => '5952',
        'valor_total' => 4, 'valor_pis' => 2, 'valor_cofins' => 2, 'cnpj' => '00000000000191',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return [$user->id, $clienteId];
}
