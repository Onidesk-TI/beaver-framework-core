/* Frankei · bootstrap + scan + integração SqlWizard */
(function (window, document) {
    'use strict';

    var state = {
        panel: null,
        result: null,
        filter: { severity: null, category: null }
    };

    /* ── Boot ──────────────────────────────────────────────── */

       function boot() {
        var host = document.getElementById('frankei-host');
        if (!host) {
            // Sem host — modo embedado (dentro do SqlWizard), o painel é criado por quem chama
            window.Frankei = window.Frankei || {};
            window.Frankei.runScan = runScan;
            window.Frankei.setFilter = setFilter;
            window.Frankei.clearResults = clearResults;
            window.Frankei.updateBadges = updateBadges;

              // ← attachPanel apenas regista o painel (não substitui onAction/onLoad)
            window.Frankei.attachPanel = function (panel) {
                state.panel = panel;
            };

            registerListeners();
            return;
        }

        // A partir daqui: modo standalone (com #frankei-host)
        var panel = window.FrankeiPanel.create({ title: 'Frankei' });
        // Forçar modo docked (ancorar ao #frankei-host)
        try { localStorage.setItem('frankei:panelState', JSON.stringify({ docked: true, minimized: false })); } catch(e) {}
        panel.docked = true;
        panel.open();

        window.Frankei = window.Frankei || {};
        window.Frankei.panel = panel;
        window.Frankei.runScan = runScan;
        window.Frankei.setFilter = setFilter;
        window.Frankei.clearResults = clearResults;
        window.Frankei.updateBadges = updateBadges;

        panel.onAction = onAction;
        panel.onLoad = onLoad;

        state.panel = panel;
        registerListeners();
    }

    /* ── Eventos externos ──────────────────────────────────── */

    function registerListeners() {
        window.addEventListener('sqlwizard:database-selected', function (e) {
            var d = e.detail || {};
            if (!d.connectionId || !d.database) return;
            if (!state.panel) return;

            state.panel.setContext({
                connectionId: d.connectionId,
                database:     d.database
            });

            runScan({ silent: true });
        });

        window.addEventListener('sqlwizard:canvas-changed', function (e) {
            if (!state.panel) return;

            var ctx = state.panel.getContext ? state.panel.getContext() : {};
            if (!ctx.connectionId) return;

            clearTimeout(window.Frankei._canvasTimer);
            window.Frankei._canvasTimer = setTimeout(function () {
                window.FrankeiApi.scan({
                    connection_id: ctx.connectionId,
                    database:      ctx.database || '',
                    force:         true
                }).then(function (result) {
                    updateBadges(result);
                }).catch(function () { /* silencioso */ });
            }, 800);
        });
    }

    /* ── Handlers do painel ────────────────────────────────── */

    function onLoad() {
        if (state.result && state.panel) {
            window.FrankeiRender.renderAll(state.panel.body(), state.result, state.filter);
        }
    }

    function onAction(action) {
        if (action === 'scan')  return runScan({});
        if (action === 'clear') return clearResults();
    }

    /* ── Scan ──────────────────────────────────────────────── */

    function runScan(opts) {
        opts = opts || {};

        if (!state.panel) {
            console.warn('[frankei] painel não está pronto');
            return;
        }

        var ctx = state.panel.getContext ? state.panel.getContext() : {};
        if (!ctx.connectionId) {
            if (!opts.silent) showError('Sem ligação selecionada. Escolhe uma no explorer.');
            return;
        }

        if (!opts.silent) {
            setBusy(true);
            showLoading();
        }

        window.FrankeiApi.scan({
            connection_id: ctx.connectionId,
            database:      ctx.database || '',
            tables:        ctx.tables   || [],
            scanners:      []
        })
        .then(function (result) {
            state.result = result;
            state.filter = { severity: null, category: null };
            window.FrankeiRender.renderAll(state.panel.body(), result, state.filter);
            if (!opts.silent) switchTab('overview');
        })
        .catch(function (err) {
            if (!opts.silent) showError(err.message || 'Erro ao correr scan');
        })
        .finally(function () {
            if (!opts.silent) setBusy(false);
        });
    }

    /* ── Estado UI ─────────────────────────────────────────── */

    function setBusy(busy) {
        if (!state.panel) return;
        var body = state.panel.body();
        if (!body) return;
        var btn = body.querySelector('[data-act="scan"]');
        if (!btn) return;
        btn.disabled = !!busy;
        btn.textContent = busy ? '⏳ A correr…' : '▶ Correr scan';
    }

    function showLoading() {
        if (!state.panel) return;
        var overview = state.panel.body().querySelector('[data-panel="overview"]');
        if (overview) overview.innerHTML = '<div class="fk-empty">A analisar…</div>';
    }

    function showError(msg) {
        if (!state.panel) return;
        var overview = state.panel.body().querySelector('[data-panel="overview"]');
        if (overview) overview.innerHTML = '<div class="fk-empty fk-error-msg">' + msg + '</div>';
        switchTab('overview');
    }

    function switchTab(name) {
        if (!state.panel) return;
        var body = state.panel.body();
        if (!body) return;
        body.querySelectorAll('.fk-tab').forEach(function (t) {
            t.classList.toggle('is-active', t.getAttribute('data-tab') === name);
        });
        body.querySelectorAll('.fk-tab-panel').forEach(function (p) {
            p.classList.toggle('is-active', p.getAttribute('data-panel') === name);
        });
    }

    function clearResults() {
        state.result = null;
        state.filter = { severity: null, category: null };
        if (!state.panel) return;

        var body = state.panel.body();
        if (!body) return;

        body.querySelectorAll('.fk-tab-panel').forEach(function (p) {
            var name = p.getAttribute('data-panel');
            if (name === 'overview')    p.innerHTML = '<div class="fk-empty"><p>Corre um scan para ver o resumo.</p></div>';
            if (name === 'findings')    p.innerHTML = '<div class="fk-empty"><p>Sem problemas detetados.</p></div>';
            if (name === 'suggestions') p.innerHTML = '<div class="fk-empty"><p>Sem sugestões por agora.</p></div>';
            if (name === 'raw')         p.innerHTML = '<pre class="fk-raw"><code>// resultado em bruto</code></pre>';
        });
    }

    function setFilter(f) {
        state.filter = Object.assign({}, state.filter, f || {});
        if (state.result && state.panel) {
            var findings = state.panel.body().querySelector('[data-panel="findings"]');
            if (findings) window.FrankeiRender.renderFindings(findings, state.result, state.filter);
        }
    }

    /* ── Badges no canvas do SqlWizard ─────────────────────── */

    function updateBadges(result) {
        if (!window.Frankei) window.Frankei = {};

        var byTable = {};
        var order = { critical: 4, high: 3, medium: 2, low: 1, info: 0 };

        (result.findings || []).forEach(function (f) {
            if (!f.table) return;
            if (f.severity === 'info') return;

            if (!byTable[f.table]) byTable[f.table] = { total: 0, worst: 'low' };
            byTable[f.table].total++;

            if (order[f.severity] > order[byTable[f.table].worst]) {
                byTable[f.table].worst = f.severity;
            }
        });

        window.Frankei.tablesStats = byTable;

        // pede ao canvas para redesenhar (se existir)
        if (window.SqlWizardCanvas && typeof window.SqlWizardCanvas.drawEdges === 'function') {
            // dispara evento para o canvas atualizar os cards (o canvas pode ou não ouvir)
            window.dispatchEvent(new CustomEvent('frankei:stats-updated', {
                detail: { tables: byTable }
            }));
        }
    }

    /* ── Arranque ──────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})(window, document);