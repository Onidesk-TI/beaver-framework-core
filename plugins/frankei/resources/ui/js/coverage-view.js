/* === Frankei Coverage Redesign — auto-injetado === */
(function loadFrankeiCoverage() {
    if (window.FrankeiCoverage) return;

    // URLs corretos servidos por frankei_serve_asset() em routes/web.php
    var assets = [
        { id: 'cv-drawer-css',       tag: 'link', rel: 'stylesheet', href: '/plugins/frankei/css/coverage-drawer.css' },
        { id: 'fk-redesign-css',     tag: 'link', rel: 'stylesheet', href: '/plugins/frankei/css/coverage-redesign.css' },
        { id: 'fk-redesign-js',      tag: 'script', src: '/plugins/frankei/js/coverage-redesign.js' },
        { id: 'fk-report-adapter-js',tag: 'script', src: '/plugins/frankei/js/coverage-report-adapter.js' }
    ];

    assets.forEach(function (a) {
        if (document.getElementById(a.id)) return;
        if (a.tag === 'link') {
            var l = document.createElement('link');
            l.id = a.id; l.rel = a.rel; l.href = a.href;
            document.head.appendChild(l);
        } else {
            var s = document.createElement('script');
            s.id = a.id; s.src = a.src; s.defer = true;
            document.head.appendChild(s);
        }
    });

    /* ============================================================
     * PREMIUM (v4) — Melhorias visuais
     * ============================================================ */

    /* ---------- 1) Ícones por namespace ---------- */
    function iconForNamespace(ns) {
        const s = (ns || '').toLowerCase();
        if (s.includes('controllers'))   return '⚙️';
        if (s.includes('\\services\\'))   return '🛠️';
        if (s.includes('scanners\\security')) return '🔒';
        if (s.includes('scanners\\performance')) return '🚀';
        if (s.includes('scanners\\code')) return '📐';
        if (s.includes('scanners\\passwords')) return '🔑';
        if (s.includes('scanners'))      return '🔎';
        if (s.includes('console\\commands')) return '💻';
        if (s.includes('console'))       return '⌨️';
        if (s.includes('database'))      return '🗄️';
        if (s.includes('migrations'))    return '📦';
        if (s.includes('models'))        return '🧬';
        if (s.includes('validation'))    return '✅';
        if (s.includes('plugins\\frankei')) return '🦫';
        if (s.includes('frankei'))       return '🔍';
        if (s.includes('sdk'))           return '🔌';
        if (s.includes('middleware'))    return '🛡️';
        if (s.includes('queue'))         return '📬';
        if (s.includes('cache'))         return '💾';
        if (s.includes('session'))       return '🎫';
        if (s.includes('storage'))       return '📂';
        if (s.includes('scheduling'))    return '⏰';
        if (s.includes('mail'))          return '📧';
        if (s.includes('i18n'))          return '🌍';
        if (s.includes('view'))          return '👁️';
        if (s.includes('http'))          return '🌐';
        if (s.includes('foundation'))    return '🏛️';
        if (s.includes('hashing'))       return '🔐';
        if (s.includes('encryption'))    return '🔒';
        if (s.includes('ratelimiter'))   return '⏱️';
        if (s.includes('cookies'))       return '🍪';
        if (s.includes('draft'))         return '📝';
        if (s.includes('error'))         return '⚠️';
        return '📁';
    }

    /* ---------- 2) Nível de cobertura por % ---------- */
    function levelFromPct(pct) {
        if (pct >= 80) return 'good';
        if (pct >= 50) return 'ok';
        if (pct > 0)   return 'bad';
        return 'none';
    }

    /* ---------- 3) Aplicar melhorias às linhas ---------- */
    function enhanceRows() {
        const rows = document.querySelectorAll('.cv-table tbody tr');
        if (!rows.length) return 0;

        let count = 0;
        rows.forEach(tr => {
            if (tr.dataset.fkEnhanced === '1') return;

            // Percentagem
            const pctText = tr.querySelector('.cv-bar-pct')?.textContent || '0%';
            const pct = parseFloat(pctText.replace('%', '').replace(',', '.')) || 0;
            const lvl = levelFromPct(pct);

            tr.dataset.level = lvl;
            tr.dataset.fkEnhanced = '1';

            // Cor das barras — reaproveita classes
            tr.querySelectorAll('.cv-bar-fill').forEach(fill => {
                fill.classList.remove('cv-lvl-good', 'cv-lvl-ok', 'cv-lvl-bad', 'cv-lvl-none');
                fill.classList.add('cv-lvl-' + lvl);
            });
            tr.querySelectorAll('.cv-bar-pct').forEach(p => {
                p.classList.remove('cv-lvl-good', 'cv-lvl-ok', 'cv-lvl-bad', 'cv-lvl-none');
                p.classList.add('cv-lvl-' + lvl);
            });

            // Ícone por namespace
            const iconEl = tr.querySelector('.cv-class-icon');
            const nsEl   = tr.querySelector('.cv-class-module');
            if (iconEl && nsEl) {
                const ns = nsEl.textContent.trim();
                iconEl.textContent = iconForNamespace(ns);
                // 'none' → mantém neutro
                if (lvl === 'good') iconEl.textContent = '🟢';
                else if (lvl === 'ok') iconEl.textContent = '🟡';
                else if (lvl === 'bad') iconEl.textContent = '🔴';
            }

            count++;
        });

        return count;
    }

    /* ---------- 4) Barra de progresso GLOBAL ---------- */
    function injectGlobalProgress() {
        if (document.querySelector('.cv-progress-global')) return 0;

        const wrap = document.getElementById('cvTableWrap');
        if (!wrap) return 0;

        // Calcular cobertura global (média das % da tabela)
        const rows = document.querySelectorAll('.cv-table tbody tr');
        let total = 0, n = 0;
        let good = 0, ok = 0, bad = 0, none = 0;

        rows.forEach(tr => {
            const pctText = tr.querySelector('.cv-bar-pct')?.textContent || '0%';
            const pct = parseFloat(pctText.replace('%', '').replace(',', '.')) || 0;
            total += pct;
            n++;
            if (pct >= 80) good++;
            else if (pct >= 50) ok++;
            else if (pct > 0) bad++;
            else none++;
        });

        const avg = n > 0 ? (total / n).toFixed(2) : '0.00';
        const avgNum = parseFloat(avg);
        const lvl = levelFromPct(avgNum);

        const el = document.createElement('div');
        el.className = 'cv-progress-global';
        el.innerHTML = `
            <div class="cv-progress-head">
                <span class="cv-progress-label">📈 Cobertura global</span>
                <span class="cv-progress-value ${lvl === 'none' ? '' : lvl}">${avg}%</span>
            </div>
            <div class="cv-progress-track">
                <div class="cv-progress-fill ${lvl === 'none' ? '' : lvl}" data-target="${avg}"></div>
            </div>
            <div class="cv-progress-legend">
                <span class="cv-legend-item">
                    <span class="cv-legend-dot" style="background:#ea580c"></span>
                    ${good} bem cobertas (≥80%)
                </span>
                <span class="cv-legend-item">
                    <span class="cv-legend-dot" style="background:#b8860b"></span>
                    ${ok} parciais (50-79%)
                </span>
                <span class="cv-legend-item">
                    <span class="cv-legend-dot" style="background:#6b7280"></span>
                    ${bad + none} não cobertas (&lt;50%)
                </span>
            </div>
        `;

        // Inserir antes da tabela
        const parent = wrap.parentElement;
        const wrapper = wrap.closest('.cv-table-card');
        if (wrapper && wrapper.parentNode) {
            wrapper.parentNode.insertBefore(el, wrapper);
        } else {
            parent.insertBefore(el, wrap);
        }

        // Animar a barra no próximo frame
        requestAnimationFrame(() => {
            setTimeout(() => {
                const fill = el.querySelector('.cv-progress-fill');
                if (fill) fill.style.width = avg + '%';
            }, 100);
        });

        return 1;
    }

    /* ---------- 5) Contadores nos chips ---------- */
    function addChipCounts() {
        const chips = document.querySelectorAll('.cv-chip');
        if (!chips.length) return 0;

        // Contar por nível
        const rows = document.querySelectorAll('.cv-table tbody tr');
        let countAll = 0, countBad = 0, countOk = 0, countGood = 0;

        rows.forEach(tr => {
            const pctText = tr.querySelector('.cv-bar-pct')?.textContent || '0%';
            const pct = parseFloat(pctText.replace('%', '').replace(',', '.')) || 0;
            countAll++;
            if (pct >= 80) countGood++;
            else if (pct >= 50) countOk++;
            else if (pct < 50) countBad++;
        });

        const map = {
            'all':  countAll,
            'bad':  countBad,
            'ok':   countOk,
            'good': countGood,
        };

        let added = 0;
        chips.forEach(chip => {
            if (chip.querySelector('.cv-chip-count')) return;
            const filter = chip.dataset.cvFilter || 'all';
            const count = map[filter] ?? 0;

            const span = document.createElement('span');
            span.className = 'cv-chip-count';
            span.textContent = count;
            chip.appendChild(span);
            added++;
        });

        return added;
    }

    /* ---------- 6) Animar barras internas ---------- */
    function animateBars() {
        // Guardar o target uma vez, animar SEMPRE (idempotente)
        document.querySelectorAll('.cv-bar-fill[style*="width"]').forEach(fill => {
            // Lê o target do style atual (ex: "81.4%")
            const style = fill.getAttribute('style') || '';
            const match = style.match(/width:\s*([\d.]+)%/);
            if (!match) return;

            const target = match[1] + '%';

            // Se já animado, não repetir
            if (fill.dataset.fkAnimated === '1') return;
            fill.dataset.fkAnimated = '1';

            // Definir largura 0 e animar até target
            fill.style.transition = 'none';
            fill.style.width = '0%';

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    fill.style.transition = 'width .9s cubic-bezier(.22,.61,.36,1)';
                    fill.style.width = target;
                });
            });
        });
        return 1;
    }

    /* ---------- 7) Arrancar melhorias ---------- */
    var __fkPremiumRunning = false;

    function applyPremiumFeatures() {
        // Guard de reentrada — o observer dispara com o próprio trabalho
        if (__fkPremiumRunning) return;

        // Guard: se nao ha chips nem tabela, nao fazer nada
        if (!document.querySelector('.cv-chip') && !document.querySelector('.cv-table')) {
            return;
        }

        __fkPremiumRunning = true;

        try {
        const a = enhanceRows();
        addDataLabels();
        const b = injectGlobalProgress();
        const c = addChipCounts();
        const d = animateBars();

        console.log(
            '[FrankeiCoverage-premium] aplicado:',
            'rows=' + a,
            'global=' + b,
            'chips=' + c,
            'animated=' + d
        );
        } finally {
            __fkPremiumRunning = false;
        }
    }

    /* ---------- 8) data-label nas <td> (responsivo) ---------- */
    function addDataLabels() {
        const labels = ['Classe', 'Lines', 'Methods', 'CRAP', 'LOC'];
        let count = 0;

        document.querySelectorAll('.cv-table tbody tr').forEach(tr => {
            if (tr.dataset.fkLabels === '1') return;

            tr.querySelectorAll('td').forEach((td, i) => {
                if (labels[i]) {
                    td.setAttribute('data-label', labels[i]);
                }
            });
            tr.dataset.fkLabels = '1';
            count++;
        });

        return count;
    }

    // Executar sempre que a tabela for re-renderizada (debounce real)
    var __fkApplyTimer = null;
    var __fkApplyPending = false;

    function scheduleApplyPremium() {
        // Cancela timeouts pendentes — só corre UMA vez após estabilizar
        if (__fkApplyTimer) clearTimeout(__fkApplyTimer);

        __fkApplyPending = true;

        __fkApplyTimer = setTimeout(function(){
            __fkApplyTimer = null;
            if (!__fkApplyPending) return;
            __fkApplyPending = false;

            try {
                applyPremiumFeatures();
            } catch (e) {
                console.warn('[FrankeiCoverage-premium] erro:', e);
            }
        }, 200);
    }

    // Expor API
    if (typeof window.CoverageView === 'object') {
        window.CoverageView.applyPremium = applyPremiumFeatures;
    } else {
        window.CoverageView = { applyPremium: applyPremiumFeatures };
    }

    // Observar mutações do DOM (caso a tabela seja re-renderizada)
    // Escopo apertado: só o #cvTableWrap, e só childList (não attributes).
    if (typeof MutationObserver !== 'undefined') {
        var cvTarget = document.getElementById('cvTableWrap');

        // Se o alvo ainda não existe, tentar mais tarde
        if (!cvTarget) {
            var retry = 0;
            var tryAttach = setInterval(function(){
                cvTarget = document.getElementById('cvTableWrap');
                if (cvTarget || ++retry > 40) {
                    clearInterval(tryAttach);
                    if (cvTarget) attachObserver(cvTarget);
                }
            }, 250);
        } else {
            attachObserver(cvTarget);
        }

        function attachObserver(target) {
            if (target.dataset.fkMoBound === '1') return;
            target.dataset.fkMoBound = '1';

            var mo = new MutationObserver(function(muts){
                // Ignorar mutações que nós próprios provocámos
                if (__fkApplyPending) return;
                scheduleApplyPremium();
            });

            mo.observe(target, {
                childList: true,
                subtree: true
                // sem attributes — não dispara com data-fk-labels / data-fk-animated
            });
        }
    }

    // Arranque inicial
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleApplyPremium);
    } else {
        scheduleApplyPremium();
    }



    /* ============================================================
     * Sincronizar filtros do dashboard -> iframe
     * ============================================================ */
    function notifyIframe(filter) {
        const iframes = document.querySelectorAll('iframe');
        if (!iframes.length) return 0;

        let sent = 0;
        iframes.forEach(iframe => {
            try {
                const doc = iframe.contentDocument;
                if (!doc) return;

                const url = iframe.src || '';
                if (!url.includes('coverage-inline')) return;

                iframe.contentWindow.postMessage({
                    type:  'fk-filter',
                    value: filter,
                }, '*');

                sent++;
            } catch (e) {
                // Cross-origin, ignorar
            }
        });

        return sent;
    }

    // Expor para debug
    if (typeof window.CoverageView === 'object') {
        window.CoverageView.notifyIframe = notifyIframe;
    }


    /* ============================================================
     * Painel lateral — lista de classes/métodos filtrados
     * ============================================================ */
    function renderFilteredPanel(filter) {
        const wrap = document.getElementById('cvTableWrap');
        if (!wrap) return 0;

        const old = document.querySelector('.cv-panel');
        if (old) old.remove();

        const rows = wrap.querySelectorAll('.cv-table tbody tr');
        const items = [];

        rows.forEach(tr => {
            const pctText = tr.querySelector('.cv-bar-pct')?.textContent || '0%';
            const pct = parseFloat(pctText.replace('%', '').replace(',', '.')) || 0;

            let level = 'bad';
            if (pct >= 80)      level = 'good';
            else if (pct >= 50) level = 'ok';

            if (filter !== 'all' && level !== filter) return;

            const icon = tr.querySelector('.cv-class-icon')?.textContent?.trim() || '📁';
            const ns   = tr.querySelector('.cv-class-module')?.textContent?.trim() || '';
            const name = tr.querySelector('.cv-class-name-short')?.textContent?.trim() || '';
            const path = tr.dataset.pathTooltip || '';

            items.push({ icon, ns, name, pct, path, level });
        });

        if (!items.length) return 0;

        const panel = document.createElement('div');
        panel.className = 'cv-panel';

        const list = document.createElement('div');
        list.className = 'cv-panel-list';

        const labels = {
            all:  '📋 Todas as classes',
            good: '🟢 Bem cobertas (≥80%)',
            ok:   '🟡 Parciais (50-79%)',
            bad:  '🔴 Críticas (<50%)',
        };

        list.innerHTML =
            '<div class="cv-panel-head">' +
            '  <span>' + (labels[filter] || filter) + '</span>' +
            '  <span class="cv-panel-count">' + items.length + '</span>' +
            '</div>' +
            '<div class="cv-panel-items"></div>';

        const itemsWrap = list.querySelector('.cv-panel-items');
        items.forEach(item => {
            const el = document.createElement('div');
            el.className = 'cv-panel-item';
            el.dataset.path  = item.path;
            el.dataset.level = item.level;
            el.innerHTML =
                '<span class="cv-panel-icon">' + item.icon + '</span>' +
                '<span class="cv-panel-name">' +
                '  <span class="cv-panel-ns">' + item.ns + '</span>' + item.name +
                '</span>' +
                '<span class="cv-panel-pct ' + item.level + '">' + item.pct.toFixed(1) + '%</span>';

            el.addEventListener('click', () => {
                itemsWrap.querySelectorAll('.cv-panel-item').forEach(i => i.classList.remove('active'));
                el.classList.add('active');
                openClassFile(item);
            });

            itemsWrap.appendChild(el);
        });

        // Inserir o painel antes da tabela
        const tableCard = wrap.querySelector('.cv-table-card');
        if (tableCard && tableCard.parentNode) {
            tableCard.parentNode.insertBefore(panel, tableCard);
        } else {
            wrap.insertBefore(panel, wrap.firstChild);
        }

        panel.appendChild(list);
        return items.length;
    }

    /* ============================================================
     * Abrir o ficheiro da classe (com anchor se possível)
     * ============================================================ */
    function openClassFile(item) {
        // Construir o path relativo ao coverage
        let rel = item.path || '';
        rel = rel.replace(/^.*?\/(?:beaver-framework2?|project|app-root)\//i, '')
            .replace(/^\/+/, '');
        rel = rel.replace(/\.php$/, '.php.html');

        const fileUrl = '/api/frankei/terminal/coverage-inline?file='
            + encodeURIComponent('current/coverage/' + rel);

        // Tentar usar o openDrawer() se existir
        if (typeof openDrawer === 'function') {
            try {
                openDrawer({
                    file: item.path,
                    shortName: item.name,
                    name: item.name,
                });
                console.log('[CoverageView] openDrawer chamado para', item.name);
                return;
            } catch (e) {
                console.warn('[CoverageView] openDrawer falhou:', e);
            }
        }

        // Fallback: iframe existente
        const iframe = document.querySelector('.cv-drawer-body iframe, iframe');
        if (iframe) {
            iframe.src = fileUrl;
            return;
        }

        // Último recurso
        window.open(fileUrl, '_blank');
    }

    /* ============================================================
     * Ligar chips ao painel
     * ============================================================ */
    function bindChipsToPanel() {
        const chips = document.querySelectorAll('.cv-chip');
        if (!chips.length) return 0;

        let count = 0;
        chips.forEach(chip => {
            if (chip.dataset.fkPanelBound === '1') return;

            chip.addEventListener('click', function () {
                const filter = chip.dataset.cvFilter || 'all';
                // Pequeno delay para o rerender() correr primeiro
                setTimeout(() => renderFilteredPanel(filter), 60);
            });

            chip.dataset.fkPanelBound = '1';
            count++;
        });

        return count;
    }

    /* ============================================================
     * Arranque
     * ============================================================ */
    function initPanel() {
        bindChipsToPanel();
        setTimeout(() => renderFilteredPanel('all'), 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPanel);
    } else {
        initPanel();
    }


    // Expor funções no window (para o IIFE principal as ver)
    if (typeof window !== 'undefined') {
        window.__fkRenderFilteredPanel = renderFilteredPanel;
        window.__fkNotifyIframe = notifyIframe;
        window.__fkOpenClassFile = openClassFile;
        window.__fkBindChipsToPanel = typeof bindChipsToPanel === 'function' ? bindChipsToPanel : null;
        console.log('[CoverageView] funções expostas no window');
    }

})();
/* === /Frankei Coverage Redesign === */

