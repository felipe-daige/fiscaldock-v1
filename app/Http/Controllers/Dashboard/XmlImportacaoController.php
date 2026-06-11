<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MonitoramentoPlano;
use App\Models\Participante;
use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\CreditService;
use App\Services\Xml\NfeXmlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class XmlImportacaoController extends Controller
{
    private const AUTH_VIEW_PREFIX = 'autenticado.importacao.';

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function __construct(
        protected CreditService $creditService,
        protected \App\Services\Xml\ExcluirImportacaoXmlService $excluir,
        protected \App\Services\Xml\DefinirClienteXmlService $definirClienteService,
    ) {}

    /**
     * Importação XML do dono autenticado (ou 404).
     */
    private function importacaoDoDono($id): XmlImportacao
    {
        return XmlImportacao::where('id', $id)
            ->where('user_id', (int) Auth::id())
            ->firstOrFail();
    }

    /**
     * Prévia de impacto da exclusão (modal).
     */
    public function previewExclusao(Request $request, $id): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($this->excluir->preview($this->importacaoDoDono($id)));
    }

    /**
     * Exclui a importação XML e seus derivados.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $imp = $this->importacaoDoDono($id);

        if (in_array($imp->status, ['processando', 'pendente'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Importação em processamento não pode ser excluída. Aguarde a conclusão.',
            ], Response::HTTP_CONFLICT);
        }

        $excluirParticipantes = $request->boolean('excluir_participantes');
        $resultado = $this->excluir->execute($imp, $excluirParticipantes);

        Log::info('Importação XML excluída', [
            'user_id' => (int) Auth::id(),
            'importacao_id' => (int) $id,
            'excluir_participantes' => $excluirParticipantes,
            'resultado' => $resultado,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Importação excluída com sucesso.',
            'resultado' => $resultado,
        ]);
    }

    /**
     * "Decidir depois": define qual lado (emit/dest) é o cliente e reclassifica o lote.
     */
    public function definirCliente(Request $request, $id): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $imp = $this->importacaoDoDono($id);

        if (in_array($imp->status, ['processando', 'pendente'], true)) {
            return response()->json(['success' => false, 'error' => 'Importação ainda em processamento.'], Response::HTTP_CONFLICT);
        }

        $validated = $request->validate(['lado' => 'required|in:emit,dest']);
        $resultado = $this->definirClienteService->execute($imp, $validated['lado']);

        Log::info('Cliente da importação XML definido', [
            'user_id' => (int) Auth::id(), 'importacao_id' => (int) $id,
            'lado' => $validated['lado'], 'resultado' => $resultado,
        ]);

        return response()->json(['success' => true, 'message' => 'Cliente definido com sucesso.', 'resultado' => $resultado]);
    }

    /**
     * Página de importação de XMLs (placeholder - em desenvolvimento).
     */
    public function index(Request $request)
    {
        $xmlView = self::AUTH_VIEW_PREFIX.'xml';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Clientes do usuário (empresa própria primeiro) para o seletor de dono da importação.
        $clientes = Cliente::where('user_id', $user->id)
            ->orderByDesc('is_empresa_propria')
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'documento', 'is_empresa_propria']);
        $empresaPropriaId = $clientes->firstWhere('is_empresa_propria', true)?->id;

        $importacoes = XmlImportacao::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'clientes' => $clientes,
            'empresaPropriaId' => $empresaPropriaId,
            'importacoes' => $importacoes,
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($xmlView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $xmlView], $data));
    }

    /**
     * Detalhes de uma importação XML específica.
     */
    public function show(Request $request, $id)
    {
        $view = self::AUTH_VIEW_PREFIX.'xml-detalhes';

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();
        $userId = (int) $user->id;

        $importacao = XmlImportacao::where('id', $id)
            ->where('user_id', $userId)
            ->with('cliente')
            ->firstOrFail();

        // Pagination limits
        $allowedPerPages = [10, 25, 50, 100];
        $perPageParticipantes = in_array((int) $request->input('per_page_participantes'), $allowedPerPages) ? (int) $request->input('per_page_participantes') : 10;

        // "Decidir depois": se exatamente um lado dominante já é cliente do usuário,
        // reclassifica o lote antes de montar os dados da tela; senão mantém o picker.
        $definirClienteCandidatos = null;
        $clienteAutoVinculado = null;
        if (! $importacao->cliente_id && $importacao->status === 'concluido') {
            $clienteAutoVinculado = $this->definirClienteService->autoDefinirSeClienteExistente($importacao);
            if ($clienteAutoVinculado) {
                $importacao->refresh()->load('cliente');
            } else {
                $definirClienteCandidatos = $this->definirClienteService->candidatos($importacao);
            }
        }

        // Dual-path: participante_ids (n8n v2) ou importacao_xml_id (legado)
        if (! empty($importacao->participante_ids)) {
            $participantes = Participante::whereIn('id', $importacao->participante_ids)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        } else {
            $participantes = Participante::where('importacao_xml_id', $id)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->paginate($perPageParticipantes, ['*'], 'page')
                ->withQueryString();
        }

        // Notas efetivamente gravadas por esta importação (vazio em lote 100% duplicado,
        // pois a dedup não religa a nota existente a este importacao_xml_id).
        $notas = XmlNota::where('importacao_xml_id', $id)
            ->where('user_id', $userId)
            ->orderByDesc('data_emissao')
            ->limit(200)
            ->get();

        // Agregados para o resultado consolidado (toda a base do lote, não só as 200 exibidas).
        [$resumoTributario, $porUf, $catalogoItens, $alertas] = $this->montarConsolidado($id, $userId);

        $data = compact('importacao', 'participantes', 'notas', 'resumoTributario', 'porUf', 'catalogoItens', 'alertas', 'definirClienteCandidatos', 'clienteAutoVinculado');

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    /**
     * Monta os agregados do resultado consolidado de uma importação XML:
     * resumo tributário, distribuição por UF (contraparte), catálogo de itens e alertas.
     *
     * @return array{0: array<string,mixed>, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection, 3: array<int,array<string,mixed>>}
     */
    private function montarConsolidado(int $importacaoId, int $userId): array
    {
        $base = fn () => XmlNota::where('importacao_xml_id', $importacaoId)->where('user_id', $userId);

        $agg = $base()->selectRaw('
            COUNT(*) as qtd,
            COALESCE(SUM(valor_total),0) as valor_total,
            COALESCE(SUM(valor_desconto),0) as desconto,
            COALESCE(SUM(icms_valor),0) as icms,
            COALESCE(SUM(icms_st_valor),0) as icms_st,
            COALESCE(SUM(pis_valor),0) as pis,
            COALESCE(SUM(cofins_valor),0) as cofins,
            COALESCE(SUM(ipi_valor),0) as ipi,
            COALESCE(SUM(tributos_total),0) as tributos,
            COALESCE(SUM(CASE WHEN tipo_nota = 0 THEN 1 ELSE 0 END),0) as entradas,
            COALESCE(SUM(CASE WHEN tipo_nota = 1 THEN 1 ELSE 0 END),0) as saidas,
            COALESCE(SUM(CASE WHEN finalidade = 4 THEN 1 ELSE 0 END),0) as devolucoes,
            COALESCE(SUM(CASE WHEN tipo_nota = 0 THEN valor_total ELSE 0 END),0) as valor_entradas,
            COALESCE(SUM(CASE WHEN tipo_nota = 1 THEN valor_total ELSE 0 END),0) as valor_saidas
        ')->first();

        $qtd = (int) ($agg->qtd ?? 0);
        $resumoTributario = [
            'qtd' => $qtd,
            'valor_total' => (float) $agg->valor_total,
            'desconto' => (float) $agg->desconto,
            'icms' => (float) $agg->icms,
            'icms_st' => (float) $agg->icms_st,
            'pis' => (float) $agg->pis,
            'cofins' => (float) $agg->cofins,
            'ipi' => (float) $agg->ipi,
            'tributos' => (float) $agg->tributos,
            'entradas' => (int) $agg->entradas,
            'saidas' => (int) $agg->saidas,
            'devolucoes' => (int) $agg->devolucoes,
            'valor_entradas' => (float) $agg->valor_entradas,
            'valor_saidas' => (float) $agg->valor_saidas,
            'ticket_medio' => $qtd > 0 ? ((float) $agg->valor_total) / $qtd : 0.0,
        ];

        // UF da contraparte: em saída o cliente é o dest; em entrada o fornecedor é o emit.
        $porUf = $base()
            ->selectRaw('CASE WHEN tipo_nota = 1 THEN dest_uf ELSE emit_uf END as uf, COUNT(*) as qtd, COALESCE(SUM(valor_total),0) as valor')
            ->groupByRaw('CASE WHEN tipo_nota = 1 THEN dest_uf ELSE emit_uf END')
            ->orderByDesc('valor')
            ->limit(10)
            ->get()
            ->filter(fn ($r) => ! empty($r->uf))
            ->values();

        $catalogoItens = \App\Models\XmlNotaItem::query()
            ->join('xml_notas', 'xml_notas.id', '=', 'xml_notas_itens.xml_nota_id')
            ->where('xml_notas.importacao_xml_id', $importacaoId)
            ->where('xml_notas_itens.user_id', $userId)
            ->selectRaw('
                xml_notas_itens.codigo_item,
                MAX(xml_notas_itens.descricao) as descricao,
                MAX(xml_notas_itens.ncm) as ncm,
                MAX(xml_notas_itens.cfop) as cfop,
                COUNT(*) as ocorrencias,
                COALESCE(SUM(xml_notas_itens.quantidade),0) as quantidade,
                COALESCE(SUM(xml_notas_itens.valor_total),0) as valor_total
            ')
            ->groupBy('xml_notas_itens.codigo_item')
            ->orderByDesc('valor_total')
            ->limit(100)
            ->get();

        // Alertas NF-e (leves, derivados do acervo deste lote).
        $alertas = [];
        $itensSemNcm = \App\Models\XmlNotaItem::query()
            ->join('xml_notas', 'xml_notas.id', '=', 'xml_notas_itens.xml_nota_id')
            ->where('xml_notas.importacao_xml_id', $importacaoId)
            ->where('xml_notas_itens.user_id', $userId)
            ->where(fn ($q) => $q->whereNull('xml_notas_itens.ncm')->orWhere('xml_notas_itens.ncm', ''))
            ->count();
        if ($itensSemNcm > 0) {
            $alertas[] = ['sev' => 'alerta', 'titulo' => 'Itens sem NCM', 'detalhe' => "{$itensSemNcm} item(ns) sem código NCM — pode comprometer classificação fiscal e catálogo."];
        }
        $notasValorZero = $base()->where('valor_total', '<=', 0)->count();
        if ($notasValorZero > 0) {
            $alertas[] = ['sev' => 'alerta', 'titulo' => 'Notas com valor zero', 'detalhe' => "{$notasValorZero} nota(s) com valor total igual a zero."];
        }
        if ($resumoTributario['devolucoes'] > 0) {
            $alertas[] = ['sev' => 'info', 'titulo' => 'Devoluções no lote', 'detalhe' => "{$resumoTributario['devolucoes']} nota(s) de devolução (finalidade 4) — confira o impacto no faturamento."];
        }
        $semParticipante = $base()->whereNull('emit_participante_id')->whereNull('dest_participante_id')->count();
        if ($semParticipante > 0) {
            $alertas[] = ['sev' => 'alerta', 'titulo' => 'Notas sem participante', 'detalhe' => "{$semParticipante} nota(s) sem emitente nem destinatário vinculado a um participante cadastrado."];
        }

        return [$resumoTributario, $porUf, $catalogoItens, $alertas];
    }

    /**
     * Página de importação de XMLs (versão funcional - dev only).
     */
    public function indexDev(Request $request)
    {
        $xmlView = self::AUTH_VIEW_PREFIX.'xml';

        if (! view()->exists($xmlView)) {
            abort(404);
        }

        if (! Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Buscar clientes do usuário para o select
        $clientes = Cliente::where('user_id', $user->id)
            ->orderBy('razao_social')
            ->get();

        // Últimas importações do usuário
        $importacoes = XmlImportacao::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'clientes' => $clientes,
            'importacoes' => $importacoes,
            'credits' => $this->creditService->getBalance($user),
            'planos' => MonitoramentoPlano::ativos(),
        ];

        if ($this->isAjaxRequest($request)) {
            $renderedView = view($xmlView, $data)->render();

            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => $xmlView,
        ], $data));
    }

    /**
     * Inicia importação de XMLs enviando para n8n (sempre como ZIP).
     *
     * Se o modo de envio for 'xml' (arquivos avulsos), comprime em ZIP antes de enviar.
     * Isso simplifica o workflow do n8n que sempre recebe um único ZIP.
     */
    public function importar(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        $validated = $request->validate([
            'tipo_documento' => 'required|in:NFE',
            'modo_envio' => 'required|in:zip,xml',
            'cliente_id' => ['required_without_all:criar_cliente_lado,decidir_depois', 'nullable', 'integer', \Illuminate\Validation\Rule::exists('clientes', 'id')->where('user_id', $user->id)],
            'criar_cliente_lado' => ['required_without_all:cliente_id,decidir_depois', 'nullable', 'in:emit,dest'],
            'decidir_depois' => ['nullable', 'boolean'],
            'tab_id' => 'required|string|max:36',
            'arquivos' => 'required|array|min:1|max:100',
            'arquivos.*.nome' => 'required|string|max:255',
            'arquivos.*.tipo' => 'required|string|max:100',
            'arquivos.*.conteudo_base64' => 'required|string',
        ]);

        $tamanhoTotal = 0;
        foreach ($validated['arquivos'] as $arquivo) {
            $tamanhoTotal += strlen(base64_decode($arquivo['conteudo_base64']));
        }
        if ($tamanhoTotal > 200 * 1024 * 1024) {
            return response()->json(['success' => false, 'error' => 'Tamanho total excede 200MB.'], Response::HTTP_BAD_REQUEST);
        }

        // Dono/perspectiva: cliente JÁ cadastrado (ownerDoc forçado) | criar pelo lado
        // (ownerLado emit/dest) | DECIDIR DEPOIS (sem cliente; escolhido no resultado).
        $decidirDepois = (bool) ($validated['decidir_depois'] ?? false);
        $ownerLado = $validated['criar_cliente_lado'] ?? '';
        $clienteId = $validated['cliente_id'] ?? null;
        $ownerDoc = '';
        if ($decidirDepois) {
            $clienteId = null;
            $ownerLado = ''; // importa sem dono; reclassifica no /definir-cliente
        } elseif ($ownerLado === '') {
            $ownerDoc = (string) $this->getClienteCnpj($clienteId);
            if ($ownerDoc === '') {
                return response()->json(['success' => false, 'error' => 'Cliente selecionado sem documento cadastrado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $clienteId = null; // definido pelo Job a partir do dono criado
        }

        // Nome do arquivo enviado (padrão EFD): ZIP/XML único = o próprio nome; vários
        // avulsos = primeiro nome + sufixo de contagem.
        $nomes = array_column($validated['arquivos'], 'nome');
        $filename = count($nomes) === 1
            ? $nomes[0]
            : $nomes[0].' (+'.(count($nomes) - 1).')';

        try {
            $importacao = XmlImportacao::create([
                'user_id' => $user->id,
                'cliente_id' => $clienteId,
                'tipo_documento' => 'NFE',
                'filename' => $filename,
                'modo_envio' => $validated['modo_envio'],
                'total_arquivos' => count($validated['arquivos']),
                'tamanho_total_bytes' => $tamanhoTotal,
                'status' => 'pendente',
                'iniciado_em' => now(),
            ]);

            $dir = "xml-imports/{$importacao->id}";
            $totalXmls = $this->extrairXmlsParaStorage($validated, $dir);

            // Guard de re-importação (doc único): 1 XML cuja chave já existe no acervo →
            // não reimporta, leva direto pra view individual da nota. Lotes (>1) seguem o
            // fluxo normal (processa novos, ignora duplicados, mostra o aviso no resultado).
            if ($totalXmls === 1 && ($existente = $this->notaDuplicadaNoLote($dir, (int) $user->id))) {
                $importacao->delete();
                Storage::disk('local')->deleteDirectory($dir);

                return response()->json([
                    'success' => true,
                    'duplicado' => true,
                    'nota_id' => $existente->id,
                    'nota_url' => route('app.notas.detalhes', ['origem' => 'xml', 'id' => $existente->id]),
                    'message' => 'Esta nota já está no seu acervo.',
                ]);
            }

            $importacao->update(['status' => 'processando', 'total_xmls' => $totalXmls]);

            \App\Jobs\ProcessarXmlImportacaoJob::dispatch(
                $importacao->id, (int) $user->id, $validated['tab_id'], $ownerDoc, $dir, $ownerLado
            );

            return response()->json([
                'success' => true,
                'importacao_id' => $importacao->id,
                'message' => 'Importação iniciada com sucesso.',
            ]);
        } catch (\Throwable $e) {
            Log::error('XmlImportacao: exceção ao iniciar', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            if (isset($importacao)) {
                $importacao->update(['status' => 'erro', 'erro_mensagem' => 'Erro interno: '.$e->getMessage()]);
            }

            return response()->json(['success' => false, 'error' => 'Erro interno ao processar importação.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extrai os XMLs (avulsos ou de dentro de um ZIP) para o disco local,
     * retornando a contagem de .xml gravados. O Job lê desse diretório.
     */
    private function extrairXmlsParaStorage(array $validated, string $dir): int
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $count = 0;

        if ($validated['modo_envio'] === 'zip') {
            $tmp = tempnam(sys_get_temp_dir(), 'xmlimp_').'.zip';
            file_put_contents($tmp, base64_decode($validated['arquivos'][0]['conteudo_base64']));
            try {
                $zip = new ZipArchive;
                if ($zip->open($tmp) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $name = $zip->getNameIndex($i);
                        if (! str_ends_with(strtolower($name), '.xml')) {
                            continue;
                        }
                        $conteudo = $zip->getFromIndex($i);
                        if ($conteudo !== false) {
                            $disk->put($dir.'/'.$this->sanitizarNomeArquivo(basename($name)).'-'.$i.'.xml', $conteudo);
                            $count++;
                        }
                    }
                    $zip->close();
                }
            } finally {
                @unlink($tmp);
            }
        } else {
            foreach ($validated['arquivos'] as $idx => $arquivo) {
                if (! str_ends_with(strtolower($arquivo['nome']), '.xml')) {
                    continue;
                }
                $disk->put($dir.'/'.$this->sanitizarNomeArquivo($arquivo['nome']).'-'.$idx.'.xml', base64_decode($arquivo['conteudo_base64']));
                $count++;
            }
        }

        return $count;
    }

    /**
     * Guard de doc único: se o diretório do lote tem 1 XML cuja chave_acesso já está
     * no acervo do usuário, retorna a nota existente (alvo do redirect). Resolução é
     * server-side pela chave do arquivo — nunca por ID vindo do front. Parse falho não
     * bloqueia (retorna null → o import segue o fluxo normal e o Job trata o erro).
     */
    private function notaDuplicadaNoLote(string $dir, int $userId): ?XmlNota
    {
        $disk = Storage::disk('local');
        $arquivo = collect($disk->files($dir))
            ->first(fn ($f) => str_ends_with(strtolower($f), '.xml'));
        if (! $arquivo) {
            return null;
        }

        try {
            $parsed = app(NfeXmlParser::class)->parse($disk->get($arquivo));
        } catch (\Throwable $e) {
            return null;
        }

        $chave = $parsed['header']['chave_acesso'] ?? null;
        if (! $chave) {
            return null;
        }

        return XmlNota::where('user_id', $userId)->where('chave_acesso', $chave)->first();
    }

    /**
     * Conta XMLs dentro de um arquivo ZIP.
     *
     * Exclui arquivos na pasta __MACOSX (resource forks do Mac).
     * Usa fallback com comando unzip se ZipArchive falhar.
     * Retorna -1 se não conseguir contar (n8n fará a contagem).
     *
     * @param  string  $zipPath  Caminho para o arquivo ZIP
     * @return int Quantidade de XMLs encontrados, ou -1 se indisponível
     */
    private function contarXmlsNoZip(string $zipPath): int
    {
        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            // Tentar fallback com unzip
            $fallback = $this->validarZipComUnzip($zipPath);
            if ($fallback['success']) {
                return $fallback['total_xmls'] ?? 0;
            }

            // Verificar magic bytes como último recurso
            $content = @file_get_contents($zipPath, false, null, 0, 4);
            if ($content && $this->isValidZipMagicBytes($content)) {
                Log::info('contarXmlsNoZip: ZIP aceito via magic bytes, contagem será feita pelo n8n', [
                    'zipPath' => $zipPath,
                ]);

                return -1; // -1 indica que n8n fará a contagem
            }

            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name &&
                str_ends_with(strtolower($name), '.xml') &&
                ! str_starts_with($name, '__MACOSX/')) {
                $count++;
            }
        }

        $zip->close();

        return $count;
    }

    /**
     * Sanitiza nome do arquivo para segurança.
     */
    private function sanitizarNomeArquivo(string $nome): string
    {
        $extensao = pathinfo($nome, PATHINFO_EXTENSION);
        $nomeBase = pathinfo($nome, PATHINFO_FILENAME);
        $nomeBaseSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomeBase);

        if (! in_array(strtolower($extensao), ['xml', 'zip'])) {
            $extensao = 'xml';
        }

        return $nomeBaseSanitizado.'.'.strtolower($extensao);
    }

    /**
     * SSE para acompanhar progresso de importação XML.
     */
    public function streamProgresso(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = auth()->id();
        $tabId = $request->query('tab_id');

        if (! $tabId) {
            return response()->json([
                'success' => false,
                'error' => 'tab_id obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Usa a mesma chave de cache do progresso SPED
        $cacheKey = "progresso:{$userId}:{$tabId}";

        Log::info('SSE XML streamProgresso iniciado', [
            'user_id' => $userId,
            'tab_id' => $tabId,
            'cache_key' => $cacheKey,
        ]);

        return response()->stream(function () use ($cacheKey, $userId, $tabId) {
            $tentativas = 0;
            $maxTentativas = 600; // 10 minutos (XMLs podem demorar mais)
            $lastDataHash = null;

            // Enviar comentário inicial
            echo ": SSE connection established for XML progress stream (user:{$userId}, tab:{$tabId})\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ($tentativas < $maxTentativas) {
                try {
                    // Lê dados do cache (n8n envia via API)
                    $data = Cache::get($cacheKey);

                    if ($data) {
                        // Calcular hash para detectar mudanças
                        $currentHash = md5(json_encode($data));

                        // Só enviar se os dados mudaram
                        if ($currentHash !== $lastDataHash) {
                            $lastDataHash = $currentHash;

                            // Enviar dados de progresso
                            echo 'data: '.json_encode($data)."\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                            // Se status é final, encerrar a conexão
                            if (in_array($data['status'] ?? '', ['concluido', 'erro'])) {
                                Log::info('SSE XML streamProgresso: status final recebido', [
                                    'user_id' => $userId,
                                    'tab_id' => $tabId,
                                    'status' => $data['status'],
                                ]);
                                // Limpar cache após status final
                                Cache::forget($cacheKey);
                                break;
                            }
                        }
                    }

                    // Verificar se a conexão ainda está ativa
                    if (connection_aborted()) {
                        Log::info('SSE XML streamProgresso: conexão abortada pelo cliente', [
                            'user_id' => $userId,
                            'tab_id' => $tabId,
                        ]);
                        break;
                    }

                    sleep(1);
                    $tentativas++;

                } catch (\Exception $e) {
                    Log::error('SSE XML streamProgresso: erro no loop', [
                        'user_id' => $userId,
                        'tab_id' => $tabId,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(1);
                    $tentativas++;
                    if (connection_aborted()) {
                        break;
                    }
                }
            }

            // Se chegou no limite, encerrar
            if ($tentativas >= $maxTentativas) {
                echo 'data: '.json_encode([
                    'status' => 'timeout',
                    'progresso' => 0,
                    'mensagem' => 'Tempo limite atingido. Tente novamente.',
                ])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                Log::warning('SSE XML streamProgresso: timeout', [
                    'user_id' => $userId,
                    'tab_id' => $tabId,
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Valida arquivo antes de importar (conta XMLs em ZIPs, detecta tipo).
     */
    public function validar(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'arquivo' => 'required|array',
            'arquivo.nome' => 'required|string|max:255',
            'arquivo.conteudo_base64' => 'required|string',
        ]);

        $fileName = $validated['arquivo']['nome'];
        $base64Content = $validated['arquivo']['conteudo_base64'];

        // Check base64 size before decoding (avoid memory issues)
        $estimatedSize = (int) (strlen($base64Content) * 0.75);
        if ($estimatedSize > 50 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'error' => 'Arquivo excede 50MB.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $content = base64_decode($base64Content, true);
        if ($content === false) {
            return response()->json([
                'success' => false,
                'error' => 'Conteúdo base64 inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (str_ends_with(strtolower($fileName), '.zip')) {
            return $this->validarZip($content, $fileName);
        } else {
            return $this->validarXml($content, $fileName);
        }
    }

    /**
     * Valida arquivo ZIP e conta XMLs dentro.
     *
     * Usa ZipArchive do PHP como método primário. Se falhar (comum com ZIPs
     * criados no Mac via Finder), tenta fallback com comando `unzip` do sistema.
     */
    private function validarZip(string $content, string $fileName): JsonResponse
    {
        // Detectar Apple Finder Bookmark (arquivo arrastado da Lixeira ou alias)
        if ($this->isAppleBookmark($content)) {
            Log::warning('Arquivo é Apple Finder Bookmark, não ZIP real', [
                'arquivo' => $fileName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Este arquivo é uma referência, não o ZIP real.',
                'hint' => 'Tire o arquivo da Lixeira antes de enviar, ou use "Comprimir" novamente.',
            ]);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xml_validate_');
        if (! $tempFile) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao criar arquivo temporário.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            file_put_contents($tempFile, $content);

            $zip = new ZipArchive;
            $result = $zip->open($tempFile);

            if ($result !== true) {
                // Logar erro real para diagnóstico
                Log::warning('ZipArchive falhou ao abrir arquivo', [
                    'arquivo' => $fileName,
                    'erro_codigo' => $result,
                    'erro_mensagem' => $this->getZipArchiveErrorMessage($result),
                ]);

                // Tentar fallback com unzip do sistema (suporta mais formatos)
                $fallback = $this->validarZipComUnzip($tempFile);

                if ($fallback['success']) {
                    Log::info('Fallback unzip funcionou', [
                        'arquivo' => $fileName,
                        'total_xmls' => $fallback['total_xmls'],
                    ]);

                    return response()->json([
                        'success' => true,
                        'tipo' => 'zip',
                        'total_xmls' => $fallback['total_xmls'],
                        'mensagem' => $fallback['total_xmls'] === 0 ? 'Nenhum XML encontrado no ZIP' : null,
                    ]);
                }

                // Terceiro fallback: verificar magic bytes
                // ZIPs do Mac (Archive Utility) às vezes usam formatos que as ferramentas
                // não conseguem abrir para listar, mas o n8n (via Node.js) consegue extrair
                if ($this->isValidZipMagicBytes($content)) {
                    Log::info('Fallback magic bytes: ZIP aceito para processamento', [
                        'arquivo' => $fileName,
                        'ziparchive_erro' => $this->getZipArchiveErrorMessage($result),
                        'unzip_erro' => $fallback['error'] ?? 'desconhecido',
                    ]);

                    return response()->json([
                        'success' => true,
                        'tipo' => 'zip',
                        'total_xmls' => -1, // -1 indica que a contagem será feita pelo n8n
                        'validacao_relaxada' => true,
                        'mensagem' => 'ZIP aceito. A contagem será feita durante o processamento.',
                    ]);
                }

                // Todos os métodos falharam - retornar erro detalhado com dica
                Log::error('ZIP inválido: ZipArchive, unzip e magic bytes falharam', [
                    'arquivo' => $fileName,
                    'ziparchive_erro' => $this->getZipArchiveErrorMessage($result),
                    'unzip_erro' => $fallback['error'] ?? 'desconhecido',
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $this->getZipArchiveErrorMessage($result),
                    'hint' => 'Se criado no Mac, tente: zip -r arquivo.zip pasta/',
                ]);
            }

            // Contar XMLs (excluindo __MACOSX que contém resource forks do Mac)
            $totalXmls = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName &&
                    str_ends_with(strtolower($entryName), '.xml') &&
                    ! str_starts_with($entryName, '__MACOSX/')) {
                    $totalXmls++;
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'tipo' => 'zip',
                'total_xmls' => $totalXmls,
                'mensagem' => $totalXmls === 0 ? 'Nenhum XML encontrado no ZIP' : null,
            ]);

        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Traduz códigos de erro do ZipArchive para mensagens amigáveis.
     */
    private function getZipArchiveErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            ZipArchive::ER_NOZIP => 'Arquivo não é um ZIP válido ou usa formato não suportado',
            ZipArchive::ER_COMPNOTSUPP => 'Método de compressão não suportado pelo servidor',
            ZipArchive::ER_INCONS => 'ZIP inconsistente (possível corrupção)',
            ZipArchive::ER_CRC => 'Erro de CRC (arquivo corrompido)',
            ZipArchive::ER_EOF => 'Arquivo truncado ou incompleto',
            ZipArchive::ER_NOENT => 'Arquivo não encontrado',
            ZipArchive::ER_OPEN => 'Não foi possível abrir o arquivo',
            ZipArchive::ER_READ => 'Erro de leitura',
            ZipArchive::ER_MEMORY => 'Erro de memória',
            default => "Erro ao processar ZIP (código: {$errorCode})",
        };
    }

    /**
     * Valida ZIP usando comando `unzip` do sistema como fallback.
     *
     * O comando unzip suporta mais métodos de compressão que o ZipArchive do PHP,
     * incluindo alguns formatos criados pelo Archive Utility do Mac.
     *
     * @return array{success: bool, total_xmls?: int, error?: string}
     */
    private function validarZipComUnzip(string $tempFile): array
    {
        // Verificar se unzip está disponível
        $whichResult = Process::run(['which', 'unzip']);
        if (! $whichResult->successful()) {
            return ['success' => false, 'error' => 'unzip não disponível no sistema'];
        }

        // Testar integridade do ZIP
        $testResult = Process::run(['unzip', '-t', $tempFile]);

        if (! $testResult->successful()) {
            return [
                'success' => false,
                'error' => trim($testResult->errorOutput()) ?: 'ZIP inválido',
            ];
        }

        // Listar conteúdo e contar XMLs (excluindo __MACOSX)
        $listResult = Process::run(['unzip', '-l', $tempFile]);

        if (! $listResult->successful()) {
            // ZIP é válido mas não conseguimos listar - retornar sucesso com 0 XMLs
            return ['success' => true, 'total_xmls' => 0];
        }

        $output = $listResult->output();

        // Contar arquivos .xml que NÃO estão em __MACOSX
        $lines = explode("\n", $output);
        $totalXmls = 0;

        foreach ($lines as $line) {
            // Formato típico: "  12345  2024-01-01 10:00   path/to/file.xml"
            if (preg_match('/\.xml$/i', $line) && ! preg_match('/__MACOSX/i', $line)) {
                $totalXmls++;
            }
        }

        return ['success' => true, 'total_xmls' => $totalXmls];
    }

    /**
     * Verifica se o conteúdo tem magic bytes de um arquivo ZIP válido.
     *
     * ZIP files começam com "PK" (Phil Katz, criador do formato).
     * Esta verificação é usada como último fallback quando ZipArchive
     * e unzip falham em abrir o arquivo (comum com ZIPs do Mac).
     *
     * @param  string  $content  Conteúdo binário do arquivo
     * @return bool True se os magic bytes indicam um ZIP
     */
    private function isValidZipMagicBytes(string $content): bool
    {
        if (strlen($content) < 4) {
            return false;
        }

        $magic = substr($content, 0, 4);

        // ZIP magic numbers
        return $magic === "PK\x03\x04"  // Normal ZIP (local file header)
            || $magic === "PK\x05\x06"  // Empty ZIP (end of central directory)
            || $magic === "PK\x07\x08"; // Spanned ZIP (data descriptor)
    }

    /**
     * Verifica se o conteúdo é um Apple Finder Bookmark.
     *
     * Bookmarks são enviados pelo Finder quando o arquivo está na Lixeira
     * ou é um alias. O conteúdo começa com "book" seguido de bytes nulos
     * e contém "mark" nos primeiros 16 bytes.
     *
     * @param  string  $content  Conteúdo binário do arquivo
     * @return bool True se é um Apple Finder Bookmark
     */
    private function isAppleBookmark(string $content): bool
    {
        if (strlen($content) < 8) {
            return false;
        }

        // Apple Bookmark magic: "book" seguido de bytes nulos, ou "mark" nos primeiros bytes
        return substr($content, 0, 4) === 'book'
            && substr($content, 4, 4) === "\x00\x00\x00\x00"
            || str_contains(substr($content, 0, 16), 'mark');
    }

    /**
     * Valida arquivo XML e tenta detectar o tipo de documento.
     */
    private function validarXml(string $content, string $fileName): JsonResponse
    {
        // Suppress libxml errors to handle them gracefully
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument;
            $loaded = $dom->loadXML($content);

            if (! $loaded) {
                $errors = libxml_get_errors();
                libxml_clear_errors();

                return response()->json([
                    'success' => false,
                    'error' => 'XML mal formado.',
                ]);
            }

            libxml_clear_errors();

            // Try to detect document type from root element and content
            $tipoDocumento = $this->detectarTipoDocumento($dom);

            return response()->json([
                'success' => true,
                'tipo' => 'xml',
                'total_xmls' => 1,
                'tipo_documento' => $tipoDocumento,
            ]);

        } finally {
            libxml_use_internal_errors($previousErrors);
        }
    }

    /**
     * Detecta o tipo de documento fiscal a partir do DOM.
     */
    private function detectarTipoDocumento(\DOMDocument $dom): ?string
    {
        $rootElement = $dom->documentElement;
        if (! $rootElement) {
            return null;
        }

        $rootName = strtolower($rootElement->localName);

        // NF-e detection
        if (in_array($rootName, ['nfeproc', 'nfe', 'enviarnfe'])) {
            return 'NFE';
        }

        // CT-e detection
        if (in_array($rootName, ['cteproc', 'cte', 'enviarcte'])) {
            return 'CTE';
        }

        // NFS-e detection (various formats)
        if (str_contains($rootName, 'nfse') || str_contains($rootName, 'infnfse')) {
            return 'NFSE';
        }

        // Check for NFS-e tags inside the document
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('x', $rootElement->namespaceURI ?: '');

        // Look for common NFS-e elements
        $nfseElements = $dom->getElementsByTagName('InfNfse');
        if ($nfseElements->length > 0) {
            return 'NFSE';
        }

        $nfseElements = $dom->getElementsByTagName('Nfse');
        if ($nfseElements->length > 0) {
            return 'NFSE';
        }

        // Look for NF-e elements inside
        $nfeElements = $dom->getElementsByTagName('infNFe');
        if ($nfeElements->length > 0) {
            return 'NFE';
        }

        // Look for CT-e elements inside
        $cteElements = $dom->getElementsByTagName('infCte');
        if ($cteElements->length > 0) {
            return 'CTE';
        }

        return null;
    }

    /**
     * Verifica se a requisição é AJAX (navegação SPA).
     */
    private function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Redireciona para login preservando URL.
     */
    private function redirectToLogin(Request $request)
    {
        session(['url.intended' => $request->fullUrl()]);

        return redirect()->route('login');
    }

    /**
     * Busca o CNPJ do cliente pelo ID.
     *
     * @return string|null CNPJ limpo (apenas numeros) ou null
     */
    private function getClienteCnpj(?int $clienteId): ?string
    {
        if (! $clienteId) {
            return null;
        }

        $cliente = Cliente::find($clienteId);
        if (! $cliente || empty($cliente->documento)) {
            return null;
        }

        // Retorna apenas numeros (remove formatacao)
        return preg_replace('/[^0-9]/', '', $cliente->documento);
    }

    /**
     * Retorna os detalhes dos participantes e notas de uma importacao.
     */
    public function getParticipantes(Request $request, int $importacaoId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario nao autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Auth::id();

        // Buscar importacao
        $importacao = XmlImportacao::where('id', $importacaoId)
            ->where('user_id', $userId)
            ->first();

        if (! $importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importacao nao encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Buscar participantes pelos IDs armazenados
        $participantes = [];
        $participantesNovos = 0;
        $participantesAtualizados = 0;

        // Se participante_ids estiver vazio, tentar extrair das notas fiscais (fallback)
        $participanteIds = $importacao->participante_ids;
        if (empty($participanteIds)) {
            $participanteIds = $this->extrairParticipanteIdsDasNotas($importacaoId, $userId);
        }

        if (! empty($participanteIds)) {
            $participantesQuery = \App\Models\Participante::whereIn('id', $participanteIds)
                ->where('user_id', $userId)
                ->orderBy('razao_social')
                ->get();

            foreach ($participantesQuery as $p) {
                // Determinar se e novo baseado no created_at vs iniciado_em da importacao
                $isNovo = $p->created_at >= $importacao->iniciado_em;

                if ($isNovo) {
                    $participantesNovos++;
                } else {
                    $participantesAtualizados++;
                }

                $participantes[] = [
                    'id' => $p->id,
                    'cnpj' => $p->documento,
                    'cnpj_formatado' => $p->cnpj_formatado,
                    'razao_social' => $p->razao_social,
                    'nome_fantasia' => $p->nome_fantasia,
                    'endereco' => $p->endereco,
                    'inscricao_estadual' => $p->inscricao_estadual,
                    'is_novo' => $isNovo,
                ];
            }
        }

        // Buscar notas fiscais da importacao
        $notasFiscais = [];
        $resumoFinanceiro = [
            'valor_total' => 0,
            'icms_total' => 0,
            'icms_st_total' => 0,
            'pis_cofins_total' => 0,
            'ipi_total' => 0,
            'tributos_total' => 0,
            'qtd_entradas' => 0,
            'qtd_saidas' => 0,
            'qtd_devolucoes' => 0,
        ];

        $notasQuery = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->orderBy('data_emissao', 'desc')
            ->get();

        // Nota: xml_chaves_processadas foi eliminada - deduplicação agora usa xml_notas diretamente

        foreach ($notasQuery as $nota) {
            // Acumular resumo financeiro
            $resumoFinanceiro['valor_total'] += (float) $nota->valor_total;
            $resumoFinanceiro['icms_total'] += (float) $nota->icms_valor;
            $resumoFinanceiro['icms_st_total'] += (float) $nota->icms_st_valor;
            $resumoFinanceiro['pis_cofins_total'] += (float) $nota->pis_valor + (float) $nota->cofins_valor;
            $resumoFinanceiro['ipi_total'] += (float) $nota->ipi_valor;
            $resumoFinanceiro['tributos_total'] += (float) $nota->tributos_total;

            if ($nota->tipo_nota === \App\Models\XmlNota::TIPO_ENTRADA) {
                $resumoFinanceiro['qtd_entradas']++;
            } else {
                $resumoFinanceiro['qtd_saidas']++;
            }

            if ($nota->finalidade === \App\Models\XmlNota::FINALIDADE_DEVOLUCAO) {
                $resumoFinanceiro['qtd_devolucoes']++;
            }

            $notasFiscais[] = [
                'id' => $nota->id,
                'numero_nota' => $nota->numero_documento,
                'serie' => $nota->serie,
                'data_emissao' => $nota->data_emissao?->format('d/m/Y'),
                'emit_cnpj' => $nota->emit_documento,
                'emit_cnpj_formatado' => $nota->emit_documento_formatado,
                'emit_razao_social' => $nota->emit_razao_social,
                'emit_uf' => $nota->emit_uf,
                'dest_cnpj' => $nota->dest_documento,
                'dest_cnpj_formatado' => $nota->dest_documento_formatado,
                'dest_razao_social' => $nota->dest_razao_social,
                'dest_uf' => $nota->dest_uf,
                'valor_total' => (float) $nota->valor_total,
                'valor_formatado' => $nota->valor_formatado,
                'icms_valor' => (float) $nota->icms_valor,
                'pis_valor' => (float) $nota->pis_valor,
                'cofins_valor' => (float) $nota->cofins_valor,
                'ipi_valor' => (float) $nota->ipi_valor,
                'tipo_nota' => $nota->tipo_nota,
                'tipo_nota_desc' => $nota->tipo_nota_descricao,
                'finalidade' => $nota->finalidade,
                'finalidade_desc' => $nota->finalidade_descricao,
                'natureza_operacao' => $nota->natureza_operacao,
            ];
        }

        return response()->json([
            'success' => true,
            'importacao' => [
                'id' => $importacao->id,
                'tipo_documento' => $importacao->tipo_documento,
                'total_xmls' => $importacao->total_xmls ?? $importacao->xmls_processados,
                'xmls_processados' => $importacao->xmls_processados,
                'status' => $importacao->status,
                'concluido_em' => $importacao->concluido_em?->format('d/m/Y H:i'),
            ],
            'resumo_financeiro' => $resumoFinanceiro,
            'notas_fiscais' => $notasFiscais,
            'participantes' => $participantes,
            'totais' => [
                'participantes_novos' => $participantesNovos ?: $importacao->participantes_novos,
                'participantes_atualizados' => $participantesAtualizados ?: $importacao->participantes_atualizados,
                'notas_total' => count($notasFiscais),
            ],
        ]);
    }

    /**
     * Salva CNPJs novos descobertos durante importacao como participantes e/ou clientes.
     *
     * Chamado pelo frontend apos o usuario revisar a lista de CNPJs novos
     * e decidir quais salvar. Cria os registros e atualiza as FKs em xml_notas.
     */
    public function salvarCnpjsNovos(Request $request, int $importacaoId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario nao autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Auth::id();

        // Validar que a importacao pertence ao usuario
        $importacao = XmlImportacao::where('id', $importacaoId)
            ->where('user_id', $userId)
            ->first();

        if (! $importacao) {
            return response()->json([
                'success' => false,
                'error' => 'Importacao nao encontrada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'cnpjs' => 'required|array|min:1|max:500',
            'cnpjs.*.cnpj' => 'required|string|size:14',
            'cnpjs.*.salvar_como' => 'required|in:participante,cliente',
            'cnpjs.*.razao_social' => 'nullable|string|max:255',
            'cnpjs.*.nome_fantasia' => 'nullable|string|max:255',
            'cnpjs.*.uf' => 'nullable|string|max:2',
            'cnpjs.*.cep' => 'nullable|string|max:10',
            'cnpjs.*.municipio' => 'nullable|string|max:255',
            'cnpjs.*.telefone' => 'nullable|string|max:20',
            'cnpjs.*.crt' => 'nullable|integer|in:1,2,3',
        ]);

        $criados = [];
        $erros = [];

        try {
            DB::beginTransaction();

            foreach ($validated['cnpjs'] as $cnpjData) {
                $cnpj = $cnpjData['cnpj'];

                try {
                    $clienteId = null;

                    // Se salvar como cliente, criar o registro em clientes primeiro
                    if ($cnpjData['salvar_como'] === 'cliente') {
                        $cliente = Cliente::firstOrCreate(
                            [
                                'user_id' => $userId,
                                'documento' => $cnpj,
                            ],
                            [
                                'tipo_pessoa' => 'PJ',
                                'razao_social' => $cnpjData['razao_social'] ?? null,
                                'nome' => $cnpjData['nome_fantasia'] ?? $cnpjData['razao_social'] ?? null,
                                'ativo' => true,
                                'is_empresa_propria' => false,
                            ]
                        );
                        $clienteId = $cliente->id;
                    }

                    // Criar participante (upsert para evitar conflitos)
                    $participante = Participante::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'documento' => $cnpj,
                        ],
                        [
                            'razao_social' => $cnpjData['razao_social'] ?? null,
                            'nome_fantasia' => $cnpjData['nome_fantasia'] ?? null,
                            'uf' => $cnpjData['uf'] ?? null,
                            'cep' => $cnpjData['cep'] ?? null,
                            'municipio' => $cnpjData['municipio'] ?? null,
                            'telefone' => $cnpjData['telefone'] ?? null,
                            'crt' => $cnpjData['crt'] ?? null,
                            'cliente_id' => $clienteId,
                            'importacao_xml_id' => $importacaoId,
                            'origem_tipo' => $importacao->tipo_documento ?? 'NFE',
                        ]
                    );

                    // Atualizar xml_notas: preencher FKs onde CNPJ coincide e participante_id e NULL
                    XmlNota::where('importacao_xml_id', $importacaoId)
                        ->where('user_id', $userId)
                        ->where('emit_documento', $cnpj)
                        ->whereNull('emit_participante_id')
                        ->update([
                            'emit_participante_id' => $participante->id,
                            'emit_cliente_id' => $clienteId,
                        ]);

                    XmlNota::where('importacao_xml_id', $importacaoId)
                        ->where('user_id', $userId)
                        ->where('dest_documento', $cnpj)
                        ->whereNull('dest_participante_id')
                        ->update([
                            'dest_participante_id' => $participante->id,
                            'dest_cliente_id' => $clienteId,
                        ]);

                    $criados[] = [
                        'cnpj' => $cnpj,
                        'participante_id' => $participante->id,
                        'cliente_id' => $clienteId,
                        'salvo_como' => $cnpjData['salvar_como'],
                    ];
                } catch (\Exception $e) {
                    Log::warning('salvarCnpjsNovos: erro ao salvar CNPJ', [
                        'cnpj' => $cnpj,
                        'error' => $e->getMessage(),
                    ]);
                    $erros[] = [
                        'cnpj' => $cnpj,
                        'erro' => $e->getMessage(),
                    ];
                }
            }

            // Atualizar participante_ids na importacao
            $novosIds = array_column($criados, 'participante_id');
            $idsAtuais = $importacao->participante_ids ?? [];
            $idsMerged = array_values(array_unique(array_merge($idsAtuais, $novosIds)));
            $importacao->update(['participante_ids' => $idsMerged]);

            DB::commit();

            Log::info('salvarCnpjsNovos: concluido', [
                'importacao_id' => $importacaoId,
                'user_id' => $userId,
                'criados' => count($criados),
                'erros' => count($erros),
            ]);

            return response()->json([
                'success' => true,
                'criados' => $criados,
                'erros' => $erros,
                'total_criados' => count($criados),
                'total_erros' => count($erros),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('salvarCnpjsNovos: erro geral', [
                'importacao_id' => $importacaoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao salvar CNPJs: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Extrai IDs de participantes das notas fiscais da importação (fallback).
     *
     * Usado quando participante_ids não foi preenchido no registro XmlImportacao.
     * Busca emit_participante_id e dest_participante_id únicos das notas.
     * Salva os IDs no registro para próximas consultas.
     *
     * @param  int  $importacaoId  ID da importação
     * @param  int  $userId  ID do usuário (para validação)
     * @return array IDs únicos dos participantes
     */
    private function extrairParticipanteIdsDasNotas(int $importacaoId, int $userId): array
    {
        // Buscar IDs de emitentes
        $emitIds = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->whereNotNull('emit_participante_id')
            ->distinct()
            ->pluck('emit_participante_id')
            ->toArray();

        // Buscar IDs de destinatários
        $destIds = \App\Models\XmlNota::where('importacao_xml_id', $importacaoId)
            ->where('user_id', $userId)
            ->whereNotNull('dest_participante_id')
            ->distinct()
            ->pluck('dest_participante_id')
            ->toArray();

        // Combinar e remover duplicados
        $participanteIds = array_values(array_unique(array_merge($emitIds, $destIds)));

        // Se encontrou IDs, salvar no registro para próximas consultas
        if (! empty($participanteIds)) {
            try {
                XmlImportacao::where('id', $importacaoId)
                    ->where('user_id', $userId)
                    ->update(['participante_ids' => $participanteIds]);

                Log::info('Fallback: participante_ids extraídos das notas fiscais', [
                    'importacao_id' => $importacaoId,
                    'user_id' => $userId,
                    'total_ids' => count($participanteIds),
                ]);
            } catch (\Exception $e) {
                Log::warning('Fallback: erro ao salvar participante_ids', [
                    'importacao_id' => $importacaoId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $participanteIds;
    }
}
