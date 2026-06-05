<?php

namespace App\Jobs;

use App\Services\Consultas\Dto\ResultadoFonte;
use App\Services\Consultas\FonteRegistry;
use App\Services\Consultas\Persistencia\PersistenciaCnpj;
use App\Services\Consultas\Providers\MinhaReceitaProvider;
use App\Services\Consultas\ThrottleProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessarConsultaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $loteId,
        public string $alvoTipo,   // 'participante' | 'cliente'
        public int $alvoId,
        public int $userId,
        public string $tabId,
        public array $consultasIncluidas,
        public array $alvo,
        public array $etapas,
    ) {}

    public function handle(
        FonteRegistry $registry,
        ThrottleProvider $throttle,
        PersistenciaCnpj $persistencia,
    ): void {
        $total = count($this->etapas);
        $this->progresso(etapa: 1, total: $total, label: $this->etapas[0] ?? 'Preparando consulta', status: 'processando');

        // Alvo mutável: a UF autoritativa vem do cadastro (minhareceita) e alimenta as
        // fontes UF-dependentes (ex: CND Estadual). O cadastro é a 1ª fonte de todo plano.
        $alvo = $this->alvo;

        $passo = 1;
        $creditosFalhos = 0;
        foreach ($registry->fontesDe($this->consultasIncluidas) as $fonte) {
            $passo++;

            // Cobertura do provedor indisponível p/ este alvo (ex: UF/cidade) → pula sem
            // chamar nem cobrar; persiste como INDISPONIVEL com o MOTIVO (não é falha estornável).
            if (! $fonte->aplicavelPara($alvo)) {
                $persistencia->gravar($this->loteId, $this->alvoTipo, $this->alvoId, new ResultadoFonte(
                    $fonte->chave(),
                    $fonte->normalizar(['_motivo' => $fonte->motivoIndisponivel($alvo)], 'nao_aplicavel'),
                    'nao_aplicavel', 0, $fonte->motivoIndisponivel($alvo),
                ));
                $this->progresso(
                    etapa: min($passo, $total), total: $total,
                    label: $this->etapas[$passo - 1] ?? $fonte->chave(), status: 'processando',
                );

                continue;
            }

            $throttle->aguardar($fonte->provider());

            $provider = $this->resolverProvider($fonte->provider());
            $resp = $provider->consultar($fonte->slug(), $fonte->params($alvo));

            $dados = $fonte->normalizar($resp->raw, $resp->status);

            // A UF do cadastro é autoritativa para as próximas fontes UF-dependentes.
            if ($fonte->chave() === 'cadastro' && ! empty($dados['endereco']['uf'])) {
                $alvo['uf'] = $dados['endereco']['uf'];
            }

            $resultado = new ResultadoFonte(
                $fonte->chave(), $dados,
                $resp->status, $fonte->custoCreditos(), $resp->mensagem,
            );
            $persistencia->gravar($this->loteId, $this->alvoTipo, $this->alvoId, $resultado);

            if ($resultado->ehFalhaEstornavel()) {
                $creditosFalhos += $resultado->custoCreditos;
            }

            $this->progresso(
                etapa: min($passo, $total), total: $total,
                label: $this->etapas[$passo - 1] ?? $fonte->chave(), status: 'processando',
            );
        }

        // Estorno preciso: total por participante (overwrite = idempotente em retry do job).
        // Somado por FecharLoteService ao fechar o lote. Ver project_camada_consultas_laravel.
        Cache::put("consulta_estorno:{$this->loteId}:{$this->alvoTipo}:{$this->alvoId}", $creditosFalhos, 86400);
    }

    private function resolverProvider(string $nome)
    {
        return match ($nome) {
            'minhareceita' => app(MinhaReceitaProvider::class),
            'infosimples' => app(\App\Services\Consultas\Providers\InfoSimplesProvider::class),
            default => throw new \RuntimeException("Provider não suportado: {$nome}"),
        };
    }

    private function progresso(int $etapa, int $total, string $label, string $status): void
    {
        Cache::put("progresso:{$this->userId}:{$this->tabId}", [
            'tab_id' => $this->tabId,
            'etapa' => $etapa,
            'total_etapas' => $total,
            'etapa_label' => $label,
            'status' => $status,
        ], 600);
    }
}
