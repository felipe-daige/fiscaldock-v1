<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    public function todosIds(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Nao autenticado'], 401);
        }

        $status = trim($request->string('status')->toString());
        $tipo = strtoupper(trim($request->string('tipo')->toString()));
        $busca = trim($request->string('busca')->toString());
        $regime = trim($request->string('regime')->toString());
        $situacao = trim($request->string('situacao')->toString());
        $uf = strtoupper(trim($request->string('uf')->toString()));

        $ids = Cliente::where('user_id', $user->id)
            ->where('is_empresa_propria', false)
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'ativos') {
                    $query->where('ativo', true);
                } elseif ($status === 'inativos') {
                    $query->where('ativo', false);
                }
            })
            ->when($tipo !== '', fn ($query) => $query->where('tipo_pessoa', $tipo))
            ->when($busca !== '', function ($query) use ($busca) {
                $documento = preg_replace('/\D/', '', $busca);

                $query->where(function ($sub) use ($busca, $documento) {
                    $sub->where('razao_social', 'ilike', "%{$busca}%")
                        ->orWhere('nome', 'ilike', "%{$busca}%");

                    if ($documento !== '') {
                        $sub->orWhere('documento', 'like', "%{$documento}%");
                    }
                });
            })
            ->when($regime !== '', fn ($query) => $query->where('regime_tributario', 'ilike', $regime))
            ->when($situacao !== '', fn ($query) => $query->where('situacao_cadastral', 'ilike', $situacao))
            ->when($uf !== '', fn ($query) => $query->where('uf', $uf))
            ->pluck('id');

        return response()->json([
            'success' => true,
            'ids' => $ids,
            'total' => $ids->count(),
        ]);
    }

    /**
     * Store a newly created cliente in storage.
     */
    public function store(Request $request)
    {
        try {
            $tipoPessoa = $request->input('tipo_pessoa');
            $isPJ = $tipoPessoa === 'PJ';

            $rules = [
                'tipo_pessoa' => 'required|in:PF,PJ',
                'documento' => 'required|string|max:18|unique:clientes,documento',
                'telefone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'uf' => 'nullable|string|size:2',
                'cep' => 'nullable|string|max:9',
                'municipio' => 'nullable|string|max:255',
                'is_empresa_propria' => 'nullable|boolean',
                // Campos compartilhados PJ/PF
                'nome_fantasia' => 'nullable|string|max:255',
                'endereco' => 'nullable|string|max:255',
                'numero' => 'nullable|string|max:20',
                'complemento' => 'nullable|string|max:100',
                'bairro' => 'nullable|string|max:100',
                'situacao_cadastral' => 'nullable|string|max:50',
                'codigo_municipal' => 'nullable|string|max:10',
                // Campos PJ-only
                'inscricao_estadual' => 'nullable|string|max:20',
                'crt' => 'nullable|in:1,2,3',
                'regime_tributario' => 'nullable|string|max:50',
                'cnpj_matriz' => 'nullable|string|max:14',
                'suframa' => 'nullable|string|max:20',
                'capital_social' => 'nullable|numeric|min:0',
                'natureza_juridica' => 'nullable|string|max:100',
                'porte' => 'nullable|string|max:50',
                'data_inicio_atividade' => 'nullable|date',
                'cnae_principal' => 'nullable|string|max:10',
                'cnae_principal_descricao' => 'nullable|string|max:255',
                'cnaes_secundarios' => 'nullable|array',
                'qsa' => 'nullable|array',
            ];

            if ($isPJ) {
                $rules['razao_social'] = 'required|string|max:255';
                $rules['nome'] = 'nullable|string|max:255';
            } else {
                $rules['nome'] = 'required|string|max:255';
                $rules['razao_social'] = 'nullable|string|max:255';
            }

            $validated = $request->validate($rules);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario nao autenticado'
                ], 401);
            }

            $documentoLimpo = preg_replace('/\D/', '', $validated['documento']);

            if ($isPJ) {
                if (strlen($documentoLimpo) !== 14) {
                    throw ValidationException::withMessages([
                        'documento' => 'CNPJ deve ter 14 digitos'
                    ]);
                }
            } else {
                if (strlen($documentoLimpo) !== 11) {
                    throw ValidationException::withMessages([
                        'documento' => 'CPF deve ter 11 digitos'
                    ]);
                }
            }

            $cliente = Cliente::create([
                'user_id' => $user->id,
                'tipo_pessoa' => $validated['tipo_pessoa'],
                'documento' => $documentoLimpo,
                'nome' => $validated['nome'] ?? null,
                'razao_social' => $validated['razao_social'] ?? null,
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'inscricao_estadual' => $isPJ ? ($validated['inscricao_estadual'] ?? null) : null,
                'crt' => $isPJ ? ($validated['crt'] ?? null) : null,
                'telefone' => $validated['telefone'] ?? null,
                'email' => $validated['email'] ?? null,
                'uf' => isset($validated['uf']) ? strtoupper($validated['uf']) : null,
                'cep' => isset($validated['cep']) ? preg_replace('/\D/', '', $validated['cep']) : null,
                'municipio' => $validated['municipio'] ?? null,
                'endereco' => $validated['endereco'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'situacao_cadastral' => $validated['situacao_cadastral'] ?? null,
                'regime_tributario' => $isPJ ? ($validated['regime_tributario'] ?? null) : null,
                'cnpj_matriz' => $isPJ ? ($validated['cnpj_matriz'] ?? null) : null,
                'suframa' => $isPJ ? ($validated['suframa'] ?? null) : null,
                'codigo_municipal' => $validated['codigo_municipal'] ?? null,
                'capital_social' => $isPJ ? ($validated['capital_social'] ?? null) : null,
                'natureza_juridica' => $isPJ ? ($validated['natureza_juridica'] ?? null) : null,
                'porte' => $isPJ ? ($validated['porte'] ?? null) : null,
                'data_inicio_atividade' => $isPJ ? ($validated['data_inicio_atividade'] ?? null) : null,
                'cnae_principal' => $isPJ ? ($validated['cnae_principal'] ?? null) : null,
                'cnae_principal_descricao' => $isPJ ? ($validated['cnae_principal_descricao'] ?? null) : null,
                'cnaes_secundarios' => $isPJ ? ($validated['cnaes_secundarios'] ?? null) : null,
                'qsa' => $isPJ ? ($validated['qsa'] ?? null) : null,
                'is_empresa_propria' => $validated['is_empresa_propria'] ?? false,
                'ativo' => true,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente cadastrado com sucesso!',
                    'redirect' => '/app/clientes',
                    'cliente' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->nome,
                        'documento' => $cliente->documento_formatado,
                    ]
                ], 201);
            }

            return redirect()
                ->route('app.clientes')
                ->with('success', 'Cliente cadastrado com sucesso!');

        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validacao',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao cadastrar cliente: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erro ao cadastrar cliente. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Show the edit form for an existing cliente.
     */
    public function edit(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nao autenticado', 'redirect' => '/login']);
            }
            return redirect('/login');
        }

        $cliente = Cliente::where('user_id', $user->id)->findOrFail($id);

        $viewName = 'autenticado.clientes.novo';
        $data = ['cliente' => $cliente];

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $renderedView = view($viewName, $data)->render();
            return response($renderedView)->header('Content-Type', 'text/html');
        }

        return view('autenticado.layouts.app', array_merge([
            'initialView' => $viewName
        ], $data));
    }

    /**
     * Update an existing cliente.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario nao autenticado'
                ], 401);
            }

            $cliente = Cliente::where('user_id', $user->id)->findOrFail($id);

            $tipoPessoa = $cliente->tipo_pessoa;
            $isPJ = $tipoPessoa === 'PJ';

            $rules = [
                'documento' => 'required|string|max:18|unique:clientes,documento,' . $cliente->id,
                'telefone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'uf' => 'nullable|string|size:2',
                'cep' => 'nullable|string|max:9',
                'municipio' => 'nullable|string|max:255',
                'is_empresa_propria' => 'nullable|boolean',
                // Campos compartilhados PJ/PF
                'nome_fantasia' => 'nullable|string|max:255',
                'endereco' => 'nullable|string|max:255',
                'numero' => 'nullable|string|max:20',
                'complemento' => 'nullable|string|max:100',
                'bairro' => 'nullable|string|max:100',
                'situacao_cadastral' => 'nullable|string|max:50',
                'codigo_municipal' => 'nullable|string|max:10',
                // Campos PJ-only
                'inscricao_estadual' => 'nullable|string|max:20',
                'crt' => 'nullable|in:1,2,3',
                'regime_tributario' => 'nullable|string|max:50',
                'cnpj_matriz' => 'nullable|string|max:14',
                'suframa' => 'nullable|string|max:20',
                'capital_social' => 'nullable|numeric|min:0',
                'natureza_juridica' => 'nullable|string|max:100',
                'porte' => 'nullable|string|max:50',
                'data_inicio_atividade' => 'nullable|date',
                'cnae_principal' => 'nullable|string|max:10',
                'cnae_principal_descricao' => 'nullable|string|max:255',
                'cnaes_secundarios' => 'nullable|array',
                'qsa' => 'nullable|array',
            ];

            if ($isPJ) {
                $rules['razao_social'] = 'required|string|max:255';
                $rules['nome'] = 'nullable|string|max:255';
            } else {
                $rules['nome'] = 'required|string|max:255';
                $rules['razao_social'] = 'nullable|string|max:255';
            }

            $validated = $request->validate($rules);

            $cliente->update([
                'documento' => preg_replace('/\D/', '', $validated['documento']),
                'nome' => $validated['nome'] ?? null,
                'razao_social' => $validated['razao_social'] ?? null,
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'inscricao_estadual' => $isPJ ? ($validated['inscricao_estadual'] ?? null) : null,
                'crt' => $isPJ ? ($validated['crt'] ?? null) : null,
                'telefone' => $validated['telefone'] ?? null,
                'email' => $validated['email'] ?? null,
                'uf' => isset($validated['uf']) ? strtoupper($validated['uf']) : null,
                'cep' => isset($validated['cep']) ? preg_replace('/\D/', '', $validated['cep']) : null,
                'municipio' => $validated['municipio'] ?? null,
                'endereco' => $validated['endereco'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'situacao_cadastral' => $validated['situacao_cadastral'] ?? null,
                'regime_tributario' => $isPJ ? ($validated['regime_tributario'] ?? null) : null,
                'cnpj_matriz' => $isPJ ? ($validated['cnpj_matriz'] ?? null) : null,
                'suframa' => $isPJ ? ($validated['suframa'] ?? null) : null,
                'codigo_municipal' => $validated['codigo_municipal'] ?? null,
                'capital_social' => $isPJ ? ($validated['capital_social'] ?? null) : null,
                'natureza_juridica' => $isPJ ? ($validated['natureza_juridica'] ?? null) : null,
                'porte' => $isPJ ? ($validated['porte'] ?? null) : null,
                'data_inicio_atividade' => $isPJ ? ($validated['data_inicio_atividade'] ?? null) : null,
                'cnae_principal' => $isPJ ? ($validated['cnae_principal'] ?? null) : null,
                'cnae_principal_descricao' => $isPJ ? ($validated['cnae_principal_descricao'] ?? null) : null,
                'cnaes_secundarios' => $isPJ ? ($validated['cnaes_secundarios'] ?? null) : null,
                'qsa' => $isPJ ? ($validated['qsa'] ?? null) : null,
                'is_empresa_propria' => $validated['is_empresa_propria'] ?? $cliente->is_empresa_propria,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso!',
                    'redirect' => '/app/clientes',
                ]);
            }

            return redirect()
                ->route('app.clientes')
                ->with('success', 'Cliente atualizado com sucesso!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validacao',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar cliente: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erro ao atualizar cliente. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Delete an individual cliente.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Nao autenticado'], 401);
        }

        $cliente = Cliente::where('user_id', $user->id)->find($id);
        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Cliente nao encontrado'], 404);
        }

        if ($cliente->is_empresa_propria) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir a empresa própria.',
            ], 403);
        }

        try {
            $nome = $cliente->razao_social ?? $cliente->nome ?? '';
            $documento = $cliente->documento;

            $cliente->delete();

            Log::info('Cliente excluido', [
                'user_id' => $user->id,
                'cliente_id' => $id,
                'documento' => $documento,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente excluido com sucesso.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir cliente', [
                'user_id' => $user->id,
                'cliente_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir cliente. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Bulk delete clientes.
     */
    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Nao autenticado'], 401);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
        ]);

        try {
            $count = Cliente::where('user_id', $user->id)
                ->where('is_empresa_propria', false)
                ->whereIn('id', $validated['ids'])
                ->delete();

            Log::info('Clientes excluidos em lote', [
                'user_id' => $user->id,
                'count' => $count,
                'ids' => $validated['ids'],
            ]);

            return response()->json([
                'success' => true,
                'message' => $count . ' cliente(s) excluido(s) com sucesso.',
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir clientes em lote', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir clientes. Tente novamente.',
            ], 500);
        }
    }
}
