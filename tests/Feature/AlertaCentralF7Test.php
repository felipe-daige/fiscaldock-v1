<?php

use App\Models\Alerta;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\AlertaCentralService;
use App\Services\NotasFiscaisAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * F7 — Alertas centrais (persistidos). `detectarSituacaoIrregular` usava `!= 'ATIVA'`,
 * tratando o código '02' (= ATIVA na Receita) como irregular — falso, e inconsistente
 * com os outros detectores que usam `NOT IN ('02','ATIVA')`. `detectarFornecedores-
 * IrregularesComNotas` somava `efd_notas` cru → "valor em risco" dobrado (P1) + cancelada.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->impF = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    $this->impC = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $mkPart = fn (string $razao, string $doc, string $sit) => DB::table('participantes')->insertGetId([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'razao_social' => $razao, 'documento' => $doc,
        'situacao_cadastral' => $sit, 'origem_tipo' => 'MANUAL', 'created_at' => now(), 'updated_at' => now(),
    ]);
    // S1: código '02' = ATIVA na Receita → NÃO é irregular
    $this->s1 = $mkPart('FORNECEDOR ATIVO', '11111111000111', '02');
    // S2: SUSPENSA → irregular
    $this->s2 = $mkPart('FORNECEDOR SUSPENSO', '22222222000122', 'SUSPENSA');

    $mk = fn (int $part, array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'participante_id' => $part,
        'importacao_id' => $this->impF->id, 'numero' => random_int(1, 99999), 'serie' => '1',
        'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false, 'modelo' => '55',
        'tipo_operacao' => 'entrada', 'valor_total' => 1000,
    ], $a));

    $mk($this->s1, ['chave_acesso' => str_pad('1', 44, '0', STR_PAD_LEFT), 'origem_arquivo' => 'fiscal']);

    // S2: a MESMA NF-e nas 2 origens (mesma chave) + 1 cancelada
    $chave = str_pad('2', 44, '0', STR_PAD_LEFT);
    $mk($this->s2, ['chave_acesso' => $chave, 'origem_arquivo' => 'fiscal', 'valor_total' => 1000]);
    $mk($this->s2, ['chave_acesso' => $chave, 'origem_arquivo' => 'contribuicoes', 'importacao_id' => $this->impC->id, 'valor_total' => 1000]);
    $mk($this->s2, ['chave_acesso' => str_pad('9', 44, '0', STR_PAD_LEFT), 'origem_arquivo' => 'fiscal', 'valor_total' => 500, 'cancelada' => true]);

    app(AlertaCentralService::class)->recalcular($this->user->id);
});

it('situacao irregular trata código 02 como ATIVA (não alerta) e flagra SUSPENSA', function () {
    $irregulares = Alerta::where('user_id', $this->user->id)->where('tipo', 'situacao_irregular')->get();

    // Só S2 (SUSPENSA). S1 ('02' = ATIVA) NÃO pode gerar alerta.
    expect($irregulares)->toHaveCount(1);
    expect($irregulares->first()->participante_id)->toBe($this->s2);
});

it('fornecedor irregular não dobra valor em risco nem conta cancelada (P1/P4)', function () {
    $alerta = Alerta::where('user_id', $this->user->id)->where('tipo', 'fornecedor_irregular')->first();

    expect($alerta)->not->toBeNull();
    // 1 NF-e (deduplicada das 2 origens), R$ 1.000 — não 3 notas / R$ 2.500.
    expect($alerta->total_afetados)->toBe(1);
    expect((float) $alerta->detalhes['valor_em_risco'])->toBe(1000.0);
});
