<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar nos headers X-Forwarded-* do Traefik (TLS termina no proxy).
        // Isso faz o Laravel reconhecer HTTPS corretamente e gerar asset()/@vite com https://.
        $middleware->append(\App\Http\Middleware\TrustProxies::class);
        // Cabeçalhos de segurança (anti-clickjacking, nosniff, HSTS, referrer-policy)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Logar todas as requisições HTTP recebidas
        $middleware->append(\App\Http\Middleware\LogHttpRequests::class);
        // Garantir que a empresa própria do usuário existe (recria silenciosamente se ausente)
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureEmpresaPropriaExists::class);

        // Excluir rotas de API do CSRF para permitir chamadas externas (n8n)
        // No Laravel 12, rotas em api.php não têm CSRF por padrão, mas garantimos aqui
        // Usando array com padrões para garantir que todas as rotas /api/* sejam excluídas
        $middleware->validateCsrfTokens(except: [
            'api/*',
            '/api/*',
            'api/data/receive',
            '/api/data/receive',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tratar exceções de autenticação para rotas API
        // Garantir que sempre retornem JSON 401 em vez de redirecionar
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            // Verificar se é rota API ou requisição que aceita JSON
            $isApiRoute = $request->is('api/*')
                || str_starts_with($request->path(), 'api/')
                || str_starts_with($request->url(), $request->getSchemeAndHttpHost().'/api/');

            $acceptsJson = $request->header('Accept') && str_contains($request->header('Accept'), 'application/json');
            $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';

            // Se for rota API ou requisição que aceita JSON, retornar JSON 401
            if ($isApiRoute || $acceptsJson || $isAjax || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado.',
                ], 401);
            }

            // Para requisições web normais, deixar o Laravel tratar (redirecionar)
            return null;
        });

        // CSRF token expirado/rotacionado.
        // No Laravel 11+, prepareException() converte TokenMismatchException em
        // HttpException(419) ANTES dos render callbacks — por isso casamos pelo
        // HttpException 419 (e não pelo TokenMismatchException, que nunca chega aqui).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // demais HttpException seguem o tratamento padrão
            }

            $acceptsJson = $request->header('Accept') && str_contains($request->header('Accept'), 'application/json');
            $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';

            // AJAX/JSON: devolve 419 com um token novo para o cliente atualizar a
            // meta csrf-token e refazer a requisição uma vez, de forma transparente.
            if ($acceptsJson || $isAjax || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sessão expirada. Atualizando credenciais, tente novamente.',
                    'csrf_token' => csrf_token(),
                ], 419);
            }

            // Requisições web normais (form submit): redireciona para o login.
            return redirect('/login')->with('error', 'Sua sessão expirou. Faça login novamente.');
        });
    })->create();
