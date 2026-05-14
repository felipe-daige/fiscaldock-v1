<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('tem as colunas novas de cliente e parent', function () {
    expect(Schema::hasColumn('monitoramento_assinaturas', 'cliente_id'))->toBeTrue();
    expect(Schema::hasColumn('monitoramento_consultas', 'cliente_id'))->toBeTrue();
    expect(Schema::hasColumn('monitoramento_consultas', 'parent_consulta_id'))->toBeTrue();
});
