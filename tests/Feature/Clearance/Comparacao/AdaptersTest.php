<?php

use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\Adapters\EfdNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazCteAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazNfeAdapter;
use App\Services\Clearance\Comparacao\NotaNormalizada;
use Illuminate\Support\Facades\DB;

$testUserIds = [];

beforeEach(function () use (&$testUserIds) {
    $testUserIds = [];

    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => env('DB_HOST', 'postgres'),
        'database.connections.pgsql.port' => env('DB_PORT', 5432),
        'database.connections.pgsql.database' => 'fiscaldock_test',
        'database.connections.pgsql.username' => env('DB_USERNAME', 'postgres'),
        'database.connections.pgsql.password' => env('DB_PASSWORD', 'fdpCjI5U7KvpBdWjVLzzAEs2q5NOeGRu'),
        'database.connections.pgsql.schema' => 'public',
    ]);

    DB::purge('pgsql');
    DB::reconnect('pgsql');
});

afterEach(function () use (&$testUserIds) {
    if (! empty($testUserIds)) {
        User::whereIn('id', $testUserIds)->delete();
    }
});

function adapterCriarUser(array &$ids): User
{
    $user = User::factory()->create();
    $ids[] = $user->id;

    return $user;
}

it('XmlNotaDeclaradoAdapter mapeia campos canônicos', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '35202404123456789012555678901234567890123456';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
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
        'payload' => [
            'det' => [
                ['nItem' => '1', 'prod' => ['cProd' => 'A', 'xProd' => 'Produto A', 'NCM' => '12345678', 'CFOP' => '5102', 'qCom' => '10', 'uCom' => 'UN', 'vUnCom' => '100', 'vProd' => '1000']],
            ],
            'ide' => ['natOp' => 'Venda', 'mod' => '55'],
            'emit' => ['IE' => '111', 'enderEmit' => ['UF' => 'SP']],
            'dest' => ['IE' => '222', 'enderDest' => ['UF' => 'RJ']],
            'total' => ['ICMSTot' => ['vBC' => '1000', 'vICMS' => '180', 'vIPI' => '0', 'vPIS' => '6.5', 'vCOFINS' => '30']],
        ],
    ]);

    $adapter = new XmlNotaDeclaradoAdapter($nota);
    $normalizada = $adapter->carregar();

    expect($normalizada)->toBeInstanceOf(NotaNormalizada::class);
    expect($normalizada->chave)->toBe($chave);
    expect($normalizada->tipoDocumento)->toBe('NFE');
    expect($normalizada->header['numero'])->toBe('1234');
    expect($normalizada->header['modelo'])->toBe('55');
    expect($normalizada->partes['emit']['cnpj'])->toBe('12345678000190');
    expect($normalizada->partes['emit']['uf'])->toBe('SP');
    expect($normalizada->partes['dest']['uf'])->toBe('RJ');
    expect($normalizada->totais['valor_total'])->toBe(1000.00);
    expect($normalizada->totais['valor_icms'])->toBe(180.00);
    expect($normalizada->itens)->toHaveCount(1);
    expect($normalizada->itens[0]->cProd)->toBe('A');
    expect($normalizada->itens[0]->ncm)->toBe('12345678');
    expect($normalizada->itens[0]->cfop)->toBe('5102');
    expect($normalizada->itens[0]->qCom)->toBe(10.0);
    expect($normalizada->itens[0]->vProd)->toBe(1000.0);
    expect($adapter->origemLabel())->toContain('XML');
});

