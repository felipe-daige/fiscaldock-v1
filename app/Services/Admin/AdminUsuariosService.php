<?php

namespace App\Services\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lista de usuários e atividade derivada para o console admin (read-only, escopo global).
 */
class AdminUsuariosService
{
    private const ORDENAVEIS = ['created_at', 'ultima_atividade_ts', 'credits', 'qtd_consultas'];

    /**
     * @param  array{q?:string,ordenar?:string}  $filtros
     */
    public function lista(array $filtros, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $ordenar = in_array($filtros['ordenar'] ?? '', self::ORDENAVEIS, true) ? $filtros['ordenar'] : 'created_at';

        $q = DB::table('users')->selectRaw(
            'users.*,
             (select count(*) from consulta_lotes cl where cl.user_id = users.id) as qtd_consultas,
             ((select count(*) from efd_importacoes ei where ei.user_id = users.id)
              + (select count(*) from xml_importacoes xi where xi.user_id = users.id)) as qtd_importacoes,
             (select max(last_activity) from sessions se where se.user_id = users.id) as ultima_atividade_ts,
             (select sp.nome from account_subscriptions s join subscription_plans sp on sp.id = s.subscription_plan_id
                where s.user_id = users.id and s.status = \'ativa\' limit 1) as plano_nome'
        );

        $busca = trim((string) ($filtros['q'] ?? ''));
        if ($busca !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca).'%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhere('empresa', 'ilike', $like)
                    ->orWhere('cnpj', 'ilike', $like);
            });
        }

        $q->orderByRaw("{$ordenar} desc nulls last");

        return $q->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array{qtd_consultas:int,qtd_importacoes:int,creditos_consumidos:float,total_pago:float}
     */
    public function kpis(int $userId): array
    {
        return [
            'qtd_consultas' => DB::table('consulta_lotes')->where('user_id', $userId)->count(),
            'qtd_importacoes' => DB::table('efd_importacoes')->where('user_id', $userId)->count()
                + DB::table('xml_importacoes')->where('user_id', $userId)->count(),
            'creditos_consumidos' => abs((float) DB::table('credit_transactions')->where('user_id', $userId)->where('amount', '<', 0)->sum('amount')),
            'total_pago' => (float) DB::table('mercado_pago_payments')->where('user_id', $userId)->where('status', 'approved')->sum('valor'),
        ];
    }

    public function assinaturaAtiva(int $userId): ?object
    {
        return DB::table('account_subscriptions as s')
            ->join('subscription_plans as p', 'p.id', '=', 's.subscription_plan_id')
            ->where('s.user_id', $userId)->where('s.status', 'ativa')
            ->selectRaw('s.*, p.nome as plano_nome, p.preco_mensal_centavos, p.preco_anual_centavos')
            ->first();
    }

    public function ultimaSessao(int $userId): ?object
    {
        return DB::table('sessions')->where('user_id', $userId)->orderByDesc('last_activity')->first();
    }

    /**
     * Atividade derivada (timeline) — UNION das fontes, desc por data.
     *
     * @return Collection<int, array{tipo:string,data:?string,titulo:string,detalhe:?string}>
     */
    public function timeline(int $userId, int $limit = 50): Collection
    {
        $lim = max(1, min(200, $limit));
        $sql = "
            SELECT 'consulta' AS tipo, created_at AS data, ('Consulta de '||total_participantes||' CNPJ(s)') AS titulo, status AS detalhe FROM consulta_lotes WHERE user_id = :u
            UNION ALL
            SELECT 'importacao_efd', created_at, ('Importação EFD '||coalesce(tipo_efd,'')), status FROM efd_importacoes WHERE user_id = :u
            UNION ALL
            SELECT 'importacao_xml', created_at, 'Importação XML', status FROM xml_importacoes WHERE user_id = :u
            UNION ALL
            SELECT 'credito', created_at, (type||' '||amount::text), coalesce(description,'') FROM credit_transactions WHERE user_id = :u
            UNION ALL
            SELECT 'pagamento', created_at, ('Pagamento '||status||' R$ '||valor::text), coalesce(tipo,'') FROM mercado_pago_payments WHERE user_id = :u
            ORDER BY data DESC NULLS LAST
            LIMIT {$lim}";

        return collect(DB::select($sql, ['u' => $userId]))->map(fn ($r) => [
            'tipo' => $r->tipo,
            'data' => $r->data,
            'titulo' => $r->titulo,
            'detalhe' => $r->detalhe,
        ]);
    }
}
