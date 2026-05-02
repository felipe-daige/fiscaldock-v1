<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detecta divergências entre o catálogo (efd_catalogo_itens / registro 0200)
 * e os itens efetivamente declarados em notas (XML+EFD via service unificado).
 *
 * Quatro tipos de alerta:
 *  - sem_catalogo:   nota declara codigo_item que NÃO está em efd_catalogo_itens.
 *  - ncm_divergente: NCM declarado nas notas ≠ NCM cadastrado no catálogo.
 *  - unidade_divergente: unidade_medida da nota ≠ unid_inv do catálogo.
 *  - aliquota_incompativel: aliquota_icms declarada ≠ aliq_icms cadastrada
 *    (tolerância de 0.5pp; abaixo disso é ruído de arredondamento).
 */
final class AlertasCatalogoService
{
    public const TOLERANCIA_ALIQUOTA_PP = 0.5;

    public const TIPO_SEM_CATALOGO = 'sem_catalogo';

    public const TIPO_NCM_DIVERGENTE = 'ncm_divergente';

    public const TIPO_UNIDADE_DIVERGENTE = 'unidade_divergente';

    public const TIPO_ALIQUOTA_INCOMPATIVEL = 'aliquota_incompativel';

    public function __construct(private readonly NotaItemUnificadoService $itens) {}

    /**
     * Lista completa de alertas, um por (codigo_item, tipo).
     *
     * @param  array{data_inicio?: ?string, data_fim?: ?string, cliente_id?: ?int, tipo_operacao?: ?string}  $filtros
     */
    public function gerar(int $userId, array $filtros = []): Collection
    {
        $linhas = $this->itens->itensUnificados($userId, $filtros);
        if ($linhas->isEmpty()) {
            return collect();
        }

        $catalogo = $this->indexarCatalogo($userId);
        $alertas = collect();

        foreach ($linhas->groupBy('codigo_item') as $codigo => $grupo) {
            $codigo = (string) $codigo;
            $cat = $catalogo[$codigo] ?? null;

            if ($cat === null) {
                $alertas->push($this->montarAlerta(
                    self::TIPO_SEM_CATALOGO,
                    $codigo,
                    $grupo,
                    cadastro: null,
                    detalhe: 'Item declarado em notas sem registro no catálogo (0200).'
                ));

                continue;
            }

            // NCM divergente — NCM cadastrado existe, NCM declarado existe, e diferem
            $ncmsNotas = $grupo->pluck('ncm')->filter()->unique();
            if ($cat->cod_ncm && $ncmsNotas->isNotEmpty() && ! $ncmsNotas->contains($cat->cod_ncm)) {
                $alertas->push($this->montarAlerta(
                    self::TIPO_NCM_DIVERGENTE,
                    $codigo,
                    $grupo,
                    cadastro: ['ncm' => $cat->cod_ncm],
                    detalhe: "NCM cadastrado: {$cat->cod_ncm} | declarado nas notas: ".$ncmsNotas->implode(', ')
                ));
            }

            // Unidade divergente
            $unidadesNotas = $grupo->pluck('unidade_medida')->filter()->unique();
            if ($cat->unid_inv && $unidadesNotas->isNotEmpty() && ! $unidadesNotas->contains($cat->unid_inv)) {
                $alertas->push($this->montarAlerta(
                    self::TIPO_UNIDADE_DIVERGENTE,
                    $codigo,
                    $grupo,
                    cadastro: ['unidade' => $cat->unid_inv],
                    detalhe: "Unidade cadastrada: {$cat->unid_inv} | declarada: ".$unidadesNotas->implode(', ')
                ));
            }

            // Alíquota incompatível — só compara se ambos lados têm valor
            if ($cat->aliq_icms !== null) {
                $aliqMedia = $this->aliquotaMediaPonderada($grupo);
                if ($aliqMedia !== null && abs((float) $cat->aliq_icms - $aliqMedia) > self::TOLERANCIA_ALIQUOTA_PP) {
                    $alertas->push($this->montarAlerta(
                        self::TIPO_ALIQUOTA_INCOMPATIVEL,
                        $codigo,
                        $grupo,
                        cadastro: ['aliquota' => (float) $cat->aliq_icms],
                        detalhe: sprintf(
                            'Alíquota cadastrada: %s%% | média ponderada nas notas: %s%% (tolerância: %s pp)',
                            number_format((float) $cat->aliq_icms, 2, ',', '.'),
                            number_format($aliqMedia, 2, ',', '.'),
                            number_format(self::TOLERANCIA_ALIQUOTA_PP, 1, ',', '.')
                        )
                    ));
                }
            }
        }

        return $alertas;
    }

