<?php

use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    config()->set('services.webhook.importacao_efd_contribuicoes_url', 'http://n8n.test/hook');
});

// CNPJ do 0000 = 97551165000193
function efdSpedCap(): string
{
    return "|0000|006|0|||01062024|30062024|HIDRATOP|97551165000193|MS|5003702||00|9|\r\n".
        "|A100|0|0|F|00||1|1|CHV|01062024|01062024|1000.00|9|0|1000.00|6.5|1000.00|30|0|0|0|\r\n".
        "|9999|3|\r\n";
}

function efdCapPropria(User $user): void
{
    Cliente::create([
        'user_id' => $user->id, 'documento' => '10000000000191', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Propria', 'is_empresa_propria' => true, 'ativo' => true,
    ]);
}

function efdCapNormal(User $user, string $doc): void
{
    Cliente::create([
        'user_id' => $user->id, 'documento' => $doc, 'tipo_pessoa' => 'PJ',
        'razao_social' => 'Cliente '.$doc, 'is_empresa_propria' => false, 'ativo' => true,
    ]);
}

function uploadEfdCap(object $test, User $user)
{
    return $test->actingAs($user)->postJson('/app/importacao/efd/importar-txt', [
        'arquivo' => UploadedFile::fake()->createWithContent('sped.txt', efdSpedCap()),
        'tipo_efd' => 'EFD PIS/COFINS',
    ]);
}

it('EFD vincula ao cliente existente do mesmo CNPJ sem criar novo', function () {
    $user = User::factory()->create();
    efdCapPropria($user);
    $cli = Cliente::create([
        'user_id' => $user->id, 'documento' => '97551165000193', 'tipo_pessoa' => 'PJ',
        'razao_social' => 'HIDRATOP', 'is_empresa_propria' => false, 'ativo' => true,
    ]); // cap cheio (própria + 1), mas vincular não cria novo

    uploadEfdCap($this, $user)->assertOk()->assertJson(['success' => true]);

    $imp = EfdImportacao::where('user_id', $user->id)->first();
    expect($imp->cliente_id)->toBe($cli->id);
    expect(Cliente::where('user_id', $user->id)->where('is_empresa_propria', false)->count())->toBe(1);
});

it('EFD de CNPJ novo auto-cria cliente quando cabe no cap', function () {
    $user = User::factory()->create();
    efdCapPropria($user); // room = 1

    uploadEfdCap($this, $user)->assertOk();

    $cli = Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->first();
    expect($cli)->not->toBeNull();
    expect($cli->is_empresa_propria)->toBeFalse();
    expect(EfdImportacao::where('user_id', $user->id)->first()->cliente_id)->toBe($cli->id);
});

it('EFD de CNPJ novo é bloqueado quando o cap está cheio', function () {
    $user = User::factory()->create();
    efdCapPropria($user);
    efdCapNormal($user, '22222222000191'); // usa o +1 → cap cheio

    uploadEfdCap($this, $user)->assertStatus(403);

    expect(Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->exists())->toBeFalse();
    expect(EfdImportacao::where('user_id', $user->id)->exists())->toBeFalse();
});

it('trial ativo importa EFD de CNPJ novo sem cap', function () {
    $user = User::factory()->trialAtivo()->create();
    efdCapPropria($user);
    efdCapNormal($user, '22222222000191');
    efdCapNormal($user, '33333333000191');

    uploadEfdCap($this, $user)->assertOk();

    expect(Cliente::where('user_id', $user->id)->where('documento', '97551165000193')->exists())->toBeTrue();
});