it('EfdNotaDeclaradoAdapter mapeia efd_notas e efd_notas_itens (saída → emit=cliente, dest=participante)', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '35202404123456789012555678901234567890123456';

    $cliente = Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '12345678000190',
        'razao_social' => 'ACME LTDA',
        'uf' => 'SP',
        'inscricao_estadual' => '111',
    ]);

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '98765432000110',
        'tipo_documento' => 'CNPJ',
        'razao_social' => 'XYZ COMERCIO',
        'uf' => 'RJ',
        'inscricao_estadual' => '222',
    ]);

    $importacaoId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'test.txt',
        'status' => 'concluido',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $efdNota = EfdNota::create([
        'user_id' => $user->id,
        'importacao_id' => $importacaoId,
        'cliente_id' => $cliente->id,
        'participante_id' => $participante->id,
        'chave_acesso' => $chave,
        'modelo' => '55',
        'numero' => 1234,
        'serie' => '1',
        'data_emissao' => '2026-04-12',
        'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal',
        'valor_total' => 1000.00,
    ]);

    DB::table('efd_catalogo_itens')->insert([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'importacao_id' => $importacaoId,
        'cod_item' => 'A',
        'descr_item' => 'Produto A',
        'tipo_item' => '00',
        'cod_ncm' => '12345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('efd_notas_itens')->insert([
        'user_id' => $user->id,
        'efd_nota_id' => $efdNota->id,
        'numero_item' => 1,
        'codigo_item' => 'A',
        'descricao' => 'Produto A',
        'cfop' => 5102,
        'quantidade' => 10,
        'valor_unitario' => 100,
        'valor_total' => 1000,
        'unidade_medida' => 'UN',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $efdNota->refresh();
    $adapter = new EfdNotaDeclaradoAdapter($efdNota);
    $normalizada = $adapter->carregar();

    expect($normalizada)->toBeInstanceOf(NotaNormalizada::class);
    expect($normalizada->chave)->toBe($chave);
    expect($normalizada->tipoDocumento)->toBe('NFE');
    expect($normalizada->header['numero'])->toBe('1234');
    expect($normalizada->header['modelo'])->toBe('55');
    expect($normalizada->partes['emit']['cnpj'])->toBe('12345678000190');
    expect($normalizada->partes['emit']['razao_social'])->toBe('ACME LTDA');
    expect($normalizada->partes['emit']['uf'])->toBe('SP');
    expect($normalizada->partes['dest']['cnpj'])->toBe('98765432000110');
    expect($normalizada->partes['dest']['uf'])->toBe('RJ');
    expect($normalizada->totais['valor_total'])->toBe(1000.00);
    expect($normalizada->itens)->toHaveCount(1);
    expect($normalizada->itens[0]->cProd)->toBe('A');
    expect($normalizada->itens[0]->ncm)->toBe('12345678');
    expect($normalizada->itens[0]->cfop)->toBe('5102');
    expect($normalizada->itens[0]->uCom)->toBe('UN');
    expect($adapter->origemLabel())->toContain('EFD');
});

it('EfdNotaDeclaradoAdapter inverte emit/dest para entrada (emit=participante, dest=cliente)', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '35202404123456789012555678901234567890123457';

    $cliente = Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '11111111000111',
        'razao_social' => 'COMPRADOR LTDA',
        'uf' => 'MG',
    ]);

    $participante = Participante::create([
        'user_id' => $user->id,
        'documento' => '22222222000122',
        'tipo_documento' => 'CNPJ',
        'razao_social' => 'FORNECEDOR LTDA',
        'uf' => 'PR',
    ]);

    $importacaoId = DB::table('efd_importacoes')->insertGetId([
        'user_id' => $user->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'test.txt',
        'status' => 'concluido',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $efdNota = EfdNota::create([
        'user_id' => $user->id,
        'importacao_id' => $importacaoId,
        'cliente_id' => $cliente->id,
        'participante_id' => $participante->id,
        'chave_acesso' => $chave,
        'modelo' => '55',
        'numero' => 5678,
        'serie' => '1',
        'data_emissao' => '2026-04-13',
        'tipo_operacao' => 'entrada',
        'origem_arquivo' => 'fiscal',
        'valor_total' => 500.00,
    ]);

    $efdNota->refresh();
    $adapter = new EfdNotaDeclaradoAdapter($efdNota);
    $normalizada = $adapter->carregar();

    expect($normalizada->partes['emit']['cnpj'])->toBe('22222222000122');
    expect($normalizada->partes['emit']['uf'])->toBe('PR');
    expect($normalizada->partes['dest']['cnpj'])->toBe('11111111000111');
    expect($normalizada->partes['dest']['uf'])->toBe('MG');
});

