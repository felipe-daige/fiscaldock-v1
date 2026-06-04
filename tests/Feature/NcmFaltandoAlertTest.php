<?php

use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\User;
use App\Services\NotasFiscaisAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Alerta "NCM faltando": item de mercadoria/produto (tipo 00–06) que aparece em
 * notas mas está sem NCM no catálogo (0200) — risco fiscal de classificação. Item
 * que não exige NCM (07–10/99) não dispara. Reusa NotasFiscaisAlertService, então
 * flui pro AlertaCentral automaticamente.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = DB::table('clientes')->insertGetId([
        'user_id' => $this->user->id, 'razao_social' => 'EMPRESA', 'documento' => '00000000000100',
        'is_empresa_propria' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->imp = EfdImportacao::create(['user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'tipo_efd' => 'EFD ICMS/IPI', 'filename' => 'i.txt', 'status' => 'concluido', 'iniciado_em' => now()]);

    $this->cat = fn (string $cod, string $tipo, ?string $ncm) => DB::table('efd_catalogo_itens')->insert([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'cod_item' => $cod, 'descr_item' => "Item {$cod}", 'tipo_item' => $tipo, 'cod_ncm' => $ncm,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->nota = EfdNota::create([
        'user_id' => $this->user->id, 'cliente_id' => $this->cliente, 'importacao_id' => $this->imp->id,
        'numero' => 1, 'serie' => '1', 'data_emissao' => '2024-01-15', 'valor_desconto' => 0, 'cancelada' => false,
        'chave_acesso' => str_pad('A', 44, '0', STR_PAD_LEFT), 'modelo' => '55', 'tipo_operacao' => 'saida',
        'origem_arquivo' => 'fiscal', 'valor_total' => 100,
    ]);
    $this->item = fn (string $cod) => DB::table('efd_notas_itens')->insert([
        'efd_nota_id' => $this->nota->id, 'user_id' => $this->user->id, 'numero_item' => random_int(1, 999),
        'codigo_item' => $cod, 'quantidade' => 1, 'valor_total' => 50, 'cfop' => 5102,
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

function ncmAlerta(int $userId): ?array
{
    $res = app(NotasFiscaisAlertService::class)->detectar($userId, []);

    return collect($res['alertas'])->firstWhere('id', 'ncm_faltando');
}

it('mercadoria sem NCM em nota dispara o alerta ncm_faltando', function () {
    ($this->cat)('MERC', '00', null);
    ($this->item)('MERC');

    $alerta = ncmAlerta($this->user->id);

    expect($alerta)->not->toBeNull();
    expect($alerta['total_afetados'])->toBe(1);
    expect($alerta['severidade'])->toBe('media');
});

it('serviço sem NCM NÃO dispara o alerta (não exige NCM)', function () {
    ($this->cat)('SERV', '09', null);
    ($this->item)('SERV');

    expect(ncmAlerta($this->user->id))->toBeNull();
});

it('mercadoria COM NCM não dispara o alerta', function () {
    ($this->cat)('OK', '00', '12345678');
    ($this->item)('OK');

    expect(ncmAlerta($this->user->id))->toBeNull();
});
