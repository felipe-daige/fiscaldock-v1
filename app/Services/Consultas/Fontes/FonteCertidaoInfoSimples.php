<?php

namespace App\Services\Consultas\Fontes;

use App\Services\Consultas\Contracts\Fonte;

/**
 * Base para certidões via InfoSimples (CND Federal, CNDT, CRF FGTS...).
 * Trata o fluxo comum de não-sucesso (611→INDETERMINADO, 612→NAO_ENCONTRADA,
 * técnico→nada). Cada certidão só implementa `mapearSucesso()` (data[0]→bloco).
 */
abstract class FonteCertidaoInfoSimples implements Fonte
{
    abstract public function chave(): string;

    abstract public function slug(): string;

    abstract public function custoCreditos(): int;

    /** Mapeia o data[0] da resposta de sucesso no bloco interno da certidão. */
    abstract protected function mapearSucesso(array $data): array;

    public function fornece(): array
    {
        return [$this->chave()];
    }

    public function provider(): string
    {
        return 'infosimples';
    }

    public function pronta(): bool
    {
        // Só roteia pro Laravel quando o InfoSimples estiver explicitamente ativado
        // (pago/validado) E houver token. Até lá, planos pagos seguem no n8n.
        return (bool) config('consultas.infosimples_ativo', false)
            && filled(config('consultas.providers.infosimples.token'));
    }

    public function params(array $alvo): array
    {
        return ['cnpj' => preg_replace('/[^0-9]/', '', (string) ($alvo['cnpj'] ?? ''))];
    }

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        if ($status === 'sucesso') {
            return $this->bloco($this->mapearSucesso($raw['data'][0] ?? []));
        }

        // 611: a fonte não emitiu por dados insuficientes — INDETERMINADO, nunca irregular.
        if ($status === 'indeterminado') {
            return $this->bloco(['status' => 'INDETERMINADO', 'mensagem' => $this->mensagem($raw)]);
        }

        if ($status === 'nao_encontrado') {
            return $this->bloco(['status' => 'NAO_ENCONTRADA', 'mensagem' => $this->mensagem($raw)]);
        }

        // retry/fatal/erro_participante: falha técnica/parâmetro — nada a persistir aqui
        // (a mensagem do erro vai p/ consulta_resultados.error_message pelo job).
        return [];
    }

    protected function bloco(array $dados): array
    {
        return [
            $this->chave() => $dados,
            'consultas_realizadas' => [$this->chave()],
        ];
    }

    protected function mensagem(array $raw): ?string
    {
        $m = $raw['code_message'] ?? null;
        if (! empty($raw['errors']) && is_array($raw['errors'])) {
            $m = trim(($m ? $m.' ' : '').implode('; ', $raw['errors']));
        }

        return $m ?: null;
    }
}
