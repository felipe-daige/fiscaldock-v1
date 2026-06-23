<?php

namespace App\Services\Admin;

use App\Services\PricingCatalogService;
use Illuminate\Support\Facades\DB;

/**
 * Camada de override dos parâmetros comerciais globais (CFO §6.1).
 *
 * A tabela `comercial_parametros` começa VAZIA → `valor()` cai no default hardcoded do registro
 * abaixo. Um admin que grava um override passa a vencer. **Garante zero mudança de preço em prod
 * até alguém editar.** Escopo MVP: peg global (credit_unit_price/minimum_deposit) + preços por
 * plano (preco_validacao/licitacao/compliance). Os defaults espelham as constantes/valores atuais —
 * o teste anti-drift trava qualquer divergência.
 */
class ComercialParametroService
{
    /**
     * Registro canônico do que é editável. `default` = valor atual (fallback). `tipo` = cast.
     *
     * @var array<string, array{rotulo: string, tipo: string, default: int|float, dica?: string}>
     */
    public const DEFAULTS = [
        'credit_unit_price' => [
            'rotulo' => 'Preço por crédito (R$)',
            'tipo' => 'float',
            'default' => PricingCatalogService::CREDIT_UNIT_PRICE, // 0.20
            'dica' => 'Base de toda a precificação. 1 crédito = este valor.',
        ],
        'minimum_deposit' => [
            'rotulo' => 'Depósito mínimo (R$)',
            'tipo' => 'float',
            'default' => PricingCatalogService::MINIMUM_DEPOSIT, // 100.00
            'dica' => 'Valor mínimo de recarga avulsa.',
        ],
        'preco_validacao' => [
            'rotulo' => 'Preço Validação (créditos)',
            'tipo' => 'int',
            'default' => 15,
        ],
        'preco_licitacao' => [
            'rotulo' => 'Preço Licitação (créditos)',
            'tipo' => 'int',
            'default' => 50,
        ],
        'preco_compliance' => [
            'rotulo' => 'Preço Compliance (créditos)',
            'tipo' => 'int',
            'default' => 100,
        ],
    ];

    /** @var array<string, string>|null cache por request dos overrides persistidos */
    private ?array $cache = null;

    /**
     * Valor efetivo do parâmetro: override do banco se existir, senão o default do registro.
     */
    public function valor(string $chave, mixed $default = null): mixed
    {
        $registro = self::DEFAULTS[$chave] ?? null;
        $fallback = $default ?? $registro['default'] ?? null;
        $tipo = $registro['tipo'] ?? 'string';

        $override = $this->overrides()[$chave] ?? null;

        if ($override === null) {
            return $fallback;
        }

        return $this->cast($override, $tipo);
    }

    /**
     * Grava (upsert) um override. Recusa chave fora do registro.
     */
    public function definir(string $chave, mixed $valor, ?int $userId): void
    {
        if (! array_key_exists($chave, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Parâmetro comercial desconhecido: {$chave}");
        }

        $tipo = self::DEFAULTS[$chave]['tipo'];
        $normalizado = (string) $this->cast($valor, $tipo);

        DB::table('comercial_parametros')->updateOrInsert(
            ['chave' => $chave],
            ['valor' => $normalizado, 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now()],
        );

        $this->cache = null;
    }

    public function resetar(string $chave): void
    {
        DB::table('comercial_parametros')->where('chave', $chave)->delete();
        $this->cache = null;
    }

    /**
     * Tabela para o painel: por parâmetro, default, override (ou null) e valor efetivo.
     *
     * @return array<string, array{rotulo: string, tipo: string, dica: ?string, default: mixed, override: mixed, efetivo: mixed}>
     */
    public function efetivos(): array
    {
        $overrides = $this->overrides();
        $saida = [];

        foreach (self::DEFAULTS as $chave => $registro) {
            $temOverride = array_key_exists($chave, $overrides);

            $saida[$chave] = [
                'rotulo' => $registro['rotulo'],
                'tipo' => $registro['tipo'],
                'dica' => $registro['dica'] ?? null,
                'default' => $registro['default'],
                'override' => $temOverride ? $this->cast($overrides[$chave], $registro['tipo']) : null,
                'efetivo' => $this->valor($chave),
            ];
        }

        return $saida;
    }

    /** @return array<string, string> */
    private function overrides(): array
    {
        if ($this->cache === null) {
            $this->cache = DB::table('comercial_parametros')->pluck('valor', 'chave')->all();
        }

        return $this->cache;
    }

    private function cast(mixed $valor, string $tipo): mixed
    {
        return match ($tipo) {
            'int' => (int) $valor,
            'float' => round((float) $valor, 4),
            default => (string) $valor,
        };
    }
}
