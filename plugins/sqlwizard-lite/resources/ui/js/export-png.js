/* SqlWizard Lite — Export PNG do canvas (com SVG redesenhado) */
window.SqlWizardExportPNG = (function (window, document) {
    'use strict';

    function download(filename, dataUrl) {
        var a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { document.body.removeChild(a); }, 100);
    }

    function loadHtml2Canvas() {
        return new Promise(function (resolve, reject) {
            if (window.html2canvas) { resolve(window.html2canvas); return; }
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
            script.onload = function () { resolve(window.html2canvas); };
            script.onerror = function () { reject(new Error('Falha ao carregar html2canvas')); };
            document.head.appendChild(script);
        });
    }

    function exportPNG() {
        var canvas = document.querySelector('.canvas');
        if (!canvas) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas nao encontrado');
            return;
        }

        var state = window.SqlWizardCanvas && window.SqlWizardCanvas.getState
            ? window.SqlWizardCanvas.getState()
            : null;
        var tables = state ? state.tables : [];
        var relations = state ? state.relations : [];
        if (!tables.length) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas vazio');
            return;
        }

        // Bounds do conteúdo
        var minX = Math.min.apply(null, tables.map(function (t) { return t.x; }));
        var minY = Math.min.apply(null, tables.map(function (t) { return t.y; }));
        var maxX = Math.max.apply(null, tables.map(function (t) { return t.x + 260; }));
        var maxY = Math.max.apply(null, tables.map(function (t) { return t.y + 200; }));
        var padding = 60;
        var width  = maxX - minX + padding * 2;
        var height = maxY - minY + padding * 2;

        // Wrapper temporário
        var wrapper = document.createElement('div');
        wrapper.style.cssText = 'position:fixed;left:-99999px;top:0;width:' + width + 'px;height:' + height + 'px;background:#F4F5F7;overflow:hidden;';
        document.body.appendChild(wrapper);

        // Clonar canvas SEM o SVG
        var clone = canvas.cloneNode(true);
        var cloneSvg = clone.querySelector('#relations, .relations-layer, svg');
        if (cloneSvg) cloneSvg.remove();

        clone.style.cssText = 'position:absolute;left:' + (padding - minX) + 'px;top:' + (padding - minY) + 'px;width:' + width + 'px;height:' + height + 'px;transform:none;';
        wrapper.appendChild(clone);

        // Toast
        if (window.SqlWizardToast) SqlWizardToast.success('A gerar PNG...', { duration: 2000 });

        var filename = 'sqlwizard-schema-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.png';

        loadHtml2Canvas()
            .then(function (html2canvas) {
                return html2canvas(wrapper, {
                    backgroundColor: '#F4F5F7',
                    scale: 2,
                    logging: false,
                    useCORS: true
                });
            })
            .then(function (baseCanvas) {
                // ─── Canvas separado só para as linhas ───
                var lineCanvas = document.createElement('canvas');
                lineCanvas.width  = baseCanvas.width;
                lineCanvas.height = baseCanvas.height;
                var lctx = lineCanvas.getContext('2d');
                lctx.scale(2, 2);
                lctx.lineCap = 'round';

                relations.forEach(function (r) {
                    var a = tables.find(function (t) { return t.id === r.from; });
                    var b = tables.find(function (t) { return t.id === r.to; });
                    if (!a || !b) return;

                    var ax = a.x + 220 + padding - minX;
                    var ay = a.y + 40  + padding - minY;
                    var bx = b.x       + padding - minX;
                    var by = b.y + 40  + padding - minY;
                    var mx = (ax + bx) / 2;
                    var my = (ay + by) / 2;

                    // Linha
                    lctx.beginPath();
                    lctx.moveTo(ax, ay);
                    lctx.bezierCurveTo(mx, ay, mx, by, bx, by);
                    lctx.strokeStyle = '#B84500';
                    lctx.lineWidth = 4;
                    lctx.setLineDash([14, 7]);
                    lctx.stroke();
                    lctx.setLineDash([]);

                    // Seta
                    var angle = Math.atan2(by - ay, bx - ax);
                    var arrowSize = 9;
                    lctx.beginPath();
                    lctx.moveTo(bx, by);
                    lctx.lineTo(bx - arrowSize * Math.cos(angle - Math.PI / 6), by - arrowSize * Math.sin(angle - Math.PI / 6));
                    lctx.lineTo(bx - arrowSize * Math.cos(angle + Math.PI / 6), by - arrowSize * Math.sin(angle + Math.PI / 6));
                    lctx.closePath();
                    lctx.fillStyle = '#B84500';
                    lctx.fill();

                    // Badge
                    var card = r.card || 'n:1';
                    lctx.font = 'bold 11px "JetBrains Mono", monospace';
                    var textWidth = lctx.measureText(card).width;
                    var badgeW = textWidth + 14;
                    var badgeH = 18;

                    lctx.fillStyle = '#FFFFFF';
                    lctx.strokeStyle = '#B84500';
                    lctx.lineWidth = 1.5;
                    lctx.beginPath();
                    if (lctx.roundRect) {
                        lctx.roundRect(mx - badgeW / 2, my - badgeH / 2, badgeW, badgeH, 4);
                    } else {
                        lctx.rect(mx - badgeW / 2, my - badgeH / 2, badgeW, badgeH);
                    }
                    lctx.fill();
                    lctx.stroke();

                    lctx.fillStyle = '#B84500';
                    lctx.textAlign = 'center';
                    lctx.textBaseline = 'middle';
                    lctx.fillText(card, mx, my);
                });

                // ─── Combinar base + linhas ───
                var finalCanvas = document.createElement('canvas');
                finalCanvas.width  = baseCanvas.width;
                finalCanvas.height = baseCanvas.height;
                var fctx = finalCanvas.getContext('2d');
                fctx.drawImage(baseCanvas, 0, 0);
                fctx.drawImage(lineCanvas, 0, 0);

                download(filename, finalCanvas.toDataURL('image/png'));
                if (window.SqlWizardToast) SqlWizardToast.success('PNG descarregado!');
            })
            .catch(function (err) {
                console.error('[export-png]', err);
                if (window.SqlWizardToast) SqlWizardToast.error('Erro: ' + err.message);
            })
            .finally(function () {
                wrapper.remove();
            });
    }

    return { exportPNG: exportPNG };
})(window, document);
