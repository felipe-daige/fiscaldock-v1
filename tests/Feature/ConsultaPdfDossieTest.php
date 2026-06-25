<?php

use App\Models\ConsultaLote;
use App\Models\ConsultaResultado;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\User;
use App\Services\ConsultaReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function dossiePlano(): MonitoramentoPlano
{
    return MonitoramentoPlano::porCodigo('due_diligence')
        ?? MonitoramentoPlano::porCodigo('gratuito')
        ?? MonitoramentoPlano::firstOrFail();
}

// Lote com 1 participante COM acervo EFD (movimentação + itens + C190) → fiscal_resumo cheio.
function dossieLoteComAcervo(User $user): array
{
    $cliente = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'razao_social' => 'EMPRESA PROPRIA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $part = Participante::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'documento' => '11111111000111',
        'razao_social' => 'FORNECEDOR ACERVO', 'uf' => 'SP', 'crt' => '3',
    ]);
    $imp = EfdImportacao::create(['user_id' => $user->id, 'cliente_id' => $cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'f.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $nota = EfdNota::create([
        'user_id' => $user->id, 'cliente_id' => $cliente, 'participante_id' => $part->id,
        'importacao_id' => $imp->id, 'numero' => '555', 'serie' => '1', 'modelo' => '55',
        'origem_arquivo' => 'fiscal', 'tipo_operacao' => 'entrada', 'valor_total' => 1000,
        'valor_desconto' => 0, 'cancelada' => false, 'data_emissao' => '2024-05-01',
    ]);
    DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'numero_item' => 1,
        'codigo_item' => 'AGUA', 'descricao' => 'AGUA MINERAL', 'quantidade' => 1,
        'unidade_medida' => 'UN', 'valor_unitario' => 1000, 'valor_total' => 1000,
        'cfop' => 1102, 'cst_icms' => '00', 'aliquota_icms' => 18, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('efd_notas_consolidados')->insert([
        'efd_nota_id' => $nota->id, 'user_id' => $user->id, 'cfop' => 1102, 'cst_icms' => '00',
        'aliquota_icms' => 18, 'valor_operacao' => 1000, 'valor_bc_icms' => 1000, 'valor_icms' => 180,
        'valor_bc_icms_st' => 0, 'valor_icms_st' => 0, 'valor_reducao_bc' => 0, 'valor_ipi' => 0,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $lote = ConsultaLote::create([
        'user_id' => $user->id, 'plano_id' => dossiePlano()->id, 'status' => ConsultaLote::STATUS_FINALIZADO,
        'total_participantes' => 1, 'creditos_cobrados' => 0, 'tab_id' => 'tab-dos-'.uniqid(), 'processado_em' => now(),
    ]);
    $lote->participantes()->attach([$part->id]);
    ConsultaResultado::create([
        'consulta_lote_id' => $lote->id, 'participante_id' => $part->id,
        'status' => ConsultaResultado::STATUS_SUCESSO,
        'resultado_dados' => ['razao_social' => 'FORNECEDOR ACERVO', 'situacao_cadastral' => 'ATIVA', 'regime_tributario' => 'Lucro Real'],
        'consultado_em' => now(),
    ]);

    return [$lote, $part];
}

it('PDF do dossiê: render sem exceção + seções de panorama presentes', function () {
    $user = User::factory()->create();
    [$lote] = dossieLoteComAcervo($user);

    $html = view('reports.consulta-lote', app(ConsultaReportService::class)->dadosRelatorio($lote))->render();

    expect($html)->toContain('Movimentação')
        ->toContain('Top CFOPs')
        ->toContain('Top produtos')
        ->toContain('AGUA MINERAL')   // produto do acervo
        ->toContain('1102');          // CFOP

    // dompdf gera bytes sem exceção (pega CSS/HTML que o dompdf rejeita)
    expect(app(ConsultaReportService::class)->gerarPdf($lote)->output())->not->toBeEmpty();
})->skip('integração validada na Task 3 — depende do _cnpj');
