/* SqlWizard · Seta de destaque (feedback visual) */
window.SqlWizardPointer = (function (window, document) {
    'use strict';

    var active = [];

    /**
     * Mostra uma seta a piscar junto a um elemento.
     *
     * @param {string|Element} target   selector ou elemento
     * @param {object} opts
     *   position: 'top' | 'bottom' | 'left' | 'right'  (auto se omitido)
     *   label:    texto por baixo da seta
     *   duration: ms (default 3000)
     */
    function point(target, opts) {
        opts = opts || {};

        var el = typeof target === 'string'
            ? document.querySelector(target)
            : target;

        if (!el) {
            console.warn('[pointer] alvo não encontrado:', target);
            return;
        }

        // limpa anteriores
        clear();

        var rect = el.getBoundingClientRect();
        if (rect.width === 0 && rect.height === 0) {
            // elemento invisível ou display:none — tenta outra vez
            setTimeout(function () { point(target, opts); }, 150);
            return;
        }

        var pos = opts.position || autoPosition(rect);
        var glyph = {
            top:    '▼',
            bottom: '▲',
            left:   '▶',
            right:  '◀'
        }[pos] || '▼';

        var arrow = document.createElement('div');
        arrow.className = 'pa-arrow pa-arrow-' + pos;
        arrow.innerHTML =
            '<span class="pa-glyph">' + glyph + '</span>' +
            (opts.label
                ? '<span class="pa-label">' + escape(opts.label) + '</span>'
                : '');

        document.body.appendChild(arrow);

        // posiciona
        var gap = 12;
        var w   = arrow.offsetWidth  || 60;
        var h   = arrow.offsetHeight || 60;

        var left, top;

        if (pos === 'top') {
            left = rect.left + rect.width / 2 - w / 2;
            top  = rect.top - h - gap;
        } else if (pos === 'bottom') {
            left = rect.left + rect.width / 2 - w / 2;
            top  = rect.bottom + gap;
        } else if (pos === 'left') {
            left = rect.left - w - gap;
            top  = rect.top + rect.height / 2 - h / 2;
        } else { // right
            left = rect.right + gap;
            top  = rect.top + rect.height / 2 - h / 2;
        }

        // clamp à janela
        left = Math.max(8, Math.min(left, window.innerWidth  - w - 8));
        top  = Math.max(8, Math.min(top,  window.innerHeight - h - 8));

        arrow.style.left = left + 'px';
        arrow.style.top  = top + 'px';

        active.push(arrow);

        // auto-remove
        var duration = opts.duration || 3000;
        setTimeout(function () {
            arrow.classList.add('is-leaving');
            setTimeout(function () {
                if (arrow.parentNode) arrow.parentNode.removeChild(arrow);
                active = active.filter(function (a) { return a !== arrow; });
            }, 400);
        }, duration);
    }

    function autoPosition(rect) {
        // se o alvo ocupa mais de 60% da largura ou está no topo → aponta de cima
        if (rect.width > window.innerWidth * 0.5) return 'top';
        if (rect.top > window.innerHeight * 0.6)  return 'top';
        if (rect.left > window.innerWidth  * 0.5) return 'left';
        return 'top';
    }

    function clear() {
        active.forEach(function (a) {
            if (a.parentNode) a.parentNode.removeChild(a);
        });
        active = [];
    }

    function escape(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    return { point: point, clear: clear };
})(window, document);
