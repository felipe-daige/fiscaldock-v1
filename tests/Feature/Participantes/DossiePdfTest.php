<?php

use App\Models\Cliente;
use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarNotaEfd(User $u, Participante $p, string $tipo, ?string $data, float $valor, bool $cancelada = false): EfdNota
{
    $cliente = Cliente::firstOrCreate(['user_id' => $u->id, 'documento' => '00000000000191'], ['razao_social' => 'Empresa Teste']);
    $imp = EfdImportacao::firstOrCreate(['user_id' => $u->id, 'tipo_efd' => 'EFD ICMS/IPI'], []);

    return EfdNota::create([
        'user_id' => $u->id, 'cliente_id' => $cliente->id, 'importacao_id' => $imp->id,
        'participante_id' => $p->id, 'modelo' => '55', 'numero' => (string) random_int(1, 9999999),
        'tipo_operacao' => $tipo, 'valor_total' => $valor, 'data_emissao' => $data, 'cancelada' => $cancelada,
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->trialAtivo()->create();
    $this->p = Participante::create(['user_id' => $this->user->id, 'documento' => '07863768000138', 'razao_social' => 'ACME DOSSIE LTDA', 'uf' => 'SP', 'crt' => '3']);
    $plano = MonitoramentoPlano::ativos()->first() ?? MonitoramentoPlano::create([
        'nome' => 'Gratuito', 'codigo' => 'gratuito', 'ativo' => true, 'creditos_por_consulta' => 0, 'consultas_incluidas' => [], 'etapas' => [],
    ]);
    $lote = ConsultaLote::create([
        'user_id' => $this->user->id, 'plano_id' => $plano->id,
        'status' => ConsultaLote::STATUS_FINALIZADO, 'total_participantes' => 1, 'creditos_cobrados' => 0,
        'tab_id' => 'tab-'.uniqid(), 'processado_em' => now(),
    ]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $this->p->id, 'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'ACME DOSSIE LTDA', 'situacao_cadastral' => 'ATIVA', 'cnd_federal' => ['status' => 'NEGATIVA']],
        'consultado_em' => now(),
    ]);
    criarNotaEfd($this->user, $this->p, 'saida', '2026-01-10', 500);
});

it('baixa o dossie em pdf do dono', function () {
    $resp = $this->actingAs($this->user)->get("/app/participante/{$this->p->id}/dossie");
    $resp->assertOk();
    $resp->assertHeader('content-type', 'application/pdf');
});

it('bloqueia dossie de participante de outro usuario', function () {
    $outro = User::factory()->trialAtivo()->create();
    $this->actingAs($outro)->get("/app/participante/{$this->p->id}/dossie")->assertNotFound();
});

it('a view do dossie renderiza secoes de consulta e movimentacao', function () {
    $dados = app(\App\Services\Participantes\DossieParticipanteBuilder::class)->montar($this->p);
    $html = view('reports.dossie.participante', $dados)->render();
    expect($html)->toContain('ACME DOSSIE LTDA')
        ->and($html)->toContain('Movimentações')
        ->and($html)->toContain('Regularidade');
});