    /**
     * Resumo por tipo: { tipo => count }.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function resumo(int $userId, array $filtros = []): array
    {
        $alertas = $this->gerar($userId, $filtros);

        return [
            self::TIPO_SEM_CATALOGO => $alertas->where('tipo', self::TIPO_SEM_CATALOGO)->count(),
            self::TIPO_NCM_DIVERGENTE => $alertas->where('tipo', self::TIPO_NCM_DIVERGENTE)->count(),
            self::TIPO_UNIDADE_DIVERGENTE => $alertas->where('tipo', self::TIPO_UNIDADE_DIVERGENTE)->count(),
            self::TIPO_ALIQUOTA_INCOMPATIVEL => $alertas->where('tipo', self::TIPO_ALIQUOTA_INCOMPATIVEL)->count(),
        ];
    }

    /**
     * Retorna o catálogo do usuário indexado por cod_item.
     * Quando há múltiplas versões (importações distintas) usa a mais recente
     * (`ORDER BY id DESC`), conforme regra de fallback documentada em
     * docs/catalogo/integracao-itens-catalogo.md.
     *
     * @return array<string, object>
     */
    private function indexarCatalogo(int $userId): array
    {
        $linhas = DB::table('efd_catalogo_itens')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get(['cod_item', 'cod_ncm', 'aliq_icms', 'unid_inv']);

        $indexado = [];
        foreach ($linhas as $linha) {
            $codigo = (string) $linha->cod_item;
            // Primeira ocorrência (mais recente, por causa do ORDER BY DESC) ganha.
            if (! isset($indexado[$codigo])) {
                $indexado[$codigo] = $linha;
            }
        }

        return $indexado;
    }

    /**
     * Média de aliquota_icms ponderada por valor_total das linhas. Null quando
     * nenhuma linha tem alíquota declarada.
     */
    private function aliquotaMediaPonderada(Collection $grupo): ?float
    {
        $comAliquota = $grupo->filter(fn ($l) => $l->aliquota_icms !== null);
        if ($comAliquota->isEmpty()) {
            return null;
        }

        $valor = (float) $comAliquota->sum('valor_total');
        if ($valor <= 0) {
            return null;
        }

        $somaPonderada = $comAliquota->sum(fn ($l) => (float) $l->aliquota_icms * (float) $l->valor_total);

        return round($somaPonderada / $valor, 4);
    }

    /**
     * @param  array<string, mixed>|null  $cadastro
     * @return array{tipo: string, codigo_item: string, descricao: string, total_notas: int, valor_movimentado: float, cadastro: ?array, detalhe: string}
     */
    private function montarAlerta(string $tipo, string $codigo, Collection $grupo, ?array $cadastro, string $detalhe): array
    {
        return [
            'tipo' => $tipo,
            'codigo_item' => $codigo,
            'descricao' => (string) ($grupo->first()->descricao ?? ''),
            'total_notas' => $grupo->pluck('chave_acesso')->filter()->unique()->count(),
            'valor_movimentado' => (float) $grupo->sum('valor_total'),
            'cadastro' => $cadastro,
            'detalhe' => $detalhe,
        ];
    }
}
