<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use App\Services\PricingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LandingPageController extends Controller
{
    private const BASE_URL = 'https://fiscaldock.com';

    /**
     * Tema padrão usado nas páginas públicas.
     */
    protected string $themeClass = 'theme-default';

    public function inicio(Request $request)
    {
        return $this->renderLanding($request, 'paginas.inicio', [
            'title' => 'FiscalDock | Radar de Riscos Fiscais',
            'description' => 'Monitore CNPJs, emita CND, CNDT e FGTS numa só consulta e detecte inconsistências no SPED antes da malha fiscal. Créditos prepagos, sem mensalidade.',
            'canonical' => self::BASE_URL . '/',
            'og_type' => 'website',
            'og_image' => self::BASE_URL . '/binary_files/logo/Logo FiscalDock.png',
        ]);
    }

    public function solucoes(Request $request)
    {
        return $this->renderLanding($request, 'solucoes.index', [
            'title' => 'Soluções — FiscalDock | Importação SPED, Monitoramento, BI Fiscal',
            'description' => 'Seis módulos num só radar fiscal: importação SPED/EFD, monitoramento de participantes, consultas CNPJ, BI fiscal, clearance de notas e central de alertas. Conheça os produtos.',
            'canonical' => self::BASE_URL . '/solucoes',
            'og_type' => 'website',
            'og_title' => 'Seis produtos. Um só radar fiscal — FiscalDock',
            'og_image' => self::BASE_URL . '/binary_files/logo/Logo FiscalDock.png',
        ]);
    }

    public function duvidas(Request $request)
    {
        return $this->renderLanding($request, 'paginas.duvidas', [
            'title' => 'Dúvidas Frequentes — FiscalDock',
            'description' => 'Respostas sobre importação SPED, monitoramento fiscal, créditos, faixas de economia e segurança dos dados na FiscalDock. Tire sua dúvida antes de começar.',
            'canonical' => self::BASE_URL . '/duvidas',
            'og_type' => 'website',
            'og_title' => 'Perguntas frequentes — FiscalDock',
            'og_image' => self::BASE_URL . '/binary_files/logo/Logo FiscalDock.png',
        ]);
    }

    public function precos(Request $request, PricingCatalogService $pricingCatalogService)
    {
        return $this->renderLanding($request, 'paginas.precos', [
            'title' => 'Preços — FiscalDock | Créditos e Faixas por Volume',
            'description' => 'Compre créditos avulsos e pague menos por consulta conforme seu volume acumulado. Modelo sem assinatura, com faixas de economia para Compliance e Clearance.',
            'canonical' => self::BASE_URL . '/precos',
            'og_type' => 'website',
            'og_title' => 'Créditos e faixas de economia — FiscalDock',
            'og_image' => self::BASE_URL . '/binary_files/logo/Logo FiscalDock.png',
        ], [
            'pricingData' => $pricingCatalogService->getLandingPricingData(),
        ]);
    }

    /**
     * Captura lead enviado pelo formulário inline do banner #contato da LP.
     * Salva o e-mail e redireciona para /criar-conta com o campo pré-preenchido.
     */
    public function capturarLead(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim($validated['email']));

        $rateKey = 'landing-lead:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return back()
                ->withErrors(['email' => 'Muitas tentativas. Tente novamente em alguns instantes.'])
                ->withInput();
        }
        RateLimiter::hit($rateKey, 60);

        LandingLead::create([
            'email' => $email,
            'origem' => $request->input('origem', 'banner_contato'),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('signup', ['email' => $email]);
    }

    /**
     * Renderiza uma view da landing page aplicando o tema padrão e redirecionando
     * usuários autenticados para o dashboard.
     */
    private function renderLanding(Request $request, string $viewName, array $seo = [], array $viewData = [])
    {
        $fullViewName = "landing_page.$viewName";

        if (!view()->exists($fullViewName)) {
            abort(404);
        }

        if (Auth::check()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Você já está logado',
                    'redirect' => '/app/dashboard',
                ]);
            }

            return redirect('/app/dashboard');
        }

        if ($request->ajax()) {
            return view($fullViewName, $viewData);
        }

        return view('landing_page.layouts.public', array_merge([
            'initialView' => $viewName,
            'themeClass' => $this->themeClass,
            'seo' => $seo,
        ], $viewData));
    }
}
