<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SystemCriticalError
{
    public function forAsyncFailure(?string $internalMessage = null, ?string $internalCode = null, array $context = []): array
    {
        return [
            'title' => 'Falha no processamento',
            'message' => 'Ocorreu uma instabilidade interna ao processar sua solicitação. Nosso suporte pode ajudar você a concluir essa etapa.',
            'action_label' => config('support.contact_label', 'Falar com o suporte'),
            'action_url' => $this->buildSupportUrl($context),
            'action_target' => '_blank',
            'action_rel' => 'noopener noreferrer',
        ];
    }

    public function forTimeout(array $context = []): array
    {
        return [
            'title' => 'Processamento indisponível no momento',
            'message' => 'A solicitação levou mais tempo do que o esperado para ser concluída. Nosso suporte pode acompanhar esse caso com você.',
            'action_label' => config('support.contact_label', 'Falar com o suporte'),
            'action_url' => $this->buildSupportUrl($context),
            'action_target' => '_blank',
            'action_rel' => 'noopener noreferrer',
        ];
    }

    public function publicMessage(?string $internalMessage = null, ?string $internalCode = null, array $context = []): string
    {
        return $this->forAsyncFailure($internalMessage, $internalCode, $context)['message'];
    }

    public function buildSupportUrl(array $context = []): string
    {
        $baseUrl = (string) config('support.whatsapp_url', 'https://wa.me/5567999844366');
        $message = $this->buildSupportMessage($context);
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.'text='.rawurlencode($message);
    }

    private function buildSupportMessage(array $context = []): string
    {
        $lines = [
            'Olá, preciso de suporte com uma falha de processamento na FiscalDock.',
        ];

        $origin = $this->sanitizeContextValue($context['context'] ?? $context['action'] ?? null, 80);
        $url = $this->sanitizeContextValue($context['url'] ?? null, 240);
        $reference = $this->sanitizeContextValue($context['reference'] ?? null, 80);

        if ($origin !== '') {
            $lines[] = 'Contexto: '.$origin;
        }

        if ($reference !== '') {
            $lines[] = 'Referência: '.$reference;
        }

        if ($url !== '') {
            $lines[] = 'Página: '.$url;
        }

        return implode("\n", $lines);
    }

    private function sanitizeContextValue(mixed $value, int $maxLength): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $sanitized = strip_tags((string) $value);
        $sanitized = preg_replace('/\s+/u', ' ', $sanitized) ?? '';

        return Str::limit(trim($sanitized), $maxLength, '');
    }
}
