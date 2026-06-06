<?php

namespace App\Services\Consultas\Fontes;

class CnjImprobidadeFonte extends FonteInfoSimplesBase
{
    public function chave(): string
    {
        return 'cnj_improbidade';
    }

    public function slug(): string
    {
        return 'cnj/improbidade';
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cnj_improbidade', 2);
    }

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        // A resposta traz a CERTIDÃO (data[0]) com certidao_negativa + registros. O array
        // não-vazio NÃO significa condenação — ler os flags reais.
        if ($status === 'sucesso') {
            $d0 = $raw['data'][0] ?? [];
            $registros = (int) ($d0['registros'] ?? 0);
            $negativa = (bool) ($d0['certidao_negativa'] ?? false);

            return $this->bloco([
                'possui_condenacao' => ! $negativa || $registros > 0,
                'total_condenacoes' => $registros,
                'condenacoes' => $d0['registros_lista'] ?? [],
                'comprovante' => $d0['site_receipt'] ?? null,
                'consulta_datahora' => $d0['consulta_datahora'] ?? null,
            ]);
        }

        if ($status === 'nao_encontrado') {
            return $this->bloco(['possui_condenacao' => false, 'total_condenacoes' => 0, 'condenacoes' => []]);
        }

        if ($status === 'indeterminado') {
            return $this->bloco(['status' => 'INDETERMINADO', 'mensagem' => $this->mensagem($raw)]);
        }

        if ($status === 'nao_aplicavel') {
            return $this->blocoIndisponivel($raw);
        }

        return [];
    }
}
