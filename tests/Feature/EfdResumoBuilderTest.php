<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\EfdResumoBuilder;
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

    $this->imp = EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'icms.txt',
        'status' => 'processando',
        'iniciado_em' => now()->subMinutes(2),
    ]);
});

it('retorna resumo zerado em importacao vazia', function () {
    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['importacao_id'])->toBe($this->imp->id);
    expect($resumo['user_id'])->toBe($this->user->id);
    expect($resumo['tipo_sped'])->toBe('EFD ICMS/IPI');
    expect($resumo['estatisticas']['total_notas_processadas'])->toBe(0);
    expect($resumo['estatisticas']['notas_canceladas'])->toBe(0);
    expect($resumo['estatisticas']['total_participantes_processados'])->toBe(0);
    expect($resumo['blocos']['notas_mercadorias']['total_notas'])->toBe(0);
    expect($resumo['blocos']['notas_mercadorias']['valor_total'])->toBe(0.0);
    expect($resumo['blocos']['notas_transportes']['total_notas'])->toBe(0);
    expect($resumo['totais']['notas'])->toBe(0);
    expect($resumo['totais']['valor'])->toBe(0.0);
});

it('conta NF-e e CT-e com somas por modelo', function () {
    // 2 NF-e entrada R$ 1000 cada
    foreach ([1, 2] as $n) {
        EfdNota::create([
            'user_id' => $this->user->id,
            'cliente_id' => $this->cliente,
            'importacao_id' => $this->imp->id,
            'chave_acesso' => str_pad((string) $n, 44, '0', STR_PAD_LEFT),
            'modelo' => '55',
            'numero' => 100 + $n,
            'serie' => '1',
            'data_emissao' => '2024-01-0'.$n,
            'tipo_operacao' => 'entrada',
            'valor_total' => 1000,
            'cancelada' => false,
        ]);
    }
    // 1 NF-e saida R$ 5000
    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('99', 44, '0', STR_PAD_LEFT),
        'modelo' => '55',
        'numero' => 999,
        'serie' => '1',
        'data_emissao' => '2024-01-10',
        'tipo_operacao' => 'saida',
        'valor_total' => 5000,
        'cancelada' => false,
    ]);
    // 1 CT-e entrada R$ 250
    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('77', 44, '0', STR_PAD_LEFT),
        'modelo' => '57',
        'numero' => 777,
        'serie' => '1',
        'data_emissao' => '2024-01-15',
        'tipo_operacao' => 'entrada',
        'valor_total' => 250,
        'cancelada' => false,
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['blocos']['notas_mercadorias']['total_notas'])->toBe(3);
    expect($resumo['blocos']['notas_mercadorias']['valor_total'])->toBe(7000.0);
    expect($resumo['blocos']['notas_transportes']['total_notas'])->toBe(1);
    expect($resumo['blocos']['notas_transportes']['valor_total'])->toBe(250.0);
    expect($resumo['estatisticas']['total_notas_processadas'])->toBe(4);
    expect($resumo['totais']['notas'])->toBe(4);
    expect($resumo['totais']['valor'])->toBe(7250.0);
});

it('separa canceladas em estatistica dedicada', function () {
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('a', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-01',
        'tipo_operacao' => 'saida', 'valor_total' => 100, 'cancelada' => false,
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('b', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 2, 'serie' => '1', 'data_emissao' => '2024-01-02',
        'tipo_operacao' => 'saida', 'valor_total' => 0, 'cancelada' => true,
    ]);
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('c', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 3, 'serie' => '1', 'data_emissao' => '2024-01-03',
        'tipo_operacao' => 'saida', 'valor_total' => 0, 'cancelada' => true,
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['estatisticas']['total_notas_processadas'])->toBe(3);
    expect($resumo['estatisticas']['notas_canceladas'])->toBe(2);
});

