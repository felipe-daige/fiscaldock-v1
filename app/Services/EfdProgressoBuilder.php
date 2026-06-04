<?php

namespace App\Services;

use App\Models\EfdImportacao;
use Illuminate\Support\Facades\DB;

class EfdProgressoBuilder
{
    private const BLOCOS_PIS_COFINS = [
        'participantes', 'catalogo', 'notas_servicos', 'notas_mercadorias',
        'apuracao_pis_cofins', 'retencoes_fonte',
    ];

    private const BLOCOS_ICMS_IPI = [
        'participantes', 'catalogo', 'notas_mercadorias', 'notas_transportes', 'apuracao_icms',
    ];

    public function build(EfdImportacao $imp): array
    {
        $status = (string) $imp->status;
        $isConcluido = $status === 'concluido';
        $isErro = $status === 'erro';

        $blocosEsperados = $imp->tipo_efd === 'EFD ICMS/IPI'
            ? self::BLOCOS_ICMS_IPI
            : self::BLOCOS_PIS_COFINS;

        $counts = $this->contagens($imp->id);
        $blocos = [];
        $comDados = 0;

        foreach ($blocosEsperados as $bloco) {
            $count = $counts[$bloco] ?? 0;
            if ($count > 0) {
                $blocos[$bloco] = [
                    'status' => $isConcluido ? 'concluido' : 'processando',
                    'count' => $count,
                    'progresso' => 100,
                ];
                $comDados++;
            } elseif ($isConcluido) {
                $blocos[$bloco] = ['status' => 'skip', 'count' => 0, 'progresso' => 0];
            }
        }

        if ($isConcluido) {
            $progresso = 100;
        } elseif ($isErro) {
            $progresso = 0;
        } else {
            $progresso = (int) round(($comDados / count($blocosEsperados)) * 100);
        }

        $resumoFinal = $isConcluido ? (new EfdResumoBuilder)->build($imp) : null;

        return [
            'status' => $status,
            'progresso' => $progresso,
            'mensagem' => $this->mensagem($status, $counts),
            'bloco' => null,
            'dados' => [
                'tipo_documento' => $imp->tipo_efd,
            ],
            'notas_blocos' => $blocos,
            'blocos_esperados' => $blocosEsperados,
            'resumo_final' => $resumoFinal,
        ];
    }

    private function contagens(int $impId): array
    {
        $notasPorModelo = DB::table('efd_notas')
            ->where('importacao_id', $impId)
            ->selectRaw("modelo, COUNT(*) AS qtd")
            ->groupBy('modelo')
            ->pluck('qtd', 'modelo');

        return [
            'participantes' => (int) DB::table('participantes')->where('importacao_efd_id', $impId)->count(),
            'catalogo' => (int) DB::table('efd_catalogo_itens')->where('importacao_id', $impId)->count(),
            'notas_servicos' => (int) ($notasPorModelo['00'] ?? 0),
            'notas_mercadorias' => (int) ($notasPorModelo['55'] ?? 0),
            'notas_transportes' => (int) ($notasPorModelo['57'] ?? 0),
            'apuracao_pis_cofins' => (int) DB::table('efd_apuracoes_contribuicoes')->where('importacao_id', $impId)->count(),
            'apuracao_icms' => (int) DB::table('efd_apuracoes_icms')->where('importacao_id', $impId)->count(),
            'retencoes_fonte' => (int) DB::table('efd_retencoes_fonte')->where('importacao_id', $impId)->count(),
        ];
    }

    private function mensagem(string $status, array $counts): string
    {
        if ($status === 'concluido') {
            return 'Importacao concluida.';
        }
        if ($status === 'erro') {
            return 'Importacao interrompida.';
        }

        $partes = [];
        if (($counts['participantes'] ?? 0) > 0) {
            $partes[] = $counts['participantes'].' participantes';
        }
        $totalNotas = ($counts['notas_servicos'] ?? 0) + ($counts['notas_mercadorias'] ?? 0) + ($counts['notas_transportes'] ?? 0);
        if ($totalNotas > 0) {
            $partes[] = $totalNotas.' notas';
        }
        if (($counts['catalogo'] ?? 0) > 0) {
            $partes[] = $counts['catalogo'].' itens de catalogo';
        }

        return $partes === [] ? 'Iniciando importacao...' : 'Importando: '.implode(', ', $partes).'.';
    }
}
