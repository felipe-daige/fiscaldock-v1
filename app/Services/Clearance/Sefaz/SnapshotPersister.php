<?php

namespace App\Services\Clearance\Sefaz;

use App\Models\CteConsulta;
use App\Models\NfeConsulta;

class SnapshotPersister
{
    /** UPSERT por (user_id, chave_acesso) na tabela certa por tipo de documento. */
    public function upsert(DocumentoSnapshot $s, ContextoPersistencia $ctx): void
    {
        $model = $s->tipoDocumento === 'CTE' ? CteConsulta::class : NfeConsulta::class;

        $model::updateOrCreate(
            ['user_id' => $ctx->userId, 'chave_acesso' => $s->chaveAcesso],
            array_merge($s->colunas, [
                'tipo_documento' => $s->tipoDocumento,
                'cliente_id' => $ctx->clienteId,
                'consulta_lote_id' => $ctx->consultaLoteId,
                'credit_transaction_id' => $ctx->creditTransactionId,
                'correlation_id' => $ctx->correlationId,
                'custo' => $ctx->custo,
                'error_code' => $s->errorCode,
                'error_message' => $s->errorMessage,
                'payload' => $s->payload,
                'consultado_em' => now(),
            ])
        );
    }
}
