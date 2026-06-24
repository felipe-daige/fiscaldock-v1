<?php

namespace App\Services\Consultas\Fontes;

use App\Services\Consultas\Contracts\Fonte;

/**
 * Base comum a TODA fonte InfoSimples (certidões e não-certidões: sintegra, sanções...).
 * Centraliza provider, gate de cutover (pronta), params padrão, bloco e mensagem.
 * Cada fonte implementa `normalizar()` conforme seu shape.
 */
abstract class FonteInfoSimplesBase implements Fonte
{
    abstract public function chave(): string;

    abstract public function slug(): string;

    abstract public function custoCreditos(): int;

    abstract public function normalizar(array $raw, string $status = 'sucesso'): array;

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
        // Só consulta o InfoSimples quando estiver explicitamente ativado
        // (pago/validado) E houver token. Enquanto false, as fontes InfoSimples não rodam.
        return (bool) config('consultas.infosimples_ativo', false)
            && filled(config('consultas.providers.infosimples.token'));
    }

    public function slugPara(array $alvo): string
    {
        return $this->slug();
    }

    public function aplicavelPara(array $alvo): bool
    {
        return true; // por padrão, aplica-se a todo CNPJ (cobertura nacional)
    }

    public function motivoIndisponivel(array $alvo): string
    {
        return 'Cobertura indisponível para este alvo no provedor.';
    }

    public function params(array $alvo): array
    {
        return ['cnpj' => preg_replace('/[^0-9]/', '', (string) ($alvo['cnpj'] ?? ''))];
    }

    /**
     * Bloco padrão para status não-consultado (nao_aplicavel: bloqueado por allowlist de teste
     * ou cobertura). Fontes de lista usam isto para mostrar INDISPONIVEL em vez de sumir.
     */
    protected function blocoIndisponivel(array $raw): array
    {
        return $this->bloco([
            'status' => 'INDISPONIVEL',
            'mensagem' => $raw['_motivo'] ?? $this->mensagem($raw) ?? 'Não consultado.',
        ]);
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
