<?php

namespace App\Services\Clearance;

use App\Jobs\ProcessarClearanceJob;
use App\Models\ConsultaLote;
use App\Models\EfdNota;
use App\Models\User;
use App\Models\XmlNota;
use App\Services\CreditService;
use App\Services\ValidacaoContabilService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Orquestra o clearance SEFAZ em lote: resolve as chaves do acervo, debita créditos,
 * cria o ConsultaLote e dispara um ProcessarClearanceJob por documento via Bus::batch.
 * Substitui o despacho ao webhook n8n (desligado no cutover de 2026-06-07).
 */
class ClearanceLoteService
{
    public function __construct(private CreditService $creditService) {}

    /**
     * @param  array<int>  $notaIds
     * @param  array<int|string, string>  $origens  id => 'efd'|'xml'
     * @param  string  $tier  'basico' | 'full'
     * @return array<string, mixed>  inclui 'http_status' quando 'success' === false
     */
    public function iniciar(array $notaIds, array $origens, string $tier, int $userId, ?string $tabId): array
    {
        $tier = in_array($tier, ['basico', 'full'], true) ? $tier : 'basico';
        $itens = $this->resolverItens($notaIds, $origens, $userId);

        if ($itens->isEmpty()) {
            return [
                'success' => false,
                'http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'error' => 'Nenhuma nota válida para clearance (NFS-e e chaves inválidas são ignoradas).',
            ];
        }

        $user = User::findOrFail($userId);
        $custoUnit = ValidacaoContabilService::custoUnitarioPorTier($tier);
        $custoTotal = $itens->count() * $custoUnit;

        if (! $this->creditService->hasEnough($user, $custoTotal)) {
            return [
                'success' => false,
                'http_status' => Response::HTTP_PAYMENT_REQUIRED,
                'error' => 'Créditos insuficientes.',
                'custo_necessario' => $custoTotal,
                'saldo_atual' => $this->creditService->getBalance($user),
            ];
        }

        $this->creditService->deduct(
            $user,
            $custoTotal,
            'clearance_lote',
            "Clearance em lote ({$tier}) · {$itens->count()} documento(s)"
        );

        $lote = null;

        try {
            $lote = ConsultaLote::create([
                'user_id' => $userId,
                'cliente_id' => null,
                'plano_id' => null,
                'status' => ConsultaLote::STATUS_PROCESSANDO,
                'total_participantes' => $itens->count(),
                'creditos_cobrados' => $custoTotal,
                'tab_id' => $tabId,
            ]);

            // Lista de chaves do lote → FecharClearanceLoteService soma o estorno por doc.
            Cache::put("clearance_lote_chaves:{$lote->id}", $itens->pluck('chave')->all(), 86400);

            $total = $itens->count();
            $jobs = $itens->values()->map(fn (array $item, int $i) => new ProcessarClearanceJob(
                loteId: $lote->id,
                chave: $item['chave'],
                tipoDocumento: $item['tipo'],
                userId: $userId,
                tabId: (string) $tabId,
                clienteId: $item['cliente_id'],
                custoCreditos: $custoUnit,
                indice: $i + 1,
                total: $total,
            ))->all();

            Bus::batch($jobs)
                ->name("clearance-lote-{$lote->id}")
                ->then(fn () => app(FecharClearanceLoteService::class)->fechar($lote->id))
                ->dispatch();

            return [
                'success' => true,
                'consulta_lote_id' => $lote->id,
                'tab_id' => $tabId,
                'total_notas' => $total,
                'creditos_cobrados' => $custoTotal,
                'novo_saldo' => $this->creditService->getBalance($user),
                'resultado_url' => route('app.clearance.notas.resultado', ['consultaLoteId' => $lote->id]),
            ];
        } catch (\Throwable $e) {
            if ($lote) {
                $lote->update([
                    'status' => ConsultaLote::STATUS_ERRO,
                    'error_code' => 'INTERNAL_ERROR',
                    'error_message' => $e->getMessage(),
                ]);
            }

            $this->creditService->add($user, $custoTotal, 'clearance_refund', 'Estorno · falha ao iniciar clearance em lote');

            Log::error('Clearance lote: exceção ao iniciar', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'http_status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => 'Erro ao iniciar o clearance. Créditos estornados.',
            ];
        }
    }

    /**
     * Resolve nota_ids (efd/xml) → itens [chave, tipo('nfe'|'cte'), cliente_id], escopo user_id.
     * Pula NFS-e e chaves != 44 dígitos; dedup por chave (primeiro vence).
     *
     * @return Collection<int, array{chave: string, tipo: string, cliente_id: int|null}>
     */
    private function resolverItens(array $notaIds, array $origens, int $userId): Collection
    {
        $xmlIds = [];
        $efdIds = [];
        foreach ($notaIds as $id) {
            $origem = $origens[$id] ?? $origens[(string) $id] ?? 'xml';
            if ($origem === 'efd') {
                $efdIds[] = (int) $id;
            } else {
                $xmlIds[] = (int) $id;
            }
        }

        $itens = collect();

        if ($xmlIds !== []) {
            XmlNota::whereIn('id', $xmlIds)
                ->where('user_id', $userId)
                ->get(['id', 'chave_acesso', 'tipo_documento', 'emit_cliente_id', 'dest_cliente_id'])
                ->each(function (XmlNota $nota) use ($itens) {
                    $tipo = $this->tipoConsulta(strtoupper((string) ($nota->tipo_documento ?: 'NFE')));
                    if ($tipo !== null) {
                        $itens->push([
                            'chave' => preg_replace('/\D/', '', (string) $nota->chave_acesso),
                            'tipo' => $tipo,
                            'cliente_id' => $nota->emit_cliente_id ?: $nota->dest_cliente_id,
                        ]);
                    }
                });
        }

        if ($efdIds !== []) {
            EfdNota::whereIn('id', $efdIds)
                ->where('user_id', $userId)
                ->get(['id', 'chave_acesso', 'modelo', 'cliente_id'])
                ->each(function (EfdNota $nota) use ($itens) {
                    $tipo = $this->tipoConsultaPorModelo(strtoupper((string) $nota->modelo));
                    if ($tipo !== null) {
                        $itens->push([
                            'chave' => preg_replace('/\D/', '', (string) $nota->chave_acesso),
                            'tipo' => $tipo,
                            'cliente_id' => $nota->cliente_id,
                        ]);
                    }
                });
        }

        return $itens
            ->filter(fn (array $item) => strlen($item['chave']) === 44)
            ->unique('chave')
            ->values();
    }

    /** tipo_documento textual (XML) → slug de consulta ('nfe'|'cte') ou null (não suportado). */
    private function tipoConsulta(string $tipoDocumento): ?string
    {
        return match ($tipoDocumento) {
            'NFE', 'NFCE' => 'nfe',
            'CTE' => 'cte',
            default => null, // NFSE e afins ficam fora
        };
    }

    /** modelo (EFD) → slug de consulta ('nfe'|'cte') ou null. */
    private function tipoConsultaPorModelo(string $modelo): ?string
    {
        return match ($modelo) {
            '55', '65' => 'nfe',
            '57' => 'cte',
            default => null,
        };
    }
}
