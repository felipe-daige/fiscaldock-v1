<?php

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persiste um log com detalhe em array e relações', function () {
    $admin = User::factory()->create();
    $alvo = User::factory()->create();

    $log = AdminActionLog::create([
        'admin_user_id' => $admin->id,
        'target_user_id' => $alvo->id,
        'acao' => 'creditar',
        'motivo' => 'ajuste de cortesia',
        'detalhe' => ['valor' => 50, 'saldo_depois' => 60],
        'ip' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $fresh = AdminActionLog::find($log->id);
    expect($fresh->detalhe)->toBe(['valor' => 50, 'saldo_depois' => 60]);
    expect($fresh->admin->id)->toBe($admin->id);
    expect($fresh->alvo->id)->toBe($alvo->id);
});
