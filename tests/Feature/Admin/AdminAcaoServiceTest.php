<?php
// tests/Feature/Admin/AdminAcaoServiceTest.php
use App\Models\AdminActionLog;
use App\Models\User;
use App\Services\Admin\AdminAcaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->svc = app(AdminAcaoService::class);
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('creditar positivo adiciona saldo e registra audit', function () {
    $alvo = User::factory()->create(['credits' => 10]);

    $log = $this->svc->creditar($this->admin, $alvo, 50, 'cortesia');

    expect($alvo->fresh()->credits)->toBe(60);
    expect($log->acao)->toBe('creditar');
    expect($log->detalhe['valor'])->toBe(50);
    expect($log->detalhe['saldo_depois'])->toBe(60);
    expect(AdminActionLog::where('target_user_id', $alvo->id)->count())->toBe(1);
});

it('creditar negativo debita via deduct e registra acao debitar', function () {
    $alvo = User::factory()->create(['credits' => 100]);

    $log = $this->svc->creditar($this->admin, $alvo, -30, 'estorno manual');

    expect($alvo->fresh()->credits)->toBe(70);
    expect($log->acao)->toBe('debitar');
});

it('creditar com valor zero lança', function () {
    $alvo = User::factory()->create(['credits' => 10]);
    expect(fn () => $this->svc->creditar($this->admin, $alvo, 0, 'x'))
        ->toThrow(InvalidArgumentException::class);
});

it('debito acima do saldo lança e não muta', function () {
    $alvo = User::factory()->create(['credits' => 10]);
    expect(fn () => $this->svc->creditar($this->admin, $alvo, -50, 'x'))
        ->toThrow(RuntimeException::class);
    expect($alvo->fresh()->credits)->toBe(10);
});
