<?php

namespace App\Services;

use App\Models\EfdDivergencia;
use App\Models\EfdImportacao;
use Illuminate\Support\Facades\DB;

/**
 * Reconcilia o SPED bruto (arquivo_base64) com o que o pipeline persistiu
 * em efd_notas / efd_notas_itens. Para cada discrepância gera um registro
 * em efd_divergencias com o motivo classificado.
 *
 * - C100 com COD_SIT ∈ {02,06,08} → divergência INFO (esperado descartar)
 * - C100 no SPED ausente do banco com COD_SIT='00' → ERRO (pipeline perdeu)
 * - C170 do SPED ausente do banco → AVISO (duplicação/constraint)
 *
 * Idempotente: rodar 2x não duplica linhas em efd_divergencias.
 */
class EfdAuditoriaService
{
    public function auditar(EfdImportacao $imp): array
    {
        $sped = $this->lerSpedBruto($imp);
        $registros = $this->parse($sped);

        $c100Sped = $registros['C100'] ?? [];
        $c170Sped = $registros['C170'] ?? [];

        $c100Banco = DB::table('efd_notas')
            ->where('user_id', $imp->user_id)
            ->where('importacao_id', $imp->id)
            ->get(['id', 'chave_acesso', 'numero', 'serie', 'modelo']);

        $c100BancoPorChave = $c100Banco->keyBy('chave_acesso');

        $resultado = [
            'c100_sped' => count($c100Sped),
            'c100_banco' => $c100Banco->count(),
            'canceladas' => 0,
            'c170_sped' => count($c170Sped),
            'c170_banco' => 0,
            'divergencias_geradas' => 0,
        ];

        // --- C100: canceladas e ausentes ---
        foreach ($c100Sped as $c100) {
            $codSit = $c100['COD_SIT'] ?? '00';
            $chave = $c100['CHV_NFE'] ?? null;
            $numero = isset($c100['NUM_DOC']) ? (int) $c100['NUM_DOC'] : null;

            $motivo = match ($codSit) {
                '02' => EfdDivergencia::MOTIVO_CANCELADA_DESCARTADA,
                '06' => EfdDivergencia::MOTIVO_COMPLEMENTAR_DESCARTADA,
                '08' => EfdDivergencia::MOTIVO_REGULARIZACAO_DESCARTADA,
                default => null,
            };

            if ($motivo) {
                $this->registrar($imp, [
                    'bloco' => 'C100',
                    'motivo' => $motivo,
                    'severidade' => EfdDivergencia::SEVERIDADE_INFO,
                    'chave_acesso' => $chave,
                    'numero_documento' => $numero,
                    'payload_descartado' => $c100,
                    'mensagem' => "C100 COD_SIT={$codSit} descartado pelo pipeline",
                ]);
                $resultado['canceladas']++;
                $resultado['divergencias_geradas']++;

                continue;
            }

            // C100 normal mas ausente no banco
            if ($chave && ! $c100BancoPorChave->has($chave)) {
                $this->registrar($imp, [
                    'bloco' => 'C100',
                    'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO,
                    'severidade' => EfdDivergencia::SEVERIDADE_ERRO,
                    'chave_acesso' => $chave,
                    'numero_documento' => $numero,
                    'payload_descartado' => $c100,
                    'mensagem' => 'C100 presente no SPED mas ausente no banco',
                ]);
                $resultado['divergencias_geradas']++;
            }
        }

        // --- C170: agrupar por C100 pai (via numero|serie|modelo do pai atual) ---
        // Reconstroi pai-filho percorrendo SPED ordenado: cada C100 vira contexto.
        $itensSpedPorChave = $this->agruparItensPorPai($sped);

        $c170Banco = DB::table('efd_notas_itens')
            ->join('efd_notas', 'efd_notas.id', '=', 'efd_notas_itens.efd_nota_id')
            ->where('efd_notas.user_id', $imp->user_id)
            ->where('efd_notas.importacao_id', $imp->id)
            ->select('efd_notas.chave_acesso', 'efd_notas.numero', 'efd_notas_itens.numero_item')
            ->get();

        $resultado['c170_banco'] = $c170Banco->count();

        $itensBancoPorChave = [];
        foreach ($c170Banco as $r) {
            $itensBancoPorChave[$r->chave_acesso][$r->numero_item] = true;
        }

        // Coleta união de chaves do SPED e do banco — algumas podem só existir num lado
        $todasChaves = array_unique(array_merge(
            array_keys($itensSpedPorChave),
            array_keys($itensBancoPorChave)
        ));

        // Itens do SPED ausentes no banco (pipeline perdeu)
        foreach ($todasChaves as $chave) {
            $itens = $itensSpedPorChave[$chave] ?? [];
            if (! $c100BancoPorChave->has($chave)) {
                continue;
            }
            $numerosSpedPorNota = [];
            foreach ($itens as $item) {
                $numItem = (int) ($item['NUM_ITEM'] ?? 0);
                $numerosSpedPorNota[$numItem] = true;

                if (! isset($itensBancoPorChave[$chave][$numItem])) {
                    $numeroDoc = $c100BancoPorChave->get($chave)?->numero;
                    $this->registrar($imp, [
                        'bloco' => 'C170',
                        'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO,
                        'severidade' => EfdDivergencia::SEVERIDADE_AVISO,
                        'chave_acesso' => $chave,
                        'numero_documento' => $numeroDoc,
                        'numero_item' => $numItem,
                        'payload_descartado' => $item,
                        'mensagem' => "C170 num_item={$numItem} presente no SPED mas ausente no banco",
                    ]);
                    $resultado['divergencias_geradas']++;
                }
            }

            // Itens no banco que NÃO existem no SPED (duplicação que escapou ON CONFLICT)
            if (isset($itensBancoPorChave[$chave])) {
                foreach ($itensBancoPorChave[$chave] as $numItem => $_) {
                    if (! isset($numerosSpedPorNota[$numItem])) {
                        $numeroDoc = $c100BancoPorChave->get($chave)?->numero;
                        $this->registrar($imp, [
                            'bloco' => 'C170',
                            'motivo' => EfdDivergencia::MOTIVO_DUPLICADA_PROCESSAMENTO,
                            'severidade' => EfdDivergencia::SEVERIDADE_ERRO,
                            'chave_acesso' => $chave,
                            'numero_documento' => $numeroDoc,
                            'numero_item' => $numItem,
                            'payload_descartado' => ['origem' => 'banco', 'numero_item' => $numItem],
                            'mensagem' => "C170 num_item={$numItem} presente no banco mas ausente no SPED (duplicação)",
                        ]);
                        $resultado['divergencias_geradas']++;
                    }
                }
            }
        }

        return $resultado;
    }

