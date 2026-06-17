@php
    $fmtVal = function ($tipo, $v) {
        if ($v === null) return '—';
        return $tipo === 'float' ? number_format((float) $v, 2, ',', '.') : number_format((int) $v, 0, ',', '.');
    };
@endphp
<div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Admin — Parâmetros comerciais</h1>
            <p class="text-xs text-gray-500 mt-0.5">Override dos números globais do catálogo. Sem override, vale o padrão do sistema. Cliente nunca vê esta tela.</p>
        </div>

        @include('autenticado.admin.partials.nav', ['tab' => 'comercial'])

        @if(session('status'))
            <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 p-3 text-sm text-gray-700" style="border-left-color: #047857">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 p-3 text-sm text-gray-700" style="border-left-color: #dc2626">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="space-y-4">
            @foreach($parametros as $chave => $p)
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">{{ $p['rotulo'] }}</h2>
                            @if(!empty($p['dica']))
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $p['dica'] }}</p>
                            @endif
                        </div>
                        @if($p['override'] !== null)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b45309">Customizado</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #6b7280">Padrão</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-3 mb-3 text-sm">
                            <div class="bg-gray-50 rounded p-2">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Padrão do sistema</p>
                                <p class="font-bold text-gray-900">{{ $fmtVal($p['tipo'], $p['default']) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded p-2">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">Valor efetivo (em uso)</p>
                                <p class="font-bold text-gray-900">{{ $fmtVal($p['tipo'], $p['efetivo']) }}</p>
                            </div>
                        </div>
                        <div class="flex items-end gap-2">
                            <form method="POST" action="{{ route('app.admin.comercial.update', $chave) }}" class="flex items-end gap-2 flex-1">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-[11px] text-gray-500 mb-1">Novo valor</label>
                                    <input type="number" step="{{ $p['tipo'] === 'float' ? '0.01' : '1' }}" min="0" name="valor"
                                           value="{{ $p['efetivo'] }}"
                                           class="w-full text-[13px] py-2.5 px-3 border border-gray-300 rounded">
                                </div>
                                <button type="submit" class="px-4 py-2.5 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #0b1f3a">Salvar</button>
                            </form>
                            @if($p['override'] !== null)
                                <form method="POST" action="{{ route('app.admin.comercial.reset', $chave) }}"
                                      onsubmit="return confirm('Voltar este parâmetro ao padrão do sistema?');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2.5 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #374151">Resetar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
