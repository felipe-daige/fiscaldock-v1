{{-- Minha Empresa - Configurar (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="minha-empresa-configurar">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Configurar Minha Empresa</h1>
            <p class="mt-1 text-xs text-gray-500">Selecione qual empresa você deseja monitorar como sua empresa principal.</p>
        </div>

        {{-- Bloco de seleção --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Suas Empresas (Pessoa Jurídica)</span>
            </div>

            @if(($clientes ?? collect())->count() > 0)
                <div class="p-4 space-y-2" id="lista-empresas">
                    @foreach($clientes as $cliente)
                        @php $ativa = $empresaAtual && $empresaAtual->id === $cliente->id; @endphp
                        <div class="empresa-item flex items-center justify-between p-4 rounded border transition-all cursor-pointer hover:bg-gray-50/50 {{ $ativa ? 'border-gray-800 bg-gray-50' : 'border-gray-300' }}"
                             data-cliente-id="{{ $cliente->id }}"
                             onclick="selecionarEmpresa({{ $cliente->id }})">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 {{ $ativa ? 'text-gray-900' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $cliente->razao_social ?? $cliente->nome }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono">CNPJ: {{ $cliente->documento_formatado }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($ativa)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                          style="background-color: #1f2937">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Principal
                                    </span>
                                @else
                                    <button type="button" class="btn-selecionar inline-flex items-center px-3 py-1.5 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition" onclick="event.stopPropagation(); definirPrincipal({{ $cliente->id }})">
                                        Selecionar
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Estado vazio --}}
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhuma empresa cadastrada</h3>
                    <p class="mt-2 text-xs text-gray-500">Você ainda não possui empresas (PJ) cadastradas no sistema.</p>
                    <a href="/app/cliente/novo" data-link class="mt-6 inline-flex items-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold transition hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Cadastrar Empresa
                    </a>
                </div>
            @endif
        </div>

        {{-- Informações adicionais --}}
        <div class="mt-6 bg-white rounded border border-gray-300 border-l-4 border-l-blue-500 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">O que acontece ao selecionar?</h4>
                    <ul class="mt-2 space-y-1 text-xs text-gray-700">
                        <li>— A empresa selecionada aparecerá no dashboard "Minha Empresa"</li>
                        <li>— Você poderá monitorar CNDs, situação cadastral e score de risco</li>
                        <li>— Alertas e lembretes serão personalizados para esta empresa</li>
                        <li>— Você pode alterar a empresa principal a qualquer momento</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Voltar --}}
        @if($empresaAtual ?? false)
            <div class="mt-6 text-center">
                <a href="/app/minha-empresa" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar para Minha Empresa
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function selecionarEmpresa(clienteId) {
    document.querySelectorAll('.empresa-item').forEach(item => {
        item.classList.remove('border-gray-800', 'bg-gray-50');
        item.classList.add('border-gray-300');
    });

    const selectedItem = document.querySelector(`[data-cliente-id="${clienteId}"]`);
    if (selectedItem) {
        selectedItem.classList.remove('border-gray-300');
        selectedItem.classList.add('border-gray-800', 'bg-gray-50');
    }
}

function definirPrincipal(clienteId) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...';

    fetch('/app/minha-empresa/definir-principal', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ cliente_id: clienteId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.spaNavigate) {
                window.spaNavigate(data.redirect || '/app/minha-empresa');
            } else {
                window.location.href = data.redirect || '/app/minha-empresa';
            }
        } else {
            alert(data.message || 'Erro ao definir empresa principal');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar. Tente novamente.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
