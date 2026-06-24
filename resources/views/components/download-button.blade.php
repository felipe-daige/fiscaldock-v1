@props([
    'path',
    'filename',
    'overlay' => 'download-overlay',
    'extraOnDone' => '',
])

@php
    // Botão de download do design system. Cache-robusto: TODA a lógica (fetch -> blob
    // -> <a download> -> toggle do spinner) vive num onclick inline (server-rendered
    // fresco), sem depender de JS-file cacheado (nginx js=1h + bi.js sem ?v=). Lê os
    // filtros #filtro-cliente / #filtro-periodo e passa cliente_id + meses (datas
    // computadas server-side). Sem aspas duplas no JS (o atributo onclick usa aspas duplas).
    $js = "(function(){"
        . "var ov=document.getElementById('{$overlay}');"
        . "var c=document.getElementById('filtro-cliente');"
        . "var p=document.getElementById('filtro-periodo');"
        . "var u='{$path}?cliente_id='+((c&&c.value)||'')+'&meses='+((p&&p.value)||0);"
        . "if(ov)ov.classList.remove('hidden');"
        . "fetch(u,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){if(!r.ok)throw 0;return r.blob();})"
        . ".then(function(b){"
        .   "var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='{$filename}';"
        .   "document.body.appendChild(a);a.click();"
        .   "setTimeout(function(){URL.revokeObjectURL(a.href);a.remove();},1000);"
        .   "if(ov)ov.classList.add('hidden');{$extraOnDone}"
        . "})"
        . ".catch(function(){if(ov)ov.classList.add('hidden');alert('Falha ao gerar o arquivo. Tente novamente.');});"
        . "})()";
@endphp

<button type="button" onclick="{{ $js }}" {{ $attributes }}>{{ $slot }}</button>
