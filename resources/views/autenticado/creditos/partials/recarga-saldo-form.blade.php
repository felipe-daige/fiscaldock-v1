{{-- Opt-in do auto top-up por saldo baixo. Reutilizado em "sem recarga" e "saldo pausada". --}}
<div class="w-full">
    <h4 class="text-sm font-semibold text-gray-800">Auto top-up por saldo</h4>
    <p class="text-[13px] text-gray-500 mt-1 mb-3">
        Recompra o pacote automaticamente quando seu saldo fica abaixo do limite. Cancele quando quiser.
    </p>
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label for="recarga-saldo-limite" class="block text-[11px] text-gray-500 mb-1">Recarregar quando o saldo (em R$) for menor que</label>
            <input id="recarga-saldo-limite" type="number" min="1" value="10" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white w-44" />
        </div>
        <div>
            <label for="recarga-saldo-pacote" class="block text-[11px] text-gray-500 mb-1">Pacote a recomprar</label>
            <select id="recarga-saldo-pacote" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white">
                @foreach(($pricing['featured_offers'] ?? []) as $pac)
                    <option value="{{ $pac['slug'] }}" data-valor="{{ $pac['preco'] }}">{{ $pac['nome'] }} — R$ {{ number_format($pac['preco'], 0, ',', '.') }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" id="recarga-saldo-ativar" class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-white rounded" style="background-color: #1d4ed8">Ativar auto top-up por saldo</button>
    </div>
</div>
