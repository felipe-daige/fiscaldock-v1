<?php

namespace App\Services;

use Carbon\Carbon;

class ParecerFiscalService
{
    private const COR_ALTA = '#dc2626';

    private const COR_MEDIA = '#f59e0b';

    private const COR_BAIXA = '#6b7280';

    private const COR_INFO = '#3b82f6';

    /**
     * Gera o parecer fiscal a partir do payload de uma consulta bem-sucedida.
     *
     * Retorna lista ordenada por severidade decrescente. Cada item:
     *   - chave      (string)  ex: 'historico_simples'
     *   - severidade (string)  'info'|'baixa'|'media'|'alta'
     *   - titulo     (string)
     *   - descricao  (string)
     *   - hex        (string)  cor inline para o badge (padrão DANFE)
     *   - icone      (string)  chave do icon system
     *
     * @param  array<string, mixed>  $resultadoDados  conteúdo de ConsultaResultado::resultado_dados
     * @return array<int, array<string, string>>
     */
    public function gerar(array $resultadoDados): array
    {
        if (empty($resultadoDados)) {
            return [];
        }

        $itens = array_filter([
            $this->detectSituacaoInativa($resultadoDados),
            $this->detectSocioPj($resultadoDados),
            $this->detectHistoricoSimples($resultadoDados),
            $this->detectDivergenciaCnae($resultadoDados),
            $this->detectRegimeTributario($resultadoDados),
        ]);

        return array_values($itens);
    }

    /**
     * Gera uma versão compacta do parecer para tabelas e resumos.
     *
     * Remove itens apenas contextuais e expõe um rótulo curto para badge,
     * preservando o texto completo no tooltip.
     *
     * @param  array<string, mixed>  $resultadoDados
     * @return array<int, array<string, string>>
     */
    public function gerarResumo(array $resultadoDados): array
    {
        return $this->compactarParaResumo($this->gerar($resultadoDados));
    }

    private function detectSituacaoInativa(array $dados): ?array
    {
        $situacao = strtoupper(trim((string) ($dados['situacao_cadastral'] ?? '')));

        if ($situacao === '' || $situacao === 'ATIVA') {
            return null;
        }

        $motivo = trim((string) ($dados['motivo_situacao_cadastral'] ?? ''));
        $descricao = $motivo !== ''
            ? "Situação cadastral: {$situacao}. Motivo: {$motivo}. Nova operação precisa ser avaliada antes de emitir notas."
            : "Situação cadastral: {$situacao}. Nova operação precisa ser avaliada antes de emitir notas.";

        return [
            'chave' => 'situacao_inativa',
            'severidade' => 'alta',
            'titulo' => 'Empresa inativa na Receita Federal',
            'descricao' => $descricao,
            'hex' => self::COR_ALTA,
            'icone' => 'x-circle',
        ];
    }

    private function detectSocioPj(array $dados): ?array
    {
        $qsa = $dados['qsa'] ?? null;

        if (! is_array($qsa) || empty($qsa)) {
            return null;
        }

        $sociosPj = [];

        foreach ($qsa as $socio) {
            $documento = (string) ($socio['cpf_cnpj'] ?? '');
            $apenasDigitos = preg_replace('/\D/', '', $documento);

            if (strlen($apenasDigitos) === 14) {
                $sociosPj[] = trim((string) ($socio['nome'] ?? ''));
            }
        }

        if (empty($sociosPj)) {
            return null;
        }

        $quantidade = count($sociosPj);
        $sufixo = $quantidade === 1 ? 'sócio pessoa jurídica' : 'sócios pessoa jurídica';
        $nomes = implode(', ', array_filter($sociosPj));

        $descricao = "{$quantidade} {$sufixo} no QSA" . ($nomes !== '' ? " ({$nomes})" : '')
            . '. Estrutura de holding ou empresa controlada — atenção a operações intragrupo e preços de transferência.';

        return [
            'chave' => 'socio_pj',
            'severidade' => 'media',
            'titulo' => 'Sócio pessoa jurídica no QSA',
            'descricao' => $descricao,
            'hex' => self::COR_MEDIA,
            'icone' => 'users',
        ];
    }

    private function detectHistoricoSimples(array $dados): ?array
    {
        $dataOpcao = $dados['data_opcao_simples'] ?? null;
        $dataExclusao = $dados['data_exclusao_simples'] ?? null;

        if (empty($dataOpcao) || empty($dataExclusao)) {
            return null;
        }

        try {
            $inicio = Carbon::parse((string) $dataOpcao)->format('d/m/Y');
            $fim = Carbon::parse((string) $dataExclusao)->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }

        return [
            'chave' => 'historico_simples',
            'severidade' => 'media',
            'titulo' => 'Histórico no Simples Nacional',
            'descricao' => "Empresa foi optante do Simples Nacional entre {$inicio} e {$fim}. Operações nesse período têm tratamento diferente de PIS/COFINS e ICMS — conferir alíquotas e créditos por período.",
            'hex' => self::COR_MEDIA,
            'icone' => 'clock',
        ];
    }

