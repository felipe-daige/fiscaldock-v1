<?php

use App\Models\Alerta;
use App\Models\Cliente;
use App\Models\EfdImportacao;
use App\Models\User;
use App\Services\AlertaCentralService;

/**
 * O alerta gap_importacao deve medir por COMPETÊNCIA (período da EFD), não pela
 * data de upload (created_at). Janela = últimos 12 meses a partir de hoje (nudge
 * de obrigação recorrente).
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(AlertaCentralService::class);
});

afterEach(function () {
    $uid = $this->user->id;
    Alerta::where('user_id', $uid)->delete();
    EfdImportacao::where('user_id', $uid)->delete();
    Cliente::where('user_id', $uid)->forceDelete();
    $this->user->forceDelete();
});

it('conta gap por competência, ignorando a data de upload', function () {
    $cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '11222333000181',
        'nome' => 'X',
        'razao_social' => 'X',
        'is_empresa_propria' => true,
    ]);

    // Competência 20 meses atrás (fora da janela de 12m), mas upload HOJE.
    // Bug antigo (created_at) marcaria o mês atual como entregue => 11 faltantes.
    // Correto (competência): nenhum dos 12 meses recentes foi entregue => 12.
    EfdImportacao::create([
        'user_id' => $this->user->id,
        'cliente_id' => $cliente->id,
        'tipo_efd' => 'EFD PIS/COFINS',
        'status' => 'concluido',
        'periodo_inicio' => now()->subMonths(20)->startOfMonth()->toDateString(),
        'periodo_fim' => now()->subMonths(20)->endOfMonth()->toDateString(),
        'created_at' => now(),
    ]);

    $this->service->recalcular($this->user->id);

    $alerta = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'gap_importacao')
        ->first();

    expect($alerta)->not->toBeNull();
    expect($alerta->total_afetados)->toBe(12);
});

it('não acusa gap quando as 12 competências recentes foram entregues', function () {
    $cliente = Cliente::create([
        'user_id' => $this->user->id,
        'documento' => '11222333000181',
        'nome' => 'X',
        'razao_social' => 'X',
        'is_empresa_propria' => true,
    ]);

    for ($i = 0; $i <= 11; $i++) {
        EfdImportacao::create([
            'user_id' => $this->user->id,
            'cliente_id' => $cliente->id,
            'tipo_efd' => 'EFD PIS/COFINS',
            'status' => 'concluido',
            'periodo_inicio' => now()->subMonths($i)->startOfMonth()->toDateString(),
            'periodo_fim' => now()->subMonths($i)->endOfMonth()->toDateString(),
        ]);
    }

    $this->service->recalcular($this->user->id);

    $alerta = Alerta::where('user_id', $this->user->id)
        ->where('tipo', 'gap_importacao')
        ->first();

    expect($alerta)->toBeNull();
});
