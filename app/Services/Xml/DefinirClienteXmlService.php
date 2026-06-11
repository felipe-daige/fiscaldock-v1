<?php

namespace App\Services\Xml;

use App\Models\Cliente;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use Illuminate\Support\Facades\DB;

/**
 * "Decidir depois": reclassifica uma importação XML que rodou sem cliente, definindo
 * qual lado (emit/dest) é o cliente. Cria/vincula o Cliente desse lado a partir dos dados
 * da nota, marca a contraparte como participante e remove o participante provisório do
 * dono se ficar órfão. Espelha o modo `ownerLado` do XmlNotaImporter, mas pós-importação.
 */
class DefinirClienteXmlService
{
    /**
     * Candidatos por lado (parte dominante + nº de distintos), para a tela escolher.
     *
     * @return array{emit: array, dest: array}
     */
    public function candidatos(XmlImportacao $imp): array
    {
        return [
            'emit' => $this->ladoCandidato($imp, 'emit'),
            'dest' => $this->ladoCandidato($imp, 'dest'),
        ];
    }

    /**
     * @return array{participantes_removidos:int, notas:int, cliente_id:?int}
     */
    public function execute(XmlImportacao $imp, string $lado): array
    {
        $tipoNota = $lado === 'emit' ? XmlNota::TIPO_SAIDA : XmlNota::TIPO_ENTRADA;
        $docCol = $lado === 'emit' ? 'emit_documento' : 'dest_documento';
        $razaoCol = $lado === 'emit' ? 'emit_razao_social' : 'dest_razao_social';
        $ufCol = $lado === 'emit' ? 'emit_uf' : 'dest_uf';
        $ieCol = $lado === 'emit' ? 'emit_ie' : 'dest_ie';
        $clienteCol = $lado === 'emit' ? 'emit_cliente_id' : 'dest_cliente_id';
        $partCol = $lado === 'emit' ? 'emit_participante_id' : 'dest_participante_id';
        $contraparteDocCol = $lado === 'emit' ? 'dest_documento' : 'emit_documento';
        $contraparteRazaoCol = $lado === 'emit' ? 'dest_razao_social' : 'emit_razao_social';
        $contraparteUfCol = $lado === 'emit' ? 'dest_uf' : 'emit_uf';
        $contraparteMunCol = $lado === 'emit' ? 'dest_municipio_ibge' : 'emit_municipio_ibge';
        $contraparteIeCol = $lado === 'emit' ? 'dest_ie' : 'emit_ie';
        $contrapartePartCol = $lado === 'emit' ? 'dest_participante_id' : 'emit_participante_id';

        return DB::transaction(function () use ($imp, $tipoNota, $docCol, $razaoCol, $ufCol, $ieCol, $clienteCol, $partCol, $contraparteDocCol, $contraparteRazaoCol, $contraparteUfCol, $contraparteMunCol, $contraparteIeCol, $contrapartePartCol) {
            $userId = (int) $imp->user_id;
            $clienteIds = [];
            $donoPartIds = [];

            $notas = XmlNota::where('importacao_xml_id', $imp->id)->where('user_id', $userId)->get();
            foreach ($notas as $nota) {
                $doc = preg_replace('/\D/', '', (string) $nota->{$docCol});
                if ($doc === '') {
                    continue;
                }

                $cliente = Cliente::firstOrCreate(
                    ['user_id' => $userId, 'documento' => $doc],
                    [
                        'tipo_pessoa' => strlen($doc) === 11 ? 'PF' : 'PJ',
                        'razao_social' => $nota->{$razaoCol},
                        'nome' => $nota->{$razaoCol},
                        'uf' => $nota->{$ufCol},
                        'inscricao_estadual' => $nota->{$ieCol},
                        'ativo' => true,
                        'is_empresa_propria' => false,
                    ]
                );
                $clienteIds[] = $cliente->id;
                if ($nota->{$partCol}) {
                    $donoPartIds[] = (int) $nota->{$partCol};
                }

                $contraparte = $this->participanteContraparte(
                    $userId,
                    $cliente->id,
                    $nota->{$contraparteDocCol},
                    $nota->{$contraparteRazaoCol},
                    $nota->{$contraparteUfCol},
                    $nota->{$contraparteMunCol},
                    $nota->{$contraparteIeCol},
                    $imp
                );

                $nota->update([
                    'tipo_nota' => $tipoNota,
                    $clienteCol => $cliente->id,
                    $partCol => null,
                    $contrapartePartCol => $contraparte?->id,
                    'cliente_id' => $cliente->id,
                ]);
            }

            // cliente da importação = o dono mais comum (pro histórico)
            $clienteImportacao = collect($clienteIds)->countBy()->sortDesc()->keys()->first();
            $imp->cliente_id = $clienteImportacao;

            // recompõe participante_ids (sem o lado dono) e limpa órfãos provisórios
            $imp->participante_ids = XmlNota::where('importacao_xml_id', $imp->id)
                ->get(['emit_participante_id', 'dest_participante_id'])
                ->flatMap(fn ($n) => [$n->emit_participante_id, $n->dest_participante_id])
                ->filter()->unique()->values()->all();
            $imp->save();

            $removidos = $this->limparOrfaos($userId, array_values(array_unique($donoPartIds)));

            return ['participantes_removidos' => $removidos, 'notas' => $notas->count(), 'cliente_id' => $clienteImportacao];
        });
    }

