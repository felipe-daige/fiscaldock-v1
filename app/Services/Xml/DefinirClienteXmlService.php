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

        return DB::transaction(function () use ($imp, $tipoNota, $docCol, $razaoCol, $ufCol, $ieCol, $clienteCol, $partCol) {
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

                $nota->update([
                    'tipo_nota' => $tipoNota,
                    $clienteCol => $cliente->id,
                    $partCol => null,
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
