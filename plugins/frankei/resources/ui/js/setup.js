/* ============================================================
   Frankei · Setup — validação em tempo real do path
   ============================================================ */
(function(){
  'use strict';

  var input   = document.getElementById('fkPath');
  var status  = document.getElementById('fkStatus');
  var checks  = document.getElementById('fkChecks');
  var submit  = document.getElementById('fkSubmitBtn');
  var form    = document.getElementById('fkFormPath');

  if (!input || !status || !checks || !submit) return;

  var debounce = null;
  var lastPath = '';

  function setStatus(tipo, icone){
    status.className = 'fk-input-status ' + (tipo || '');
    status.textContent = icone || '';
  }

  function setInputState(classe){
    input.classList.remove('fk-input-ok','fk-input-err');
    if (classe) input.classList.add(classe);
  }

  function limparChecks(){
    checks.innerHTML = '';
  }

  function renderCheck(ok, texto){
    var div = document.createElement('div');
    div.className = 'fk-check';
    var ico = document.createElement('span');
    ico.className = 'fk-check-ico ' + (ok === true ? 'ok' : ok === false ? 'err' : 'muted');
    ico.textContent = ok === true ? '✓' : ok === false ? '✗' : '·';
    var span = document.createElement('span');
    span.textContent = texto;
    div.appendChild(ico);
    div.appendChild(span);
    return div;
  }

  function renderStats(stats){
    if (!stats) return;
    var wrap = document.createElement('div');
    wrap.className = 'fk-checks-stats';

    var chips = [];
    if (stats.php_files > 0)   chips.push(stats.php_files + ' ficheiros PHP');
    if (stats.controllers > 0) chips.push(stats.controllers + ' controllers');
    if (stats.models > 0)      chips.push(stats.models + ' models');
    if (stats.views > 0)       chips.push(stats.views + ' views');

    chips.forEach(function(t){
      var c = document.createElement('span');
      c.className = 'fk-chip';
      c.textContent = t;
      wrap.appendChild(c);
    });

    checks.appendChild(wrap);
  }

  function validar(path){
    if (!path || path.length < 2){
      limparChecks();
      setStatus('', '');
      setInputState('');
      submit.disabled = true;
      return;
    }

    setStatus('loading', '⏳');
    submit.disabled = true;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/frankei/validate-path', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;

      limparChecks();

      if (xhr.status !== 200){
        setStatus('err', '✗');
        setInputState('fk-input-err');
        checks.appendChild(renderCheck(false, 'Erro ao validar (HTTP ' + xhr.status + ')'));
        submit.disabled = true;
        return;
      }

      var r;
      try { r = JSON.parse(xhr.responseText); }
      catch(e) {
        setStatus('err', '✗');
        checks.appendChild(renderCheck(false, 'Resposta inválida do servidor.'));
        return;
      }

      if (r.ok){
        setStatus('ok', '✓');
        setInputState('fk-input-ok');
        checks.appendChild(renderCheck(true, r.framework === 'beaver' ? 'Projeto Beaver detetado' : 'Projeto PHP detetado'));
        renderStats(r.stats);
        submit.disabled = false;
      } else {
        setStatus('err', '✗');
        setInputState('fk-input-err');
        checks.appendChild(renderCheck(false, r.motivo || 'Caminho inválido.'));

        if (r.checks){
          Object.keys(r.checks).forEach(function(k){
            var labels = {
              composer:    'composer.json',
              beaver_file: 'ficheiro beaver',
              app_dir:     'app/',
              src_dir:     'src/',
              public_dir:  'public/',
              routes_dir:  'routes/',
              index_php:   'index.php',
              public_php:  'public/index.php'
            };
            checks.appendChild(renderCheck(r.checks[k], labels[k] || k));
          });
        }
        submit.disabled = true;
      }
    };

    xhr.send(JSON.stringify({ path: path }));
  }

  input.addEventListener('input', function(){
    var v = input.value.trim();
    if (v === lastPath) return;
    lastPath = v;

    clearTimeout(debounce);
    debounce = setTimeout(function(){ validar(v); }, 320);
  });

  // Se já vem com valor (erro no POST), validar logo
  if (input.value.trim()){
    setTimeout(function(){ validar(input.value.trim()); }, 100);
  }

  form.addEventListener('submit', function(e){
    if (submit.disabled){
      e.preventDefault();
      return;
    }
    submit.disabled = true;
    submit.textContent = 'A validar…';
  });
})();
