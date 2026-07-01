<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function mostrarFormularioEsqueciSenha(Request $request)
    {
        if ($request->ajax()) {
            return view('landing_page.auth.esqueci-senha');
        }

        return view('landing_page.layouts.public', [
            'initialView' => 'auth.esqueci-senha',
            'seo' => [
                'title' => 'Esqueci minha senha — FiscalDock',
                'robots' => 'noindex,nofollow',
            ],
        ]);
    }

    public function enviarLinkReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        PasswordBroker::sendResetLink($request->only('email'));

        // Anti-enumeração: a resposta é sempre a mesma, exista ou não o e-mail.
        $mensagem = 'Se este e-mail estiver cadastrado, você vai receber um link para redefinir sua senha.';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $mensagem]);
        }

        return back()->with('status', $mensagem);
    }

    public function mostrarFormularioReset(Request $request, string $token)
    {
        $email = $request->query('email', '');

        if ($request->ajax()) {
            return view('landing_page.auth.redefinir-senha', compact('token', 'email'));
        }

        return view('landing_page.layouts.public', [
            'initialView' => 'auth.redefinir-senha',
            'token' => $token,
            'email' => $email,
            'seo' => [
                'title' => 'Redefinir Senha — FiscalDock',
                'robots' => 'noindex,nofollow',
            ],
        ]);
    }

    public function resetar(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->uncompromised()],
        ], [
            'token.required' => 'Link de redefinição inválido.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'password.letters' => 'A senha deve conter pelo menos uma letra.',
            'password.numbers' => 'A senha deve conter pelo menos um número.',
            'password.uncompromised' => 'Esta senha apareceu em vazamentos públicos de dados. Por segurança, escolha outra.',
        ]);

        $status = PasswordBroker::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            $mensagem = 'Link inválido ou expirado. Solicite um novo.';

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $mensagem], 422);
            }

            return back()->withErrors(['email' => $mensagem])->withInput($request->only('email'));
        }

        $mensagem = 'Senha redefinida com sucesso! Faça login com sua nova senha.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('status', $mensagem);
    }
}
