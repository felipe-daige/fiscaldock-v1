<?php

use App\Models\CertificadoDigital;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// Helpers guardados: gerarPfx/certUploadFile podem já vir do CertificadoDigitalServiceTest
// quando os dois arquivos rodam juntos.
if (! function_exists('gerarPfx')) {
    function gerarPfx(string $senha, string $cnpj = '00000000000191', int $dias = 365): string
    {
        $pkey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => "EMPRESA TESTE:{$cnpj}"], $pkey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $pkey, $dias, ['digest_alg' => 'sha256']);
        $pfx = '';
        openssl_pkcs12_export($x509, $pfx, $pkey, $senha);

        return $pfx;
    }
}

if (! function_exists('certUploadFile')) {
    function certUploadFile(string $pfx): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->createWithContent('cert.pfx', $pfx);
    }
}

function cadastroEmpresa(User $u): Cliente
{
    return Cliente::create([
        'user_id' => $u->id, 'is_empresa_propria' => true, 'tipo_pessoa' => 'PJ',
        'documento' => '00000000000191', 'razao_social' => 'Empresa Propria',
    ]);
}

it('POST certificado válido cadastra e redireciona com sucesso', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    cadastroEmpresa($user);

    actingAs($user)->post('/app/minha-empresa/certificado', [
        'certificado' => certUploadFile(gerarPfx('senha1')), 'senha' => 'senha1',
    ])->assertRedirect();

    expect(CertificadoDigital::where('user_id', $user->id)->exists())->toBeTrue();
});

it('POST com senha errada volta com erro e não grava', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    cadastroEmpresa($user);

    actingAs($user)->from('/app/minha-empresa')->post('/app/minha-empresa/certificado', [
        'certificado' => certUploadFile(gerarPfx('certa')), 'senha' => 'errada',
    ])->assertRedirect('/app/minha-empresa')->assertSessionHasErrors('certificado');

    expect(CertificadoDigital::where('user_id', $user->id)->exists())->toBeFalse();
});

it('DELETE remove o certificado', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $empresa = cadastroEmpresa($user);
    app(\App\Services\Clearance\CertificadoDigitalService::class)
        ->validarEArmazenar(certUploadFile(gerarPfx('s')), 's', $empresa);

    actingAs($user)->delete('/app/minha-empresa/certificado')->assertRedirect();
    expect(CertificadoDigital::where('user_id', $user->id)->exists())->toBeFalse();
});

it('Empresa mostra form de upload quando não há certificado', function () {
    $user = User::factory()->create();
    cadastroEmpresa($user);

    actingAs($user)->get('/app/minha-empresa')
        ->assertOk()
        ->assertSee('Certificado Digital', false)
        ->assertSee('name="certificado"', false)
        ->assertSee('name="senha"', false);
});

it('Empresa mostra status quando há certificado', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $empresa = cadastroEmpresa($user);
    app(\App\Services\Clearance\CertificadoDigitalService::class)
        ->validarEArmazenar(certUploadFile(gerarPfx('s')), 's', $empresa);

    actingAs($user)->get('/app/minha-empresa')
        ->assertOk()
        ->assertSee('Válido até', false)
        ->assertSee('Remover', false);
});
