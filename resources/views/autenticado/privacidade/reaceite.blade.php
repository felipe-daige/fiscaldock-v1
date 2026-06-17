<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Atualizamos nossos termos | FiscalDock</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-lg bg-white rounded-lg border border-gray-300 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200" style="border-top: 4px solid #0b1f3a">
                <h1 class="text-lg font-bold text-gray-900">Atualizamos nossos termos</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Revisamos os <strong>Termos de Uso</strong> e a <strong>Política de Privacidade</strong> da FiscalDock.
                    Para continuar usando a plataforma, precisamos do seu aceite na versão atual.
                </p>
            </div>

            <div class="px-6 py-5">
                <div class="flex flex-wrap gap-3 mb-5 text-sm">
                    <a href="{{ route('termos') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Ler os Termos de Uso (v{{ config('legal.terms_version') }})
                    </a>
                    <a href="{{ route('privacidade') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Ler a Política de Privacidade (v{{ config('legal.privacy_version') }})
                    </a>
                </div>

                @if($errors->any())
                    <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 p-3 text-sm text-gray-700" style="border-left-color: #dc2626">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('app.reaceite.aceitar') }}" id="form-reaceite">
                    @csrf
                    <label class="flex items-start gap-2.5 mb-5 cursor-pointer">
                        <input type="checkbox" name="aceito" value="1" class="mt-0.5 w-4 h-4">
                        <span class="text-sm text-gray-700">
                            Li e aceito os Termos de Uso e a Política de Privacidade atualizados.
                        </span>
                    </label>
                </form>

                <div class="flex items-center justify-between gap-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[13px] text-gray-500 hover:underline">Sair</button>
                    </form>
                    <button type="submit" form="form-reaceite" class="px-5 py-2.5 rounded text-[13px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #0b1f3a">
                        Aceitar e continuar
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
