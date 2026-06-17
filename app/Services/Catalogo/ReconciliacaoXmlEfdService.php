<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Facades\DB;

/**
 * Reconciliação nota-level XML × EFD por chave_acesso (declarado × documentado).
 * Grão de NOTA — o NotaItemUnificadoService é grão de item. Cálculo on-demand, sem persistência.
 */
class ReconciliacaoXmlEfdService
{
    private const TOLERANCIA = 0.01;

    public function __construct(
        private NotaItemUnificadoService $unificado,
        private AlertaCatalogoDescarteService $descartes,
    ) {}

    /**
     * @param  array{periodo_de?:string,periodo_ate?:string,cliente_id?:int}  $filtros
     * @return array{documentadas:int,reconciliadas:int,divergencia_total:int,nao_declaradas:int,efd_sem_xml:int}
     */
    public function resumo(int $userId, array $filtros = []): array
    {
        $bind = ['uid' => $userId];
        $xmlWhere = '';
        $efdWhere = '';
        if (! empty($filtros['cliente_id'])) {
            $bind['cli'] = (int) $filtros['cliente_id'];
            $xmlWhere .= ' AND xn.cliente_id = :cli';
            $efdWhere .= ' AND en.cliente_id = :cli';
        }
        if (! empty($filtros['periodo_de'])) {
            $bind['de'] = $filtros['periodo_de'];
            $xmlWhere .= ' AND xn.data_emissao >= :de';
            $efdWhere .= ' AND en.data_emissao >= :de';
        }
        if (! empty($filtros['periodo_ate'])) {
            $bind['ate'] = $filtros['periodo_ate'];
            $xmlWhere .= ' AND xn.data_emissao <= :ate';
            $efdWhere .= ' AND en.data_emissao <= :ate';
        }

        $linhas = DB::select("
            WITH xml_tot AS (
                SELECT xn.chave_acesso, SUM(xn.valor_total) AS xml_total
                FROM xml_notas xn
                WHERE xn.user_id = :uid AND xn.chave_acesso IS NOT NULL{$xmlWhere}
                GROUP BY xn.chave_acesso
            ),
            efd_tot AS (
                SELECT en.chave_acesso,
                       COALESCE(
                           MAX(en.valor_total) FILTER (WHERE en.origem_arquivo = 'fiscal'),
                           MAX(en.valor_total)
                       ) AS efd_total
                FROM efd_notas en
                WHERE en.user_id = :uid AND en.chave_acesso IS NOT NULL AND en.cancelada = false
                GROUP BY en.chave_acesso
            )
            SELECT x.chave_acesso, x.xml_total, e.efd_total
            FROM xml_tot x
            LEFT JOIN efd_tot e ON e.chave_acesso = x.chave_acesso
        ", $bind);

        $documentadas = count($linhas);
        $reconciliadas = 0;
        $divergencia = 0;
        $naoDeclaradas = 0;
        foreach ($linhas as $l) {
            if ($l->efd_total === null) {
                $naoDeclaradas++;
            } elseif (round(abs((float) $l->xml_total - (float) $l->efd_total), 6) <= self::TOLERANCIA) {
                $reconciliadas++;
            } else {
                $divergencia++;
            }
        }

        $efdSemXml = (int) (DB::selectOne("
            SELECT COUNT(*) AS c FROM (
                SELECT DISTINCT en.chave_acesso
                FROM efd_notas en
                WHERE en.user_id = :uid AND en.chave_acesso IS NOT NULL AND en.cancelada = false{$efdWhere}
                AND NOT EXISTS (
                    SELECT 1 FROM xml_notas xn
                    WHERE xn.user_id = :uid AND xn.chave_acesso = en.chave_acesso
                )
            ) t
        ", $bind)->c ?? 0);

        return [
            'documentadas' => $documentadas,
            'reconciliadas' => $reconciliadas,
            'divergencia_total' => $divergencia,
            'nao_declaradas' => $naoDeclaradas,
            'efd_sem_xml' => $efdSemXml,
        ];
    }

    /**
     * Compõe os baldes nota-level com contagens de item (divergência NCM, sem-catálogo) pro card do clearance.
     *
     * @return array{ncm_revisar_qtd:int,sem_catalogo_qtd:int,nao_declaradas_qtd:int,temSinal:bool}
     */
    public function resumoAlertas(int $userId): array
    {
        $recon = $this->resumo($userId);

        $descNcm = $this->descartes->descartados($userId, 'ncm_divergente');
        $descSem = $this->descartes->descartados($userId, 'sem_catalogo');

        $ncmRevisar = collect($this->unificado->divergenciasNcmPorItem($userId))
            ->filter(fn ($d) => $d['ncm_divergente'])
            ->reject(fn ($d, $cod) => in_array((string) $cod, $descNcm, true))
            ->count();

        $semCatalogo = $this->unificado->itensSemCatalogo($userId)
            ->reject(fn ($i) => in_array($i['codigo_item'], $descSem, true))
            ->count();

        $naoDeclaradas = $recon['nao_declaradas'];

        return [
            'ncm_revisar_qtd' => $ncmRevisar,
            'sem_catalogo_qtd' => $semCatalogo,
            'nao_declaradas_qtd' => $naoDeclaradas,
            'temSinal' => ($ncmRevisar + $semCatalogo + $naoDeclaradas) > 0,
        ];
    }
}
