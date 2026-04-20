<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogHttpRequests
{
    /**
     * Campos sensíveis que devem ser sanitizados nos logs.
     */
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'key',
        'api_key',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_', true);

        // Captura informações da requisição antes do processamento
        $requestData = $this->captureRequestData($request, $requestId);

        // Processa a requisição
        $response = $next($request);

        // Calcula o tempo de processamento
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Adiciona informações da resposta
        $requestData['response'] = [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ];

        // Determina o nível de log baseado no status code
        $logLevel = $this->getLogLevel($response->getStatusCode());

        // Loga TODA requisição HTTP/HTTPS diretamente no arquivo
        $this->writeToLogFile($request, $response, $requestData, $duration, $logLevel);

        return $response;
    }

    /**
     * Escreve a requisição diretamente no arquivo de log.
     */
    private function writeToLogFile(
        Request $request,
        Response $response,
        array $requestData,
        float $duration,
        string $logLevel
    ): void {
        $logFile = storage_path('logs/laravel.log');
        
        // Garante que o diretório existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Formata a mensagem de log
        $timestamp = date('Y-m-d H:i:s');
        $method = $request->method();
        $url = $request->fullUrl();
        $statusCode = $response->getStatusCode();
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? 'N/A';

        // Linha principal do log
        $logEntry = sprintf(
            "[%s] %s.%s: %s %s | IP: %s | Status: %d | Duration: %.2fms | User-Agent: %s",
            $timestamp,
            config('app.env', 'local'),
            strtoupper($logLevel),
            $method,
            $url,
            $ip,
            $statusCode,
            $duration,
            $userAgent
        ) . PHP_EOL;

        // Adiciona informações adicionais
        if (!empty($requestData['query'])) {
            $logEntry .= sprintf(
                "  Query: %s",
                json_encode($requestData['query'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ) . PHP_EOL;
        }

        if (!empty($requestData['body']) && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $logEntry .= sprintf(
                "  Body: %s",
                json_encode($requestData['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ) . PHP_EOL;
        }

        if (!empty($requestData['user'])) {
            $logEntry .= sprintf(
                "  User: ID=%d, Email=%s",
                $requestData['user']['id'],
                $requestData['user']['email'] ?? 'N/A'
            ) . PHP_EOL;
        }

        if (!empty($requestData['headers'])) {
            $logEntry .= sprintf(
                "  Headers: %s",
                json_encode($requestData['headers'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ) . PHP_EOL;
        }

        $logEntry .= "---" . PHP_EOL;

        // Escreve apenas no laravel.log
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Também usa o sistema de log do Laravel para aparecer no laravel.log com formatação padrão
        try {
            Log::{$logLevel}(
                sprintf(
                    '%s %s - %s - %dms',
                    $method,
                    $url,
                    $statusCode,
                    $duration
                ),
                [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'query' => $requestData['query'] ?? null,
                    'user' => $requestData['user'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            // Se falhar, ignora silenciosamente
        }
    }

    /**
     * Captura dados da requisição para logging.
     */
    private function captureRequestData(Request $request, string $requestId): array
    {
        $data = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $this->captureHeaders($request),
        ];

        // Adiciona body apenas para métodos que podem ter corpo
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $data['body'] = $this->sanitizeData($request->all());
        }

        // Adiciona query string se existir
        $queryString = $request->getQueryString();
        if ($queryString) {
            parse_str($queryString, $queryParams);
            if (!empty($queryParams)) {
                $data['query'] = $this->sanitizeData($queryParams);
            }
        }

        // Adiciona informações do usuário autenticado se existir
        if ($request->user()) {
            $data['user'] = [
                'id' => $request->user()->id,
                'email' => $request->user()->email ?? null,
            ];
        }

        return $data;
    }

    /**
     * Captura headers relevantes da requisição.
     */
    private function captureHeaders(Request $request): array
    {
        $relevantHeaders = [
            'content-type',
            'accept',
            'accept-language',
            'referer',
            'origin',
        ];

        $headers = [];

        foreach ($relevantHeaders as $header) {
            if ($request->hasHeader($header)) {
                $headers[$header] = $request->header($header);
            }
        }

        // Para Authorization, apenas indica se existe (não loga o valor completo)
        if ($request->hasHeader('authorization')) {
            $authHeader = $request->header('authorization');
            if ($authHeader) {
                // Extrai apenas o tipo (Bearer, Basic, etc.)
                $authType = explode(' ', $authHeader, 2)[0] ?? 'Unknown';
                $headers['authorization'] = $authType . ' [REDACTED]';
            }
        }

        return $headers;
    }

    /**
     * Sanitiza dados removendo campos sensíveis.
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Verifica se o campo é sensível
            $isSensitive = false;
            foreach ($this->sensitiveFields as $sensitiveField) {
                if (str_contains($lowerKey, $sensitiveField)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                // Limita o tamanho de strings muito grandes (ex: arquivos base64)
                if (is_string($value) && strlen($value) > 1000) {
                    $sanitized[$key] = substr($value, 0, 1000) . '...[TRUNCATED]';
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Determina o nível de log baseado no status code da resposta.
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }
}
