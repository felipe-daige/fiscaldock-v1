<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\Clearance\DivergenciaService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('retorna estrutura vazia quando não há snapshots', function () {
    $service = new DivergenciaService;

    $resultado = $service->analisar(new Collection, userId: 1, creditosCobrados: 0);

    expect($resultado)->toHaveKeys(['veredito', 'kpis', 'breakdown', 'divergencias', 'sem_divergencia', 'ruido']);
    expect($resultado['veredito']['severidade'])->toBe('ok');
    expect($resultado['veredito']['total_criticas'])->toBe(0);
    expect($resultado['veredito']['total_revisar'])->toBe(0);
    expect($resultado['veredito']['valor_divergente'])->toBe(0.0);
    expect($resultado['kpis']['existencia']['total'])->toBe(0);
    expect($resultado['divergencias'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($resultado['sem_divergencia'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
    expect($resultado['ruido'])->toBeInstanceOf(Collection::class)->toHaveCount(0);
});

it('carrega declarado de efd_notas e xml_notas pela chave', function () {
    $user = User::factory()->create();
    $cliente = \App\Models\Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '12345678901234',
        'razao_social' => 'Test Cliente',
        'is_empresa_propria' => false,
    ]);

    $importacao = EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'finalizada',
        'periodo_inicial' => '2024-02-01',
        'periodo_final' => '2024-02-29',
    ]);

    EfdNota::create([
        'user_id' => $user->id,
        'importacao_id' => $importacao->id,
        'cliente_id' => $cliente->id,
        'chave_acesso' => '50240246088921000159550010000017471100017471',
        'modelo' => '55',
        'numero' => 1747,
        'serie' => '1',
        'data_emissao' => '2024-02-29',
        'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal',
        'valor_total' => 250.00,
    ]);

    $service = new DivergenciaService;

    $declarado = $service->buscarDeclaradoPorChave(
        $user->id,
        ['50240246088921000159550010000017471100017471', '00000000000000000000000000000000000000000000']
    );

    expect($declarado)->toBeArray();
    expect($declarado)->toHaveKey('50240246088921000159550010000017471100017471');
    expect($declarado['50240246088921000159550010000017471100017471']['valor_total'])->toEqual(250.00);
    expect($declarado['50240246088921000159550010000017471100017471']['origem'])->toBe('efd');
    expect($declarado)->not->toHaveKey('00000000000000000000000000000000000000000000');
});

it('classifica severidade pelas regras canônicas', function () {
    $service = new DivergenciaService;

    // Caso SOTRACTOR: declarado 250, SEFAZ 1135 → crítica
    expect($service->classificarSeveridade(statusSefaz: 'AUTORIZADA', declarado: 250.00, sefaz: 1135.00))
        ->toBe('critica');

    // Caso WURTH 111720: declarado 4801, SEFAZ 4957,52 → revisar (3,3%, R$ 156,52)
    expect($service->classificarSeveridade(statusSefaz: 'AUTORIZADA', declarado: 4801.00, sefaz: 4957.52))
        ->toBe('revisar');

    // Caso WURTH 111721: declarado 51, SEFAZ 51,11 → ruído (R$ 0,11)
    expect($service->classificarSeveridade(statusSefaz: 'AUTORIZADA', declarado: 51.00, sefaz: 51.11))
        ->toBe('ruido');

    // Caso PANTANAL CT-e: declarado = SEFAZ → ok
    expect($service->classificarSeveridade(statusSefaz: 'AUTORIZADA', declarado: 114.98, sefaz: 114.98))
        ->toBe('ok');

    // Nota fria: status SEFAZ NAO_ENCONTRADA + declarado > 0 → crítica
    expect($service->classificarSeveridade(statusSefaz: 'NAO_ENCONTRADA', declarado: 1000.00, sefaz: null))
        ->toBe('critica');

    // Cancelada declarada: SEFAZ CANCELADA + declarado > 0 → crítica
    expect($service->classificarSeveridade(statusSefaz: 'CANCELADA', declarado: 500.00, sefaz: 500.00))
        ->toBe('critica');

    // Sem declarado (só SEFAZ): → ok
    expect($service->classificarSeveridade(statusSefaz: 'AUTORIZADA', declarado: null, sefaz: 1000.00))
        ->toBe('ok');
});