    private function detectDivergenciaCnae(array $dados): ?array
    {
        $cnaes = $dados['cnaes'] ?? null;

        if (! is_array($cnaes)) {
            return null;
        }

        $principal = $cnaes['principal']['codigo'] ?? null;
        $secundarios = $cnaes['secundarios'] ?? [];

        if (! is_string($principal) || ! is_array($secundarios) || empty($secundarios)) {
            return null;
        }

        $prefixoPrincipal = $this->prefixoCnae($principal);
        if ($prefixoPrincipal === null) {
            return null;
        }

        $prefixosDivergentes = [];
        $totalSecundarios = 0;
        $divergentes = 0;

        foreach ($secundarios as $cnae) {
            $codigo = (string) ($cnae['codigo'] ?? '');
            $prefixo = $this->prefixoCnae($codigo);

            if ($prefixo === null) {
                continue;
            }

            $totalSecundarios++;

            if ($prefixo !== $prefixoPrincipal) {
                $divergentes++;
                $prefixosDivergentes[$prefixo] = true;
            }
        }

        if ($totalSecundarios === 0 || $divergentes / $totalSecundarios <= 0.5) {
            return null;
        }

        $listaPrefixos = implode(', ', array_keys($prefixosDivergentes));
        $descricaoPrincipal = (string) ($cnaes['principal']['descricao'] ?? $principal);

        return [
            'chave' => 'divergencia_cnae',
            'severidade' => 'baixa',
            'titulo' => 'Atividades heterogêneas nos CNAEs',
            'descricao' => "CNAE principal: {$descricaoPrincipal}. {$divergentes} de {$totalSecundarios} CNAEs secundários cobrem outros grupos (divisões {$listaPrefixos}). Revisar CFOPs declarados contra o escopo contratual de cada operação.",
            'hex' => self::COR_BAIXA,
            'icone' => 'info-circle',
        ];
    }

    private function detectRegimeTributario(array $dados): ?array
    {
        $regime = trim((string) ($dados['regime_tributario'] ?? ''));

        if ($regime === '') {
            return null;
        }

        $ano = $dados['regime_tributario_ano'] ?? null;
        $titulo = "Regime: {$regime}";
        $descricao = is_numeric($ano)
            ? "Regime tributário declarado para o ano-base {$ano}. Base para conduta de PIS/COFINS, ICMS e IRPJ."
            : 'Regime tributário declarado. Base para conduta de PIS/COFINS, ICMS e IRPJ.';

        return [
            'chave' => 'regime_tributario',
            'severidade' => 'info',
            'titulo' => $titulo,
            'descricao' => $descricao,
            'hex' => self::COR_INFO,
            'icone' => 'info-circle',
        ];
    }

    /**
     * Extrai os 2 primeiros dígitos (divisão CNAE) de um código formatado.
     * Ex: "4930-2/02" → "49"; "493" → "49"; "X" → null.
     */
    private function prefixoCnae(string $codigo): ?string
    {
        $apenasDigitos = preg_replace('/\D/', '', $codigo);

        if (strlen($apenasDigitos) < 2) {
            return null;
        }

        return substr($apenasDigitos, 0, 2);
    }

    /**
     * @param  array<int, array<string, string>>  $itens
     * @return array<int, array<string, string>>
     */
    private function compactarParaResumo(array $itens): array
    {
        return array_values(array_map(function (array $item): array {
            $titulo = trim((string) ($item['titulo'] ?? 'Parecer'));
            $descricao = trim((string) ($item['descricao'] ?? ''));

            $item['badge_label'] = $this->resolveBadgeLabel($item);
            $item['tooltip'] = $descricao !== ''
                ? "{$titulo}: {$descricao}"
                : $titulo;

            return $item;
        }, array_filter($itens, function (array $item): bool {
            return ($item['severidade'] ?? null) !== 'info';
        })));
    }

    /**
     * @param  array<string, string>  $item
     */
    private function resolveBadgeLabel(array $item): string
    {
        return match ($item['chave'] ?? null) {
            'situacao_inativa' => 'Inativa na RF',
            'socio_pj' => 'QSA com PJ',
            'historico_simples' => 'Histórico Simples',
            'divergencia_cnae' => 'CNAEs diversos',
            default => trim((string) ($item['titulo'] ?? 'Parecer')),
        };
    }
}
