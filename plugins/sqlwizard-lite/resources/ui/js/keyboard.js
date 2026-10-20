/* SqlWizard Lite — Keyboard shortcuts */
window.SqlWizardKeyboard = (function (window, document) {
    'use strict';

    var zoomLevel = 1;
    var MIN_ZOOM = 0.25;
    var MAX_ZOOM = 3;

    function duplicateSelected() {
        if (!window.SqlWizardCanvas || !window.SqlWizardCanvas.getState) return;
        var state = window.SqlWizardCanvas.getState();
        // A tabela selecionada está no inspector (pode estar hidden)
        var inspector = document.getElementById('inspector');
        if (!inspector || inspector.hidden) {
            if (window.SqlWizardToast) SqlWizardToast.error('Seleciona uma tabela primeiro', { title: 'Duplicate' });
            return;
        }
        // Encontrar tabela pelo nome no título do inspector
        var titleEl = document.getElementById('insp-title');
        if (!titleEl) return;
        var tableName = titleEl.textContent;
        var table = state.tables.find(function (t) { return t.name === tableName; });
        if (!table) return;

        var newTable = JSON.parse(JSON.stringify(table));
        newTable.id = 't_' + Math.random().toString(36).slice(2, 9);
        newTable.name = tableName + '_copy';
        newTable.x = table.x + 40;
        newTable.y = table.y + 40;
        state.tables.push(newTable);

        if (window.SqlWizardCanvas.setState) window.SqlWizardCanvas.setState(state);
        if (window.SqlWizardToast) SqlWizardToast.success('Tabela duplicada: ' + newTable.name, { duration: 2000 });
    }

    function deleteSelected() {
        // Já existe no canvas.js (Delete). Não duplicar aqui.
    }

    function applyZoom(level) {
        zoomLevel = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, level));
        var canvas = document.querySelector('.canvas');
        if (canvas) {
            canvas.style.transform = 'scale(' + zoomLevel + ')';
            canvas.style.transformOrigin = 'top left';
        }
        var zoomDisplay = document.getElementById('zoom-display');
        if (zoomDisplay) zoomDisplay.textContent = Math.round(zoomLevel * 100) + '%';
    }

    function zoomIn()  { applyZoom(zoomLevel + 0.1); }
    function zoomOut() { applyZoom(zoomLevel - 0.1); }
    function zoomReset() { applyZoom(1); }

    function zoomFit() {
        var canvas = document.querySelector('.canvas');
        var wrap = document.querySelector('.canvas-wrap');
        if (!canvas || !wrap) return;
        var state = window.SqlWizardCanvas.getState();
        if (!state.tables.length) return;
        var minX = Math.min.apply(null, state.tables.map(function (t) { return t.x; }));
        var minY = Math.min.apply(null, state.tables.map(function (t) { return t.y; }));
        var maxX = Math.max.apply(null, state.tables.map(function (t) { return t.x + 260; }));
        var maxY = Math.max.apply(null, state.tables.map(function (t) { return t.y + 200; }));
        var w = maxX - minX + 80;
        var h = maxY - minY + 80;
        var scale = Math.min(wrap.clientWidth / w, wrap.clientHeight / h, 1.5);
        applyZoom(scale);
        wrap.scrollLeft = minX * scale - 40;
        wrap.scrollTop  = minY * scale - 40;
    }

    document.addEventListener('keydown', function (e) {
        var ctrl = e.ctrlKey || e.metaKey;
        var tag = (document.activeElement && document.activeElement.tagName) || '';
        var isInput = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';

        // Ctrl+Z → Undo
        if (ctrl && !e.shiftKey && e.key.toLowerCase() === 'z' && !isInput) {
            e.preventDefault();
            if (window.SqlWizardHistory && window.SqlWizardHistory.undo()) {
                if (window.SqlWizardToast) SqlWizardToast.success('Desfeito', { duration: 1200 });
            } else {
                if (window.SqlWizardToast) SqlWizardToast.error('Nada para desfazer', { duration: 1200 });
            }
            return;
        }

        // Ctrl+Shift+Z ou Ctrl+Y → Redo
        if (ctrl && ((e.shiftKey && e.key.toLowerCase() === 'z') || e.key.toLowerCase() === 'y') && !isInput) {
            e.preventDefault();
            if (window.SqlWizardHistory && window.SqlWizardHistory.redo()) {
                if (window.SqlWizardToast) SqlWizardToast.success('Refeito', { duration: 1200 });
            } else {
                if (window.SqlWizardToast) SqlWizardToast.error('Nada para refazer', { duration: 1200 });
            }
            return;
        }

        // Ctrl+D → Duplicate
        if (ctrl && e.key.toLowerCase() === 'd' && !isInput) {
            e.preventDefault();
            duplicateSelected();
            return;
        }

        // Ctrl++ → Zoom in
        if (ctrl && (e.key === '+' || e.key === '=') && !isInput) {
            e.preventDefault();
            zoomIn();
            return;
        }

        // Ctrl+- → Zoom out
        if (ctrl && e.key === '-' && !isInput) {
            e.preventDefault();
            zoomOut();
            return;
        }

        // Ctrl+0 → Zoom reset
        if (ctrl && e.key === '0' && !isInput) {
            e.preventDefault();
            zoomReset();
            return;
        }

        // Ctrl+Shift+F → Zoom fit
        if (ctrl && e.shiftKey && e.key.toLowerCase() === 'f' && !isInput) {
            e.preventDefault();
            zoomFit();
            return;
        }
    });

    return {
        zoomIn: zoomIn,
        zoomOut: zoomOut,
        zoomReset: zoomReset,
        zoomFit: zoomFit,
        duplicateSelected: duplicateSelected,
        getZoom: function () { return zoomLevel; }
    };
})(window, document);
