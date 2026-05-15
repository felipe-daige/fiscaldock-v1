@php
    $supportError = app(\App\Support\SystemCriticalError::class);
    $usuario = \Illuminate\Support\Facades\Auth::user();
    $supportUrl = $supportError->buildSupportUrl([
        'context' => 'Erro 500 ao acessar a página',
        'url' => request()->fullUrl(),
        'reference' => $usuario?->email,
    ]);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro inesperado | FiscalDock</title>
    <link rel="icon" type="image/png" href="{{ asset('binary_files/logo/Logo FiscalDock.png') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f3f4f6;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            max-width: 520px;
            width: 100%;
            padding: 32px 28px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .logo { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .logo img { height: 28px; width: auto; }
        .logo span { font-weight: 700; font-size: 18px; color: #111827; }
        .eyebrow {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #9ca3af;
            margin: 0 0 8px;
        }
        h1 { font-size: 20px; font-weight: 700; margin: 0 0 12px; color: #111827; }
        p { font-size: 14px; line-height: 1.55; color: #374151; margin: 0 0 8px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: opacity 0.15s ease, transform 0.15s ease;
        }
        .btn:hover { opacity: 0.92; }
        .btn-primary {
            background-color: #25D366;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(37, 211, 102, 0.25);
        }
        .btn-primary:hover { background-color: #1ebe57; }
        .btn-secondary { background-color: #ffffff; color: #1f2937; border-color: #d1d5db; }
        .meta {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 12px;
            color: #6b7280;
            word-break: break-all;
        }
        .meta strong { color: #374151; font-weight: 600; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="logo">
            <img src="{{ asset('binary_files/logo/logo-fiscaldock_whitebg-removebg.png') }}" alt="FiscalDock">
            <span>FiscalDock</span>
        </div>

        <p class="eyebrow">Erro 500</p>
        <h1>Algo deu errado por aqui</h1>
        <p>Ocorreu uma instabilidade interna ao processar sua solicitação. Já registramos o erro — se preferir agilizar, fale com o suporte e nós te ajudamos.</p>

        <div class="actions">
            <a class="btn btn-primary" href="{{ $supportUrl }}" target="_blank" rel="noopener noreferrer">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Falar com o suporte no WhatsApp
            </a>
            <a class="btn btn-secondary" href="/app/dashboard">Voltar ao painel</a>
        </div>

        <div class="meta">
            <strong>URL:</strong> {{ request()->fullUrl() }}<br>
            <strong>Horário:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </main>
</body>
</html>
