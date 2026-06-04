<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\BiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * F9 — Ficha do participante (`BiService::getFichaParticipante`). O resumo somava
 * `efd_notas` cru → P1 dobra + cancelada (P4); a carga lia tributo dos itens das 2
 * origens (P2/P8). Mas o dedup tem de ser ESCOPADO AO PARTICIPANTE: a atribuição de
 * participante difere entre fiscal e contribuicoes (medido: 436 notas de contrib de
 * um participante têm gêmea fiscal atribuída a OUTRO). Dedup global dropparia essas.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $mkPart = fn (string $doc) => DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'razao_social' => "P {$doc}",
        'documento' => $doc, 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->p1 = $mkPart('11111111000111');
    $this->p2 = $mkPart('22222222000122');
    $impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $impC = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'numero' => random_int(1, 99999),
        'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'modelo' => '55', 'tipo_operacao' => 'saida',
    ], $a));
    $c190 = fn (EfdNota $n, float $icms) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => $n->valor_total, 'valor_bc_icms' => 0, 'valor_icms' => $icms, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $item = fn (EfdNota $n, float $pis, float $cofins, float $icms = 0) => DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => 'X',
        'quantidade' => 1, 'valor_total' => 100, 'cfop' => 5102, 'valor_icms' => $icms, 'valor_pis' => $pis,
        'valor_cofins' => $cofins, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $k = fn (string $s) => str_pad($s, 44, '0', STR_PAD_LEFT);

    // A: mesma NF-e do MESMO p1 nas 2 origens → dobra REAL, colapsa em 1
    $aF = $mk(['importacao_id' => $impF->id, 'chave_acesso' => $k('A'), 'origem_arquivo' => 'fiscal', 'participante_id' => $this->p1, 'valor_total' => 1000]);
    $c190($aF, 100);
    $item($aF, 0, 0, 0.01); // item fiscal lixo (ICMS≈0)
    $aC = $mk(['importacao_id' => $impC->id, 'chave_acesso' => $k('A'), 'origem_arquivo' => 'contribuicoes', 'participante_id' => $this->p1, 'valor_total' => 1000]);
    $item($aC, 10, 20);

    // B: contribuicoes sob p1, mas a gêmea fiscal (mesma chave) está sob p2 → NÃO pode sumir de p1
    $bC = $mk(['importacao_id' => $impC->id, 'chave_acesso' => $k('B'), 'origem_arquivo' => 'contribuicoes', 'participante_id' => $this->p1, 'valor_total' => 500]);
    $item($bC, 5, 5);
    $bF = $mk(['importacao_id' => $impF->id, 'chave_acesso' => $k('B'), 'origem_arquivo' => 'fiscal', 'participante_id' => $this->p2, 'valor_total' => 500]);
    $c190($bF, 90); // ICMS sob p2 — não entra na carga de p1

    // cancelada sob p1 → fora
    $mk(['importacao_id' => $impF->id, 'chave_acesso' => $k('E'), 'origem_arquivo' => 'fiscal', 'participante_id' => $this->p1, 'valor_total' => 9999, 'cancelada' => true]);

    $this->ficha = app(BiService::class)->getFichaParticipante($this->user->id, $this->p1, null, null);
});

it('resumo do participante: dedup escopado (dobra real colapsa, cross-atribuída fica), sem cancelada', function () {
    $r = $this->ficha['resumo'];

    // A (colapsada de 2→1) + B (mantida, gêmea fiscal é de outro participante) = 2. Cancelada fora.
    expect($r['total_notas'])->toBe(2);
    expect($r['total_saidas'])->toEqual(1500.0); // A 1000 + B 500, não 2500
});

it('carga tributária lê ICMS do C190 e PIS/COFINS dos itens contribuicoes (P2/P8)', function () {
    // ICMS C190 de p1 = 100 (só A; B é de p2). PIS/COFINS contrib de p1 = (10+20)+(5+5)=40. Total 140.
    // NÃO o item fiscal lixo (0,01) nem o ICMS de B (sob p2).
    expect($this->ficha['resumo']['carga_tributaria'])->toEqual(140.0);
});

it('ultimas_notas não duplica a NF-e do mesmo participante nas 2 origens (P1)', function () {
    $chaves = collect($this->ficha['ultimas_notas'])->pluck('chave_acesso');

    expect($chaves->duplicates())->toBeEmpty();
    expect(collect($this->ficha['ultimas_notas']))->toHaveCount(2); // A, B
});
