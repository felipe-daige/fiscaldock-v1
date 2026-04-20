<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    private const AUTH_SUPPORT_VIEW = 'autenticado.suporte.index';

    public function index(Request $request)
    {
        $context = $this->buildPrefillData($request);

        if ($this->isAjaxRequest($request)) {
            return response()
                ->view(self::AUTH_SUPPORT_VIEW, $context)
                ->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge([
            'initialView' => self::AUTH_SUPPORT_VIEW,
        ], $context));
    }

    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'categoria' => 'required|string|in:duvida,problema_tecnico,cobranca,sugestao',
            'assunto' => 'required|string|max:150',
            'mensagem' => 'required|string|max:2000',
            'contexto' => 'nullable|string|max:120',
            'url_origem' => 'nullable|string|max:500',
            'mensagem_erro' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        Mail::to(config('mail.support_address'))
            ->send(new SupportTicket(
                user: $user,
                payload: [
                    'categoria' => $validated['categoria'],
                    'assunto' => Str::of($validated['assunto'])->trim()->toString(),
                    'mensagem' => Str::of($validated['mensagem'])->trim()->toString(),
                    'contexto' => $validated['contexto'] ?? null,
                    'url_origem' => $validated['url_origem'] ?? null,
                    'mensagem_erro' => $validated['mensagem_erro'] ?? null,
                    'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                    'ip' => $request->ip(),
                    'enviado_em' => now(),
                ],
            ));

        $successMessage = 'Solicitação enviada ao suporte com sucesso.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
            ]);
        }

        return redirect()
            ->route('app.suporte.index')
            ->with('support_success', $successMessage);
    }

    private function buildPrefillData(Request $request): array
    {
        $contexto = $this->sanitizeText($request->query('contexto'), 120);
        $urlOrigem = $this->sanitizeText($request->query('url'), 500);
        $mensagemErro = $this->sanitizeText($request->query('mensagem'), 500);

        $assunto = old('assunto');
        if ($assunto === null && $urlOrigem !== '') {
            $assunto = Str::limit('Erro em '.$urlOrigem, 150, '');
        }

        $mensagem = old('mensagem');
        if ($mensagem === null && $mensagemErro !== '') {
            $mensagem = trim($mensagemErro."\n\nDescreva abaixo o que estava fazendo quando o erro aconteceu:");
        }

        return [
            'supportCategories' => [
                'duvida' => 'Dúvida',
                'problema_tecnico' => 'Problema técnico',
                'cobranca' => 'Cobrança',
                'sugestao' => 'Sugestão',
            ],
            'prefillContext' => [
                'contexto' => old('contexto', $contexto),
                'url_origem' => old('url_origem', $urlOrigem),
                'mensagem_erro' => old('mensagem_erro', $mensagemErro),
                'assunto' => $assunto ?? '',
                'mensagem' => $mensagem ?? '',
                'categoria' => old('categoria', $contexto !== '' ? 'problema_tecnico' : 'duvida'),
            ],
        ];
    }

    private function sanitizeText(mixed $value, int $maxLength): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $sanitized = strip_tags((string) $value);
        $sanitized = preg_replace('/\s+/u', ' ', $sanitized) ?? '';

        return Str::limit(trim($sanitized), $maxLength, '');
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
