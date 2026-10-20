/* ═══════════════════════════════════════════════════════════
   BEAVER EDITOR — versão final
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  function syncOne(root) {
    var id    = root.dataset.editor;
    var body  = root.querySelector('[data-editor-content="' + id + '"]');
    var input = root.querySelector('[data-editor-input="' + id + '"]');
    var count = root.querySelector('[data-editor-count="' + id + '"]');
    if (!body || !input) return;

    var html = body.innerHTML.trim();
    if (html === '<br>' || html === '<div><br></div>' || html === '<p><br></p>') html = '';
    input.value = html;

    if (count) {
      var txt = (body.innerText || body.textContent || '').trim();
      count.textContent = txt.length;
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  function initOne(root) {
    if (root.__beaverReady) return;
    root.__beaverReady = true;

    var id    = root.dataset.editor;
    var body  = root.querySelector('[data-editor-content="' + id + '"]');
    var input = root.querySelector('[data-editor-input="' + id + '"]');
    if (!body || !input) return;

    // Sync imediato
    syncOne(root);

    // Sync em todos os eventos possíveis
    ['input','keyup','keydown','change','blur','paste','cut','focus','mouseup'].forEach(function (evt) {
      body.addEventListener(evt, function () { syncOne(root); });
    });

    // Sync no submit do form (garantia final)
    var form = root.closest('form');
    if (form) {
      form.addEventListener('submit', function () { syncOne(root); }, true);
    }

    // Toolbar
    root.querySelectorAll('[data-cmd]').forEach(function (btn) {
      btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var cmd = btn.dataset.cmd, arg = btn.dataset.arg;
        if (cmd === 'criarLink') {
          var u = prompt('URL:', 'https://'); if (u) { body.focus(); document.execCommand('createLink', false, u); }
        } else if (cmd === 'inserirImagem') {
          var u2 = prompt('Imagem:', 'https://'); if (u2) { body.focus(); document.execCommand('insertImage', false, u2); }
        } else if (cmd === 'codigo-inline') {
          var s = window.getSelection().toString() || 'código';
          body.focus();
          document.execCommand('insertHTML', false, '<code>' + escapeHtml(s) + '</code>');
        } else if (cmd === 'codigo-bloco') {
          var s2 = window.getSelection().toString() || 'código';
          body.focus();
          document.execCommand('insertHTML', false, '<pre><code>' + escapeHtml(s2) + '</code></pre>');
        } else if (cmd === 'preview') {
          var prev = root.querySelector('[data-editor-preview="' + id + '"]');
          if (prev) { prev.hidden = !prev.hidden; if (!prev.hidden) prev.innerHTML = body.innerHTML; }
        } else {
          body.focus();
          try { document.execCommand(cmd, false, arg || null); } catch (er) {}
        }
        syncOne(root);
      });
    });

    console.log('✅ editor inicializado:', id);
  }

  function initAll() {
    var list = document.querySelectorAll('.editor[data-editor]');
    console.log('🔍 editores encontrados:', list.length);
    list.forEach(initOne);
  }

  // Expõe para debug e chamada manual
  window.__beaverInitEditors = initAll;

  // Corre agora ou no DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
