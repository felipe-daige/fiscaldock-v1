<?php

use Illuminate\Support\Facades\Blade;

it('renderiza botao gatilho com label e aria', function () {
    $html = Blade::render('<x-acoes-menu />');

    expect($html)->toContain('data-acoes-trigger');
    expect($html)->toContain('aria-haspopup="menu"');
    expect($html)->toContain('aria-expanded="false"');
    expect($html)->toContain('Ações'); // label padrão
});

it('modo kebab renderiza gatilho de ícone sem texto de label', function () {
    $html = Blade::render('<x-acoes-menu trigger="kebab" />');

    expect($html)->toContain('data-acoes-trigger');
    expect($html)->toContain('aria-haspopup="menu"');
    expect($html)->toContain('aria-label="Ações"'); // acessível mesmo sem texto
    expect($html)->not->toContain('>Ações<'); // sem label textual visível
});

it('aceita label customizado', function () {
    $html = Blade::render('<x-acoes-menu label="Opções" />');

    expect($html)->toContain('Opções');
});

it('renderiza o painel oculto com role menu e o slot', function () {
    $html = Blade::render('<x-acoes-menu><span class="xis">conteudo</span></x-acoes-menu>');

    expect($html)->toContain('data-acoes-panel');
    expect($html)->toContain('role="menu"');
    expect($html)->toContain('hidden'); // painel começa oculto
    expect($html)->toContain('class="xis"');
    expect($html)->toContain('conteudo');
});

it('acoes-item com href vira link', function () {
    $html = Blade::render('<x-acoes-item href="/app/x">Excel</x-acoes-item>');

    expect($html)->toContain('<a ');
    expect($html)->toContain('href="/app/x"');
    expect($html)->toContain('role="menuitem"');
    expect($html)->toContain('Excel');
});

it('acoes-item sem href vira button', function () {
    $html = Blade::render('<x-acoes-item>Excluir</x-acoes-item>');

    expect($html)->toContain('<button ');
    expect($html)->toContain('Excluir');
    expect($html)->not->toContain('<a ');
});

it('acoes-item variant danger marca visualmente', function () {
    $html = Blade::render('<x-acoes-item variant="danger" href="/x">Excluir</x-acoes-item>');

    // cor de perigo aplicada inline (design system: cor por style, não classe Tailwind)
    expect($html)->toContain('#b91c1c');
});

it('acoes-item repassa atributos extras (data-*, target)', function () {
    $html = Blade::render('<x-acoes-item href="/x" target="_blank" data-foo="bar">Ver</x-acoes-item>');

    expect($html)->toContain('target="_blank"');
    expect($html)->toContain('data-foo="bar"');
});

it('o script do menu sai uma unica vez mesmo com varios menus', function () {
    $html = Blade::render('<x-acoes-menu /><x-acoes-menu /><x-acoes-menu />');

    // @once garante 1 bloco de script/style para N menus (sentinela 1×/bloco)
    expect(substr_count($html, 'acoes-menu-init-block'))->toBe(1);
    // mas os 3 gatilhos saem (aria-haspopup só existe no botão, não no script)
    expect(substr_count($html, 'aria-haspopup="menu"'))->toBe(3);
});
