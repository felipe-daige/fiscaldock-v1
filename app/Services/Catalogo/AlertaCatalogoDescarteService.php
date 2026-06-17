<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoAlertaDescarte;
use InvalidArgumentException;

/**
 * Descarte (dispensa) persistido de alertas de catálogo por usuário e item.
 * Os painéis e contagens filtram os descartados no backend (não confia no front).
 */
class AlertaCatalogoDescarteService
{
    /**
     * Códigos de item descartados pelo usuário para um tipo de alerta.
     *
     * @return array<int, string>
     */
    public function descartados(int $userId, string $tipo): array
    {
        $this->validarTipo($tipo);

        return CatalogoAlertaDescarte::query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo)
            ->pluck('codigo_item')
            ->all();
    }

    /** Idempotente (unique user_id+tipo+codigo_item). */
    public function descartar(int $userId, string $tipo, string $codigoItem): void
    {
        $this->validarTipo($tipo);

        CatalogoAlertaDescarte::firstOrCreate([
            'user_id' => $userId,
            'tipo' => $tipo,
            'codigo_item' => $codigoItem,
        ]);
    }

    public function restaurar(int $userId, string $tipo, string $codigoItem): void
    {
        $this->validarTipo($tipo);

        CatalogoAlertaDescarte::query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo)
            ->where('codigo_item', $codigoItem)
            ->delete();
    }

    private function validarTipo(string $tipo): void
    {
        if (! in_array($tipo, CatalogoAlertaDescarte::TIPOS, true)) {
            throw new InvalidArgumentException("Tipo de alerta inválido: {$tipo}");
        }
    }
}
