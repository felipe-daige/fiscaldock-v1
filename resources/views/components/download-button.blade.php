@props([
    'path',
    'filename',
    'overlay' => 'download-overlay',
    'extraOnDone' => '',
    'query' => '',
])

@php
    // Botão de download do design system. Cache-robusto (onclick inline, sem JS-file).
    // Download NATIVO via <iframe> (cookies de sessão vão automático — fetch+blob dava
    // "Falha" no browser do usuário apesar do 200). Spinner mostrado até o servidor
    // devolver o arquivo: o controller seta cookie `bi_download=<token>` na resposta e
    // este JS faz poll do cookie pra esconder o overlay (fallback: timeout 40s).
    // Lê #filtro-cliente / #filtro-periodo (cliente_id + meses, datas server-side).
    // Sem aspas duplas no JS (o atributo onclick já usa aspas duplas).
    // Detecção por PRESENÇA do cookie `bi_download` (não pelo valor): o Laravel
    // criptografa o valor (EncryptCookies), então o JS não consegue ler o token —
    // mas o NOME do cookie não é criptografado. Limpa antes de iniciar e faz poll
    // até o cookie reaparecer (= a resposta do download chegou).
    // `query` opcional injeta params estáticos (ex.: formato=csv) p/ páginas sem os
    // filtros do BI. cliente_id/meses só entram na URL se os selects existirem na
    // página — assim o mesmo componente serve BI (com filtros) e lote (com formato).
    $tokExpr = "'d'+Date.now()+Math.floor(Math.random()*1e6)";
    $js = "(function(){"
        . "var ov=document.getElementById('{$overlay}');"
        . "var c=document.getElementById('filtro-cliente');"
        . "var p=document.getElementById('filtro-periodo');"
        . "var tok={$tokExpr};"
        . "var qs=[];"
        . ($query !== '' ? "qs.push('{$query}');" : "")
        . "if(c)qs.push('cliente_id='+(c.value||''));"
        . "if(p)qs.push('meses='+(p.value||0));"
        . "qs.push('download_token='+tok);"
        . "var u='{$path}?'+qs.join('&');"
        . "document.cookie='bi_download=; path=/; max-age=0';"
        . "if(ov)ov.classList.remove('hidden');"
        . "var f=document.createElement('iframe');f.style.display='none';f.src=u;document.body.appendChild(f);"
        . "var n=0;var t=setInterval(function(){n++;"
        .   "if(document.cookie.indexOf('bi_download=')>-1){"
        .     "clearInterval(t);document.cookie='bi_download=; path=/; max-age=0';"
        .     "if(ov)ov.classList.add('hidden');{$extraOnDone}setTimeout(function(){f.remove();},60000);"
        .   "}else if(n>160){"
        .     "clearInterval(t);if(ov)ov.classList.add('hidden');setTimeout(function(){f.remove();},60000);"
        .   "}"
        . "},250);"
        . "})()";
@endphp

<button type="button" onclick="{{ $js }}" {{ $attributes }}>{{ $slot }}</button>
