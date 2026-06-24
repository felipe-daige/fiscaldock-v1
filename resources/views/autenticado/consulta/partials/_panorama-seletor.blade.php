{{-- Seletor "Top N" client-side do panorama fiscal. Espera: $count (total disponível na lista),
     $default (quantas linhas começam visíveis). onchange inline (cache-safe, sem JS externo):
     toggla .hidden nas linhas [data-pf-row] dentro do [data-pf-list] pai conforme o N escolhido. --}}
@if($count > 5)
    <select onchange="(function(s){var b=s.closest('[data-pf-list]');var n=s.value==='all'?1e9:parseInt(s.value,10);b.querySelectorAll('[data-pf-row]').forEach(function(r,i){r.classList.toggle('hidden',i>=n);});})(this)"
            class="text-[10px] text-gray-500 border border-gray-200 rounded px-1 py-0.5 bg-white">
        @foreach([5, 10, 20, 50] as $opt)
            @if($opt < $count)
                <option value="{{ $opt }}" @selected($opt === $default)>Top {{ $opt }}</option>
            @endif
        @endforeach
        <option value="all" @selected($default >= $count)>Todos ({{ $count }})</option>
    </select>
@endif