/**
 * CoverageView — Vista bonita do coverage (clover.xml)
 * window.CoverageView.render(container, data)
 */
(function (window, document) {
    'use strict';

    // ─── Helpers ───
    function fmtPct(n) { return (n || 0).toFixed(2) + '%'; }
    function fmtNum(n) { return Number(n || 0).toLocaleString('pt-PT'); }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function levelOf(pct, total) {
        if (!total) return 'none';
        if (pct >= 80) return 'good';
        if (pct >= 50) return 'ok';
        if (pct > 0)   return 'bad';
        return 'none';
    }
    function iconForLevel(l) {
        return { good: '🟢', ok: '🟡', bad: '🔴', none: '⚪' }[l] || '⚪';
    }

    function crapLevel(crap) {
        if (crap >= 30) return 'high';
        if (crap >= 10) return 'mid';
        return 'low';
    }

    // ─── Renders ───
    function renderTotals(totals) {
        var cards = [
            { key: 'lines',     icon: '📝', label: 'Lines',              data: totals.lines },
            { key: 'functions', icon: '⚙️', label: 'Functions & Methods', data: totals.functions },
            { key: 'classes',   icon: '🏛️', label: 'Classes & Traits',    data: totals.classes },
        ];

        return '<div class="cv-totals">' + cards.map(function (c) {
            var pct = c.data.percent || 0;
            var lvl = levelOf(pct, c.data.total);
            return ''
                + '<div class="cv-total-card cv-level-' + lvl + '">'
                +   '<span class="cv-tc-icon">' + c.icon + '</span>'
                +   '<div class="cv-tc-label">' + c.label + '</div>'
                +   '<div class="cv-tc-value">'
                +     '<span class="cv-pct">' + fmtPct(pct) + '</span>'
                +   '</div>'
                +   '<div class="cv-tc-sub">'
                +     fmtNum(c.data.covered) + ' / ' + fmtNum(c.data.total) + ' cobertos'
                +   '</div>'
                +   '<div class="cv-tc-bar">'
                +     '<div class="cv-tc-fill" style="width:' + pct + '%"></div>'
                +   '</div>'
                + '</div>';
        }).join('') + '</div>';
    }

    function renderBar(pct, total, covered) {
        var lvl = levelOf(pct, total);
        var cnt = (covered != null && total != null)
            ? '<span class="cv-bar-count">' + fmtNum(covered) + ' / ' + fmtNum(total) + '</span>'
            : '';
        return ''
            + '<div class="cv-bar">'
            +   '<div class="cv-bar-track">'
            +     '<div class="cv-bar-fill cv-lvl-' + lvl + '" style="width:' + pct + '%"></div>'
            +   '</div>'
            +   cnt
            +   '<span class="cv-bar-pct cv-lvl-' + lvl + '">' + fmtPct(pct) + '</span>'
            + '</div>';
    }

    function renderClassRow(c) {
        var lvl = levelOf(c.lines.percent, c.lines.total);

        // Separar namespace e classe
        var fullName = c.name || '';
        var lastNs   = fullName.lastIndexOf('\\');
        var ns       = lastNs > -1 ? fullName.substring(0, lastNs + 1) : '';
        var shortCls = lastNs > -1 ? fullName.substring(lastNs + 1) : fullName;

        var nsHtml = ns
            ? '<span class="cv-class-module">' + escapeHtml(ns) + '</span>'
            : '';
        var clsHtml = '<span class="cv-class-name-short">' + escapeHtml(shortCls) + '</span>';

        var covKey = String(Math.round(c.lines.percent));
        return ''
            + '<tr data-class="' + escapeHtml(c.name) + '" data-path-tooltip="' + escapeHtml(c.file || '') + '" data-coverage="' + covKey + '">'
            +   '<td>'
            +     '<div class="cv-class-name">'
            +       '<span class="cv-class-icon">' + iconForLevel(lvl) + '</span>'
            +       nsHtml + clsHtml
            +     '</div>'
            +     '<div class="cv-class-path">' + escapeHtml(c.package || '') + '</div>'
            +   '</td>'
            +   '<td class="cv-bar-cell">' + renderBar(c.lines.percent, c.lines.total, c.lines.covered, c.lines.total) + '</td>'
            +   '<td class="cv-bar-cell">' + renderBar(c.functions.percent, c.functions.total, c.functions.covered, c.functions.total) + '</td>'
            +   '<td style="text-align:right">'
            +     '<span class="cv-badge cv-crap-' + crapLevel(c.crap) + '">' + c.crap + '</span>'
            +   '</td>'
            +   '<td style="text-align:right;font-family:ui-monospace,monospace;color:#6B7280">'
            +     fmtNum(c.lines.total)
            +   '</td>'
            + '</tr>';
    }

    function renderTable(classes) {
        return ''
            + '<div class="cv-table-card">'
            +   '<table class="cv-table">'
            +     '<thead><tr>'
            +       '<th>Classe</th>'
            +       '<th>Lines</th>'
            +       '<th>Methods</th>'
            +       '<th style="text-align:right">CRAP</th>'
            +       '<th style="text-align:right">LOC</th>'
            +     '</tr></thead>'
            +     '<tbody id="cvTableBody">'
            +       classes.map(renderClassRow).join('')
            +     '</tbody>'
            +   '</table>'
            + '</div>';
    }

    // ─── Drawer ───
    function updateRunBadge(className) {
        var badge = document.getElementById('fkCvRunBadge');
        if (!badge) return;

        badge.textContent = '…';
        badge.classList.remove('cv-run-badge-ok', 'cv-run-badge-low', 'cv-run-badge-zero');

        fetch('/api/frankei/terminal/coverage/data', { credentials: 'include' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var cls = (d.classes || []).filter(function (x) { return x.name === className; })[0];

                var clsPct = cls && cls.lines ? (cls.lines.percent || 0) : 0;
                var totPct = (d.totals && d.totals.lines) ? (d.totals.lines.percent || 0) : 0;

                badge.textContent = clsPct.toFixed(0) + '% · ' + totPct.toFixed(2) + '%';
                badge.title = 'Classe: ' + clsPct.toFixed(2) + '%  |  Projeto: ' + totPct.toFixed(2) + '%';

                if (clsPct >= 50)       badge.classList.add('cv-run-badge-ok');
                else if (clsPct > 0)    badge.classList.add('cv-run-badge-low');
                else                    badge.classList.add('cv-run-badge-zero');
            })
            .catch(function () {
                badge.textContent = '—';
            });
    }

    function openDrawer(c) {
        // Extrair o path relativo ao coverage
        var rel = (c.file || '')
            .replace(/^.*?\/(?:beaver-framework2?|project|app-root)\//i, '')
            .replace(/^\/+/, '')
            .replace(/\.php$/, '.php.html');

        var url = '/api/frankei/terminal/coverage-inline?file=' + encodeURIComponent('current/coverage/' + rel);

        // 1) Usa a Modal B (fkModal) em vez de criar .cv-drawer-backdrop
        var modal = document.getElementById('fkModal');
        if (!modal) {
            console.warn('[coverage-view] fkModal nao encontrada — fallback para drawer antigo');
            openDrawerLegacy(c, rel, url);
            return;
        }
        var body = modal.querySelector('.fk-modal-body');
        if (!body) {
            console.warn('[coverage-view] .fk-modal-body nao encontrado');
            return;
        }

        // 2) Injeta conteudo na Modal B
        body.innerHTML = ''
            + '<div class="cv-modal-layout">'
            +   '<div class="cv-modal-main">'
            +     '<div class="cv-modal-head">'
            +       '<div class="cv-modal-head-main">'
            +         '<h3>'
            +           '<span class="cv-modal-copy-target" id="fkCvCopyFqcn" data-copy="' + escapeHtml(c.name || '') + '" title="Clica para copiar o nome completo (FQCN)">'
            +             escapeHtml(c.shortName || c.name || '')
            +           '</span>'
            +         '</h3>'
            +         '<div class="cv-modal-path">'
            +           '<span class="cv-modal-copy-target" id="fkCvCopyPath" data-copy="' + escapeHtml(c.file || '') + '" title="Clica para copiar o path do ficheiro">'
            +             escapeHtml(rel)
            +           '</span>'
            +         '</div>'
            +       '</div>'
            +     '</div>'
            +     '<div class="cv-modal-filters">'
            +       '<button type="button" class="fk-filter-btn active" data-fk-filter="all">\u{1F4CB} Todas</button>'
            +       '<button type="button" class="fk-filter-btn" data-fk-filter="good">\u{1F7E2} Cobertas</button>'
            +       '<button type="button" class="fk-filter-btn" data-fk-filter="bad">\u{1F534} Nao cobertas</button>'
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
            +     '<button type="button" class="cv-modal-action" data-cv-action="lista">'
            +       '<span class="cv-modal-action-icon">\u{1F4CB}</span> Lista principal'
            +     '</button>'
            +     '<button type="button" class="cv-modal-action" data-cv-action="nova-aba">'
            +       '<span class="cv-modal-action-icon">\u{1F195}</span> Nova aba'
            +     '</button>'
            +     '<div class="cv-modal-sep"></div>'
            +     '<button type="button" class="cv-modal-action cv-modal-action-primary" data-cv-action="criar-teste">'
            +       '<span class="cv-modal-action-icon">\u2795</span> Criar teste'
            +     '</button>'
            +     '<button type="button" class="cv-modal-action cv-modal-action-secondary" data-cv-action="correr-teste" id="fkCvRunTestsBtn">'
            +       '<span class="cv-modal-action-icon">\u25B6</span> Correr testes'
            +       '<span class="cv-run-badge" id="fkCvRunBadge">…</span>'
            +     '</button>'
            +   '</aside>'
            + '</div>';

        // 3) Abre a modal
        modal.classList.add('open');

        // Actualizar badge de cobertura
        updateRunBadge(c && c.name ? c.name : '');




        // ─── Fechar a Modal B (só o ✕; sem handlers globais) ───
        (function bindCloseB() {
            if (modal.dataset.fkCloseBound === '1') return;
            modal.dataset.fkCloseBound = '1';

            var closeBtn = modal.querySelector('#fkModalClose');
            if (!closeBtn) return;

            closeBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                modal.classList.remove('open');
            });
        })();


        // ─── Copiar FQCN / path ao clicar ───
        (function bindCopyTargets() {
            ['fkCvCopyFqcn', 'fkCvCopyPath'].forEach(function (id) {
                var el = body.querySelector('#' + id);
                if (!el || el.dataset.fkCopyBound === '1') return;
                el.dataset.fkCopyBound = '1';

                el.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();

                    var text = el.dataset.copy || '';
                    if (!text) return;

                    var done = function () {
                        var orig = el.textContent;
                        el.classList.add('cv-modal-copy-flash');
                        setTimeout(function () { el.classList.remove('cv-modal-copy-flash'); }, 900);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(done).catch(function () {
                            fallback(text, done);
                        });
                    } else {
                        fallback(text, done);
                    }
                });
            });

            function fallback(texto, cb) {
                var ta = document.createElement('textarea');
                ta.value = texto;
                ta.style.cssText = 'position:fixed;opacity:0;';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); cb(); } catch (e) {}
                document.body.removeChild(ta);
            }
        })();

        // 3b) Handlers da sidebar de navegacao
        var side = body.querySelector('.cv-modal-side');
        if (side) {
            side.addEventListener('click', function (ev) {
                var btn = ev.target.closest('[data-cv-action]');
                if (!btn) return;
                var action = btn.getAttribute('data-cv-action');
                var ifr = body.querySelector('.cv-modal-code iframe');

                if (action === 'voltar') {
                    close();
                } else if (action === 'recarregar') {
                    if (ifr) { ifr.src = ifr.src; }
                } else if (action === 'lista') {
                    close();
                    var cvRoot = document.querySelector('.cv');
                    if (cvRoot) cvRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else if (action === 'nova-aba') {
                    window.open(url, '_blank', 'noopener');
                } else if (action === 'correr-teste') {
                    ev.preventDefault(); ev.stopPropagation();

                    var lastInfo  = window.__fkLastCoverageClass || {};
                    var fqcn      = (lastInfo.name || cls2 || '').replace(/^\\+/, '');
                    var parts     = fqcn.split('\\');
                    var shortName = parts[parts.length - 1] || '';
                    var filter    = shortName ? (shortName + 'Test') : '';

                    if (!filter) {
                        alert('Não sei o nome da classe.');
                        return;
                    }

                    btn.disabled = true;
                    var origHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="cv-modal-action-icon">\u23F3</span> A correr…';

                    var cmd = 'beaver test --filter=' + filter + ' --coverage --engine=pest';

                    fetch('/api/frankei/terminal/exec', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ command: cmd })
                    })
                    .then(function(r){ return r.json(); })
                    .then(function(d){
                        btn.disabled = false;
                        btn.innerHTML = origHtml;

                        if (d && d.ok === false) {
                            alert('Falha: ' + (d.error || 'erro desconhecido') + '\n\nVê o terminal para detalhes.');
                            return;
                        }

                        // Recarregar o iframe para mostrar o novo coverage
                        if (ifr) {
                            ifr.src = ifr.src;
                        }

                        setTimeout(function () {
                            var cls = window.__fkLastCoverageClass || {};
                            if (cls.name) updateRunBadge(cls.name);
                        }, 1500);
                    })
                    .catch(function(e){
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                        alert('Erro de rede: ' + e.message);
                    });

                } else if (action === 'criar-teste') {
                    if (window.FrankeiTestCreator && typeof window.FrankeiTestCreator.open === 'function') {
                        var cls = (c && (c.name || c.class)) || '';
                        window.FrankeiTestCreator.open({ class: cls, file: c.file || '' });
                    } else {
                        alert('Módulo de criação de testes não carregado.');
                    }
                }
            });
        }

        // 4) Handlers de fecho (backdrop + ESC)
        var close = function () {
            modal.classList.remove('open');
        };
        modal.addEventListener('click', function onBackdrop(e) {
            if (e.target === modal) {
                close();
                modal.removeEventListener('click', onBackdrop);
            }
        });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                close();
                document.removeEventListener('keydown', esc);
            }
        });

        // 5) Filtros dentro da modal
        body.querySelectorAll('.fk-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                body.querySelectorAll('.fk-filter-btn').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                var filter = btn.dataset.fkFilter;
                var iframe = body.querySelector('iframe');
                if (iframe && iframe.contentWindow) {
                    try {
                        iframe.contentWindow.postMessage({
                            type: 'fk-filter',
                            value: filter
                        }, '*');
                    } catch (err) {
                        // cross-origin — ignorar
                    }
                }
            });
        });
    }

    // Fallback: versao antiga (drawer solto) — so usada se a fkModal nao existir
    function openDrawerLegacy(c, rel, url) {
        var backdrop = document.createElement('div');
        backdrop.className = 'cv-drawer-backdrop';
        backdrop.innerHTML = ''
            + '<div class="cv-drawer">'
            +   '<div class="cv-drawer-head">'
            +     '<div>'
            +       '<h3>' + escapeHtml(c.shortName) + '</h3>'
            +       '<div class="cv-drawer-path">' + escapeHtml(rel) + '</div>'
            +     '</div>'
            +     '<button class="cv-drawer-close" aria-label="Fechar">\u00D7</button>'
            +   '</div>'
            +   '<div class="cv-drawer-body">'
            +     '<iframe src="' + url + '" loading="lazy"></iframe>'
            +   '</div>'
            + '</div>';
        document.body.appendChild(backdrop);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                backdrop.classList.add('open');
            });
        });
        var close = function () {
            backdrop.classList.remove('open');
            setTimeout(function () { backdrop.remove(); }, 220);
        };
        backdrop.querySelector('.cv-drawer-close').addEventListener('click', close);
        backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
        });
    }

    // ─── Render principal ───
    function render(container, data) {
        if (!data || !data.classes) {
            container.innerHTML = '<div class="cv-empty"><div class="cv-empty-icon">📭</div><p>Sem dados de coverage. Gera um report primeiro.</p></div>';
            return;
        }

        var html = ''
            + '<div class="cv">'
            +   '<div class="cv-header">'
            +     '<div>'
            +       '<h2>Coverage Report</h2>'
            +       '<div class="cv-meta">Gerado em ' + new Date((data.generated || 0) * 1000).toLocaleString('pt-PT') + '</div>'
            +     '</div>'
            +     '<div class="cv-header-actions">'
            +       '<button class="cv-btn cv-btn-ghost" id="cvExportJson">📥 Exportar JSON</button>'
            +       '<button class="cv-btn cv-btn-primary" id="cvRefresh">🔄 Recarregar</button>'
            +     '</div>'
            +   '</div>'
            +   renderTotals(data.totals)
            +   '<div class="cv-tabs">'
            +     '<button class="cv-tab active" data-cv-view="all">📋 Todas as classes</button>'
            +     '<button class="cv-tab" data-cv-view="insufficient">⚠️ Cobertura insuficiente</button>'
            +     '<button class="cv-tab" data-cv-view="complex">🧩 Complexidade alta</button>'
            +   '</div>'
            +   '<div class="cv-content">'
            +     '<div class="cv-filters">'
            +       '<input type="text" class="cv-search" id="cvSearch" placeholder="🔍 Procurar classe...">'
            +       '<button class="cv-chip active" data-cv-filter="all">Todas</button>'
            +       '<button class="cv-chip" data-cv-filter="bad">🔴 Críticas (&lt;50%)</button>'
            +       '<button class="cv-chip" data-cv-filter="ok">🟡 Médias (50-80%)</button>'
            +       '<button class="cv-chip" data-cv-filter="good">🟢 Boas (&gt;80%)</button>'
            +     '</div>'
            +     '<div id="cvTableWrap"></div>'
            +   '</div>'
            + '</div>';

        container.innerHTML = html;

        // ─── Estado ───
        var state = {
            classes: data.classes,
            filter: 'all',
            search: '',
            view: 'all'
        };

        function applyFilters() {
            var list = state.classes.slice();

            // View
            if (state.view === 'insufficient') {
                list = list.filter(function (c) { return c.lines.percent < 50; });
            } else if (state.view === 'complex') {
                list = list.filter(function (c) { return c.crap >= 30; });
                list.sort(function (a, b) { return b.crap - a.crap; });
            }

            // Filter
            if (state.filter !== 'all') {
                list = list.filter(function (c) {
                    var p = c.lines.percent;
                    if (state.filter === 'bad')  return p < 50;
                    if (state.filter === 'ok')   return p >= 50 && p < 80;
                    if (state.filter === 'good') return p >= 80;
                    return true;
                });
            }

            // Search
            if (state.search) {
                var q = state.search.toLowerCase();
                list = list.filter(function (c) {
                    return c.name.toLowerCase().indexOf(q) >= 0
                        || c.shortName.toLowerCase().indexOf(q) >= 0
                        || (c.package || '').toLowerCase().indexOf(q) >= 0;
                });
            }

            return list;
        }

        function rerender() {
            var list = applyFilters();
            var wrap = document.getElementById('cvTableWrap');
            if (!list.length) {
                wrap.innerHTML = '<div class="cv-empty"><div class="cv-empty-icon">🔍</div><p>Nenhuma classe encontrada</p></div>';
                return;
            }
            wrap.innerHTML = renderTable(list);
            wrap.querySelectorAll('tr[data-class]').forEach(function (tr) {
                tr.addEventListener('click', function () {
                    var c = state.classes.filter(function (x) { return x.name === tr.dataset.class; })[0];
                    if (!c) return;

                    window.__fkLastCoverageClass = {
                        name:  String(c.name || ''),
                        short: String(c.shortName || ''),
                        file:  String(c.file || '')
                    };

                    openDrawer(c);
                });
            });
        }

        // ─── Eventos ───
        document.getElementById('cvSearch').addEventListener('input', function (e) {
            state.search = e.target.value;
            rerender();
        });

        document.querySelectorAll('.cv-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                document.querySelectorAll('.cv-chip').forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');
                state.filter = chip.dataset.cvFilter;
                if (typeof window.__fkNotifyIframe === 'function') {
                    window.__fkNotifyIframe(state.filter);
                }
                rerender();
            });
        });

        document.querySelectorAll('.cv-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.cv-tab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                state.view = tab.dataset.cvView;
                rerender();
            });
        });

        document.getElementById('cvExportJson').addEventListener('click', function () {
            var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'coverage-' + Date.now() + '.json';
            a.click();
            URL.revokeObjectURL(url);
        });

        document.getElementById('cvRefresh').addEventListener('click', function () {
            if (typeof window.CoverageView.reload === 'function') {
                window.CoverageView.reload(container);
            }
        });

        rerender();
    }

    // ─── API ───
    window.CoverageView = {
        render: render,
        renderPanel: function (filter) {
            // Função renderFilteredPanel está no IIFE 1 — chamar via window
            if (typeof window.__fkRenderFilteredPanel === 'function') {
                return window.__fkRenderFilteredPanel(filter || 'all');
            }
            console.warn('[CoverageView] __fkRenderFilteredPanel indisponível');
            return 0;
        },
        openClassFile: function (item) {
            if (typeof openClassFile === 'function') {
                return openClassFile(item);
            }
            console.warn('[CoverageView] openClassFile indisponível');
        },
        rebuildPanel: function (filter) {
            if (typeof window.__fkRenderFilteredPanel === 'function') {
                return window.__fkRenderFilteredPanel(filter || 'all');
            }
            return 0;
        },
        reload: function (container) {
            // NÃO esvaziar o container — mantém o conteúdo antigo visível
            // e mete um overlay DENTRO do #cvTableWrap
            var wrap = container.querySelector('#cvTableWrap');

            if (wrap && !wrap.querySelector('.fk-cv-loading-overlay')) {
                var ov = document.createElement('div');
                ov.className = 'fk-cv-loading-overlay';
                ov.innerHTML =
                    '<div class="fk-cv-loading-spinner"></div>' +
                    '<p>A carregar cobertura…</p>';
                wrap.appendChild(ov);
            }

            fetch('/api/frankei/terminal/coverage/data', { credentials: 'include' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    // render() substitui o container inteiro — o overlay
                    // antigo desaparece automaticamente
                    render(container, d);

                    setTimeout(function () {
                        if (typeof window.__fkRenderFilteredPanel === 'function') {
                            window.__fkRenderFilteredPanel('all');
                        }
                    }, 100);
                })
                .catch(function (e) {
                    if (wrap) {
                        var o = wrap.querySelector('.fk-cv-loading-overlay');
                        if (o) o.remove();
                    }
                    var view = container.querySelector('.cv-content') || container;
                    view.innerHTML =
                        '<div class="cv-empty">' +
                        '  <div class="cv-empty-icon">⚠️</div>' +
                        '  <p>Erro: ' + escapeHtml(e.message) + '</p>' +
                        '</div>';
                });
        }
    };

    /* ============================================================
     * Auto-arranque do painel
     * ============================================================ */
    (function bootPanel() {
        let attempts = 0;
        const tick = () => {
            attempts++;
            const wrap = document.getElementById('cvTableWrap');
            const rows = wrap?.querySelectorAll('.cv-table tbody tr').length || 0;

            if (rows > 0) {
                // Ligar chips ao painel
                document.querySelectorAll('.cv-chip').forEach(chip => {
                    if (chip.dataset.fkPanelBound === '1') return;
                    chip.addEventListener('click', function () {
                        const filter = chip.dataset.cvFilter || 'all';
                        setTimeout(() => window.__fkRenderFilteredPanel && window.__fkRenderFilteredPanel(filter), 60);
                    });
                    chip.dataset.fkPanelBound = '1';
                });

                // Forçar fetch fresco para o painel não ficar com dados HTML antigos
                if (typeof window.CoverageView.reload === 'function') {
                    window.CoverageView.reload(wrap);
                    console.log('[CoverageView] painel inicial — reload fresh com', rows, 'linhas');
                } else if (typeof window.__fkRenderFilteredPanel === 'function') {
                    window.__fkRenderFilteredPanel('all');
                    console.log('[CoverageView] painel inicial OK com', rows, 'linhas');
                }
                return;
            }

            if (attempts < 60) {
                setTimeout(tick, 100);
            }
        };
        tick();
    })();


    /* ============================================================
     * Adicionar renderPanel à API existente
     * ============================================================ */
    if (typeof window.CoverageView === 'object' && window.CoverageView) {
        window.CoverageView.renderPanel = function (filter) {
            if (typeof window.__fkRenderFilteredPanel === 'function') {
                return window.__fkRenderFilteredPanel(filter || 'all');
            }
            console.warn('[CoverageView] __fkRenderFilteredPanel indisponível');
            return 0;
        };

        window.CoverageView.rebuildPanel = function (filter) {
            if (typeof window.__fkRenderFilteredPanel === 'function') {
                return window.__fkRenderFilteredPanel(filter || 'all');
            }
            return 0;
        };

        window.CoverageView.openClassFile = function (item) {
            if (typeof window.__fkOpenClassFile === 'function') {
                return window.__fkOpenClassFile(item);
            }
        };

        console.log('[CoverageView] API estendida com renderPanel (via window.__fk*)');
    }

})(window, document);


