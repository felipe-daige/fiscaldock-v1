@php
    $saldoAtual = (int) ($saldoAtual ?? 0);
    $custoEstimadoCreditos = (int) ($custoEstimadoCreditos ?? 14);
    $clientes = $clientes ?? collect();
    $defaultClienteId = $defaultClienteId ?? null;
    $ultimasConsultasDfe = $ultimasConsultasDfe ?? collect();
    $saldoSuficiente = $saldoAtual >= $custoEstimadoCreditos;
    $possuiClientesDisponiveis = $clientes->isNotEmpty() && !empty($defaultClienteId);

    $badgeCoresSituacao = [
        'AUTORIZADA' => '#047857',
        'NEGATIVA' => '#047857',
        'CANCELADA' => '#dc2626',
        'DENEGADA' => '#dc2626',
        'INUTILIZADA' => '#dc2626',
        'INDETERMINADO' => '#d97706',
        'NAO_ENCONTRADA' => '#d97706',
        'ERRO_PARAMETRO' => '#6b7280',
        'ERRO_PROVEDOR' => '#6b7280',
    ];
@endphp

<style>
    .buscar-dfe-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 1.5rem;
    }

    @media (max-width: 1023px) {
        .buscar-dfe-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
            align-items: start;
        }
    }

    .documento-tipo-card {
        border-color: #d1d5db;
        background-color: #f9fafb;
        position: relative;
    }

    .documento-tipo-card.is-selected {
        border-color: #1f2937;
        background-color: #f3f4f6;
        box-shadow: inset 0 0 0 1px #1f2937;
    }

    .documento-tipo-card.is-disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .documento-tipo-card.is-disabled:hover {
        background-color: #f9fafb;
    }

    .documento-tipo-card .documento-selecionado {
        display: none;
    }

    .documento-tipo-card.is-selected .documento-selecionado {
        display: inline-flex;
    }

    .progress-track {
        width: 100%;
        height: 6px;
        background-color: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background-color: #1f2937;
        width: 8%;
        transition: width 350ms ease-out;
    }
</style>

