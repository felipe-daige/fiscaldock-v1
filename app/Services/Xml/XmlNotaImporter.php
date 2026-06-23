<?php

namespace App\Services\Xml;

use App\Models\Cliente;
use App\Models\Participante;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Support\Facades\DB;

/**
 * Persiste uma NF-e parseada: dedup por (user_id, chave_acesso), grava
 * xml_notas + xml_notas_itens, classifica entrada/saída pela perspectiva do
 * dono e liga emit/dest a participantes (find-or-create) e clientes (match).
 */
class XmlNotaImporter
{
    public function __construct(private EntitlementService $entitlements = new EntitlementService) {}

    /**
     * @param  string|null  $ownerDoc  CNPJ do dono FORÇADO (override manual). Vazio/null = modo
     *                                 AUTO: infere o dono pelo cliente cadastrado que casa.
     * @param  string|null  $ownerLado  'emit'|'dest' = modo CRIAR CLIENTE PELO LADO: esse lado é o
     *                                  dono e vira Cliente (criado se novo); o outro é participante.
     * @return 'novo'|'duplicado'|'duplicado_atualizado'|'sem_dono'
     */
    public function importar(array $parsed, ?string $ownerDoc, XmlImportacao $imp, ?string $ownerLado = null): string
    {
        $userId = (int) $imp->user_id;
        $h = $parsed['header'];
        $ownerDoc = preg_replace('/[^0-9]/', '', (string) $ownerDoc);

        $existente = XmlNota::where('user_id', $userId)
            ->where('chave_acesso', $h['chave_acesso'])
            ->first();

        if ($existente) {
            if (empty($existente->status_autorizacao) && ! empty($h['status_autorizacao'])) {
                $existente->update([
                    'protocolo_autorizacao' => $h['protocolo_autorizacao'],
                    'data_autorizacao' => $h['data_autorizacao'],
                    'status_autorizacao' => $h['status_autorizacao'],
                    'motivo_autorizacao' => $h['motivo_autorizacao'],
                ]);

                return 'duplicado_atualizado';
            }

            return 'duplicado';
        }

        $emitDoc = $h['emit_documento'];
        $destDoc = $h['dest_documento'];

        // Resolve clientes uma vez (reusado p/ classificação e p/ emit/dest_cliente_id).
        $emitCliente = $this->clienteInfo($userId, $emitDoc);
        $destCliente = $this->clienteInfo($userId, $destDoc);

        if ($ownerLado === 'emit' || $ownerLado === 'dest') {
            // Modo "criar cliente pelo lado": o lado escolhido é o dono → vira Cliente
            // (criado se novo, a partir dos dados da nota); o outro lado é participante.
            $lado = $ownerLado;
            $donoAusente = false;
            $donoDoc = $lado === 'emit' ? $emitDoc : $destDoc;
            if (! empty($donoDoc)) {
                $cli = $this->criarClienteDono(
                    $userId, $donoDoc,
                    $lado === 'emit' ? $h['emit_razao_social'] : $h['dest_razao_social'],
                    $lado === 'emit' ? $h['emit_uf'] : $h['dest_uf'],
                    $lado === 'emit' ? $h['emit_ie'] : $h['dest_ie'],
                );
                if ($lado === 'emit') {
                    $emitCliente = $cli;
                } else {
                    $destCliente = $cli;
                }
            }
        } else {
            [$lado, $donoAusente] = $this->resolverDono($ownerDoc, $emitDoc, $destDoc, $emitCliente, $destCliente);
        }

        if ($lado === 'emit') {
            $h['tipo_nota'] = XmlNota::TIPO_SAIDA;
        } elseif ($lado === 'dest') {
            $h['tipo_nota'] = XmlNota::TIPO_ENTRADA;
        }
        // $lado === null → mantém o tpNF cru do parser como fallback.

        // xml_notas.emit_documento/dest_documento são NOT NULL em prod.
        $h['emit_documento'] = $h['emit_documento'] ?? '';
        $h['dest_documento'] = $h['dest_documento'] ?? '';

        return DB::transaction(function () use ($parsed, $h, $userId, $imp, $emitDoc, $destDoc, $emitCliente, $destCliente, $donoAusente, $lado) {
            $payload = $parsed['payload'];
            if ($donoAusente) {
                $payload['_dono_ausente'] = true;
            }

            $clienteDonoId = match ($lado) {
                'emit' => $emitCliente?->id,
                'dest' => $destCliente?->id,
                default => null,
            };

            // Só a CONTRAPARTE (lado não-dono) vira participante. O lado dono é o cliente
            // (vinculado por *_cliente_id), nunca participante. Sem dono na nota ($lado null,
            // raro com cliente obrigatório) → ambos os lados são contrapartes.
            $emitPart = $lado === 'emit' ? null : $this->participante($userId, $clienteDonoId, $emitDoc, $h['emit_razao_social'], $h['emit_uf'], $h['emit_municipio_ibge'], $h['emit_ie'], $imp);
            $destPart = $lado === 'dest' ? null : $this->participante($userId, $clienteDonoId, $destDoc, $h['dest_razao_social'], $h['dest_uf'], $h['dest_municipio_ibge'], $h['dest_ie'], $imp);

            $nota = XmlNota::create(array_merge($h, [
                'user_id' => $userId,
                'importacao_xml_id' => $imp->id,
                'cliente_id' => $imp->cliente_id ?: $clienteDonoId,
                'payload' => $payload,
                'emit_participante_id' => $emitPart?->id,
                'dest_participante_id' => $destPart?->id,
                'emit_cliente_id' => $emitCliente?->id,
                'dest_cliente_id' => $destCliente?->id,
            ]));

            foreach ($parsed['itens'] as $item) {
                XmlNotaItem::create(array_merge($item, [
                    'xml_nota_id' => $nota->id,
                    'user_id' => $userId,
                ]));
            }

            return $donoAusente ? 'sem_dono' : 'novo';
        });
    }

