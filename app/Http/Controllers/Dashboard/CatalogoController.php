<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\EfdCatalogoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function index(Request $request)
    {
        $view = 'autenticado.catalogo.index';

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response('Não autenticado', 401);
            }

            return redirect()->route('login');
        }

        $userId = (int) Auth::id();

        $filtros = $request->only(['cliente_id', 'tipo_item', 'ncm', 'busca']);
        $perPage = 25;
        $page = max(1, (int) $request->get('page', 1));

        $baseQuery = EfdCatalogoItem::where('user_id', $userId);

        if (! empty($filtros['cliente_id'])) {
            $baseQuery->where('cliente_id', $filtros['cliente_id']);
        }

        // KPIs
        $totalProdutos = (clone $baseQuery)->distinct('cod_item')->count('cod_item');
        $semNcm = (clone $baseQuery)->where(fn ($q) => $q->whereNull('cod_ncm')->orWhere('cod_ncm', ''))->distinct('cod_item')->count('cod_item');

        // Cross-reference with efd_notas_itens
        $clienteFilter = ! empty($filtros['cliente_id']) ? ' AND ci.cliente_id = ' . ((int) $filtros['cliente_id']) : '';

        $comMovimentacao = DB::selectOne("
            SELECT COUNT(DISTINCT ci.cod_item) as total
            FROM efd_catalogo_itens ci
            INNER JOIN efd_notas_itens ni ON ni.codigo_item = ci.cod_item AND ni.user_id = ci.user_id
            WHERE ci.user_id = ?{$clienteFilter}
        ", [$userId]);

        $valorMovimentado = DB::selectOne("
            SELECT COALESCE(SUM(ni.valor_total), 0) as total
            FROM efd_notas_itens ni
            INNER JOIN efd_catalogo_itens ci ON ci.cod_item = ni.codigo_item AND ci.user_id = ni.user_id
            WHERE ci.user_id = ?{$clienteFilter}
        ", [$userId]);

        $aliqDivergente = DB::selectOne("
            SELECT COUNT(DISTINCT ci.cod_item) as total
            FROM efd_catalogo_itens ci
            INNER JOIN efd_notas_itens ni ON ni.codigo_item = ci.cod_item AND ni.user_id = ci.user_id
            WHERE ci.user_id = ?
            AND ci.aliq_icms IS NOT NULL AND ni.aliquota_icms IS NOT NULL
            AND ABS(ci.aliq_icms - ni.aliquota_icms) > 0.01{$clienteFilter}
        ", [$userId]);

        $kpis = [
            'total_produtos' => $totalProdutos,
            'com_movimentacao' => (int) ($comMovimentacao->total ?? 0),
            'sem_movimentacao' => $totalProdutos - (int) ($comMovimentacao->total ?? 0),
            'valor_movimentado' => (float) ($valorMovimentado->total ?? 0),
            'aliq_divergente' => (int) ($aliqDivergente->total ?? 0),
            'sem_ncm' => $semNcm,
        ];

        // Tabela consolidada — registro mais recente por cod_item
        $latestIds = (clone $baseQuery)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('cod_item');

        $itensQuery = EfdCatalogoItem::whereIn('id', $latestIds)
            ->select('efd_catalogo_itens.*')
            ->addSelect(DB::raw("(SELECT COUNT(*) FROM efd_notas_itens ni WHERE ni.codigo_item = efd_catalogo_itens.cod_item AND ni.user_id = {$userId}) as total_movimentacoes"))
            ->addSelect(DB::raw("(SELECT COALESCE(SUM(ni.valor_total), 0) FROM efd_notas_itens ni WHERE ni.codigo_item = efd_catalogo_itens.cod_item AND ni.user_id = {$userId}) as valor_movimentado"))
            ->addSelect(DB::raw("(SELECT AVG(ni.aliquota_icms) FROM efd_notas_itens ni WHERE ni.codigo_item = efd_catalogo_itens.cod_item AND ni.user_id = {$userId} AND ni.aliquota_icms IS NOT NULL) as aliq_icms_media_notas"));

        // Filtros
        if (! empty($filtros['tipo_item'])) {
            $itensQuery->where('tipo_item', $filtros['tipo_item']);
        }
        if (! empty($filtros['ncm'])) {
            $itensQuery->where('cod_ncm', 'ilike', '%' . $filtros['ncm'] . '%');
        }
        if (! empty($filtros['busca'])) {
            $busca = $filtros['busca'];
            $itensQuery->where(function ($q) use ($busca) {
                $q->where('cod_item', 'ilike', "%{$busca}%")
                  ->orWhere('descr_item', 'ilike', "%{$busca}%")
                  ->orWhere('cod_ncm', 'ilike', "%{$busca}%");
            });
        }

        $totalItens = $itensQuery->count();
        $itens = (clone $itensQuery)
            ->orderBy('cod_item')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $clientes = Cliente::where('user_id', $userId)
            ->orderBy('razao_social')
            ->get(['id', 'razao_social']);

        // Chart data — Top 10 CFOPs
        $cfops = DB::select("
            SELECT ni.cfop, COUNT(*) as total, SUM(ni.valor_total) as valor
            FROM efd_notas_itens ni
            INNER JOIN efd_catalogo_itens ci ON ci.cod_item = ni.codigo_item AND ci.user_id = ni.user_id
            WHERE ci.user_id = ? AND ni.cfop IS NOT NULL{$clienteFilter}
            GROUP BY ni.cfop
            ORDER BY total DESC
            LIMIT 10
        ", [$userId]);

        // Chart data — Top 10 CSTs ICMS
        $cstsIcms = DB::select("
            SELECT ni.cst_icms, COUNT(*) as total
            FROM efd_notas_itens ni
            INNER JOIN efd_catalogo_itens ci ON ci.cod_item = ni.codigo_item AND ci.user_id = ni.user_id
            WHERE ci.user_id = ? AND ni.cst_icms IS NOT NULL AND ni.cst_icms != ''{$clienteFilter}
            GROUP BY ni.cst_icms
            ORDER BY total DESC
            LIMIT 10
        ", [$userId]);

        $data = [
            'itens' => $itens,
            'kpis' => $kpis,
            'clientes' => $clientes,
            'filtros' => $filtros,
            'cfops' => $cfops,
            'csts_icms' => $cstsIcms,
            'paginacao' => [
                'total' => $totalItens,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($totalItens / $perPage),
            ],
        ];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    public function historico(Request $request, string $codItem)
    {
        if (! Auth::check()) {
            return response('Não autenticado', 401);
        }

        $userId = (int) Auth::id();
        $clienteId = $request->query('cliente_id');

        $query = EfdCatalogoItem::where('user_id', $userId)
            ->where('cod_item', $codItem)
            ->with('importacao:id,filename,concluido_em')
            ->orderByDesc('id');

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        $registros = $query->get();

        // Fiscal summary from notas
        $fiscalQuery = DB::select("
            SELECT ni.cfop, ni.cst_icms, ni.cst_pis, n.tipo_operacao,
                   COUNT(*) as cnt, SUM(ni.valor_total) as valor
            FROM efd_notas_itens ni
            JOIN efd_notas n ON n.id = ni.efd_nota_id
            WHERE ni.codigo_item = ? AND ni.user_id = ?
            GROUP BY ni.cfop, ni.cst_icms, ni.cst_pis, n.tipo_operacao
            ORDER BY cnt DESC
        ", [$codItem, $userId]);

        $entradas = collect($fiscalQuery)->where('tipo_operacao', 'entrada');
        $saidas = collect($fiscalQuery)->where('tipo_operacao', 'saida');
        $cfopsUnicos = collect($fiscalQuery)->pluck('cfop')->filter()->unique()->values();
        $cstsUnicos = collect($fiscalQuery)->pluck('cst_icms')->filter()->unique()->values();
        $totalEntradas = $entradas->sum('valor');
        $totalSaidas = $saidas->sum('valor');

        // Notas fiscais que contêm este item (últimas 10)
        $notas = DB::select("
            SELECT DISTINCT n.id, n.numero, n.serie, n.tipo_operacao, n.valor_total, n.data_emissao,
                   n.origem_arquivo, p.razao_social as participante_nome
            FROM efd_notas_itens ni
            JOIN efd_notas n ON n.id = ni.efd_nota_id
            LEFT JOIN participantes p ON p.id = n.participante_id
            WHERE ni.codigo_item = ? AND ni.user_id = ?
            ORDER BY n.data_emissao DESC
            LIMIT 10
        ", [$codItem, $userId]);

        $totalNotas = count($notas);
        $totalRegistros = $registros->count();
        $descricaoItem = $registros->first()?->descr_item ?? '';

        // ── Container principal ──
        $html = '<div class="p-4 space-y-3">';

        // ── KPIs em linha (estilo DANFE divide-x) ──
        if (! empty($fiscalQuery)) {
            $html .= '<div class="bg-white rounded border border-gray-300">';
            $html .= '<div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">';

            // Entradas
            $html .= '<div class="px-4 py-3">';
            $html .= '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Entradas</p>';
            $html .= '<p class="text-lg font-bold text-gray-900">R$ ' . number_format($totalEntradas, 2, ',', '.') . '</p>';
            $html .= '<p class="text-[11px] text-gray-500 mt-0.5">' . $entradas->sum('cnt') . ' ocorrências</p>';
            $html .= '</div>';

            // Saídas
            $html .= '<div class="px-4 py-3">';
            $html .= '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saídas</p>';
            $html .= '<p class="text-lg font-bold text-gray-900">R$ ' . number_format($totalSaidas, 2, ',', '.') . '</p>';
            $html .= '<p class="text-[11px] text-gray-500 mt-0.5">' . $saidas->sum('cnt') . ' ocorrências</p>';
            $html .= '</div>';

            // CFOPs
            $html .= '<div class="px-4 py-3">';
            $html .= '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CFOPs</p>';
            $html .= '<div class="flex flex-wrap gap-1 mt-1">';
            foreach ($cfopsUnicos as $cfop) {
                $html .= '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #4338ca">' . e($cfop) . '</span>';
            }
            $html .= '</div></div>';

            // CSTs
            $html .= '<div class="px-4 py-3">';
            $html .= '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CSTs ICMS</p>';
            $html .= '<div class="flex flex-wrap gap-1 mt-1">';
            foreach ($cstsUnicos as $cst) {
                $html .= '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #374151">' . e($cst) . '</span>';
            }
            $html .= '</div></div>';

            $html .= '</div></div>';
        }

        // ── Notas Fiscais ──
        if (! empty($notas)) {
            $html .= '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
            $html .= '<div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">';
            $html .= '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Notas Fiscais</span>';
            $html .= '<span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">' . $totalNotas . '</span>';
            $html .= '</div>';
            $html .= '<div class="overflow-x-auto">';
            $html .= '<table class="min-w-full">';
            $html .= '<thead><tr class="border-b border-gray-300">';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Número</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>';
            $html .= '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emissão</th>';
            $html .= '<th class="px-3 py-2 bg-gray-50"></th>';
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';

            foreach ($notas as $nota) {
                $url = '/app/notas-fiscais/efd/' . $nota->id;
                $tipoOp = $nota->tipo_operacao === 'entrada' ? 'Entrada' : 'Saída';
                $tipoBg = $nota->tipo_operacao === 'entrada' ? '#047857' : '#d97706';
                $dataFmt = $nota->data_emissao ? date('d/m/Y', strtotime($nota->data_emissao)) : '—';

                $html .= '<tr class="hover:bg-gray-50/50 transition-colors">';
                $html .= '<td class="px-3 py-2"><a href="' . $url . '" data-link class="font-mono text-sm text-gray-900 hover:text-gray-600 hover:underline">' . e($nota->numero ?: '—') . ($nota->serie ? '/' . e($nota->serie) : '') . '</a></td>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-700 truncate max-w-[200px]">' . e($nota->participante_nome ?: '—') . '</td>';
                $html .= '<td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' . $tipoBg . '">' . $tipoOp . '</span></td>';
                $html .= '<td class="px-3 py-2 text-right text-sm font-semibold text-gray-900 font-mono">R$ ' . number_format((float) ($nota->valor_total ?? 0), 2, ',', '.') . '</td>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-500">' . $dataFmt . '</td>';
                $html .= '<td class="px-3 py-2 text-right"><a href="' . $url . '" data-link class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver nota <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div></div>';
        }

        // ── Histórico de Importações ──
        if ($registros->isEmpty()) {
            $html .= '<div class="bg-white rounded border border-gray-300 p-4 text-center">';
            $html .= '<p class="text-sm text-gray-400">Nenhum registro de importação encontrado.</p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
            $html .= '<div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">';
            $html .= '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de Importações</span>';
            $html .= '<span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">' . $totalRegistros . '</span>';
            $html .= '</div>';
            $html .= '<div class="overflow-x-auto">';
            $html .= '<table class="min-w-full">';
            $html .= '<thead><tr class="border-b border-gray-300">';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Arquivo</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">NCM</th>';
            $html .= '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Alíq. ICMS</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Unidade</th>';
            $html .= '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>';
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';

            $prevNcm = null;
            $prevAliq = null;
            foreach ($registros as $reg) {
                $ncmChanged = $prevNcm !== null && $prevNcm !== $reg->cod_ncm;
                $aliqChanged = $prevAliq !== null && abs((float) $prevAliq - (float) $reg->aliq_icms) > 0.01;
                $highlightClass = ($ncmChanged || $aliqChanged) ? ' style="border-left: 3px solid #d97706"' : '';

                $html .= '<tr class="hover:bg-gray-50/50 transition-colors"' . $highlightClass . '>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-700">' . e($reg->importacao?->filename ?? 'ID ' . $reg->importacao_id) . '</td>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-500">' . ($reg->importacao?->concluido_em?->format('d/m/Y') ?? '—') . '</td>';
                $html .= '<td class="px-3 py-2 font-mono text-sm ' . ($ncmChanged ? 'font-semibold' : 'text-gray-700') . '">';
                if ($ncmChanged) {
                    $html .= '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">' . e($reg->cod_ncm ?: '—') . '</span>';
                } else {
                    $html .= e($reg->cod_ncm ?: '—');
                }
                $html .= '</td>';
                $html .= '<td class="px-3 py-2 text-right text-sm font-mono ' . ($aliqChanged ? 'font-semibold' : 'text-gray-700') . '">';
                if ($aliqChanged) {
                    $html .= '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">' . ($reg->aliq_icms !== null ? number_format((float) $reg->aliq_icms, 2, ',', '.') . '%' : '—') . '</span>';
                } else {
                    $html .= ($reg->aliq_icms !== null ? number_format((float) $reg->aliq_icms, 2, ',', '.') . '%' : '—');
                }
                $html .= '</td>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-600">' . e($reg->unid_inv ?: '—') . '</td>';
                $html .= '<td class="px-3 py-2 text-sm text-gray-700 truncate max-w-[300px]">' . e($reg->descr_item ?: '—') . '</td>';
                $html .= '</tr>';

                $prevNcm = $reg->cod_ncm;
                $prevAliq = $reg->aliq_icms;
            }

            $html .= '</tbody></table></div></div>';
        }
        $html .= '</div>';

        return response($html)->header('Content-Type', 'text/html');
    }

    private function isAjaxRequest(Request $request): bool
    {
        if (method_exists($request, 'ajax')) {
            return $request->ajax();
        }

        return $request->wantsJson()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