    /**
     * Lê o conteúdo bruto do SPED a partir de efd_importacoes.arquivo_base64.
     * Apesar do nome, o campo armazena uma string JSON-encoded com o texto SPED.
     */
    private function lerSpedBruto(EfdImportacao $imp): string
    {
        $raw = $imp->arquivo_base64;
        if (! $raw) {
            return '';
        }
        $decoded = json_decode($raw, true);

        return is_string($decoded) ? $decoded : (string) $raw;
    }

    /**
     * Parse simples: agrupa registros por código (C100, C170, etc.).
     */
    private function parse(string $sped): array
    {
        $registros = [];
        $linhas = preg_split('/\r\n|\r|\n/', $sped);

        foreach ($linhas as $linha) {
            if ($linha === '' || $linha[0] !== '|') {
                continue;
            }
            $campos = explode('|', $linha);
            // posição 0 é vazia (antes do primeiro |), posição 1 é o REG
            $reg = $campos[1] ?? null;
            if (! $reg) {
                continue;
            }
            $registros[$reg][] = $this->mapearCampos($reg, $campos);
        }

        return $registros;
    }

    /**
     * Mapeia o array bruto pra nomes de campo conhecidos.
     * Foca em C100 e C170 (cobre os casos de auditoria atuais).
     */
    private function mapearCampos(string $reg, array $campos): array
    {
        if ($reg === 'C100') {
            return [
                'REG' => $reg,
                'IND_OPER' => $campos[2] ?? null,
                'IND_EMIT' => $campos[3] ?? null,
                'COD_PART' => $campos[4] ?? null,
                'COD_MOD' => $campos[5] ?? null,
                'COD_SIT' => $campos[6] ?? null,
                'SER' => $campos[7] ?? null,
                'NUM_DOC' => $campos[8] ?? null,
                'CHV_NFE' => $campos[9] ?? null,
                'DT_DOC' => $campos[10] ?? null,
                'DT_E_S' => $campos[11] ?? null,
                'VL_DOC' => $campos[12] ?? null,
            ];
        }
        if ($reg === 'C170') {
            return [
                'REG' => $reg,
                'NUM_ITEM' => $campos[2] ?? null,
                'COD_ITEM' => $campos[3] ?? null,
                'DESCR_COMPL' => $campos[4] ?? null,
                'QTD' => $campos[5] ?? null,
                'UNID' => $campos[6] ?? null,
                'VL_ITEM' => $campos[7] ?? null,
            ];
        }

        // fallback: devolve cru
        return ['REG' => $reg, 'raw' => $campos];
    }

    /**
     * Percorre o SPED de cima pra baixo associando C170 ao C100 imediatamente anterior.
     * Retorna [chave_acesso => [C170, C170, ...]].
     */
    private function agruparItensPorPai(string $sped): array
    {
        $out = [];
        $chaveAtual = null;
        $linhas = preg_split('/\r\n|\r|\n/', $sped);
        foreach ($linhas as $linha) {
            if ($linha === '' || $linha[0] !== '|') {
                continue;
            }
            $campos = explode('|', $linha);
            $reg = $campos[1] ?? null;
            if ($reg === 'C100') {
                $chaveAtual = $campos[9] ?? null;
            } elseif ($reg === 'C170' && $chaveAtual) {
                $out[$chaveAtual][] = $this->mapearCampos('C170', $campos);
            }
        }

        return $out;
    }

    private function registrar(EfdImportacao $imp, array $dados): void
    {
        EfdDivergencia::updateOrCreate(
            [
                'importacao_id' => $imp->id,
                'bloco' => $dados['bloco'],
                'motivo' => $dados['motivo'],
                'chave_acesso' => $dados['chave_acesso'] ?? null,
                'numero_item' => $dados['numero_item'] ?? null,
            ],
            [
                'user_id' => $imp->user_id,
                'severidade' => $dados['severidade'],
                'numero_documento' => $dados['numero_documento'] ?? null,
                'payload_descartado' => $dados['payload_descartado'],
                'mensagem' => $dados['mensagem'] ?? null,
                'detectado_em' => now(),
            ]
        );
    }
}
