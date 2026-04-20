{{-- Novo/Editar Cliente --}}
@php
    $isEditing = isset($cliente) && $cliente;
    $tipoPessoa = $isEditing ? $cliente->tipo_pessoa : 'PJ';
    $estados = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
@endphp
<div class="bg-gray-100 min-h-screen" id="novo-cliente-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-4 sm:mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $isEditing ? 'Editar Cliente' : 'Novo Cliente' }}</h1>
                    <p class="mt-1 text-xs text-gray-500">
                        @if($isEditing)
                            Atualize os dados do cliente. Tipo de pessoa e documento não podem ser alterados.
                        @else
                            Cadastre pessoa jurídica (CNPJ) ou física (CPF).
                        @endif
                    </p>
                </div>
                <a href="/app/clientes" data-link
                   class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-300 rounded p-4 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    @if($isEditing)
                        <h4 class="text-sm font-semibold text-gray-900">Edição de Cliente</h4>
                        <p class="text-sm text-gray-700 mt-0.5">
                            O tipo de pessoa e o documento não podem ser alterados. Atualize os demais campos conforme necessário.
                        </p>
                    @else
                        <h4 class="text-sm font-semibold text-gray-900">Cadastro de Clientes</h4>
                        <p class="text-sm text-gray-700 mt-0.5">
                            Cadastre empresas (CNPJ) ou pessoas físicas (CPF) para organizar a base operacional e vincular participantes e importações.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            {{-- Form Column (2/3) --}}
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">

                {{-- Card 1: Dados do Cliente --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados do Cliente</span>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-5">

                    {{-- Tipo Pessoa Toggle --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pessoa</label>
                        <div class="grid grid-cols-2 gap-3 {{ $isEditing ? 'pointer-events-none opacity-60' : '' }}">
                            <button type="button" id="btn-pj"
                                class="tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 {{ $tipoPessoa === 'PJ' ? 'border-gray-800 bg-gray-50' : 'border-gray-300 bg-white' }} cursor-pointer transition-all"
                                onclick="setTipoPessoa('PJ')">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 {{ $tipoPessoa === 'PJ' ? 'text-gray-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <span class="block text-sm font-semibold text-gray-900">Pessoa Jurídica</span>
                                    <span class="block text-xs text-gray-500">CNPJ</span>
                                </div>
                            </button>
                            <button type="button" id="btn-pf"
                                class="tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 {{ $tipoPessoa === 'PF' ? 'border-gray-800 bg-gray-50' : 'border-gray-300 bg-white' }} cursor-pointer transition-all"
                                onclick="setTipoPessoa('PF')">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 {{ $tipoPessoa === 'PF' ? 'text-gray-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <span class="block text-sm font-semibold text-gray-900">Pessoa Física</span>
                                    <span class="block text-xs text-gray-500">CPF</span>
                                </div>
                            </button>
                        </div>
                        <input type="hidden" id="tipo_pessoa" value="{{ $tipoPessoa }}">
                    </div>

                    {{-- Documento --}}
                    <div class="mb-5">
                        <label for="documento" class="block text-sm font-medium text-gray-700 mb-1">
                            <span id="label-doc">{{ $tipoPessoa === 'PJ' ? 'CNPJ' : 'CPF' }}</span> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="documento"
                            value="{{ $isEditing ? $cliente->documento_formatado : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 {{ $isEditing ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                            placeholder="{{ $tipoPessoa === 'PJ' ? '00.000.000/0000-00' : '000.000.000-00' }}"
                            maxlength="18"
                            {{ $isEditing ? 'readonly' : '' }}>
                        <p id="doc-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    {{-- Razao Social --}}
                    <div id="field-razao" class="{{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                        <label for="razao_social" class="block text-sm font-medium text-gray-700 mb-1">
                            Razão Social <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="razao_social"
                            value="{{ $isEditing ? $cliente->razao_social : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="Razão social da empresa">
                    </div>

                    {{-- Nome / Nome Fantasia --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                                <span id="label-nome">{{ $tipoPessoa === 'PJ' ? 'Nome Fantasia' : 'Nome Completo' }}</span>
                                <span id="nome-required" class="{{ $tipoPessoa === 'PJ' ? 'hidden' : '' }} text-red-500">*</span>
                            </label>
                            <input type="text" id="nome"
                                value="{{ $isEditing ? $cliente->nome : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="{{ $tipoPessoa === 'PJ' ? 'Nome fantasia (opcional)' : 'Nome completo' }}">
                        </div>
                        {{-- Inscricao Estadual (PJ only) --}}
                        <div id="field-ie" class="{{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                            <label for="inscricao_estadual" class="block text-sm font-medium text-gray-700 mb-1">Inscrição Estadual</label>
                            <input type="text" id="inscricao_estadual"
                                value="{{ $isEditing ? $cliente->inscricao_estadual : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Inscrição estadual"
                                maxlength="20">
                        </div>
                    </div>

                    {{-- CRT (PJ only) + Telefone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div id="field-crt" class="{{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                            <label for="crt" class="block text-sm font-medium text-gray-700 mb-1">CRT (Regime Tributário)</label>
                            @php $crtVal = $isEditing ? $cliente->crt : ''; @endphp
                            <select id="crt"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Não informado</option>
                                <option value="1" {{ $crtVal == 1 ? 'selected' : '' }}>1 - Simples Nacional</option>
                                <option value="2" {{ $crtVal == 2 ? 'selected' : '' }}>2 - Simples (Excesso)</option>
                                <option value="3" {{ $crtVal == 3 ? 'selected' : '' }}>3 - Regime Normal</option>
                            </select>
                        </div>
                        <div>
                            <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" id="telefone"
                                value="{{ $isEditing ? $cliente->telefone : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="(00) 00000-0000"
                                maxlength="20">
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email"
                            value="{{ $isEditing ? $cliente->email : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="email@empresa.com">
                    </div>

                    {{-- Empresa Propria Toggle --}}
                    <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                        <button type="button" id="btn-empresa-propria"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-gray-400 focus:ring-offset-2 {{ ($isEditing && $cliente->is_empresa_propria) ? 'bg-gray-800' : 'bg-gray-200' }}"
                            role="switch"
                            aria-checked="{{ ($isEditing && $cliente->is_empresa_propria) ? 'true' : 'false' }}"
                            onclick="toggleEmpresaPropria()">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ ($isEditing && $cliente->is_empresa_propria) ? 'translate-x-5' : 'translate-x-0' }}"
                                id="toggle-knob"></span>
                        </button>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Esta é minha empresa</span>
                            <p class="text-xs text-gray-400">Marque se este CNPJ é da sua própria empresa/escritório</p>
                        </div>
                        <input type="hidden" id="is_empresa_propria" value="{{ ($isEditing && $cliente->is_empresa_propria) ? '1' : '0' }}">
                    </div>
                    </div>
                </div>

                {{-- Card 2: Endereco --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Endereço</span>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-4">

                    {{-- CEP com busca --}}
                    <div>
                        <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <div class="flex gap-2">
                            <input type="text" id="cep"
                                value="{{ $isEditing ? $cliente->cep : '' }}"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="00000-000"
                                maxlength="9">
                            <button type="button" id="btn-buscar-cep"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm font-semibold hover:bg-gray-200 border border-gray-300 transition-colors">
                                Buscar
                            </button>
                        </div>
                        <p id="cep-status" class="mt-1 text-xs hidden"></p>
                    </div>

                    {{-- Logradouro --}}
                    <div>
                        <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                        <input type="text" id="endereco"
                            value="{{ $isEditing ? $cliente->endereco : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="Rua, Avenida, etc.">
                    </div>

                    {{-- Numero + Complemento --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Numero</label>
                            <input type="text" id="numero"
                                value="{{ $isEditing ? $cliente->numero : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="123"
                                maxlength="20">
                        </div>
                        <div>
                            <label for="complemento" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input type="text" id="complemento"
                                value="{{ $isEditing ? $cliente->complemento : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Apto, Sala, etc."
                                maxlength="100">
                        </div>
                    </div>

                    {{-- Bairro --}}
                    <div>
                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                        <input type="text" id="bairro"
                            value="{{ $isEditing ? $cliente->bairro : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="Nome do bairro"
                            maxlength="100">
                    </div>

                    {{-- Municipio + UF --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="municipio" class="block text-sm font-medium text-gray-700 mb-1">Município</label>
                            <input type="text" id="municipio"
                                value="{{ $isEditing ? $cliente->municipio : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Nome do municipio">
                        </div>
                        <div>
                            <label for="uf" class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                            <select id="uf"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Selecione</option>
                                @foreach($estados as $uf)
                                    <option value="{{ $uf }}" {{ ($isEditing && $cliente->uf === $uf) ? 'selected' : '' }}>{{ $uf }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Card 3: Dados Receita Federal (PJ only) --}}
                <div id="card-dados-rf" class="bg-white rounded border border-gray-300 overflow-hidden {{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                    <div class="bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados Receita Federal</span>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-4">
                    <p class="text-sm text-gray-700">Campos preenchidos automaticamente por consultas ou manualmente.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="situacao_cadastral" class="block text-sm font-medium text-gray-700 mb-1">Situação Cadastral</label>
                            <input type="text" id="situacao_cadastral"
                                value="{{ $isEditing ? $cliente->situacao_cadastral : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="ATIVA, BAIXADA, etc.">
                        </div>
                        <div>
                            <label for="regime_tributario" class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário</label>
                            <input type="text" id="regime_tributario"
                                value="{{ $isEditing ? $cliente->regime_tributario : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Simples Nacional, Lucro Presumido, etc.">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="capital_social" class="block text-sm font-medium text-gray-700 mb-1">Capital Social</label>
                            <input type="text" id="capital_social"
                                value="{{ $isEditing && $cliente->capital_social ? number_format($cliente->capital_social, 2, ',', '.') : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="0,00">
                        </div>
                        <div>
                            <label for="natureza_juridica" class="block text-sm font-medium text-gray-700 mb-1">Natureza Jurídica</label>
                            <input type="text" id="natureza_juridica"
                                value="{{ $isEditing ? $cliente->natureza_juridica : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="206-2 - Sociedade Ltda">
                        </div>
                        <div>
                            <label for="porte" class="block text-sm font-medium text-gray-700 mb-1">Porte</label>
                            <input type="text" id="porte"
                                value="{{ $isEditing ? $cliente->porte : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="ME, EPP, Demais">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="data_inicio_atividade" class="block text-sm font-medium text-gray-700 mb-1">Início Atividade</label>
                            <input type="date" id="data_inicio_atividade"
                                value="{{ $isEditing && $cliente->data_inicio_atividade ? $cliente->data_inicio_atividade->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        </div>
                        <div>
                            <label for="cnpj_matriz" class="block text-sm font-medium text-gray-700 mb-1">CNPJ Matriz</label>
                            <input type="text" id="cnpj_matriz"
                                value="{{ $isEditing ? $cliente->cnpj_matriz : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Apenas para filiais"
                                maxlength="14">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="cnae_principal" class="block text-sm font-medium text-gray-700 mb-1">CNAE Principal</label>
                            <input type="text" id="cnae_principal"
                                value="{{ $isEditing ? $cliente->cnae_principal : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="0000-0/00"
                                maxlength="10">
                        </div>
                        <div>
                            <label for="cnae_principal_descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição CNAE</label>
                            <input type="text" id="cnae_principal_descricao"
                                value="{{ $isEditing ? $cliente->cnae_principal_descricao : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Descrição da atividade principal">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="suframa" class="block text-sm font-medium text-gray-700 mb-1">SUFRAMA</label>
                            <input type="text" id="suframa"
                                value="{{ $isEditing ? $cliente->suframa : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Número SUFRAMA (opcional)"
                                maxlength="20">
                        </div>
                        <div>
                            <label for="codigo_municipal" class="block text-sm font-medium text-gray-700 mb-1">Código Municipal</label>
                            <input type="text" id="codigo_municipal"
                                value="{{ $isEditing ? $cliente->codigo_municipal : '' }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Código IBGE"
                                maxlength="10">
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex items-center gap-3">
                    <button type="button" id="btn-salvar"
                        class="px-6 py-2.5 bg-gray-800 text-white rounded text-sm font-medium hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        onclick="salvarCliente()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $isEditing ? 'Salvar Alteracoes' : 'Cadastrar Cliente' }}
                    </button>
                    <a href="/app/clientes" data-link class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                        Cancelar
                    </a>
                    <span id="save-status" class="text-sm text-gray-400 hidden"></span>
                </div>
            </div>

            {{-- Preview Column (1/3) --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4 bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Preview</span>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5 space-y-4">
                        {{-- Tipo --}}
                        <div class="flex items-center gap-2">
                            <span id="preview-badge"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                style="background-color: {{ $tipoPessoa === 'PJ' ? '#374151' : '#9ca3af' }}">
                                {{ $tipoPessoa }}
                            </span>
                            <span id="preview-empresa-propria"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white {{ ($isEditing && $cliente->is_empresa_propria) ? '' : 'hidden' }}"
                                style="background-color: #047857">
                                Empresa Própria
                            </span>
                        </div>

                        {{-- Nome/Razao --}}
                        <div>
                            <p id="preview-razao" class="text-sm font-semibold text-gray-900">{{ $isEditing ? ($cliente->razao_social ?? $cliente->nome ?? '-') : '-' }}</p>
                            <p id="preview-nome" class="text-sm text-gray-500">{{ $isEditing ? $cliente->nome : '' }}</p>
                        </div>

                        {{-- Documento --}}
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Documento</span>
                            <p id="preview-doc" class="text-sm font-mono text-gray-700">{{ $isEditing ? $cliente->documento_formatado : '-' }}</p>
                        </div>

                        {{-- CRT (PJ) --}}
                        <div id="preview-crt-wrap" class="{{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime Tributário</span>
                            <p id="preview-crt" class="text-sm text-gray-700">
                                @if($isEditing && $cliente->crt)
                                    @switch($cliente->crt)
                                        @case(1) Simples Nacional @break
                                        @case(2) Simples (Excesso) @break
                                        @case(3) Regime Normal @break
                                    @endswitch
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        {{-- Contato --}}
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Contato</span>
                            <p id="preview-email" class="text-sm text-gray-700">{{ $isEditing ? $cliente->email : '-' }}</p>
                            <p id="preview-tel" class="text-sm text-gray-700">{{ $isEditing ? $cliente->telefone : '' }}</p>
                        </div>

                        {{-- Endereço --}}
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Endereço</span>
                            <p id="preview-endereco" class="text-sm text-gray-700">
                                @if($isEditing && $cliente->endereco)
                                    {{ implode(', ', array_filter([$cliente->endereco, $cliente->numero, $cliente->bairro])) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        {{-- Localização --}}
                        <div>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Localização</span>
                            <p id="preview-local" class="text-sm text-gray-700">
                                @if($isEditing && ($cliente->municipio || $cliente->uf))
                                    {{ implode(' - ', array_filter([$cliente->municipio, $cliente->uf])) }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        {{-- Situação (PJ) --}}
                        <div id="preview-situacao-wrap" class="{{ $tipoPessoa === 'PF' ? 'hidden' : '' }}">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação Cadastral</span>
                            <p id="preview-situacao" class="text-sm text-gray-700">{{ $isEditing && $cliente->situacao_cadastral ? $cliente->situacao_cadastral : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var viaCepBaseUrl = '{{ config("services.viacep.url") }}';
(function() {
    const isEditing = {{ $isEditing ? 'true' : 'false' }};
    const clienteId = {{ $isEditing ? $cliente->id : 'null' }};
    let empresaPropria = {{ ($isEditing && $cliente->is_empresa_propria) ? 'true' : 'false' }};

    // PJ-only field containers
    const pjOnlyFields = ['field-razao', 'field-ie', 'field-crt', 'card-dados-rf', 'preview-crt-wrap', 'preview-situacao-wrap'];

    window.setTipoPessoa = function(tipo) {
        if (isEditing) return;
        document.getElementById('tipo_pessoa').value = tipo;

        const btnPJ = document.getElementById('btn-pj');
        const btnPF = document.getElementById('btn-pf');
        const labelDoc = document.getElementById('label-doc');
        const labelNome = document.getElementById('label-nome');
        const nomeRequired = document.getElementById('nome-required');
        const docInput = document.getElementById('documento');
        const previewBadge = document.getElementById('preview-badge');
        const iconPJ = btnPJ.querySelector('.w-10');
        const iconPF = btnPF.querySelector('.w-10');
        const svgPJ = btnPJ.querySelector('svg');
        const svgPF = btnPF.querySelector('svg');

        if (tipo === 'PJ') {
            btnPJ.className = 'tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 border-gray-800 bg-gray-50 cursor-pointer transition-all';
            btnPF.className = 'tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 border-gray-300 bg-white cursor-pointer transition-all';
            iconPJ.className = 'w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0';
            iconPF.className = 'w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0';
            svgPJ.className.baseVal = 'w-5 h-5 text-gray-700';
            svgPF.className.baseVal = 'w-5 h-5 text-gray-500';
            labelDoc.textContent = 'CNPJ';
            labelNome.textContent = 'Nome Fantasia';
            nomeRequired.classList.add('hidden');
            docInput.placeholder = '00.000.000/0000-00';
            previewBadge.textContent = 'PJ';
            previewBadge.className = 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white';
            previewBadge.style.backgroundColor = '#374151';
            pjOnlyFields.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('hidden');
            });
        } else {
            btnPJ.className = 'tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 border-gray-300 bg-white cursor-pointer transition-all';
            btnPF.className = 'tipo-pessoa-btn flex items-center gap-3 p-3 rounded border-2 border-gray-800 bg-gray-50 cursor-pointer transition-all';
            iconPJ.className = 'w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0';
            iconPF.className = 'w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0';
            svgPJ.className.baseVal = 'w-5 h-5 text-gray-500';
            svgPF.className.baseVal = 'w-5 h-5 text-gray-700';
            labelDoc.textContent = 'CPF';
            labelNome.textContent = 'Nome Completo';
            nomeRequired.classList.remove('hidden');
            docInput.placeholder = '000.000.000-00';
            previewBadge.textContent = 'PF';
            previewBadge.className = 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white';
            previewBadge.style.backgroundColor = '#9ca3af';
            pjOnlyFields.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
        }
    };

    window.toggleEmpresaPropria = function() {
        empresaPropria = !empresaPropria;
        const btn = document.getElementById('btn-empresa-propria');
        const knob = document.getElementById('toggle-knob');
        const input = document.getElementById('is_empresa_propria');
        const preview = document.getElementById('preview-empresa-propria');

        if (empresaPropria) {
            btn.classList.remove('bg-gray-200');
            btn.classList.add('bg-gray-800');
            btn.setAttribute('aria-checked', 'true');
            knob.classList.remove('translate-x-0');
            knob.classList.add('translate-x-5');
            input.value = '1';
            preview.classList.remove('hidden');
        } else {
            btn.classList.remove('bg-gray-800');
            btn.classList.add('bg-gray-200');
            btn.setAttribute('aria-checked', 'false');
            knob.classList.remove('translate-x-5');
            knob.classList.add('translate-x-0');
            input.value = '0';
            preview.classList.add('hidden');
        }
    };

    // ViaCEP integration
    var btnCep = document.getElementById('btn-buscar-cep');
    var cepInput = document.getElementById('cep');
    if (btnCep && cepInput) {
        btnCep.addEventListener('click', buscarCep);
        cepInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); buscarCep(); }
        });
    }

    function buscarCep() {
        var cep = (cepInput.value || '').replace(/\D/g, '');
        var statusEl = document.getElementById('cep-status');
        if (cep.length !== 8) {
            statusEl.textContent = 'CEP deve ter 8 digitos.';
            statusEl.className = 'mt-1 text-xs text-red-600';
            return;
        }
        statusEl.textContent = 'Buscando...';
        statusEl.className = 'mt-1 text-xs text-gray-500';
        btnCep.disabled = true;

        fetch(viaCepBaseUrl + '/' + cep + '/json/')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btnCep.disabled = false;
                if (data.erro) {
                    statusEl.textContent = 'CEP nao encontrado.';
                    statusEl.className = 'mt-1 text-xs text-red-600';
                    return;
                }
                statusEl.textContent = 'Endereco preenchido!';
                statusEl.className = 'mt-1 text-xs text-green-600';
                setTimeout(function() { statusEl.className = 'mt-1 text-xs hidden'; }, 3000);

                if (data.logradouro) document.getElementById('endereco').value = data.logradouro;
                if (data.bairro) document.getElementById('bairro').value = data.bairro;
                if (data.localidade) document.getElementById('municipio').value = data.localidade;
                if (data.uf) {
                    var ufSelect = document.getElementById('uf');
                    for (var i = 0; i < ufSelect.options.length; i++) {
                        if (ufSelect.options[i].value === data.uf) {
                            ufSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
                if (data.ibge) document.getElementById('codigo_municipal').value = data.ibge;

                updatePreviewLocal();
                updatePreviewEndereco();
            })
            .catch(function() {
                btnCep.disabled = false;
                statusEl.textContent = 'Erro ao buscar CEP.';
                statusEl.className = 'mt-1 text-xs text-red-600';
            });
    }

    // Live preview updates
    ['razao_social', 'nome', 'documento', 'email', 'telefone', 'municipio', 'endereco', 'numero', 'bairro', 'situacao_cadastral'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            var val = el.value.trim();
            switch(id) {
                case 'razao_social':
                    document.getElementById('preview-razao').textContent = val || '-';
                    break;
                case 'nome':
                    document.getElementById('preview-nome').textContent = val;
                    if (document.getElementById('tipo_pessoa').value === 'PF') {
                        document.getElementById('preview-razao').textContent = val || '-';
                    }
                    break;
                case 'documento':
                    document.getElementById('preview-doc').textContent = val || '-';
                    break;
                case 'email':
                    document.getElementById('preview-email').textContent = val || '-';
                    break;
                case 'telefone':
                    document.getElementById('preview-tel').textContent = val;
                    break;
                case 'municipio':
                    updatePreviewLocal();
                    break;
                case 'endereco':
                case 'numero':
                case 'bairro':
                    updatePreviewEndereco();
                    break;
                case 'situacao_cadastral':
                    document.getElementById('preview-situacao').textContent = val || '-';
                    break;
            }
        });
    });

    var ufSelect = document.getElementById('uf');
    if (ufSelect) {
        ufSelect.addEventListener('change', updatePreviewLocal);
    }

    var crtSelect = document.getElementById('crt');
    if (crtSelect) {
        crtSelect.addEventListener('change', function() {
            var opt = crtSelect.options[crtSelect.selectedIndex];
            document.getElementById('preview-crt').textContent = opt.value ? opt.textContent.trim() : '-';
        });
    }

    function updatePreviewLocal() {
        var mun = document.getElementById('municipio').value.trim();
        var uf = document.getElementById('uf').value;
        var parts = [mun, uf].filter(Boolean);
        document.getElementById('preview-local').textContent = parts.length ? parts.join(' - ') : '-';
    }

    function updatePreviewEndereco() {
        var end = document.getElementById('endereco').value.trim();
        var num = document.getElementById('numero').value.trim();
        var bairro = document.getElementById('bairro').value.trim();
        var parts = [end, num, bairro].filter(Boolean);
        document.getElementById('preview-endereco').textContent = parts.length ? parts.join(', ') : '-';
    }

    // Parse capital_social from BR format to number
    function parseCapitalSocial() {
        var val = (document.getElementById('capital_social').value || '').trim();
        if (!val) return null;
        // Convert BR format (1.234,56) to float
        val = val.replace(/\./g, '').replace(',', '.');
        var num = parseFloat(val);
        return isNaN(num) ? null : num;
    }

    window.salvarCliente = function() {
        var btn = document.getElementById('btn-salvar');
        var status = document.getElementById('save-status');
        btn.disabled = true;
        status.textContent = 'Salvando...';
        status.classList.remove('hidden', 'text-red-600', 'text-green-600');
        status.classList.add('text-gray-400');

        var tipo = document.getElementById('tipo_pessoa').value;
        var isPJ = tipo === 'PJ';

        var data = {
            tipo_pessoa: tipo,
            documento: document.getElementById('documento').value,
            nome: document.getElementById('nome').value || null,
            razao_social: isPJ ? (document.getElementById('razao_social').value || null) : null,
            nome_fantasia: document.getElementById('nome').value || null,
            inscricao_estadual: isPJ ? (document.getElementById('inscricao_estadual').value || null) : null,
            crt: isPJ ? (document.getElementById('crt').value || null) : null,
            email: document.getElementById('email').value || null,
            telefone: document.getElementById('telefone').value || null,
            uf: document.getElementById('uf').value || null,
            cep: document.getElementById('cep').value || null,
            municipio: document.getElementById('municipio').value || null,
            endereco: document.getElementById('endereco').value || null,
            numero: document.getElementById('numero').value || null,
            complemento: document.getElementById('complemento').value || null,
            bairro: document.getElementById('bairro').value || null,
            is_empresa_propria: empresaPropria,
        };

        // PJ-only Dados RF fields
        if (isPJ) {
            data.situacao_cadastral = document.getElementById('situacao_cadastral').value || null;
            data.regime_tributario = document.getElementById('regime_tributario').value || null;
            data.capital_social = parseCapitalSocial();
            data.natureza_juridica = document.getElementById('natureza_juridica').value || null;
            data.porte = document.getElementById('porte').value || null;
            data.data_inicio_atividade = document.getElementById('data_inicio_atividade').value || null;
            data.cnpj_matriz = document.getElementById('cnpj_matriz').value || null;
            data.cnae_principal = document.getElementById('cnae_principal').value || null;
            data.cnae_principal_descricao = document.getElementById('cnae_principal_descricao').value || null;
            data.suframa = document.getElementById('suframa').value || null;
            data.codigo_municipal = document.getElementById('codigo_municipal').value || null;
        }

        var url = isEditing ? '/app/clientes/' + clienteId : '/app/cliente/novo';
        var method = isEditing ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                status.textContent = res.message;
                status.classList.remove('text-gray-400');
                status.classList.add('text-green-600');
                if (res.redirect && typeof window.navigateTo === 'function') {
                    setTimeout(function() { window.navigateTo(res.redirect); }, 500);
                }
            } else {
                var msg = res.message || 'Erro ao salvar';
                if (res.errors) {
                    var firstError = Object.values(res.errors)[0];
                    if (Array.isArray(firstError)) msg = firstError[0];
                }
                status.textContent = msg;
                status.classList.remove('text-gray-400');
                status.classList.add('text-red-600');
                btn.disabled = false;
            }
        })
        .catch(function() {
            status.textContent = 'Erro de conexao';
            status.classList.remove('text-gray-400');
            status.classList.add('text-red-600');
            btn.disabled = false;
        });
    };
})();
</script>
