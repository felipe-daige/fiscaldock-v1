<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        try {
            return parent::handle($request, $next, ...$guards);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            throw $e;
        }
    }
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Rotas API sempre retornam JSON 401 em vez de redirecionar
        // Testar múltiplas formas de detecção
        $isApiRoute = $request->is('api/*') 
            || str_starts_with($request->path(), 'api/')
            || str_starts_with($request->url(), $request->getSchemeAndHttpHost() . '/api/');
        
        // Verificar se o header Accept contém application/json
        $acceptsJson = $request->header('Accept') && str_contains($request->header('Accept'), 'application/json');
        
        // Verificar se é requisição AJAX
        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';
        
        // Se for rota API, sempre retornar null (JSON 401)
        if ($isApiRoute) {
            return null;
        }
        
        // Se a requisição aceita JSON (header Accept: application/json), retornar null
        // Isso garante que requisições AJAX/API sempre recebam JSON 401
        if ($acceptsJson || $isAjax) {
            return null;
        }
        
        // Se a requisição espera JSON (método expectsJson do Laravel), retornar null
        // Isso faz o Laravel retornar JSON 401 em vez de redirecionar
        if ($request->expectsJson()) {
            return null;
        }
        
        // Para requisições web normais, redirecionar para login
        return route('login');
    }
}


