<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\NotasFiscaisAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * F7 — Alertas de notas. Os detectores partiam de uma base crua (sem dedup de
 * origem P1, sem excluir cancelada P4) e liam só C170 (P2) → falsos-positivos:
 *  - "duplicadas": a mesma NF-e nas 2 origens contava como duplicata (medido: 87% falso).
 *  - "sem itens": NF-e fiscal do perfil B (detalhada por C190, não C170) sempre disparava (medido: 98,6% falso).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->part = DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'PARCEIRO', 'documento' => '11111111000111',
        'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->impC = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $this->mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'participante_id' => $this->part,
        'importacao_id' => $this->impF->id,
        'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'modelo' => '55', 'tipo_operacao' => 'saida', 'valor_total' => 1000,
    ], $a));
    $this->c190 = fn (EfdNota $n) => DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $n->id, 'user_id' => $this->user->id, 'cfop' => 5102, 'cst_icms' => '00', 'aliquota_icms' => 18,
        'valor_operacao' => $n->valor_total, 'valor_bc_icms' => 0, 'valor_icms' => 100, 'valor_bc_icms_st' => 0,
        'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $k = fn (string $s) => str_pad($s, 44, '0', STR_PAD_LEFT);

    // A: mesma NF-e (nº 100) nas 2 origens (mesma chave) → NÃO é duplicata.
    // A fiscal tem C190 (não é "sem itens"); A contrib é removida pelo dedup (gêmea fiscal).
    $aF = ($this->mk)(['numero' => 100, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('A')]);
    ($this->c190)($aF);
    ($this->mk)(['numero' => 100, 'origem_arquivo' => 'contribuicoes', 'chave_acesso' => $k('A'), 'importacao_id' => $this->impC->id]);

    // D: duplicata REAL — nº 400, mesma série/participante/modelo, 2 chaves distintas, mesma origem fiscal
    $d1 = ($this->mk)(['numero' => 400, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('D1')]);
    $d2 = ($this->mk)(['numero' => 400, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('D2')]);
    ($this->c190)($d1);
    ($this->c190)($d2);

    // B: NF-e fiscal com C190 e SEM C170 (perfil B) → NÃO é "sem itens"
    $b = ($this->mk)(['numero' => 200, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('B')]);
    ($this->c190)($b);

    // C: NF-e sem item E sem C190 → "sem itens" REAL
    ($this->mk)(['numero' => 300, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('C')]);

    // E: cancelada com valor 0 → NÃO conta em "valor zerado" (P4)
    ($this->mk)(['numero' => 500, 'origem_arquivo' => 'fiscal', 'chave_acesso' => $k('E'), 'valor_total' => 0, 'cancelada' => true]);

    $this->svc = new NotasFiscaisAlertService;
});

function f7alerta(array $res, string $id): ?array
{
    return collect($res['alertas'])->firstWhere('id', $id);
}

it('duplicadas não conta a mesma NF-e das 2 origens (P1)', function () {
    $res = $this->svc->detectar($this->user->id, []);
    $a = f7alerta($res, 'notas_duplicadas');

    // Só o grupo D (duplicata real). A (gêmea de origem) NÃO conta.
    expect($a)->not->toBeNull();
    expect($a['total_afetados'])->toBe(1);
});

it('sem itens ignora NF-e que tem C190 consolidado (P2 perfil B)', function () {
    $res = $this->svc->detectar($this->user->id, []);
    $a = f7alerta($res, 'notas_sem_itens');

    // Só C (sem item e sem C190). B tem C190 → não é "sem itens".
    expect($a)->not->toBeNull();
    expect($a['total_afetados'])->toBe(1);
});

it('valor zerado exclui notas canceladas (P4)', function () {
    $res = $this->svc->detectar($this->user->id, []);
    $a = f7alerta($res, 'notas_valor_zerado');

    // E (valor 0) é cancelada → não deve gerar alerta.
    expect($a)->toBeNull();
});
