<?php

use App\Console\Commands\AuditarItensCatalogo;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Auditoria item↔catálogo (0200). A associação é por código (codigo_item = cod_item),
 * já gravado nos dois lados pelo n8n — Laravel só confere por JOIN. CT-e (mod 57) usa
 * D190 consolidado, sem COD_ITEM, então fica FORA da checagem (senão vira órfão falso).
 *  - orfao_estrito: não acha catálogo na MESMA importação (pode ser só versão de outro mês).
 *  - orfao_real: não acha em NENHUM catálogo do usuário (furo de extração de verdade).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'jan.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->impF2 = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'fev.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $cat = fn (string $cod, int $imp) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp,
        'cod_item' => $cod, 'descr_item' => "Item {$cod}", 'tipo_item' => '00', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $cat('AAA', $this->impF->id);   // catálogo da mesma importação
    $cat('BBB', $this->impF2->id);  // catálogo SÓ de outra importação

    $mk = fn (string $modelo, int $imp) => EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $imp,
        'numero' => random_int(1, 99999), 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0,
        'cancelada' => false, 'modelo' => $modelo, 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 10,
    ]);
    $item = fn (EfdNota $n, string $cod) => DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'numero_item' => 1, 'codigo_item' => $cod,
        'quantidade' => 1, 'valor_total' => 10, 'cfop' => 5102, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $item($mk('55', $this->impF->id), 'AAA'); // casa estrito
    $item($mk('55', $this->impF->id), 'BBB'); // órfão estrito (BBB só na impF2), órfão real NÃO
    $item($mk('55', $this->impF->id), 'CCC'); // órfão real (não existe em catálogo nenhum)
    $item($mk('55', $this->impF->id), '');    // sem código (codigo_item é NOT NULL → string vazia)
    $item($mk('57', $this->impF->id), 'ZZZ'); // CT-e → deve ser EXCLUÍDO
});

it('audita órfãos NF-e/NFS-e por importação e exclui CT-e', function () {
    $rows = collect(app(AuditarItensCatalogo::class)->audit($this->user->id));

    $r = $rows->first(fn ($x) => $x['origem'] === 'fiscal' && $x['modelo'] === '55');
    expect($r['itens'])->toBe(4);          // AAA, BBB, CCC, '' — CT-e fora
    expect($r['sem_codigo'])->toBe(1);     // ''
    expect($r['orfao_estrito'])->toBe(2);  // BBB, CCC
    expect($r['orfao_real'])->toBe(1);     // CCC

    expect($rows->first(fn ($x) => $x['modelo'] === '57'))->toBeNull(); // CT-e não entra
});
