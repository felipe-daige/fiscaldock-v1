{{-- Modal "Nova assinatura" — duas etapas (escolha do alvo + plano/frequência) --}}
<div id="modal-nova-assinatura" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 max-w-xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-widest">Nova assinatura</span>
            <button type="button" id="modal-fechar" class="text-gray-400 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="overflow-y-auto p-4 flex-1">
            <div id="modal-erro" class="hidden mb-3 p-3 rounded bg-red-50 border border-red-200 text-xs text-red-700"></div>

            {{-- Etapa 1: escolha do alvo --}}
            <div id="modal-etapa-1">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Escolha o tipo de alvo</p>
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <label class="border border-gray-300 rounded p-3 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="tipo_alvo" value="cliente" class="mr-2" checked>
                        <span class="text-sm font-semibold">Cliente</span>
                        <p class="text-[11px] text-gray-500 mt-1">Empresa própria que você está acompanhando.</p>
                    </label>
                    <label class="border border-gray-300 rounded p-3 cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="tipo_alvo" value="participante" class="mr-2">
                        <span class="text-sm font-semibold">Participante</span>
                        <p class="text-[11px] text-gray-500 mt-1">Fornecedor, cliente do seu cliente ou parceiro.</p>
                    </label>
                </div>

                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Buscar CNPJ ou razão social</label>
                <input type="text" id="modal-busca" autocomplete="off" placeholder="digite ao menos 2 caracteres"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded mb-2">

                <div id="modal-resultados" class="border border-gray-200 rounded divide-y divide-gray-200 max-h-56 overflow-y-auto">
                    {{-- preenchido por JS --}}
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="button" id="modal-avancar" disabled
                            class="px-4 py-2 rounded text-white text-xs font-semibold disabled:opacity-40"
                            style="background-color: #047857;">Avançar</button>
                </div>
            </div>

            {{-- Etapa 2: plano + frequência --}}
            <div id="modal-etapa-2" class="hidden">
                <div id="modal-alvo-escolhido" class="mb-4 p-3 bg-gray-50 rounded border border-gray-200 text-xs"></div>

                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Plano</label>
                <select id="modal-plano" class="w-full px-3 py-2 text-sm border border-gray-300 rounded mb-3">
                    @foreach (\App\Models\MonitoramentoPlano::ativos()->whereIn('codigo', config('monitoramento.planos_assinatura', [])) as $plano)
                        <option value="{{ $plano->id }}" data-creditos="{{ $plano->custo_creditos }}">
                            {{ $plano->nome }} ({{ $plano->custo_creditos }} créditos / consulta)
                        </option>
                    @endforeach
                </select>

                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Frequência</label>
                <select id="modal-frequencia" class="w-full px-3 py-2 text-sm border border-gray-300 rounded mb-3">
                    <option value="mensal" selected>Mensal (30 dias)</option>
                    <option value="quinzenal" disabled>Quinzenal — em breve</option>
                    <option value="60dias" disabled>60 dias — em breve</option>
                </select>

                <div class="p-3 bg-blue-50 border border-blue-200 rounded text-xs text-blue-900">
                    Custo mensal estimado: <strong id="modal-custo">—</strong> créditos.
                    Saldo atual: <strong>{{ $credits ?? 0 }}</strong> créditos.
                </div>

                <div class="mt-4 flex justify-between">
                    <button type="button" id="modal-voltar" class="px-3 py-2 rounded border border-gray-300 text-xs">Voltar</button>
                    <button type="button" id="modal-criar"
                            class="px-4 py-2 rounded text-white text-xs font-semibold"
                            style="background-color: #047857;">Criar assinatura</button>
                </div>
            </div>
        </div>
    </div>
</div>
