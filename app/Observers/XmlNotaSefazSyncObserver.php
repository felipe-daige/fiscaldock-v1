<?php

namespace App\Observers;

use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\DivergenciaSnapshotService;

/**
 * Recalcula divergencia_* sempre que situacao_sefaz/verificado_sefaz_em
 * mudam (snapshot SEFAZ chegou ou foi atualizado).
 *
 * Usa saveQuietly() lá dentro pra não disparar updated() de novo (loop).
 */
class XmlNotaSefazSyncObserver
{
    public function __construct(
        private readonly DivergenciaSnapshotService $snapshot,
    ) {}

    public function saved(XmlNota $nota): void
    {
        if (! $this->snapshotMudou($nota)) {
            return;
        }

        $this->snapshot->sincronizar($nota);
    }

    private function snapshotMudou(XmlNota $nota): bool
    {
        if (! $nota->wasRecentlyCreated) {
            $changed = $nota->getChanges();

            return array_key_exists('situacao_sefaz', $changed)
                || array_key_exists('verificado_sefaz_em', $changed);
        }

        return $nota->situacao_sefaz !== null;
    }
}
