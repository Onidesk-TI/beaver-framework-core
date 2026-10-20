/* ============================================================
/* ============================================================
 * Frankei · Floating Panel
 * Painel com drag, resize, dock, minimize.
 * Carrega o layout interno via fetch('/plugins/frankei/layout.html').
 * Persiste estado em localStorage.
 * ============================================================ */
window.FrankeiPanel = (function (window, document) {
    'use strict';

    var STORAGE_KEY = 'frankei:panelState';
    var MIN_W = 320, MIN_H = 220;
    var DOCK_HEIGHT = 320;
    var LAYOUT_URL = '/plugins/frankei/layout.html';

    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) { return {}; }
    }

    function saveState(s) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(s)); } catch (e) {}
    }

    function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

    function create(opts) {
        opts = opts || {};

        var state = loadState();
        var panel = {
            el: null,
            docked: !!state.docked,
            minimized: !!state.minimized,
            x: typeof state.x === 'number' ? state.x : (window.innerWidth - 520),
            y: typeof state.y === 'number' ? state.y : 80,
            w: typeof state.w === 'number' ? state.w : 480,
            h: typeof state.h === 'number' ? state.h : 360,
            context: {},
            onAction: null
        };

        function escape(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function build() {
            var el = document.createElement('div');
            el.className = 'fk-panel';
            el.innerHTML =
                '<div class="fk-header">' +
                '  <span class="fk-title">' + escape(opts.title || 'Frankei') + '</span>' +
                '  <button class="fk-btn" data-act="minimize" title="Minimizar">—</button>' +
                '  <button class="fk-btn" data-act="dock" title="Fixar/Flutuar">⇱</button>' +
                '  <button class="fk-btn is-close" data-act="close" title="Fechar">✕</button>' +
                '</div>' +
                '<div class="fk-body"><div class="fk-placeholder">A carregar…</div></div>' +
                '<div class="fk-resize-handle fk-rz-n"></div>' +
                '<div class="fk-resize-handle fk-rz-s"></div>' +
                '<div class="fk-resize-handle fk-rz-e"></div>' +
                '<div class="fk-resize-handle fk-rz-w"></div>' +
                '<div class="fk-resize-handle fk-rz-ne"></div>' +
                '<div class="fk-resize-handle fk-rz-nw"></div>' +
                '<div class="fk-resize-handle fk-rz-se"></div>' +
                '<div class="fk-resize-handle fk-rz-sw"></div>';

            document.body.appendChild(el);
            panel.el = el;

            bindHeader();
            bindResize();
            applyLayout();
            loadLayout();
        }

        function loadLayout() {
            var body = panel.el.querySelector('.fk-body');
            if (!body) return;

            fetch(LAYOUT_URL)
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(function (html) {
                    body.innerHTML = html;
                    bindLayout(body);
                    if (panel.onLoad) panel.onLoad(body);
                })
                .catch(function (err) {
                    body.innerHTML = '<div class="fk-placeholder">Falha a carregar layout: ' +
                        escape(err.message) + '</div>';
                });
        }

        function bindLayout(root) {
            root.querySelectorAll('.fk-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var name = tab.getAttribute('data-tab');
                    root.querySelectorAll('.fk-tab').forEach(function (t) {
                        t.classList.remove('is-active');
                    });
                    tab.classList.add('is-active');
                    root.querySelectorAll('.fk-tab-panel').forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-panel') === name);
                    });
                });
            });

            root.querySelectorAll('[data-act]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var act = btn.getAttribute('data-act');
                    if (typeof panel.onAction === 'function') panel.onAction(act);
                });
            });
        }

        function applyLayout() {
            var el = panel.el;
            el.classList.toggle('is-docked', panel.docked);
            el.classList.toggle('is-minimized', panel.minimized);

            if (panel.docked) {
                el.style.left = '0';
                el.style.top = 'auto';
                el.style.bottom = '0';
                el.style.width = '100%';
                el.style.height = (panel.minimized ? 36 : DOCK_HEIGHT) + 'px';
            } else {
                el.style.left = clamp(panel.x, 0, window.innerWidth - 80) + 'px';
                el.style.top  = clamp(panel.y, 0, window.innerHeight - 40) + 'px';
                el.style.bottom = 'auto';
                el.style.width  = panel.w + 'px';
                el.style.height = (panel.minimized ? 36 : panel.h) + 'px';
            }
        }

        function persist() {
            saveState({
                docked: panel.docked,
                minimized: panel.minimized,
                x: panel.x, y: panel.y,
                w: panel.w, h: panel.h
            });
        }

        function bindHeader() {
            var header = panel.el.querySelector('.fk-header');

            header.querySelector('[data-act="minimize"]').onclick = function (e) {
                e.stopPropagation();
                panel.minimized = !panel.minimized;
                applyLayout();
                persist();
            };

            header.querySelector('[data-act="dock"]').onclick = function (e) {
                e.stopPropagation();
                panel.docked = !panel.docked;
                applyLayout();
                persist();
            };

            header.querySelector('[data-act="close"]').onclick = function (e) {
                e.stopPropagation();
                panel.close();
            };

            var drag = null;

            header.addEventListener('pointerdown', function (e) {
                if (e.target.closest('.fk-btn')) return;
                if (panel.docked) return;
                drag = {
                    startX: e.clientX, startY: e.clientY,
                    origX: panel.x, origY: panel.y
                };
                header.setPointerCapture(e.pointerId);
            });

            header.addEventListener('pointermove', function (e) {
                if (!drag) return;
                panel.x = drag.origX + (e.clientX - drag.startX);
                panel.y = drag.origY + (e.clientY - drag.startY);
                applyLayout();
            });

            header.addEventListener('pointerup', function (e) {
                if (!drag) return;
                drag = null;
                header.releasePointerCapture(e.pointerId);
                persist();
            });

            header.addEventListener('pointercancel', function () { drag = null; });
        }

        function bindResize() {
            panel.el.querySelectorAll('.fk-resize-handle').forEach(function (handle) {
                handle.addEventListener('pointerdown', function (e) {
                    if (panel.docked) return;
                    e.preventDefault();
                    e.stopPropagation();

                    var dir = handle.className.match(/fk-rz-([nsew]+)/)[1];
                    var start = {
                        x: e.clientX, y: e.clientY,
                        w: panel.w, h: panel.h,
                        px: panel.x, py: panel.y
                    };

                    handle.setPointerCapture(e.pointerId);

                    function onMove(ev) {
                        var dx = ev.clientX - start.x;
                        var dy = ev.clientY - start.y;

                        if (dir.indexOf('e') !== -1) panel.w = Math.max(MIN_W, start.w + dx);
                        if (dir.indexOf('s') !== -1) panel.h = Math.max(MIN_H, start.h + dy);
                        if (dir.indexOf('w') !== -1) {
                            var nw = Math.max(MIN_W, start.w - dx);
                            panel.x = start.px + (start.w - nw);
                            panel.w = nw;
                        }
                        if (dir.indexOf('n') !== -1) {
                            var nh = Math.max(MIN_H, start.h - dy);
                            panel.y = start.py + (start.h - nh);
                            panel.h = nh;
                        }
                        applyLayout();
                    }

                    function onUp() {
                        handle.removeEventListener('pointermove', onMove);
                        handle.removeEventListener('pointerup', onUp);
                        persist();
                    }

                    handle.addEventListener('pointermove', onMove);
                    handle.addEventListener('pointerup', onUp);
                });
            });
        }

        panel.open = function () {
            if (!panel.el) build();
            panel.el.style.display = '';
        };

        panel.close = function () {
            if (panel.el) { panel.el.remove(); panel.el = null; }
        };

        panel.body = function () {
            return panel.el ? panel.el.querySelector('.fk-body') : null;
        };

        panel.setTitle = function (t) {
            if (!panel.el) return;
            panel.el.querySelector('.fk-title').textContent = t;
        };

        panel.setContext = function (ctx) {
            panel.context = ctx || {};
            if (panel.el) {
                panel.el.dispatchEvent(new CustomEvent('frankei:context', {
                    detail: panel.context
                }));
            }
        };

        panel.getContext = function () { return panel.context || {}; };

        return panel;
    }

    return { create: create };

})(window, document);
