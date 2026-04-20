@php
    $supportCategories = $supportCategories ?? [];
    $prefillContext = $prefillContext ?? [];

    $hasErrorContext = filled($prefillContext['contexto'] ?? null)
        || filled($prefillContext['url_origem'] ?? null)
        || filled($prefillContext['mensagem_erro'] ?? null);
@endphp

<div class="bg-gray-100 min-h-screen" id="suporte-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs text-gray-500">
                    <a href="/app/dashboard" class="text-gray-600 hover:text-gray-900 hover:underline" data-link>Dashboard</a>
                    <span class="mx-1 text-gray-400">/</span>
                    <span>Suporte</span>
                </p>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-1">Suporte</h1>
                <p class="text-xs text-gray-500 mt-1">Abra um atendimento com contexto operacional e registre o que aconteceu.</p>
            </div>
            <a href="https://wa.me/5567999844366"
               target="_blank"
               rel="noreferrer"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-sm font-medium self-start">
                WhatsApp: (67) 99984-4366
            </a>
        </div>

        @if(session('support_success'))
            <div class="bg-white rounded border border-gray-300 p-4 mb-6 border-l-4 border-l-blue-500">
                <p class="text-sm text-gray-700">{{ session('support_success') }}</p>
            </div>
        @endif

        @if($hasErrorContext)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Contexto do Erro</span>
                </div>
                <div class="p-4 space-y-3">
                    <p class="text-sm text-gray-700">Você veio de uma tela com erro. Revise o contexto abaixo e complemente a descrição antes de enviar.</p>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 text-sm">
                        <div class="bg-gray-50 border border-gray-200 rounded p-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Contexto</p>
                            <p class="text-gray-700">{{ $prefillContext['contexto'] ?: 'Nao informado' }}</p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded p-3 lg:col-span-2">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">URL de origem</p>
                            <p class="text-gray-700 break-all">{{ $prefillContext['url_origem'] ?: 'Nao informada' }}</p>
                        </div>
                    </div>

                    @if(!empty($prefillContext['mensagem_erro']))
                        <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-amber-500">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Mensagem recebida</p>
                            <p class="text-sm text-gray-700 mt-2">{{ $prefillContext['mensagem_erro'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('app.suporte.store') }}" class="bg-white rounded border border-gray-300 overflow-hidden">
                    @csrf

                    <input type="hidden" name="contexto" value="{{ $prefillContext['contexto'] }}">
                    <input type="hidden" name="url_origem" value="{{ $prefillContext['url_origem'] }}">
                    <input type="hidden" name="mensagem_erro" value="{{ $prefillContext['mensagem_erro'] }}">

                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Abrir Atendimento</span>
                    </div>

                    <div class="p-4 space-y-4">
                        <div>
                            <label for="categoria" class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Categoria</label>
                            <select id="categoria" name="categoria" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                @foreach($supportCategories as $value => $label)
                                    <option value="{{ $value }}" @selected(($prefillContext['categoria'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="assunto" class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Assunto</label>
                            <input id="assunto" name="assunto" type="text" maxlength="150" value="{{ $prefillContext['assunto'] }}" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('assunto')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mensagem" class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Mensagem</label>
                            <textarea id="mensagem" name="mensagem" rows="10" maxlength="2000" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">{{ $prefillContext['mensagem'] }}</textarea>
                            @error('mensagem')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-[11px] text-gray-500">O atendimento segue gratuitamente para o suporte operacional.</p>
                        <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Canal Direto</span>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-gray-700">Se preferir um contato imediato, use o canal operacional abaixo.</p>
                        <a href="https://wa.me/5567999844366"
                           target="_blank"
                           rel="noreferrer"
                           class="inline-flex mt-3 px-3 py-1.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                           style="background-color: #374151">
                            WhatsApp
                        </a>
                        <p class="text-sm text-gray-700 mt-3">(67) 99984-4366</p>
                        <p class="text-sm text-gray-700">suporte@fiscaldock.com.br</p>
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-blue-500">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Orientação</p>
                    <p class="text-sm text-gray-700 mt-2">Inclua o que tentou fazer, se o problema acontece sempre e qualquer detalhe que ajude a reproduzir o comportamento.</p>
                </div>
            </div>
        </div>
    </div>
</div>
