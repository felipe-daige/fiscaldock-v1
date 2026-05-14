{{-- Cliente - Detalhes --}}
@php
    $clienteNome = $cliente->razao_social ?? $cliente->nome ?? 'Cliente';
    $tipoPessoaBadge = $cliente->tipo_pessoa === 'PJ'
        ? ['label' => 'PJ', 'hex' => '#374151']
        : ['label' => 'PF', 'hex' => '#6b7280'];
    $statusBadge = $cliente->ativo
        ? ['label' => 'ATIVO', 'hex' => '#047857']
        : ['label' => 'INATIVO', 'hex' => '#dc2626'];
    $empresaBadge = ['label' => 'EMPRESA PRÓPRIA', 'hex' => '#0f766e'];
    $resumoCliente = [
        ['label' => 'Participantes Vinculados', 'valor' => number_format($totalParticipantes, 0, ',', '.'), 'sub' => 'Base vinculada ao cadastro'],
        ['label' => 'Notas Fiscais', 'valor' => number_format($totalNotas, 0, ',', '.'), 'sub' => 'Notas unificadas EFD e XML'],
        ['label' => 'Localização', 'valor' => implode(' / ', array_filter([$cliente->municipio, $cliente->uf])) ?: 'Não informado', 'sub' => 'Município e UF'],
        ['label' => 'Cadastro', 'valor' => $cliente->created_at?->format('d/m/Y') ?? '-', 'sub' => 'Data de inclusão'],
    ];
    $dadosCadastrais = [
        ['label' => $cliente->tipo_pessoa === 'PJ' ? 'Razão Social' : 'Nome', 'valor' => $cliente->razao_social ?? $cliente->nome ?? '-', 'mono' => false],
        ['label' => $cliente->tipo_pessoa === 'PJ' ? 'Nome Fantasia' : 'Nome de Exibição', 'valor' => $cliente->nome ?? '-', 'mono' => false],
        ['label' => $cliente->tipo_pessoa === 'PJ' ? 'CNPJ' : 'CPF', 'valor' => $cliente->documento_formatado, 'mono' => true],
        ['label' => 'E-mail', 'valor' => $cliente->email ?: 'Não informado', 'mono' => false],
        ['label' => 'Telefone', 'valor' => $cliente->telefone ?: 'Não informado', 'mono' => false],
        ['label' => 'Município / UF', 'valor' => implode(' - ', array_filter([$cliente->municipio, $cliente->uf])) ?: 'Não informado', 'mono' => false],
        ['label' => 'CEP', 'valor' => $cliente->cep ?: 'Não informado', 'mono' => true],
        ['label' => 'Status do Cadastro', 'valor' => $cliente->ativo ? 'Ativo' : 'Inativo', 'mono' => false],
    ];
@endphp

