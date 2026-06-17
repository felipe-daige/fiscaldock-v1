<?php

use App\Models\User;
use App\Services\Catalogo\AlertaCatalogoDescarteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('descarta e lista os descartados por tipo, isolado por usuário', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $svc = app(AlertaCatalogoDescarteService::class);

    $svc->descartar($u1->id, 'ncm_divergente', '008033');
    $svc->descartar($u1->id, 'sem_catalogo', '1401');
    $svc->descartar($u2->id, 'ncm_divergente', '999'); // outro usuário não vaza

    expect($svc->descartados($u1->id, 'ncm_divergente'))->toBe(['008033']);
    expect($svc->descartados($u1->id, 'sem_catalogo'))->toBe(['1401']);
    expect($svc->descartados($u2->id, 'ncm_divergente'))->toBe(['999']);
});

it('descartar é idempotente (mesmo item duas vezes não duplica)', function () {
    $u = User::factory()->create();
    $svc = app(AlertaCatalogoDescarteService::class);

    $svc->descartar($u->id, 'ncm_divergente', '008033');
    $svc->descartar($u->id, 'ncm_divergente', '008033');

    expect($svc->descartados($u->id, 'ncm_divergente'))->toBe(['008033']);
});

it('restaurar remove o descarte', function () {
    $u = User::factory()->create();
    $svc = app(AlertaCatalogoDescarteService::class);

    $svc->descartar($u->id, 'sem_catalogo', '1401');
    $svc->restaurar($u->id, 'sem_catalogo', '1401');

    expect($svc->descartados($u->id, 'sem_catalogo'))->toBe([]);
});

it('rejeita tipo fora do enum', function () {
    $u = User::factory()->create();
    $svc = app(AlertaCatalogoDescarteService::class);

    expect(fn () => $svc->descartar($u->id, 'inexistente', '1'))
        ->toThrow(InvalidArgumentException::class);
});
