/* Frankei Coverage — Sidebar com filtros */
(function () {
  'use strict';

  function pctFromRow(tr) {
    var cells = tr.querySelectorAll('td');
    if (cells.length < 3) return 0;
    var m = (cells[2].textContent || '').match(/([\d.]+)\s*%/);
    return m ? parseFloat(m[1]) : 0;
  }

  function nameFromRow(tr) {
    var td = tr.querySelector('td');
    return td ? (td.textContent || '').trim() : '';
  }

  function levelFromPct(p) {
    if (p >= 80) return 'high';
    if (p >= 50) return 'medium';
    if (p > 0) return 'low';
    return 'none';
  }

  function run() {
    if (document.querySelector('.fk-sidebar')) return;

    // Encontrar tabela de resumo (que tem "Total")
    var summaryTables = document.querySelectorAll('table.table-bordered, table.table');
    var summaryTable = null;
    for (var i = 0; i < summaryTables.length; i++) {
      if (/Total/i.test(summaryTables[i].textContent) && /%/.test(summaryTables[i].textContent)) {
        summaryTable = summaryTables[i];
        break;
      }
    }
    if (!summaryTable) return;

    // Extrair linhas (excepto header e "Total")
    var allRows = summaryTable.querySelectorAll('tbody tr');
    var methods = [];
    allRows.forEach(function (tr) {
      var name = nameFromRow(tr);
      if (!name || /Total/i.test(name)) return;
      methods.push({
        el: tr,
        name: name.replace(/^[🔴🟠🟡🟢⚪\s]+/, '').trim(),
        pct: pctFromRow(tr),
        raw: name,
      });
    });
    if (!methods.length) return;

    // Estatísticas para os filtros
    var total = methods.length;
    var covered = methods.filter(function (m) { return m.pct > 0; }).length;
    var uncovered = total - covered;

    // Construir a sidebar
    var sidebar = document.createElement('aside');
    sidebar.className = 'fk-sidebar';
    sidebar.innerHTML = ''
      + '<h3>Métodos <span style="opacity:.55;font-weight:600">(' + total + ')</span></h3>'
      + '<div class="fk-sidebar-filters">'
      +   '<button type="button" class="fk-sidebar-filter active" data-filter="all">Todos <span class="ct">' + total + '</span></button>'
      +   '<button type="button" class="fk-sidebar-filter" data-filter="uncovered">Não cob <span class="ct">' + uncovered + '</span></button>'
      +   '<button type="button" class="fk-sidebar-filter" data-filter="covered">Cob <span class="ct">' + covered + '</span></button>'
      + '</div>'
      + '<div class="fk-sidebar-list"></div>';

    var list = sidebar.querySelector('.fk-sidebar-list');
    methods.forEach(function (m) {
      var lvl = levelFromPct(m.pct);
      var item = document.createElement('div');
      item.className = 'fk-sidebar-item';
      item.dataset.filter = m.pct > 0 ? 'covered' : 'uncovered';
      item.dataset.pct = m.pct;
      item.innerHTML = ''
        + '<span class="dot ' + lvl + '"></span>'
        + '<span class="name" title="' + m.name.replace(/"/g, '&quot;') + '">' + m.name + '</span>'
        + '<span class="pct">' + m.pct.toFixed(1) + '%</span>';

      item.addEventListener('click', function () {
        // Scroll até à linha na tabela de código
        var targetId = null;
        var abbr = m.el.querySelector('a[href^="#"]');
        if (abbr) targetId = abbr.getAttribute('href').replace('#', '');

        var target = null;
        if (targetId) {
          target = document.getElementById(targetId);
          if (target) target = target.closest('tr');
        }
        // Fallback: procurar no código pela linha com o nome
        if (!target) {
          var codeRows = document.querySelectorAll('table#code tbody tr');
          for (var k = 0; k < codeRows.length; k++) {
            if (codeRows[k].textContent.indexOf(m.name) !== -1) { target = codeRows[k]; break; }
          }
        }
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          target.classList.add('fk-highlight');
          setTimeout(function () { target.classList.remove('fk-highlight'); }, 1300);
        }
        item.classList.add('flash');
        setTimeout(function () { item.classList.remove('flash'); }, 1000);
      });

      list.appendChild(item);
    });

    // Aplicar filtros
    sidebar.querySelectorAll('.fk-sidebar-filter').forEach(function (btn) {
      btn.addEventListener('click', function () {
        sidebar.querySelectorAll('.fk-sidebar-filter').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var f = btn.dataset.filter;
        list.querySelectorAll('.fk-sidebar-item').forEach(function (it) {
          if (f === 'all') { it.classList.remove('hidden'); return; }
          it.classList.toggle('hidden', it.dataset.filter !== f);
        });
      });
    });

  // FK_STATS_GRID: painel de estatisticas
  function buildStats(methods) {
    var summaryTables = document.querySelectorAll('table.table-bordered, table.table');
    var summaryTable = null;
    for (var i = 0; i < summaryTables.length; i++) {
      if (/Total/i.test(summaryTables[i].textContent) && /%/.test(summaryTables[i].textContent)) {
        summaryTable = summaryTables[i];
        break;
      }
    }

    var linesPct = null, linesCov = null, linesTotal = null;
    var funcsPct = null, funcsCov = null, funcsTotal = null;

    if (summaryTable) {
      var txt = summaryTable.textContent || '';
      var mAll = txt.match(/(\d+\.?\d*)\s*\/\s*(\d+\.?\d*)/g) || [];
      var pAll = txt.match(/(\d+\.?\d*)\s*%/g) || [];

      if (mAll[0]) { var p1 = mAll[0].split('/'); linesCov = parseInt(p1[0]); linesTotal = parseInt(p1[1]); }
      if (mAll[1]) { var p2 = mAll[1].split('/'); funcsCov = parseInt(p2[0]); funcsTotal = parseInt(p2[1]); }
      if (pAll[0]) linesPct = parseFloat(pAll[0]);
      if (pAll[1]) funcsPct  = parseFloat(pAll[1]);
    }

    var total = methods.length;
    var covered = methods.filter(function (m) { return m.pct > 0; }).length;
    var uncovered = total - covered;
    var pctCov = total ? Math.round(covered / total * 100) : 0;

    var engine = (window.FK_TEST_ENGINE || window.FK_ENGINE || 'phpunit');
    engine = String(engine);
    engine = engine.charAt(0).toUpperCase() + engine.slice(1);

    var lastModTxt = '\u2014';
    if (document.lastModified) {
      var d = new Date(document.lastModified);
      if (!isNaN(d.getTime())) {
        var diffMin = Math.floor((Date.now() - d.getTime()) / 60000);
        if (diffMin < 1)         lastModTxt = 'agora';
        else if (diffMin < 60)   lastModTxt = 'h\u00e1 ' + diffMin + ' min';
        else if (diffMin < 1440) lastModTxt = 'h\u00e1 ' + Math.floor(diffMin/60) + 'h';
        else                     lastModTxt = d.toLocaleDateString('pt-PT');
      }
    }

    function faixa(p) {
      if (p === null || p === undefined || isNaN(p)) return 'none';
      if (p >= 80) return 'good';
      if (p >= 50) return 'medium';
      if (p > 0)   return 'low';
      return 'none';
    }

    var cards = [
      { icon: '\ud83d\udc18', label: 'Motor',          value: engine, cls: 'engine' },
      { icon: '\ud83c\udfaf', label: 'Cobertura',      value: (linesPct !== null ? linesPct.toFixed(1) + '%' : '\u2014'), cls: faixa(linesPct) },
      { icon: '\ud83d\udcca', label: 'Linhas',         value: (linesCov !== null && linesTotal !== null ? linesCov + ' / ' + linesTotal : '\u2014'), cls: '' },
      { icon: '\ud83e\uddee', label: 'M\u00e9todos',  value: covered + ' / ' + total, cls: faixa(pctCov) },
      { icon: '\u2705', label: 'Cob. m\u00e9todos',    value: pctCov + '%', cls: faixa(pctCov) },
      { icon: '\u26a0\ufe0f', label: 'Falta cobrir',   value: String(uncovered), cls: (uncovered > 0 ? 'low' : 'good') },
      { icon: '\ud83d\udd2c', label: 'Cob. fun\u00e7\u00f5es', value: (funcsPct !== null ? funcsPct.toFixed(1) + '%' : '\u2014'), cls: faixa(funcsPct) },
      { icon: '\u23f1\ufe0f', label: 'Atualizado',     value: lastModTxt, cls: '' }
    ];

    var wrap = document.createElement('div');
    wrap.className = 'fk-stats-grid';

    cards.forEach(function (c) {
      var card = document.createElement('div');
      card.className = 'fk-stat-card' + (c.cls ? ' fk-stat-' + c.cls : '');
      card.innerHTML = ''
        + '<span class="fk-stat-icon">' + c.icon + '</span>'
        + '<span class="fk-stat-body">'
        +   '<span class="fk-stat-label">' + c.label + '</span>'
        +   '<span class="fk-stat-value">' + c.value + '</span>'
        + '</span>';
      wrap.appendChild(card);
    });

    return wrap;
  }

      // Colocar a sidebar antes do container-fluid
    var container = document.querySelector('.container-fluid');
    if (!container) return;

    // Mover breadcrumbs (nav + row) para fora da grid
    var crumbsNav = container.querySelector('nav[aria-label="breadcrumb"]');
    var crumbsRow = crumbsNav ? crumbsNav.closest('.row') : null;
    if (crumbsRow) crumbsRow.parentNode.removeChild(crumbsRow);

    var layout = document.createElement('div');
    layout.className = 'fk-layout';
    container.parentNode.insertBefore(layout, container);
    layout.appendChild(sidebar);
    layout.appendChild(container);


    // FK_STATS_GRID: inserir stats antes do container

    (function(){

      try {

        var statsGrid = buildStats(methods);

        if (statsGrid) layout.insertBefore(statsGrid, container);

        if (!container.textContent.trim() && container.querySelectorAll('table').length === 0) {

          container.style.display = 'none';

        }

      } catch(e) { console.warn('[FK_STATS_GRID] erro:', e); }

    })();

    // Reinserir breadcrumbs full-width, antes da grid
    if (crumbsRow) {
      layout.parentNode.insertBefore(crumbsRow, layout);
    }
  }

  // FK_HDR_ULTRA
  function fkSetupHeader() {
    try {
      var header = document.querySelector('header');
      var nav = document.querySelector('nav[aria-label="breadcrumb"]');
      if (!header || !nav) {
        console.log('[FK_HDR_ULTRA] header=', !!header, 'nav=', !!nav);
        return false;
      }
      if (nav.dataset.fkHdrUltra === '1') {
        console.log('[FK_HDR_ULTRA] ja configurado');
        return true;
      }
      nav.dataset.fkHdrUltra = '1';
      console.log('[FK_HDR_ULTRA] a configurar...');

      // CSS inline defensivo
      nav.style.position = nav.style.position === 'absolute'
        ? 'absolute' : 'relative';
      nav.style.paddingRight = '46px';

      // --- Botao toggle ---
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'fk-hdr-toggle';
      btn.innerHTML = '<span class="fk-hdr-toggle-icon">\u25BE</span>';
      btn.style.cssText = [
        'position:absolute',
        'top:50%',
        'right:6px',
        'transform:translateY(-50%)',
        'z-index:9999',
        'pointer-events:auto',
        'display:inline-flex',
        'align-items:center',
        'justify-content:center',
        'width:28px',
        'height:28px',
        'border:1px solid #e8dcc8',
        'border-radius:6px',
        'background:#fff',
        'color:#c25a1f',
        'cursor:pointer',
        'font-size:13px',
        'font-family:inherit',
        'line-height:1'
      ].join(';');
      nav.appendChild(btn);

      // --- Grip resize ---
      var grip = document.createElement('div');
      grip.className = 'fk-hdr-grip';
      grip.innerHTML = '<span class="fk-hdr-grip-dot"></span>';
      grip.style.cssText = [
        'position:absolute',
        'left:0',
        'right:0',
        'bottom:-3px',
        'height:10px',
        'cursor:ns-resize',
        'z-index:9998',
        'display:flex',
        'align-items:center',
        'justify-content:center'
      ].join(';');
      var dot = grip.querySelector('.fk-hdr-grip-dot');
      if (dot) dot.style.cssText = 'width:42px;height:3px;border-radius:3px;background:#d4b896;display:block;';
      nav.parentNode.appendChild(grip);

      // --- Conteudo a colapsar: .fk-layout ou .fk-content-area ---
      function acharConteudo() {
        return header.querySelector('.fk-layout')
            || header.querySelector('.fk-content-area')
            || document.querySelector('.fk-layout')
            || document.querySelector('.fk-content-area');
      }

      var conteudo = acharConteudo();
      console.log('[FK_HDR_ULTRA] conteudo encontrado?', !!conteudo,
                  conteudo ? conteudo.className : '');

      // --- Estado ---
      var KEY_COL = 'fk:hdr:collapsed';
      var colapsado = false;
      try { colapsado = localStorage.getItem(KEY_COL) === '1'; } catch (_) {}

      function aplicar() {
        var c = acharConteudo();
        if (c) c.style.setProperty('display', colapsado ? 'none' : '', 'important');
        var ico = btn.querySelector('.fk-hdr-toggle-icon');
        if (ico) ico.textContent = colapsado ? '\u25B4' : '\u25BE';
        btn.title = colapsado ? 'Mostrar conteudo' : 'Ocultar conteudo';
        console.log('[FK_HDR_ULTRA] estado:', colapsado ? 'colapsado' : 'expandido', '| alvo:', c ? c.className : 'null');
      }
      aplicar();

      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        colapsado = !colapsado;
        try { localStorage.setItem(KEY_COL, colapsado ? '1' : '0'); } catch (_) {}
        aplicar();
        console.log('[FK_HDR_ULTRA] botao clicado ->', colapsado);
      });

      console.log('[FK_HDR_ULTRA] OK — botao e grip adicionados');
      return true;
    } catch (e) {
      console.error('[FK_HDR_ULTRA] ERRO:', e);
      return false;
    }
  }

  // Retry ate conseguir
  (function retry() {
    var tentativas = 0;
    var max = 20;
    function tentar() {
      tentativas++;
      var ok = fkSetupHeader();
      if (ok) return;
      if (tentativas < max) {
        setTimeout(tentar, 300);
      } else {
        console.warn('[FK_HDR_ULTRA] desisto apos ' + max + ' tentativas');
      }
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', tentar);
    } else {
      tentar();
    }
    window.addEventListener('load', function () { setTimeout(tentar, 200); });
  })();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
