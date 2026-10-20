(function () {
  'use strict';

  var grid         = document.getElementById('plugins-grid');
  var emptyState   = document.getElementById('empty-state');
  var pagination   = document.getElementById('pagination');
  var countLabel   = document.getElementById('results-count');
  var filterName   = document.getElementById('filter-name');
  var filterCat    = document.getElementById('filter-category');
  var filterSort   = document.getElementById('filter-sort');
  var resetBtn     = document.getElementById('reset-filters');
  var emptyReset   = document.getElementById('empty-reset');

  if (!grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.plugin-card'));
  var PER_PAGE = 6;
  var currentPage = 1;
  var activeTags = new Set();

  // ── Estado atual dos filtros
  var state = {
    name: '',
    category: '',
    sort: 'stars'
  };

  // ── Aplica filtros + ordenação + paginação
  function apply() {
    var filtered = cards.filter(function (card) {
      var name = card.dataset.name || '';
      var author = card.dataset.author || '';
      var desc = card.dataset.description || '';
      var cat = card.dataset.category || '';
      var tags = (card.dataset.tags || '').split(',').filter(Boolean);

      if (state.name) {
        var q = state.name;
        if (name.indexOf(q) === -1 && author.indexOf(q) === -1 && desc.indexOf(q) === -1) {
          return false;
        }
      }

      if (state.category && cat !== state.category) return false;

      if (activeTags.size > 0) {
        for (var t of activeTags) {
          if (tags.indexOf(t) === -1) return false;
        }
      }

      return true;
    });

    // Ordenar
    filtered.sort(function (a, b) {
      switch (state.sort) {
        case 'downloads':
          return (+b.dataset.downloads || 0) - (+a.dataset.downloads || 0);
        case 'updated':
          return (+b.dataset.updated || 0) - (+a.dataset.updated || 0);
        case 'name':
          return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        case 'stars':
        default:
          return (+b.dataset.stars || 0) - (+a.dataset.stars || 0);
      }
    });

    // Esconde tudo
    cards.forEach(function (c) { c.hidden = true; });

    // Total
    var total = filtered.length;
    var pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (currentPage > pages) currentPage = pages;

    // Fatia
    var start = (currentPage - 1) * PER_PAGE;
    var slice = filtered.slice(start, start + PER_PAGE);
    slice.forEach(function (c) { c.hidden = false; });

    // Empty
    if (total === 0) {
      emptyState.hidden = false;
      pagination.innerHTML = '';
    } else {
      emptyState.hidden = true;
      renderPagination(pages);
    }

    // Count
    countLabel.textContent =
      total + (total === 1 ? ' plugin' : ' plugins') +
      (total !== cards.length ? ' de ' + cards.length : '');
  }

  // ── Paginação
  function renderPagination(totalPages) {
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    function btn(label, page, opts) {
      opts = opts || {};
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'page-btn' + (opts.current ? ' is-current' : '') + (opts.dots ? ' dots' : '');
      b.innerHTML = label;
      if (opts.dots || opts.disabled) {
        b.disabled = true;
      } else {
        b.addEventListener('click', function () {
          currentPage = page;
          apply();
          window.scrollTo({ top: grid.offsetTop - 160, behavior: 'smooth' });
        });
      }
      return b;
    }

    // Anterior
    pagination.appendChild(
      btn('‹', currentPage - 1, { disabled: currentPage === 1 })
    );

    // Páginas
    var range = [];
    var push = function (n) { if (range.indexOf(n) === -1) range.push(n); };

    push(1);
    if (currentPage > 3) push(currentPage - 2);
    if (currentPage > 2) push(currentPage - 1);
    push(currentPage);
    if (currentPage < totalPages - 1) push(currentPage + 1);
    if (currentPage < totalPages - 2) push(currentPage + 2);
    push(totalPages);

    range.sort(function (a, b) { return a - b; });

    var last = 0;
    range.forEach(function (n) {
      if (last && n - last > 1) {
        pagination.appendChild(btn('…', 0, { dots: true }));
      }
      pagination.appendChild(btn(String(n), n, { current: n === currentPage }));
      last = n;
    });

    // Próximo
    pagination.appendChild(
      btn('›', currentPage + 1, { disabled: currentPage === totalPages })
    );
  }

  // ── Eventos
  var debounce;
  filterName.addEventListener('input', function (e) {
    clearTimeout(debounce);
    debounce = setTimeout(function () {
      state.name = e.target.value.trim().toLowerCase();
      currentPage = 1;
      apply();
    }, 180);
  });

  filterCat.addEventListener('change', function (e) {
    state.category = e.target.value;
    currentPage = 1;
    apply();
  });

  filterSort.addEventListener('change', function (e) {
    state.sort = e.target.value;
    apply();
  });

  document.querySelectorAll('.chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var tag = chip.dataset.tag;
      if (activeTags.has(tag)) {
        activeTags.delete(tag);
        chip.classList.remove('is-active');
      } else {
        activeTags.add(tag);
        chip.classList.add('is-active');
      }
      currentPage = 1;
      apply();
    });
  });

  function resetFilters() {
    filterName.value = '';
    filterCat.value = '';
    filterSort.value = 'stars';
    state = { name: '', category: '', sort: 'stars' };
    activeTags.clear();
    document.querySelectorAll('.chip').forEach(function (c) {
      c.classList.remove('is-active');
    });
    currentPage = 1;
    apply();
  }

  resetBtn.addEventListener('click', resetFilters);
  emptyReset.addEventListener('click', resetFilters);

  // ── Tabs dentro dos cards
  grid.addEventListener('click', function (e) {
    var tab = e.target.closest('.tab');
    if (tab) {
      var card = tab.closest('.plugin-card');
      card.querySelectorAll('.tab').forEach(function (t) {
        t.classList.toggle('is-active', t === tab);
      });
      card.querySelectorAll('.panel').forEach(function (p) {
        p.classList.toggle('is-active', p.dataset.panel === tab.dataset.tab);
      });
      return;
    }

    var copyBtn = e.target.closest('.copy-btn');
    if (copyBtn) {
      var code = copyBtn.parentElement.querySelector('code');
      if (!code) return;
      var text = code.textContent;
      navigator.clipboard.writeText(text).then(function () {
        var old = copyBtn.textContent;
        copyBtn.textContent = 'Copiado!';
        copyBtn.classList.add('is-copied');
        setTimeout(function () {
          copyBtn.textContent = old;
          copyBtn.classList.remove('is-copied');
        }, 1400);
      });
    }
  });

  // ── Init
  apply();
})();