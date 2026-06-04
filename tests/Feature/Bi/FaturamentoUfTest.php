<?php

use App\Models\User;
use App\Services\BiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('getFaturamentoPorUf usa a UF do participante destinatario (EFD) apos enriquecimento', function () {
    $user = User::factory()->create();
    $userId = $user->id;
    $clienteId = \DB::table('clientes')->insertGetId([
        'user_id' => $userId, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = \DB::table('efd_importacoes')->insertGetId([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'tipo_efd' => 'EFD ICMS/IPI', 'status' => 'concluido',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // participante destinatário SEM UF -> não aparece; depois COM UF -> aparece
    $part = \DB::table('participantes')->insertGetId([
        'user_id' => $userId, 'documento' => '11222333000181', 'razao_social' => 'Cliente SP',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    \DB::table('efd_notas')->insert([
        'user_id' => $userId, 'cliente_id' => $clienteId, 'importacao_id' => $imp, 'participante_id' => $part,
        'origem_arquivo' => 'fiscal', 'modelo' => '55', 'tipo_operacao' => 'saida', 'cancelada' => false,
        'valor_total' => 1000, 'data_emissao' => '2024-01-10', 'numero' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $svc = app(BiService::class);
    expect($svc->getFaturamentoPorUf($userId))->toBe([]); // sem UF (cadastro não enriquecido)

    \DB::table('participantes')->where('id', $part)->update(['uf' => 'SP']); // consulta enriquece
    $r = $svc->getFaturamentoPorUf($userId);

    expect($r)->toHaveCount(1);
    expect($r[0]['uf'])->toBe('SP');
    expect($r[0]['total'])->toBe(1000.0);
});
