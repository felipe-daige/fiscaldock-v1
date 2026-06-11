<?php

use App\Jobs\ProcessarXmlImportacaoJob;
use App\Models\Cliente;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\NfeFixtureMint;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function semearXmls(int $impId): string
{
    $dir = "xml-imports/{$impId}";
    foreach (glob(base_path('tests/Fixtures/nfe/*-nfe.xml')) as $f) {
        Storage::disk('local')->put($dir.'/'.basename($f), file_get_contents($f));
    }

    return $dir;
}

it('processa o diretório, grava notas e marca concluído', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'zip',
        'status' => 'processando', 'iniciado_em' => now(),
    ]);
    $dir = semearXmls($imp->id);

    (new ProcessarXmlImportacaoJob($imp->id, $user->id, 'tab-1', '97551165000193', $dir))
        ->handle(app(\App\Services\Xml\NfeXmlParser::class), app(\App\Services\Xml\XmlNotaImporter::class));

    $imp->refresh();
    expect($imp->status)->toBe('concluido');
    expect($imp->xmls_novos)->toBe(10);
    expect(XmlNota::where('user_id', $user->id)->count())->toBe(10);

    $cache = Cache::get("progresso:{$user->id}:tab-1");
    expect($cache['status'])->toBe('concluido');
});

it('conta duplicados ao reprocessar o mesmo diretório', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $imp1 = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'zip', 'status' => 'processando', 'iniciado_em' => now()]);
    $dir1 = semearXmls($imp1->id);
    (new ProcessarXmlImportacaoJob($imp1->id, $user->id, 't1', '97551165000193', $dir1))
        ->handle(app(\App\Services\Xml\NfeXmlParser::class), app(\App\Services\Xml\XmlNotaImporter::class));

    $imp2 = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'zip', 'status' => 'processando', 'iniciado_em' => now()]);
    $dir2 = semearXmls($imp2->id);
    (new ProcessarXmlImportacaoJob($imp2->id, $user->id, 't2', '97551165000193', $dir2))
        ->handle(app(\App\Services\Xml\NfeXmlParser::class), app(\App\Services\Xml\XmlNotaImporter::class));

    $imp2->refresh();
    expect($imp2->xmls_duplicados_processados)->toBe(10);
    expect($imp2->xmls_novos)->toBe(0);
    expect(XmlNota::where('user_id', $user->id)->count())->toBe(10);
});

it('modo criar-pelo-lado: cria o cliente e backfilla o cliente_id da importação', function () {
    Storage::fake('local');
    $user = App\Models\User::factory()->create();
    $imp = XmlImportacao::create([
        'user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'zip',
        'status' => 'processando', 'iniciado_em' => now(),
    ]);
    $dir = semearXmls($imp->id); // 10 fixtures, todas com emit 97551165000193

    (new ProcessarXmlImportacaoJob($imp->id, $user->id, 'tab-1', '', $dir, 'emit'))
        ->handle(app(\App\Services\Xml\NfeXmlParser::class), app(\App\Services\Xml\XmlNotaImporter::class));

    $cliente = App\Models\Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    expect($cliente)->not->toBeNull();

    $imp->refresh();
    expect($imp->cliente_id)->toBe($cliente->id); // backfill do dono mais comum
});

it('deduplica a mesma chave repetida dentro do mesmo lote', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'zip', 'status' => 'processando', 'iniciado_em' => now()]);

    // Mesmo XML gravado com dois nomes diferentes no mesmo diretório.
    $dir = "xml-imports/{$imp->id}";
    $conteudo = file_get_contents(base_path('tests/Fixtures/nfe/50240197551165000193550010000248021000214750-nfe.xml'));
    Storage::disk('local')->put($dir.'/nota-a.xml', $conteudo);
    Storage::disk('local')->put($dir.'/nota-b.xml', $conteudo);

    (new ProcessarXmlImportacaoJob($imp->id, $user->id, 't1', '97551165000193', $dir))
        ->handle(app(\App\Services\Xml\NfeXmlParser::class), app(\App\Services\Xml\XmlNotaImporter::class));

    $imp->refresh();
    expect($imp->xmls_novos)->toBe(1);
    expect($imp->xmls_duplicados_processados)->toBe(1);
    expect(XmlNota::where('user_id', $user->id)->count())->toBe(1);
});

it('header fica null (Vários) quando o lote resolve mais de um cliente dono', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    // Dois clientes cadastrados: cada um é EMITENTE de uma nota (saída) → 2 donos.
    Cliente::create(['user_id' => $user->id, 'documento' => '11111111000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE A', 'ativo' => true, 'is_empresa_propria' => false]);
    Cliente::create(['user_id' => $user->id, 'documento' => '33333333000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE B', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'processando', 'iniciado_em' => now()]);
    $dir = "xml-imports/{$imp->id}";
    Storage::disk('local')->put("$dir/a.xml", NfeFixtureMint::make('11111111000191', '22222222000191', '50240111111111000191550010000999990000000001'));
    Storage::disk('local')->put("$dir/b.xml", NfeFixtureMint::make('33333333000191', '44444444000191', '50240133333333000191550010000999990000000002'));

    // decidir_depois: ownerDoc='' ownerLado=''
    (new ProcessarXmlImportacaoJob($imp->id, $user->id, 'tab-1', '', $dir, ''))
        ->handle(app(NfeXmlParser::class), app(XmlNotaImporter::class));

    $imp->refresh();
    expect($imp->cliente_id)->toBeNull();
    expect($imp->clientesResolvidos())->toBe(2);
});

it('header recebe o cliente único quando o lote resolve um só dono', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $cliente = Cliente::create(['user_id' => $user->id, 'documento' => '11111111000191', 'tipo_pessoa' => 'PJ', 'razao_social' => 'CLIENTE A', 'ativo' => true, 'is_empresa_propria' => false]);

    $imp = XmlImportacao::create(['user_id' => $user->id, 'tipo_documento' => 'NFE', 'modo_envio' => 'xml', 'status' => 'processando', 'iniciado_em' => now()]);
    $dir = "xml-imports/{$imp->id}";
    // Mesmo emitente (cliente A) vende para 2 compradores distintos → 1 dono.
    Storage::disk('local')->put("$dir/a.xml", NfeFixtureMint::make('11111111000191', '22222222000191', '50240111111111000191550010000999990000000001'));
    Storage::disk('local')->put("$dir/b.xml", NfeFixtureMint::make('11111111000191', '44444444000191', '50240111111111000191550010000999990000000002'));

    (new ProcessarXmlImportacaoJob($imp->id, $user->id, 'tab-2', '', $dir, ''))
        ->handle(app(NfeXmlParser::class), app(XmlNotaImporter::class));

    $imp->refresh();
    expect($imp->cliente_id)->toBe($cliente->id);
    expect($imp->clientesResolvidos())->toBe(1);
});
