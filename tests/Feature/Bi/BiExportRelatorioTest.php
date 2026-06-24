<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\BiExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function semearBiExport(): array
{
    $user = User::factory()->trialAtivo()->create();
    $cli = DB::table('clientes')->insertGetId([
        'user_id' => $user->id, 'documento' => '00000000000191', 'razao_social' => 'Empresa Teste',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $imp = EfdImportacao::create([
        'user_id' => $user->id, 'cliente_id' => $cli, 'tipo_efd' => 'EFD ICMS/IPI',
        'filename' => 'x.txt', 'status' => 'concluido', 'iniciado_em' => now()->subMinutes(2),
    ]);
    $mk = fn (array $a) => EfdNota::create(array_merge([
        'user_id' => $user->id, 'cliente_id' => $cli, 'importacao_id' => $imp->id,
        'numero' => random_int(1, 99999), 'serie' => '1', 'modelo' => '55',
        'valor_desconto' => 0, 'cancelada' => false, 'origem_arquivo' => 'fiscal',
    ], $a));
    $mk(['chave_acesso' => str_pad('A', 44, '0'), 'tipo_operacao' => 'saida', 'data_emissao' => '2024-01-10', 'valor_total' => 1000]);
    $mk(['chave_acesso' => str_pad('B', 44, '0'), 'tipo_operacao' => 'saida', 'data_emissao' => '2024-02-10', 'valor_total' => 500]);
    $mk(['chave_acesso' => str_pad('C', 44, '0'), 'tipo_operacao' => 'entrada', 'data_emissao' => '2024-01-10', 'valor_total' => 300]);

    return [$user->id, $cli];
}

it('relatorioCompleto traz kpis, cobertura e as 4 secoes', function () {
    [$uid] = semearBiExport();
    $rel = app(BiExportService::class)->relatorioCompleto($uid, null, null, null);

    expect($rel)->toHaveKeys(['periodo', 'kpis', 'cobertura', 'secoes']);
    expect($rel['kpis'])->toHaveKeys(['faturamento', 'aquisicoes', 'tributos', 'saldo_liquido', 'total_notas', 'aliquota_media']);
    expect(array_keys($rel['secoes']))->toBe(['faturamento', 'tributos', 'apuracao-notas', 'cfop']);
    foreach ($rel['secoes'] as $sec) {
        expect($sec)->toHaveKeys(['titulo', 'colunas', 'linhas']);
    }
});

it('barChartItens calcula pct relativo ao maximo da serie', function () {
    $svc = app(BiExportService::class);
    $linhas = [['jan/24', '1.000,00'], ['fev/24', '500,00']];
    $itens = $svc->barChartItens($linhas, 0, 1, '#2563eb');

    expect($itens[0]['pct'])->toBe(100); // 1000 = máximo
    expect($itens[1]['pct'])->toBe(50);  // 500/1000
    expect($itens[0]['label'])->toBe('jan/24');
    expect($itens[0]['valor'])->toBe('1.000,00');
    expect($itens[0]['hex'])->toBe('#2563eb');
});

it('barChartItens com serie toda zero retorna pct 0 (sem divisao por zero)', function () {
    $itens = app(BiExportService::class)->barChartItens([['jan/24', '0,00']], 0, 1, '#2563eb');
    expect($itens[0]['pct'])->toBe(0);
});

it('relatorioCompleto respeita o filtro de periodo', function () {
    [$uid] = semearBiExport();
    $rel = app(BiExportService::class)->relatorioCompleto($uid, '2024-01-01', '2024-01-31', null);
    // faturamento mensal só de jan: 1 linha
    expect(count($rel['secoes']['faturamento']['linhas']))->toBe(1);
});

use App\Support\Reports\XlsxReport;

it('GET /app/bi/exportar-xlsx baixa um xlsx', function () {
    if (! XlsxReport::disponivel()) {
        $this->markTestSkipped('openspout indisponível neste ambiente');
    }
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-xlsx');

    $resp->assertOk();
    expect($resp->headers->get('content-type'))
        ->toContain('spreadsheetml.sheet');
});

it('GET /app/bi/exportar-pdf baixa um pdf', function () {
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-pdf');

    $resp->assertOk();
    expect($resp->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($resp->getContent(), 0, 4))->toBe('%PDF');
});

it('GET /app/bi/exportar-csv-zip baixa um zip', function () {
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-csv-zip');

    $resp->assertOk();
    expect($resp->headers->get('content-type'))->toContain('zip');
    $resp->assertDownload('bi-fiscal-'.now()->format('Ymd').'.csv.zip');
});

it('exportar-pdf aceita o param meses (datas computadas server-side)', function () {
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-pdf?meses=3');

    $resp->assertOk();
    expect(substr($resp->getContent(), 0, 4))->toBe('%PDF');
});

it('seta cookie bi_download com o token quando download_token é informado (spinner)', function () {
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-pdf?download_token=abc123');

    $resp->assertOk();
    // O valor vai criptografado (EncryptCookies); o frontend detecta por PRESENÇA do
    // cookie, não pelo valor. Aqui basta garantir que o cookie é emitido e legível por JS.
    $cookie = collect($resp->headers->getCookies())->first(fn ($c) => $c->getName() === 'bi_download');
    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeFalse(); // JS precisa ler (presença) pra esconder o spinner
    expect($cookie->getValue())->not->toBe(''); // tem algum valor (token criptografado)
});

it('NÃO seta cookie bi_download sem download_token', function () {
    [$uid] = semearBiExport();

    $resp = $this->actingAs(User::find($uid))->get('/app/bi/exportar-xlsx');

    $resp->assertOk();
    expect(collect($resp->headers->getCookies())->first(fn ($c) => $c->getName() === 'bi_download'))->toBeNull();
});
