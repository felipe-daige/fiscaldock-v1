<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Backfill de participante_id nas notas fiscais (EFD ICMS/IPI). O workflow n8n
 * fiscal não casa o COD_PART do C100 com o 0150 (~95% das saídas ficam sem
 * participante_id), apesar do dado existir no SPED. Este comando re-parseia os
 * SPED fiscais (0150 COD_PART→CNPJ + C100 chave→COD_PART) e preenche por chave.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '97551165000193',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->imp = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $this->pA = DB::table('participantes')->insertGetId(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'razao_social' => 'FORNEC A', 'documento' => '11222333000181', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now()]);
    $this->pB = DB::table('participantes')->insertGetId(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'razao_social' => 'FORNEC B', 'documento' => '44555666000172', 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now()]);

    $this->k = fn (string $s) => str_pad($s, 44, '0', STR_PAD_LEFT);
    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0,
        'cancelada' => false, 'modelo' => '55', 'tipo_operacao' => 'saida', 'valor_total' => 100,
    ], $a));

    $this->n1 = $mk(['chave_acesso' => ($this->k)('1'), 'origem_arquivo' => 'fiscal', 'participante_id' => null]);
    $this->n2 = $mk(['chave_acesso' => ($this->k)('2'), 'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'participante_id' => null]);
    $this->n3 = $mk(['chave_acesso' => ($this->k)('3'), 'origem_arquivo' => 'fiscal', 'participante_id' => null]); // COD_PART vazio
    $this->n4 = $mk(['chave_acesso' => ($this->k)('1'), 'origem_arquivo' => 'fiscal', 'participante_id' => $this->pB]); // já preenchida
    $this->n5 = $mk(['chave_acesso' => ($this->k)('1'), 'origem_arquivo' => 'contribuicoes', 'participante_id' => null]); // outra origem

    // SPED fiscal sintético
    $this->dir = sys_get_temp_dir().'/sped_'.uniqid();
    mkdir($this->dir);
    $lines = [
        '|0000|018|0|01012024|31012024|EMPRESA|97551165000193|',
        '|0150|0001|FORNEC A|1058|11222333000181||111|',
        '|0150|0002|FORNEC B|1058|44555666000172||222|',
        '|C100|1|0|0001|55|00|1|248|'.($this->k)('1').'|03012024|03012024|100|',
        '|C100|0|1|0002|55|00|1|249|'.($this->k)('2').'|03012024|03012024|100|',
        '|C100|1|0||55|00|1|250|'.($this->k)('3').'|03012024|03012024|100|',
    ];
    file_put_contents($this->dir.'/SPED-FISCAL-jan.txt', implode("\n", $lines));
});

afterEach(function () {
    @unlink($this->dir.'/SPED-FISCAL-jan.txt');
    @rmdir($this->dir);
});

it('preenche participante_id das notas fiscais por chave a partir do SPED', function () {
    $this->artisan('efd:backfill-participantes-fiscal', ['--dir' => $this->dir])->assertOk();

    expect(EfdNota::find($this->n1->id)->participante_id)->toBe($this->pA); // COD_PART 0001 → FORNEC A
    expect(EfdNota::find($this->n2->id)->participante_id)->toBe($this->pB); // entrada, COD_PART 0002 → FORNEC B
});

it('não sobrescreve participante já preenchido, ignora COD_PART vazio e outras origens', function () {
    $this->artisan('efd:backfill-participantes-fiscal', ['--dir' => $this->dir])->assertOk();

    expect(EfdNota::find($this->n3->id)->participante_id)->toBeNull();          // COD_PART vazio (consumidor)
    expect(EfdNota::find($this->n4->id)->participante_id)->toBe($this->pB);     // já tinha pB, não vira pA
    expect(EfdNota::find($this->n5->id)->participante_id)->toBeNull();          // contribuicoes não é tocada
});

it('dry-run não grava nada', function () {
    $this->artisan('efd:backfill-participantes-fiscal', ['--dir' => $this->dir, '--dry-run' => true])->assertOk();

    expect(EfdNota::find($this->n1->id)->participante_id)->toBeNull();
});
