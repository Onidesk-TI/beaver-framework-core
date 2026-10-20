(function () {
  'use strict';

  var terminal = document.getElementById('terminal');
  if (!terminal) return;

  var LOG = [
    {t:'ok',      m:'<span class="t-hl">beaver-online-store.css</span> · line 44 → <span class="t-hl">.beaver-cart-button:hover</span>'},
    {t:'ok',      m:'cache limpa <span class="t-dim">· 4 ms</span>'},
    {t:'ok',      m:'página recarregada na tab <span class="t-url">beaverphp.com/shop</span>'},
    {t:'ok',      m:'composer autoload otimizado <span class="t-dim">· 61 classes</span>'},
    {t:'ok',      m:'migration <span class="t-hl">2024_09_19_add_slug</span> executada'},
    {t:'ok',      m:'rota <span class="t-hl">/shop/products/{id}</span> em cache'},
    {t:'suggest', m:'<span class="t-suggest-text">sugestão</span> · ativar <span class="t-hl">eager loading</span> em <span class="t-hl">ProductController@index</span>'},
    {t:'suggest', m:'<span class="t-suggest-text">sugestão</span> · extrair <span class="t-hl">BeaverCartService</span> para pacote independente'},
    {t:'warn',    m:'<span class="t-warn-text">aviso</span> · query em <span class="t-hl">ProductRepo</span> demora <span class="t-warn-text">142 ms</span>'},
    {t:'warn',    m:'<span class="t-warn-text">aviso</span> · N+1 detetado em <span class="t-hl">OrderController@show</span>'},
    {t:'error',   m:'<span class="t-error-text">erro</span> · FK <span class="t-hl">cart_id</span> não existe em <span class="t-hl">orders</span>'},
    {t:'run',     m:'a corrigir migration…'},
    {t:'ok',      m:'migration corrigida <span class="t-dim">· 2024_09_19_fix_cart_fk</span>'},
    {t:'error',   m:'<span class="t-error-text">erro</span> · teste <span class="t-hl">CartTest::test_remove_item</span> falhou'},
    {t:'ok',      m:'teste corrigido <span class="t-dim">· 42/42 passed</span>'},
    {t:'run',     m:'bundling assets…'},
    {t:'ok',      m:'bundle reconstruído <span class="t-dim">· 312 KB</span>'},
    {t:'ok',      m:'phpstan <span class="t-dim">· 0 erros</span>'},
    {t:'suggest', m:'<span class="t-suggest-text">sugestão</span> · criar índice <span class="t-hl">idx_products_slug</span> <span class="t-dim">· −63% tempo</span>'},
    {t:'ok',      m:'índice <span class="t-hl">idx_products_slug</span> criado'},
    {t:'ok',      m:'queue worker <span class="t-dim">· 3 jobs processados</span>'},
    {t:'ok',      m:'backup criado <span class="t-dim">· 12.4 MB</span>'},
    {t:'ok',      m:'health check <span class="t-dim">· 200 OK · 34 ms</span>'},
    {t:'ok',      m:'lighthouse <span class="t-hl">98/100</span>'},
    {t:'run',     m:'docker image rebuild…'},
    {t:'ok',      m:'docker image <span class="t-dim">· 82 MB</span>'},
    {t:'ok',      m:'deploy preview → <span class="t-url">beaverphp.com/staging</span>'},
    {t:'ok',      m:'git push origin main <span class="t-dim">· 4 commits</span>'},
    {t:'ok',      m:'SSL válido até <span class="t-hl">2027-03-14</span>'},
    {t:'ok',      m:'beaver build <span class="t-hl">✓</span> <span class="t-dim">· próxima iteração em 5s</span>'}
  ];

  var ICON = {
    ok:      '<span class="t-ok">✔</span>',
    run:     '<span class="t-run">▶</span>',
    suggest: '<span class="t-suggest">◆</span>',
    warn:    '<span class="t-warn">▲</span>',
    error:   '<span class="t-error">✖</span>'
  };

  var clock = 11 * 3600 + 54 * 60 + 2;
  function tick() {
    clock += 1 + Math.floor(Math.random() * 3);
    var h = Math.floor(clock / 3600) % 24;
    var m = Math.floor((clock % 3600) / 60);
    var s = clock % 60;
    var p = function (n) { return String(n).padStart(2, '0'); };
    return '[' + p(h) + ':' + p(m) + ':' + p(s) + ']';
  }

  function cursorLine() {
    var el = document.createElement('div');
    el.className = 'term-line term-cursor-line';
    el.innerHTML = '<span class="t-prompt">▸</span> <span class="term-cursor">▊</span>';
    return el;
  }

  var MAX = 20;

  function pushLine(entry) {
    var oldCursor = terminal.querySelector('.term-cursor-line');
    if (oldCursor) oldCursor.remove();

    var div = document.createElement('div');
    div.className = 'term-line';
    div.innerHTML = '<span class="t-time">' + tick() + '</span> ' + ICON[entry.t] + ' ' + entry.m;
    terminal.appendChild(div);
    terminal.appendChild(cursorLine());

    while (terminal.children.length > MAX) {
      terminal.removeChild(terminal.firstChild);
    }
    terminal.scrollTop = terminal.scrollHeight;
  }

  (function seed() {
    var count = 13;
    for (var k = 0; k < count; k++) {
      var entry = LOG[k % LOG.length];
      var div = document.createElement('div');
      div.className = 'term-line';
      div.style.animation = 'none';
      div.innerHTML = '<span class="t-time">' + tick() + '</span> ' + ICON[entry.t] + ' ' + entry.m;
      terminal.appendChild(div);
    }
    terminal.appendChild(cursorLine());
    terminal.scrollTop = terminal.scrollHeight;
  })();

  var idx = 13;
  setInterval(function () {
    pushLine(LOG[idx % LOG.length]);
    idx++;
  }, 1200);

  var ano = document.getElementById('ano');
  if (ano) ano.textContent = new Date().getFullYear();
})();
