<?php

namespace App\Services\Consultas;

use App\Services\Consultas\Contracts\Fonte;

class FonteRegistry
{
    /** @var array<string, Fonte> chave da fonte => Fonte */
    private array $fontes = [];

    /** @var array<string, Fonte> sub-atributo de consultas_incluidas => Fonte que o fornece */
    private array $porAtributo = [];

    /** @param Fonte[] $fontes */
    public function __construct(array $fontes = [])
    {
        foreach ($fontes as $f) {
            $this->fontes[$f->chave()] = $f;
            foreach ($f->fornece() as $atributo) {
                $this->porAtributo[$atributo] = $f;
            }
        }
    }

    public function get(string $chave): ?Fonte
    {
        return $this->fontes[$chave] ?? null;
    }

    /**
     * True se TODOS os sub-atributos do plano (consultas_incluidas) são fornecidos
     * por uma fonte registrada E PRONTA (gate de cutover). Roteamento Laravel×n8n.
     */
    public function cobre(array $atributos): bool
    {
        if (empty($atributos)) {
            return false;
        }

        foreach ($atributos as $atributo) {
            $fonte = $this->porAtributo[$atributo] ?? null;
            if (! $fonte || ! $fonte->pronta()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fontes (deduplicadas) necessárias para atender os sub-atributos do plano.
     *
     * @return Fonte[]
     */
    public function fontesDe(array $atributos): array
    {
        $out = [];
        foreach ($atributos as $atributo) {
            $fonte = $this->porAtributo[$atributo] ?? null;
            if ($fonte) {
                $out[$fonte->chave()] = $fonte;
            }
        }

        return array_values($out);
    }
}
