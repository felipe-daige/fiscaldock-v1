<?php

namespace App\Models;

use App\Support\Monitoramento\PlanoCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MonitoramentoPlano extends Model
{
    use HasFactory;

    protected $table = 'monitoramento_planos';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'consultas_incluidas',
        'etapas',
        'custo_creditos',
        'is_gratuito',
        'is_active',
        'ordem',
    ];

    protected $casts = [
        'consultas_incluidas' => 'array',
        'etapas' => 'array',
        'custo_creditos' => 'integer',
        'is_gratuito' => 'boolean',
        'is_active' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Assinaturas que usam este plano.
     */
    public function assinaturas(): HasMany
    {
        return $this->hasMany(MonitoramentoAssinatura::class, 'plano_id');
    }

    /**
     * Consultas realizadas com este plano.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(MonitoramentoConsulta::class, 'plano_id');
    }

    /**
     * Retorna planos ativos ordenados.
     *
     * @return Collection<int, self>
     */
    public static function ativos()
    {
        return static::query()
            ->get()
            ->filter(fn (self $plano) => $plano->is_active)
            ->sortBy('ordem')
            ->values();
    }

    /**
     * Encontra plano por código.
     */
    public static function porCodigo(string $codigo): ?self
    {
        return static::where('codigo', $codigo)->first();
    }

    /**
     * Resolve a definição canônica do plano pelo código.
     *
     * @return array<string, mixed>|null
     */
    public function resolvedDefinition(): ?array
    {
        return PlanoCatalog::forCodigo($this->attributes['codigo'] ?? $this->getRawOriginal('codigo'));
    }

    /**
     * @return array<int, string>
     */
    public function resolvedConsultasIncluidas(): array
    {
        return $this->consultas_incluidas ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolvedEtapas(): array
    {
        return $this->etapas ?? [];
    }

    public function resolvedTotalEtapas(): int
    {
        return collect($this->resolvedEtapas())
            ->pluck('numero')
            ->map(fn ($numero) => (int) $numero)
            ->filter(fn (int $numero) => $numero > 0)
            ->max() ?? 0;
    }

    public function getNomeAttribute($value): ?string
    {
        return $this->resolveCatalogScalar('nome', $value);
    }

    public function getDescricaoAttribute($value): ?string
    {
        return $this->resolveCatalogScalar('descricao', $value);
    }

    /**
     * @return array<int, string>
     */
    public function getConsultasIncluidasAttribute($value): array
    {
        return $this->resolveCatalogArray('consultas_incluidas', $value) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getEtapasAttribute($value): ?array
    {
        return $this->resolveCatalogArray('etapas', $value);
    }

    public function getCustoCreditosAttribute($value): int
    {
        return (int) $this->resolveCatalogScalar('custo_creditos', $value);
    }

    public function getIsGratuitoAttribute($value): bool
    {
        return (bool) $this->resolveCatalogScalar('is_gratuito', $value);
    }

    public function getIsActiveAttribute($value): bool
    {
        return (bool) $this->resolveCatalogScalar('is_active', $value);
    }

    public function getOrdemAttribute($value): int
    {
        return (int) $this->resolveCatalogScalar('ordem', $value);
    }

    private function resolveCatalogScalar(string $field, mixed $fallback): mixed
    {
        $definition = $this->resolvedDefinition();

        if ($definition !== null && array_key_exists($field, $definition)) {
            return $definition[$field];
        }

        return $fallback;
    }

    /**
     * @return array<int, mixed>|null
     */
    private function resolveCatalogArray(string $field, mixed $fallback): ?array
    {
        $definition = $this->resolvedDefinition();

        if ($definition !== null && array_key_exists($field, $definition)) {
            return $definition[$field];
        }

        return $this->decodeJsonArray($fallback);
    }

    /**
     * @return array<int, mixed>|null
     */
    private function decodeJsonArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
