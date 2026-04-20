<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnsureEmpresaPropriaExists
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::check() || $request->is('api/*')) {
            return $next($request);
        }

        $user = Auth::user();

        $hasEmpresaPropria = $user->clientes()
            ->where('is_empresa_propria', true)
            ->exists();

        if (!$hasEmpresaPropria) {
            $tipoPessoa = $user->cnpj ? 'PJ' : 'PF';
            $documento = $user->cnpj;

            if (!$documento) {
                Log::warning('Usuário sem documento (CNPJ/CPF) — empresa própria não criada', [
                    'user_id' => $user->id,
                ]);
                return $next($request);
            }

            try {
                DB::transaction(function () use ($user, $tipoPessoa, $documento) {
                    Cliente::create([
                        'user_id'            => $user->id,
                        'tipo_pessoa'        => $tipoPessoa,
                        'documento'          => $documento,
                        'nome'               => $user->empresa,
                        'razao_social'       => $tipoPessoa === 'PJ' ? $user->empresa : null,
                        'telefone'           => $user->telefone,
                        'email'              => $user->email,
                        'is_empresa_propria' => true,
                        'ativo'              => true,
                    ]);
                });

                Log::info('Empresa própria recriada para usuário', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::error('Falha ao recriar empresa própria', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }
}
