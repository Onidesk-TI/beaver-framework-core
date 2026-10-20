/* ═══════════════════════════════════════════════════════════
   FORUM — responder / cancelar
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  function init() {
    var banner   = document.getElementById('reply-banner');
    var bannerNm = document.getElementById('reply-banner-nome');
    var parentEl = document.getElementById('parent-id');
    var cancelar = document.getElementById('cancelar-reply');
    var formSec  = document.getElementById('responder');

    if (!banner || !parentEl) return;

    // Clique em "↩ Responder"
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-reply-to]');
      if (!btn) return;
      e.preventDefault();

      var id = btn.getAttribute('data-reply-to');
      if (!id) return;

      // Nome do parent (autor a quem se responde)
      var nome = btn.getAttribute('data-parent-nome') || null;

      // Fallback: nome do autor da própria reply
      if (!nome) {
        var reply = document.getElementById('r' + id);
        if (reply) {
          var elNome = reply.querySelector('.author-name');
          if (elNome) nome = elNome.textContent.replace(/\s+/g, ' ').trim();
        }
      }
      if (!nome) nome = 'esta resposta';

      bannerNm.textContent = nome;
      banner.hidden = false;
      parentEl.value = id;

      if (formSec) formSec.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // foco no editor rico OU textarea
      setTimeout(function () {
        var rico = document.querySelector('.editor-body[contenteditable]');
        var ta   = document.getElementById('editor-resposta');
        if (rico) rico.focus();
        else if (ta) ta.focus();
      }, 250);
    });

    // Cancelar
    if (cancelar) {
      cancelar.addEventListener('click', function (e) {
        e.preventDefault();
        banner.hidden = true;
        parentEl.value = '';
        bannerNm.textContent = '';
      });
    }

    // Clique em "✎ Editar"
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-edit-reply]');
      if (!btn) return;
      e.preventDefault();
      var id = btn.getAttribute('data-edit-reply');
      if (!id) return;
      window.location.href = '/forum/reply/' + id + '/editar';
    });

    // ESC cancela
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !banner.hidden) {
        banner.hidden = true;
        parentEl.value = '';
        bannerNm.textContent = '';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

    // ═══════ Votação (thread + reply) ═══════
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.voto-btn[data-voto]');
      if (!btn || btn.disabled) return;
      e.preventDefault();

      var container = btn.closest('[data-alvo][data-id]');
      if (!container) return;

      var alvo   = container.getAttribute('data-alvo');
      var alvoId = container.getAttribute('data-id');
      var valor  = parseInt(btn.getAttribute('data-voto'), 10);
      if (!alvo || !alvoId || isNaN(valor)) return;

      // Se já está ativo, remove (toggle → valor 0)
      if (btn.classList.contains('active')) valor = 0;

      var csrf = (window.FORUM && window.FORUM.csrf) ? window.FORUM.csrf : '';

      var fd = new FormData();
      fd.append('_csrf',   csrf);
      fd.append('alvo',    alvo);
      fd.append('alvo_id', alvoId);
      fd.append('valor',   valor);

      container.querySelectorAll('.voto-btn').forEach(function(b){ b.disabled = true; });

      fetch('/forum/votar', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(r){ return r.json(); })
      .then(function(resp){
        if (!resp.ok) {
          console.warn('Voto falhou:', resp.erro);
          if (resp.erro) alert(resp.erro);
          return;
        }
        var total = container.querySelector('.voto-total');
        if (total) total.textContent = resp.total;

        container.querySelectorAll('.voto-btn').forEach(function(b){
          b.classList.remove('active');
          var v = parseInt(b.getAttribute('data-voto'), 10);
          if (v === resp.votoAtual) b.classList.add('active');
        });
      })
      .catch(function(err){ console.error('Erro voto:', err); })
      .finally(function(){
        container.querySelectorAll('.voto-btn').forEach(function(b){ b.disabled = false; });
      });
    });

})();
