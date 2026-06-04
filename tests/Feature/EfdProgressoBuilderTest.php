<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\EfdProgressoBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = \DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id,
        'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100',
        'is_empresa_propria' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function novaImportacao(int $userId, int $clienteId, string $tipo = 'EFD PIS/COFINS', string $status = 'processando'): EfdImportacao
{
    return EfdImportacao::create([
        'user_id' => $userId,
        'cliente_id' => $clienteId,
        'tipo_efd' => $tipo,
        'filename' => 'teste.txt',
        'status' => $status,
        'iniciado_em' => now()->subMinutes(1),
    ]);
}

it('retorna status processando e notas_blocos vazio quando importacao acabou de comecar', function () {
    $imp = novaImportacao($this->user->id, $this->cliente);

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['status'])->toBe('processando');
    expect($p['notas_blocos'])->toBe([]);
    expect($p['progresso'])->toBe(0);
    expect($p['resumo_final'])->toBeNull();
});

it('marca bloco participantes como processando quando ja tem participantes inseridos', function () {
    $imp = novaImportacao($this->user->id, $this->cliente);

    \DB::table('participantes')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $imp->id,
        'documento' => '12345678000100', 'razao_social' => 'X',
        'tipo_documento' => 'PJ', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['notas_blocos']['participantes']['status'])->toBe('processando');
    expect($p['notas_blocos']['participantes']['count'])->toBe(1);
});

it('marca todos os blocos com count>0 como concluido quando importacao esta concluido', function () {
    $imp = novaImportacao($this->user->id, $this->cliente, 'EFD PIS/COFINS', 'concluido');

    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp->id,
        'chave_acesso' => str_pad('1', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'numero' => 1,
        'serie' => '1', 'data_emissao' => '2024-01-01', 'tipo_operacao' => 'saida', 'valor_total' => 100, 'cancelada' => false,
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp->id,
        'chave_acesso' => 'NFSE1', 'modelo' => '00', 'numero' => 999,
        'serie' => '', 'data_emissao' => '2024-01-01', 'tipo_operacao' => 'saida', 'valor_total' => 50, 'cancelada' => false,
    ]);

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['status'])->toBe('concluido');
    expect($p['notas_blocos']['notas_mercadorias']['status'])->toBe('concluido');
    expect($p['notas_blocos']['notas_servicos']['status'])->toBe('concluido');
    expect($p['progresso'])->toBe(100);
    expect($p['resumo_final'])->not->toBeNull();
    expect($p['resumo_final']['blocos']['notas_servicos']['total_notas'])->toBe(1);
});

it('reflete status erro do banco', function () {
    $imp = novaImportacao($this->user->id, $this->cliente, 'EFD PIS/COFINS', 'erro');

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['status'])->toBe('erro');
    expect($p['resumo_final'])->toBeNull();
});

it('separa blocos por tipo (PIS/COFINS nao expoe apuracao_icms)', function () {
    $imp = novaImportacao($this->user->id, $this->cliente, 'EFD PIS/COFINS', 'concluido');

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['blocos_esperados'])->toContain('participantes');
    expect($p['blocos_esperados'])->toContain('notas_servicos');
    expect($p['blocos_esperados'])->toContain('apuracao_pis_cofins');
    expect($p['blocos_esperados'])->not->toContain('apuracao_icms');
    expect($p['blocos_esperados'])->not->toContain('notas_transportes');
});

it('ICMS/IPI expoe apuracao_icms e notas_transportes mas nao notas_servicos', function () {
    $imp = novaImportacao($this->user->id, $this->cliente, 'EFD ICMS/IPI', 'concluido');

    $p = (new EfdProgressoBuilder)->build($imp);

    expect($p['blocos_esperados'])->toContain('apuracao_icms');
    expect($p['blocos_esperados'])->toContain('notas_transportes');
    expect($p['blocos_esperados'])->not->toContain('notas_servicos');
    expect($p['blocos_esperados'])->not->toContain('apuracao_pis_cofins');
});

it('progresso = razao de blocos com count>0 sobre blocos esperados durante processamento', function () {
    $imp = novaImportacao($this->user->id, $this->cliente, 'EFD PIS/COFINS');
    // PIS/COFINS espera 6 blocos: participantes, catalogo, notas_servicos, notas_mercadorias, apuracao_pis_cofins, retencoes_fonte

    \DB::table('participantes')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_efd_id' => $imp->id,
        'documento' => '11111111000111', 'razao_social' => 'P1', 'tipo_documento' => 'PJ',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    \DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp->id,
        'cod_item' => 'A1', 'descr_item' => 'X', 'tipo_item' => '00',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $p = (new EfdProgressoBuilder)->build($imp);

    // 2 de 6 blocos com dados → ~33%
    expect($p['progresso'])->toBeGreaterThan(0);
    expect($p['progresso'])->toBeLessThan(100);
});
