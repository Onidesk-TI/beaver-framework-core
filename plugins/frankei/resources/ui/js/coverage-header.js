/* ═══════════════════════════════════════════════════════════
   Frankei Coverage — Header com donuts (Lines/Functions/Classes)
   Lê os valores da tabela de resumo original e substitui por donuts.
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  function parsePct(text) {
    var m = String(text || '').match(/([\d.]+)\s*%/);
    return m ? parseFloat(m[1]) : 0;
  }

  function parseCount(text) {
    var m = String(text || '').match(/(\d+)\s*\/\s*(\d+)/);
    return m ? { part: parseInt(m[1], 10), total: parseInt(m[2], 10) } : { part: 0, total: 0 };
  }

  function levelFromPct(pct) {
    if (pct >= 80) return 'high';
    if (pct >= 50) return 'medium';
    if (pct > 0)   return 'low';
    return 'none';
  }

  function makeDonut(pct) {
    var r = 38;
    var c = 2 * Math.PI * r;
    var dash = (pct / 100) * c;
    return ''
      + '<svg viewBox="0 0 92 92" aria-hidden="true">'
      + '  <circle class="track" cx="46" cy="46" r="' + r + '"></circle>'
      + '  <circle class="fill" cx="46" cy="46" r="' + r + '" '
      + '          stroke-dasharray="' + dash + ' ' + (c - dash) + '"></circle>'
      + '</svg>'
      + '<span class="pct">' + pct.toFixed(2) + '%</span>';
  }

  function buildCard(label, pct, count) {
    var lvl = levelFromPct(pct);
    return ''
      + '<div class="fk-donut-card level-' + lvl + '">'
      + '  <div class="fk-donut-chart">' + makeDonut(pct) + '</div>'
      + '  <div class="fk-donut-meta">'
      + '    <span class="fk-donut-label">' + label + '</span>'
      + '    <span class="fk-donut-value">' + count.part + ' / ' + count.total + '</span>'
      + '    <span class="fk-donut-sub">' + (count.total - count.part) + ' por cobrir</span>'
      + '  </div>'
      + '</div>';
  }

  function run() {
    // Já foi aplicado?
    if (document.querySelector('.fk-donut-header')) return;

    // Encontrar a tabela com "Total"
    var tables = document.querySelectorAll('table.table-bordered, table.table');
    var totalRow = null;
    var container = null;

    for (var i = 0; i < tables.length; i++) {
      var row = tables[i].querySelector('tbody tr, tr');
      // procura linha com "Total"
      var rows = tables[i].querySelectorAll('tr');
      for (var j = 0; j < rows.length; j++) {
        var txt = rows[j].textContent || '';
        if (/Total/i.test(txt) && /%/.test(txt)) {
          totalRow = rows[j];
          container = tables[i].closest('.table-responsive') || tables[i].parentElement;
          break;
        }
      }
      if (totalRow) break;
    }

    if (!totalRow) return;

    // Extrair células — esperado: [0]=nome, [1]=bar, [2]=%, [3]=count,
    //                             [4]=bar, [5]=%, [6]=count,
    //                             [7]=CRAP, [8]=bar, [9]=%, [10]=count
    var cells = totalRow.querySelectorAll('td');
    if (cells.length < 11) return;

    var linesPct   = parsePct(cells[2].textContent);
    var linesCount = parseCount(cells[3].textContent);

    var funcPct    = parsePct(cells[5].textContent);
    var funcCount  = parseCount(cells[6].textContent);

    var classPct   = parsePct(cells[9].textContent);
    var classCount = parseCount(cells[10].textContent);

    // Construir o header
    var header = document.createElement('div');
    header.className = 'fk-donut-header';
    header.innerHTML = ''
      + buildCard('Lines', linesPct, linesCount)
      + buildCard('Functions & Methods', funcPct, funcCount)
      + buildCard('Classes & Traits', classPct, classCount);

    // Inserir antes do container da tabela
    if (container && container.parentNode) {
      container.parentNode.insertBefore(header, container);
    }

    // Ativar classe que esconde a tabela original
    document.body.classList.add('fk-donuts-active');

    // Animação: começar com 0% e depois encher
    var fills = header.querySelectorAll('.fk-donut-chart .fill');
    fills.forEach(function (el) {
      var full = el.getAttribute('stroke-dasharray');
      el.setAttribute('stroke-dasharray', '0 ' + full.split(' ')[1]);
      requestAnimationFrame(function () {
        setTimeout(function () {
          el.setAttribute('stroke-dasharray', full);
        }, 60);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
