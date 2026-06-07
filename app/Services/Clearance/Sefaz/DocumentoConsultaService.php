<?php

namespace App\Services\Clearance\Sefaz;

use App\Services\Consultas\Providers\InfoSimplesProvider;
use App\Services\Consultas\ThrottleProvider;
use InvalidArgumentException;

class DocumentoConsultaService
{
    public function __construct(
        private InfoSimplesProvider $provider,
        private ThrottleProvider $throttle,
        private NfeSnapshotNormalizer $nfeNormalizer,
        private CteSnapshotNormalizer $cteNormalizer,
    ) {}

    /**
     * Núcleo PURO: consulta a SEFAZ por chave e devolve o snapshot normalizado.
     * NÃO persiste — o caller (lote/busca-notas) decide a persistência.
     *
     * @param  string  $tipoDocumento  'nfe' | 'cte'
     */
    public function consultar(string $chave, string $tipoDocumento, ?int $clienteId = null): DocumentoSnapshot
    {
        $chave = preg_replace('/\D/', '', $chave);
        if (strlen($chave) !== 44) {
            throw new InvalidArgumentException('Chave de acesso deve ter 44 dígitos.');
        }

        $modelo = substr($chave, 20, 2);
        $tipo = strtolower($tipoDocumento);

        // $param = nome do argumento que o InfoSimples espera pra chave: receita-federal/nfe → 'nfe',
        // receita-federal/cte → 'cte' (confirmado pela própria API: "O parâmetro 'nfe' não pode ser vazio").
        [$slug, $param, $modelosOk, $normalizer] = match ($tipo) {
            'nfe' => ['receita-federal/nfe', 'nfe', ['55', '65'], $this->nfeNormalizer],
            'cte' => ['receita-federal/cte', 'cte', ['57'], $this->cteNormalizer],
            default => throw new InvalidArgumentException("Tipo de documento inválido: {$tipoDocumento}"),
        };

        if (! in_array($modelo, $modelosOk, true)) {
            throw new InvalidArgumentException("Modelo {$modelo} incompatível com {$tipoDocumento}.");
        }

        $resp = $this->chamar($slug, $param, $chave);

        // Retry 1x em status retryável (espelha "Wait 30s + Retry" dos Code Nodes).
        $statusFinal = $resp->status;
        if ($resp->status === 'retry') {
            $resp2 = $this->chamar($slug, $param, $chave);
            $resp = $resp2;
            $statusFinal = $resp2->status === 'sucesso' ? 'sucesso' : 'retry';
        }

        $billable = (bool) ($resp->raw['header']['billable'] ?? false);

        return $normalizer->normalizar($resp->raw, $statusFinal, $chave, $billable);
    }

    private function chamar(string $slug, string $param, string $chave)
    {
        $this->throttle->aguardar('infosimples');

        // O nome do argumento é o tipo do doc ('nfe'/'cte'), não 'chave'/'chave_acesso'.
        return $this->provider->consultar($slug, [$param => $chave]);
    }
}
