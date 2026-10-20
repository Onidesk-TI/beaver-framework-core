/* ============================================================
 * Frankei — Coverage Inline Enhancer (v3)
 *
 * Corre dentro do iframe do coverage-inline (código-fonte PHP).
 *
 * Features:
 *   - Ícones nas breadcrumbs
 *   - Bolinhas coloridas + bold laranja na tabela de resumo
 *   - Marcadores ✓ / ✗ nas linhas do código
 *   - Badge global com cobertura média
 *   - Filtros rápidos (todas / cobertas / não cobertas)
 *   - Tooltip nas linhas com contagem de cobertura
 *   - Botão flutuante "fechar"
 *   - Copiar linha ao clicar
 *
 * @package    Beaver\Plugins\Frankei
 * @subpackage UI\Coverage
 * @author     Franco <franco@onidesk.local>
 * @copyright  (c) 2026 Onidesk / Beaver Framework
 * @license    Proprietary — All rights reserved
 * @since      Release 2.0.0
 * @version    3.0.0
 * ============================================================ */
(function () {
    'use strict';

    /* ---------- 12) Botao flutuante "voltar ao topo" ---------- */
    (function fkSetupScrollTopBtn() {
        if (window.__fkScrollTopReady) return;
        window.__fkScrollTopReady = true;

        // CSS
        var css = document.createElement('style');
        css.textContent = `
            #fkScrollTopBtn {
                position: fixed;
                right: 24px;
                bottom: 24px;
                z-index: 2000;
                width: 44px;
                height: 44px;
                border-radius: 50%;
                border: 1px solid rgba(126,155,250,0.5);
                background: rgba(20,30,50,0.92);
                color: #7e9bfa;
                font-size: 20px;
                line-height: 1;
                cursor: pointer;
                display: none;
                align-items: center;
                justify-content: center;
                box-shadow: 0 6px 20px -8px rgba(0,0,0,0.6);
                transition: background 0.15s, transform 0.15s, opacity 0.2s;
                opacity: 0;
            }
            #fkScrollTopBtn.visible {
                display: flex;
                opacity: 1;
            }
            #fkScrollTopBtn:hover {
                background: rgba(126,155,250,0.25);
                transform: translateY(-2px);
            }
            @media (prefers-reduced-motion: reduce) {
                #fkScrollTopBtn { transition: none; }
            }
        `;
        (document.head || document.documentElement).appendChild(css);

        // Botão
        var btn = document.createElement('button');
        btn.id = 'fkScrollTopBtn';
        btn.type = 'button';
        btn.title = 'Voltar ao topo';
        btn.setAttribute('aria-label', 'Voltar ao topo');
        btn.innerHTML = '&#8593;';  // seta para cima
        (document.body || document.documentElement).appendChild(btn);

        // Ação: volta ao topo e esconde
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            var scroller = document.querySelector('.container-fluid');
            if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
            btn.classList.remove('visible');
        });

        // Exposto globalmente para ser chamado por forceAnchorScroll
        window.__fkShowScrollTopBtn = function () {
            btn.classList.add('visible');
        };
        window.__fkHideScrollTopBtn = function () {
            btn.classList.remove('visible');
        };

        // ─── Aparece/desaparece automaticamente conforme o scroll ───
        var THRESHOLD = 250;   // px a partir do topo para mostrar

        function scrollTop(){
            var scroller = document.querySelector('.container-fluid');
            if (scroller && scroller.scrollTop > 0) return scroller.scrollTop;
            return window.pageYOffset || document.documentElement.scrollTop || 0;
        }

        function syncVisibility(){
            if (scrollTop() > THRESHOLD) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }

        // Listener no window
        window.addEventListener('scroll', syncVisibility, { passive: true });

        // Listener no container com scroll interno
        (function bindScroller(){
            var scroller = document.querySelector('.container-fluid');
            if (scroller) {
                scroller.addEventListener('scroll', syncVisibility, { passive: true });
            } else {
                // Se ainda não existe, tentar depois
                setTimeout(bindScroller, 500);
            }
        })();

        // Estado inicial
        syncVisibility();
    })();


    /* ---------- 11) Navegacao interna (breadcrumb + links /report/preview) ---------- */
    (function fkInternalNav() {
        if (window.__fkInternalNavBound) return;
        window.__fkInternalNavBound = true;

        // 1) Se houver <base target="_blank"> ou "_top", neutraliza-o
        var base = document.querySelector('base');
        if (base) {
            var t = (base.getAttribute('target') || '').toLowerCase();
            if (t === '_blank' || t === '_top' || t === '_parent') {
                base.removeAttribute('target');
            }
        }

        // 2) Intercepta cliques em links internos e forca navegacao no iframe
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a) return;

            var href = a.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#') return;          // anchors internos: deixar passar
            if (/^(https?:)?\/\//i.test(href)) return;          // links externos: deixar passar

            // Apenas links para o proprio coverage (preview / inline)
            if (!/\/report\/preview|\/coverage-inline/.test(href)) return;

            e.preventDefault();
            e.stopPropagation();
            window.location.href = href;   // navega DENTRO do iframe
        }, true);   // capture=true: apanha ANTES de qualquer outro handler
    })();


    // ─── Scroll fix: html/body sem scroll, só o container interno faz ───
    (function fkInlineScrollFix() {
        const id = 'fk-inline-scrollfix';
        if (document.getElementById(id)) return;

        // Não aplicar em páginas de ficheiro individual (PHPUnit class coverage).
        // O dashboard tem body próprio e funciona com flex+overflow:hidden;
        // os ficheiros de classe têm DUAS .container-fluid (header + body)
        // e o layout colapsa — daí o conteúdo parecer vazio.
        var isClassFile = /Code Coverage for /i.test(document.title || '')
                       || !!document.querySelector('table#code');
        if (isClassFile) return;

        const style = document.createElement('style');
        style.id = id;
        style.textContent = `
            html, body {
                height: 100% !important;
                max-height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }
            body {
                display: flex !important;
                flex-direction: column !important;
            }
            body > header {
                flex: 0 0 auto !important;
            }
            body > .container-fluid {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                overflow: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        `;
        (document.head || document.documentElement).appendChild(style);
    })();


    /* ---------- cores por % ---------- */

    function dotForPct(pct) {
        if (pct >= 80) return { dot: '🟢', color: '#16a34a', label: 'good'   };
        if (pct >= 50) return { dot: '🟡', color: '#b8860b', label: 'medium' };
        if (pct >   5) return { dot: '🟠', color: '#ea580c', label: 'high'   };
        return              { dot: '🔴', color: '#dc2626', label: 'low'    };
    }

    /* ---------- ícones breadcrumb ---------- */

    function iconForSegment(text, isLast) {
        const t = (text || '').trim().toLowerCase();
        if (isLast)                            return '📄';
        if (t === '/' || t === '')             return '🏠';
        if (t === 'app')                       return '📦';
        if (t === 'src')                       return '📚';
        if (t === 'sdk')                       return '🔌';
        if (t === 'plugins')                   return '🧩';
        if (t === 'tests')                     return '🧪';
        if (t === 'controllers')               return '⚙️';
        if (t === 'controller')                return '⚙️';
        if (t === 'models')                    return '🗄️';
        if (t === 'model')                     return '🗄️';
        if (t === 'views')                     return '👁️';
        if (t === 'services')                  return '🛠️';
        if (t === 'middleware')                return '🛡️';
        if (t === 'jobs' || t === 'queue')     return '⚡';
        if (t === 'scanners')                  return '🔎';
        if (t === 'security')                  return '🔒';
        if (t === 'performance')               return '🚀';
        if (t === 'code')                      return '📐';
        if (t === 'frankei')                   return '🔍';
        if (t === 'beaver-framework2')         return '🏠';
        return '📁';
    }

    /* ---------- 1) breadcrumbs ---------- */

    function decorateBreadcrumbs() {
        const items = document.querySelectorAll('.breadcrumb-item');
        if (!items.length) return 0;
        items.forEach((li, idx) => {
            if (li.dataset.fkIcon === '1') return;
            const link = li.querySelector('a');
            const text = (link ? link.textContent : li.textContent).trim();
            const isLast = idx === items.length - 1;
            const span = document.createElement('span');
            span.className = 'fk-crumb-icon';
            span.textContent = iconForSegment(text, isLast) + ' ';
            li.insertBefore(span, li.firstChild);
            li.dataset.fkIcon = '1';
            li.classList.add('fk-crumb-decorated');
        });
        return items.length;
    }

    /* ---------- 2) título ---------- */

    function decorateTitle() {
        const title = document.querySelector('h1, h2, .file-title, header h1');
        if (title && !title.dataset.fkTitle) {
            const t = (title.textContent || '').trim();
            if (t && !/📄/.test(t)) {
                title.insertAdjacentHTML('afterbegin', '<span class="fk-title-icon">📄</span> ');
                title.dataset.fkTitle = '1';
                return 1;
            }
        }
        return 0;
    }

    /* ---------- 3) linhas de resumo ---------- */

    function findPctFromRow(tr) {
        const cells = tr.querySelectorAll('td, th');
        for (const td of cells) {
            const txt = (td.textContent || '').replace(/\u00a0/g, ' ').trim();
            const m = txt.match(/^(\d+(?:[.,]\d+)?)\s*%/);
            if (m) return parseFloat(m[1].replace(',', '.'));
        }
        const all = (tr.textContent || '').match(/(\d+(?:[.,]\d+)?)\s*%/);
        return all ? parseFloat(all[1].replace(',', '.')) : 0;
    }

    function decorateSummaryRows() {
        let tables = document.querySelectorAll('table.table-bordered');
        if (!tables.length) tables = document.querySelectorAll('table.table');
        if (!tables.length) tables = document.querySelectorAll('table');

        let count = 0;

        tables.forEach(table => {
            if (table.id === 'code') return;
            if (table.querySelectorAll('tr').length < 2) return;

            const candidateRows = [];
            table.querySelectorAll('tbody tr').forEach(tr => candidateRows.push(tr));
            if (!candidateRows.length) {
                table.querySelectorAll('tr').forEach(tr => {
                    if (!tr.closest('thead')) candidateRows.push(tr);
                });
            }

            candidateRows.forEach(tr => {
                if (tr.dataset.fkSummary === '1') return;
                if (!tr.querySelector('td')) return;
                if (tr.querySelector('.fk-method-link, .fk-method-dot')) return;

                const pct = findPctFromRow(tr);
                const info = dotForPct(pct);

                const firstTd = tr.querySelector('td:first-child');
                if (!firstTd) return;

                if (!firstTd.querySelector('.fk-summary-dot')) {
                    const dot = document.createElement('span');
                    dot.className = 'fk-summary-dot';
                    dot.textContent = info.dot;
                    firstTd.insertBefore(dot, firstTd.firstChild);
                }

                // fkNameNotRed: o nome do metodo NAO deve ficar vermelho
                // (so a bolinha .fk-summary-dot fica colorida)
                firstTd.style.color = '';
                firstTd.style.fontWeight = '';

                tr.querySelectorAll('td').forEach(td => {
                    const txt = (td.textContent || '').trim();
                    if (/^\d+(?:[.,]\d+)?\s*%/.test(txt) ||
                        /^\d+\s*\/\s*\d+/.test(txt) ||
                        /^\d+$/.test(txt)) {
                        td.style.color = info.color;
                        td.style.fontWeight = '700';
                        td.style.fontFamily = 'ui-monospace, monospace';
                    }
                });

                tr.dataset.fkSummary = '1';
                tr.classList.add('fk-summary-row-' + info.label);
                count++;
            });
        });

        return count;
    }

    /* ---------- 4) linhas de código ---------- */

    function decorateCodeLines() {
        const table = document.getElementById('code');
        if (!table || table.dataset.fkLines === '1') return 0;

        let count = 0;
        table.querySelectorAll('tbody tr, tr').forEach(tr => {
            const firstCell = tr.querySelector('td:first-child');
            if (!firstCell) return;

            // PHPUnit 12:
            //   class="danger d-flex"  → linha NÃO coberta
            //   class=" d-flex"        → linha coberta (ou neutra)
            const cls = tr.className || '';
            const isNotCovered = cls.includes('danger');

            if (isNotCovered) {
                tr.dataset.fkCoverage = 'notcovered';
                firstCell.classList.add('fk-line-notcovered');
                if (firstCell.dataset.fkLine !== '1') {
                    firstCell.insertAdjacentHTML('afterbegin', '<span class="fk-line-marker">✗</span> ');
                    firstCell.dataset.fkLine = '1';
                }
            } else {
                tr.dataset.fkCoverage = 'covered';
                firstCell.classList.add('fk-line-covered');
                if (firstCell.dataset.fkLine !== '1') {
                    firstCell.insertAdjacentHTML('afterbegin', '<span class="fk-line-marker">✓</span> ');
                    firstCell.dataset.fkLine = '1';
                }
            }
            count++;
        });

        table.dataset.fkLines = '1';
        return count;
    }

    /* ---------- 5) métodos com <a> ---------- */

    function decorateMethodLinks() {
        const tables = document.querySelectorAll('table.table-bordered');
        let count = 0;

        tables.forEach(table => {
            if (table.id === 'code') return;
            table.querySelectorAll('tbody tr, tr').forEach(tr => {
                const a = tr.querySelector('td a');
                if (!a || a.dataset.fkIcon === '1') return;
                if (tr.querySelector('.fk-summary-dot')) return;

                const pct = findPctFromRow(tr);
                const info = dotForPct(pct);

                const wrap = document.createElement('span');
                wrap.className = 'fk-method-link fk-method-' + info.label;
                wrap.style.cssText =
                    'display:inline-flex;align-items:center;gap:6px;font-weight:700;'
                  + 'color:' + info.color + ';';

                const dot = document.createElement('span');
                dot.className = 'fk-method-dot';
                dot.textContent = info.dot;
                dot.style.cssText = 'font-size:13px;line-height:1;';

                const pctLabel = document.createElement('span');
                pctLabel.className = 'fk-method-pct';
                pctLabel.textContent = pct.toFixed(1) + '%';

                a.parentNode.insertBefore(wrap, a);
                wrap.appendChild(dot);
                wrap.appendChild(a);
                wrap.appendChild(pctLabel);

                a.style.color = 'inherit';
                a.style.textDecoration = 'none';
                a.style.fontWeight = '700';

                a.dataset.fkIcon = '1';
                count++;
            });
        });

        return count;
    }

    /* ---------- 6) badge global de cobertura ---------- */

    function injectGlobalStats() {
        if (document.querySelector('.fk-global-stats')) return 0;

        const table = document.querySelector('table.table-bordered');
        if (!table) return 0;

        const rows = table.querySelectorAll('tbody tr');
        let totalPct = 0, count = 0;

        rows.forEach(tr => {
            const m = (tr.textContent || '').match(/(\d+(?:[.,]\d+)?)\s*%/);
            if (m) {
                totalPct += parseFloat(m[1].replace(',', '.'));
                count++;
            }
        });

        if (!count) return 0;

        const avg = (totalPct / count).toFixed(1);

        const badge = document.createElement('div');
        badge.className = 'fk-global-stats';
        badge.innerHTML =
            '<span class="fk-gs-icon">📊</span>'
          + '<span class="fk-gs-label">Cobertura média</span>'
          + '<span class="fk-gs-value">' + avg + '%</span>'
          + '<span class="fk-gs-sep">·</span>'
          + '<span class="fk-gs-count">' + count + ' método(s)</span>';

        const codeTable = document.getElementById('code');
        if (!codeTable) return 0;

        const anchor = codeTable.closest('.fk-code-wrap') || codeTable;
        anchor.parentNode.insertBefore(badge, anchor);

        return 1;
    }

    /* ---------- 7) filtros rápidos ---------- */

    function injectFilters() {
        if (document.querySelector('.fk-line-filters')) return 0;

        const codeTable = document.getElementById('code');
        if (!codeTable) return 0;

        const anchor = codeTable.closest('.fk-code-wrap') || codeTable;

        // (chips removidos — vivem na Modal B; filtro chega por postMessage)
        const wrap = null;

        function applyFilter(mode) {
            // Compatibilidade com nomes antigos
            const map = {
                'covered':    'good',
                'partial':    'ok',
                'notcovered': 'bad',
            };
            const m = map[mode] || mode;

            codeTable.querySelectorAll('tbody tr, tr').forEach(tr => {
                const cov = tr.dataset.fkCoverage || '';
                tr.style.removeProperty('display');

                if (m === 'good' && cov !== 'covered')    tr.style.setProperty('display','none','important');
                if (m === 'ok'   && cov !== 'partial')    tr.style.setProperty('display','none','important');
                if (m === 'bad'  && cov !== 'notcovered') tr.style.setProperty('display','none','important');
            });
        }

        // 1) Filtro por clique local — removido (chips vivem na Modal B)

        // 2) Filtro por mensagem do parent (dashboard)
        window.addEventListener('message', function (ev) {
            if (!ev.data || ev.data.type !== 'fk-filter') return;
            const mode = ev.data.value || 'all';

            // Atualizar botão ativo
            (wrap?.querySelectorAll('.fk-filter-btn') || []).forEach(b => {
                b.classList.toggle('active', b.dataset.fkFilter === mode);
            });

            applyFilter(mode);
        });

        // Sincronizar com o dashboard (postMessage)
        window.addEventListener('message', function (ev) {
            if (!ev.data || ev.data.type !== 'fk-filter') return;
            const mode = ev.data.value || 'all';

            // Ativar o botão correspondente
            (wrap?.querySelectorAll('.fk-filter-btn') || []).forEach(b => {
                b.classList.toggle('active', b.dataset.fkFilter === mode);
            });

            // Aplicar filtro
            codeTable.querySelectorAll('tbody tr, tr').forEach(tr => {
                const cov = tr.dataset.fkCoverage || '';
                tr.style.removeProperty('display');

                if (mode === 'good' && cov !== 'covered')    tr.style.setProperty('display','none','important');
                if (mode === 'ok'   && cov !== 'partial')    tr.style.setProperty('display','none','important');
                if (mode === 'bad'  && cov !== 'notcovered') tr.style.setProperty('display','none','important');
            });
        });

        return 1;
    }


    /* ---------- 9) copiar linha ao clicar ---------- */

    function enableCopyLine() {
        const table = document.getElementById('code');
        if (!table || table.dataset.fkCopy === '1') return 0;

        table.addEventListener('click', function (ev) {
            const tr = ev.target.closest('tr');
            if (!tr) return;
            const codeCell = tr.querySelector('td:last-child');
            if (!codeCell) return;

            const text = (codeCell.textContent || '').trim();
            if (!text) return;

            navigator.clipboard.writeText(text).then(() => {
                tr.classList.add('fk-copied');
                setTimeout(() => tr.classList.remove('fk-copied'), 800);
            }).catch(() => {});
        });

        table.dataset.fkCopy = '1';
        return 1;
    }

    /* ---------- 10) realçar cabeçalhos ---------- */

    function enhanceTableHeaders() {
        const table = document.getElementById('code');
        if (!table || table.dataset.fkEnh === '1') return 0;

        table.querySelectorAll('thead tr').forEach(row => {
            row.querySelectorAll('th, td').forEach(cell => {
                const t = (cell.textContent || '').trim().toLowerCase();
                if (t === 'line' || t === 'linha') {
                    cell.innerHTML = '📏 ' + cell.innerHTML;
                }
            });
        });

        table.dataset.fkEnh = '1';
        return 1;
    }


    /* ---------- envolver a tabela #code num wrapper ---------- */
    // Isto permite que o overflow:hidden vá para o wrapper
    // e a tabela fique com overflow:visible (necessário para o scroll
    // até às âncoras #N funcionar).

    function wrapCodeTable() {
        const table = document.getElementById('code');
        if (!table) return 0;

        // Se a tabela já está dentro de um wrapper, não fazer nada.
        // Os elementos extras (badge, filtros) são inseridos ANTES do
        // wrapper pelas funções respetivas (com closest('.fk-code-wrap')).
        if (table.closest('.fk-code-wrap')) {
            return 0;
        }

        const wrap = document.createElement('div');
        wrap.className = 'fk-code-wrap';
        table.parentNode.insertBefore(wrap, table);
        wrap.appendChild(table);

        return 1;
    }

    /* ---------- scroll instantâneo para âncoras #N ---------- */

    function forceAnchorScroll() {
        document.addEventListener('click', function (ev) {
            const a = ev.target.closest('a[href^="#"]');
            if (!a) return;

            const hash = a.getAttribute('href');
            if (!hash || hash === '#') return;

            // IDs numéricos (#11, #201) não são seletores CSS válidos.
            // Usamos getElementById que aceita qualquer string.
            const id = hash.slice(1);
            const target = document.getElementById(id);
            if (!target) return;

            ev.preventDefault();
            try { history.pushState(null, '', hash); } catch (err) {}

            const tr = target.closest('tr') || target;

            // Encontrar o container que realmente faz scroll.
            // O <html>/<body> foram bloqueados pelo scrollfix; o scroll
            // vive no .container-fluid (ou num ancestral com overflow auto).
            let scroller = tr.parentElement;
            while (scroller && scroller !== document.body) {
                const cs = getComputedStyle(scroller);
                const canScroll = (cs.overflowY === 'auto' || cs.overflowY === 'scroll')
                                && scroller.scrollHeight > scroller.clientHeight;
                if (canScroll) break;
                scroller = scroller.parentElement;
            }
            if (!scroller || scroller === document.body) scroller = document.scrollingElement;

            const headerOffset = 130;  // folga para o breadcrumb + titulo do coverage
            const targetTop = tr.offsetTop;

            if (scroller.scrollTo) {
                scroller.scrollTo({ top: Math.max(0, targetTop - headerOffset), behavior: 'smooth' });
            } else {
                scroller.scrollTop = Math.max(0, targetTop - headerOffset);
            }

            tr.classList.add('fk-target-flash');
            setTimeout(() => tr.classList.remove('fk-target-flash'), 2500);

            // Mostra o botão "voltar ao topo"
            if (window.__fkShowScrollTopBtn) window.__fkShowScrollTopBtn();
        }, true);
    }

    /* ---------- arranca ---------- */

    function boot() {
        try {
            const c1  = decorateBreadcrumbs();
            const c2  = decorateTitle();
            const c3  = decorateSummaryRows();
            const c4  = decorateCodeLines();
            const c6  = enhanceTableHeaders();

            // IMPORTANTE: badge e filtros inseridos ANTES do wrapCodeTable
            // (assim ficam FORA do wrapper, que só vai envolver a tabela)
            const c7  = injectGlobalStats();
            const c8  = injectFilters();

            wrapCodeTable();   // só agora envolve a tabela num wrapper

            const c5  = 0;
            const c9  = enableCopyLine();
            forceAnchorScroll();

            console.log(
                '[FrankeiCoverage-inline] v3 aplicado:',
                'crumbs=' + c1,
                'title=' + c2,
                'summary=' + c3,
                'lines=' + c4,
                'headers=' + c6,
                'stats=' + c7,
                'filters=' + c8,
                'copy=' + c9
            );
        } catch (e) {
            console.warn('[FrankeiCoverage-inline] erro:', e);
        }
    }

    function schedule() {
        setTimeout(boot, 100);
        setTimeout(boot, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', schedule);
    } else {
        schedule();
    }
})();

    /* ---------- 13) Fallback: mostra o botão ao clicar em anchors ---------- */
    (function fkAnchorBtnFallback() {
        if (window.__fkAnchorBtnBound) return;
        window.__fkAnchorBtnBound = true;
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a[href^="#"]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href === '#') return;
            var id = href.slice(1);
            if (!document.getElementById(id)) return;
            setTimeout(function () {
                if (window.__fkShowScrollTopBtn) window.__fkShowScrollTopBtn();
            }, 300);
        }, true);
    })();

    /* ---------- 13) Botao "copiar código" dentro do fk-code-wrap ---------- */
    (function fkSetupCodeCopyBtn() {
        if (window.__fkCodeCopyReady) return;
        window.__fkCodeCopyReady = true;

        function tryInstall() {
            var wrap = document.querySelector('.fk-code-wrap');
            var table = document.getElementById('code');
            var anchor = wrap || table;
            if (!anchor) return false;
            if (anchor.querySelector('#fkCodeCopyBtn')) return true;

            var btn = document.createElement('button');
            btn.id = 'fkCodeCopyBtn';
            btn.type = 'button';
            btn.title = 'Copiar código desta classe';
            btn.innerHTML = '📋 Copiar código';
            btn.style.cssText = [
                'position:absolute',
                'top:10px',
                'right:14px',
                'z-index:50',
                'display:inline-flex',
                'align-items:center',
                'gap:6px',
                'padding:5px 12px',
                'border-radius:8px',
                'border:1px solid #d1d5db',
                'background:#ffffff',
                'color:#374151',
                'font-size:11.5px',
                'font-weight:600',
                'cursor:pointer',
                'box-shadow:0 1px 3px rgba(0,0,0,.06)',
                'transition:background .15s, border-color .15s, transform .15s'
            ].join(';');

            // Wrap precisa de position:relative para o botão absoluto
            var wrapStyle = getComputedStyle(anchor).position;
            if (wrapStyle === 'static') {
                anchor.style.position = 'relative';
            }

            btn.addEventListener('mouseenter', function () {
                btn.style.background = '#f3f4f6';
                btn.style.borderColor = '#9ca3af';
            });
            btn.addEventListener('mouseleave', function () {
                btn.style.background = '#ffffff';
                btn.style.borderColor = '#d1d5db';
            });

            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();

                if (!table) {
                    alert('Não encontrei a tabela de código.');
                    return;
                }

                var linhas = [];
                table.querySelectorAll('tr').forEach(function (tr) {
                    var td = tr.querySelector('td:last-child');
                    if (!td) return;
                    var txt = td.textContent.replace(/\s+$/g, '');
                    linhas.push(txt);
                });

                while (linhas.length && linhas[0].trim() === '') linhas.shift();
                var code = linhas.join('\n');

                if (!code) { alert('Código vazio.'); return; }

                var done = function () {
                    btn.innerHTML = '✅ Copiado';
                    btn.style.background = '#d1fae5';
                    btn.style.borderColor = '#6ee7b7';
                    btn.style.color = '#065f46';
                    setTimeout(function () {
                        btn.innerHTML = '📋 Copiar código';
                        btn.style.background = '#ffffff';
                        btn.style.borderColor = '#d1d5db';
                        btn.style.color = '#374151';
                    }, 1400);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(done).catch(function () {
                        fallback(code, done);
                    });
                } else {
                    fallback(code, done);
                }
            });

            function fallback(texto, cb) {
                var ta = document.createElement('textarea');
                ta.value = texto;
                ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); cb(); }
                catch (e) { alert('Falha ao copiar.'); }
                document.body.removeChild(ta);
            }

            anchor.appendChild(btn);
            return true;
        }

        // Tentar imediatamente e ao longo dos primeiros segundos
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(tryInstall, 100);
            });
        } else {
            setTimeout(tryInstall, 100);
        }
        [300, 800, 1500, 3000].forEach(function (t) { setTimeout(tryInstall, t); });
    })();
