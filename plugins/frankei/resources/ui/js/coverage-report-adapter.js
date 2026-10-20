/* ============================================================
 * Frankei — Coverage Report Adapter (v2)
 *
 * Converte a <table> do php-code-coverage v12 no layout
 * .coverage-row do Frankei.
 *
 * Estrutura alvo (PHPUnit 12.5.8):
 *   <tr>
 *     <td><img class="octicon"><a href="...">AuthController.php</a></td>
 *     <td><div class="progress-bar bg-*" aria-valuenow="86.49" style="width: 86.49%">…</div></td>
 *     <td><div>86.49%</div></td>
 *     <td><div>235&nbsp;/&nbsp;296</div></td>
 *     … (repetido para Functions e Classes)
 *   </tr>
 *
 * @package    Beaver\Plugins\Frankei
 * @subpackage UI\Coverage
 * @author     Franco <franco@onidesk.local>
 * @copyright  (c) 2026 Onidesk / Beaver Framework
 * @license    Proprietary — All rights reserved
 * @since      Release 2.0.0
 * @version    2.0.0
 * ============================================================ */
(function () {
    'use strict';

    /* ---------- helpers ---------- */

    function text(el) {
        return (el?.textContent || '').replace(/\u00a0/g, ' ').trim();
    }

    function pickPct(tr) {
        // 1) 1º .progress-bar da <tr> = linha "Lines" (é o que queremos)
        const bar = tr.querySelector('.progress-bar');
        if (bar) {
            const avn = bar.getAttribute('aria-valuenow');
            if (avn !== null && avn !== '') {
                const n = parseFloat(avn);
                if (!isNaN(n)) return n;
            }
            const style = bar.getAttribute('style') || '';
            const m = style.match(/width\s*:\s*([\d.]+)/);
            if (m) return parseFloat(m[1]);
        }

        // 2) fallback: primeiro <td> com "NN%"
        const cells = tr.querySelectorAll('td');
        for (const td of cells) {
            const m = text(td).match(/^([\d.]+)\s*%$/);
            if (m) return parseFloat(m[1]);
        }

        return null;
    }

    function pickCounts(tr) {
        // procura o primeiro <td> que só tenha "X / Y"
        const cells = tr.querySelectorAll('td');
        for (const td of cells) {
            const m = text(td).match(/^(\d+)\s*\/\s*(\d+)$/);
            if (m) {
                return {
                    covered: parseInt(m[1], 10),
                    total:   parseInt(m[2], 10)
                };
            }
        }
        return { covered: 0, total: 0 };
    }

    function pickNameAndHref(tr) {
        // o <a> da 1ª célula é o nome do ficheiro/classe
        const a = tr.querySelector('td:first-child a');
        if (!a) return { name: '', href: '' };

        const rawName = text(a);
        if (!rawName) return { name: '', href: '' };

        // Remove extensão .php/.html e normaliza "AuthController.php" -> "AuthController"
        let name = rawName.replace(/\.(php|html|htm)$/i, '');

        return {
            name,
            href: a.getAttribute('href') || ''
        };
    }

    /* ---------- conversão ---------- */

    function adaptTable(table) {
        const items = [];

        table.querySelectorAll('tbody tr, tr').forEach(tr => {
            // salta linhas de cabeçalho (só <td>&nbsp;</td>)
            if (!tr.querySelector('td')) return;

            const { name, href } = pickNameAndHref(tr);
            if (!name) return;

            // salta a linha "Total"
            if (/^(total|sum|overall)$/i.test(name)) return;

            const pct = pickPct(tr);
            if (pct === null) return;

            const { covered, total } = pickCounts(tr);

            items.push({
                name,
                pct,
                covered,
                total,
                path: href || name
            });
        });

        return items;
    }

    /* ---------- render ---------- */

    function waitForFrankei(cb, attempts = 0) {
        if (window.FrankeiCoverage) return cb();
        if (attempts > 50) return;
        setTimeout(() => waitForFrankei(cb, attempts + 1), 100);
    }

    function render(root) {
        const tables = root.querySelectorAll('table.table, table.coverage, table');
        let replaced = 0;

        tables.forEach(table => {
            const items = adaptTable(table);
            if (items.length < 1) return;

            const wrap = document.createElement('div');
            wrap.className = 'fk-report-list';
            wrap.innerHTML = window.FrankeiCoverage.renderList(items, { group: true });

            table.parentNode.replaceChild(wrap, table);
            window.FrankeiCoverage.animateFills(wrap);
            replaced++;
        });

        return replaced;
    }

    /* ---------- boot ---------- */

    function boot() {
        // Só correr no /report/preview (tabela de índice com classes)
        // NÃO correr no /coverage-inline (código-fonte do ficheiro)
        const url = location.href;
        const isInline = url.includes('/coverage-inline');
        const isPreview = url.includes('/report/preview');

        // Tabelas com as classes do PHPUnit de índice (Bootstrap index)
        const hasIndexTable =
            !!document.querySelector('table.table-bordered') &&
            !document.getElementById('code');   // #code é a tabela de código-fonte

        if (isInline || !hasIndexTable) {
            console.log('[FrankeiCoverage] não é página de índice — adapter ignorado');
            return;
        }

        waitForFrankei(() => {
            const ok = render(document.body);
            if (ok) {
                console.log('[FrankeiCoverage] report adaptado ✓ (' + ok + ' tabela(s))');
            } else {
                // Não é erro: a página pode já ter a tabela no layout Frankei (.cv-*)
                var hasCv = !!document.querySelector('.cv-table, .cv-table-card');
                console.log('[FrankeiCoverage] sem tabela PHPUnit para adaptar'
                    + (hasCv ? ' — já está no layout Frankei (.cv-*)' : ''));
                // Debug: mostra o que existe
                const tables = document.querySelectorAll('table');
                console.log('[FrankeiCoverage] tabelas encontradas:', tables.length);
                tables.forEach((t, i) => {
                    const rows = t.querySelectorAll('tr');
                    console.log(`  table ${i}: ${rows.length} tr`);
                    if (rows[0]) console.log('  1ª tr:', rows[0].outerHTML.slice(0, 300));
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