    /**
     * "Decidir depois" automático: se exatamente um lado dominante já for Cliente,
     * reclassifica o lote por esse lado. Nenhum ou dois matches mantêm a escolha manual.
     *
     * @return array{lado:string, cliente:Cliente, resultado:array}|null
     */
    public function autoDefinirSeClienteExistente(XmlImportacao $imp): ?array
    {
        $candidatos = $this->candidatos($imp);

        $matches = [];
        foreach (['emit', 'dest'] as $lado) {
            $doc = preg_replace('/\D/', '', (string) ($candidatos[$lado]['documento'] ?? ''));
            if ($doc === '') {
                continue;
            }

            $cliente = Cliente::where('user_id', (int) $imp->user_id)
                ->where('documento', $doc)
                ->first();

            if ($cliente) {
                $matches[$lado] = $cliente;
            }
        }

        if (count($matches) !== 1) {
            return null;
        }

        $lado = array_key_first($matches);

        return [
            'lado' => $lado,
            'cliente' => $matches[$lado],
            'resultado' => $this->execute($imp, $lado),
        ];
    }

    /**
     * Parte dominante de um lado + nº de documentos distintos (pra rotular a escolha).
     */
    private function ladoCandidato(XmlImportacao $imp, string $lado): array
    {
        $docCol = $lado === 'emit' ? 'emit_documento' : 'dest_documento';
        $razaoCol = $lado === 'emit' ? 'emit_razao_social' : 'dest_razao_social';

        $linhas = XmlNota::where('importacao_xml_id', $imp->id)
            ->where('user_id', $imp->user_id)
            ->whereNotNull($docCol)->where($docCol, '!=', '')
            ->selectRaw("$docCol as documento, MAX($razaoCol) as razao, COUNT(*) as qtd")
            ->groupBy($docCol)
            ->orderByDesc('qtd')
            ->get();

        $top = $linhas->first();

        return [
            'documento' => $top->documento ?? null,
            'razao' => $top->razao ?? null,
            'distintos' => $linhas->count(),
        ];
    }

    private function participanteContraparte(
        int $userId,
        int $clienteId,
        ?string $doc,
        ?string $razao,
        ?string $uf,
        ?string $municipioIbge,
        ?string $ie,
        XmlImportacao $imp
    ): ?Participante {
        $doc = preg_replace('/\D/', '', (string) $doc);
        if ($doc === '') {
            return null;
        }

        $participante = Participante::firstOrNew(['user_id' => $userId, 'documento' => $doc]);

        if (! $participante->exists) {
            $participante->fill([
                'razao_social' => $razao,
                'uf' => $uf,
                'codigo_municipal' => $municipioIbge,
                'inscricao_estadual' => $ie,
                'origem_tipo' => 'xml',
                'importacao_xml_id' => $imp->id,
            ]);
        }

        // Fill-only: não rouba uma contraparte já vinculada a outro cliente (ver XmlNotaImporter).
        if (empty($participante->cliente_id)) {
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
    }

    /**
     * Participante (ex-dono) sem nenhuma referência em xml_notas/efd_notas e sem dado pago → apaga.
     */
    private function limparOrfaos(int $userId, array $partIds): int
    {
        $removidos = 0;
        foreach ($partIds as $pid) {
            $emXml = XmlNota::where('user_id', $userId)
                ->where(fn ($q) => $q->where('emit_participante_id', $pid)->orWhere('dest_participante_id', $pid))
                ->exists();
            $emEfd = EfdNota::where('participante_id', $pid)->exists();
            $pago = DB::table('consulta_resultados')->where('participante_id', $pid)->exists()
                || DB::table('monitoramento_consultas')->where('participante_id', $pid)->exists()
                || DB::table('monitoramento_assinaturas')->where('participante_id', $pid)->exists()
                || DB::table('participante_scores')->where('participante_id', $pid)->exists();

            if (! $emXml && ! $emEfd && ! $pago) {
                $removidos += Participante::where('id', $pid)->delete();
            }
        }

        return $removidos;
    }
}
