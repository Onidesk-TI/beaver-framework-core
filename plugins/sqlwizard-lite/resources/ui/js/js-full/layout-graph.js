/* SqlWizard · Auto-layout em grafo */
window.SqlWizardLayout = (function (window, document) {
    'use strict';

    var COL_W = 340;
    var ROW_H = 360;
    var PAD   = 40;

    function buildGraph(drafts, onlyTables) {
        var byTable = {};
        drafts.forEach(function (d) {
            if (onlyTables && onlyTables.length && onlyTables.indexOf(d.table) === -1) return;
            byTable[d.table] = d;
        });

        var edges = [];
        Object.keys(byTable).forEach(function (t) {
            var draft = byTable[t];
            (draft.fields || []).forEach(function (field) {
                var s = String(field).trim();
                var m = s.match(/^([a-z0-9_]+):ref\.([a-z0-9_]+)/i);
                if (m && byTable[m[2]]) { edges.push({ from: t, to: m[2] }); return; }
                var m2 = s.match(/^([a-z0-9_]+)_id(?::.*)?$/i);
                if (m2) {
                    var base = m2[1];
                    var cands = [base, base+'s', base+'es', base.replace(/y$/, 'ies')];
                    for (var i = 0; i < cands.length; i++) {
                        if (byTable[cands[i]]) { edges.push({ from: t, to: cands[i] }); return; }
                    }
                }
            });
        });
        return { byTable: byTable, edges: edges };
    }

    function assignLevels(graph) {
        var incoming = {}, outgoing = {};
        Object.keys(graph.byTable).forEach(function (t) { incoming[t] = 0; outgoing[t] = []; });
        graph.edges.forEach(function (e) {
            if (!incoming.hasOwnProperty(e.to)) return;
            if (!outgoing.hasOwnProperty(e.from)) return;
            incoming[e.to]++;
            outgoing[e.from].push(e.to);
        });

        var levels = {}, queue = [];
        Object.keys(graph.byTable).forEach(function (t) {
            if (incoming[t] === 0) { levels[t] = 0; queue.push(t); }
        });

        // BFS: só processa cada nó UMA vez (evita loops em ciclos)
        var processed = {};
        var safety = 0;

        while (queue.length && safety++ < 10000) {
            var t = queue.shift();
            if (processed[t]) continue;
            processed[t] = true;

            outgoing[t].forEach(function (child) {
                if (processed[child]) return;
                var nl = levels[t] + 1;
                if (typeof levels[child] === 'undefined' || levels[child] < nl) {
                    levels[child] = nl;
                    queue.push(child);
                }
            });
        }

        var maxLvl = 0;
        Object.keys(levels).forEach(function (t) { if (levels[t] > maxLvl) maxLvl = levels[t]; });
        Object.keys(graph.byTable).forEach(function (t) {
            if (typeof levels[t] === 'undefined') levels[t] = maxLvl + 1;
        });

        return levels;
    }

    function applyToCanvas(onlyTables) {
        var canvas = window.SqlWizardCanvas;
        var explorer = window.SqlWizardExplorer;
        if (!canvas || !explorer) return;

        var graph = buildGraph(explorer.drafts || [], onlyTables || null);
        var names = Object.keys(graph.byTable);
        if (!names.length) return;

        var levels = assignLevels(graph);
        var byLevel = {};
        names.forEach(function (t) {
            var lvl = levels[t] || 0;
            if (!byLevel[lvl]) byLevel[lvl] = [];
            byLevel[lvl].push(t);
        });
        Object.keys(byLevel).forEach(function (lvl) { byLevel[lvl].sort(); });

        var positions = {};
        try { positions = JSON.parse(localStorage.getItem('sqlwizard:positions') || '{}'); }
        catch (e) { positions = {}; }

        Object.keys(byLevel).forEach(function (lvl) {
            var col = parseInt(lvl, 10);
            byLevel[lvl].forEach(function (t, row) {
                var draft = graph.byTable[t];
                if (!draft) return;
                var x = PAD + col * COL_W;
                var y = PAD + row * ROW_H;
                positions[draft.id] = { x: x, y: y };
                if (canvas.has && canvas.has(draft.id)) {
                    var card = canvas.el.querySelector('.card[data-id="' + draft.id + '"]');
                    if (card) { card.style.left = x + 'px'; card.style.top = y + 'px'; }
                }
            });
        });

        try { localStorage.setItem('sqlwizard:positions', JSON.stringify(positions)); } catch (e) {}
        if (canvas.drawEdges) canvas.drawEdges();
    }

    return { applyToCanvas: applyToCanvas, buildGraph: buildGraph, assignLevels: assignLevels };
})(window, document);
