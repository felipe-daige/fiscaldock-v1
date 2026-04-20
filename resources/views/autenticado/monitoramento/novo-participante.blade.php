{{-- Novo/Editar Participante - Cadastro Manual (PJ/PF) --}}
@php
    $isEditing = isset($participante) && $participante;
    $tipoDoc = $isEditing ? ($participante->tipo_documento ?? 'PJ') : 'PJ';
@endphp
<div class="bg-gray-100 min-h-screen" id="novo-participante-container">
    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header inline --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $isEditing ? 'Editar Participante' : 'Novo Participante' }}</h1>
                    <p class="mt-1 text-xs text-gray-500">
                        @if($isEditing)
                            Atualize os dados do participante <strong>{{ $participante->cnpj_formatado }}</strong>.
                        @else
                            Cadastre pessoa jurídica (CNPJ) ou física (CPF).
                        @endif
                    </p>
                </div>
                <a href="{{ $isEditing ? '/app/participante/' . $participante->id : '/app/participantes' }}" data-link
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        {{-- Info box --}}
        <div class="bg-white border border-gray-300 rounded p-4 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    @if($isEditing)
                        <h4 class="text-sm font-semibold text-gray-900">Edição de Participante</h4>
                        <p class="text-sm text-gray-700 mt-0.5">
                            O tipo de documento e o {{ $tipoDoc === 'PF' ? 'CPF' : 'CNPJ' }} não podem ser alterados. Atualize os demais campos conforme necessário.
                        </p>
                    @else
                        <h4 class="text-sm font-semibold text-gray-900">Cadastro de Participantes</h4>
                        <p class="text-sm text-gray-700 mt-0.5">
                            Cadastre empresas (CNPJ) ou pessoas físicas (CPF) para monitoramento fiscal, consultas e análise de risco.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            {{-- Form Area (2/3) --}}
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                <form id="form-novo-participante" method="POST" class="space-y-4 sm:space-y-6"
                    @if($isEditing) data-participante-id="{{ $participante->id }}" @endif>
                    @csrf
                    <input type="hidden" name="tipo_documento" id="np_tipo_documento" value="{{ $tipoDoc }}">

                    {{-- Card: Tipo de Pessoa + Dados --}}
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados do Participante</span>
                        </div>
                        <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-5">

                            {{-- Toggle PF/PJ --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pessoa</label>
                                <div class="grid grid-cols-2 gap-3 {{ $isEditing ? 'pointer-events-none opacity-60' : '' }}">
                                <button type="button" id="np_btn_pj"
                                    class="np-tipo-btn flex items-center gap-3 p-3 rounded border-2 {{ $tipoDoc === 'PJ' ? 'border-gray-800 bg-gray-50' : 'border-gray-300 bg-white' }} cursor-pointer transition-all"
                                    onclick="window._npToggleTipo('PJ')">
                                    <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 {{ $tipoDoc === 'PJ' ? 'text-gray-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="block text-sm font-semibold text-gray-900">Pessoa Jurídica</span>
                                        <span class="block text-xs text-gray-500">CNPJ</span>
                                    </div>
                                </button>
                                <button type="button" id="np_btn_pf"
                                    class="np-tipo-btn flex items-center gap-3 p-3 rounded border-2 {{ $tipoDoc === 'PF' ? 'border-gray-800 bg-gray-50' : 'border-gray-300 bg-white' }} cursor-pointer transition-all"
                                    onclick="window._npToggleTipo('PF')">
                                    <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 {{ $tipoDoc === 'PF' ? 'text-gray-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <span class="block text-sm font-semibold text-gray-900">Pessoa Física</span>
                                        <span class="block text-xs text-gray-500">CPF</span>
                                    </div>
                                </button>
                                </div>
                            </div>

                            {{-- Documento (CNPJ/CPF) --}}
                            <div>
                                <label for="np_cnpj" id="np_label_doc" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $tipoDoc === 'PF' ? 'CPF' : 'CNPJ' }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="np_cnpj"
                                    name="cnpj"
                                    required
                                    placeholder="{{ $tipoDoc === 'PF' ? '000.000.000-00' : '00.000.000/0000-00' }}"
                                    maxlength="{{ $tipoDoc === 'PF' ? '14' : '18' }}"
                                    value="{{ $isEditing ? $participante->cnpj_formatado : '' }}"
                                    {{ $isEditing ? 'readonly' : '' }}
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 {{ $isEditing ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                >
                                <p id="np_cnpj_error" class="mt-1 text-sm text-red-600 hidden"></p>
                            </div>

                            {{-- Razão Social (PJ only) --}}
                            <div id="np_campo_razao_social">
                                <label for="np_razao_social" class="block text-sm font-medium text-gray-700 mb-1">
                                    Razão Social <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="np_razao_social"
                                    name="razao_social"
                                    value="{{ old('razao_social', $isEditing ? $participante->razao_social : '') }}"
                                    placeholder="Razão social completa"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                <p id="np_razao_social_error" class="mt-1 text-sm text-red-600 hidden"></p>
                            </div>

                            {{-- Nome Fantasia / Nome Completo --}}
                            <div>
                                <label for="np_nome_fantasia" id="np_label_nome_fantasia" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nome Fantasia
                                </label>
                                <input
                                    type="text"
                                    id="np_nome_fantasia"
                                    name="nome_fantasia"
                                    value="{{ old('nome_fantasia', $isEditing ? $participante->nome_fantasia : '') }}"
                                    placeholder="Nome fantasia (opcional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                <p id="np_nome_fantasia_error" class="mt-1 text-sm text-red-600 hidden"></p>
                            </div>

                            {{-- Inscricao Estadual (PJ only) --}}
                            <div id="np_campo_ie">
                                <label for="np_inscricao_estadual" class="block text-sm font-medium text-gray-700 mb-1">
                                    Inscrição Estadual
                                </label>
                                <input
                                    type="text"
                                    id="np_inscricao_estadual"
                                    name="inscricao_estadual"
                                    value="{{ old('inscricao_estadual', $isEditing ? $participante->inscricao_estadual : '') }}"
                                    placeholder="Inscrição estadual (opcional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                            </div>

                            {{-- CRT + Telefone --}}
                            <div id="np_grid_crt_tel" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div id="np_campo_crt">
                                    <label for="np_crt" class="block text-sm font-medium text-gray-700 mb-1">
                                        CRT (Regime Tributário)
                                    </label>
                                    <select
                                        id="np_crt"
                                        name="crt"
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                        @php $crtVal = old('crt', $isEditing ? $participante->crt : ''); @endphp
                                        <option value="">Não informado</option>
                                        <option value="1" {{ $crtVal == '1' ? 'selected' : '' }}>1 - Simples Nacional</option>
                                        <option value="2" {{ $crtVal == '2' ? 'selected' : '' }}>2 - Simples (Excesso)</option>
                                        <option value="3" {{ $crtVal == '3' ? 'selected' : '' }}>3 - Regime Normal</option>
                                    </select>
                                </div>
                                <div id="np_campo_telefone">
                                    <label for="np_telefone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Telefone
                                    </label>
                                    <input
                                        type="text"
                                        id="np_telefone"
                                        name="telefone"
                                        value="{{ old('telefone', $isEditing ? $participante->telefone : '') }}"
                                        placeholder="(00) 00000-0000"
                                        maxlength="15"
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                </div>
                            </div>

                            {{-- Cliente associado --}}
                            <div>
                                <label for="np_cliente_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Cliente Associado
                                </label>
                                <select
                                    id="np_cliente_id"
                                    name="cliente_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                                    @php $clienteIdVal = old('cliente_id', $isEditing ? $participante->cliente_id : ''); @endphp
                                    <option value="">Não associar</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ $clienteIdVal == $cliente->id ? 'selected' : '' }}>{{ $cliente->razao_social ?? $cliente->nome }} ({{ $cliente->documento }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Endereço --}}
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Endereço</span>
                        </div>
                        <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-4">
                            {{-- CEP --}}
                            <div>
                                <label for="np_cep" class="block text-sm font-medium text-gray-700 mb-1">
                                    CEP
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        id="np_cep"
                                        name="cep"
                                        value="{{ old('cep', $isEditing ? $participante->cep : '') }}"
                                        placeholder="00000-000"
                                        maxlength="9"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                    <button
                                        type="button"
                                        id="np_btn_buscar_cep"
                                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded text-xs font-semibold hover:bg-gray-200 border border-gray-300 transition-colors"
                                    >
                                        Buscar
                                    </button>
                                </div>
                                <p id="np_cep_status" class="mt-1 text-sm hidden"></p>
                            </div>

                            {{-- Logradouro --}}
                            <div>
                                <label for="np_endereco" class="block text-sm font-medium text-gray-700 mb-1">
                                    Logradouro
                                </label>
                                <input
                                    type="text"
                                    id="np_endereco"
                                    name="endereco"
                                    value="{{ old('endereco', $isEditing ? $participante->endereco : '') }}"
                                    placeholder="Rua, Avenida, etc."
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                            </div>

                            {{-- Numero + Complemento (2 colunas) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="np_numero" class="block text-sm font-medium text-gray-700 mb-1">
                                        Número
                                    </label>
                                    <input
                                        type="text"
                                        id="np_numero"
                                        name="numero"
                                        value="{{ old('numero', $isEditing ? $participante->numero : '') }}"
                                        placeholder="123"
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                </div>
                                <div>
                                    <label for="np_complemento" class="block text-sm font-medium text-gray-700 mb-1">
                                        Complemento
                                    </label>
                                    <input
                                        type="text"
                                        id="np_complemento"
                                        name="complemento"
                                        value="{{ old('complemento', $isEditing ? $participante->complemento : '') }}"
                                        placeholder="Apto, Sala, etc."
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                </div>
                            </div>

                            {{-- Bairro --}}
                            <div>
                                <label for="np_bairro" class="block text-sm font-medium text-gray-700 mb-1">
                                    Bairro
                                </label>
                                <input
                                    type="text"
                                    id="np_bairro"
                                    name="bairro"
                                    value="{{ old('bairro', $isEditing ? $participante->bairro : '') }}"
                                    placeholder="Nome do bairro"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                >
                            </div>

                            {{-- Municipio + UF (2 colunas) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="np_municipio" class="block text-sm font-medium text-gray-700 mb-1">
                                        Município
                                    </label>
                                    <input
                                        type="text"
                                        id="np_municipio"
                                        name="municipio"
                                        value="{{ old('municipio', $isEditing ? $participante->municipio : '') }}"
                                        placeholder="Nome do município"
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                </div>
                                <div>
                                    <label for="np_uf" class="block text-sm font-medium text-gray-700 mb-1">
                                        UF
                                    </label>
                                    @php $ufVal = old('uf', $isEditing ? $participante->uf : ''); @endphp
                                    <select
                                        id="np_uf"
                                        name="uf"
                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    >
                                        <option value="">Selecione</option>
                                        @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                            <option value="{{ $uf }}" {{ $ufVal === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botoes de Acao --}}
                    <div class="flex gap-4 justify-end">
                        <a href="{{ $isEditing ? '/app/participante/' . $participante->id : '/app/participantes' }}" data-link
                           class="px-6 py-2.5 bg-white text-gray-700 rounded text-sm font-medium hover:bg-gray-50 border border-gray-300 transition-colors">
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            id="np_btn_salvar"
                            class="px-6 py-2.5 bg-gray-800 text-white rounded text-sm font-medium hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ $isEditing ? 'Atualizar Participante' : 'Salvar Participante' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Preview Sidebar (1/3) --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4 bg-white rounded border border-gray-300 p-4 sm:p-6">
                    <h3 class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-4">Preview</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span id="np_preview_badge"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-white"
                                style="background-color: {{ $tipoDoc === 'PJ' ? '#374151' : '#9ca3af' }}">
                                {{ $tipoDoc }}
                            </span>
                            <span id="np_preview_cliente_badge"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-white {{ ($isEditing && $participante->cliente_id) ? '' : 'hidden' }}"
                                style="background-color: #047857">
                                Cliente Associado
                            </span>
                        </div>

                        <div>
                            <p id="np_preview_razao" class="text-sm font-semibold text-gray-900">{{ $isEditing ? ($participante->razao_social ?: $participante->nome_fantasia ?: '-') : '-' }}</p>
                            <p id="np_preview_nome" class="text-xs text-gray-400">{{ $isEditing ? ($participante->nome_fantasia ?: '') : '' }}</p>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Documento</span>
                            <p id="np_preview_doc" class="text-sm font-mono text-gray-700">{{ $isEditing ? $participante->cnpj_formatado : '-' }}</p>
                        </div>

                        <div id="np_preview_crt_wrap" class="{{ $tipoDoc === 'PF' ? 'hidden' : '' }}">
                            <span class="text-xs uppercase tracking-wide text-gray-400">Regime Tributário</span>
                            <p id="np_preview_crt" class="text-sm text-gray-700">
                                @if($isEditing && $participante->crt)
                                    @switch($participante->crt)
                                        @case(1) Simples Nacional @break
                                        @case(2) Simples (Excesso) @break
                                        @case(3) Regime Normal @break
                                    @endswitch
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Cliente</span>
                            <p id="np_preview_cliente" class="text-sm text-gray-700">{{ $isEditing && $participante->cliente ? ($participante->cliente->razao_social ?? $participante->cliente->nome ?? '-') : '-' }}</p>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Contato</span>
                            <p id="np_preview_tel" class="text-sm text-gray-700">{{ $isEditing ? ($participante->telefone ?: '-') : '-' }}</p>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Endereço</span>
                            <p id="np_preview_endereco" class="text-sm text-gray-700">
                                @if($isEditing && $participante->endereco)
                                    {{ implode(', ', array_filter([$participante->endereco, $participante->numero, $participante->bairro])) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wide text-gray-400">Localização</span>
                            <p id="np_preview_local" class="text-sm text-gray-700">
                                @if($isEditing && ($participante->municipio || $participante->uf))
                                    {{ implode(' - ', array_filter([$participante->municipio, $participante->uf])) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast notification container --}}
<div id="np_toast" class="fixed top-4 right-4 z-50 hidden">
    <div id="np_toast_content" class="flex items-center gap-3 px-4 py-3 rounded border border-gray-300 text-sm font-medium max-w-sm">
        <span id="np_toast_icon"></span>
        <span id="np_toast_message"></span>
    </div>
</div>

<script>
    var viaCepBaseUrl = '{{ config("services.viacep.url") }}';
(function() {
    'use strict';

    var currentTipo = 'PJ';

    // === Masks ===
    function maskCNPJ(value) {
        return value
            .replace(/\D/g, '')
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2')
            .substring(0, 18);
    }

    function maskCPF(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
            .substring(0, 14);
    }

    function maskCEP(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{5})(\d)/, '$1-$2')
            .substring(0, 9);
    }

    function maskTelefone(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4,5})(\d{4})$/, '$1-$2')
            .substring(0, 15);
    }

    // === Validations ===
    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14) return false;
        if (/^(\d)\1+$/.test(cnpj)) return false;

        var tamanho = cnpj.length - 2;
        var numeros = cnpj.substring(0, tamanho);
        var digitos = cnpj.substring(tamanho);
        var soma = 0;
        var pos = tamanho - 7;

        for (var i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        var resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0)) return false;

        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;

        for (var j = tamanho; j >= 1; j--) {
            soma += numeros.charAt(tamanho - j) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(1)) return false;

        return true;
    }

    function validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return false;
        if (/^(\d)\1+$/.test(cpf)) return false;

        var soma = 0;
        for (var i = 0; i < 9; i++) {
            soma += parseInt(cpf.charAt(i)) * (10 - i);
        }
        var resto = (soma * 10) % 11;
        if (resto === 10) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return false;

        soma = 0;
        for (var j = 0; j < 10; j++) {
            soma += parseInt(cpf.charAt(j)) * (11 - j);
        }
        resto = (soma * 10) % 11;
        if (resto === 10) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return false;

        return true;
    }

    // === Toggle PF/PJ ===
    function toggleTipoDocumento(tipo) {
        currentTipo = tipo;
        var isPF = tipo === 'PF';

        // Hidden input
        document.getElementById('np_tipo_documento').value = tipo;

        // Toggle buttons visual
        var btnPJ = document.getElementById('np_btn_pj');
        var btnPF = document.getElementById('np_btn_pf');
        var iconPJ = btnPJ.querySelector('.w-10');
        var iconPF = btnPF.querySelector('.w-10');
        var svgPJ = btnPJ.querySelector('svg');
        var svgPF = btnPF.querySelector('svg');

        if (isPF) {
            btnPJ.className = 'np-tipo-btn flex items-center gap-3 p-3 rounded border-2 border-gray-300 bg-white cursor-pointer transition-all';
            btnPF.className = 'np-tipo-btn flex items-center gap-3 p-3 rounded border-2 border-gray-800 bg-gray-50 cursor-pointer transition-all';
            iconPJ.className = 'w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0';
            iconPF.className = 'w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0';
            svgPJ.className.baseVal = 'w-5 h-5 text-gray-500';
            svgPF.className.baseVal = 'w-5 h-5 text-gray-700';
        } else {
            btnPJ.className = 'np-tipo-btn flex items-center gap-3 p-3 rounded border-2 border-gray-800 bg-gray-50 cursor-pointer transition-all';
            btnPF.className = 'np-tipo-btn flex items-center gap-3 p-3 rounded border-2 border-gray-300 bg-white cursor-pointer transition-all';
            iconPJ.className = 'w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0';
            iconPF.className = 'w-10 h-10 bg-gray-100 rounded flex items-center justify-center shrink-0';
            svgPJ.className.baseVal = 'w-5 h-5 text-gray-700';
            svgPF.className.baseVal = 'w-5 h-5 text-gray-500';
        }

        // Document field: label, placeholder, maxlength
        var labelDoc = document.getElementById('np_label_doc');
        var inputDoc = document.getElementById('np_cnpj');
        if (isPF) {
            labelDoc.innerHTML = 'CPF <span class="text-red-500">*</span>';
            inputDoc.placeholder = '000.000.000-00';
            inputDoc.maxLength = 14;
        } else {
            labelDoc.innerHTML = 'CNPJ <span class="text-red-500">*</span>';
            inputDoc.placeholder = '00.000.000/0000-00';
            inputDoc.maxLength = 18;
        }

        // Re-apply mask to current value
        var rawVal = inputDoc.value.replace(/\D/g, '');
        if (rawVal) {
            inputDoc.value = isPF ? maskCPF(rawVal) : maskCNPJ(rawVal);
        }

        // Show/hide PJ-only fields
        document.getElementById('np_campo_razao_social').style.display = isPF ? 'none' : '';
        document.getElementById('np_campo_ie').style.display = isPF ? 'none' : '';
        document.getElementById('np_campo_crt').style.display = isPF ? 'none' : '';

        // Adjust telefone grid: if PF, telefone goes full width
        var gridCrtTel = document.getElementById('np_grid_crt_tel');
        if (isPF) {
            gridCrtTel.className = '';
        } else {
            gridCrtTel.className = 'grid grid-cols-1 md:grid-cols-2 gap-4';
        }

        // Nome Fantasia label and required indicator
        var labelNome = document.getElementById('np_label_nome_fantasia');
        var inputNome = document.getElementById('np_nome_fantasia');
        if (isPF) {
            labelNome.innerHTML = 'Nome Completo <span class="text-red-500">*</span>';
            inputNome.placeholder = 'Nome completo da pessoa';
        } else {
            labelNome.innerHTML = 'Nome Fantasia';
            inputNome.placeholder = 'Nome fantasia (opcional)';
        }

        // Clear errors when toggling
        clearAllErrors();

        // Update CTA link
        atualizarLinkConsultaCnpj(inputDoc.value);
        atualizarPreview();
    }

    // Expose for inline onclick
    window._npToggleTipo = toggleTipoDocumento;

    // === ViaCEP ===
    async function buscarCEP(cep) {
        var cepLimpo = cep.replace(/\D/g, '');
        if (cepLimpo.length !== 8) return;

        var statusEl = document.getElementById('np_cep_status');
        statusEl.textContent = 'Buscando CEP...';
        statusEl.className = 'mt-1 text-sm text-gray-600';
        statusEl.classList.remove('hidden');

        try {
            var response = await fetch(viaCepBaseUrl + '/' + cepLimpo + '/json/');
            var data = await response.json();

            if (!data.erro) {
                document.getElementById('np_endereco').value = data.logradouro || '';
                document.getElementById('np_bairro').value = data.bairro || '';
                document.getElementById('np_municipio').value = data.localidade || '';
                document.getElementById('np_uf').value = data.uf || '';
                atualizarPreview();
                statusEl.textContent = 'CEP encontrado!';
                statusEl.className = 'mt-1 text-sm text-green-600';
                setTimeout(function() { statusEl.classList.add('hidden'); }, 2000);
            } else {
                statusEl.textContent = 'CEP nao encontrado.';
                statusEl.className = 'mt-1 text-sm text-red-600';
            }
        } catch (error) {
            statusEl.textContent = 'Erro ao buscar CEP. Tente novamente.';
            statusEl.className = 'mt-1 text-sm text-red-600';
        }
    }

    // === Toast ===
    function showToast(message, type) {
        var toast = document.getElementById('np_toast');
        var content = document.getElementById('np_toast_content');
        var icon = document.getElementById('np_toast_icon');
        var msg = document.getElementById('np_toast_message');

        msg.textContent = message;

        if (type === 'success') {
            content.className = 'flex items-center gap-3 px-4 py-3 rounded border text-sm font-medium max-w-sm text-white';
            content.style.backgroundColor = '#047857';
            content.style.borderColor = '#047857';
            icon.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        } else {
            content.className = 'flex items-center gap-3 px-4 py-3 rounded border text-sm font-medium max-w-sm text-white';
            content.style.backgroundColor = '#b91c1c';
            content.style.borderColor = '#b91c1c';
            icon.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        }

        toast.classList.remove('hidden');
        setTimeout(function() { toast.classList.add('hidden'); }, 4000);
    }

    // === Field error helpers ===
    function showFieldError(fieldId, message) {
        var errorEl = document.getElementById(fieldId + '_error');
        var inputEl = document.getElementById(fieldId);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        if (inputEl) {
            inputEl.classList.remove('border-gray-300');
            inputEl.classList.add('border-red-500');
        }
    }

    function clearFieldError(fieldId) {
        var errorEl = document.getElementById(fieldId + '_error');
        var inputEl = document.getElementById(fieldId);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
        if (inputEl) {
            inputEl.classList.remove('border-red-500');
            inputEl.classList.add('border-gray-300');
        }
    }

    function clearAllErrors() {
        ['np_cnpj', 'np_razao_social', 'np_nome_fantasia'].forEach(clearFieldError);
    }

    // === Update CTA link with CNPJ ===
    function atualizarLinkConsultaCnpj(cnpjValue) {
        var link = document.getElementById('np_link_consultar_cnpj');
        if (!link) return;
        var cnpjLimpo = cnpjValue.replace(/\D/g, '');
        if (currentTipo === 'PJ' && cnpjLimpo.length === 14) {
            link.href = '/app/consulta/avulso?cnpj=' + cnpjLimpo;
        } else {
            link.href = '/app/consulta/avulso';
        }
    }

    function atualizarPreview() {
        var badge = document.getElementById('np_preview_badge');
        var badgeCliente = document.getElementById('np_preview_cliente_badge');
        var nome = document.getElementById('np_nome_fantasia');
        var razao = document.getElementById('np_razao_social');
        var doc = document.getElementById('np_cnpj');
        var crt = document.getElementById('np_crt');
        var cliente = document.getElementById('np_cliente_id');
        var telefone = document.getElementById('np_telefone');
        var endereco = document.getElementById('np_endereco');
        var numero = document.getElementById('np_numero');
        var bairro = document.getElementById('np_bairro');
        var municipio = document.getElementById('np_municipio');
        var uf = document.getElementById('np_uf');
        var crtWrap = document.getElementById('np_preview_crt_wrap');

        if (badge) {
            badge.textContent = currentTipo;
            badge.style.backgroundColor = currentTipo === 'PF' ? '#9ca3af' : '#374151';
        }

        if (crtWrap) {
            crtWrap.classList.toggle('hidden', currentTipo === 'PF');
        }

        var razaoValor = currentTipo === 'PF'
            ? ((nome && nome.value.trim()) || '-')
            : ((razao && razao.value.trim()) || '-');
        var nomeValor = currentTipo === 'PF'
            ? ''
            : ((nome && nome.value.trim()) || '');
        var docValor = (doc && doc.value.trim()) || '-';
        var crtValor = '-';
        if (crt && crt.value) {
            crtValor = crt.options[crt.selectedIndex].text;
        }
        var clienteValor = '-';
        if (cliente && cliente.value) {
            clienteValor = cliente.options[cliente.selectedIndex].text;
            if (badgeCliente) badgeCliente.classList.remove('hidden');
        } else if (badgeCliente) {
            badgeCliente.classList.add('hidden');
        }
        var telValor = (telefone && telefone.value.trim()) || '-';
        var enderecoValor = [endereco && endereco.value.trim(), numero && numero.value.trim(), bairro && bairro.value.trim()].filter(Boolean).join(', ') || '-';
        var localValor = [municipio && municipio.value.trim(), uf && uf.value].filter(Boolean).join(' - ') || '-';

        document.getElementById('np_preview_razao').textContent = razaoValor;
        document.getElementById('np_preview_nome').textContent = nomeValor;
        document.getElementById('np_preview_doc').textContent = docValor;
        document.getElementById('np_preview_crt').textContent = crtValor;
        document.getElementById('np_preview_cliente').textContent = clienteValor;
        document.getElementById('np_preview_tel').textContent = telValor;
        document.getElementById('np_preview_endereco').textContent = enderecoValor;
        document.getElementById('np_preview_local').textContent = localValor;
    }

    // === Init ===
    function init() {
        var form = document.getElementById('form-novo-participante');
        if (!form) return;

        var editId = form.dataset.participanteId || null;
        var isEditing = !!editId;

        // Read initial tipo from hidden input (set by Blade)
        currentTipo = document.getElementById('np_tipo_documento').value || 'PJ';

        var cnpjInput = document.getElementById('np_cnpj');
        var cepInput = document.getElementById('np_cep');
        var telefoneInput = document.getElementById('np_telefone');
        var btnBuscarCep = document.getElementById('np_btn_buscar_cep');
        var camposPreview = ['np_razao_social', 'np_nome_fantasia', 'np_cnpj', 'np_crt', 'np_cliente_id', 'np_telefone', 'np_endereco', 'np_numero', 'np_bairro', 'np_municipio', 'np_uf'];

        // In edit mode, apply toggle visual for the stored tipo
        if (isEditing && currentTipo === 'PF') {
            toggleTipoDocumento('PF');
        }
        atualizarPreview();

        camposPreview.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', atualizarPreview);
            el.addEventListener('change', atualizarPreview);
        });

        // Document input mask (dynamic)
        if (cnpjInput) {
            cnpjInput.addEventListener('input', function() {
                if (currentTipo === 'PF') {
                    this.value = maskCPF(this.value);
                } else {
                    this.value = maskCNPJ(this.value);
                }
                atualizarLinkConsultaCnpj(this.value);
            });
            cnpjInput.addEventListener('blur', function() {
                var val = this.value.replace(/\D/g, '');
                if (currentTipo === 'PF') {
                    if (val.length === 11 && !validarCPF(val)) {
                        showFieldError('np_cnpj', 'CPF invalido. Verifique os digitos.');
                    } else if (val.length > 0 && val.length < 11) {
                        showFieldError('np_cnpj', 'CPF incompleto.');
                    } else {
                        clearFieldError('np_cnpj');
                    }
                } else {
                    if (val.length === 14 && !validarCNPJ(val)) {
                        showFieldError('np_cnpj', 'CNPJ invalido. Verifique os digitos.');
                    } else if (val.length > 0 && val.length < 14) {
                        showFieldError('np_cnpj', 'CNPJ incompleto.');
                    } else {
                        clearFieldError('np_cnpj');
                    }
                }
            });
        }

        // CEP mask
        if (cepInput) {
            cepInput.addEventListener('input', function() {
                this.value = maskCEP(this.value);
            });
        }

        // Telefone mask
        if (telefoneInput) {
            telefoneInput.addEventListener('input', function() {
                this.value = maskTelefone(this.value);
            });
        }

        // Buscar CEP
        if (btnBuscarCep) {
            btnBuscarCep.addEventListener('click', function(e) {
                e.preventDefault();
                var cep = document.getElementById('np_cep').value;
                if (cep.replace(/\D/g, '').length === 8) {
                    buscarCEP(cep);
                } else {
                    var statusEl = document.getElementById('np_cep_status');
                    statusEl.textContent = 'Informe um CEP valido com 8 digitos.';
                    statusEl.className = 'mt-1 text-sm text-red-600';
                    statusEl.classList.remove('hidden');
                }
            });
        }

        // Form submit (AJAX)
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            clearAllErrors();

            var isPF = currentTipo === 'PF';
            var docLabel = isPF ? 'CPF' : 'CNPJ';
            var expectedLen = isPF ? 11 : 14;

            // Client-side validation: document (skip in edit mode - readonly)
            if (!isEditing) {
                var docVal = cnpjInput.value.replace(/\D/g, '');
                if (docVal.length !== expectedLen) {
                    showFieldError('np_cnpj', 'Informe um ' + docLabel + ' valido com ' + expectedLen + ' digitos.');
                    cnpjInput.focus();
                    return;
                }
                if (isPF && !validarCPF(docVal)) {
                    showFieldError('np_cnpj', 'CPF invalido. Verifique os digitos.');
                    cnpjInput.focus();
                    return;
                }
                if (!isPF && !validarCNPJ(docVal)) {
                    showFieldError('np_cnpj', 'CNPJ invalido. Verifique os digitos.');
                    cnpjInput.focus();
                    return;
                }
            }

            // Client-side validation: required fields per type
            if (!isPF) {
                var razaoSocial = document.getElementById('np_razao_social').value.trim();
                if (!razaoSocial) {
                    showFieldError('np_razao_social', 'Razao social e obrigatoria.');
                    document.getElementById('np_razao_social').focus();
                    return;
                }
            } else {
                var nomeCompleto = document.getElementById('np_nome_fantasia').value.trim();
                if (!nomeCompleto) {
                    showFieldError('np_nome_fantasia', 'Nome completo e obrigatorio.');
                    document.getElementById('np_nome_fantasia').focus();
                    return;
                }
            }

            // Disable button
            var btnSalvar = document.getElementById('np_btn_salvar');
            btnSalvar.disabled = true;
            btnSalvar.textContent = 'Salvando...';

            // Collect form data
            var formData = new FormData(form);
            var body = {};
            formData.forEach(function(value, key) {
                if (key !== '_token' && value !== '') {
                    body[key] = value;
                }
            });

            // Get CSRF token
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            var token = csrfToken ? csrfToken.getAttribute('content') : formData.get('_token');

            // Determine URL and method based on mode
            var fetchUrl = isEditing
                ? '/app/participante/' + editId
                : '/app/participante/novo';

            if (isEditing) {
                body._method = 'PUT';
            }

            var btnLabel = isEditing ? 'Atualizar Participante' : 'Salvar Participante';

            fetch(fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(body)
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function(result) {
                btnSalvar.disabled = false;
                btnSalvar.textContent = btnLabel;

                if (result.data.success) {
                    showToast(result.data.message || (isEditing ? 'Participante atualizado!' : 'Participante cadastrado!'), 'success');
                    // Redirect after short delay
                    setTimeout(function() {
                        var redirectUrl = result.data.redirect || '/app/participantes';
                        var spaLink = document.querySelector('a[data-link][href="' + redirectUrl + '"]');
                        if (spaLink) {
                            spaLink.click();
                        } else {
                            window.location.href = redirectUrl;
                        }
                    }, 800);
                } else if (result.status === 422 && result.data.errors) {
                    // Validation errors
                    var errors = result.data.errors;
                    if (errors.cnpj) showFieldError('np_cnpj', errors.cnpj[0]);
                    if (errors.razao_social) showFieldError('np_razao_social', errors.razao_social[0]);
                    if (errors.nome_fantasia) showFieldError('np_nome_fantasia', errors.nome_fantasia[0]);
                    if (errors.cliente_id) showToast(errors.cliente_id[0], 'error');
                } else {
                    showToast(result.data.error || (isEditing ? 'Erro ao atualizar participante.' : 'Erro ao cadastrar participante.'), 'error');
                }
            })
            .catch(function(error) {
                btnSalvar.disabled = false;
                btnSalvar.textContent = btnLabel;
                showToast('Erro de conexao. Tente novamente.', 'error');
            });
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-init for SPA navigation
    window.initNovoParticipante = init;
})();
</script>
