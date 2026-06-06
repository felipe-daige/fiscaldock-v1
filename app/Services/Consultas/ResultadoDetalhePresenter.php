<?php

namespace App\Services\Consultas;

use App\Models\ConsultaResultado;
use App\Support\CertidaoBadge;

/**
 * Transforma o `resultado_dados` (jsonb por fonte) de um ConsultaResultado em blocos
 * exibíveis no detalhe expansível por CNPJ. Cada fonte vira um card com badge, itens
 * (label/valor/tooltip), listas (CNAEs, QSA, bases de sanção...), mensagem oficial e
 * link de comprovante quando houver.
 *
 * Objetivo: exibir TUDO que a consulta trouxe — inclusive fontes que a tabela resumida
 * não mostra (CND Estadual/Municipal, SINTEGRA, sanções CGU, improbidade CNJ).
 *
 * Bloco:
 *   ['chave','titulo','badge'(array|null),'itens'[],'listas'[],'mensagem'(?string),'comprovante_url'(?string)]
 */
class ResultadoDetalhePresenter
{
    /** Ordem canônica dos blocos (cadastro sempre primeiro). */
    private const ORDEM = [
        'cnd_federal',
        'cnd_estadual',
        'cnd_municipal',
        'crf_fgts',
        'cndt',
        'sintegra',
        'cgu_cnc',
        'cnj_improbidade',
    ];

    /** @return array<int, array<string, mixed>> */
    public function blocos(ConsultaResultado $resultado): array
    {
        $dados = is_array($resultado->resultado_dados) ? $resultado->resultado_dados : [];
        $blocos = [];

        if ($cadastro = $this->blocoCadastro($dados)) {
            $blocos[] = $cadastro;
        }

        foreach (self::ORDEM as $chave) {
            if (! array_key_exists($chave, $dados) || ! is_array($dados[$chave]) || empty($dados[$chave])) {
                continue;
            }

            $bloco = match ($chave) {
                'cgu_cnc' => $this->blocoSancoes($dados[$chave]),
                'cnj_improbidade' => $this->blocoImprobidade($dados[$chave]),
                'sintegra' => $this->blocoSintegra($dados[$chave]),
                default => $this->blocoCertidao($chave, $dados[$chave]),
            };

            if ($bloco) {
                $blocos[] = $bloco;
            }
        }

        return $blocos;
    }

    private function nomeFonte(string $chave): string
    {
        return (string) config("consultas.fonte_nome.{$chave}", $chave);
    }

    private function bloco(string $chave, string $titulo, ?array $badge, array $itens, array $listas = [], ?string $mensagem = null, ?string $comprovante = null): array
    {
        return [
            'chave' => $chave,
            'titulo' => $titulo,
            'badge' => $badge,
            'itens' => array_values(array_filter($itens, fn ($i) => ($i['valor'] ?? null) !== null && $i['valor'] !== '')),
            'listas' => array_values(array_filter($listas, fn ($l) => ! empty($l['linhas']))),
            'mensagem' => $this->limpar($mensagem),
            'comprovante_url' => $this->urlValida($comprovante),
        ];
    }