it('XmlNotaSefazNfeAdapter mapeia payload nfe_clearance + colunas snapshot', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '35202404123456789012555678901234567890123456';

    $loteId = DB::table('consulta_lotes')->insertGetId([
        'user_id' => $user->id,
        'status' => 'concluido',
        'total_participantes' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'busca_avulsa',
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
        'payload' => [
            'nfe_clearance' => [
                'serie' => '1',
                'modelo' => '55',
                'numero' => '1234',
                'status' => 'AUTORIZADA',
                'valor_total' => 1000.00,
                'natureza_operacao' => 'Venda',
                'totais' => [
                    'normalizado_valor_nfe' => 1000.0,
                    'normalizado_valor_icms' => 180.0,
                    'normalizado_valor_pis' => 6.5,
                    'normalizado_valor_cofins' => 30.0,
                ],
                'emitente' => ['cnpj' => '12345678000190', 'nome' => 'ACME', 'ie' => '111', 'uf' => 'SP'],
                'destinatario' => ['cnpj' => '98765432000110', 'nome' => 'XYZ', 'ie' => '222', 'uf' => 'RJ'],
                'eventos' => [
                    ['evento' => 'Autorização de Uso', 'protocolo' => '135240412345', 'data_autorizacao' => '12/04/2026 às 14:32:00'],
                ],
                'produtos' => [
                    ['num' => 1, 'descricao' => 'Produto A', 'quantidade' => '10,0000', 'valor_produto' => '1000,000', 'valor_unidade' => '100,000', 'unidade_comercial' => 'UN'],
                ],
            ],
        ],
    ]);
    DB::table('xml_notas')->where('id', $nota->id)->update([
        'consulta_lote_id' => $loteId,
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => now(),
    ]);
    $nota->refresh();

    $adapter = new XmlNotaSefazNfeAdapter($nota);
    $normalizada = $adapter->carregar();

    expect($normalizada)->toBeInstanceOf(NotaNormalizada::class);
    expect($normalizada->chave)->toBe($chave);
    expect($normalizada->tipoDocumento)->toBe('NFE');
    expect($normalizada->header['numero'])->toBe('1234');
    expect($normalizada->header['modelo'])->toBe('55');
    expect($normalizada->metaSefaz['situacao'])->toBe('AUTORIZADA');
    expect($normalizada->metaSefaz['protocolo'])->toBe('135240412345');
    expect($normalizada->partes['emit']['cnpj'])->toBe('12345678000190');
    expect($normalizada->partes['dest']['uf'])->toBe('RJ');
    expect($normalizada->totais['valor_total'])->toBe(1000.0);
    expect($normalizada->totais['valor_icms'])->toBe(180.0);
    expect($normalizada->itens)->toHaveCount(1);
    expect($normalizada->itens[0]->nItem)->toBe(1);
    expect($normalizada->itens[0]->xProd)->toBe('Produto A');
    expect($normalizada->itens[0]->qCom)->toBe(10.0);
    expect($normalizada->itens[0]->vProd)->toBe(1000.0);
    expect($normalizada->itens[0]->cProd)->toBeNull();
    expect($normalizada->itens[0]->ncm)->toBeNull();
    expect($adapter->origemLabel())->toContain('SEFAZ');
});

it('XmlNotaSefazNfeAdapter funciona sem snapshot (situacao_sefaz null)', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '35202404123456789012555678901234567890123499';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'busca_avulsa',
        'tipo_documento' => 'NFE',
        'numero_nota' => 99,
        'serie' => 1,
        'data_emissao' => '2026-04-15 09:00:00',
        'valor_total' => 50.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '12345678000190',
        'dest_cnpj' => '98765432000110',
        'payload' => [
            'nfe_clearance' => ['status' => 'CANCELADA', 'numero' => '99'],
        ],
    ]);

    $adapter = new XmlNotaSefazNfeAdapter($nota);
    $normalizada = $adapter->carregar();

    expect($normalizada->metaSefaz['situacao'])->toBe('CANCELADA');
    expect($normalizada->itens)->toBe([]);
});

