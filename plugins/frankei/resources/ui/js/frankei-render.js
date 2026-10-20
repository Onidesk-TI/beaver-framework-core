window.FrankeiRender = (function () {
    'use strict';

    var SEV_ORDER = ['critical', 'high', 'medium', 'low', 'info'];

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Tab: Visão geral ──────────────────────────────────── */

    function renderOverview(root, result) {
        var s = result.stats || {};
        var sev = s.by_severity || {};
        var cat = s.by_category || {};

        var sevCards = SEV_ORDER.map(function (k) {
            var n = sev[k] || 0;
            return '<div class="fk-sev-card fk-sev-' + k + (n === 0 ? ' is-zero' : '') + '">' +
                '  <div class="fk-sev-count">' + n + '</div>' +
                '  <div class="fk-sev-label">' + k + '</div>' +
                '</div>';
        }).join('');

        var catCards = Object.keys(cat).map(function (k) {
            return '<div class="fk-cat-card">' +
                '  <span class="fk-cat-name">' + esc(k) + '</span>' +
                '  <span class="fk-cat-count">' + cat[k] + '</span>' +
                '</div>';
        }).join('');

        root.innerHTML =
            '<div class="fk-overview">' +
            '  <div class="fk-stats-line">' +
            '    <strong>' + (s.total || 0) + '</strong> findings · ' +
            '    <span class="fk-muted">' + (s.elapsed_ms || 0) + ' ms</span>' +
            '  </div>' +
            '  <div class="fk-sev-grid">' + sevCards + '</div>' +
            '  <div class="fk-section-title">Categorias</div>' +
            '  <div class="fk-cat-grid">' + catCards + '</div>' +
            (result.errors && result.errors.length
                ? '<div class="fk-section-title">Erros</div>' +
                  '<div class="fk-errors">' +
                    result.errors.map(function (e) {
                        return '<div class="fk-error">' + esc(e.scanner) + ': ' + esc(e.message) + '</div>';
                    }).join('') +
                  '</div>'
                : '') +
            '</div>';
    }

    /* ── Tab: Problemas ────────────────────────────────────── */

    function renderFindings(root, result, filter) {
        var findings = result.findings || [];

        if (filter && filter.severity) {
            findings = findings.filter(function (f) { return f.severity === filter.severity; });
        }
        if (filter && filter.category) {
            findings = findings.filter(function (f) { return f.category === filter.category; });
        }

        if (!findings.length) {
            root.innerHTML = '<div class="fk-empty">Nenhum problema nesta vista.</div>';
            return;
        }

        // Ordenar por severidade (mais grave primeiro)
        findings = findings.slice().sort(function (a, b) {
            return SEV_ORDER.indexOf(a.severity) - SEV_ORDER.indexOf(b.severity);
        });

        // Barra de filtros
        var filtersBar =
            '<div class="fk-filter-bar">' +
            '  <button class="fk-chip' + (!filter || !filter.severity ? ' is-active' : '') + '" data-sev="">todos</button>' +
            SEV_ORDER.map(function (k) {
                var n = (result.stats.by_severity || {})[k] || 0;
                return '<button class="fk-chip fk-chip-' + k + (filter && filter.severity === k ? ' is-active' : '') +
                       '" data-sev="' + k + '">' + k + ' <span class="fk-chip-n">' + n + '</span></button>';
            }).join('') +
            '</div>';

        // Lista
        var items = findings.map(function (f) {
            var tableCol = f.table
                ? '<code class="fk-tbl">' + esc(f.table) + (f.column ? '.' + esc(f.column) : '') + '</code>'
                : '';
            var sug = f.suggestion
                ? '<div class="fk-sug"><div class="fk-sug-label">Sugestão</div><pre>' + esc(f.suggestion) + '</pre></div>'
                : '';
            var imp = f.impact
                ? '<div class="fk-impact">' + esc(f.impact) + '</div>'
                : '';

            return '<details class="fk-finding fk-finding-' + f.severity + '">' +
                '  <summary>' +
                '    <span class="fk-badge fk-badge-' + f.severity + '">' + f.severity + '</span>' +
                '    <span class="fk-finding-title">' + esc(f.title) + '</span>' +
                '    <span class="fk-finding-scanner">' + esc(f.scanner) + '</span>' +
                '  </summary>' +
                '  <div class="fk-finding-body">' +
                (tableCol ? '<div class="fk-finding-loc">' + tableCol + '</div>' : '') +
                (f.description ? '<p>' + esc(f.description) + '</p>' : '') +
                imp + sug +
                (f.reference
                    ? '<a class="fk-ref" href="' + esc(f.reference) + '" target="_blank" rel="noopener">documentação ↗</a>'
                    : '') +
                '  </div>' +
                '</details>';
        }).join('');

        root.innerHTML = filtersBar + '<div class="fk-findings">' + items + '</div>';

        // Ações dos filtros
        root.querySelectorAll('.fk-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sev = btn.getAttribute('data-sev');
                if (window.Frankei) {
                    window.Frankei.setFilter({ severity: sev || null });
                }
            });
        });
    }

    /* ── Tab: Sugestões ────────────────────────────────────── */

    function renderSuggestions(root, result) {
        var withSug = (result.findings || []).filter(function (f) { return f.suggestion; });

        if (!withSug.length) {
            root.innerHTML = '<div class="fk-empty">Sem sugestões por agora.</div>';
            return;
        }

        // Agrupa por sugestão (várias tabelas podem partilhar o mesmo "CREATE INDEX")
        var bySug = {};
        withSug.forEach(function (f) {
            var key = f.suggestion;
            if (!bySug[key]) bySug[key] = { suggestion: f.suggestion, items: [] };
            bySug[key].items.push(f);
        });

        var groups = Object.keys(bySug).map(function (key) {
            var g = bySug[key];
            var list = g.items.slice(0, 8).map(function (f) {
                return '<li><code>' + esc(f.table || '') + (f.column ? '.' + esc(f.column) : '') + '</code></li>';
            }).join('');
            var more = g.items.length > 8
                ? '<li class="fk-muted">+ ' + (g.items.length - 8) + ' mais</li>'
                : '';

            return '<div class="fk-sug-group">' +
                '  <div class="fk-sug-head">' +
                '    <span class="fk-badge fk-badge-' + g.items[0].severity + '">' + g.items[0].severity + '</span>' +
                '    ' + esc(g.items[0].title.replace(/^(Tabela sem PRIMARY KEY|FK sem índice|Tipo suspeito|Nullable em excesso): .*/, '$1')) +
                '    <span class="fk-muted">×' + g.items.length + '</span>' +
                '  </div>' +
                '  <pre class="fk-sug-code">' + esc(g.suggestion) + '</pre>' +
                '  <details><summary>Aplicar a:</summary><ul>' + list + more + '</ul></details>' +
                '</div>';
        }).join('');

        root.innerHTML = '<div class="fk-sugs">' + groups + '</div>';
    }

    /* ── Tab: Raw ──────────────────────────────────────────── */

    function renderRaw(root, result) {
        root.innerHTML = '<pre class="fk-raw"><code>' +
            esc(JSON.stringify(result, null, 2)) +
            '</code></pre>';
    }

    /* ── API pública ───────────────────────────────────────── */

    function renderAll(root, result, filter) {
        var overview = root.querySelector('[data-panel="overview"]');
        var findings = root.querySelector('[data-panel="findings"]');
        var sugs     = root.querySelector('[data-panel="suggestions"]');
        var raw      = root.querySelector('[data-panel="raw"]');

        if (overview) renderOverview(overview, result);
        if (findings) renderFindings(findings, result, filter || {});
        if (sugs)     renderSuggestions(sugs, result);
        if (raw)      renderRaw(raw, result);
    }

    return {
        renderAll: renderAll,
        renderFindings: renderFindings
    };
})();