<div class="min-h-screen bg-gray-100" id="cliente-show-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <a
                            href="/app/clientes"
                            class="text-xs text-gray-600 hover:text-gray-900 hover:underline"
                            data-link
                        >
                            Voltar para clientes
                        </a>
                        <span class="text-gray-300 hidden sm:inline">|</span>
                        <span class="text-xs text-gray-500">Cadastro operacional</span>
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-2">{{ $clienteNome }}</h1>
                    <p class="text-xs text-gray-500 mt-1">{{ $cliente->documento_formatado }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoPessoaBadge['hex'] }}">{{ $tipoPessoaBadge['label'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusBadge['hex'] }}">{{ $statusBadge['label'] }}</span>
                    @if($cliente->is_empresa_propria)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $empresaBadge['hex'] }}">{{ $empresaBadge['label'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ações Operacionais</span>
                        <p class="text-[11px] text-gray-500 mt-1">Gerencie o cadastro e acompanhe os vínculos fiscais do cliente.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('app.cliente.edit', $cliente->id) }}"
                            data-link
                            class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded"
                        >
                            Editar cadastro
                        </a>
                        @if(!$cliente->is_empresa_propria && !($assinaturaAtiva ?? null))
                            <button
                                type="button"
                                id="btn-criar-assinatura"
                                class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded"
                            >
                                Criar assinatura
                            </button>
                        @endif
                        @if(!$cliente->is_empresa_propria)
                            <button
                                type="button"
                                id="btn-excluir-cliente"
                                data-id="{{ $cliente->id }}"
                                data-nome="{{ $clienteNome }}"
                                class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded"
                            >
                                Excluir
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                @foreach($resumoCliente as $item)
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">{{ $item['label'] }}</p>
                        <p class="text-lg font-bold text-gray-900">{{ $item['valor'] }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">{{ $item['sub'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados Cadastrais</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                        @foreach($dadosCadastrais as $dado)
                            <div class="px-4 py-3 sm:px-5 sm:py-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $dado['label'] }}</p>
                                <p class="text-sm text-gray-700 {{ $dado['mono'] ? 'font-mono' : '' }}">{{ $dado['valor'] }}</p>
                            </div>
                        @endforeach
                        @if($cliente->endereco ?? null)
                            <div class="px-4 py-3 sm:px-5 sm:py-4 sm:col-span-2 lg:col-span-3 border-t border-gray-200">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Endereço</p>
                                <p class="text-sm text-gray-700">{{ $cliente->endereco }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Relacional</span>
                            <a href="/app/clientes" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">
                                Voltar à carteira
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                        <div class="px-4 py-4">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Participantes vinculados</p>
                            <p class="text-base font-bold text-gray-900">{{ number_format($totalParticipantes, 0, ',', '.') }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">Cadastros que usam este cliente como vínculo operacional.</p>
                        </div>
                        <div class="px-4 py-4">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas registradas</p>
                            <p class="text-base font-bold text-gray-900">{{ number_format($totalNotas, 0, ',', '.') }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">Movimentação fiscal consolidada disponível para análise.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-0">
                    @include('autenticado.partials.notas-fiscais-card', [
                        'notas' => $notasFiscais,
                        'totalNotas' => $totalNotasFiscais,
                        'ajaxUrl' => $notasAjaxUrl,
                        'contexto' => $notasContexto,
                        'entityId' => $notasEntityId,
                    ])
                </div>

                <div>
                    @include('autenticado.monitoramento._assinatura-historico')
                </div>
            </div>

            <div class="space-y-6">
                @include('autenticado.monitoramento._assinatura-painel')

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Situação do Cadastro</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo de Pessoa</p>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoPessoaBadge['hex'] }}">{{ $tipoPessoaBadge['label'] }}</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status</p>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusBadge['hex'] }}">{{ $statusBadge['label'] }}</span>
                        </div>
                        @if($cliente->is_empresa_propria)
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Classificação</p>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $empresaBadge['hex'] }}">{{ $empresaBadge['label'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Metadados</span>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Criado em</p>
                            <p class="text-sm text-gray-700">{{ $cliente->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Documento</p>
                            <p class="text-sm text-gray-700 font-mono">{{ $cliente->documento_formatado }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Base fiscal</p>
                            <p class="text-sm text-gray-700">{{ number_format($totalNotas, 0, ',', '.') }} notas disponíveis</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$cliente->is_empresa_propria)
            <div id="modal-excluir-show" class="fixed inset-0 z-50 hidden">
                <div class="absolute inset-0 bg-black/40" id="modal-excluir-show-overlay"></div>
                <div class="relative z-10 flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded border border-gray-300 w-full max-w-md overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Excluir Cliente</span>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-700">
                                Confirmar exclusão de <strong>{{ $clienteNome }}</strong>? Esta ação remove o cadastro da carteira.
                            </p>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-200 bg-white flex justify-end gap-2">
                            <button type="button" id="btn-cancelar-excluir-show" class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded">
                                Cancelar
                            </button>
                            <button type="button" id="btn-confirmar-excluir-show" class="px-3 py-2 text-sm font-medium bg-gray-800 text-white hover:bg-gray-700 rounded" data-id="{{ $cliente->id }}">
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                var btnExcluir = document.getElementById('btn-excluir-cliente');
                var modal = document.getElementById('modal-excluir-show');
                var overlay = document.getElementById('modal-excluir-show-overlay');
                var btnCancelar = document.getElementById('btn-cancelar-excluir-show');
                var btnConfirmar = document.getElementById('btn-confirmar-excluir-show');

                if (btnExcluir) {
                    btnExcluir.addEventListener('click', function() {
                        if (modal) modal.classList.remove('hidden');
                    });
                }

                function fecharModal() {
                    if (modal) modal.classList.add('hidden');
                }

                if (overlay) overlay.addEventListener('click', fecharModal);
                if (btnCancelar) btnCancelar.addEventListener('click', fecharModal);

                if (btnConfirmar) {
                    btnConfirmar.addEventListener('click', function() {
                        var id = this.dataset.id;
                        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                            || document.querySelector('input[name="_token"]')?.value
                            || '';

                        fetch('/app/cliente/' + id, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.success) {
                                if (window.navigateTo) {
                                    window.navigateTo('/app/clientes');
                                } else {
                                    window.location.href = '/app/clientes';
                                }
                            } else {
                                fecharModal();
                                alert(data.message || 'Erro ao excluir cliente.');
                            }
                        })
                        .catch(function() {
                            fecharModal();
                            alert('Erro ao excluir cliente.');
                        });
                    });
                }
            })();
            </script>

            {{-- Modal Criar Assinatura de Monitoramento --}}
            <div id="modal-criar-assinatura" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded border border-gray-300 max-w-md w-full overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Criar Assinatura</span>
                            <button type="button" class="modal-criar-assinatura-close text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <form id="form-criar-assinatura">
                        <div class="p-4 sm:p-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Selecione o Plano</label>
                                <select name="plano_id" id="select-plano-assinatura" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" required>
                                    <option value="">Selecione...</option>
                                    @foreach($planos as $plano)
                                        <option value="{{ $plano->id }}" data-creditos="{{ $plano->custo_creditos }}">
                                            {{ $plano->nome }} ({{ $plano->custo_creditos }} creditos)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Frequência</label>
                                <select name="frequencia" id="select-frequencia" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" required>
                                    <option value="mensal" selected>Mensal</option>
                                    <option value="quinzenal" disabled>Quinzenal (em breve)</option>
                                    <option value="60dias" disabled>60 dias (em breve)</option>
                                </select>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded p-4">
                                <p class="text-sm text-gray-600 mb-2">Cliente:</p>
                                <p class="text-sm font-semibold text-gray-900">{{ $cliente->razao_social ?? $cliente->nome }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $cliente->documento_formatado }}</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 bg-white flex justify-end gap-3">
                            <button type="button" class="modal-criar-assinatura-close px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-semibold transition hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 rounded bg-gray-800 text-white text-sm font-semibold transition hover:bg-gray-700">
                                Criar Assinatura
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            (function() {
                var btnCriar = document.getElementById('btn-criar-assinatura');
                var modal = document.getElementById('modal-criar-assinatura');
                var form = document.getElementById('form-criar-assinatura');
                var clienteId = {{ $cliente->id }};

                if (btnCriar && modal) {
                    btnCriar.addEventListener('click', function() {
                        modal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    });
                }

                document.querySelectorAll('.modal-criar-assinatura-close').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        if (modal) modal.classList.add('hidden');
                        document.body.style.overflow = '';
                    });
                });

                if (form) {
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        var planoId = document.getElementById('select-plano-assinatura').value;
                        var frequencia = document.getElementById('select-frequencia').value;

                        if (!planoId) {
                            window.showToast && window.showToast('Selecione um plano', 'error');
                            return;
                        }

                        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        var submitBtn = this.querySelector('button[type="submit"]');
                        var originalText = submitBtn.textContent;
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Criando...';

                        try {
                            var response = await fetch('/app/monitoramento/assinatura', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'Accept': 'application/json',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    cliente_id: clienteId,
                                    plano_id: planoId,
                                    frequencia: frequencia,
                                }),
                            });

                            var data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.error || 'Erro ao criar assinatura');
                            }

                            window.showToast && window.showToast('Assinatura criada com sucesso!', 'success');
                            modal.classList.add('hidden');
                            document.body.style.overflow = '';
                            setTimeout(function() { window.location.reload(); }, 1500);
                        } catch (err) {
                            window.showToast && window.showToast(err.message || 'Erro ao criar assinatura', 'error');
                        } finally {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    });
                }
            })();
            </script>

            <script>
            (function() {
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function acaoAssinatura(url, method, confirmMsg) {
                    if (confirmMsg && !confirm(confirmMsg)) return;
                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    })
                    .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
                    .then(function(r) {
                        if (!r.ok) {
                            throw new Error(r.data.error || 'Erro ao atualizar a assinatura');
                        }
                        window.showToast && window.showToast('Assinatura atualizada.', 'success');
                        setTimeout(function() { window.location.reload(); }, 1200);
                    })
                    .catch(function(err) {
                        window.showToast && window.showToast(err.message || 'Erro ao atualizar a assinatura', 'error');
                    });
                }

                document.querySelectorAll('.btn-pausar-assinatura').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        acaoAssinatura('/app/monitoramento/assinatura/' + this.dataset.assinaturaId + '/pausar', 'POST', 'Deseja pausar esta assinatura?');
                    });
                });
                document.querySelectorAll('.btn-reativar-assinatura').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        acaoAssinatura('/app/monitoramento/assinatura/' + this.dataset.assinaturaId + '/reativar', 'POST', null);
                    });
                });
                document.querySelectorAll('.btn-cancelar-assinatura').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        acaoAssinatura('/app/monitoramento/assinatura/' + this.dataset.assinaturaId, 'DELETE', 'Tem certeza que deseja cancelar esta assinatura? Esta ação não pode ser desfeita.');
                    });
                });
            })();
            </script>
        @endif
    </div>
</div>