it('conta participantes e catalogo do escopo da importacao', function () {
    // 3 participantes criados por ESTA importação (importacao_efd_id), referenciados por notas.
    $p1 = \DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $this->imp->id,
        'documento' => '12345678000100', 'razao_social' => 'P1',
        'tipo_documento' => 'PJ', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $p2 = \DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $this->imp->id,
        'documento' => '98765432000100', 'razao_social' => 'P2',
        'tipo_documento' => 'PJ', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $p3 = \DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $this->imp->id,
        'documento' => '11122233344', 'razao_social' => 'P3 CPF',
        'tipo_documento' => 'PF', 'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([$p1, $p2, $p3] as $i => $pid) {
        EfdNota::create([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
            'participante_id' => $pid,
            'chave_acesso' => str_pad('p'.$i, 44, '0', STR_PAD_LEFT),
            'modelo' => '55', 'numero' => 300 + $i, 'serie' => '1', 'data_emissao' => '2024-01-0'.($i + 1),
            'tipo_operacao' => 'entrada', 'valor_total' => 10, 'cancelada' => false,
        ]);
    }

    \DB::table('efd_catalogo_itens')->insert([
        [
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
            'importacao_id' => $this->imp->id,
            'cod_item' => 'A1', 'descr_item' => 'Item A', 'tipo_item' => '00',
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
            'importacao_id' => $this->imp->id,
            'cod_item' => 'B2', 'descr_item' => 'Item B', 'tipo_item' => '00',
            'created_at' => now(), 'updated_at' => now(),
        ],
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    // total = participantes REFERENCIADOS pelas notas (movimentados)
    expect($resumo['estatisticas']['total_participantes_processados'])->toBe(3);
    expect($resumo['estatisticas']['total_cnpjs_unicos'])->toBe(2);
    expect($resumo['estatisticas']['total_cpfs_unicos'])->toBe(1);
    // novos = criados por esta importação (importacao_efd_id) — aqui os 3
    expect($resumo['estatisticas']['participantes_novos'])->toBe(3);
    expect($resumo['estatisticas']['participantes_repetidos'])->toBe(0);
    expect($resumo['blocos']['catalogo']['total_itens'])->toBe(2);
});

it('distingue participantes referenciados (total) de novos desta importacao', function () {
    // Participante pré-existente, criado por OUTRA importação
    $impAnterior = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'anterior.txt',
        'status' => 'concluido', 'iniciado_em' => now()->subDay(),
    ]);
    $preExistente = \DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $impAnterior->id,
        'documento' => '55555555000100', 'razao_social' => 'PRE',
        'tipo_documento' => 'PJ', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
    ]);
    // Participante novo, criado por ESTA importação
    $novo = \DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'importacao_efd_id' => $this->imp->id,
        'documento' => '66666666000100', 'razao_social' => 'NOVO',
        'tipo_documento' => 'PJ', 'created_at' => now(), 'updated_at' => now(),
    ]);

    // ESTA importação referencia AMBOS nas notas
    foreach ([$preExistente, $novo] as $i => $pid) {
        EfdNota::create([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
            'participante_id' => $pid,
            'chave_acesso' => str_pad('r'.$i, 44, '0', STR_PAD_LEFT),
            'modelo' => '55', 'numero' => 400 + $i, 'serie' => '1', 'data_emissao' => '2024-01-0'.($i + 1),
            'tipo_operacao' => 'entrada', 'valor_total' => 10, 'cancelada' => false,
        ]);
    }

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['estatisticas']['total_participantes_processados'])->toBe(2); // referenciados: pré + novo
    expect($resumo['estatisticas']['participantes_novos'])->toBe(1);             // só o criado por esta importação
    expect($resumo['estatisticas']['participantes_repetidos'])->toBe(1);         // o pré-existente
    expect($resumo['participante_ids'])->toContain($preExistente);
    expect($resumo['participante_ids'])->toContain($novo);
});

