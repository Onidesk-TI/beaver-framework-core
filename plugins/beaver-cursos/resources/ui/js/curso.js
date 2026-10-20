(function () {
  'use strict';

  var root = document.documentElement;
  var stored = localStorage.getItem('beaver-cursos-theme');
  if (stored === 'dark' || stored === 'light') root.setAttribute('data-theme', stored);
  else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
    root.setAttribute('data-theme', 'dark');

  document.querySelectorAll('#theme-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('beaver-cursos-theme', next);
    });
  });

  var cfg = window.CURSOS_CONFIG || {};

  // LOGIN
  var loginForm = document.getElementById('formLogin');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch('/cursos/login', {
        method: 'POST',
        body: new URLSearchParams(new FormData(loginForm)),
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.ok) location.reload();
          else {
            var err = document.getElementById('loginError');
            err.textContent = d.erro || 'Erro ao entrar.';
            err.style.display = 'block';
          }
        })
        .catch(function () {
          var err = document.getElementById('loginError');
          err.textContent = 'Erro de rede.';
          err.style.display = 'block';
        });
    });
  }

  // INICIAR
  var iniciarForm = document.getElementById('formIniciar');
  if (iniciarForm) {
    iniciarForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = iniciarForm.querySelector('button');
      btn.disabled = true;
      btn.textContent = 'A preparar…';
      fetch('/cursos/iniciar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(iniciarForm)),
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.ok && d.redirect) location.href = d.redirect;
          else {
            btn.disabled = false;
            btn.textContent = 'Iniciar teste';
            alert(d.erro || 'Erro ao iniciar.');
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.textContent = 'Iniciar teste';
          alert('Erro de rede.');
        });
    });
  }

  // TIMER
  var timerEl = document.getElementById('timer');
  if (timerEl) {
    var remain = parseInt(timerEl.dataset.remain, 10);
    var timerText = document.getElementById('timerText');
    function pad(n) { return String(n).padStart(2, '0'); }
    function tick() {
      if (remain <= 0) {
        timerText.textContent = '00:00';
        finalizar();
        return;
      }
      remain--;
      var m = Math.floor(remain / 60), s = remain % 60;
      timerText.textContent = pad(m) + ':' + pad(s);
      timerEl.classList.remove('warn', 'danger');
      if (remain <= 60)       timerEl.classList.add('danger');
      else if (remain <= 300) timerEl.classList.add('warn');
    }
    tick();
    setInterval(tick, 1000);
  }

  // NAVEGAÇÃO
  var questoes = Array.from(document.querySelectorAll('.questao'));
  var btnPrev = document.getElementById('btnPrev');
  var btnNext = document.getElementById('btnNext');
  var progressFill = document.getElementById('progressFill');
  var progressText = document.getElementById('progressText');
  var overlay = document.getElementById('overlay');
  var current = 0;

  function updateUI() {
    questoes.forEach(function (q, i) { q.hidden = i !== current; });
    if (btnPrev) btnPrev.disabled = current === 0;
    if (btnNext) btnNext.textContent = current === questoes.length - 1 ? 'Rever respostas' : 'Seguinte →';
    if (progressFill) progressFill.style.width = (((current + 1) / questoes.length) * 100) + '%';
    if (progressText) progressText.textContent = (current + 1) + ' / ' + questoes.length;
  }

  if (questoes.length) updateUI();

  if (btnPrev) btnPrev.addEventListener('click', function () {
    if (current > 0) { current--; updateUI(); }
  });

  if (btnNext) btnNext.addEventListener('click', function () {
    if (current < questoes.length - 1) {
      current++;
      updateUI();
    } else if (overlay) {
      overlay.hidden = false;
    }
  });

  document.querySelectorAll('#navList button').forEach(function (b) {
    b.addEventListener('click', function () {
      current = parseInt(b.dataset.goto, 10);
      updateUI();
    });
  });

  // GUARDAR RESPOSTAS
  function guardarResposta(artigo) {
    var qid  = artigo.dataset.id;
    var tipo = artigo.dataset.tipo;
    var val  = '';
    if (tipo === 'escolha') {
      var checked = artigo.querySelector('input[type=radio]:checked');
      if (!checked) return;
      val = checked.value;
    } else {
      var ta = artigo.querySelector('textarea');
      val = ta ? ta.value : '';
    }
    fetch('/cursos/resposta', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ questao_id: parseInt(qid, 10), resposta: val }),
    }).then(function (r) {
      if (!r.ok) throw 0;
      var btn = document.querySelector('#navList button[data-goto="' + artigo.dataset.index + '"]');
      if (btn) btn.classList.add('done');
    }).catch(function () {});
  }

  questoes.forEach(function (artigo) {
    artigo.addEventListener('change', function () { guardarResposta(artigo); });
    var ta = artigo.querySelector('textarea');
    if (ta) {
      var timeout;
      ta.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(function () { guardarResposta(artigo); }, 700);
      });
    }
  });

  // FINALIZAR
  function finalizar() {
    fetch('/cursos/finalizar', { method: 'POST' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok && d.redirect) location.href = d.redirect;
        else location.href = '/cursos/' + (cfg.cursoSlug || '');
      })
      .catch(function () {
        location.href = '/cursos/' + (cfg.cursoSlug || '');
      });
  }

  var btnFinish = document.getElementById('btnFinish');
  var btnCancel = document.getElementById('btnCancel');
  var btnConfirm = document.getElementById('btnConfirm');
  if (btnFinish && overlay)  btnFinish.addEventListener('click',  function () { overlay.hidden = false; });
  if (btnCancel && overlay)  btnCancel.addEventListener('click',  function () { overlay.hidden = true; });
  if (btnConfirm)            btnConfirm.addEventListener('click', finalizar);

  // ANTI-CÓPIA
  if (document.body.classList.contains('cursos-teste')) {
    document.addEventListener('copy',  function (e) { e.preventDefault(); });
    document.addEventListener('cut',   function (e) { e.preventDefault(); });
    document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    document.addEventListener('selectstart', function (e) {
      var t = e.target;
      if (t.tagName !== 'TEXTAREA' && t.tagName !== 'INPUT') e.preventDefault();
    });
    document.addEventListener('keydown', function (e) {
      var k = (e.key || '').toLowerCase();
      if ((e.ctrlKey || e.metaKey) && ['c','x','u','s','p'].indexOf(k) !== -1) e.preventDefault();
      if (e.key === 'F12') e.preventDefault();
    });
    window.addEventListener('beforeunload', function (e) {
      e.preventDefault();
      e.returnValue = '';
    });
  }
})();

// Se a imagem de fundo falhar, remove o style → volta ao gradiente
document.querySelectorAll('.cursos-card-thumb[data-has-image]').forEach(el => {
  const url = (el.style.backgroundImage.match(/url\(['"]?(.*?)['"]?\)/) || [])[1];
  if (!url) return;
  const img = new Image();
  img.onerror = () => {
    el.style.backgroundImage = '';
    el.removeAttribute('data-has-image');
  };
  img.src = url;
});
