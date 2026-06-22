<?php

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\User;

/**
 * Regressão: quando o usuário importa o EFD da PRÓPRIA empresa, todos os 0150
 * (contrapartes) ficam com cliente_id = empresa própria. O scope não pode zerar
 * a listagem inteira de participantes — deve excluir apenas o registro que
 * REPRESENTA a empresa própria (mesmo documento).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    $uid = $this->user->id;
    Participante::where('user_id', $uid)->forceDelete();
    Cliente::where('user_id', $uid)->forceDelete();
    $this->user->forceDelete();
});

it('mantém as contrapartes do livro da empresa própria (não zera a listagem)', function () {
    $empresaPropria = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '97551165000193',
        'nome' => 'HIDRATOP LTDA',
        'razao_social' => 'HIDRATOP LTDA',
        'is_empresa_propria' => true,
    ]);

    foreach (['07903169001768', '44373108000600', '96414391115'] as $doc) {
        Participante::create([
            'user_id' => $this->user->id,
            'cliente_id' => $empresaPropria->id,
            'documento' => $doc,
            'razao_social' => "Contraparte {$doc}",
        ]);
    }

    $visiveis = Participante::where('user_id', $this->user->id)
        ->excludingEmpresaPropria()
        ->count();

    expect($visiveis)->toBe(3);
});

it('exclui apenas o registro que representa a própria empresa (mesmo documento)', function () {
    $empresaPropria = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '97551165000193',
        'nome' => 'HIDRATOP LTDA',
        'razao_social' => 'HIDRATOP LTDA',
        'is_empresa_propria' => true,
    ]);

    // Registro que É a própria empresa (mesmo documento) — deve ser excluído.
    Participante::create([
        'user_id' => $this->user->id,
        'cliente_id' => $empresaPropria->id,
        'documento' => '97551165000193',
        'razao_social' => 'HIDRATOP LTDA',
    ]);

    // Contraparte legítima — deve permanecer.
    Participante::create([
        'user_id' => $this->user->id,
        'cliente_id' => $empresaPropria->id,
        'documento' => '07903169001768',
        'razao_social' => 'Cliente X',
    ]);

    $docs = Participante::where('user_id', $this->user->id)
        ->excludingEmpresaPropria()
        ->orderBy('documento')
        ->pluck('documento')
        ->all();

    expect($docs)->toBe(['07903169001768']);
});