it('inclui apuracao ICMS quando presente', function () {
    \DB::table('efd_apuracoes_icms')->insert([
        'importacao_id' => $this->imp->id,
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'icms_a_recolher' => 1500.00,
        'st_icms_recolher' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['blocos']['apuracao_icms']['total_notas'])->toBe(1);
    expect($resumo['blocos']['apuracao_icms']['valor_total'])->toBe(1500.0);
});

it('conta NFS-e (modelo 00) em notas_servicos e soma nos totais', function () {
    // 3 NFS-e PIS/COFINS R$ 1000 cada
    foreach ([1, 2, 3] as $n) {
        EfdNota::create([
            'user_id' => $this->user->id,
            'cliente_id' => $this->cliente,
            'importacao_id' => $this->imp->id,
            'chave_acesso' => 'NFSE'.$n,
            'modelo' => '00',
            'numero' => 1000 + $n,
            'serie' => '',
            'data_emissao' => '2024-02-0'.$n,
            'tipo_operacao' => 'saida',
            'origem_arquivo' => 'contribuicoes',
            'valor_total' => 1000,
            'cancelada' => false,
        ]);
    }
    // 1 NF-e R$ 500 pra garantir que mercadorias continua funcionando
    EfdNota::create([
        'user_id' => $this->user->id,
        'cliente_id' => $this->cliente,
        'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('55', 44, '0', STR_PAD_LEFT),
        'modelo' => '55',
        'numero' => 55,
        'serie' => '1',
        'data_emissao' => '2024-02-10',
        'tipo_operacao' => 'saida',
        'valor_total' => 500,
        'cancelada' => false,
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['blocos']['notas_servicos']['total_notas'])->toBe(3);
    expect($resumo['blocos']['notas_servicos']['valor_total'])->toBe(3000.0);
    expect($resumo['blocos']['notas_mercadorias']['total_notas'])->toBe(1);
    expect($resumo['blocos']['notas_mercadorias']['valor_total'])->toBe(500.0);
    expect($resumo['estatisticas']['total_notas_processadas'])->toBe(4);
    expect($resumo['totais']['notas'])->toBe(4);
    expect($resumo['totais']['valor'])->toBe(3500.0);
});

it('inclui NFS-e na mensagem humana', function () {
    foreach ([1, 2] as $n) {
        EfdNota::create([
            'user_id' => $this->user->id,
            'cliente_id' => $this->cliente,
            'importacao_id' => $this->imp->id,
            'chave_acesso' => 'NFSE'.$n,
            'modelo' => '00',
            'numero' => 2000 + $n,
            'serie' => '',
            'data_emissao' => '2024-02-0'.$n,
            'tipo_operacao' => 'saida',
            'origem_arquivo' => 'contribuicoes',
            'valor_total' => 100,
            'cancelada' => false,
        ]);
    }

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['mensagem'])->toContain('2 NFS-e');
});

it('gera mensagem humana com counts principais', function () {
    foreach (range(1, 5) as $n) {
        EfdNota::create([
            'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
            'chave_acesso' => str_pad((string) $n, 44, '0', STR_PAD_LEFT),
            'modelo' => '55', 'numero' => $n, 'serie' => '1', 'data_emissao' => '2024-01-01',
            'tipo_operacao' => 'entrada', 'valor_total' => 100, 'cancelada' => false,
        ]);
    }
    EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'chave_acesso' => str_pad('z', 44, '0', STR_PAD_LEFT),
        'modelo' => '55', 'numero' => 999, 'serie' => '1', 'data_emissao' => '2024-01-01',
        'tipo_operacao' => 'saida', 'valor_total' => 0, 'cancelada' => true,
    ]);

    $resumo = (new EfdResumoBuilder)->build($this->imp);

    expect($resumo['mensagem'])->toContain('5');     // notas regulares
    expect($resumo['mensagem'])->toContain('1');     // 1 cancelada
});