    private function item(string $label, mixed $valor, ?string $tooltip = null): array
    {
        return ['label' => $label, 'valor' => $this->texto($valor), 'tooltip' => $this->limpar($tooltip)];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cadastro (minhareceita)
    // ──────────────────────────────────────────────────────────────────────────

    private function blocoCadastro(array $d): ?array
    {
        $temCadastro = isset($d['razao_social']) || isset($d['situacao_cadastral']) || isset($d['cnaes']) || isset($d['qsa']);
        if (! $temCadastro) {
            return null;
        }

        $situacao = trim((string) ($d['situacao_cadastral'] ?? ''));
        $motivo = trim((string) ($d['motivo_situacao_cadastral'] ?? ''));

        $itens = [
            $this->item('Nome fantasia', $d['nome_fantasia'] ?? null),
            $this->item('Situação cadastral', $situacao !== '' ? $situacao : null, $motivo !== '' ? "Motivo: {$motivo}" : null),
            $this->item('Natureza jurídica', $d['natureza_juridica'] ?? null),
            $this->item('Porte', $d['porte'] ?? null),
            $this->item('Capital social', $this->moeda($d['capital_social'] ?? null)),
            $this->item('Matriz/Filial', isset($d['matriz_filial']) ? ucfirst((string) $d['matriz_filial']) : null),
            $this->item('Início de atividade', $d['data_inicio_atividade'] ?? null),
            $this->item('Regime tributário', $d['regime_tributario'] ?? null),
            $this->item('Endereço', $this->endereco($d['endereco'] ?? null)),
            $this->item('Telefone', $d['telefone_1'] ?? null),
        ];

        $listas = [
            $this->lista('CNAEs', array_map(function ($c) {
                $cod = trim((string) ($c['codigo'] ?? ''));
                $desc = trim((string) ($c['descricao'] ?? ''));
                $marca = ! empty($c['principal']) ? ' (principal)' : '';

                return trim(($cod !== '' ? "{$cod} — " : '').$desc).$marca;
            }, is_array($d['cnaes'] ?? null) ? $d['cnaes'] : [])),
            $this->lista('Quadro societário (QSA)', array_map(function ($s) {
                $nome = trim((string) ($s['nome'] ?? ''));
                $qual = trim((string) ($s['qualificacao'] ?? ''));
                $entrada = trim((string) ($s['data_entrada'] ?? ''));
                $extra = array_filter([$qual, $entrada !== '' ? "desde {$entrada}" : '']);

                return $nome.(! empty($extra) ? ' — '.implode(', ', $extra) : '');
            }, is_array($d['qsa'] ?? null) ? $d['qsa'] : [])),
            $this->lista('Histórico de regime (RFB)', array_map(function ($r) {
                $ano = trim((string) ($r['ano'] ?? ''));
                $forma = trim((string) ($r['forma'] ?? ''));

                return trim("{$ano} — {$forma}", ' —');
            }, is_array($d['regime_tributario_historico'] ?? null) ? $d['regime_tributario_historico'] : [])),
        ];

        return $this->bloco('cadastro', 'Dados cadastrais', null, $itens, $listas);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Certidões (CND Federal/Estadual/Municipal, FGTS, CNDT)
    // ──────────────────────────────────────────────────────────────────────────

    private function blocoCertidao(string $chave, array $d): array
    {
        $badge = CertidaoBadge::classificar($d, $chave === 'cnd_federal');

        $itens = [
            $this->item('Situação informada', $d['status'] ?? null),
            $this->item('UF', $d['uf'] ?? null),
            $this->item('Município', $d['municipio'] ?? null),
            $this->item('Certidão nº', $d['certidao_codigo'] ?? null),
            $this->item('Emissão', $d['emissao_data'] ?? null),
            $this->item('Validade', $d['data_validade'] ?? null),
        ];

        if ($chave === 'cnd_federal') {
            $itens[] = $this->item('Débitos RFB', $this->simNao($d['debitos_rfb'] ?? null));
            $itens[] = $this->item('Débitos PGFN', $this->simNao($d['debitos_pgfn'] ?? null));
        }

        $mensagem = $d['mensagem'] ?? ($badge['motivo'] ?? null);

        return $this->bloco($chave, $this->tituloCertidao($chave), $badge, $itens, [], $mensagem);
    }

    private function tituloCertidao(string $chave): string
    {
        return match ($chave) {
            'cnd_federal' => 'CND Federal (Receita/PGFN)',
            'cnd_estadual' => 'CND Estadual (SEFAZ)',
            'cnd_municipal' => 'CND Municipal',
            'crf_fgts' => 'CRF FGTS (Caixa)',
            'cndt' => 'CNDT (débitos trabalhistas)',
            default => $this->nomeFonte($chave),
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SINTEGRA
    // ──────────────────────────────────────────────────────────────────────────

    private function blocoSintegra(array $d): array
    {
        $badge = CertidaoBadge::classificar(['situacao' => $d['situacao'] ?? null]);

        $itens = [
            $this->item('Situação', $d['situacao'] ?? null),
            $this->item('Inscrição estadual', $d['inscricao_estadual'] ?? null),
            $this->item('UF', $d['uf'] ?? null),
            $this->item('Regime de apuração', $d['regime_apuracao'] ?? null),
            $this->item('Atividade econômica', $d['atividade_economica'] ?? null),
            $this->item('Data da situação', $d['data_situacao'] ?? null),
        ];

        return $this->bloco('sintegra', 'SINTEGRA', $badge, $itens, [], $d['mensagem'] ?? null);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sanções (CGU CNC)
    // ──────────────────────────────────────────────────────────────────────────

    private function blocoSancoes(array $d): array
    {
        $possui = (bool) ($d['possui_sancao'] ?? false);
        $badge = $possui
            ? ['label' => 'Com sanção', 'hex' => CertidaoBadge::HEX_IRREGULAR]
            : ['label' => 'Regular', 'hex' => CertidaoBadge::HEX_REGULAR];

        $comRegistro = array_values(array_filter((array) ($d['bases_com_registro'] ?? [])));

        $itens = [
            $this->item('Sanções encontradas', $possui ? ($comRegistro ? implode(', ', $comRegistro) : 'Sim') : 'Nenhuma'),
            $this->item('Validade', $d['data_validade'] ?? null),
        ];

        $listas = [
            $this->lista('Bases consultadas', array_map(function ($b) {
                $nome = trim((string) ($b['nome'] ?? ''));
                $sit = trim((string) ($b['situacao'] ?? ''));

                return trim("{$nome} — {$sit}", ' —');
            }, is_array($d['bases'] ?? null) ? $d['bases'] : [])),
        ];

        return $this->bloco('cgu_cnc', 'Sanções (CGU)', $badge, $itens, $listas, $d['mensagem'] ?? null, $d['comprovante'] ?? null);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Improbidade (CNJ)
    // ──────────────────────────────────────────────────────────────────────────

    private function blocoImprobidade(array $d): array
    {
        $possui = (bool) ($d['possui_condenacao'] ?? false);
        $badge = $possui
            ? ['label' => 'Com condenação', 'hex' => CertidaoBadge::HEX_IRREGULAR]
            : ['label' => 'Regular', 'hex' => CertidaoBadge::HEX_REGULAR];

        $itens = [
            $this->item('Condenações', (string) ((int) ($d['total_condenacoes'] ?? 0))),
        ];

        $listas = [
            $this->lista('Registros', array_map(function ($c) {
                if (is_string($c)) {
                    return $c;
                }
                if (is_array($c)) {
                    return trim((string) ($c['titulo'] ?? $c['processo'] ?? json_encode($c, JSON_UNESCAPED_UNICODE)));
                }

                return (string) $c;
            }, is_array($d['condenacoes'] ?? null) ? $d['condenacoes'] : [])),
        ];

        return $this->bloco('cnj_improbidade', 'Improbidade (CNJ)', $badge, $itens, $listas, $d['mensagem'] ?? null, $d['comprovante'] ?? null);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function lista(string $titulo, array $linhas): array
    {
        return ['titulo' => $titulo, 'linhas' => array_values(array_filter(array_map('trim', $linhas), fn ($l) => $l !== ''))];
    }

    private function texto(mixed $v): ?string
    {
        if ($v === null || $v === '' || (is_array($v) && empty($v))) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? 'Sim' : 'Não';
        }

        return is_scalar($v) ? trim((string) $v) : null;
    }

    private function simNao(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }

        return $v ? 'Sim' : 'Não';
    }

    private function moeda(mixed $v): ?string
    {
        if ($v === null || $v === '' || ! is_numeric($v)) {
            return null;
        }

        return 'R$ '.number_format((float) $v, 2, ',', '.');
    }

    private function endereco(mixed $e): ?string
    {
        if (! is_array($e)) {
            return null;
        }

        $logradouro = trim(implode(' ', array_filter([
            trim((string) ($e['tipo_logradouro'] ?? '')),
            trim((string) ($e['logradouro'] ?? '')),
        ])));
        $partes = array_filter([
            $logradouro,
            trim((string) ($e['numero'] ?? '')),
            trim((string) ($e['bairro'] ?? '')),
            trim(implode('/', array_filter([trim((string) ($e['municipio'] ?? '')), trim((string) ($e['uf'] ?? ''))]))),
            trim((string) ($e['cep'] ?? '')),
        ], fn ($p) => $p !== '');

        $texto = implode(', ', $partes);

        return $texto !== '' ? $texto : null;
    }

    private function limpar(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }
        $texto = preg_replace('/\s+/u', ' ', trim($texto));

        return $texto !== '' ? $texto : null;
    }

    private function urlValida(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
