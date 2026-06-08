<?php

namespace App\Services\Efd;

use App\Models\EfdImportacao;

class EfdImportacaoDuplicidadeService
{
    /**
     * Decide o tipo de conflito de uma nova importação contra o acervo do usuário.
     *
     * @return array{caso: 'identico'|'periodo'|null, importacao: EfdImportacao|null}
     */
    public function verificar(
        int $userId,
        ?int $clienteId,
        string $tipoEfd,
        ?string $periodoInicio,
        ?string $periodoFim,
        string $arquivoHash,
    ): array {
        // 1. Arquivo idêntico (hash) — engano puro, qualquer cliente do usuário.
        $identico = EfdImportacao::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'erro')
            ->where('arquivo_hash', $arquivoHash)
            ->latest('id')
            ->first();

        if ($identico !== null) {
            return ['caso' => 'identico', 'importacao' => $identico];
        }

        // 2. Mesmo período/tipo/cliente, conteúdo diferente — possível retificadora.
        if ($periodoInicio !== null && $periodoFim !== null) {
            $periodo = EfdImportacao::query()
                ->where('user_id', $userId)
                ->where('status', '!=', 'erro')
                ->where('tipo_efd', $tipoEfd)
                ->where('periodo_inicio', $periodoInicio)
                ->where('periodo_fim', $periodoFim)
                ->when(
                    $clienteId === null,
                    fn ($q) => $q->whereNull('cliente_id'),
                    fn ($q) => $q->where('cliente_id', $clienteId),
                )
                ->latest('id')
                ->first();

            if ($periodo !== null) {
                return ['caso' => 'periodo', 'importacao' => $periodo];
            }
        }

        return ['caso' => null, 'importacao' => null];
    }
}
