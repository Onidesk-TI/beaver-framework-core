/* SqlWizard Lite — Auto-layout */
window.SqlWizardAutoLayout = (function (window, document) {
    'use strict';

    function layout() {
        var state = window.SqlWizardPreview.getState();
        var tables = state.tables || [];
        var relations = state.relations || [];
        if (!tables.length) {
            if (window.SqlWizardToast) SqlWizardToast.success('Canvas vazio', { duration: 1500 });
            else if (window.showToast) window.showToast('Canvas vazio', 'ok');
            return;
        }

        var deps = {}, all = {}, order = [], visited = {}, visiting = {};
        tables.forEach(function (t) { deps[t.id] = []; all[t.id] = t; });
        relations.forEach(function (r) { if (deps[r.to] && r.from !== r.to) deps[r.to].push(r.from); });
        function visit(id) {
            if (visited[id] || visiting[id]) return;
            visiting[id] = true;
            (deps[id] || []).forEach(visit);
            visiting[id] = false;
            visited[id] = true;
            order.push(all[id]);
        }
        tables.forEach(function (t) { visit(t.id); });

        var COLS = 4, GAP_X = 340, GAP_Y = 280, START_X = 60, START_Y = 60;
        var positions = [];

        if (!document.getElementById('sw-auto-layout-style')) {
            var style = document.createElement('style');
            style.id = 'sw-auto-layout-style';
            style.textContent = '.tbl { transition: left .4s ease, top .4s ease; }';
            document.head.appendChild(style);
        }

        order.forEach(function (t, i) {
            var x = START_X + (i % COLS) * GAP_X;
            var y = START_Y + Math.floor(i / COLS) * GAP_Y;
            var el = document.querySelector('.tbl[data-id="' + t.id + '"]');
            if (el) {
                el.style.left = x + 'px';
                el.style.top = y + 'px';
            }
            t.x = x;
            t.y = y;
            positions.push({ id: t.id, x: x, y: y });
        });

        if (window.SqlWizardCanvas && window.SqlWizardCanvas.getState) {
            var st = window.SqlWizardCanvas.getState();
            positions.forEach(function (p) {
                var t = st.tables.find(function (x) { return x.id === p.id; });
                if (t) { t.x = p.x; t.y = p.y; }
            });
            if (window.SqlWizardCanvas.save) window.SqlWizardCanvas.save();
            if (window.SqlWizardCanvas.render) window.SqlWizardCanvas.render();
        }

        if (window.SqlWizardToast) SqlWizardToast.success('Auto-layout aplicado', { duration: 1500 });
        else if (window.showToast) window.showToast('Auto-layout aplicado', 'ok');
    }

    return { layout: layout };
})(window, document);
