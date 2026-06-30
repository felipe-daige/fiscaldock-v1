<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Admin — Pendências</h1>
            <p class="text-xs text-gray-500 mt-0.5">Lembretes e notas operacionais do time FiscalDock. Compartilhado entre admins.</p>
        </div>

        @include('autenticado.admin.partials.nav', ['tab' => 'pendencias'])

        @if(session('ok'))
            <div class="mb-4 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-3 py-2">{{ session('ok') }}</div>
        @endif

        {{-- Form de criação --}}
        <form method="POST" action="{{ route('app.admin.pendencias.store') }}" class="bg-white border border-gray-300 rounded p-4 mb-6 space-y-3">
            @csrf
            <input name="titulo" required maxlength="255" placeholder="Título da pendência"
                   value="{{ old('titulo') }}" class="w-full border-gray-300 rounded text-sm" />
            @error('titulo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <textarea name="nota" rows="2" placeholder="Nota / contexto (opcional)"
                      class="w-full border-gray-300 rounded text-sm">{{ old('nota') }}</textarea>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-500">Lembrar em
                    <input type="date" name="lembrar_em" value="{{ old('lembrar_em') }}" class="border-gray-300 rounded text-sm ml-1" />
                </label>
                <button class="ml-auto bg-gray-900 text-white text-sm px-4 py-1.5 rounded hover:bg-gray-700">Adicionar</button>
            </div>
        </form>

        {{-- Abertas --}}
        <div class="space-y-2">
            @forelse($abertas as $p)
                <div class="bg-white border @class(['border-red-300 bg-red-50' => $p->esta_vencida, 'border-gray-300' => ! $p->esta_vencida]) rounded p-3 flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $p->titulo }}</p>
                        @if($p->nota)
                            <p class="text-xs text-gray-600 mt-0.5 whitespace-pre-line">{{ $p->nota }}</p>
                        @endif
                        <p class="text-[11px] text-gray-400 mt-1">
                            @if($p->lembrar_em)
                                <span class="@class(['text-red-600 font-semibold' => $p->esta_vencida])">{{ $p->esta_vencida ? 'vencida em' : 'lembrar em' }} {{ $p->lembrar_em->format('d/m/Y') }}</span>
                                ·
                            @endif
                            criada por {{ $p->criadoPor?->name ?? '—' }} em {{ $p->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('app.admin.pendencias.resolver', $p) }}">
                        @csrf
                        <button class="text-xs text-emerald-700 hover:underline whitespace-nowrap">resolver</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-400">Nenhuma pendência aberta. 🎉</p>
            @endforelse
        </div>

        {{-- Resolvidas (colapsadas) --}}
        @if($resolvidas->isNotEmpty())
            <details class="mt-6 text-sm">
                <summary class="cursor-pointer text-gray-500">Resolvidas ({{ $resolvidas->count() }})</summary>
                <div class="mt-2 space-y-1">
                    @foreach($resolvidas as $p)
                        <div class="flex items-center gap-2 text-xs text-gray-500 border-b border-gray-200 py-1">
                            <span class="line-through flex-1 min-w-0 truncate">{{ $p->titulo }}</span>
                            <span class="whitespace-nowrap">{{ $p->resolvidoPor?->name ?? '—' }} · {{ $p->resolvido_em?->format('d/m/Y') }}</span>
                            <form method="POST" action="{{ route('app.admin.pendencias.reabrir', $p) }}">@csrf<button class="text-gray-500 hover:underline">reabrir</button></form>
                            <form method="POST" action="{{ route('app.admin.pendencias.destroy', $p) }}" onsubmit="return confirm('Excluir esta pendência?')">@csrf @method('DELETE')<button class="text-red-500 hover:underline">excluir</button></form>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</div>
