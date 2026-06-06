<?php

namespace App\Services\Consultas\Fontes;

class CguCncFonte extends FonteInfoSimplesBase
{
    public function chave(): string
    {
        return 'cgu_cnc';
    }

    public function slug(): string
    {
        return 'cgu/cnc-tipo1';
    }

    public function custoCreditos(): int
    {
        return (int) config('consultas.fontes.cgu_cnc', 2);
    }

    public function normalizar(array $raw, string $status = 'sucesso'): array
    {
        // A resposta traz a CERTIDÃO (data[0]) — o array não-vazio NÃO significa sanção.
        // possui_sancao = a fonte NÃO conseguiu emitir certidão negativa OU alguma base tem registro.
        if ($status === 'sucesso') {
            $d0 = $raw['data'][0] ?? [];
            $bases = $d0['bases_dados_consultas'] ?? [];
            $comRegistro = array_values(array_filter($bases, fn ($b) => ! preg_match('/nada\s*consta/i', (string) ($b['situacao'] ?? ''))));
            $semSancao = (bool) ($d0['conseguiu_emitir_certidao_negativa'] ?? false) && count($comRegistro) === 0;

            return $this->bloco([
                'possui_sancao' => ! $semSancao,
                'bases' => $bases,
                'bases_com_registro' => array_map(fn ($b) => $b['nome'] ?? null, $comRegistro),
                'mensagem' => $d0['mensagem'] ?? null,
                'comprovante' => $d0['site_receipt'] ?? null,
                'data_validade' => $d0['data_validade'] ?? null,
                'consulta_datahora' => $d0['datahora_emissao'] ?? null,
            ]);
        }

        if ($status === 'nao_encontrado') {
            return $this->bloco(['possui_sancao' => false, 'total_sancoes' => 0, 'sancoes' => []]);
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
