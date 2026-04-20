@php
    $user ??= Auth::user();
    $fullName = trim(($user->name ?? '') . ' ' . ($user->sobrenome ?? ''));
    $fullName = $fullName !== '' ? $fullName : ($user->email ?? 'Usuário');
    $initials = strtoupper(trim(sprintf('%s%s',
        mb_substr(trim($user->name ?? ''), 0, 1),
        ! empty($user->sobrenome) ? mb_substr($user->sobrenome, 0, 1) : ''
    )));
    $initials = $initials !== '' ? $initials : 'U';
    $emailVerified = ! empty($user->email_verified_at);
    $emailStatusLabel = $emailVerified ? 'Verificado' : 'Não verificado';
    $emailStatusHex = $emailVerified ? '#047857' : '#d97706';
    $accountStatusLabel = 'Ativo';
    $accountStatusHex = '#047857';
    $memberSince = $user->created_at ? $user->created_at->format('d/m/Y') : '—';
    $creditAmount = number_format($user->credits ?? 0, 0, ',', '.');
@endphp

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">

        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Perfil</h1>
            <p class="text-xs text-gray-500 mt-1">Informações pessoais, créditos e status da conta.</p>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados do usuário</span>
            </div>
            <div class="px-4 py-6 space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded border border-gray-200 bg-gray-50 flex items-center justify-center text-xl font-bold text-gray-900">
                        {{ $initials }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $fullName }}</p>
                        <p class="text-[11px] text-gray-500">{{ $user->email ?? '—' }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nome</dt>
                        <dd class="text-sm text-gray-900">{{ $user->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Sobrenome</dt>
                        <dd class="text-sm text-gray-900">{{ $user->sobrenome ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail</dt>
                        <dd class="text-sm text-gray-900">{{ $user->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Telefone</dt>
                        <dd class="text-sm text-gray-900">{{ $user->telefone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Créditos</span>
            </div>
            <div class="p-6 text-center space-y-2">
                <p class="text-sm text-gray-500">Saldo disponível</p>
                <p class="text-4xl font-bold text-gray-900">{{ $creditAmount }}</p>
                <p class="text-[11px] text-gray-500">créditos</p>
                <div class="mt-4">
                    <a href="/app/creditos" data-link class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded" style="background-color: #1f2937">
                        Comprar créditos
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Informações da conta</span>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Membro desde</p>
                    <p class="text-sm text-gray-900">{{ $memberSince }}</p>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">ID</p>
                    <p class="text-sm text-gray-900">{{ $user->id ?? '—' }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">E-mail</p>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $emailStatusHex }}">
                            {{ $emailStatusLabel }}
                        </span>
                        <p class="text-sm text-gray-900">{{ $user->email ?? '—' }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status</p>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white inline-flex" style="background-color: {{ $accountStatusHex }}">
                        {{ $accountStatusLabel }}
                    </span>
                    <p class="text-[10px] text-gray-500">Credenciais ativas</p>
                </div>
            </div>
        </div>

    </div>
</div>
