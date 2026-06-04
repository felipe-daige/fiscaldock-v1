<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\ValidacaoContabilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * F6 — Validação Contábil / Score. As agregações de status (getEstatisticas,
 * getKpisStatusReceita, queryNotasUnificadasComSituacao) deduplicavam EFD×XML
 * mas NÃO fiscal×contribuicoes (P1) e não excluíam cancelada (P4) → total e
 * contadores de situação dobravam quando a mesma NF-e existe nas 2 origens.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA TESTE',
        'documento' => '00000000000100', 'is_empresa_propria' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $impFiscal = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);
    $impContrib = EfdImportacao::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente,
        'tipo_efd' => 'EFD PIS/COFINS', 'filename' => 'p.txt', 'status' => 'concluido', 'iniciado_em' => now(),
    ]);

    $this->mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'numero' => random_int(1, 99999),
        'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
    ], $a));

    $a = str_pad('A', 44, '0', STR_PAD_LEFT);
    $b = str_pad('B', 44, '0', STR_PAD_LEFT);
    $c = str_pad('C', 44, '0', STR_PAD_LEFT);

    // A: NF-e nas 2 origens; validação conforme/AUTORIZADA na FISCAL (origem canônica)
    $aFiscal = ($this->mk)(['importacao_id' => $impFiscal->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 1000]);
    $aFiscal->update(['validacao' => ['situacao' => 'AUTORIZADA', 'classificacao' => 'conforme', 'score_total' => 90, 'consultado_em' => '2024-02-01']]);
    ($this->mk)(['importacao_id' => $impContrib->id, 'chave_acesso' => $a, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 1000]);

    // B: NF-e nas 2 origens, sem validação
    ($this->mk)(['importacao_id' => $impFiscal->id, 'chave_acesso' => $b, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 800]);
    ($this->mk)(['importacao_id' => $impContrib->id, 'chave_acesso' => $b, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 800]);

    // NFS-e: só contribuicoes, sem gêmea fiscal
    ($this->mk)(['importacao_id' => $impContrib->id, 'chave_acesso' => null, 'modelo' => '00', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 500]);

    // E: cancelada com validação crítica — não pode entrar em nenhuma contagem (P4)
    $e = ($this->mk)(['importacao_id' => $impFiscal->id, 'chave_acesso' => str_pad('E', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 0, 'cancelada' => true]);
    $e->update(['validacao' => ['situacao' => 'CANCELADA', 'classificacao' => 'critico', 'score_total' => 10, 'consultado_em' => '2024-02-02']]);

    // C: NF-e nas 2 origens, situação bloqueante DENEGADA em AMBAS — não pode listar 2×
    $cFiscal = ($this->mk)(['importacao_id' => $impFiscal->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'fiscal', 'valor_total' => 300]);
    $cFiscal->update(['validacao' => ['situacao' => 'DENEGADA', 'classificacao' => 'irregular', 'score_total' => 30, 'consultado_em' => '2024-02-03']]);
    $cContrib = ($this->mk)(['importacao_id' => $impContrib->id, 'chave_acesso' => $c, 'modelo' => '55', 'tipo_operacao' => 'saida', 'origem_arquivo' => 'contribuicoes', 'valor_total' => 300]);
    $cContrib->update(['validacao' => ['situacao' => 'DENEGADA', 'classificacao' => 'irregular', 'score_total' => 30, 'consultado_em' => '2024-02-03']]);

    $this->svc = new ValidacaoContabilService;
});

it('getEstatisticas não dobra origem e exclui cancelada (P1/P4)', function () {
    $e = $this->svc->getEstatisticas($this->user->id);

    // A, B, NFS-e, C = 4 notas (dups colapsadas, E cancelada fora)
    expect($e['total_notas'])->toBe(4);
    expect($e['conforme'])->toBe(1);    // A
    expect($e['irregular'])->toBe(1);   // C (uma vez, não duas)
    expect($e['critico'])->toBe(0);     // E cancelada fora
});

it('getKpisStatusReceita não dobra origem e exclui cancelada (P1/P4)', function () {
    $k = $this->svc->getKpisStatusReceita($this->user->id);

    expect($k['total'])->toBe(4);        // A, B, NFS-e, C
    expect($k['autorizadas'])->toBe(1);  // A
    expect($k['denegadas'])->toBe(1);    // C uma vez
    expect($k['canceladas'])->toBe(0);   // E (nota cancelada) fora
});

it('getNotasComSituacaoBloqueante não duplica a mesma NF-e das 2 origens (P1)', function () {
    $bloq = $this->svc->getNotasComSituacaoBloqueante($this->user->id, 10);

    // C está DENEGADA em fiscal E contribuicoes → deve aparecer 1×
    expect(collect($bloq)->where('chave', str_pad('C', 44, '0', STR_PAD_LEFT)))->toHaveCount(1);
});

it('listagem de seleção do clearance não duplica NF-e nem oferece cancelada (P1/P4)', function () {
    $r = actingAs($this->user)->getJson('/app/clearance/notas/todos-ids')->assertOk()->json();

    // A, B, C (modelo 55, deduplicados); NFS-e (modelo 00) e E (cancelada) fora.
    // Garante que a validação só pode cair na linha fiscal — base do dedup das KPIs.
    expect($r['total'])->toBe(3);
});
