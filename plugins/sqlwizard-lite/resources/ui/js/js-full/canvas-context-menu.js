/* SqlWizard · Menu de contexto do canvas (clique direito no vazio) */
window.SqlWizardCanvasMenu = (function (window, document) {
    'use strict';

    var menuEl = null;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function items() {
        return [
            { action: 'zoom-in',      icon: '➕',  label: 'Zoom in',          hint: 'Ctrl + scroll' },
            { action: 'zoom-out',     icon: '➖',  label: 'Zoom out' },
            { action: 'zoom-reset',   icon: '🎯',  label: 'Reset zoom',       hint: '100%' },
            { action: 'zoom-fit',     icon: '▣',   label: 'Ajustar à vista' },
            { type: 'sep' },
            { action: 'export-sql',   icon: '⬇️',  label: 'Exportar SQL…' },
            { action: 'export-json',  icon: '📄',  label: 'Exportar JSON' },
            { action: 'import-json',  icon: '📥',  label: 'Importar schema…' },
            { action: 'migration',    icon: '🦫',  label: 'Gerar Migration…' },
            { type: 'sep' },
            { action: 'venn-wizard',  icon: '🎯',  label: 'Venn SQL Wizard…' },
            { action: 'library',      icon: '📚',  label: 'Library (templates SQL)…' },
            { action: 'auto-layout',  icon: '🎨',  label: 'Auto-layout (grafo)' },
            { action: 'select-all',   icon: '☑️',  label: 'Selecionar tudo' },
            { action: 'stats',        icon: '📊',  label: 'Estatísticas' },
            { type: 'sep' },
            { action: 'clear',        icon: '🧹',  label: 'Limpar canvas',  danger: true }
        ];
    }

    function build() {
        var el = document.createElement('div');
        el.className = 'cm-backdrop';
        el.innerHTML =
            '<div class="cm-panel">' +
            '  <div class="cm-header">' +
            '    <span class="cm-title">Canvas</span>' +
            '    <button class="cm-close" id="cm-close">✕</button>' +
            '  </div>' +
            '  <div class="cm-list" id="cm-list"></div>' +
            '</div>';

        document.body.appendChild(el);
        el.querySelector('#cm-close').onclick = close;
        el.addEventListener('click', function (e) {
            if (e.target === el) close();
        });

        return el;
    }

    function render() {
        var list = menuEl.querySelector('#cm-list');

        list.innerHTML = items().map(function (it) {
            if (it.type === 'sep') return '<div class="cm-sep"></div>';

            return '<button class="cm-item' + (it.danger ? ' is-danger' : '') + '" ' +
                   '        data-action="' + esc(it.action) + '" type="button">' +
                   '  <span class="cm-icon">' + it.icon + '</span>' +
                   '  <span class="cm-label">' + esc(it.label) + '</span>' +
                   (it.hint ? '<span class="cm-hint">' + esc(it.hint) + '</span>' : '') +
                   '</button>';
        }).join('');

        list.querySelectorAll('.cm-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.getAttribute('data-action');
                close();
                dispatch(action);
            });
        });
    }

    function dispatch(action) {
        var canvas = window.SqlWizardCanvas;
        if (!canvas) return;

        switch (action) {
            case 'zoom-in':
                canvas.zoomAt(canvas.el.clientWidth / 2, canvas.el.clientHeight / 2, 1.2);
                break;

            case 'zoom-out':
                canvas.zoomAt(canvas.el.clientWidth / 2, canvas.el.clientHeight / 2, 1 / 1.2);
                break;

            case 'zoom-reset':
                canvas.zoom = 1; canvas.panX = 0; canvas.panY = 0;
                canvas.applyTransform(); canvas.saveView();
                break;

            case 'zoom-fit':
                canvas.fitToView();
                break;

            case 'export-sql':
                if (window.SqlWizardExportModal) window.SqlWizardExportModal.open('sql');
                else toast('Módulo de exportação indisponível', 'error');
                break;

            case 'export-json':
                if (window.SqlWizardExportModal) window.SqlWizardExportModal.open('json');
                else toast('Módulo de exportação indisponível', 'error');
                break;

            case 'import-json':
                if (window.SqlWizardImportModal) window.SqlWizardImportModal.open();
                else if (window.SqlWizardSchemaIO) window.SqlWizardSchemaIO.import();
                else toast('Módulo de importação indisponível', 'error');
                break;

            case 'migration':
                openMigration();
                break;

            case 'venn-wizard':
                if (window.SqlWizardVennWizard) window.SqlWizardVennWizard.open({});
                else toast('Módulo indisponível', 'error');
                break;

            case 'library':
                openLibrary();
                break;

            case 'auto-layout':
                if (window.SqlWizardLayout) {
                    window.SqlWizardLayout.applyToCanvas();
                    toast('Layout reorganizado', 'ok');
                }
                break;

            case 'select-all':
                canvas.selected = canvas.cards.slice();
                canvas.renderSelection();
                break;

            case 'stats':
                showStats();
                break;

            case 'clear':
                clearCanvas();
                break;
        }
    }

    /* ── Migration ─────────────────────────────────────── */

    function openMigration() {
        if (!window.SqlWizardMigrationModal) {
            toast('Módulo de migration indisponível', 'error');
            return;
        }

        var canvas   = window.SqlWizardCanvas;
        var explorer = window.SqlWizardExplorer;
        if (!canvas || !explorer) return;

        var drafts = [];

        canvas.cards.forEach(function (id) {
            for (var i = 0; i < explorer.drafts.length; i++) {
                if (explorer.drafts[i].id === id) {
                    drafts.push({
                        id:     explorer.drafts[i].id,
                        table:  explorer.drafts[i].table,
                        fields: explorer.drafts[i].fields || [],
                    });
                    break;
                }
            }
        });

        if (!drafts.length) {
            toast('Adiciona tabelas ao canvas primeiro', 'warn');
            return;
        }

        window.SqlWizardMigrationModal.open(drafts);
    }

    /* ── Library (templates SQL) ──────────────────────── */

    function openLibrary() {
        if (!window.LibraryPanel) {
            console.warn('[sqlwizard] LibraryPanel não está carregado');
            toast('Library não está disponível', 'error');
            return;
        }

        var selected = (window.SqlWizardCanvas && window.SqlWizardCanvas.selected) || [];
        var draftId  = selected[0] || null;
        var draft    = null;

        if (draftId && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
            for (var i = 0; i < window.SqlWizardExplorer.drafts.length; i++) {
                if (window.SqlWizardExplorer.drafts[i].id === draftId) {
                    draft = window.SqlWizardExplorer.drafts[i];
                    break;
                }
            }
        }

        var connId = (draft && draft.source) ? draft.source.connection_id : null;
        var dbName = (draft && draft.source) ? draft.source.database      : null;

        if (!connId && window.SqlWizardExplorer && window.SqlWizardExplorer.connections) {
            var conn = window.SqlWizardExplorer.connections[0];
            if (conn) { connId = conn.id; dbName = conn.selectedDb || null; }
        }

        var tables = [];
        if (window.SqlWizardCanvas && window.SqlWizardCanvas.cards) {
            window.SqlWizardCanvas.cards.forEach(function (id) {
                if (draft && draft.id === id) { tables.push(draft.table); return; }
                if (window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                    for (var j = 0; j < window.SqlWizardExplorer.drafts.length; j++) {
                        if (window.SqlWizardExplorer.drafts[j].id === id) {
                            tables.push(window.SqlWizardExplorer.drafts[j].table);
                            break;
                        }
                    }
                }
            });
        }
        if (!tables.length && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
            tables = window.SqlWizardExplorer.drafts.map(function (d) { return d.table; });
        }

        if (!window.Library) window.Library = {};
        if (!window.Library.panel) {
            window.Library.panel = window.LibraryPanel.create({ title: 'Library' });
        }
        window.Library.panel.open();

        function renderLibrary(root) {
            window.LibraryBrowser.render(root, {
                onItemSelected: function (library, item) {
                    window.LibraryEditor.render(root, library, item, {
                        connectionId: connId, database: dbName,
                        tables: tables, table: draft ? draft.table : null
                    });
                }
            });
        }

        var root = window.Library.panel.body();
        if (root && root.querySelector('#lib-content')) {
            renderLibrary(root);
        } else {
            var tries = 0;
            (function tick() {
                var r = window.Library.panel.body();
                if (r && r.querySelector('#lib-content')) renderLibrary(r);
                else if (tries++ < 15) setTimeout(tick, 100);
            })();
        }
    }

    function showStats() {
        var canvas = window.SqlWizardCanvas;
        var explorer = window.SqlWizardExplorer;
        if (!canvas || !explorer) return;

        var tables = canvas.cards.length;
        var totalCols = 0, totalFks = 0;

        canvas.cards.forEach(function (id) {
            var draft = null;
            for (var i = 0; i < explorer.drafts.length; i++) {
                if (explorer.drafts[i].id === id) { draft = explorer.drafts[i]; break; }
            }
            if (!draft) return;
            totalCols += (draft.fields || []).length;
            totalFks  += canvas.detectFKs(draft, explorer.drafts).length;
        });

        if (window.SqlWizardModal) {
            window.SqlWizardModal.confirm({
                title: 'Estatísticas do canvas',
                body:
                    '<div class="cm-stats">' +
                    '  <div class="cm-stat"><span>Tabelas no canvas</span><strong>' + tables + '</strong></div>' +
                    '  <div class="cm-stat"><span>Total de colunas</span><strong>' + totalCols + '</strong></div>' +
                    '  <div class="cm-stat"><span>Relações detetadas</span><strong>' + totalFks + '</strong></div>' +
                    '  <div class="cm-stat"><span>Zoom atual</span><strong>' + Math.round(canvas.zoom * 100) + '%</strong></div>' +
                    '</div>',
                confirmLabel: 'Fechar', cancelLabel: null, onConfirm: function () {}
            });
        } else {
            alert('Tabelas: ' + tables + '\nColunas: ' + totalCols + '\nRelações: ' + totalFks + '\nZoom: ' + Math.round(canvas.zoom * 100) + '%');
        }
    }

    function clearCanvas() {
        var canvas = window.SqlWizardCanvas;
        if (!canvas) return;
        if (!canvas.cards.length) { toast('O canvas já está vazio', 'warn'); return; }
        var n = canvas.cards.length;

        function doClear() {
            canvas.cards.slice().forEach(function (id) { canvas.remove(id); });
            canvas.selected = []; canvas.renderSelection(); canvas.drawEdges();
            toast(n + ' tabela' + (n === 1 ? '' : 's') + ' removida' + (n === 1 ? '' : 's') + ' do canvas', 'ok');
        }

        if (window.SqlWizardModal) {
            window.SqlWizardModal.confirm({
                title: 'Limpar canvas',
                body:
                    '<p>Vais remover <strong>' + n + '</strong> tabela' + (n === 1 ? '' : 's') + ' do canvas.</p>' +
                    '<p class="is-muted">Os drafts continuam guardados — podem ser abertos outra vez pelo explorer.</p>',
                confirmLabel: 'Limpar', cancelLabel: 'Cancelar', danger: true, onConfirm: doClear
            });
        } else if (confirm('Limpar o canvas? (' + n + ' tabelas)')) doClear();
    }

    function toast(msg, kind) {
        if (window.SqlWizardToast) {
            if (kind === 'ok')     SqlWizardToast.success(msg, { duration: 2000 });
            if (kind === 'warn')   SqlWizardToast.warning(msg, { duration: 2500 });
            if (kind === 'error')  SqlWizardToast.error(msg);
        } else {
            console.log('[canvas-menu]', msg);
        }
    }

    function open(x, y) {
        if (menuEl) menuEl.remove();
        menuEl = build();
        render();

        var panel = menuEl.querySelector('.cm-panel');
        var rect = panel.getBoundingClientRect();
        var w = rect.width  || 260;
        var h = rect.height || 400;

        panel.style.left = Math.max(8, Math.min(x, window.innerWidth  - w - 8)) + 'px';
        panel.style.top  = Math.max(8, Math.min(y, window.innerHeight - h - 8)) + 'px';
    }

    function close() {
        if (menuEl) { menuEl.remove(); menuEl = null; }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menuEl) close();
    });

    return { open: open, close: close };
})(window, document);