<div class="min-h-screen bg-gray-100" id="buscar-nfe-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Buscar Notas</h1>
                <p class="text-xs text-gray-500 mt-1">Consulta avulsa por chave de acesso. Resultado inline e histórico das últimas consultas.</p>
            </div>
            <a href="/app/clearance/notas" data-link class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-sm font-medium self-start">
                Verificar notas da base
            </a>
        </div>

        <details class="bg-white rounded border border-gray-300 border-l-4 mb-4 group" style="border-left-color: #2563eb;">
            <summary class="cursor-pointer px-4 py-3 flex items-center justify-between list-none hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900">Como funciona a busca de notas</span>
                </div>
                <span class="text-[11px] font-semibold text-gray-500 group-open:hidden">Abrir</span>
                <span class="text-[11px] font-semibold text-gray-500 hidden group-open:inline">Fechar</span>
            </summary>

            <div class="border-t border-gray-200">
                <div class="px-4 py-4">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-3">Fluxo em 3 etapas</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">1</span>
                            <p class="text-sm font-semibold text-gray-900">Identificação</p>
                            <p class="text-xs text-gray-600 mt-0.5">Você escolhe o tipo do documento, vincula a consulta a um cliente e informa a chave de acesso com 44 dígitos.</p>
                        </div>
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">2</span>
                            <p class="text-sm font-semibold text-gray-900">Consulta oficial</p>
                            <p class="text-xs text-gray-600 mt-0.5">O FiscalDock envia a chave ao n8n, que consulta a fonte oficial via InfoSimples e acompanha o processamento em tempo real.</p>
                        </div>
                        <div class="relative pl-10">
                            <span class="absolute left-0 top-0 w-7 h-7 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #2563eb;">3</span>
                            <p class="text-sm font-semibold text-gray-900">Resultado</p>
                            <p class="text-xs text-gray-600 mt-0.5">Você recebe o retorno inline com situação, dados principais do DF-e e o registro entra no histórico recente do clearance.</p>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Matriz de suporte</p>
                        <span class="text-[10px] font-semibold text-gray-400">Consulta avulsa por chave</span>
                    </div>
                    <div class="overflow-x-auto border border-gray-200 rounded">
                        <table class="w-full text-xs">
                            <thead style="background-color: #f9fafb;">
                                <tr class="text-left text-[10px] font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="py-2 px-3">Documento</th>
                                    <th class="py-2 px-3">Modelo</th>
                                    <th class="py-2 px-3">Chave</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3">Observação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NF-e</td>
                                    <td class="py-2 px-3">55</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Consulta pela chave de acesso nacional.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NFC-e</td>
                                    <td class="py-2 px-3">65</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Mesma validação por chave usada para NF-e.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">CT-e</td>
                                    <td class="py-2 px-3">57</td>
                                    <td class="py-2 px-3">44 dígitos</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #047857;">Suportado</span></td>
                                    <td class="py-2 px-3 text-gray-500">Consulta avulsa para documentos de transporte.</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-medium text-gray-900">NFS-e</td>
                                    <td class="py-2 px-3">—</td>
                                    <td class="py-2 px-3">Municipal</td>
                                    <td class="py-2 px-3"><span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #6b7280;">Em breve</span></td>
                                    <td class="py-2 px-3 text-gray-500">Fora do fluxo atual de consulta avulsa.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 rounded border border-gray-200 p-3" style="background-color: #fffbeb;">
                        <p class="text-[11px] font-semibold text-gray-700 mb-1">Cliente associado é obrigatório</p>
                        <p class="text-xs text-gray-600 leading-relaxed">Cada busca avulsa precisa ficar vinculada a um cliente, inclusive quando a consulta for da própria empresa. Por isso a tela já tenta selecionar a empresa própria automaticamente antes do disparo.</p>
                    </div>
                </div>

                <div class="px-4 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Como é cobrado</p>
                        <span class="text-[10px] font-semibold text-gray-400">Por documento consultado</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="border border-gray-300 rounded p-3">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-gray-900">Consulta avulsa</p>
                                <span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #2563eb;">Clearance</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($custoEstimadoCreditos, 0, ',', '.') }} <span class="text-xs font-medium text-gray-500">créditos/documento</span></p>
                            <p class="text-[11px] text-gray-500 mt-1">Cobrança unitária para NF-e, NFC-e e CT-e consultados por chave.</p>
                        </div>
                        <div class="border border-gray-300 rounded p-3">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-gray-900">Retorno</p>
                                <span class="inline-block px-2 py-0.5 rounded text-white text-[10px] font-semibold" style="background-color: #6b7280;">Inline</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">1 <span class="text-xs font-medium text-gray-500">resultado por chave</span></p>
                            <p class="text-[11px] text-gray-500 mt-1">A tela mostra situação e dados principais assim que o provedor conclui a consulta.</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-3">A cobrança acontece no início da consulta. <strong>Falhas do provedor estornam os créditos</strong> automaticamente.</p>
                </div>
            </div>
        </details>

        <div class="buscar-dfe-grid">
            <section class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Buscar Notas</span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-wide">Consulta avulsa por chave</span>
                </div>

                <div class="p-4 sm:p-6 space-y-5">
                    <div>
                        <p class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Tipo de documento</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" role="radiogroup" aria-label="Tipo de documento fiscal">
                            <label class="documento-tipo-card border rounded p-3 cursor-pointer hover:bg-gray-100 transition-colors is-selected" data-document-type-card="nfe">
                                <input type="radio" name="documento_tipo" value="nfe" class="sr-only documento-tipo" checked>
                                <span class="flex items-start justify-between gap-2">
                                    <span class="block">
                                        <span class="block text-sm font-bold text-gray-900">NF-e</span>
                                        <span class="block text-[11px] text-gray-500 mt-0.5">Modelo 55</span>
                                    </span>
                                    <span class="documento-selecionado px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Selecionado</span>
                                </span>
                            </label>

                            <label class="documento-tipo-card border rounded p-3 cursor-pointer hover:bg-gray-100 transition-colors" data-document-type-card="cte">
                                <input type="radio" name="documento_tipo" value="cte" class="sr-only documento-tipo">
                                <span class="flex items-start justify-between gap-2">
                                    <span class="block">
                                        <span class="block text-sm font-bold text-gray-900">CT-e</span>
                                        <span class="block text-[11px] text-gray-500 mt-0.5">Modelo 57</span>
                                    </span>
                                    <span class="documento-selecionado px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Selecionado</span>
                                </span>
                            </label>

                            <label class="documento-tipo-card is-disabled border rounded p-3" data-document-type-card="nfse" aria-disabled="true" title="Em breve — NFS-e ainda não disponível">
                                <input type="radio" name="documento_tipo" value="nfse" class="sr-only documento-tipo" disabled>
                                <span class="flex items-start justify-between gap-2">
                                    <span class="block">
                                        <span class="block text-sm font-bold text-gray-900">NFS-e</span>
                                        <span class="block text-[11px] text-gray-500 mt-0.5">Serviços</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Em breve</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    @if(!$possuiClientesDisponiveis)
                        <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-amber-500">
                            <p class="text-sm font-semibold text-gray-900">Cliente obrigatório para consultar</p>
                            <p class="text-xs text-gray-600 mt-1">Esta busca só pode ser executada com um cliente associado. A conta deveria ter a empresa própria criada automaticamente; se ela não estiver disponível, cadastre ou ajuste o cliente antes de continuar.</p>
                            <a href="/app/cliente/novo" data-link class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium">
                                Cadastrar cliente
                            </a>
                        </div>
                    @endif

                    <div>
                        <label for="nfe-cliente-id" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente associado</label>
                        <select
                            id="nfe-cliente-id"
                            name="cliente_id"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            @disabled(!$possuiClientesDisponiveis)
                        >
                            @foreach($clientes as $cliente)
                                @php
                                    $documento = preg_replace('/\D/', '', (string) ($cliente->documento ?? ''));
                                    $documentoLabel = strlen($documento) === 14
                                        ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento)
                                        : ($cliente->documento ?? null);
                                    $clienteNome = $cliente->razao_social ?: $cliente->nome ?: 'Cliente sem nome';
                                @endphp
                                <option value="{{ $cliente->id }}" @selected((string) $defaultClienteId === (string) $cliente->id)>
                                    {{ $clienteNome }}@if($cliente->is_empresa_propria) (Empresa própria)@endif @if($documentoLabel) · {{ $documentoLabel }}@endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[11px] text-gray-500">Toda consulta avulsa de NF-e ou CT-e fica vinculada a um cliente. A empresa própria é selecionada automaticamente quando disponível.</p>
                    </div>

                    <div>
                        <label for="nfe-chave" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Chave de acesso</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input
                                type="text"
                                id="nfe-chave"
                                inputmode="numeric"
                                autocomplete="off"
                                maxlength="60"
                                placeholder="Cole a chave de 44 dígitos"
                                class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                @disabled(!$possuiClientesDisponiveis)
                            >
                            <button
                                type="button"
                                id="btn-consultar-nfe"
                                class="px-4 py-2 rounded text-sm font-medium text-white disabled:opacity-40 disabled:cursor-not-allowed"
                                style="background-color: #374151"
                                disabled
                            >
                                Consultar documento
                            </button>
                        </div>
                        <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p id="nfe-chave-feedback" class="text-[11px] text-gray-500">Cole a chave com 44 dígitos (pontos/espaços são removidos automaticamente).</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide"><span id="nfe-chave-count">0</span>/44 dígitos</p>
                        </div>
                    </div>

                    <div id="bloco-progresso" class="hidden border border-gray-200 rounded p-4 bg-gray-50/50">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Consultando</p>
                            <p id="progresso-percent" class="text-[10px] text-gray-500 font-mono">0%</p>
                        </div>
                        <div class="progress-track">
                            <div id="progresso-bar" class="progress-fill"></div>
                        </div>
                        <p id="progresso-etapa" class="text-xs text-gray-600 mt-2">Iniciando consulta...</p>
                    </div>

                    <div id="bloco-erro" class="hidden border border-red-200 rounded p-4" style="background-color: #fef2f2">
                        <div class="flex items-start gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start" style="background-color: #dc2626">Erro</span>
                            <div class="flex-1 min-w-0">
                                <p id="erro-titulo" class="text-sm font-semibold text-gray-900">Não foi possível consultar</p>
                                <p id="erro-mensagem" class="text-xs text-gray-700 mt-1">-</p>
                                <p id="erro-refund" class="hidden text-[11px] text-gray-500 mt-2">Créditos estornados.</p>
                            </div>
                        </div>
                    </div>

                    <div id="bloco-resultado" class="hidden border border-gray-200 rounded overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado da consulta</span>
                            <span id="resultado-status-badge" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start sm:self-auto" style="background-color: #374151">-</span>
                        </div>

                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                                <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo</p>
                                    <p id="resultado-tipo" class="text-sm font-bold text-gray-900 mt-1">-</p>
                                </div>
                                <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação</p>
                                    <p id="resultado-situacao" class="text-sm font-bold text-gray-900 mt-1">-</p>
                                </div>
                                <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor total</p>
                                    <p id="resultado-valor" class="text-sm font-bold text-gray-900 font-mono mt-1">-</p>
                                </div>
                                <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emissão</p>
                                    <p id="resultado-emissao" class="text-sm font-bold text-gray-900 mt-1">-</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                                <div class="border border-gray-200 rounded p-3">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Emitente</p>
                                    <p id="resultado-emitente" class="text-sm text-gray-900 mt-1">-</p>
                                </div>
                                <div class="border border-gray-200 rounded p-3">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Destinatário / Tomador</p>
                                    <p id="resultado-destinatario" class="text-sm text-gray-900 mt-1">-</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-[1fr,240px] gap-2">
                                <div class="border border-gray-200 rounded p-3">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Chave consultada</p>
                                    <p id="resultado-chave" class="text-xs text-gray-900 font-mono break-all mt-1">-</p>
                                </div>
                                <div class="border border-gray-200 rounded p-3">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente associado</p>
                                    <p id="resultado-cliente" class="text-sm text-gray-900 mt-1">-</p>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 pt-1">
                                <a id="btn-resultado-detalhe" href="#" class="px-4 py-2 rounded text-sm font-medium text-white text-center" style="background-color: #374151">Ver detalhe do documento</a>
                                <button type="button" id="btn-resultado-reconsultar" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-sm font-medium">Consultar novamente</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="h-full">
                <section class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
                        <span id="saldo-badge" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $saldoSuficiente ? '#047857' : '#dc2626' }}">
                            {{ $saldoSuficiente ? 'Saldo suficiente' : 'Saldo insuficiente' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-gray-200">
                        <div class="px-4 py-2.5">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Custo por consulta</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($custoEstimadoCreditos, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-500 mt-0.5">créditos por documento</p>
                        </div>
                        <div class="px-4 py-2.5">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo atual</p>
                            <p id="saldo-atual-label" class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($saldoAtual, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-500 mt-0.5">créditos disponíveis</p>
                        </div>
                    </div>
                    <div>
                        <div class="px-4 py-2.5 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Suporte atual</p>
                                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">Por chave</span>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-gray-600">NF-e / NFC-e</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Modelo 55 / 65</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-gray-600">CT-e</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Modelo 57</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-[11px] text-gray-600">NFS-e</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Em breve</span>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 py-2.5 border-t border-gray-200 bg-gray-50/60">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Escopo da busca</p>
                            <div class="space-y-1 text-[11px] text-gray-600">
                                <p>Consulta avulsa com retorno inline e histórico recente do clearance.</p>
                                <p>Cliente associado é obrigatório antes do disparo da chave.</p>
                            </div>
                        </div>

                        <div class="px-4 py-2 border-t border-gray-200 bg-gray-50">
                            <p class="text-[10px] text-gray-600">Falhas do provedor estornam os créditos automaticamente.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <section class="mt-4 sm:mt-6 bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Últimas consultas</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $ultimasConsultasDfe->count() }}</span>
            </div>

            @if($ultimasConsultasDfe->isEmpty())
                <div class="px-4 py-6 text-center">
                    <p class="text-sm font-semibold text-gray-900">Nenhuma consulta ainda</p>
                    <p class="text-xs text-gray-500 mt-1">As consultas avulsas persistidas nas tabelas canônicas do clearance aparecerão aqui.</p>
                </div>
            @else
                <div class="grid grid-cols-1 xl:grid-cols-2">
                    @foreach($ultimasConsultasDfe as $consultaHistorico)
                        @php
                            $situacao = strtoupper((string) ($consultaHistorico->status ?? 'SALVA'));
                            $badgeCor = $badgeCoresSituacao[$situacao] ?? '#374151';
                            $tipoDocumento = strtoupper((string) ($consultaHistorico->tipo_documento ?: 'NFE'));
                            $clienteHistorico = $consultaHistorico->cliente_nome ?: 'Sem cliente';
                            $chaveAbrev = $consultaHistorico->chave_acesso
                                ? substr($consultaHistorico->chave_acesso, 0, 6) . '…' . substr($consultaHistorico->chave_acesso, -4)
                                : '';
                        @endphp
                        <div class="border-b border-gray-100 xl:border-r xl:[&:nth-child(2n)]:border-r-0">
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $tipoDocumento }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badgeCor }}">{{ $situacao }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #1f2937">Clearance</span>
                                    @if($consultaHistorico->numero)
                                        <span class="text-xs font-semibold text-gray-900">Nº {{ $consultaHistorico->numero }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-700 truncate">{{ $clienteHistorico }}</p>
                                @if($chaveAbrev)
                                    <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $chaveAbrev }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $consultaHistorico->momento_consulta ?: '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>

<script>
    window.BUSCAR_NFE_CONFIG = {
        custo: {{ $custoEstimadoCreditos }},
        endpoints: {
            consultar: '{{ route('app.clearance.buscar.consultar') }}',
            resultado: '{{ url('/app/clearance/buscar/resultado') }}',
            sse: '{{ url('/app/consulta/progresso/stream') }}',
        },
        defaultClienteId: @json($defaultClienteId),
        possuiClientesDisponiveis: @json($possuiClientesDisponiveis),
        cores: @json($badgeCoresSituacao),
    };
</script>
<script src="{{ asset('js/clearance-buscar.js') }}?v={{ @filemtime(public_path('js/clearance-buscar.js')) ?: time() }}" defer></script>