it('XmlNotaSefazCteAdapter mapeia payload cte_clearance + componentes', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '50202412345678901234560570010001234567890123';

    $loteId = DB::table('consulta_lotes')->insertGetId([
        'user_id' => $user->id,
        'status' => 'concluido',
        'total_participantes' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'busca_avulsa',
        'tipo_documento' => 'CTE',
        'numero_nota' => 4321,
        'serie' => 1,
        'data_emissao' => '2026-04-12 10:00:00',
        'valor_total' => 845.30,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '08923352000120',
        'emit_razao_social' => 'TRANSP LTDA',
        'dest_cnpj' => '00000165000193',
        'dest_razao_social' => 'TOMADOR LTDA',
        'payload' => [
            'cte_clearance' => [
                'serie' => '1',
                'modelo' => '57',
                'numero' => '4321',
                'status' => 'AUTORIZADA',
                'cfop' => '5353',
                'modal' => 'rodoviario',
                'tipo_servico' => 'Normal',
                'natureza_operacao' => 'PRESTACAO DE SERVICO DE TRANSPORTE',
                'valor_prestacao' => 845.30,
                'valor_carga' => 5000.00,
                'totais' => [
                    'impostos' => [
                        'normalizado_valor_icms' => 101.44,
                        'normalizado_base_calculo_icms' => 845.30,
                    ],
                ],
                'componentes' => [
                    ['nome' => 'FRETE PESO', 'valor' => '700,00'],
                    ['nome' => 'PEDAGIO', 'valor' => '145,30'],
                ],
                'emitente' => ['cnpj' => '08923352000120', 'nome' => 'TRANSP LTDA', 'ie' => '111', 'uf' => 'MS'],
                'destinatario' => ['cnpj' => '00000165000193', 'nome' => 'DEST LTDA', 'ie' => '222', 'uf' => 'MS'],
                'tomador' => ['cnpj' => '00000165000193', 'nome' => 'TOMADOR LTDA', 'ie' => '333', 'uf' => 'MS'],
                'remetente' => ['cnpj' => '11111111000111', 'nome' => 'REMETENTE LTDA', 'uf' => 'SP'],
                'eventos' => [
                    ['evento' => 'Autorização de Uso', 'protocolo' => '150240007166359', 'data_autorizacao' => '29/02/2024 às 20:58:54-04:00'],
                ],
            ],
        ],
    ]);
    DB::table('xml_notas')->where('id', $nota->id)->update([
        'consulta_lote_id' => $loteId,
        'situacao_sefaz' => 'AUTORIZADA',
        'verificado_sefaz_em' => now(),
    ]);
    $nota->refresh();

    $adapter = new XmlNotaSefazCteAdapter($nota);
    $normalizada = $adapter->carregar();

    expect($normalizada)->toBeInstanceOf(NotaNormalizada::class);
    expect($normalizada->chave)->toBe($chave);
    expect($normalizada->tipoDocumento)->toBe('CTE');
    expect($normalizada->header['numero'])->toBe('4321');
    expect($normalizada->header['modelo'])->toBe('57');
    expect($normalizada->metaSefaz['situacao'])->toBe('AUTORIZADA');
    expect($normalizada->metaSefaz['protocolo'])->toBe('150240007166359');
    expect($normalizada->partes['emit']['cnpj'])->toBe('08923352000120');
    expect($normalizada->partes['dest']['cnpj'])->toBe('00000165000193');
    expect($normalizada->partes['tomador']['cnpj'])->toBe('00000165000193');
    expect($normalizada->partes['remetente']['cnpj'])->toBe('11111111000111');
    expect($normalizada->totais['valor_total'])->toBe(845.30);
    expect($normalizada->totais['valor_icms'])->toBe(101.44);
    expect($normalizada->itens)->toHaveCount(2);
    expect($normalizada->itens[0])->toBeInstanceOf(\App\Services\Clearance\Comparacao\ComponenteCte::class);
    expect($normalizada->itens[0]->nome)->toBe('FRETE PESO');
    expect($normalizada->itens[0]->valor)->toBe(700.00);
    expect($normalizada->itens[1]->valor)->toBe(145.30);
    expect($adapter->origemLabel())->toContain('SEFAZ');
});

it('XmlNotaSefazCteAdapter aceita partes nulas', function () use (&$testUserIds) {
    $user = adapterCriarUser($testUserIds);
    $chave = '50202412345678901234560570010001234567890124';

    $nota = XmlNota::create([
        'user_id' => $user->id,
        'nfe_id' => $chave,
        'origem' => 'busca_avulsa',
        'tipo_documento' => 'CTE',
        'numero_nota' => 1,
        'serie' => 1,
        'data_emissao' => '2026-04-15 09:00:00',
        'valor_total' => 100.00,
        'tipo_nota' => XmlNota::TIPO_SAIDA,
        'emit_cnpj' => '11111111000111',
        'dest_cnpj' => '',
        'payload' => [
            'cte_clearance' => [
                'status' => 'AUTORIZADA',
                'emitente' => ['cnpj' => '11111111000111', 'nome' => 'EMIT', 'uf' => 'SP'],
                'destinatario' => null,
                'tomador' => null,
                'remetente' => null,
                'componentes' => [],
            ],
        ],
    ]);

    $adapter = new XmlNotaSefazCteAdapter($nota);
    $normalizada = $adapter->carregar();

    expect($normalizada->partes['emit']['cnpj'])->toBe('11111111000111');
    expect($normalizada->partes['dest'])->toBeArray();
    expect($normalizada->partes['dest']['cnpj'])->toBe('');
    expect($normalizada->partes['tomador']['cnpj'])->toBeNull();
    expect($normalizada->partes['remetente']['cnpj'])->toBeNull();
    expect($normalizada->itens)->toBe([]);
});
