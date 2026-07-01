<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\Bi\BiDossieAnexoService;
use App\Services\BiExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function semearBiComDossie(): array
{
    $user = User::factory()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa BI',
        'is_empresa_propria' => true, 'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $p = Participante::create(['user_id' => $user->id, 'cliente_id' => $cli, 'documento' => '11111111000111', 'razao_social' => 'ACME ANEXO LTDA', 'origem_tipo' => 'MANUAL']);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now()]);
    EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'participante_id' => $p->id, 'importacao_id' => $imp->id,
        'numero' => 1, 'serie' => '1', 'modelo' => '55', 'chave_acesso' => str_pad('1', 44, '0'),
        'valor_total' => 1000, 'valor_desconto' => 0, 'cancelada' => false, 'origem_arquivo' => 'fiscal',
        'tipo_operacao' => 'saida', 'data_emissao' => '2026-03-10',
    ]);

    return [$user->id, $cli];
}

it('bi-executivo mostra a seção Dossiês quando $dossies é passado', function () {
    [$uid] = semearBiComDossie();
    $rel = app(BiExportService::class)->relatorioCompleto($uid, null, null, null);
    $dossies = app(BiDossieAnexoService::class)->montar($uid, null, '20');

    $html = view('reports.bi-executivo', ['relatorio' => $rel, 'dossies' => $dossies])->render();

    expect($html)->toContain('Dossiês')
        ->and($html)->toContain('ACME ANEXO LTDA')
        ->and($html)->toContain('Empresa BI');
});

it('bi-executivo sem $dossies não mostra a seção', function () {
    [$uid] = semearBiComDossie();
    $rel = app(BiExportService::class)->relatorioCompleto($uid, null, null, null);

    $html = view('reports.bi-executivo', ['relatorio' => $rel])->render();

    expect($html)->not->toContain('Dossiês');
});
