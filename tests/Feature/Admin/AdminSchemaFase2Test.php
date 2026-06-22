<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('cria admin_action_logs e a coluna users.bloqueado_em', function () {
    expect(Schema::hasTable('admin_action_logs'))->toBeTrue();
    expect(Schema::hasColumns('admin_action_logs', [
        'id', 'admin_user_id', 'target_user_id', 'acao', 'motivo', 'detalhe', 'ip', 'created_at',
    ]))->toBeTrue();
    expect(Schema::hasColumn('users', 'bloqueado_em'))->toBeTrue();
});
