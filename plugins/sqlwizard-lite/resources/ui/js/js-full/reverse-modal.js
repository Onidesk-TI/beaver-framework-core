/* SqlWizard · Modal de Engenharia Reversa */
window.SqlWizardReverse = (function (window, document) {
    'use strict';

    var CHUNK_SIZE = 6;
    var CHUNK_DELAY = 120;

    var state = {
        connectionId: null, connectionName: '',
        database: '', allTables: [],
        selected: new Set(),
        importing: false, cancelled: false
    };

    var root = null;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtNum(n) {
        if (n === null || n === undefined) return '—';
        if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return String(n);
    }

    function fmtMb(mb) {
        if (mb === null || mb === undefined) return '—';
        if (mb < 1) return (mb * 1024).toFixed(0) + ' KB';
        return mb.toFixed(2) + ' MB';
    }

    function buildModal() {
        var el = document.createElement('div');
        el.className = 'rv-backdrop';
        el.innerHTML =
            '<div class="rv-modal">' +
            '  <div class="rv-header">' +
            '    <span class="rv-icon">🔧</span>' +
            '    <div class="rv-header-text">' +
            '      <div class="rv-title">Engenharia reversa</div>' +
            '      <div class="rv-subtitle" id="rv-subtitle">a carregar…</div>' +
            '    </div>' +
            '    <button class="rv-close" id="rv-close">✕</button>' +
            '  </div>' +
            '  <div class="rv-toolbar">' +
            '    <input type="text" id="rv-search" class="rv-search" placeholder="Filtrar tabelas…">' +
            '    <label class="rv-check"><input type="checkbox" id="rv-hide-system" checked> Esconder sistema</label>' +
            '    <label class="rv-check"><input type="checkbox" id="rv-only-pk" checked> Só com PK</label>' +
            '  </div>' +
            '  <div class="rv-list" id="rv-list"><div class="rv-empty">A carregar…</div></div>' +
            '  <div class="rv-footer">' +
            '    <span class="rv-counter" id="rv-counter">0 de 0 marcadas</span>' +
            '    <div class="rv-footer-actions">' +
            '      <button class="rv-btn rv-btn-ghost" id="rv-select-all">Todas</button>' +
            '      <button class="rv-btn rv-btn-ghost" id="rv-clear">Limpar</button>' +
            '      <button class="rv-btn rv-btn-ghost" id="rv-cancel">Cancelar</button>' +
            '      <button class="rv-btn rv-btn-primary" id="rv-import">Importar</button>' +
            '    </div>' +
            '  </div>' +
            '  <div class="rv-progress" id="rv-progress" hidden>' +
            '    <div class="rv-progress-bar"><div class="rv-progress-fill" id="rv-progress-fill"></div></div>' +
            '    <div class="rv-progress-text" id="rv-progress-text">A importar…</div>' +
            '    <div class="rv-progress-log" id="rv-progress-log"></div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#rv-close').addEventListener('click', cancel);
        el.querySelector('#rv-cancel').addEventListener('click', cancel);
        el.querySelector('#rv-import').addEventListener('click', runImport);
        el.querySelector('#rv-select-all').addEventListener('click', selectAll);
        el.querySelector('#rv-clear').addEventListener('click', clearAll);
        el.querySelector('#rv-search').addEventListener('input', renderList);
        el.querySelector('#rv-hide-system').addEventListener('change', renderList);
        el.querySelector('#rv-only-pk').addEventListener('change', renderList);

        el.addEventListener('click', function (e) {
            if (e.target === el && !state.importing) cancel();
        });

        return el;
    }

    function renderList() {
        if (!root) return;
        var search = root.querySelector('#rv-search').value.toLowerCase();
        var hideSystem = root.querySelector('#rv-hide-system').checked;
        var onlyPk = root.querySelector('#rv-only-pk').checked;

        var visible = state.allTables.filter(function (t) {
            if (hideSystem && t.looks_like_system) return false;
            if (onlyPk && !t.has_pk) return false;
            if (search && t.name.toLowerCase().indexOf(search) === -1) return false;
            return true;
        });

        var list = root.querySelector('#rv-list');
        if (!visible.length) {
            list.innerHTML = '<div class="rv-empty">Nenhuma tabela corresponde aos filtros</div>';
            updateCounter();
            return;
        }

        list.innerHTML = visible.map(function (t) {
            var checked = state.selected.has(t.name) ? ' checked' : '';
            var tags = [];
            if (!t.has_pk) tags.push('<span class="rv-tag rv-tag-warn">sem PK</span>');
            if (t.looks_like_system) tags.push('<span class="rv-tag rv-tag-sys">sistema</span>');
            if (t.size_mb && t.size_mb > 100) tags.push('<span class="rv-tag rv-tag-big">⚠ grande</span>');

            return '<label class="rv-row">' +
                '<input type="checkbox" data-table="' + esc(t.name) + '"' + checked + '>' +
                '<span class="rv-row-name">' + esc(t.name) + '</span>' +
                '<span class="rv-row-tags">' + tags.join('') + '</span>' +
                '<span class="rv-row-meta">' +
                    t.columns + ' cols · ' + t.fk_count + ' FK · ' +
                    fmtNum(t.rows_estimate) + ' rows · ' + fmtMb(t.size_mb) +
                '</span>' +
                '</label>';
        }).join('');

        list.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var t = cb.getAttribute('data-table');
                if (cb.checked) state.selected.add(t);
                else state.selected.delete(t);
                updateCounter();
            });
        });

        updateCounter();
    }

    function updateCounter() {
        if (!root) return;
        var n = state.selected.size;
        var total = state.allTables.length;
        root.querySelector('#rv-counter').textContent = n + ' de ' + total + ' marcadas';
        root.querySelector('#rv-import').textContent = 'Importar ' + (n || '');
        root.querySelector('#rv-import').disabled = n === 0;
    }

    function selectAll() {
        root.querySelectorAll('#rv-list input[type="checkbox"]').forEach(function (cb) {
            cb.checked = true;
            state.selected.add(cb.getAttribute('data-table'));
        });
        updateCounter();
    }

    function clearAll() {
        root.querySelectorAll('#rv-list input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
        state.selected.clear();
        updateCounter();
    }

    function runImport() {
        if (state.importing) return;
        var tables = Array.from(state.selected);
        if (!tables.length) return;

        state.importing = true;
        state.cancelled = false;

        var progress = root.querySelector('#rv-progress');
        progress.hidden = false;

        var fill = root.querySelector('#rv-progress-fill');
        var txt  = root.querySelector('#rv-progress-text');
        var log  = root.querySelector('#rv-progress-log');
        log.innerHTML = '';

        var chunks = [];
        for (var i = 0; i < tables.length; i += CHUNK_SIZE) {
            chunks.push(tables.slice(i, i + CHUNK_SIZE));
        }

        var totalCreated = [], totalRelations = [], totalErrors = [], done = 0;

        function processChunk(idx) {
            if (state.cancelled) { finish('Cancelado'); return; }
            if (idx >= chunks.length) { finish(null, totalCreated, totalRelations, totalErrors); return; }

            var chunk = chunks[idx];
            txt.textContent = 'Lote ' + (idx + 1) + ' de ' + chunks.length + ' (' + chunk.join(', ') + ')';

            fetch('/api/sqlwizard/connections/' + encodeURIComponent(state.connectionId) + '/reverse/batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ database: state.database, tables: chunk })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) throw new Error(res.error || 'Falha no lote');

                res.created.forEach(function (c) {
                    totalCreated.push(c);
                    log.innerHTML += '<div class="rv-log-ok">✓ ' + esc(c.table) + '</div>';
                });
                res.errors.forEach(function (e) {
                    totalErrors.push(e);
                    log.innerHTML += '<div class="rv-log-err">✗ ' + esc(e.table) + ' — ' + esc(e.error) + '</div>';
                });
                if (res.relations) totalRelations = totalRelations.concat(res.relations);

                done += chunk.length;
                fill.style.width = ((done / tables.length) * 100) + '%';
                log.scrollTop = log.scrollHeight;

                setTimeout(function () { processChunk(idx + 1); }, CHUNK_DELAY);
            })
            .catch(function (err) {
                txt.textContent = 'Erro no lote ' + (idx + 1) + ': ' + err.message;
                finish(err.message, totalCreated, totalRelations, totalErrors);
            });
        }

        processChunk(0);
    }

    function finish(error, created, relations, errors) {
        state.importing = false;
        created = created || [];
        relations = relations || [];
        errors = errors || [];

        console.time('[reverse] finish total');

        // ── passo 1: recarregar explorer ──
        console.time('[reverse] Explorer.load');
        if (window.SqlWizardExplorer && window.SqlWizardExplorer.load) {
            window.SqlWizardExplorer.load();
        }
        console.timeEnd('[reverse] Explorer.load');

        // ── passo 2: aguardar drafts e adicionar ao canvas em idle ──
        setTimeout(function () {
            console.time('[reverse] coletar drafts');
            var drafts = [];
            if (window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                var byId = {};
                window.SqlWizardExplorer.drafts.forEach(function (d) { byId[d.id] = d; });
                created.forEach(function (c) {
                    if (byId[c.id] && (!window.SqlWizardCanvas || !window.SqlWizardCanvas.has(c.id))) {
                        drafts.push(byId[c.id]);
                    }
                });
            }
            console.timeEnd('[reverse] coletar drafts');

            // adiciona 1 a 1, cedendo o main thread entre cada
            var idx = 0;
            function addNext() {
                if (idx >= drafts.length) {
                    // tudo adicionado — agora desenha e faz layout
                    setTimeout(finalStep, 30);
                    return;
                }

                window.SqlWizardCanvas.add(drafts[idx], { noDraw: true });
                idx++;

                // cede o main thread a cada 3 cards
                if (idx % 3 === 0) {
                    setTimeout(addNext, 0);
                } else {
                    addNext();
                }
            }

            if (drafts.length) {
                console.time('[reverse] addMany (cards)');
                addNext();
            } else {
                finalStep();
            }
        }, 400);

        function finalStep() {
            console.timeEnd('[reverse] addMany (cards)');

            // drawEdges 1x
            console.time('[reverse] drawEdges');
            if (window.SqlWizardCanvas && window.SqlWizardCanvas.drawEdges) {
                window.SqlWizardCanvas.drawEdges();
            }
            console.timeEnd('[reverse] drawEdges');

            // layout em idle
            setTimeout(function () {
                console.time('[reverse] layout');
                if (window.SqlWizardLayout) {
                    window.SqlWizardLayout.applyToCanvas(created.map(function (c) { return c.table; }));
                }
                console.timeEnd('[reverse] layout');

                var msg = 'Importadas ' + created.length + ' tabelas';
                if (errors.length) msg += ' · ' + errors.length + ' erros';
                if (window.SqlWizardToast) {
                    if (errors.length) SqlWizardToast.warning(msg);
                    else SqlWizardToast.success(msg);
                }

                console.timeEnd('[reverse] finish total');
                close();
            }, 50);
        }
    }

    function open(opts) {
        opts = opts || {};
        state.connectionId   = opts.connectionId || null;
        state.connectionName = opts.connectionName || '';
        state.database       = opts.database || '';
        state.allTables      = [];
        state.selected       = new Set();
        state.importing      = false;
        state.cancelled      = false;

        if (!state.connectionId) { console.warn('[reverse] sem connectionId'); return; }

        if (root) root.remove();
        root = buildModal();

        root.querySelector('#rv-subtitle').textContent = state.database + ' · a carregar tabelas…';

        fetch('/api/sqlwizard/connections/' + encodeURIComponent(state.connectionId) + '/reverse/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ database: state.database })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) throw new Error(res.error || 'Falha no preview');
            state.allTables = res.tables || [];
            root.querySelector('#rv-subtitle').textContent =
                state.database + ' · ' + res.total_tables + ' tabelas · ' + fmtMb(res.total_size_mb);

            state.allTables.forEach(function (t) {
                if (!t.looks_like_system && t.has_pk) state.selected.add(t.name);
            });

            renderList();
        })
        .catch(function (err) {
            root.querySelector('#rv-list').innerHTML =
                '<div class="rv-empty rv-error">Erro: ' + esc(err.message) + '</div>';
        });
    }

    function cancel() {
        if (state.importing) {
            if (!confirm('Cancelar a importação a meio? Os drafts já criados ficam.')) return;
            state.cancelled = true;
            return;
        }
        close();
    }

    function close() {
        if (root) { root.remove(); root = null; }
    }

    return { open: open, close: close };
})(window, document);
