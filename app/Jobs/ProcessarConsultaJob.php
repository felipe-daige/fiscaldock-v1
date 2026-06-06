<?php

namespace App\Jobs;

use App\Services\Consultas\Dto\ResultadoFonte;
use App\Services\Consultas\FonteRegistry;
use App\Services\Consultas\Persistencia\PersistenciaCnpj;
use App\Services\Consultas\Providers\MinhaReceitaProvider;
use App\Services\Consultas\ThrottleProvider;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessarConsultaJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        // Etapa inicial: inicializacao.
        [$nIni, $lIni] = $this->etapaPorChave('inicializacao', 'Preparando consulta');
        $this->progresso(etapa: $nIni, total: $total, label: $lIni, status: 'processando');

        // Alvo mutável: a UF autoritativa vem do cadastro (minhareceita) e alimenta as
        // fontes UF-dependentes (ex: CND Estadual). O cadastro é a 1ª fonte de todo plano.
        $alvo = $this->alvo;

        $creditosFalhos = 0;
        foreach ($registry->fontesDe($this->consultasIncluidas) as $fonte) {
            // Progresso por GRUPO de etapa da fonte (várias fontes → mesma etapa; sem loop).
            [$nEtapa, $lEtapa] = $this->etapaParaFonte($fonte->chave());

            // Cobertura do provedor indisponível p/ este alvo (ex: UF/cidade) → pula sem
            // chamar nem cobrar; persiste como INDISPONIVEL com o MOTIVO (não é falha estornável).
            if (! $fonte->aplicavelPara($alvo)) {
                $persistencia->gravar($this->loteId, $this->alvoTipo, $this->alvoId, new ResultadoFonte(
                    $fonte->chave(),
                    $fonte->normalizar(['_motivo' => $fonte->motivoIndisponivel($alvo)], 'nao_aplicavel'),
                    'nao_aplicavel', 0, $fonte->motivoIndisponivel($alvo),
                ));
                $this->progresso(etapa: $nEtapa, total: $total, label: $lEtapa, status: 'processando');

                continue;
            }

            $throttle->aguardar($fonte->provider());

            $provider = $this->resolverProvider($fonte->provider());
            $resp = $provider->consultar($fonte->slugPara($alvo), $fonte->params($alvo));

            $dados = $fonte->normalizar($resp->raw, $resp->status);

            // UF e município do cadastro são autoritativos p/ as fontes UF/cidade-dependentes.
            if ($fonte->chave() === 'cadastro') {
                if (! empty($dados['endereco']['uf'])) {
                    $alvo['uf'] = $dados['endereco']['uf'];
                }
                if (! empty($dados['endereco']['municipio'])) {
                    $alvo['municipio'] = $dados['endereco']['municipio'];
                }
            }

            $resultado = new ResultadoFonte(
                $fonte->chave(), $dados,
                $resp->status, $fonte->custoCreditos(), $resp->mensagem,
            );
            $persistencia->gravar($this->loteId, $this->alvoTipo, $this->alvoId, $resultado);

            if ($resultado->ehFalhaEstornavel()) {
                $creditosFalhos += $resultado->custoCreditos;
            }

            $this->progresso(etapa: $nEtapa, total: $total, label: $lEtapa, status: 'processando');
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

    /** Etapa (numero, label) por chave no array de etapas do plano. */
    private function etapaPorChave(string $chave, string $fallbackLabel): array
    {
        foreach ($this->etapas as $e) {
            if (is_array($e) && ($e['chave'] ?? null) === $chave) {
                return [(int) ($e['numero'] ?? 0), (string) ($e['label'] ?? $fallbackLabel)];
            }
        }

        return [count($this->etapas), $fallbackLabel];
    }

    /** Etapa (numero, label) do GRUPO ao qual a fonte pertence (config consultas.fonte_etapa). */
    private function etapaParaFonte(string $chaveFonte): array
    {
        $chaveEtapa = (string) config("consultas.fonte_etapa.{$chaveFonte}", 'cadastrais');

        return $this->etapaPorChave($chaveEtapa, $chaveFonte);
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
