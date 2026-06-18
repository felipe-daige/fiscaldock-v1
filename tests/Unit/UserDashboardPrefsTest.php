<?php
// tests/Unit/UserDashboardPrefsTest.php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('devolve o default quando dashboard_prefs e null', function () {
    $user = User::factory()->create(['dashboard_prefs' => null]);

    $prefs = $user->dashboardPrefs();

    expect($prefs['cards']['tendencia']['visivel'])->toBeTrue()
        ->and($prefs['cards']['atividade']['visivel'])->toBeTrue()
        ->and($prefs['cards']['risco']['visivel'])->toBeTrue()
        ->and($prefs['cards']['fornecedores']['visivel'])->toBeTrue()
        ->and($prefs['atalhos_fixos'])->toBeArray();
});

it('mescla prefs salvas sobre o default sem perder chaves', function () {
    $user = User::factory()->create([
        'dashboard_prefs' => ['cards' => ['tendencia' => ['visivel' => false, 'ordem' => 0]]],
    ]);

    $prefs = $user->dashboardPrefs();

    // chave salva sobrescreve
    expect($prefs['cards']['tendencia']['visivel'])->toBeFalse()
        // chave não-salva mantém default
        ->and($prefs['cards']['triagem']['visivel'])->toBeTrue();
});
