<div class="sidebar__overlay" id="sidebar-overlay"></div>

<x-sidebar.layout>
    {{-- PAINEL --}}
    <x-sidebar.section title="PAINEL">
        <x-sidebar.item href="/app/dashboard">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
            </x-slot:icon>
            Dashboard
        </x-sidebar.item>

        <x-sidebar.item href="/app/alertas" :badge="($alertasAtivosCount ?? 0) > 0 ? $alertasAtivosCount : null" badge-label="Alertas ativos">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </x-slot:icon>
            Alertas
        </x-sidebar.item>
    </x-sidebar.section>

    {{-- DOCUMENTOS --}}
    <x-sidebar.section title="DOCUMENTOS">
        <x-sidebar.group title="Notas Fiscais" :open="request()->is('app/notas-fiscais*') || request()->is('app/catalogo*')">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>

            <x-sidebar.group-item href="/app/notas-fiscais">Listagem</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/notas-fiscais/dashboard">Dashboard</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/catalogo">Catálogo</x-sidebar.group-item>
        </x-sidebar.group>

        <x-sidebar.group title="Importação" :open="request()->is('app/importacao/*')">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                </svg>
            </x-slot:icon>

            <x-sidebar.group-item href="/app/importacao/efd">EFD</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/importacao/xml" pill="Em Breve">XML</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/importacao/historico">Histórico</x-sidebar.group-item>
        </x-sidebar.group>
    </x-sidebar.section>

    {{-- CLEARANCE NF-e --}}
    <x-sidebar.section title="CLEARANCE NF-e">
        <x-sidebar.item href="/app/clearance/dashboard">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </x-slot:icon>
            Dashboard
        </x-sidebar.item>

        <x-sidebar.item href="/app/clearance/notas">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </x-slot:icon>
            Verificar Notas
        </x-sidebar.item>

        <x-sidebar.item href="/app/clearance/buscar">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.6-5.4a7 7 0 11-14 0 7 7 0 0114 0zM9 10h4m-4 3h2"></path>
                </svg>
            </x-slot:icon>
            Buscar Notas
        </x-sidebar.item>
    </x-sidebar.section>

    {{-- INTELIGÊNCIA --}}
    <x-sidebar.section title="INTELIGÊNCIA">
        <x-sidebar.item href="/app/bi/dashboard">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
            </x-slot:icon>
            BI Fiscal
        </x-sidebar.item>

        <x-sidebar.item href="/app/resumo-fiscal">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </x-slot:icon>
            Resumo Fiscal
        </x-sidebar.item>
    </x-sidebar.section>

    {{-- CONSULTAS --}}
    <x-sidebar.section title="CONSULTAS">
        <x-sidebar.group title="Consulta CNPJ" :open="request()->is('app/consulta/*')">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </x-slot:icon>

            <x-sidebar.group-item href="/app/consulta/nova">Nova Consulta</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/consulta/historico">Histórico</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/consulta/planos">Planos</x-sidebar.group-item>
        </x-sidebar.group>

        <x-sidebar.item href="/app/score-fiscal">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </x-slot:icon>
            Score Fiscal
        </x-sidebar.item>
    </x-sidebar.section>

    {{-- CADASTROS --}}
    <x-sidebar.section title="CADASTROS">
        <x-sidebar.item href="/app/minha-empresa">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </x-slot:icon>
            Empresa
        </x-sidebar.item>

        <x-sidebar.group title="Clientes" :open="request()->is('app/clientes*') || request()->is('app/cliente/*')">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </x-slot:icon>

            <x-sidebar.group-item href="/app/clientes">Listagem</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/cliente/novo">Novo Cliente</x-sidebar.group-item>
        </x-sidebar.group>

        <x-sidebar.group title="Participantes" :open="request()->is('app/participantes*') || request()->is('app/participante/*')">
            <x-slot:icon>
                <svg class="sidebar__item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </x-slot:icon>

            <x-sidebar.group-item href="/app/participantes">Listagem</x-sidebar.group-item>
            <x-sidebar.group-item href="/app/participante/novo">Novo Participante</x-sidebar.group-item>
        </x-sidebar.group>
    </x-sidebar.section>

    <x-slot:footer>
        <div class="sidebar__user">
            <details class="group/user-details sidebar__user-panel flex flex-col-reverse marker:content-none [&::-webkit-details-marker]:hidden">
                <summary class="sidebar__user-trigger outline-none select-none">
                    <svg class="sidebar__user-avatar" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="min-w-0 flex-1">
                        <span class="sidebar__user-name">{{ Auth::user()->name ?? 'Usuário' }}</span>
                        <span class="sidebar__user-role">Conta</span>
                    </span>
                    <svg class="sidebar__group-arrow transition-transform duration-200 group-open/user-details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </summary>

                <div class="sidebar__user-menu group-open/user-details:block hidden">
                    <a href="/app/perfil" data-link data-sidebar-user-link class="sidebar__user-menu-item">
                        <svg class="sidebar__user-menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="sidebar__item-label">Perfil</span>
                    </a>
                    <a href="/app/plano" data-link data-sidebar-user-link class="sidebar__user-menu-item">
                        <svg class="sidebar__user-menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span class="sidebar__item-label">Faixa Comercial</span>
                    </a>
                    <a href="/app/creditos" data-link data-sidebar-user-link class="sidebar__user-menu-item">
                        <svg class="sidebar__user-menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                        <span class="sidebar__item-label">Créditos</span>
                    </a>
                    <a href="/app/configuracoes" data-link data-sidebar-user-link class="sidebar__user-menu-item">
                        <svg class="sidebar__user-menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="sidebar__item-label">Configurações</span>
                    </a>
                    <a href="/app/suporte" data-link data-sidebar-user-link class="sidebar__user-menu-item">
                        <svg class="sidebar__user-menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636A9 9 0 105.636 18.364 9 9 0 0018.364 5.636zM9.879 9.879a3 3 0 014.243 4.243m-4.243-4.243L12 12m2.121 2.121L12 12m0 0l-2.121-2.121M12 12l2.121 2.121"></path>
                        </svg>
                        <span class="sidebar__item-label">Suporte</span>
                    </a>
                </div>
            </details>

            <div class="sidebar__logout">
                <form action="{{ route('logout') }}" method="POST" id="logout-form-header">
                    @csrf
                    <button type="submit" class="sidebar__logout-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="sidebar__logout-label">Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </x-slot:footer>
</x-sidebar.layout>