it('agrega veredito, kpis e breakdown a partir de snapshots + declarado', function () {
    $user = \App\Models\User::factory()->create();
    $cliente = \App\Models\Cliente::create([
        'user_id' => $user->id,
        'tipo_pessoa' => 'PJ',
        'documento' => '00000000000191',
        'razao_social' => 'Empresa Teste',
        'is_empresa_propria' => true,
    ]);
    $importacao = \App\Models\EfdImportacao::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD ICMS/IPI',
        'status' => 'finalizada',
        'periodo_inicial' => '2024-02-01',
        'periodo_final' => '2024-02-29',
    ]);

    $fixtures = [
        ['50240246088921000159550010000017471100017471', 250.00, 1135.00],
        ['50240207664213000248550010000408331100408338', 135.00, 1424.35],
        ['50240243648971004576550010001117201983139706', 4801.00, 4957.52],
        ['50240243648971004576550010001117211468024730', 51.00, 51.11],
        ['35240246970030000202570010000432381000772218', 114.98, 114.98],
    ];

    foreach ($fixtures as [$chave, $declarado, $_sefaz]) {
        \App\Models\EfdNota::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'importacao_id' => $importacao->id,
            'chave_acesso' => $chave,
            'modelo' => substr($chave, 20, 2),
            'numero' => (int) substr($chave, 25, 9),
            'serie' => '1',
            'data_emissao' => '2024-02-29',
            'tipo_operacao' => 'saida',
            'origem_arquivo' => 'fiscal',
            'valor_total' => $declarado,
        ]);
    }

    $snapshots = collect($fixtures)->map(fn ($f) => (object) [
        'chave_acesso' => $f[0],
        'tipo_documento' => 'NFE',
        'modelo' => substr($f[0], 20, 2),
        'numero' => (string) (int) substr($f[0], 25, 9),
        'serie' => '1',
        'status' => 'AUTORIZADA',
        'status_label' => 'AUTORIZADA',
        'valor_total' => $f[2],
        'emit_nome' => 'EMIT '.substr($f[0], -4),
        'emit_cnpj' => substr($f[0], 6, 14),
        'dest_nome' => 'DEST',
        'participante_label' => 'DEST',
        'data_emissao' => '2024-02-29',
        'data_emissao_label' => '29/02/2024 00:00',
        'consultado_em_label' => '24/04/2026 09:11',
        'detalhe_url' => null,
    ]);

    $service = new DivergenciaService;
    $resultado = $service->analisar($snapshots, $user->id, creditosCobrados: 50);

    expect($resultado['veredito']['severidade'])->toBe('critica');
    expect($resultado['veredito']['total_criticas'])->toBe(2);
    expect($resultado['veredito']['total_revisar'])->toBe(1);
    expect(round($resultado['veredito']['valor_divergente'], 2))->toBe(2174.35);

    expect($resultado['kpis']['existencia']['total'])->toBe(5);
    expect($resultado['kpis']['existencia']['encontradas'])->toBe(5);
    expect($resultado['kpis']['status']['canceladas_declaradas'])->toBe(0);
    expect($resultado['kpis']['valor']['notas_divergentes'])->toBe(2);
    expect($resultado['kpis']['roi']['creditos'])->toBe(50);
    expect($resultado['kpis']['roi']['custo_reais'])->toBe(10.00);
    expect(round($resultado['kpis']['roi']['exposicao_reais'], 2))->toBe(2174.35);

    expect($resultado['breakdown']['valor_divergente']['count'])->toBe(2);
    expect($resultado['divergencias'])->toHaveCount(3);
    expect($resultado['sem_divergencia'])->toHaveCount(1);
    expect($resultado['ruido'])->toHaveCount(1);
});
