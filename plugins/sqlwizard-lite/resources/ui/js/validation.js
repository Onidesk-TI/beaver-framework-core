/* SqlWizard Lite — Validação de FK em tempo real */
window.SqlWizardValidation = (function (window, document) {
    'use strict';

    var badge = null;
    var panel = null;
    var pollTimer = null;

    /**
     * Valida o state inteiro e devolve lista de problemas.
     * @returns {Array<{level: 'error'|'warning'|'info', message: string, context?: object}>}
     */
    function validate(state) {
        var issues = [];
        var tables = state.tables || [];
        var relations = state.relations || [];

        // ─── Nomes duplicados ───
        var names = {};
        tables.forEach(function (t) {
            var n = (t.name || '').toLowerCase();
            if (names[n]) {
                issues.push({
                    level: 'error',
                    message: 'Nome de tabela duplicado: "' + t.name + '"',
                    context: { table_id: t.id }
                });
            }
            names[n] = true;
        });

        // ─── Tabelas vazias ───
        tables.forEach(function (t) {
            if (!t.columns || !t.columns.length) {
                issues.push({
                    level: 'warning',
                    message: 'Tabela "' + t.name + '" não tem colunas',
                    context: { table_id: t.id }
                });
            }
        });

        // ─── Tabelas sem PK ───
        tables.forEach(function (t) {
            if (!t.columns || !t.columns.length) return;
            var hasPk = t.columns.some(function (c) { return c.pk; });
            if (!hasPk) {
                issues.push({
                    level: 'warning',
                    message: 'Tabela "' + t.name + '" não tem chave primária',
                    context: { table_id: t.id }
                });
            }
        });

        // ─── Relações ───
        var seenRels = {};
        relations.forEach(function (r) {
            var a = tables.find(function (t) { return t.id === r.from; });
            var b = tables.find(function (t) { return t.id === r.to; });
            if (!a || !b) {
                issues.push({
                    level: 'error',
                    message: 'Relação aponta para tabela inexistente',
                    context: { relation: r }
                });
                return;
            }

            // Duplicada
            var key = r.from + '->' + r.to;
            if (seenRels[key]) {
                issues.push({
                    level: 'warning',
                    message: 'Relação duplicada: ' + a.name + ' → ' + b.name,
                    context: { table_id: a.id }
                });
            }
            seenRels[key] = true;

            // FK para tabela sem PK
            var hasPk = b.columns && b.columns.some(function (c) { return c.pk; });
            if (!hasPk) {
                issues.push({
                    level: 'error',
                    message: 'FK ' + a.name + ' → ' + b.name + ' aponta para tabela sem PK',
                    context: { table_id: a.id, relation: r }
                });
            }
        });

        // ─── Ciclos ───
        var cycleFound = hasCycle(tables, relations);
        if (cycleFound) {
            issues.push({
                level: 'warning',
                message: 'Existe um ciclo de relações entre tabelas'
            });
        }

        return issues;
    }

    function hasCycle(tables, relations) {
        var adj = {};
        tables.forEach(function (t) { adj[t.id] = []; });
        relations.forEach(function (r) { if (adj[r.from]) adj[r.from].push(r.to); });

        var visited = {};
        var visiting = {};
        var has = false;

        function dfs(id) {
            if (visiting[id]) { has = true; return; }
            if (visited[id]) return;
            visiting[id] = true;
            (adj[id] || []).forEach(dfs);
            visiting[id] = false;
            visited[id] = true;
        }
        tables.forEach(function (t) { dfs(t.id); });
        return has;
    }

    /* ─── Badge no header ─── */

    function getState() {
        if (window.SqlWizardCanvas && window.SqlWizardCanvas.getState) {
            try { return window.SqlWizardCanvas.getState(); } catch (e) {}
        }
        return { tables: [], relations: [] };
    }

    function computeLevel(issues) {
        var hasError = issues.some(function (i) { return i.level === 'error'; });
        if (hasError) return 'error';
        if (issues.length > 0) return 'warning';
        return 'ok';
    }

    function updateBadge() {
        var state = getState();
        var issues = validate(state);
        var level = computeLevel(issues);

        // Garantir badge
        if (!badge) {
            badge = document.getElementById('validation-badge');
            if (!badge) {
                badge = document.createElement('button');
                badge.id = 'validation-badge';
                badge.className = 'validation-badge';
                badge.type = 'button';
                badge.title = 'Ver problemas';

                var header = document.querySelector('.toolbar-center') || document.querySelector('.toolbar');
                if (header) header.appendChild(badge);

                badge.addEventListener('click', togglePanel);
            }
        }

        var icon = level === 'ok' ? '🟢' : level === 'warning' ? '🟡' : '🔴';
        var label = level === 'ok' ? 'Sem problemas' : issues.length + ' problema' + (issues.length === 1 ? '' : 's');

        badge.className = 'validation-badge ' + level;
        badge.innerHTML = '<span class="vb-dot">' + icon + '</span><span class="vb-label">' + label + '</span>';

        return issues;
    }

    /* ─── Painel de problemas ─── */

    function togglePanel() {
        if (panel) { closePanel(); return; }
        openPanel();
    }

    function openPanel() {
        if (panel) panel.remove();
        var issues = updateBadge();

        panel = document.createElement('div');
        panel.className = 'val-panel';
        panel.innerHTML =
            '<div class="val-head">' +
            '  <span class="val-title">🔍 Problemas encontrados</span>' +
            '  <button class="val-close">x</button>' +
            '</div>' +
            '<div class="val-body">' +
            (issues.length === 0
                ? '<div class="val-empty">🎉 Sem problemas no schema!</div>'
                : issues.map(function (i) {
                    var icon = i.level === 'error' ? '🔴' : i.level === 'warning' ? '🟡' : 'ℹ️';
                    var label = i.level === 'error' ? 'Erro' : i.level === 'warning' ? 'Aviso' : 'Info';
                    return '<div class="val-item ' + i.level + '">' +
                        '<span class="val-icon">' + icon + '</span>' +
                        '<div class="val-content">' +
                        '<div class="val-label">' + label + '</div>' +
                        '<div class="val-message">' + escape(i.message) + '</div>' +
                        '</div></div>';
                }).join('')
            ) +
            '</div>';

        document.body.appendChild(panel);

        panel.querySelector('.val-close').onclick = closePanel;
    }

    function closePanel() {
        if (panel) { panel.remove(); panel = null; }
    }

    function escape(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ─── Inicialização ─── */

    function start() {
        updateBadge();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(updateBadge, 2000);

        // Reagir a mudanças do canvas
        window.addEventListener('sqlwizard:state-changed', updateBadge);
    }

    function stop() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (panel) closePanel();
    }

    return {
        validate: validate,
        updateBadge: updateBadge,
        start: start,
        stop: stop,
        openPanel: openPanel,
        closePanel: closePanel
    };
})(window, document);
