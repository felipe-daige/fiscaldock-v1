<?php

namespace App\Services;

use App\Models\PrivCpfCadastro;
use App\Models\PrivCpfOperacao;
use App\Models\PrivCpfRelacionamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrivCpfDataService
{
    /**
     * Insere ou atualiza cadastro de CPF (só preenche campos vazios).
     * Usa lógica de COALESCE: só atualiza se o valor atual for NULL e o novo valor for NOT NULL.
     *
     * @param array $data Dados do cadastro (cpf, nome, uf, endereco, etc)
     * @return PrivCpfCadastro
     */
    public function upsertCadastro(array $data): PrivCpfCadastro
    {
        $cpf = preg_replace('/\D/', '', $data['cpf'] ?? '');

        if (empty($cpf) || strlen($cpf) !== 11) {
            throw new \InvalidArgumentException('CPF inválido: deve conter 11 dígitos');
        }

        // Normalizar dados
        $normalized = [
            'cpf' => $cpf,
            'nome' => $data['nome'] ?? null,
            'cod_pais' => $data['cod_pais'] ?? '1058',
            'uf' => $data['uf'] ?? null,
            'codigo_municipal' => $data['codigo_municipal'] ?? null,
            'municipio_nome' => $data['municipio_nome'] ?? null,
            'cep' => preg_replace('/\D/', '', $data['cep'] ?? '') ?: null,
            'bairro' => $data['bairro'] ?? null,
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'inscricao_estadual' => $data['inscricao_estadual'] ?? null,
            'suframa' => $data['suframa'] ?? null,
        ];

        // Usar UPSERT com COALESCE via SQL raw para PostgreSQL
        $sql = "
            INSERT INTO priv_cpf_cadastro (
                cpf, nome, cod_pais, uf, codigo_municipal, municipio_nome,
                cep, bairro, endereco, numero, complemento,
                inscricao_estadual, suframa, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON CONFLICT (cpf) DO UPDATE SET
                nome = COALESCE(priv_cpf_cadastro.nome, EXCLUDED.nome),
                cod_pais = COALESCE(priv_cpf_cadastro.cod_pais, EXCLUDED.cod_pais),
                uf = COALESCE(priv_cpf_cadastro.uf, EXCLUDED.uf),
                codigo_municipal = COALESCE(priv_cpf_cadastro.codigo_municipal, EXCLUDED.codigo_municipal),
                municipio_nome = COALESCE(priv_cpf_cadastro.municipio_nome, EXCLUDED.municipio_nome),
                cep = COALESCE(priv_cpf_cadastro.cep, EXCLUDED.cep),
                bairro = COALESCE(priv_cpf_cadastro.bairro, EXCLUDED.bairro),
                endereco = COALESCE(priv_cpf_cadastro.endereco, EXCLUDED.endereco),
                numero = COALESCE(priv_cpf_cadastro.numero, EXCLUDED.numero),
                complemento = COALESCE(priv_cpf_cadastro.complemento, EXCLUDED.complemento),
                inscricao_estadual = COALESCE(priv_cpf_cadastro.inscricao_estadual, EXCLUDED.inscricao_estadual),
                suframa = COALESCE(priv_cpf_cadastro.suframa, EXCLUDED.suframa),
                updated_at = NOW()
        ";

        DB::statement($sql, [
            $normalized['cpf'],
            $normalized['nome'],
            $normalized['cod_pais'],
            $normalized['uf'],
            $normalized['codigo_municipal'],
            $normalized['municipio_nome'],
            $normalized['cep'],
            $normalized['bairro'],
            $normalized['endereco'],
            $normalized['numero'],
            $normalized['complemento'],
            $normalized['inscricao_estadual'],
            $normalized['suframa'],
        ]);

        return PrivCpfCadastro::where('cpf', $cpf)->firstOrFail();
    }

    /**
     * Registra uma operação/documento fiscal.
     * Valida se nfe_id já existe para evitar duplicatas.
     *
     * @param array $data Dados da operação
     * @return PrivCpfOperacao|null Retorna null se nfe_id já existe
     */
    public function registrarOperacao(array $data): ?PrivCpfOperacao
    {
        // Validar se nfe_id já existe (se fornecida)
        if (!empty($data['nfe_id'])) {
            $exists = PrivCpfOperacao::where('nfe_id', $data['nfe_id'])->exists();
            if ($exists) {
                Log::debug('Operação já registrada (nfe_id duplicada)', [
                    'nfe_id' => $data['nfe_id'],
                ]);
                return null;
            }
        }

        // Normalizar valores decimais
        $normalized = $data;
        foreach (['valor_total', 'valor_mercadorias', 'valor_frete', 'valor_desconto'] as $field) {
            if (isset($normalized[$field])) {
                $normalized[$field] = is_numeric($normalized[$field]) ? (float) $normalized[$field] : null;
            }
        }

        // Normalizar CNPJ
        if (isset($normalized['cnpj_empresa'])) {
            $normalized['cnpj_empresa'] = preg_replace('/\D/', '', $normalized['cnpj_empresa']);
        }

        return PrivCpfOperacao::create($normalized);
    }

    /**
     * Atualiza ou cria relacionamento CPF ↔ CNPJ (incrementa contadores).
     *
     * @param int $cpfId ID do cadastro CPF
     * @param string $cnpj CNPJ do parceiro comercial
     * @param string $razaoSocial Nome da empresa
     * @param string $tipoRelacao 'FORNECEDOR', 'CLIENTE', 'TRANSPORTADOR'
     * @param float $valor Valor da operação
     * @param string $dataOperacao Data da operação (formato Y-m-d)
     * @return PrivCpfRelacionamento
     */
    public function atualizarRelacionamento(
        int $cpfId,
        string $cnpj,
        string $razaoSocial,
        string $tipoRelacao,
        float $valor,
        string $dataOperacao
    ): PrivCpfRelacionamento {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (empty($cnpj) || strlen($cnpj) !== 14) {
            throw new \InvalidArgumentException('CNPJ inválido: deve conter 14 dígitos');
        }

        if (!in_array($tipoRelacao, ['FORNECEDOR', 'CLIENTE', 'TRANSPORTADOR'], true)) {
            throw new \InvalidArgumentException('Tipo de relação inválido: ' . $tipoRelacao);
        }

        // Validar formato da data
        $dataObj = \DateTime::createFromFormat('Y-m-d', $dataOperacao);
        if (!$dataObj) {
            throw new \InvalidArgumentException('Data inválida: ' . $dataOperacao);
        }

        // Usar UPSERT com incremento via SQL raw para PostgreSQL
        $sql = "
            INSERT INTO priv_cpf_relacionamentos (
                cpf_id, cnpj, razao_social, tipo_relacao,
                total_operacoes, valor_total, primeira_operacao, ultima_operacao,
                created_at, updated_at
            )
            VALUES (?, ?, ?, ?, 1, ?, ?, ?, NOW(), NOW())
            ON CONFLICT (cpf_id, cnpj, tipo_relacao) DO UPDATE SET
                total_operacoes = priv_cpf_relacionamentos.total_operacoes + 1,
                valor_total = priv_cpf_relacionamentos.valor_total + EXCLUDED.valor_total,
                primeira_operacao = COALESCE(
                    LEAST(priv_cpf_relacionamentos.primeira_operacao, EXCLUDED.primeira_operacao),
                    EXCLUDED.primeira_operacao
                ),
                ultima_operacao = GREATEST(priv_cpf_relacionamentos.ultima_operacao, EXCLUDED.ultima_operacao),
                razao_social = COALESCE(EXCLUDED.razao_social, priv_cpf_relacionamentos.razao_social),
                updated_at = NOW()
        ";

        DB::statement($sql, [
            $cpfId,
            $cnpj,
            $razaoSocial ?: null,
            $tipoRelacao,
            $valor,
            $dataOperacao,
            $dataOperacao,
        ]);

        return PrivCpfRelacionamento::where('cpf_id', $cpfId)
            ->where('cnpj', $cnpj)
            ->where('tipo_relacao', $tipoRelacao)
            ->firstOrFail();
    }

    /**
     * Processa um participante completo do EFD (cadastro + operação + relacionamento).
     * Método de conveniência que orquestra os 3 anteriores.
     *
     * @param array $participante Dados do registro 0150 (cadastro)
     * @param array $operacao Dados do documento fiscal (C100, C170, D100)
     * @return void
     */
    public function processarParticipanteEfd(array $participante, array $operacao): void
    {
        try {
            // 1. Upsert cadastro
            $cadastro = $this->upsertCadastro($participante);

            // 2. Registrar operação
            $operacao['cpf_id'] = $cadastro->id;
            $operacaoRegistrada = $this->registrarOperacao($operacao);

            // Se a operação já existia (nfe_id duplicada), não atualiza relacionamento
            if (!$operacaoRegistrada) {
                return;
            }

            // 3. Atualizar relacionamento
            $cnpjEmpresa = preg_replace('/\D/', '', $operacao['cnpj_empresa'] ?? '');
            $tipoRelacao = $this->mapearTipoParticipacao($operacao['tipo_participacao'] ?? '');
            $valor = (float) ($operacao['valor_total'] ?? 0);
            $dataOperacao = $operacao['data_operacao'] ?? $operacao['data_emissao'] ?? date('Y-m-d');

            if (!empty($cnpjEmpresa) && !empty($tipoRelacao) && $valor > 0) {
                $this->atualizarRelacionamento(
                    $cadastro->id,
                    $cnpjEmpresa,
                    $operacao['razao_social_empresa'] ?? '',
                    $tipoRelacao,
                    $valor,
                    $dataOperacao
                );
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar participante EFD', [
                'participante' => $participante,
                'operacao' => $operacao,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Mapeia tipo_participacao para tipo_relacao.
     *
     * @param string $tipoParticipacao
     * @return string
     */
    private function mapearTipoParticipacao(string $tipoParticipacao): string
    {
        $tipo = strtoupper(trim($tipoParticipacao));

        return match ($tipo) {
            'FORNECEDOR', 'FORN' => 'FORNECEDOR',
            'CLIENTE', 'CLI' => 'CLIENTE',
            'TRANSPORTADOR', 'TRANSP' => 'TRANSPORTADOR',
            default => 'CLIENTE', // Default
        };
    }
}

