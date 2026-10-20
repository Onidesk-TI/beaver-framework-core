window.SqlWizardCanvas = (function (window, document) {
    'use strict';

    var STORAGE_KEY = 'sqlwizard:positions';
    var VIEW_KEY = 'sqlwizard:canvasView';
    var DRAG_THRESHOLD = 5;
    var MIN_ZOOM = 0.25;
    var MAX_ZOOM = 3;

    var iconMap = null;
    var iconFallback = '▦';

    function loadIcons() {
        if (iconMap !== null) return Promise.resolve(iconMap);
        return fetch('/api/sqlwizard/icons')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                iconMap = data || {};
                iconFallback = iconMap._fallback || '▦';
                return iconMap;
            })
            .catch(function () {
                iconMap = {};
                return iconMap;
            });
    }

    function iconFor(table, draftIcon) {
        if (draftIcon) return draftIcon;
        if (iconMap && iconMap[table]) return iconMap[table];
        return iconFallback;
    }

    function loadPositions() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function savePosition(draftId, x, y) {
        try {
            var all = loadPositions();
            all[draftId] = { x: Math.round(x), y: Math.round(y) };
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
        } catch (e) {
            console.warn('[canvas] nao consegui guardar posicao:', e);
        }
    }

    function getPosition(draftId) {
        var all = loadPositions();
        return all[draftId] || null;
    }

    function clearPosition(draftId) {
        try {
            var all = loadPositions();
            delete all[draftId];
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
        } catch (e) { }
    }

    var EDGE_ITEMS = [
        { action: 'cycle-relation', icon: '🔄', label: 'Ciclar tipo (n:1 → 1:1 → n:n)' },
        { action: 'edit-relation', icon: '✏️', label: 'Editar relação...' },
        { type: 'sep' },
        { action: 'copy-relation', icon: '📋', label: 'Copiar (tabela.coluna → ref)' },
        { action: 'relation-details', icon: 'ℹ️', label: 'Detalhes da relação' },
        { type: 'sep' },
        { action: 'relation-wizard', icon: '🧙', label: 'Wizard relação' },
        { type: 'sep' },
        { action: 'delete-relation', icon: '🗑️', label: 'Apagar relação', danger: true }
    ];

    var Canvas = {
        el: null,
        cards: [],
        edgeTypes: {},
        selected: [],   // IDs dos drafts selecionados (multi-seleção)

        // Zoom + Pan
        zoom: 1,
        panX: 0,
        panY: 0,

        edgeMenuItems: function () {
            return EDGE_ITEMS;
        },


        /* ── Seleção múltipla ───────────────────────────────── */

        isSelected: function (draftId) {
            return this.selected.indexOf(draftId) !== -1;
        },

        select: function (draftId, additive) {
            if (!additive) {
                this.selected = [draftId];
            } else if (!this.isSelected(draftId)) {
                this.selected.push(draftId);
            }
            this.renderSelection();
        },

        deselect: function (draftId) {
            this.selected = this.selected.filter(function (id) { return id !== draftId; });
            this.renderSelection();
        },

        toggleSelection: function (draftId) {
            if (this.isSelected(draftId)) {
                this.deselect(draftId);
            } else {
                this.select(draftId, true);
            }
        },

        clearSelection: function () {
            this.selected = [];
            this.renderSelection();
        },

        renderSelection: function () {
            if (!this.el) return;
            var self = this;

            this.el.querySelectorAll('.card').forEach(function (card) {
                var id = card.getAttribute('data-id');
                card.classList.toggle('is-multi-selected', self.isSelected(id));
            });
        },

        /* ── Relações (FKs) ─────────────────────────────────── */


        init: function () {
            this.el = document.getElementById('canvas');
            if (!this.el) {
                console.warn('[canvas] #canvas nao encontrado');
                return;
            }

            loadIcons();

            this.el.innerHTML =
                '<div class="canvas-viewport" id="canvas-viewport">' +
                '  <svg class="canvas-edges" xmlns="http://www.w3.org/2000/svg"></svg>' +
                '  <div class="canvas-cards" id="canvas-cards"></div>' +
                '</div>' +
                '<div class="canvas-empty">' +
                '  <span class="canvas-empty-icon">▦</span>' +
                '  <p class="is-muted">Arrasta tabelas para aqui</p>' +
                '  <p class="canvas-empty-hint">Clica num draft no Explorer</p>' +
                '</div>' +
                '<div class="canvas-controls">' +
                '  <button class="canvas-control" data-zoom="out" title="Zoom out">−</button>' +
                '  <span class="canvas-zoom-label" id="canvas-zoom-label">100%</span>' +
                '  <button class="canvas-control" data-zoom="in" title="Zoom in">+</button>' +
                '  <button class="canvas-control" data-zoom="reset" title="Reset (100%)">⌂</button>' +
                '  <button class="canvas-control" data-zoom="fit" title="Ajustar">◱</button>' +
                '</div>' +
                '<div class="edge-tooltip" id="edge-tooltip"></div>';

            this.bindZoomControls();
            this.bindWheel();
            this.loadView();
            this.applyTransform();

            // Click no vazio desmarca
            this.el.addEventListener('pointerdown', function (e) {
                if (e.target.closest('.card')) return;
                if (e.target.closest('.canvas-controls')) return;
                if (e.target.closest('.canvas-edge')) return;
                if (e.target.closest('.edge-tooltip')) return;

                Canvas.clearSelection();
            });

            // Click direito no vazio → menu de contexto
            this.el.addEventListener('contextmenu', function (e) {
                if (e.target.closest('.card')) return;
                if (e.target.closest('.canvas-edge')) return;
                if (e.target.closest('.canvas-controls')) return;
                if (e.target.closest('.edge-tooltip')) return;

                e.preventDefault();
                e.stopPropagation();

                if (window.SqlWizardCanvasMenu) {
                    window.SqlWizardCanvasMenu.open(e.clientX, e.clientY);
                }
            });
        },

        clear: function () {
            var layer = document.getElementById('canvas-cards');
            if (layer) layer.innerHTML = '';
            if (this.el) this.el.classList.remove('has-cards');
            this.cards = [];

            this.drawEdges();
        },

        add: function (draft, opts) {
            opts = opts || {};
            if (!this.el) return;

            if (this.has(draft.id)) {
                return;
            }

            var layer = document.getElementById('canvas-cards');
            if (!layer) return;

            var card = this.renderCard(draft);
            layer.appendChild(card);

            this.cards.push(draft.id);
            this.el.classList.add('has-cards');

            var self = this;
            var pos = getPosition(draft.id);
            var skipDraw = opts.noDraw === true;

            requestAnimationFrame(function () {
                if (pos) {
                    card.style.left = pos.x + 'px';
                    card.style.top = pos.y + 'px';

                    if (!self.isVisibleInViewport(card)) {
                        clearPosition(draft.id);
                        self.autoPosition(card, draft.id);
                    }
                } else {
                    self.autoPosition(card, draft.id);
                }

                if (!skipDraw) self.drawEdges();
            });

            if (!skipDraw) {
                window.dispatchEvent(new CustomEvent('sqlwizard:canvas-changed', {
                    detail: { cards: this.cards.slice() }
                }));
            }
        },

        /**
         * Adiciona vários drafts de uma vez sem redesenhar as linhas
         * em cada um. Útil para imports grandes.
         */
        addMany: function (drafts) {
            var self = this;
            drafts.forEach(function (d) {
                self.add(d, { noDraw: true });
            });

            // redesenha uma vez no fim
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    self.drawEdges();
                    window.dispatchEvent(new CustomEvent('sqlwizard:canvas-changed', {
                        detail: { cards: self.cards.slice() }
                    }));
                });
            });
        },

        /**
         * Verifica se o card cai dentro do viewport atual do canvas,
         * tendo em conta o zoom e o pan.
         */
        isVisibleInViewport: function (card) {
            var el = this.el;
            if (!el) return false;

            var viewW = el.clientWidth;
            var viewH = el.clientHeight;

            var x = parseFloat(card.style.left) || 0;
            var y = parseFloat(card.style.top) || 0;

            var w = card.offsetWidth  || 200;
            var h = card.offsetHeight || 100;

            // coords de ecrã (aplica pan + zoom)
            var sx = this.panX + x * this.zoom;
            var sy = this.panY + y * this.zoom;
            var sw = w * this.zoom;
            var sh = h * this.zoom;

            // alguma parte visível?
            return (sx + sw > 0) && (sy + sh > 0) && (sx < viewW) && (sy < viewH);
        },

        /**
         * Verifica se o card está dentro do viewport atual do canvas.
         * Considera o zoom e o pan aplicados ao viewport.
         */
        isVisibleInViewport: function (card) {
            var el = this.el;
            if (!el) return false;

            var viewW = el.clientWidth;
            var viewH = el.clientHeight;

            var x = parseFloat(card.style.left) || 0;
            var y = parseFloat(card.style.top) || 0;

            // dimensões do card em "world coords"
            var w = card.offsetWidth || 200;
            var h = card.offsetHeight || 100;

            // passa para screen coords
            var sx = this.panX + x * this.zoom;
            var sy = this.panY + y * this.zoom;
            var sw = w * this.zoom;
            var sh = h * this.zoom;

            // alguma parte visível?
            return (sx + sw > 0) && (sy + sh > 0) && (sx < viewW) && (sy < viewH);
        },

        // Alias: manter compatibilidade com código antigo
        show: function (draft) {
            this.add(draft);
        },

        remove: function (draftId) {
            var layer = document.getElementById('canvas-cards');
            if (layer) {
                var card = layer.querySelector('[data-id="' + draftId + '"]');
                if (card) card.remove();
            }

            this.cards = this.cards.filter(function (id) { return id !== draftId; });

            if (this.el && this.cards.length === 0) {
                this.el.classList.remove('has-cards');
            }
            this.drawEdges();
        },

        has: function (draftId) {
            return this.cards.indexOf(draftId) !== -1;
        },
        renderCard: function (draft) {

            var card = document.createElement('div');
            card.className = 'card';
            card.setAttribute('data-id', draft.id);
            card.setAttribute('data-table', draft.table);

            var fields = draft.fields || [];
            var self = this;
            var cols = fields.map(function (f) { return self.renderColumn(f); }).join('');

            card.innerHTML =
                '<div class="card-header">' +
                '  <span class="card-icon">' + iconFor(draft.table, draft.icon) + '</span>' +
                '  <span class="card-title">' + this.escape(draft.table) + '</span>' +
                '  <span class="card-badge">' + fields.length + '</span>' +
                '  <button class="card-zoom" title="Ver tabela em grande" data-act="zoom">⛶</button>' +
                '</div>' +
                '<div class="card-body">' +
                '  <ul class="card-columns">' + cols + '</ul>' +
                '</div>';

            // Botão ⛶ — abrir tabela em grande escala
            var zoomBtn = card.querySelector('[data-act="zoom"]');
            if (zoomBtn) {
                zoomBtn.addEventListener('pointerdown', function (e) {
                    e.stopPropagation();   // não dispara drag nem click
                });
                zoomBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    if (window.SqlWizardTableZoom) {
                        window.SqlWizardTableZoom.open(draft, card);
                    }
                });
            }

            this.attachDrag(card, draft.id);
            this.attachColumnDrag(card, draft);
            this.attachCardDrop(card, draft);

            var header = card.querySelector('.card-header');
            if (header) {
                header.addEventListener('dblclick', function () {
                    clearPosition(draft.id);
                    Canvas.center(card, draft.id);
                });
            }

            return card;
        },

        renderColumn: function (field) {
            var info = this.parseField(field);
            var iconCls = 'card-col-icon';
            var icon = '○';

            if (info.isPk) {
                iconCls += ' is-pk';
                icon = '🔑';
            } else if (info.isUnique) {
                iconCls += ' is-unique';
                icon = '⚿';
            } else if (info.nullable) {
                iconCls += ' is-null';
                icon = '?';
            }

            var nameCls = 'card-col-name';
            if (info.nullable) nameCls += ' is-nullable';

            return '<li class="card-column' + (info.nullable ? ' is-nullable' : '') + '"' +
                '    data-column="' + this.escape(info.name) + '">' +
                '<span class="' + iconCls + '">' + icon + '</span>' +
                '<span class="' + nameCls + '">' + this.escape(info.name) + '</span>' +
                '<span class="card-col-type">' + this.escape(info.type) + '</span>' +
                '</li>';
        },

        attachDrag: function (card, draftId) {
            var startX = 0, startY = 0;
            var startPositions = [];   // [{ id, left, top, el }] de todos os selecionados
            var dragging = false;

            function onDown(e) {
                if (e.button !== 0) return;
                if (e.altKey || e.shiftKey) return;

                // ── Lógica de seleção ──
                var isSel = Canvas.isSelected(draftId);

                if (e.ctrlKey || e.metaKey) {
                    Canvas.toggleSelection(draftId);
                    if (!Canvas.isSelected(draftId)) {
                        // Acabou de sair da seleção — não inicia drag
                        return;
                    }
                } else {
                    if (!isSel) {
                        Canvas.select(draftId, false);
                    }
                    // Se já estava selecionado, mantém a seleção (vai arrastar todos)
                }

                // ── Guardar posições iniciais de TODOS os selecionados ──
                startX = e.clientX;
                startY = e.clientY;
                dragging = false;

                startPositions = Canvas.selected.map(function (id) {
                    var c = Canvas.el.querySelector('.card[data-id="' + id + '"]');
                    return {
                        id: id,
                        left: c ? (parseFloat(c.style.left) || 0) : 0,
                        top: c ? (parseFloat(c.style.top) || 0) : 0,
                        el: c
                    };
                });

                card.setPointerCapture(e.pointerId);
                card.addEventListener('pointermove', onMove);
                card.addEventListener('pointerup', onUp);
                card.addEventListener('pointercancel', onUp);
            }

            function onMove(e) {
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;

                if (!dragging) {
                    if (Math.abs(dx) + Math.abs(dy) < DRAG_THRESHOLD) return;
                    dragging = true;

                    // Marcar todos os envolvidos como "dragging"
                    startPositions.forEach(function (p) {
                        if (p.el) p.el.classList.add('is-dragging');
                    });
                }

                var zoom = Canvas.zoom;
                var moveX = dx / zoom;
                var moveY = dy / zoom;

                startPositions.forEach(function (p) {
                    if (!p.el) return;
                    p.el.style.left = (p.left + moveX) + 'px';
                    p.el.style.top = (p.top + moveY) + 'px';
                });

                Canvas.drawEdges();
            }

            function onUp(e) {
                card.releasePointerCapture(e.pointerId);
                card.removeEventListener('pointermove', onMove);
                card.removeEventListener('pointerup', onUp);
                card.removeEventListener('pointercancel', onUp);

                startPositions.forEach(function (p) {
                    if (p.el) p.el.classList.remove('is-dragging');
                });

                if (dragging) {
                    // Guardar posição de todos os movidos
                    startPositions.forEach(function (p) {
                        if (p.el) savePosition(p.id, p.el.offsetLeft, p.el.offsetTop);
                    });
                } else {
                    // Foi clique (não drag)
                    window.dispatchEvent(new CustomEvent('sqlwizard:card-clicked', {
                        detail: { draftId: draftId }
                    }));
                }
            }

            card.addEventListener('contextmenu', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (window.SqlWizardContextMenu) {
                    window.SqlWizardContextMenu.open(e.clientX, e.clientY, {
                        draftId: draftId,
                        table: card.querySelector('.card-title')
                            ? card.querySelector('.card-title').textContent
                            : ''
                    });
                }
            });

            card.addEventListener('pointerdown', onDown);
        },

        center: function (card, draftId) {
            var w = this.el.clientWidth;
            var h = this.el.clientHeight;

            requestAnimationFrame(function () {
                var cw = card.offsetWidth;
                var ch = card.offsetHeight;
                var x = Math.max(20, (w - cw) / 2);
                var y = Math.max(20, (h - ch) / 2);

                card.style.left = x + 'px';
                card.style.top = y + 'px';

                if (draftId) savePosition(draftId, x, y);
            });
        },

        autoPosition: function (card, draftId) {
            var self = this;

            requestAnimationFrame(function () {
                var idx = self.cards.length - 1;   // índice do cartão recém-adicionado

                var col = idx % 3;                 // 3 colunas
                var row = Math.floor(idx / 3);

                var x = 20 + col * 340;
                var y = 20 + row * 360;

                card.style.left = x + 'px';
                card.style.top = y + 'px';

                if (draftId) savePosition(draftId, x, y);
            });
        },

        /* ── Zoom + Pan ─────────────────────────────────────── */

        applyTransform: function () {
            var vp = document.getElementById('canvas-viewport');
            if (!vp) return;

            vp.style.transform =
                'translate(' + this.panX + 'px, ' + this.panY + 'px) ' +
                'scale(' + this.zoom + ')';

            var label = document.getElementById('canvas-zoom-label');
            if (label) label.textContent = Math.round(this.zoom * 100) + '%';
            this.drawEdges();
        },

        zoomAt: function (mx, my, factor) {
            var newZoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, this.zoom * factor));
            if (newZoom === this.zoom) return;

            // O ponto (mx, my) em screen fica fixo
            this.panX = mx - (mx - this.panX) * (newZoom / this.zoom);
            this.panY = my - (my - this.panY) * (newZoom / this.zoom);
            this.zoom = newZoom;

            this.applyTransform();
            this.saveView();
        },

        setZoom: function (z, cx, cy) {
            var factor = z / this.zoom;
            this.zoomAt(cx, cy, factor);
        },

        bindZoomControls: function () {
            var self = this;

            this.el.querySelectorAll('[data-zoom]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();

                    var action = btn.getAttribute('data-zoom');
                    var rect = self.el.getBoundingClientRect();
                    var cx = rect.width / 2;
                    var cy = rect.height / 2;

                    if (action === 'in') {
                        self.zoomAt(cx, cy, 1.2);
                    } else if (action === 'out') {
                        self.zoomAt(cx, cy, 1 / 1.2);
                    } else if (action === 'reset') {
                        self.zoom = 1;
                        self.panX = 0;
                        self.panY = 0;
                        self.applyTransform();
                        self.saveView();
                    } else if (action === 'fit') {
                        self.fitToView();
                    }
                });
            });
        },

        bindWheel: function () {
            var self = this;

            function onWheel(e) {
                // Só se o rato estiver dentro do canvas
                if (!self.el || !self.el.contains(e.target)) return;

                if (e.ctrlKey || e.metaKey) {
                    // Zoom — trava o browser
                    e.preventDefault();
                    e.stopPropagation();

                    var rect = self.el.getBoundingClientRect();
                    var mx = e.clientX - rect.left;
                    var my = e.clientY - rect.top;

                    var factor = e.deltaY < 0 ? 1.1 : 1 / 1.1;
                    self.zoomAt(mx, my, factor);
                    return;
                }

                // Pan — trava o browser
                e.preventDefault();
                e.stopPropagation();

                if (e.shiftKey) {
                    self.panX -= e.deltaY;
                } else {
                    self.panX -= e.deltaX;
                    self.panY -= e.deltaY;
                }

                self.applyTransform();
                self.saveView();
            }

            // Capture phase: apanha o evento antes do browser
            document.addEventListener('wheel', onWheel, {
                passive: false,
                capture: true
            });
        },

        fitToView: function () {
            if (!this.cards.length) {
                this.zoom = 1;
                this.panX = 0;
                this.panY = 0;
                this.applyTransform();
                this.saveView();
                return;
            }

            var minX = Infinity, minY = Infinity;
            var maxX = -Infinity, maxY = -Infinity;
            var self = this;

            this.cards.forEach(function (id) {
                var card = self.el.querySelector('.card[data-id="' + id + '"]');
                if (!card) return;

                var x = parseFloat(card.style.left) || 0;
                var y = parseFloat(card.style.top) || 0;
                var w = card.offsetWidth;
                var h = card.offsetHeight;

                minX = Math.min(minX, x);
                minY = Math.min(minY, y);
                maxX = Math.max(maxX, x + w);
                maxY = Math.max(maxY, y + h);
            });

            var pad = 40;
            var worldW = (maxX - minX) + pad * 2;
            var worldH = (maxY - minY) + pad * 2;

            var viewW = this.el.clientWidth;
            var viewH = this.el.clientHeight;

            var newZoom = Math.min(viewW / worldW, viewH / worldH, MAX_ZOOM);
            newZoom = Math.max(MIN_ZOOM, newZoom);

            this.zoom = newZoom;
            this.panX = (viewW / 2) - ((minX + maxX) / 2) * newZoom;
            this.panY = (viewH / 2) - ((minY + maxY) / 2) * newZoom;

            this.applyTransform();
            this.saveView();
        },

        saveView: function () {
            try {
                localStorage.setItem('sqlwizard:canvasView', JSON.stringify({
                    zoom: this.zoom,
                    panX: this.panX,
                    panY: this.panY
                }));
            } catch (e) { }
        },

        loadView: function () {
            try {
                var raw = localStorage.getItem('sqlwizard:canvasView');
                if (!raw) return;
                var data = JSON.parse(raw);

                if (typeof data.zoom === 'number') this.zoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, data.zoom));
                if (typeof data.panX === 'number') this.panX = data.panX;
                if (typeof data.panY === 'number') this.panY = data.panY;
            } catch (e) { }
        },

        /* ── Drag de colunas para criar FK ──────────────────── */

        attachColumnDrag: function (card, draft) {
            var self = this;

            card.querySelectorAll('.card-column').forEach(function (colEl) {
                colEl.setAttribute('draggable', 'true');

                colEl.addEventListener('dragstart', function (e) {
                    var colName = colEl.getAttribute('data-column');
                    e.dataTransfer.effectAllowed = 'link';
                    e.dataTransfer.setData('application/json', JSON.stringify({
                        kind: 'fk-column',
                        sourceCardId: draft.id,
                        sourceTable: draft.table,
                        sourceColumn: colName,
                        sourceConnId: draft.source && draft.source.connection_id,
                        sourceDb: draft.source && draft.source.database
                    }));
                    colEl.classList.add('is-dragging');
                });

                colEl.addEventListener('dragend', function () {
                    colEl.classList.remove('is-dragging');
                });
            });
        },

        attachCardDrop: function (card, draft) {
            var self = this;

            card.addEventListener('dragover', function (e) {
                if (e.dataTransfer.types.indexOf('application/json') === -1) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'link';
                card.classList.add('is-drop-target');
            });

            card.addEventListener('dragleave', function (e) {
                if (card.contains(e.relatedTarget)) return;
                card.classList.remove('is-drop-target');
            });

            card.addEventListener('drop', function (e) {
                card.classList.remove('is-drop-target');
                e.preventDefault();
                e.stopPropagation();

                var raw;
                try { raw = e.dataTransfer.getData('application/json'); } catch (_) { return; }
                if (!raw) return;

                var data;
                try { data = JSON.parse(raw); } catch (_) { return; }
                if (!data || data.kind !== 'fk-column') return;
                if (data.sourceCardId === draft.id) return;   // mesmo cartão

                self.openFkModal(data, draft);
            });
        },

        openFkModal: function (source, target) {
            var self = this;

            var targetColumns = (target.fields || []).map(function (f) {
                var m = String(f).match(/^([^:]+)/);
                return m ? m[1] : f;
            });

            var defaultTargetCol = targetColumns.indexOf('id') !== -1
                ? 'id'
                : (targetColumns[0] || '');

            var sourceConnId = source.sourceConnId || (target.source && target.source.connection_id);
            var sourceDb = source.sourceDb || (target.source && target.source.database);

            if (!sourceConnId || !sourceDb) {
                if (window.SqlWizardToast) {
                    window.SqlWizardToast.error('Sem conexão/database no cartão. Reimporta a tabela.');
                }
                return;
            }

            window.SqlWizardModal.confirmFk({
                sourceLabel: source.sourceTable + '.' + source.sourceColumn,
                targetTable: target.table,
                targetColumns: targetColumns,
                defaultTargetColumn: defaultTargetCol,
                defaultOnDelete: 'CASCADE',
                defaultOnUpdate: 'CASCADE'
            }, function (values) {
                self.createFk({
                    sourceConnId: sourceConnId,
                    sourceDb: sourceDb,
                    sourceTable: source.sourceTable,
                    column: source.sourceColumn,
                    targetTable: target.table,
                    targetColumn: values.targetColumn,
                    onDelete: values.onDelete,
                    onUpdate: values.onUpdate,
                    relationType: values.relationType
                });
            });
        },

        createFk: function (payload) {
            var self = this;

            if (!window.SqlWizardApi || !window.SqlWizardApi.createFk) {
                if (window.SqlWizardToast) window.SqlWizardToast.error('API não disponível');
                return;
            }

            window.SqlWizardApi.createFk(
                payload.sourceConnId,
                payload.sourceDb,
                payload.sourceTable,
                {
                    column: payload.column,
                    references_table: payload.targetTable,
                    references_column: payload.targetColumn,
                    on_delete: payload.onDelete,
                    on_update: payload.onUpdate,
                    relation_type: payload.relationType
                }
            ).then(function () {
                if (window.SqlWizardToast) {
                    window.SqlWizardToast.success(
                        'Relação criada: ' + payload.column + ' → ' + payload.targetTable + '.' + payload.targetColumn,
                        { title: 'SqlWizard' }
                    );
                }

                if (window.SqlWizardExplorer && typeof window.SqlWizardExplorer.load === 'function') {
                    window.SqlWizardExplorer.load();
                }

                setTimeout(function () { self.drawEdges(); }, 300);
            }).catch(function (err) {
                console.error('[canvas] erro ao criar FK:', err);
                if (window.SqlWizardToast) {
                    window.SqlWizardToast.error((err && err.message) || 'Erro ao criar relação');
                }
            });
        },


        /* ── Relações (FKs) ─────────────────────────────────── */
        drawEdges: function () {
            var svg = this.el ? this.el.querySelector('.canvas-edges') : null;
            if (!svg) return;

            svg.innerHTML = '';

            var explorer = window.SqlWizardExplorer;
            if (!explorer || !explorer.drafts) return;

            var self = this;

            this.cards.forEach(function (draftId) {
                var draft = self.getDraft(draftId, explorer.drafts);
                if (!draft) return;

                var fks = self.detectFKs(draft, explorer.drafts);
                if (!fks.length) return;

                var originCard = self.el.querySelector('.card[data-id="' + draftId + '"]');
                if (!originCard) return;

                fks.forEach(function (fk) {
                    var targetCard = self.el.querySelector('.card[data-table="' + fk.refTable + '"]');
                    if (!targetCard) return;

                    var fromEl = originCard.querySelector('[data-column="' + fk.column + '"]');
                    var toEl = targetCard.querySelector('[data-column="' + fk.refColumn + '"]');

                    if (!fromEl || !toEl) return;

                    var canvasRect = self.el.getBoundingClientRect();
                    var zoom = self.zoom;
                    var panX = self.panX;
                    var panY = self.panY;

                    function wx(screenX) { return (screenX - canvasRect.left - panX) / zoom; }
                    function wy(screenY) { return (screenY - canvasRect.top - panY) / zoom; }
                    var fromRect = fromEl.getBoundingClientRect();
                    var toRect = toEl.getBoundingClientRect();

                    var x1 = wx(fromRect.right);
                    var y1 = wy(fromRect.top + fromRect.height / 2);

                    var x2 = wx(toRect.left);
                    var y2 = wy(toRect.top + toRect.height / 2);

                    var NS = 'http://www.w3.org/2000/svg';

                    // ── Path (linha) ──
                    var path = document.createElementNS(NS, 'path');
                    path.setAttribute('d', self.bezier(x1, y1, x2, y2));
                    path.setAttribute('class', 'canvas-edge');
                    path.setAttribute('data-from', draftId);
                    path.setAttribute('data-to', fk.refTable);
                    path.setAttribute('data-column', fk.column);
                    path.setAttribute('data-relation', fk.relation);

                    // ── Ponto médio da curva ──
                    var mid = self.bezierMidpoint(x1, y1, x2, y2);

                    // ── Badge (quadrado com o tipo) ──
                    var badge = document.createElementNS(NS, 'g');
                    badge.setAttribute('class', 'canvas-edge-badge');
                    badge.setAttribute('transform', 'translate(' + mid.x + ',' + mid.y + ')');
                    badge.setAttribute('data-from', draftId);
                    badge.setAttribute('data-column', fk.column);

                    var badgeW = 36, badgeH = 18;

                    var rect = document.createElementNS(NS, 'rect');
                    rect.setAttribute('class', 'canvas-edge-badge-bg');
                    rect.setAttribute('x', -badgeW / 2);
                    rect.setAttribute('y', -badgeH / 2);
                    rect.setAttribute('width', badgeW);
                    rect.setAttribute('height', badgeH);
                    rect.setAttribute('rx', 4);

                    var text = document.createElementNS(NS, 'text');
                    text.setAttribute('class', 'canvas-edge-badge-text');
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('dominant-baseline', 'middle');
                    text.textContent = fk.relation || 'n:1';

                    badge.appendChild(rect);
                    badge.appendChild(text);

                    // ── Handlers partilhados (path + badge) ──
                    function onEnter(e) { self.showEdgeTooltip(e, fk); }
                    function onMove(e) { self.moveEdgeTooltip(e); }
                    function onLeave() { self.hideEdgeTooltip(); }

                    function onClick(e) {
                        e.stopPropagation();
                        self.cycleEdgeType(draftId, fk.column);
                    }

                    function onCtx(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (!window.SqlWizardContextMenu) return;

                        window.SqlWizardContextMenu.open(
                            e.clientX, e.clientY,
                            {
                                type: 'edge',
                                draftId: draftId,
                                table: draft.table,
                                column: fk.column,
                                refTable: fk.refTable,
                                refColumn: fk.refColumn,
                                relation: fk.relation
                            },
                            self.edgeMenuItems()
                        );
                    }

                    [path, badge].forEach(function (el) {
                        el.addEventListener('mouseenter', onEnter);
                        el.addEventListener('mousemove', onMove);
                        el.addEventListener('mouseleave', onLeave);
                        el.addEventListener('click', onClick);
                        el.addEventListener('contextmenu', onCtx);
                    });

                    // ── Grupo que agrega path + badge ──
                    var group = document.createElementNS(NS, 'g');
                    group.setAttribute('class', 'canvas-edge-group');
                    group.setAttribute('data-from', draftId);
                    group.setAttribute('data-column', fk.column);
                    group.appendChild(path);
                    group.appendChild(badge);

                    svg.appendChild(group);
                });
            });
        },

        /* ── Tooltip nas linhas ─────────────────────────────── */

        showEdgeTooltip: function (e, fk) {
            var tip = document.getElementById('edge-tooltip');
            if (!tip) return;

            tip.innerHTML =
                '<span class="edge-tooltip-type">' + this.escape(fk.relation) + '</span>' +
                '<span class="edge-tooltip-path">' +
                this.escape(fk.column) + ' → ' + this.escape(fk.refTable) +
                '</span>';

            tip.classList.add('is-visible');
            this.moveEdgeTooltip(e);
        },
        moveEdgeTooltip: function (e) {
            var tip = document.getElementById('edge-tooltip');
            if (!tip) return;

            // Posiciona acima da linha, centrado no rato
            var w = tip.offsetWidth || 100;
            var h = tip.offsetHeight || 24;

            var x = e.clientX - w / 2;
            var y = e.clientY - h - 10;   // 10px acima do rato

            // Clamp aos limites do ecrã
            x = Math.max(8, Math.min(x, window.innerWidth - w - 8));
            y = Math.max(8, y);

            tip.style.left = x + 'px';
            tip.style.top = y + 'px';
        },

        hideEdgeTooltip: function () {
            var tip = document.getElementById('edge-tooltip');
            if (tip) tip.classList.remove('is-visible');
        },

        cycleEdgeType: function (draftId, column) {
            var key = draftId + ':' + column;
            var types = ['n:1', '1:1', 'n:n'];

            var current = this.edgeTypes[key];
            if (!current) {
                // Lê o que está no SVG
                var path = this.el.querySelector('.canvas-edge[data-from="' + draftId + '"][data-column="' + column + '"]');
                current = path ? path.getAttribute('data-relation') : '1:n';
            }

            var idx = types.indexOf(current);
            var next = types[(idx + 1) % types.length];

            this.edgeTypes[key] = next;

            this.drawEdges();

            // Reabre o tooltip com o novo tipo (o path antigo foi removido)
            var newPath = this.el.querySelector('.canvas-edge[data-from="' + draftId + '"][data-column="' + column + '"]');
            if (newPath) {
                var rect = newPath.getBoundingClientRect();
                this.showEdgeTooltip(
                    { clientX: rect.left + rect.width / 2, clientY: rect.top },
                    { relation: next, column: column, refTable: '', refColumn: '' }
                );
            }
        },


        bezier: function (x1, y1, x2, y2) {
            var dx = Math.max(40, Math.abs(x2 - x1) * 0.4);
            var c1x = x1 + (x2 > x1 ? dx : -dx);
            var c2x = x2 + (x2 > x1 ? -dx : dx);

            return 'M ' + x1 + ' ' + y1 +
                ' C ' + c1x + ' ' + y1 + ', ' +
                c2x + ' ' + y2 + ', ' +
                x2 + ' ' + y2;
        },

        bezierMidpoint: function (x1, y1, x2, y2) {
            var dx = Math.max(40, Math.abs(x2 - x1) * 0.4);
            var c1x = x1 + (x2 > x1 ? dx : -dx);
            var c2x = x2 + (x2 > x1 ? -dx : dx);

            // Bezier cúbica em t = 0.5  →  (P0 + 3·P1 + 3·P2 + P3) / 8
            // Control points: P1=(c1x,y1), P2=(c2x,y2)
            var mx = (x1 + 3 * c1x + 3 * c2x + x2) / 8;
            var my = (y1 + 3 * y1 + 3 * y2 + y2) / 8;

            return { x: mx, y: my };
        },

        getDraft: function (id, drafts) {
            for (var i = 0; i < drafts.length; i++) {
                if (drafts[i].id === id) return drafts[i];
            }
            return null;
        },

        detectFKs: function (draft, allDrafts) {
            var fks = [];
            var fields = draft.fields || [];

            fields.forEach(function (field) {
                var s = String(field).trim();

                // Padrão 1: "team_id:ref.teams"
                var m = s.match(/^([a-z0-9_]+):ref\.([a-z0-9_]+)(?:[?=].*)?$/i);
                if (m) {
                    fks.push({
                        column: m[1],
                        refTable: m[2],
                        refColumn: 'id',
                        unique: /\bunique\b/i.test(s)
                    });
                    return;
                }

                // Padrão 2: convenção "team_id" → "teams"
                var m2 = s.match(/^([a-z0-9_]+)_id(?::.*)?$/i);
                if (m2) {
                    var base = m2[1];

                    var candidates = [
                        base,
                        base + 's',
                        base + 'es',
                        base.replace(/y$/, 'ies')
                    ];

                    for (var i = 0; i < candidates.length; i++) {
                        for (var j = 0; j < allDrafts.length; j++) {
                            if (allDrafts[j].table === candidates[i]) {
                                fks.push({
                                    column: m2[1] + '_id',
                                    refTable: candidates[i],
                                    refColumn: 'id',
                                    unique: /\bunique\b/i.test(s)
                                });
                                return;
                            }
                        }
                    }
                }
            });

            // Detetar tipo (bridge → n:n)
            var distinctTargets = {};
            fks.forEach(function (fk) { distinctTargets[fk.refTable] = true; });
            var isBridge = (fks.length >= 2 && Object.keys(distinctTargets).length >= 2);

            fks.forEach(function (fk) {
                // Override manual (guardado em memória)
                var key = draft.id + ':' + fk.column;
                if (Canvas.edgeTypes[key]) {
                    fk.relation = Canvas.edgeTypes[key];
                } else if (fk.unique) {
                    fk.relation = '1:1';
                } else if (isBridge) {
                    fk.relation = 'n:n';
                } else {
                    fk.relation = 'n:1';
                }
            });

            return fks;
        },


        parseField: function (raw) {
            var info = { name: '', type: 'str', nullable: false, isPk: false, isUnique: false };
            var s = String(raw).trim();

            if (s === 'id') {
                info.name = 'id';
                info.type = 'id';
                info.isPk = true;
                return info;
            }

            if (s === 'timestamps') {
                info.name = 'timestamps';
                info.type = 'datetime';
                return info;
            }

            if (s.indexOf('?') !== -1) {
                info.nullable = true;
                s = s.replace(/\?/g, '');
            }

            if (s.indexOf('=') !== -1) s = s.split('=', 2)[0];

            var colon = s.indexOf(':');
            if (colon === -1) {
                info.name = s;
                return info;
            }

            info.name = s.substring(0, colon);
            var rest = s.substring(colon + 1);
            var paren = rest.indexOf('(');
            info.type = paren === -1 ? rest : rest.substring(0, paren);

            if (info.name === 'email' || info.name.indexOf('unique') !== -1) {
                info.isUnique = true;
            }

            return info;
        },

        escape: function (str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
    };

    return Canvas;

})(window, document);