/* ==== FK TEST CREATOR GLOBAL DELEGATE ==== */
/* Instalado no fim do ficheiro — não depende do openDrawer.
   Apanha qualquer clique em [data-cv-action] e age em conformidade. */
(function () {
    'use strict';

    if (window.__fkCvGlobalDelegate) return;
    window.__fkCvGlobalDelegate = true;

    document.addEventListener('click', function (ev) {
        var btn = ev.target && ev.target.closest && ev.target.closest('[data-cv-action]');
        if (!btn) return;

        // Ignorar se não estiver dentro da modal (evita conflitos)
        var modal = document.getElementById('fkModal');
        if (!modal || !modal.contains(btn)) return;

        var action = btn.getAttribute('data-cv-action');
        var body   = modal.querySelector('.fk-modal-body');
        var ifr    = body ? body.querySelector('.cv-modal-code iframe') : null;
        var url2   = body && body.dataset ? (body.dataset.fkCurrentUrl   || '') : '';
        var cls2   = body && body.dataset ? (body.dataset.fkCurrentClass || '') : '';
        var file2  = body && body.dataset ? (body.dataset.fkCurrentFile  || '') : '';

        // fallback: se ainda não temos dados no body, procurar no título
        if (!cls2) {
            var h3 = body && body.querySelector('.cv-modal-head h3');
            if (h3) cls2 = (h3.textContent || '').trim();
        }

        function closeModal() {
            modal.classList.remove('open');
        }

        if (action === 'voltar') {
            ev.preventDefault(); ev.stopPropagation();
            closeModal();

        } else if (action === 'recarregar') {
            ev.preventDefault(); ev.stopPropagation();
            if (ifr) ifr.src = ifr.src;

        } else if (action === 'lista') {
            ev.preventDefault(); ev.stopPropagation();
            closeModal();
            var cvRoot = document.querySelector('.cv');
            if (cvRoot) cvRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });

        } else if (action === 'nova-aba') {
            ev.preventDefault(); ev.stopPropagation();
            if (url2) window.open(url2, '_blank', 'noopener');

        } else if (action === 'criar-teste') {
            ev.preventDefault(); ev.stopPropagation();

            if (!window.FrankeiTestCreator || typeof window.FrankeiTestCreator.open !== 'function') {
                alert('Módulo de criação de testes não carregado.');
                return;
            }

            // Preferir o contexto guardado ao clicar na <tr>
            var info = window.__fkLastCoverageClass || {};

            var fqcn = info.name || cls2 || '';
            var file = info.file || file2 || '';

            window.FrankeiTestCreator.open({
                class: fqcn,
                file:  file
            });
        }
    }, true);
})();

/* ==== FK COVERAGE REOPEN ON ROW CLICK ==== */
/* Se o modal está aberto e o utilizador clica numa <tr data-class>,
   fecha o modal atual e deixa o clique "passar" para a nova linha. */
(function () {
    'use strict';

    document.addEventListener('click', function (ev) {
        var modal = document.getElementById('fkModal');
        if (!modal || !modal.classList.contains('open')) return;

        // Só nos interessa cliques fora do modal
        if (modal.contains(ev.target)) return;

        var tr = ev.target.closest && ev.target.closest('tr[data-class]');
        if (!tr) return;

        // Fecha o modal atual (dispatch de Escape para o handler global)
        try {
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        } catch (_) {
            modal.classList.remove('open');
        }

        // Deixa o clique "chegar" à tabela — re-dispatch depois de fechar
        setTimeout(function () {
            var novo = document.querySelector(
                'tr[data-class][data-class="' + tr.dataset.class.replace(/"/g, '\\"') + '"]'
            );
            if (novo && novo !== tr) {
                novo.click();
            } else {
                tr.click();
            }
        }, 60);
    }, true);   // capture=true corre antes de outros handlers

})();
