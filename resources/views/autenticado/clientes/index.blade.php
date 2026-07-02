{{-- Clientes - Autenticado --}}
<div class="bg-gray-100 min-h-screen" id="clientes-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Clientes</h1>
                    <p class="mt-1 text-xs text-gray-500">Cadastros operacionais, vínculos com participantes e ações da base de clientes.</p>
                </div>
                <a href="/app/cliente/novo" data-link class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Cliente
                </a>
            </div>

            <div id="clientes-error-region"></div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ativos</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($totalAtivos ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Cadastros ativos</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Inativos</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($totalInativos ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Cadastros inativos</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Pessoa Jurídica</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($totalPJ ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">CNPJs cadastrados</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Pessoa Física</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($totalPF ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">CPFs cadastrados</p>
                    </div>
                </div>
            </div>

            <form method="GET" action="/app/clientes" class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
                </div>
                <div class="p-4">
                    @php
                        $avancadosCliKeys = ['tipo', 'uf', 'regime', 'status', 'importacao'];
                        $avancadosCliAtivos = collect($avancadosCliKeys)->filter(fn ($k) => ! empty($filtros[$k] ?? null))->count();
                    @endphp

                    {{-- Filtros básicos (sempre visíveis) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Buscar</label>
                            <input
                                type="text"
                                name="busca"
                                value="{{ $filtros['busca'] ?? '' }}"
                                placeholder="Nome, CNPJ ou CPF..."
                                class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            >
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Status da consulta</label>
                            <select name="status_consulta" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Qualquer status</option>
                                <option value="nunca" {{ ($filtros['status_consulta'] ?? '') === 'nunca' ? 'selected' : '' }}>Nunca consultado</option>
                                <option value="desatualizada" {{ ($filtros['status_consulta'] ?? '') === 'desatualizada' ? 'selected' : '' }}>Desatualizada (+30 dias)</option>
                                <option value="recente" {{ ($filtros['status_consulta'] ?? '') === 'recente' ? 'selected' : '' }}>Recente (até 30 dias)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Regularidade (CND)</label>
                            <select name="regularidade" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todas</option>
                                <option value="regular" {{ ($filtros['regularidade'] ?? '') === 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="irregular" {{ ($filtros['regularidade'] ?? '') === 'irregular' ? 'selected' : '' }}>Irregular</option>
                                <option value="indeterminada" {{ ($filtros['regularidade'] ?? '') === 'indeterminada' ? 'selected' : '' }}>Indeterminada</option>
                                <option value="nao_consultado" {{ ($filtros['regularidade'] ?? '') === 'nao_consultado' ? 'selected' : '' }}>Não consultado</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Situação cadastral</label>
                            <select name="situacao" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todas</option>
                                <option value="ATIVA" {{ ($filtros['situacao'] ?? '') === 'ATIVA' ? 'selected' : '' }}>Ativa</option>
                                <option value="BAIXADA" {{ ($filtros['situacao'] ?? '') === 'BAIXADA' ? 'selected' : '' }}>Baixada</option>
                                <option value="SUSPENSA" {{ ($filtros['situacao'] ?? '') === 'SUSPENSA' ? 'selected' : '' }}>Suspensa</option>
                                <option value="INAPTA" {{ ($filtros['situacao'] ?? '') === 'INAPTA' ? 'selected' : '' }}>Inapta</option>
                            </select>
                        </div>
                    </div>

                    {{-- Toggle "Mais filtros" --}}
                    <div class="mt-3">
                        <button type="button" onclick="var a=document.getElementById('filtros-avancados-cli'); a.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180');"
                            class="inline-flex items-center gap-1.5 text-[13px] text-gray-600 hover:text-gray-900 font-medium">
                            <svg class="w-3.5 h-3.5 transition-transform {{ $avancadosCliAtivos > 0 ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            Mais filtros
                            @if($avancadosCliAtivos > 0)
                                <span class="text-[10px] text-white rounded-full px-1.5 py-0.5" style="background-color:#374151;">{{ $avancadosCliAtivos }}</span>
                            @endif
                        </button>
                    </div>

                    {{-- Filtros avançados (colapsável) --}}
                    <div id="filtros-avancados-cli" class="{{ $avancadosCliAtivos > 0 ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-3 pt-4 border-t border-gray-200">
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Tipo de pessoa</label>
                            <select name="tipo" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todos</option>
                                <option value="PJ" {{ ($filtros['tipo'] ?? '') === 'PJ' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                                <option value="PF" {{ ($filtros['tipo'] ?? '') === 'PF' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">UF</label>
                            <select name="uf" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todas</option>
                                @foreach($ufs ?? [] as $uf)
                                    <option value="{{ $uf }}" {{ ($filtros['uf'] ?? '') === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Regime</label>
                            <select name="regime" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todos</option>
                                <option value="simples nacional" {{ ($filtros['regime'] ?? '') === 'simples nacional' ? 'selected' : '' }}>Simples Nacional</option>
                                <option value="lucro presumido" {{ ($filtros['regime'] ?? '') === 'lucro presumido' ? 'selected' : '' }}>Lucro Presumido</option>
                                <option value="lucro real" {{ ($filtros['regime'] ?? '') === 'lucro real' ? 'selected' : '' }}>Lucro Real</option>
                                <option value="mei" {{ ($filtros['regime'] ?? '') === 'mei' ? 'selected' : '' }}>MEI</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Cadastro</label>
                            <select name="status" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todos</option>
                                <option value="ativos" {{ ($filtros['status'] ?? '') === 'ativos' ? 'selected' : '' }}>Ativos</option>
                                <option value="inativos" {{ ($filtros['status'] ?? '') === 'inativos' ? 'selected' : '' }}>Inativos</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Importação</label>
                            <select name="importacao" class="w-full border border-gray-300 rounded text-[13px] py-2.5 px-3 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todas</option>
                                @foreach($importacoes ?? [] as $imp)
                                    <option value="{{ $imp->id }}" {{ (string)($filtros['importacao'] ?? '') === (string)$imp->id ? 'selected' : '' }}>{{ $imp->filename }} · {{ $imp->tipo_efd }} · {{ $imp->created_at?->format('d/m/Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center gap-2">
                            <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">Filtrar</button>
                            <a href="/app/clientes" data-link class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-2">Limpar</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 text-sm">
                    <button type="button" id="btn-selecionar-todos-clientes" class="text-gray-700 hover:text-gray-900 font-medium underline">
                        Selecionar todos (<span id="total-filtrado-clientes">{{ $clientes->total() }}</span>)
                    </button>
                    <button type="button" id="btn-limpar-selecao-clientes" class="text-gray-500 hover:text-gray-700 hidden">Limpar seleção</button>
                </div>
                <span id="total-selecionados-clientes-info" class="text-xs text-gray-500 hidden">
                    <span id="total-selecionados-clientes">0</span> selecionados (todas as páginas)
                </span>
            </div>

            <div id="acoes-lote" class="hidden bg-white border border-gray-300 rounded p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded text-white text-sm font-bold" id="clientes-selecionados-count" style="background-color: #374151">0</span>
                        <span class="text-sm font-medium text-gray-900"><span id="clientes-selecionados-label">clientes selecionados</span></span>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btn-exportar" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm transition hover:bg-gray-50">Exportar</button>
                        <button type="button" id="btn-consultar-selecionados" class="px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">Consultar</button>
                        <button type="button" id="btn-bulk-delete" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold shadow-sm transition hover:bg-red-700">Deletar</button>
                        <button type="button" id="btn-limpar-selecao" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm transition hover:bg-gray-50">Limpar</button>
                    </div>
                </div>
            </div>

            <div id="clientes-list-view" class="bg-white rounded border border-gray-300 overflow-hidden">
                @if(isset($clientes) && $clientes->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="w-10 px-3 py-2.5 text-left bg-gray-50">
                                        <input type="checkbox" id="select-all-clientes" class="w-4 h-4 rounded border-gray-300 text-gray-700 focus:ring-gray-400">
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cliente</th>
                                    <th class="w-[150px] px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Documento</th>
                                    <th class="hidden lg:table-cell w-[140px] px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Regime</th>
                                    <th class="hidden xl:table-cell w-[190px] px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Contato</th>
                                    <th class="w-[90px] px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Particip.</th>
                                    <th class="w-[280px] px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Certidões</th>
                                    <th class="w-[72px] px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($clientes as $cliente)
                                    <tr class="hover:bg-gray-50/50 transition-colors cliente-row" data-cliente-id="{{ $cliente->id }}">
                                        <td class="px-3 py-3">
                                            @unless($cliente->is_empresa_propria)
                                                <input type="checkbox" class="cliente-checkbox w-4 h-4 rounded border-gray-300 text-gray-700 focus:ring-gray-400" data-id="{{ $cliente->id }}">
                                            @endunless
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2 flex-wrap min-w-0">
                                                <a
                                                    href="/app/cliente/{{ $cliente->id }}"
                                                    data-link
                                                    class="text-sm text-gray-900 hover:text-gray-600 hover:underline truncate max-w-full"
                                                    title="{{ $cliente->razao_social ?? $cliente->nome ?? '-' }}"
                                                >
                                                    {{ $cliente->razao_social ?? $cliente->nome ?? '-' }}
                                                </a>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cliente->tipo_pessoa === 'PJ' ? '#374151' : '#9ca3af' }}">
                                                    {{ $cliente->tipo_pessoa }}
                                                </span>
                                                @if($cliente->is_empresa_propria)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Empresa Própria</span>
                                                @endif
                                            </div>
                                            @if($cliente->nome_fantasia)
                                                <div class="text-[11px] text-gray-500 mt-1">
                                                    <a
                                                        href="/app/cliente/{{ $cliente->id }}"
                                                        data-link
                                                        class="text-gray-600 hover:text-gray-900 hover:underline"
                                                    >
                                                        {{ $cliente->nome_fantasia }}
                                                    </a>
                                                </div>
                                            @elseif($cliente->tipo_pessoa === 'PJ' && $cliente->nome)
                                                <div class="text-[11px] text-gray-500 mt-1">
                                                    <a
                                                        href="/app/cliente/{{ $cliente->id }}"
                                                        data-link
                                                        class="text-gray-600 hover:text-gray-900 hover:underline"
                                                    >
                                                        {{ $cliente->nome }}
                                                    </a>
                                                </div>
                                            @endif
                                            <div class="mt-2 flex items-center gap-2 flex-wrap">
                                                <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cliente->consulta_status_hex }}">
                                                    {{ $cliente->consulta_status_label }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 mt-1">
                                                {{ $cliente->consulta_status_meta }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700 font-mono truncate" title="{{ $cliente->documento_formatado }}">{{ $cliente->documento_formatado }}</td>
                                        <td class="hidden lg:table-cell px-3 py-3 text-sm text-gray-700 truncate" title="{{ $cliente->regime_tributario }}"><x-regime-tributario :valor="$cliente->regime_tributario" :nota="$cliente->regime_tributario_nota" /></td>
                                        <td class="hidden xl:table-cell px-3 py-3">
                                            <div class="text-sm text-gray-700 truncate" title="{{ $cliente->email ?: '' }}">{{ $cliente->email ?: '-' }}</div>
                                            <div class="text-[11px] text-gray-500 mt-1 truncate">
                                                {{ $cliente->telefone ?: 'Sem telefone' }}
                                                @if($cliente->uf)
                                                    <span class="mx-1">·</span>{{ $cliente->uf }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700">
                                            {{ number_format($cliente->participantes_count ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if(($cliente->situacao_cadastral ?? '') === 'ATIVA')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #047857" title="Situação cadastral (Receita Federal)">Ativa</span>
                                                @elseif($cliente->situacao_cadastral)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #d97706" title="Situação cadastral (Receita Federal)">{{ $cliente->situacao_cadastral }}</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #9ca3af" title="Situação cadastral">{{ $cliente->ativo ? 'Ativo' : 'Inativo' }}</span>
                                                @endif
                                                @forelse($cliente->certidoes_badges ?? [] as $b)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: {{ $b['hex'] }}" title="{{ $b['titulo'] }}: {{ $b['label'] }}">{{ $b['curto'] }}</span>
                                                @empty
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #9ca3af">Sem certidões consultadas</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <x-acoes-menu trigger="kebab">
                                                <x-acoes-item href="/app/cliente/{{ $cliente->id }}/editar" data-link>Editar</x-acoes-item>
                                                @unless($cliente->is_empresa_propria)
                                                    <x-acoes-item variant="danger"
                                                        data-excluir-cliente="{{ $cliente->id }}"
                                                        data-nome="{{ $cliente->razao_social ?? $cliente->nome ?? '' }}"
                                                        data-documento="{{ $cliente->documento_formatado }}">Excluir</x-acoes-item>
                                                @endunless
                                            </x-acoes-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 px-6">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum cliente encontrado</h3>
                        <p class="text-sm text-gray-600 mb-4">Ajuste os filtros ou cadastre um novo cliente.</p>
                        <a href="/app/cliente/novo" data-link class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded text-sm font-medium hover:bg-gray-700 transition-colors gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Cadastrar Cliente
                        </a>
                    </div>
                @endif
            </div>

            @if(isset($clientes) && $clientes->count() > 0)

                @if($clientes->hasPages())
                    <div class="bg-white rounded border border-gray-300 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                                Mostrando {{ $clientes->firstItem() }}-{{ $clientes->lastItem() }} de {{ $clientes->total() }}
                            </p>
                            <div>
                                {{ $clientes->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<div id="modal-excluir" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-excluir-overlay"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Excluir cliente?</h3>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-700 mb-2"><span class="font-medium" id="modal-excluir-documento"></span> - <span id="modal-excluir-nome"></span></p>
                <p class="text-sm text-gray-500">O cliente será removido permanentemente. Os participantes vinculados serão mantidos.</p>
                <p class="text-sm text-red-600 font-medium mt-2">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="btn-cancelar-exclusao" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm transition hover:bg-gray-50">Cancelar</button>
                <button type="button" id="btn-confirmar-exclusao" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold shadow-sm transition hover:bg-red-700">Excluir</button>
            </div>
        </div>
    </div>
</div>

<div id="modal-bulk-delete" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-bulk-delete-overlay"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Excluir <span id="modal-bulk-delete-count">0</span> <span id="modal-bulk-delete-label">clientes</span>?</h3>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-2">Os clientes serão removidos permanentemente. Os participantes vinculados serão mantidos.</p>
                <p class="text-sm text-red-600 font-medium">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="btn-cancelar-bulk-delete" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm transition hover:bg-gray-50">Cancelar</button>
                <button type="button" id="btn-confirmar-bulk-delete" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold shadow-sm transition hover:bg-red-700">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var STORAGE_KEY = 'clientes_selecionados';

    function initClientes() {
        var container = document.getElementById('clientes-container');
        if (!container || container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';
        var errorRegion = document.getElementById('clientes-error-region');

        // Auto-aplica os filtros ao mudar qualquer select (sem AJAX; form-submit).
        var formFiltrosClientes = container.querySelector('form[action="/app/clientes"]');
        if (formFiltrosClientes) {
            formFiltrosClientes.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    if (typeof formFiltrosClientes.requestSubmit === 'function') {
                        formFiltrosClientes.requestSubmit();
                    } else {
                        formFiltrosClientes.submit();
                    }
                });
            });
        }

        var clientesSelecionados = carregarSelecao();
        var selectAll = document.getElementById('select-all-clientes');
        var btnSelecionarTodos = document.getElementById('btn-selecionar-todos-clientes');
        var btnLimparSelecaoGlobal = document.getElementById('btn-limpar-selecao-clientes');

        function carregarSelecao() {
            try {
                var raw = sessionStorage.getItem(STORAGE_KEY);
                if (raw) return new Set(JSON.parse(raw).map(Number));
            } catch (e) {}
            return new Set();
        }

        function salvarSelecao(setIds) {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(setIds)));
            } catch (e) {}
        }

        function limparSelecaoStorage() {
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch (e) {}
        }

        function atualizarBarraAcoes() {
            var total = clientesSelecionados.size;
            var acoesLote = document.getElementById('acoes-lote');
            var totalInfo = document.getElementById('total-selecionados-clientes-info');
            var totalSelecionados = document.getElementById('total-selecionados-clientes');
            var countBadge = document.getElementById('clientes-selecionados-count');
            var label = document.getElementById('clientes-selecionados-label');
            var btnLimparGlobal = document.getElementById('btn-limpar-selecao-clientes');

            if (acoesLote) acoesLote.classList.toggle('hidden', total === 0);
            if (totalInfo) totalInfo.classList.toggle('hidden', total === 0);
            if (btnLimparGlobal) btnLimparGlobal.classList.toggle('hidden', total === 0);
            if (totalSelecionados) totalSelecionados.textContent = total;
            if (countBadge) countBadge.textContent = total;
            if (label) label.textContent = total === 1 ? 'cliente selecionado' : 'clientes selecionados';

            if (!selectAll) return;
            var checkboxes = Array.from(container.querySelectorAll('.cliente-checkbox'));
            var checked = 0;
            checkboxes.forEach(function(cb) {
                if (cb.checked) checked++;
            });
            selectAll.checked = checked > 0 && checked === checkboxes.length && checkboxes.length > 0;
            selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
            selectAll.disabled = checkboxes.length === 0;
            salvarSelecao(clientesSelecionados);
        }

        function sincronizarCheckboxesCliente(id, checked) {
            container.querySelectorAll('.cliente-checkbox[data-id="' + id + '"]').forEach(function(cb) {
                cb.checked = checked;
            });
        }

        function sincronizarCheckboxes() {
            container.querySelectorAll('.cliente-checkbox').forEach(function(cb) {
                var id = Number(cb.dataset.id);
                cb.checked = clientesSelecionados.has(id);
            });
        }

        function removerClienteDaTela(id) {
            container.querySelectorAll('tr[data-cliente-id="' + id + '"]').forEach(function(row) {
                row.remove();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                container.querySelectorAll('.cliente-checkbox').forEach(function(cb) {
                    var id = parseInt(cb.dataset.id, 10);
                    cb.checked = selectAll.checked;
                    if (selectAll.checked) {
                        clientesSelecionados.add(id);
                    } else {
                        clientesSelecionados.delete(id);
                    }
                });
                atualizarBarraAcoes();
            });
        }

        container.addEventListener('change', function(event) {
            if (!event.target.classList.contains('cliente-checkbox')) return;
            var id = parseInt(event.target.dataset.id, 10);
            sincronizarCheckboxesCliente(id, event.target.checked);
            if (event.target.checked) {
                clientesSelecionados.add(id);
            } else {
                clientesSelecionados.delete(id);
            }
            atualizarBarraAcoes();
        });

        if (btnSelecionarTodos) {
            btnSelecionarTodos.addEventListener('click', async function() {
                btnSelecionarTodos.disabled = true;
                btnSelecionarTodos.textContent = 'Carregando...';

                try {
                    clearInlineError();
                    var params = new URLSearchParams();
                    var filtrosForm = container.querySelector('form[action="/app/clientes"]');

                    if (filtrosForm) {
                        ['status', 'tipo', 'regime', 'situacao', 'uf', 'busca', 'importacao', 'regularidade', 'status_consulta'].forEach(function(name) {
                            var field = filtrosForm.querySelector('[name="' + name + '"]');
                            if (field && field.value) params.set(name, field.value);
                        });
                    }

                    var url = '/app/clientes/todos-ids' + (params.toString() ? '?' + params.toString() : '');
                    var res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    var data = await res.json();
                    if (!data.success) throw new Error('Erro ao buscar IDs');

                    data.ids.forEach(function(id) { clientesSelecionados.add(Number(id)); });
                    sincronizarCheckboxes();
                    atualizarBarraAcoes();

                    window.showToast && window.showToast(data.total + ' clientes selecionados', 'success');
                } catch (err) {
                    console.error('[Clientes] Erro ao selecionar todos:', err);
                    showInlineError('Erro ao selecionar todos os clientes', 'clientes-selecionar-todos');
                } finally {
                    var totalEl = document.getElementById('total-filtrado-clientes');
                    var total = totalEl ? totalEl.textContent : '?';
                    btnSelecionarTodos.disabled = false;
                    btnSelecionarTodos.innerHTML = 'Selecionar todos (<span id="total-filtrado-clientes">' + total + '</span>)';
                }
            });
        }

        function limparSelecao() {
            clientesSelecionados.clear();
            limparSelecaoStorage();
            sincronizarCheckboxes();
            atualizarBarraAcoes();
        }

        var btnLimparSelecao = document.getElementById('btn-limpar-selecao');
        if (btnLimparSelecao) btnLimparSelecao.addEventListener('click', limparSelecao);
        if (btnLimparSelecaoGlobal) btnLimparSelecaoGlobal.addEventListener('click', limparSelecao);

        sincronizarCheckboxes();
        atualizarBarraAcoes();

        // Abrir/fechar/posicionar do menu fica por conta do componente padrão de ações.
        // Aqui só escutamos o clique no item "Excluir" — delegado em document, sobrevive aos
        // swaps do SPA (cleanup registrado abaixo).
        function _clientesOnExcluirClick(event) {
            var btn = event.target.closest('[data-excluir-cliente]');
            if (!btn) return;
            abrirModalExclusao(btn.dataset.excluirCliente, btn.dataset.nome, btn.dataset.documento);
        }
        document.addEventListener('click', _clientesOnExcluirClick);
        if (!window._cleanupFunctions) window._cleanupFunctions = {};
        window._cleanupFunctions.clientes = function () {
            document.removeEventListener('click', _clientesOnExcluirClick);
        };

        var modalExcluir = document.getElementById('modal-excluir');
        var modalExcluirOverlay = document.getElementById('modal-excluir-overlay');
        var modalExcluirNome = document.getElementById('modal-excluir-nome');
        var modalExcluirDocumento = document.getElementById('modal-excluir-documento');
        var btnCancelarExclusao = document.getElementById('btn-cancelar-exclusao');
        var btnConfirmarExclusao = document.getElementById('btn-confirmar-exclusao');
        var clienteIdParaExcluir = null;

        function abrirModalExclusao(id, nome, documento) {
            clienteIdParaExcluir = id;
            if (modalExcluirNome) modalExcluirNome.textContent = nome || 'Sem nome';
            if (modalExcluirDocumento) modalExcluirDocumento.textContent = documento || '';
            if (modalExcluir) modalExcluir.classList.remove('hidden');
        }

        function fecharModalExclusao() {
            clienteIdParaExcluir = null;
            if (modalExcluir) modalExcluir.classList.add('hidden');
        }

        if (btnCancelarExclusao) btnCancelarExclusao.addEventListener('click', fecharModalExclusao);
        if (modalExcluirOverlay) modalExcluirOverlay.addEventListener('click', fecharModalExclusao);

        if (btnConfirmarExclusao) {
            btnConfirmarExclusao.addEventListener('click', function() {
                if (!clienteIdParaExcluir) return;
                var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                btnConfirmarExclusao.disabled = true;
                btnConfirmarExclusao.textContent = 'Excluindo...';

                fetch('/app/cliente/' + clienteIdParaExcluir, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        clearInlineError();
                        if (!data.success) throw new Error(data.message || 'Erro ao excluir cliente');
                        removerClienteDaTela(clienteIdParaExcluir);
                        clientesSelecionados.delete(parseInt(clienteIdParaExcluir, 10));
                        atualizarBarraAcoes();
                        fecharModalExclusao();
                        if (window.showToast) window.showToast(data.message || 'Cliente excluído com sucesso.', 'success');
                    })
                    .catch(function(err) {
                        showInlineError(err.message || 'Erro ao excluir cliente', 'clientes-excluir');
                    })
                    .finally(function() {
                        btnConfirmarExclusao.disabled = false;
                        btnConfirmarExclusao.textContent = 'Excluir';
                    });
            });
        }

        var modalBulkDelete = document.getElementById('modal-bulk-delete');
        var modalBulkDeleteOverlay = document.getElementById('modal-bulk-delete-overlay');
        var btnBulkDelete = document.getElementById('btn-bulk-delete');
        var btnCancelarBulkDelete = document.getElementById('btn-cancelar-bulk-delete');
        var btnConfirmarBulkDelete = document.getElementById('btn-confirmar-bulk-delete');
        var modalBulkDeleteCount = document.getElementById('modal-bulk-delete-count');
        var modalBulkDeleteLabel = document.getElementById('modal-bulk-delete-label');

        function abrirModalBulkDelete() {
            if (clientesSelecionados.size === 0 || !modalBulkDelete) return;
            if (modalBulkDeleteCount) modalBulkDeleteCount.textContent = clientesSelecionados.size;
            if (modalBulkDeleteLabel) modalBulkDeleteLabel.textContent = clientesSelecionados.size === 1 ? 'cliente' : 'clientes';
            modalBulkDelete.classList.remove('hidden');
        }

        function fecharModalBulkDelete() {
            if (modalBulkDelete) modalBulkDelete.classList.add('hidden');
        }

        if (btnBulkDelete) btnBulkDelete.addEventListener('click', abrirModalBulkDelete);
        if (btnCancelarBulkDelete) btnCancelarBulkDelete.addEventListener('click', fecharModalBulkDelete);
        if (modalBulkDeleteOverlay) modalBulkDeleteOverlay.addEventListener('click', fecharModalBulkDelete);

        if (btnConfirmarBulkDelete) {
            btnConfirmarBulkDelete.addEventListener('click', function() {
                if (clientesSelecionados.size === 0) return;
                var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                var ids = Array.from(clientesSelecionados);
                btnConfirmarBulkDelete.disabled = true;
                btnConfirmarBulkDelete.textContent = 'Excluindo...';

                fetch('/app/clientes/bulk-delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids }),
                })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        clearInlineError();
                        if (!data.success) throw new Error(data.message || 'Erro ao excluir clientes');
                        ids.forEach(function(id) {
                            removerClienteDaTela(id);
                        });
                        limparSelecao();
                        fecharModalBulkDelete();
                        if (window.showToast) window.showToast(data.message || 'Clientes excluídos com sucesso.', 'success');
                    })
                    .catch(function(err) {
                        showInlineError(err.message || 'Erro ao excluir clientes', 'clientes-excluir-lote');
                    })
                    .finally(function() {
                        btnConfirmarBulkDelete.disabled = false;
                        btnConfirmarBulkDelete.textContent = 'Excluir';
                    });
            });
        }

        var btnExportar = document.getElementById('btn-exportar');
        if (btnExportar) {
            btnExportar.addEventListener('click', function() {
                if (window.showToast) window.showToast('Exportação desta grade será implementada em etapa separada.', 'info');
            });
        }

        var btnConsultarSelecionados = document.getElementById('btn-consultar-selecionados');
        if (btnConsultarSelecionados) {
            btnConsultarSelecionados.addEventListener('click', function() {
                if (window.showToast) window.showToast('Consulta em lote por cliente permanece indisponível nesta tela.', 'info');
            });
        }

        function showInlineError(message, action) {
            if (window.showInlineError) {
                window.showInlineError(errorRegion, {
                    message: message,
                    context: {
                        action: action || 'clientes',
                        url: window.location.pathname + window.location.search,
                    },
                });
                return;
            }

            if (window.showToast) window.showToast(message, 'error');
        }

        function clearInlineError() {
            if (window.clearInlineError) {
                window.clearInlineError(errorRegion);
            }
        }
    }

    window.initClientes = initClientes;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClientes, { once: true });
    } else {
        initClientes();
    }
})();
</script>
