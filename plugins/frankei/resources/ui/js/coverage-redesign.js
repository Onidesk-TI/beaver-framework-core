/* ============================================================
 *  Frankei — Coverage Redesign helpers
 * ============================================================ */
(function (global) {
    'use strict';

    const THRESHOLDS = { high: 80, medium: 50 };

    function level(pct) {
        const p = Number(pct) || 0;
        if (p >= THRESHOLDS.high)   return 'high';
        if (p >= THRESHOLDS.medium) return 'medium';
        return 'low';
    }

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderRow(item) {
        const pct  = Math.max(0, Math.min(100, Number(item.pct) || 0));
        const lv   = level(pct);
        const path = item.path || item.name;
        const idx  = String(item.name).lastIndexOf('\\');
        const ns   = idx >= 0 ? item.name.slice(0, idx + 1) : '';
        const base = idx >= 0 ? item.name.slice(idx + 1)   : item.name;

        const countHtml = (item.total > 0)
            ? `<span class="lines-count"><span class="icon">📊</span>${item.covered ?? 0}<span class="sep">/</span>${item.total}</span>`
            : '';

        return `
            <a class="coverage-row level-${lv}"
               href="${escapeHtml(path)}"
               data-path="${escapeHtml(path)}"
               role="listitem"
               aria-label="${escapeHtml(item.name)}: ${pct.toFixed(0)}%"
               title="Abrir ${escapeHtml(item.name)}">
                <span class="status-dot ${lv}" aria-hidden="true"></span>
                <span class="class-name">${ns ? `<span class="namespace">${escapeHtml(ns)}</span>` : ''}${escapeHtml(base)}</span>
                <div class="coverage-bar">
                    <span class="fill ${lv}" data-pct="${pct}" style="width:0%"></span>
                </div>
                <span class="pct ${lv}">${pct.toFixed(0)}%</span>
                ${countHtml}
            </a>
        `;
    }

    function renderList(items, opts = {}) {
        if (!opts.group) {
            return `<div class="coverage-list" role="list">${items.map(renderRow).join('')}</div>`;
        }

        const buckets = { high: [], medium: [], low: [] };
        items.forEach(it => buckets[level(it.pct)].push(it));

        const labels = {
            high:   '🟠 Alta cobertura (≥ 80%)',
            medium: '🟡 Cobertura média (50–79%)',
            low:    '⚪ Baixa cobertura (< 50%)'
        };

        return ['high', 'medium', 'low']
            .filter(k => buckets[k].length)
            .map(k => `
                <section class="coverage-group ${k}">
                    <header>
                        <span>${labels[k]}</span>
                        <span class="count">${buckets[k].length}</span>
                    </header>
                    <div role="list">${buckets[k].map(renderRow).join('')}</div>
                </section>
            `).join('');
    }

    /* Abre a classe: click normal → Modal B; ctrl/cmd/middle → nova aba (nativo) */
    function abrirClasse(url, rowEl) {
        if (!url) return;

        // Se for ctrl/cmd/shift ou middle-click, deixa o browser abrir nova aba
        // (o handler só corre em click normal)

        var modal = document.getElementById('fkModal');
        var body  = modal && modal.querySelector('.fk-modal-body');

        // Fallback: sem modal → nova aba
        if (!modal || !body) {
            window.open(url, '_blank', 'noopener');
            return;
        }

        var nome = rowEl ? (rowEl.querySelector('.class-name')?.textContent || '').trim() : '';
        var pct  = rowEl ? (rowEl.querySelector('.pct')?.textContent || '').trim() : '';

        body.innerHTML = ''
            + '<div class="cv-modal-layout">'
            +   '<div class="cv-modal-main">'
            +     '<div class="cv-modal-head">'
            +       '<div>'
            +         '<h3>' + escapeHtml(nome) + '</h3>'
            +         '<div class="cv-modal-path">' + escapeHtml(url) + '</div>'
            +       '</div>'
            +     '</div>'
            +     '<div class="cv-modal-code">'
            +       '<iframe src="' + url + '" loading="lazy"></iframe>'
            +     '</div>'
            +   '</div>'
            +   '<aside class="cv-modal-side">'
            +     '<div class="cv-modal-side-title">Navegacao</div>'
            +     '<button type="button" class="cv-modal-action cv-modal-action-primary" data-cv-action="voltar">'
            +       '<span class="cv-modal-action-icon">\u2B05</span> Voltar'
            +     '</button>'
            +     '<button type="button" class="cv-modal-action" data-cv-action="recarregar">'
            +       '<span class="cv-modal-action-icon">\u{1F504}</span> Recarregar'
            +     '</button>'
            +     '<div class="cv-modal-sep"></div>'
            +     '<button type="button" class="cv-modal-action" data-cv-action="nova-aba">'
            +       '<span class="cv-modal-action-icon">\u{1F195}</span> Nova aba'
            +     '</button>'
            +   '</aside>'
            + '</div>';

        modal.classList.add('open');

        // Handlers da sidebar
        var side = body.querySelector('.cv-modal-side');
        var ifr  = body.querySelector('.cv-modal-code iframe');

        var close = function () {
            modal.classList.remove('open');
            body.innerHTML = '';
        };

        if (side) {
            side.addEventListener('click', function (ev) {
                var btn = ev.target.closest('[data-cv-action]');
                if (!btn) return;
                var action = btn.getAttribute('data-cv-action');
                if (action === 'voltar')          close();
                else if (action === 'recarregar') { if (ifr) ifr.src = ifr.src; }
                else if (action === 'nova-aba')   window.open(url, '_blank', 'noopener');
            });
        }

        // Fechar por ESC (uma vez só)
        var onEsc = function (e) {
            if (e.key === 'Escape') { close(); document.removeEventListener('keydown', onEsc); }
        };
        document.addEventListener('keydown', onEsc);
    }

    function instalarHandlers(root = document) {
        root.querySelectorAll('a.coverage-row[data-path]').forEach(function (row) {
            if (row.dataset.fkClickable === '1') return;
            row.dataset.fkClickable = '1';

            row.addEventListener('click', function (e) {
                // ctrl/cmd/shift/middle → deixa o browser abrir nova aba
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;
                e.preventDefault();
                abrirClasse(row.dataset.path, row);
            });
        });
    }

    function animateFills(root = document) {
        // Instalar handlers de click primeiro
        instalarHandlers(root);

        root.querySelectorAll('.coverage-bar .fill[data-pct]').forEach(el => {
            void el.offsetWidth;
            el.style.width = el.dataset.pct + '%';
        });
    }

    function injectCss(href) {
        const id = 'fk-coverage-redesign-css';
        if (document.getElementById(id)) return;
        const link = document.createElement('link');
        link.id   = id;
        link.rel  = 'stylesheet';
        link.href = href || '/plugins/frankei/resources/ui/css/coverage-redesign.css';
        document.head.appendChild(link);
    }


    /* ─────────────────────────────────────────────────────────────
       BRIDGE: clicar numa .coverage-row NÃO deve sair da modal.
       Se estivermos dentro de um iframe → avisa o parent via postMessage
       para ele trocar o src do iframe. Assim o utilizador continua na
       mesma Modal B.
    ───────────────────────────────────────────────────────────── */
    function instalarBridgeLista(root) {
        root = root || document;
        root.querySelectorAll('a.coverage-row[data-path], a.coverage-row[href]').forEach(function (row) {
            if (row.dataset.fkBridge === '1') return;
            row.dataset.fkBridge = '1';

            row.addEventListener('click', function (e) {
                // Ctrl/Cmd/Shift/middle → deixa o browser abrir nova aba
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;

                var url = row.dataset.path || row.getAttribute('href');
                if (!url) return;

                // Se estivermos num iframe, avisar o parent
                if (window.parent && window.parent !== window) {
                    e.preventDefault();
                    try {
                        window.parent.postMessage({
                            type: 'frankei:open-in-modal',
                            url: url
                        }, '*');
                    } catch (err) { /* cross-origin, ignorar */ }
                }
                // Caso contrário, deixa navegar normalmente
            });
        });
    }

    global.FrankeiCoverage = {
        level, renderRow, renderList, animateFills, injectCss, instalarBridgeLista, escapeHtml, THRESHOLDS
    };
})(window);


/* Auto-instalar quando o DOM estiver pronto */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        if (window.FrankeiCoverage && window.FrankeiCoverage.instalarBridgeLista) {
            window.FrankeiCoverage.instalarBridgeLista(document);
        }
    });
} else {
    if (window.FrankeiCoverage && window.FrankeiCoverage.instalarBridgeLista) {
        window.FrankeiCoverage.instalarBridgeLista(document);
    }
}