    /**
     * Decide qual lado é a perspectiva (dono). 'emit' => saída, 'dest' => entrada, null => indefinido.
     *
     * Forçado (ownerDoc preenchido): casa o documento com emit/dest; sem casar = dono ausente.
     * Auto (ownerDoc vazio): empresa própria vence; senão o único lado que é cliente cadastrado;
     * dois lados clientes = operação interna pela ótica do emitente; nenhum lado cliente = ausente.
     *
     * @return array{0: 'emit'|'dest'|null, 1: bool} [lado, donoAusente]
     */
    private function resolverDono(string $ownerDoc, ?string $emitDoc, ?string $destDoc, ?object $emitCliente, ?object $destCliente): array
    {
        if ($ownerDoc !== '') {
            if ($ownerDoc === $emitDoc) {
                return ['emit', false];
            }
            if ($ownerDoc === $destDoc) {
                return ['dest', false];
            }
            // Âncora ausente nesta nota → cai pro AUTO abaixo (resolve por outro cliente
            // cadastrado / empresa própria; nenhum cadastrado → sem_dono no final).
        }

        $emitPropria = (bool) ($emitCliente->is_empresa_propria ?? false);
        $destPropria = (bool) ($destCliente->is_empresa_propria ?? false);

        if ($emitPropria && ! $destPropria) {
            return ['emit', false];
        }
        if ($destPropria && ! $emitPropria) {
            return ['dest', false];
        }
        if ($emitCliente && ! $destCliente) {
            return ['emit', false];
        }
        if ($destCliente && ! $emitCliente) {
            return ['dest', false];
        }
        if ($emitCliente && $destCliente) {
            return ['emit', false]; // operação interna: ótica do emitente
        }

        return [null, true]; // nenhum lado é cliente cadastrado
    }

    private function participante(int $userId, ?int $clienteId, ?string $doc, ?string $razao, ?string $uf, ?string $mun, ?string $ie, XmlImportacao $imp): ?Participante
    {
        if (empty($doc)) {
            return null; // dest exterior / sem documento
        }
        $tipoDoc = strlen($doc) === 11 ? 'CPF' : 'CNPJ';

        $participante = Participante::firstOrNew(['user_id' => $userId, 'documento' => $doc]);

        if (! $participante->exists) {
            $participante->fill([
                'razao_social' => $razao,
                'uf' => $uf,
                'codigo_municipal' => $mun,
                'inscricao_estadual' => $ie,
                'origem_tipo' => 'xml',
                'importacao_xml_id' => $imp->id,
                'tipo_documento' => $tipoDoc,
            ]);
        }

        // Fill-only: nunca sobrescreve um vínculo já existente. Uma contraparte pode servir
        // a vários clientes, mas a coluna guarda 1 — preservamos o primeiro (first-writer-wins).
        if ($clienteId && empty($participante->cliente_id)) {
            $participante->cliente_id = $clienteId;
        }

        if (! $participante->importacao_xml_id) {
            $participante->importacao_xml_id = $imp->id;
        }

        if (! $participante->origem_tipo) {
            $participante->origem_tipo = 'xml';
        }

        if ($participante->isDirty()) {
            $participante->save();
        }

        return $participante;
        // NÃO atualizamos situacao_cadastral/regime_tributario/ultima_consulta_em (regra do projeto).
    }

    /**
     * Find-or-create do Cliente do lado dono (modo "criar cliente pelo lado"), com os dados
     * da nota. Espelha o find-or-create do participante, mas grava em `clientes`.
     */
    private function criarClienteDono(int $userId, string $doc, ?string $razao, ?string $uf, ?string $ie): ?Cliente
    {
        // null = cap do tier atingido → o lado dono não vira cliente (nota fica sem dono).
        return $this->entitlements->firstOrCreateClienteComCap($userId, $doc, [
            'tipo_pessoa' => strlen($doc) === 11 ? 'PF' : 'PJ',
            'razao_social' => $razao,
            'nome' => $razao,
            'uf' => $uf,
            'inscricao_estadual' => $ie,
            'ativo' => true,
        ]);
    }

    /** Cliente do usuário que casa com o documento (id + flag empresa própria), ou null. */
    private function clienteInfo(int $userId, ?string $doc): ?object
    {
        if (empty($doc)) {
            return null;
        }

        return Cliente::where('user_id', $userId)
            ->where('documento', $doc)
            ->first(['id', 'is_empresa_propria']);
    }
}
