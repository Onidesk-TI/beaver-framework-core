/**
 * Beaver — Header script partilhado
 * Trata: dropdowns (Docs/Produtos/Settings), theme picker, lang picker
 * Funciona em todas as páginas (site, docs, marketplace, loja)
 */
(function() {
  'use strict';

  // ── Dropdowns genéricos (Documentação, Produtos, Settings) ──
  function setupDropdown(wrapId, toggleId) {
    var wrap = document.getElementById(wrapId);
    var btn = document.getElementById(toggleId);
    if (!wrap || !btn) return;
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var open = wrap.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  setupDropdown('dropProd', 'dropToggle');
  setupDropdown('dropParceiro', 'dropParceiroToggle');
  setupDropdown('dropDocs', 'dropDocsToggle');
  setupDropdown('settingsPicker', 'settingsToggle');

  // ── Dropdown Produtos (loja) ──
  var navProdutos = document.getElementById('navProdutos');
  if (navProdutos) {
    var navProdutosBtn = navProdutos.querySelector('.nav-dropdown-toggle');
    if (navProdutosBtn) {
      navProdutosBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var open = navProdutos.classList.toggle('open');
        navProdutosBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
  }

  // ── Settings: theme picker ──
  var settings = document.getElementById('settingsPicker');
  if (settings) {
    var KNOWN = ['amber', 'ocean', 'forest'];

    var currentTheme = function() {
      var t = document.documentElement.getAttribute('data-theme') || 'amber';
      return KNOWN.indexOf(t) >= 0 ? t : 'amber';
    };

    var markTheme = function(t) {
      Array.prototype.forEach.call(
        settings.querySelectorAll('button[data-theme-value]'),
        function(b) { b.classList.toggle('active', b.dataset.themeValue === t); }
      );
    };

    var applyTheme = function(t) {
      if (KNOWN.indexOf(t) < 0) t = 'amber';
      document.documentElement.setAttribute('data-theme', t);
      try { localStorage.setItem('beaver-theme', t); } catch (e) {}
      markTheme(t);
    };

    markTheme(currentTheme());

    settings.querySelectorAll('button[data-theme-value]').forEach(function(b) {
      b.addEventListener('click', function() {
        applyTheme(b.dataset.themeValue);
      });
    });
  }

  // ── Theme picker (versão antiga da loja, id="themePicker") ──
  var tp = document.getElementById('themePicker');
  var tm = document.getElementById('themeMenu');
  if (tp && tm) {
    var KNOWN2 = ['amber', 'ocean', 'forest'];

    var mark2 = function(t) {
      Array.prototype.forEach.call(
        tm.querySelectorAll('button[data-theme-value]'),
        function(b) { b.classList.toggle('active', b.dataset.themeValue === t); }
      );
    };

    var apply2 = function(t) {
      if (KNOWN2.indexOf(t) < 0) t = 'amber';
      document.documentElement.setAttribute('data-theme', t);
      try { localStorage.setItem('beaver-theme', t); } catch (e) {}
      mark2(t);
    };

    var cur2 = document.documentElement.getAttribute('data-theme') || 'amber';
    mark2(cur2);

    tm.addEventListener('click', function(e) {
      var b = e.target.closest('button[data-theme-value]');
      if (!b) return;
      apply2(b.dataset.themeValue);
      tp.classList.remove('open');
    });
  }

  // ── Language picker (versão antiga da loja, id="langPicker") ──
  var lp = document.getElementById('langPicker');
  var lt = document.getElementById('langToggle');
  if (lp && lt) {
    lt.addEventListener('click', function(e) {
      e.stopPropagation();
      var open = lp.classList.toggle('open');
      lt.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // ── Fechar tudo ao clicar fora ──
  document.addEventListener('click', function(e) {
    [
      'dropProd', 'dropDocs', 'dropParceiro', 'settingsPicker',
      'navProdutos', 'themePicker', 'langPicker'
    ].forEach(function(id) {
      var w = document.getElementById(id);
      if (w && !w.contains(e.target)) {
        w.classList.remove('open');
        var t = w.querySelector('.nav-link, .nav-dropdown-toggle, .theme-btn, .lang-btn');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
    });
  });

  // ── Fechar tudo com Escape ──
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      [
        'dropProd', 'dropDocs', 'settingsPicker',
        'navProdutos', 'themePicker', 'langPicker'
      ].forEach(function(id) {
        var w = document.getElementById(id);
        if (w) w.classList.remove('open');
      });
    }
  });

})();


/* ═══════════════════════════════════════════════════════
   DROP_ITEM_CLOSE_FIX — fechar dropdown ao clicar num item
   Resolve o caso: <a href="#sqlwizard" class="drop-item">
   (o link está DENTRO de dropProd, por isso o handler
   "fechar fora" do header.js original não o apanha)
   ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    function closeAll() {
        ['dropProd', 'dropDocs', 'settingsPicker', 'navProdutos', 'themePicker', 'langPicker'].forEach(function (id) {
            var w = document.getElementById(id);
            if (w) {
                w.classList.remove('open');
                var t = w.querySelector('.nav-link, .nav-dropdown-toggle, .theme-btn, .lang-btn');
                if (t) t.setAttribute('aria-expanded', 'false');
            }
        });
        // Também fechar qualquer .has-dropdown aberto
        document.querySelectorAll('.has-dropdown.open').forEach(function (el) {
            el.classList.remove('open');
        });
    }

    function initDropItemClose() {
        // Apanhar cliques em itens (capture = true → corre antes de outros handlers)
        document.querySelectorAll('.has-dropdown .drop-item, .has-dropdown .dropdown a, .dropdown .drop-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                // Fechar IMEDIATAMENTE. Não fazer preventDefault (deixar o link navegar)
                closeAll();
                // Se for uma âncora (#sqlwizard, #frankei), fazer scroll suave
                var href = item.getAttribute('href') || '';
                if (href.startsWith('#')) {
                    var target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }, true); // ← capture
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropItemClose);
    } else {
        initDropItemClose();
    }
})();
