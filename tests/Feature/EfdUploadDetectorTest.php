<?php

use App\Models\EfdImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.webhook.importacao_efd_fiscal_url' => 'https://n8n.example.com/icms',
        'services.webhook.importacao_efd_contribuicoes_url' => 'https://n8n.example.com/contrib',
        'importacao.efd_manutencao.ativa' => false,
    ]);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);
});

function spedPisCofins(): string
{
    return "|0000|006|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|0|\r\n".
        "|A100|0|0|FORN|00||1|1|CHV|01022024|01022024|100|9|0|100|0.65|100|3|0|0|0|\r\n".
        "|9999|3|\r\n";
}

function spedIcmsIpi(): string
{
    return "|0000|016|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|A|0|0|\r\n".
        "|C100|0|0|FORN|55|00|1|123|CHAVE|01022024|01022024|100|9|0|100|0|100|0|0|0|0|0|0|0|0|0|0|0|0|0|\r\n".
        "|E100|01022024|29022024|\r\n".
        "|E110|0|0|0|0|0|0|0|0|0|0|0|\r\n".
        "|9999|5|\r\n";
}

it('rejeita arquivo nao-SPED com 422', function () {
    $file = UploadedFile::fake()->createWithContent('lixo.txt', 'isso nao eh sped, eh um texto aleatorio qualquer.');

    $response = $this->postJson('/app/importacao/efd/importar-txt', [
        'tipo_efd' => 'EFD PIS/COFINS',
        'arquivo' => $file,
    ]);

    $response->assertStatus(422);
    expect($response->json('success'))->toBeFalse();
    expect($response->json('error'))->toContain('SPED');
    expect(EfdImportacao::count())->toBe(0);
    Http::assertNothingSent();
});

it('corrige tipo_efd silenciosamente quando arquivo divergir', function () {
    Log::spy();

    $file = UploadedFile::fake()->createWithContent('contrib.txt', spedPisCofins());

    $response = $this->postJson('/app/importacao/efd/importar-txt', [
        'tipo_efd' => 'EFD ICMS/IPI', // usuario escolheu errado
        'arquivo' => $file,
    ]);

    $response->assertOk();

    $importacao = EfdImportacao::first();
    expect($importacao->tipo_efd)->toBe('EFD PIS/COFINS'); // sobrescrito pelo detectado

    // webhook correto disparado (contrib, nao fiscal)
    Http::assertSent(fn ($req) => str_contains($req->url(), 'contrib'));
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'icms'));

    // log de divergencia registrado
    Log::shouldHaveReceived('info')->withArgs(fn ($msg) => str_contains($msg, 'tipo_efd corrigido'))->atLeast()->once();
});

it('aceita upload quando tipo_efd bate com arquivo', function () {
    $file = UploadedFile::fake()->createWithContent('icms.txt', spedIcmsIpi());

    $response = $this->postJson('/app/importacao/efd/importar-txt', [
        'tipo_efd' => 'EFD ICMS/IPI',
        'arquivo' => $file,
    ]);

    $response->assertOk();
    expect(EfdImportacao::first()->tipo_efd)->toBe('EFD ICMS/IPI');
    Http::assertSent(fn ($req) => str_contains($req->url(), 'icms'));
});

it('aceita SPED de tipo desconhecido sem sobrescrever', function () {
    // SPED valido (0000+9999) mas sem discriminadores
    $sped = "|0000|999|0|01022024|29022024|EMPRESA|12345678000100|MG|123|3106200|0|0|\r\n".
        "|0001|0|\r\n".
        "|9999|2|\r\n";

    $file = UploadedFile::fake()->createWithContent('desconhecido.txt', $sped);

    $response = $this->postJson('/app/importacao/efd/importar-txt', [
        'tipo_efd' => 'EFD PIS/COFINS',
        'arquivo' => $file,
    ]);

    $response->assertOk();
    expect(EfdImportacao::first()->tipo_efd)->toBe('EFD PIS/COFINS');
});
