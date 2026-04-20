<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\LandingLead;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    public function showLogin(Request $request){
        if(!view()->exists("landing_page.auth.login")){
            abort(404);
        }

        if(Auth::check()){
            if($request->ajax()){
                return response()->json([
                    'success' => true,
                    'message' => 'Você já está logado',
                    'redirect' => '/app/dashboard'
                ]);
            }
            return redirect('/app/dashboard');
        }

        if($request->ajax()){
            return view("landing_page.auth.login");
        }
        return view("landing_page.layouts.public", [
            'initialView' => 'auth.login',
            'seo' => [
                'title' => 'Login — FiscalDock',
                'description' => 'Acesse sua conta FiscalDock para gerenciar importações de SPED, monitoramento fiscal e consultas tributárias.',
                'canonical' => 'https://fiscaldock.com/login',
                'robots' => 'noindex,follow',
            ],
        ]);
    }
    public function showAgendar(Request $request){
        if(!view()->exists("landing_page.auth.agendar")){
            abort(404);
        }

        $email = trim((string) $request->query('email', ''));
        $whatsAppMessage = 'Olá! Quero falar com a FiscalDock sobre a plataforma.';

        if ($email !== '') {
            $whatsAppMessage = "Olá! Quero falar com a FiscalDock sobre a plataforma. Meu e-mail é {$email}.";
        }

        if($request->ajax()){
            return view("landing_page.auth.agendar", [
                'whatsAppUrl' => 'https://wa.me/5567999844366?text=' . rawurlencode($whatsAppMessage),
            ]);
        }
        return view("landing_page.layouts.public", [
            'initialView' => 'auth.agendar',
            'seo' => [
                'title' => 'Contato Comercial — FiscalDock',
                'description' => 'Fale com a FiscalDock por WhatsApp ou e-mail para tirar dúvidas comerciais e entender como a plataforma se encaixa na sua operação fiscal.',
                'canonical' => 'https://fiscaldock.com/agendar',
                'robots' => 'noindex,follow',
                'og_type' => 'website',
                'og_title' => 'Contato comercial — FiscalDock',
                'og_image' => 'https://fiscaldock.com/binary_files/logo/Logo FiscalDock.png',
            ],
            'whatsAppUrl' => 'https://wa.me/5567999844366?text=' . rawurlencode($whatsAppMessage),
        ]);
    }

    public function showSignup(Request $request)
    {
        if (! view()->exists('landing_page.auth.criar-conta')) {
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

        if ($request->filled('email') && ! session()->hasOldInput('email')) {
            session()->flashInput(['email' => $request->query('email')]);
        }

        if ($request->ajax()) {
            return view('landing_page.auth.criar-conta');
        }

        return view('landing_page.layouts.public', [
            'initialView' => 'auth.criar-conta',
            'seo' => [
                'title' => 'Criar Conta Grátis — FiscalDock',
                'description' => 'Crie sua conta FiscalDock e receba 100 créditos grátis para usar em até 30 dias.',
                'canonical' => 'https://fiscaldock.com/criar-conta',
                'robots' => 'index,follow',
                'og_type' => 'website',
                'og_title' => 'Crie sua conta grátis — FiscalDock',
                'og_image' => 'https://fiscaldock.com/binary_files/logo/Logo FiscalDock.png',
            ],
        ]);
    }

    public function showTerms(Request $request)
    {
        if (! view()->exists('landing_page.paginas.termos')) {
            abort(404);
        }

        if ($request->ajax()) {
            return view('landing_page.paginas.termos');
        }

        return view('landing_page.layouts.public', [
            'initialView' => 'paginas.termos',
            'seo' => [
                'title' => 'Termos de Uso — FiscalDock',
                'description' => 'Leia os termos de uso da FiscalDock para o uso das páginas públicas, canais de contato e plataforma.',
                'canonical' => 'https://fiscaldock.com/termos',
                'robots' => 'index,follow',
                'og_type' => 'website',
                'og_title' => 'Termos de uso — FiscalDock',
                'og_image' => 'https://fiscaldock.com/binary_files/logo/Logo FiscalDock.png',
            ],
        ]);
    }

    public function showPrivacy(Request $request)
    {
        if (! view()->exists('landing_page.paginas.privacidade')) {
            abort(404);
        }

        if ($request->ajax()) {
            return view('landing_page.paginas.privacidade');
        }

        return view('landing_page.layouts.public', [
            'initialView' => 'paginas.privacidade',
            'seo' => [
                'title' => 'Política de Privacidade — FiscalDock',
                'description' => 'Veja como a FiscalDock coleta, utiliza e protege dados pessoais em seus canais públicos e fluxos comerciais.',
                'canonical' => 'https://fiscaldock.com/privacidade',
                'robots' => 'index,follow',
                'og_type' => 'website',
                'og_title' => 'Política de privacidade — FiscalDock',
                'og_image' => 'https://fiscaldock.com/binary_files/logo/Logo FiscalDock.png',
            ],
        ]);
    }

    public function login(Request $request){
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ], [
                'email.required' => 'O campo email é obrigatório',
                'email.email' => 'O campo email deve ser um email válido',
                'password.required' => 'O campo senha é obrigatório',
                'password.min' => 'A senha deve ter pelo menos 8 caracteres',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            LOG::info($e->errors());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $e->errors()
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        }

        $credentials = $request->only('email', 'password');

        // Log para debug
        Log::info('Tentativa de login', [
            'email' => $credentials['email'] ?? 'não fornecido',
            'password_length' => isset($credentials['password']) ? strlen($credentials['password']) : 0,
            'credentials_keys' => array_keys($credentials)
        ]);

        $user = Auth::attempt($credentials);

        if(!$user){
            // Verificar se o usuário existe
            $userExists = User::where('email', $credentials['email'] ?? '')->first();
            Log::warning('Falha no login', [
                'email' => $credentials['email'] ?? 'não fornecido',
                'user_exists' => $userExists ? 'sim' : 'não',
                'user_id' => $userExists?->id
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou senha inválidos',
                ], 401);
            }
            return back()->withErrors(['email' => 'Email ou senha inválidos'])->withInput();
        }

        Log::info('Login bem-sucedido', [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'redirect' => '/app/dashboard'
            ]);
        }
        return redirect('/app/dashboard');
    }

    public function signup(Request $request)
    {
        try {
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'sobrenome' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telefone' => 'required|string|max:20',
                'senha' => 'required|min:8|confirmed',
                'empresa' => 'required|string|max:255',
                'cargo' => 'required|string|max:255',
                'documento' => 'required|string|max:18',
                'faturamento' => 'required|string|max:255',
                'desafio_principal' => 'required|string|max:100',
                'terms_aceitos' => 'accepted',
                'marketing_opt_in' => 'nullable|boolean',
            ], [
                'terms_aceitos.accepted' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade.',
            ]);

            $validated['telefone'] = $this->normalizePhone($validated['telefone']);
            $validated['documento'] = $this->normalizeDocument($validated['documento']);

            if (! in_array(strlen($validated['documento']), [11, 14], true)) {
                throw ValidationException::withMessages([
                    'documento' => 'Informe um CPF ou CNPJ válido.',
                ]);
            }

            $conflictMessage = $this->detectSignupConflict($validated);
            if ($conflictMessage) {
                throw ValidationException::withMessages([
                    'email' => $conflictMessage,
                ]);
            }
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }

        return $this->createTrialAccount($request, $validated);
    }

    public function agendar(Request $request){
        $message = 'O cadastro direto por /agendar foi desativado. Fale com a FiscalDock pelo WhatsApp ou e-mail nesta página.';

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('agendar'),
                'whatsapp_url' => 'https://wa.me/5567999844366',
            ], 410);
        }

        return redirect()
            ->route('agendar')
            ->with('contact_notice', $message);
    }

    private function createTrialAccount(Request $request, array $validated): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        DB::beginTransaction();

        try {
            $tipoPessoa = strlen($validated['documento']) <= 11 ? 'PF' : 'PJ';

            $user = User::create([
                'name' => $validated['nome'],
                'sobrenome' => $validated['sobrenome'],
                'email' => mb_strtolower(trim($validated['email'])),
                'telefone' => $validated['telefone'],
                'password' => Hash::make($validated['senha']),
                'empresa' => $validated['empresa'],
                'cargo' => $validated['cargo'],
                'cnpj' => $validated['documento'],
                'faturamento_anual' => $validated['faturamento'],
                'desafio_principal' => $validated['desafio_principal'],
                'terms_accepted_at' => now(),
                'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
                'marketing_opt_in_at' => ! empty($validated['marketing_opt_in']) ? now() : null,
            ]);

            Cliente::create([
                'user_id' => $user->id,
                'tipo_pessoa' => $tipoPessoa,
                'documento' => $validated['documento'],
                'nome' => $validated['empresa'],
                'razao_social' => $validated['empresa'],
                'telefone' => $validated['telefone'],
                'email' => mb_strtolower(trim($validated['email'])),
                'is_empresa_propria' => true,
            ]);

            $this->creditService->grantTrial($user, 100, now()->addDays(30));

            Auth::login($user);

            LandingLead::markConvertedByEmail($validated['email']);

            DB::commit();

            $message = 'Conta criada com sucesso. Você recebeu 100 créditos grátis por 30 dias.';

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => '/app/dashboard',
                ]);
            }

            return redirect('/app/dashboard')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erro ao criar conta trial', [
                'message' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar a conta. Tente novamente.',
                ], 500);
            }

            return back()->withErrors([
                'email' => 'Erro ao criar a conta. Tente novamente.',
            ])->withInput();
        }
    }

    private function detectSignupConflict(array $validated): ?string
    {
        $email = mb_strtolower(trim($validated['email']));
        $telefone = $validated['telefone'];
        $documento = $validated['documento'];

        if (User::where('email', $email)->exists()) {
            return 'Já existe uma conta ou trial para este e-mail. Faça login ou fale com nosso time.';
        }

        if (User::where('telefone', $telefone)->exists()) {
            return 'Este telefone já foi usado em outra conta ou trial. Fale com nosso time se precisar de ajuda.';
        }

        if (User::where('cnpj', $documento)->exists()) {
            return 'Este CPF/CNPJ já foi usado em outra conta ou trial. Fale com nosso time se precisar de ajuda.';
        }

        $sameProfile = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['nome']))])
            ->whereRaw('LOWER(sobrenome) = ?', [mb_strtolower(trim($validated['sobrenome']))])
            ->whereRaw('LOWER(empresa) = ?', [mb_strtolower(trim($validated['empresa']))])
            ->exists();

        if ($sameProfile) {
            return 'Já encontramos um cadastro muito parecido com estes dados. Fale com nosso time para evitar duplicidade.';
        }

        return null;
    }

    private function normalizePhone(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    private function normalizeDocument(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if($request->ajax()){
            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso',
                'redirect' => '/inicio'
            ]);
        }
        
        return redirect('/inicio');
    }
}
