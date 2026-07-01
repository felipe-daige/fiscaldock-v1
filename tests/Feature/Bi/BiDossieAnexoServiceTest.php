<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\Bi\BiDossieAnexoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function semearAnexo(): array
{
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Cliente Um',
        'is_empresa_propria' => true, 'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $p1 = Participante::create(['user_id' => $user->id, 'cliente_id' => $cli, 'documento' => '11111111000111', 'razao_social' => 'FORNECEDOR MENOR', 'origem_tipo' => 'MANUAL'])->id;
    $p2 = Participante::create(['user_id' => $user->id, 'cliente_id' => $cli, 'documento' => '22222222000122', 'razao_social' => 'FORNECEDOR MAIOR', 'origem_tipo' => 'MANUAL'])->id;
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $mk = function (int $part, float $valor, int $n) use ($user, $cli, $imp) {
        EfdNota::create([
            'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $part, 'importacao_id' => $imp->id,
            'numero' => $n, 'serie' => '1', 'modelo' => '55', 'chave_acesso' => str_pad((string) $n, 44, '0'),
            'valor_total' => $valor, 'valor_desconto' => 0, 'cancelada' => false, 'origem_arquivo' => 'fiscal',
            'tipo_operacao' => 'entrada', 'data_emissao' => '2026-03-10',
        ]);
    };
    $mk($p1, 1000, 1);
    $mk($p2, 9000, 2);

    return compact('user', 'cli', 'p1', 'p2');
}

it('retorna null quando a opcao é vazia', function () {
    ['user' => $u] = semearAnexo();
    expect(app(BiDossieAnexoService::class)->montar($u->id, null, ''))->toBeNull();
});

it('monta dossiês de participantes ordenados por volume desc', function () {
    ['user' => $u] = semearAnexo();
    $out = app(BiDossieAnexoService::class)->montar($u->id, null, '20');

    expect($out)->not->toBeNull()
        ->and($out['participantes'])->toHaveCount(2)
        ->and($out['participantes'][0]['participante']->razao_social)->toBe('FORNECEDOR MAIOR')
        ->and($out['participantes'][1]['participante']->razao_social)->toBe('FORNECEDOR MENOR')
        ->and($out['clientes'])->toHaveCount(1)
        ->and($out['clientes'][0]['cliente']->razao_social)->toBe('Cliente Um');
});

it('escopo de cliente inclui só o cliente e seus participantes', function () {
    ['user' => $u, 'cli' => $cli] = semearAnexo();
    $out = app(BiDossieAnexoService::class)->montar($u->id, $cli, '50');

    expect($out['clientes'])->toHaveCount(1)
        ->and($out['participantes'])->toHaveCount(2);
});

it('a opcao todos aplica o teto de 300 e monta', function () {
    ['user' => $u] = semearAnexo();
    $out = app(BiDossieAnexoService::class)->montar($u->id, null, 'todos');

    expect(BiDossieAnexoService::TETO_TODOS)->toBe(300)
        ->and($out['participantes'])->toHaveCount(2);
});
