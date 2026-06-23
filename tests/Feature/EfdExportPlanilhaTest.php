<?php

use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\EfdNotaItem;
use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function efdImportacaoComNota(User $user): EfdImportacao
{
    $imp = EfdImportacao::create([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD PIS/COFINS',
        'periodo_inicio' => '2024-06-01',
        'periodo_fim' => '2024-06-30',
        'arquivo_hash' => str_repeat('a', 64),
        'status' => 'concluido',
    ]);

    $cliente = Cliente::create([
        'user_id' => $user->id,
        'documento' => '97551165000193',
        'razao_social' => 'Cliente Teste',
    ]);

    $nota = EfdNota::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $imp->id,
        'chave_acesso' => str_repeat('1', 44),
        'modelo' => '55',
        'numero' => '123',
        'serie' => '1',
        'data_emissao' => '2024-06-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 1000.00,
        'origem_arquivo' => 'contribuicoes',
    ]);

    EfdNotaItem::create([
        'efd_nota_id' => $nota->id,
        'user_id' => $user->id,
        'numero_item' => 1,
        'codigo_item' => 'PROD1',
        'descricao' => 'Produto Teste',
        'valor_total' => 1000.00,
        'cfop' => '5102',
    ]);

    return $imp;
}

/** Lê o ZIP do corpo da resposta e devolve [nome => conteúdo]. */
function lerZipDaResposta(string $body): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'efdzip');
    file_put_contents($tmp, $body);

    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();

    $arquivos = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nome = $zip->statIndex($i)['name'];
        $arquivos[$nome] = $zip->getFromName($nome);
    }
    $zip->close();
    @unlink($tmp);

    return $arquivos;
}

it('baixa um zip com os csvs dos dados da importacao', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    $response = actingAs($user)->get("/app/importacao/efd/{$imp->id}/exportar");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');

    $arquivos = lerZipDaResposta($response->streamedContent());

    expect($arquivos)->toHaveKey('notas.csv');
    expect($arquivos)->toHaveKey('notas_itens.csv');
    expect($arquivos['notas.csv'])->toContain(str_repeat('1', 44));
    expect($arquivos['notas_itens.csv'])->toContain('PROD1');
});

it('pula datasets vazios no zip', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    $response = actingAs($user)->get("/app/importacao/efd/{$imp->id}/exportar");

    $arquivos = lerZipDaResposta($response->streamedContent());

    // Sem participantes / apuração / retenções / catálogo criados → arquivos ausentes.
    expect($arquivos)->not->toHaveKey('participantes.csv');
    expect($arquivos)->not->toHaveKey('apuracao_pis_cofins.csv');
    expect($arquivos)->not->toHaveKey('apuracao_icms.csv');
    expect($arquivos)->not->toHaveKey('retencoes_fonte.csv');
    expect($arquivos)->not->toHaveKey('catalogo_itens.csv');
});

it('mostra o botao exportar planilha no detalhe da importacao', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    actingAs($user)
        ->get("/app/importacao/efd/{$imp->id}")
        ->assertOk()
        ->assertSee('Exportar planilha')
        ->assertSee("/app/importacao/efd/{$imp->id}/exportar");
});

it('exporta um unico dataset como csv quando dataset e informado', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    $response = actingAs($user)->get("/app/importacao/efd/{$imp->id}/exportar?dataset=notas");

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    expect($csv)->toContain('chave_acesso');           // header de coluna
    expect($csv)->toContain(str_repeat('1', 44));        // linha da nota
});

it('exporta dataset vazio como csv so com cabecalho', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user); // sem participantes

    $response = actingAs($user)->get("/app/importacao/efd/{$imp->id}/exportar?dataset=participantes");

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('razao_social'); // header presente
});

it('rejeita dataset invalido', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    actingAs($user)
        ->get("/app/importacao/efd/{$imp->id}/exportar?dataset=zzz_invalido")
        ->assertNotFound();
});

it('mostra o modal com a opcao de csv unico no detalhe', function () {
    $user = User::factory()->create();
    $imp = efdImportacaoComNota($user);

    actingAs($user)
        ->get("/app/importacao/efd/{$imp->id}")
        ->assertOk()
        ->assertSee('Apuração PIS/COFINS')
        ->assertSee("/app/importacao/efd/{$imp->id}/exportar?dataset=notas");
});

it('nao deixa um usuario exportar importacao de outro', function () {
    $dono = User::factory()->create();
    $outro = User::factory()->create();
    $imp = efdImportacaoComNota($dono);

    actingAs($outro)
        ->get("/app/importacao/efd/{$imp->id}/exportar")
        ->assertNotFound();
});
