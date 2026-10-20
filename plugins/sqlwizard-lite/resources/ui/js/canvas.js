/**
 * SqlWizard Lite — Canvas JS.
 * Estado em localStorage (1 draft). Sem backend, sem API.
 */
(() => {
    'use strict';

    const STORAGE_KEY = 'sqlwizard-lite-draft';
    const canvas = document.getElementById('canvas');
    const svg = document.getElementById('relations');
    const empty = document.getElementById('empty-state');
    const inspector = document.getElementById('inspector');
    const status = document.getElementById('status');
    const toastEl = document.getElementById('toast');

    let state = { tables: [], relations: [] };
    let selectedId = null;

    // ─── Helpers ─────────────────────────────────────────────

    function save() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) { }
    window.__SQLWIZARD_STATE__ = state;
    if (window.SqlWizardHistory && !window.SqlWizardHistory._applying) {
        window.SqlWizardHistory.push(state);
    }
}
    function load() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) state = JSON.parse(raw);
        } catch (e) { }
    }

    let toastTimer;
    function showToast(msg, kind = '') {
        clearTimeout(toastTimer);
        toastEl.textContent = msg;
        toastEl.className = 'toast show ' + kind;
        toastTimer = setTimeout(() => { toastEl.className = 'toast'; }, 2200);
    }

    function setStatus(msg) { status.textContent = msg; }

    function showValidationModal(issues, onConfirm) {
        const backdrop = document.createElement('div');
        backdrop.className = 'relation-modal-backdrop';
        backdrop.innerHTML = `
        <div class="relation-modal">
            <h3>⚠️ Avisos da relação</h3>
            <div class="relation-info">
                ${issues.map(i => `
                    <div>${i.level === 'error' ? '🔴' : '🟡'} ${i.message}</div>
                `).join('')}
            </div>
            <div class="relation-actions">
                <button class="btn" id="val-cancel">Cancelar</button>
                <button class="btn btn-primary" id="val-confirm">Criar mesmo assim</button>
            </div>
        </div>
    `;
        document.body.appendChild(backdrop);

        document.getElementById('val-cancel').onclick = () => backdrop.remove();
        document.getElementById('val-confirm').onclick = () => {
            backdrop.remove();
            onConfirm();
        };
        backdrop.onclick = e => { if (e.target === backdrop) backdrop.remove(); };
    }

    function escape(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    const uid = () => 't_' + Math.random().toString(36).slice(2, 9);

    // ─── Render principal ────────────────────────────────────

    function render() {
        canvas.querySelectorAll('.tbl').forEach(n => n.remove());

        state.tables.forEach(t => {
            const el = document.createElement('div');
            el.className = 'tbl' + (selectedId === t.id ? ' selected' : '');
            el.style.left = t.x + 'px';
            el.style.top = t.y + 'px';
            el.dataset.id = t.id;

            const cols = (t.columns || []).map(c => `
                <div class="tbl-col">
                    <span class="col-name">${c.pk ? '<span class="key-icon">🔑</span>' : ''}${escape(c.name)}</span>
                    <span class="col-type">${escape(c.type || '')}</span>
                </div>`).join('') || '<div class="tbl-col" style="opacity:.5">(sem colunas)</div>';

            el.innerHTML = `
                <div class="tbl-head"><span class="tbl-name">${escape(t.name)}</span></div>
                <div class="tbl-cols">${cols}</div>
                <div class="tbl-handle" title="Arrasta para ligar"></div>
            `;
            canvas.appendChild(el);
            makeDraggable(el, t);

            el.addEventListener('mousedown', e => {
                if (e.target.classList.contains('tbl-handle')) return;
                selectTable(t.id);
            });
        });

        redrawRelations();
        empty.style.display = state.tables.length ? 'none' : 'flex';
        setStatus(`${state.tables.length} tabelas · ${state.relations.length} relações`);
        save();
    }

    // ─── Desenhar relações (linhas) ──────────────────────────

    function redrawRelations() {
        svg.innerHTML = '';

        canvas.querySelectorAll('.relation-badge').forEach(el => el.remove());

        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        defs.innerHTML = `
            <marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5"
                    markerWidth="10" markerHeight="10" orient="auto-start-reverse">
                <path d="M 0 0 L 10 5 L 0 10 z" fill="#B84500"/>
            </marker>
        `;
        svg.appendChild(defs);

        state.relations.forEach((r, idx) => {
            const a = state.tables.find(t => t.id === r.from);
            const b = state.tables.find(t => t.id === r.to);
            if (!a || !b) return;

            const ax = a.x + 220, ay = a.y + 40;
            const bx = b.x, by = b.y + 40;
            const mx = (ax + bx) / 2;
            const my = (ay + by) / 2;

            const hit = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            hit.setAttribute('d', `M ${ax} ${ay} C ${mx} ${ay}, ${mx} ${by}, ${bx} ${by}`);
            hit.setAttribute('fill', 'none');
            hit.setAttribute('stroke', 'transparent');
            hit.setAttribute('stroke-width', '14');
            svg.appendChild(hit);

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${ax} ${ay} C ${mx} ${ay}, ${mx} ${by}, ${bx} ${by}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', '#B84500');
            path.setAttribute('stroke-width', '4');
            path.setAttribute('stroke-dasharray', '14 7');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('marker-end', 'url(#arrow)');
            svg.appendChild(path);

            const badge = document.createElement('div');
            badge.className = 'relation-badge';
            badge.textContent = r.card || 'n:1';
            badge.style.left = mx + 'px';
            badge.style.top = my + 'px';
            badge.dataset.index = idx;
            badge.title = 'Clique para editar';

            badge.addEventListener('click', ev => {
                ev.stopPropagation();
                openRelationModal(idx);
            });

            canvas.appendChild(badge);
        });
    }

    // ─── Arrastar tabelas ────────────────────────────────────

    function makeDraggable(el, table) {
        let ox = 0, oy = 0, dragging = false;

        el.addEventListener('mousedown', e => {
            if (e.target.classList.contains('tbl-handle')) return;
            dragging = true;
            ox = e.clientX - table.x;
            oy = e.clientY - table.y;
            el.style.zIndex = 10;
        });

        document.addEventListener('mousemove', e => {
            if (!dragging) return;
            table.x = Math.max(0, e.clientX - ox);
            table.y = Math.max(0, e.clientY - oy);
            el.style.left = table.x + 'px';
            el.style.top = table.y + 'px';
            redrawRelations();
        });

        document.addEventListener('mouseup', () => {
            if (!dragging) return;
            dragging = false;
            el.style.zIndex = '';
            save();
        });
    }

    // ═══════════════════════════════════════════════════════
    //  VALIDAÇÃO DE RELAÇÕES
    // ═══════════════════════════════════════════════════════

    function validateRelation(fromTable, toTable, card = 'n:1') {
        const issues = [];

        if (!fromTable || !toTable) {
            issues.push({ level: 'error', message: 'Tabelas inválidas' });
            return issues;
        }

        if (fromTable.id === toTable.id) {
            issues.push({ level: 'error', message: 'Não podes ligar uma tabela a si própria' });
        }

        const exists = state.relations.some(r =>
            (r.from === fromTable.id && r.to === toTable.id) ||
            (r.from === toTable.id && r.to === fromTable.id)
        );
        if (exists) {
            issues.push({ level: 'error', message: 'Já existe uma relação entre estas tabelas' });
        }

        if (hasCycle(fromTable.id, toTable.id)) {
            issues.push({ level: 'warning', message: 'Vai criar um ciclo entre tabelas' });
        }

        const hasPK = (toTable.columns || []).some(c => c.pk);
        if (!hasPK) {
            issues.push({
                level: 'warning',
                message: `A tabela "${toTable.name}" não tem chave primária`
            });
        }

        const fromHasPK = (fromTable.columns || []).some(c => c.pk);
        if (!fromHasPK && card.startsWith('1:')) {
            issues.push({
                level: 'warning',
                message: `A tabela "${fromTable.name}" não tem PK — relação 1:? pode ser inválida`
            });
        }

        if (card === 'n:n') {
            issues.push({
                level: 'info',
                message: 'Relação n:n requer tabela pivô (não gerada automaticamente)'
            });
        }

        const fromIdCol = (fromTable.columns || []).find(c => c.pk);
        const toIdCol = (toTable.columns || []).find(c => c.pk);

        if (fromIdCol && toIdCol) {
            const compat = areTypesCompatible(fromIdCol.type, toIdCol.type);
            if (!compat) {
                issues.push({
                    level: 'warning',
                    message: `Tipos diferentes: ${fromIdCol.type} → ${toIdCol.type}`
                });
            }
        }

        return issues;
    }

    function hasCycle(fromId, toId) {
        const visited = new Set();
        const stack = [toId];

        while (stack.length) {
            const current = stack.pop();
            if (current === fromId) return true;
            if (visited.has(current)) continue;
            visited.add(current);

            state.relations
                .filter(r => r.from === current)
                .forEach(r => stack.push(r.to));
        }

        return false;
    }

    function areTypesCompatible(t1, t2) {
        if (!t1 || !t2) return true;

        const norm = t => String(t).toUpperCase().replace(/\(.*\)/, '').trim();
        const a = norm(t1);
        const b = norm(t2);

        const ints = ['INT', 'BIGINT', 'SMALLINT', 'TINYINT'];
        const strings = ['VARCHAR', 'TEXT', 'CHAR'];
        const decimals = ['DECIMAL', 'FLOAT', 'DOUBLE', 'NUMERIC'];
        const dates = ['DATE', 'DATETIME', 'TIMESTAMP'];

        const sameGroup = (x, y) => {
            const groups = [ints, strings, decimals, dates];
            return groups.some(g => g.includes(x) && g.includes(y));
        };

        return a === b || sameGroup(a, b);
    }

    // ─── Criar relação (drag do handle) ──────────────────────

    function startRelation(e, fromTable) {
        e.preventDefault();
        e.stopPropagation();

        const sourceEl = canvas.querySelector(`.tbl[data-id="${fromTable.id}"]`);
        if (sourceEl) sourceEl.classList.add('relation-source');

        svg.style.pointerEvents = 'none';

        const temp = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        temp.setAttribute('stroke', '#B84500');
        temp.setAttribute('stroke-width', '5');
        temp.setAttribute('stroke-dasharray', '16 8');
        temp.setAttribute('stroke-linecap', 'round');
        temp.setAttribute('fill', 'none');
        svg.appendChild(temp);

        let currentTargetId = null;

        const clearTarget = () => {
            if (currentTargetId) {
                const prev = canvas.querySelector(`.tbl[data-id="${currentTargetId}"]`);
                if (prev) prev.classList.remove('relation-target');
                currentTargetId = null;
            }
        };

        const onMove = ev => {
            const ax = fromTable.x + 220, ay = fromTable.y + 40;
            const bx = ev.clientX, by = ev.clientY;
            const mx = (ax + bx) / 2;
            temp.setAttribute('d', `M ${ax} ${ay} C ${mx} ${ay}, ${mx} ${by}, ${bx} ${by}`);

            const el = document.elementFromPoint(ev.clientX, ev.clientY);
            const tbl = el && el.closest ? el.closest('.tbl') : null;

            const newTargetId = (tbl && tbl.dataset.id !== fromTable.id) ? tbl.dataset.id : null;

            if (newTargetId !== currentTargetId) {
                clearTarget();
                if (newTargetId) {
                    const newTbl = canvas.querySelector(`.tbl[data-id="${newTargetId}"]`);
                    if (newTbl) {
                        newTbl.classList.add('relation-target');
                        currentTargetId = newTargetId;
                    }
                }
            }
        };

        const onUp = ev => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);

            temp.remove();
            svg.style.pointerEvents = '';

            if (sourceEl) sourceEl.classList.remove('relation-source');
            clearTarget();

            const el = document.elementFromPoint(ev.clientX, ev.clientY);
            const tbl = el && el.closest ? el.closest('.tbl') : null;

            if (!tbl || !tbl.dataset.id) {
                showToast('Larga sobre outra tabela', '');
                return;
            }

            const toId = tbl.dataset.id;

            if (toId === fromTable.id) {
                showToast('Não podes ligar à mesma tabela', '');
                return;
            }

            const toTable = state.tables.find(t => t.id === toId);
            if (!toTable) {
                showToast('Tabela destino não encontrada', 'err');
                return;
            }

            const issues = validateRelation(fromTable, toTable, 'n:1');
            const errors = issues.filter(i => i.level === 'error');

            if (errors.length) {
                errors.forEach(e => showToast(e.message, 'err'));
                return;
            }

            const warnings = issues.filter(i => i.level === 'warning');
            if (warnings.length) {
                showValidationModal(warnings, () => {
                    state.relations.push({ from: fromTable.id, to: toId, card: 'n:1' });
                    render();
                    showToast('Relação criada (com avisos)', 'ok');
                });
                return;
            }

            state.relations.push({
                from: fromTable.id,
                to: toId,
                card: 'n:1'
            });

            showToast('Relação criada', 'ok');

            render();
        };

        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    }

    // ─── Selecionar tabela / inspector ───────────────────────

    function selectTable(id) {
        selectedId = id;
        const t = state.tables.find(x => x.id === id);
        if (!t) return;
        inspector.hidden = false;
        document.getElementById('insp-title').textContent = t.name;
        document.getElementById('insp-table-name').value = t.name;
        renderColumns(t);
        canvas.querySelectorAll('.tbl').forEach(n =>
            n.classList.toggle('selected', n.dataset.id === id)
        );
    }

    function renderColumns(t) {
        const list = document.getElementById('columns-list');
        list.innerHTML = '';
        (t.columns || []).forEach((c, i) => {
            const li = document.createElement('li');
            li.className = 'col-row';
            li.innerHTML = `
                <button class="col-pk ${c.pk ? 'on' : ''}" title="Chave primária">${c.pk ? '🔑' : '○'}</button>
                <input class="col-name-input" value="${escape(c.name)}" placeholder="nome">
                <select class="col-type-select">
                    ${['INT', 'BIGINT', 'VARCHAR(255)', 'TEXT', 'DATE', 'DATETIME', 'BOOLEAN', 'DECIMAL', 'JSON'].map(tp =>
                `<option ${c.type === tp ? 'selected' : ''}>${tp}</option>`).join('')}
                </select>
                <button class="col-del" title="Remover">×</button>
            `;
            li.querySelector('.col-pk').addEventListener('click', () => {
                c.pk = !c.pk; renderColumns(t); render();
            });
            li.querySelector('.col-name-input').addEventListener('input', ev => {
                c.name = ev.target.value; render();
            });
            li.querySelector('.col-type-select').addEventListener('change', ev => {
                c.type = ev.target.value; render();
            });
            li.querySelector('.col-del').addEventListener('click', () => {
                t.columns.splice(i, 1); renderColumns(t); render();
            });
            list.appendChild(li);
        });
    }

    // ─── Parser de fields ────────────────────────────────────

    function parseFieldString(raw) {
        raw = String(raw || '').trim();
        if (raw === '') return null;

        if (raw === 'id') {
            return { name: 'id', type: 'id', args: [], nullable: false, default: null, ref: null, isId: true };
        }

        const info = {
            name: '', type: 'string', args: [],
            nullable: false, default: null, ref: null, isId: false,
        };

        const eqPos = raw.indexOf('=');
        if (eqPos !== -1) {
            info.default = raw.slice(eqPos + 1);
            raw = raw.slice(0, eqPos);
        }

        const refMatch = raw.match(/:ref\.([a-z0-9_]+)/i);
        if (refMatch) {
            info.ref = refMatch[1];
            raw = raw.replace(refMatch[0], '');
        }

        if (raw.includes('?')) {
            info.nullable = true;
            raw = raw.replace(/\?/g, '');
        }

        const colonPos = raw.indexOf(':');
        if (colonPos === -1) {
            info.name = raw;
            return info;
        }

        info.name = raw.slice(0, colonPos);
        let typePart = raw.slice(colonPos + 1);

        const argsMatch = typePart.match(/\(([^)]+)\)/);
        if (argsMatch) {
            info.args = argsMatch[1].split(',').map(s => s.trim());
            typePart = typePart.replace(/\([^)]+\)/, '').trim();
        }

        info.type = typePart.toLowerCase();
        return info;
    }

    function fieldToColumn(fieldStr) {
        const info = parseFieldString(fieldStr);
        if (!info) return null;

        let liteType = 'INT';
        const t = (info.type || '').toLowerCase();

        if (t === 'id' || t === 'int' || t === 'integer') liteType = 'INT';
        else if (t === 'bigint') liteType = 'BIGINT';
        else if (t === 'string' || t === 'str') {
            const size = info.args[0] || 255;
            liteType = 'VARCHAR(' + size + ')';
        }
        else if (t === 'text') liteType = 'TEXT';
        else if (t === 'datetime' || t === 'timestamp') liteType = 'DATETIME';
        else if (t === 'date') liteType = 'DATE';
        else if (t === 'bool' || t === 'boolean') liteType = 'BOOLEAN';
        else if (t === 'decimal' || t === 'dec' || t === 'float' || t === 'double') liteType = 'DECIMAL';
        else if (t === 'json') liteType = 'JSON';

        return {
            name: info.name,
            type: liteType,
            pk: info.isId || info.name.toLowerCase() === 'id',
            nullable: info.nullable,
            default: info.default,
            ref: info.ref,
        };
    }

    // ─── Eventos da UI (inspector, toolbar) ──────────────────

    document.getElementById('insp-close').addEventListener('click', () => {
        inspector.hidden = true;
        selectedId = null;
        canvas.querySelectorAll('.tbl').forEach(n => n.classList.remove('selected'));
    });

    document.getElementById('insp-table-name').addEventListener('input', e => {
        const t = state.tables.find(x => x.id === selectedId);
        if (!t) return;
        t.name = e.target.value;
        document.getElementById('insp-title').textContent = t.name;
        render();
    });

    document.getElementById('btn-add-column').addEventListener('click', () => {
        const t = state.tables.find(x => x.id === selectedId);
        if (!t) return;
        t.columns = t.columns || [];
        t.columns.push({ name: 'nova_coluna', type: 'INT', pk: false });
        renderColumns(t); render();
    });

    document.getElementById('btn-delete-table').addEventListener('click', () => {
        if (!selectedId) return;
        if (!confirm('Apagar esta tabela?')) return;
        state.tables = state.tables.filter(t => t.id !== selectedId);
        state.relations = state.relations.filter(r =>
            r.from !== selectedId && r.to !== selectedId
        );
        inspector.hidden = true;
        selectedId = null;
        render();
    });

    document.getElementById('btn-add-table').addEventListener('click', () => {
        const t = {
            id: uid(),
            name: 'nova_tabela',
            x: 80 + state.tables.length * 40,
            y: 80 + state.tables.length * 40,
            columns: [{ name: 'id', type: 'INT', pk: true }]
        };
        state.tables.push(t);
        render();
        selectTable(t.id);
    });

    document.getElementById('btn-clear').addEventListener('click', () => {
        if (!confirm('Apagar tudo?')) return;
        state = { tables: [], relations: [] };
        localStorage.removeItem(STORAGE_KEY);
        inspector.hidden = true;
        selectedId = null;
        render();
    });

    document.getElementById('btn-export-json').addEventListener('click', () => {
        const exportState = {
            version: '1.0',
            generator: 'SqlWizard Lite',
            exported_at: new Date().toISOString(),
            tables: state.tables,
            relations: state.relations.map(r => {
                const a = state.tables.find(t => t.id === r.from);
                const b = state.tables.find(t => t.id === r.to);
                return {
                    from: r.from,
                    to: r.to,
                    from_table: a ? a.name : null,
                    to_table: b ? b.name : null,
                    card: r.card || 'n:1',
                };
            }),
        };

        const blob = new Blob([JSON.stringify(exportState, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'sqlwizard-lite.json';
        a.click();
        showToast('JSON exportado', 'ok');
    });

    document.getElementById('btn-export-sql').addEventListener('click', () => {
        const sql = state.tables.map(t => {
            const cols = (t.columns || []).map(c =>
                `  ${c.name} ${c.type || 'VARCHAR(255)'}${c.pk ? ' PRIMARY KEY' : ''}`
            ).join(',\n');
            return `CREATE TABLE ${t.name} (\n${cols}\n);`;
        }).join('\n\n');

        if (!sql) return showToast('Nada para exportar', 'err');
        const blob = new Blob([sql], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'schema.sql';
        a.click();
        showToast('SQL exportado', 'ok');
    });

    // ─── Importar JSON ───────────────────────────────────────

    document.getElementById('file-import').addEventListener('change', ev => {
        const f = ev.target.files[0];
        if (!f) return;

        const r = new FileReader();

        r.onload = () => {
            try {
                const raw = JSON.parse(r.result);

                if (!raw || typeof raw !== 'object' || !Array.isArray(raw.tables)) {
                    throw new Error('JSON inválido ou sem chave "tables"');
                }

                const tables = raw.tables.map((t, i) => {
                    const name = t.table || t.name || t.table_name || ('tabela_' + i);
                    const pos = t.position || {};
                    const x = (pos && typeof pos.x === 'number') ? pos.x : (60 + (i % 4) * 450);
                    const y = (pos && typeof pos.y === 'number') ? pos.y : (60 + Math.floor(i / 4) * 500);

                    let columns = [];
                    if (Array.isArray(t.fields)) {
                        columns = t.fields.map(fieldToColumn).filter(Boolean);
                    } else if (Array.isArray(t.columns)) {
                        columns = t.columns.map(c => ({
                            name: c.name || 'coluna',
                            type: c.type || 'INT',
                            pk: !!c.pk,
                        }));
                    }

                    return {
                        id: t.id || ('t_' + Math.random().toString(36).slice(2, 9)),
                        name,
                        x,
                        y,
                        columns,
                    };
                });

                if (tables.length === 0) {
                    throw new Error('O JSON não contém tabelas');
                }

                const COLS = 4;
                const GAP_X = 450;
                const GAP_Y = 500;
                const START_X = 60;
                const START_Y = 60;

                tables.forEach((t, i) => {
                    t.x = START_X + (i % COLS) * GAP_X;
                    t.y = START_Y + Math.floor(i / COLS) * GAP_Y;
                });

                const relations = (raw.relations || []).map(r => {
                    let fromId = null;
                    let toId = null;

                    if (r.from && tables.some(t => t.id === r.from)) {
                        fromId = r.from;
                    }
                    if (r.to && tables.some(t => t.id === r.to)) {
                        toId = r.to;
                    }

                    if (!fromId) {
                        const nameFrom = r.from_table || r.fromTable || r.from;
                        const t = tables.find(tt => tt.name === nameFrom);
                        if (t) fromId = t.id;
                    }
                    if (!toId) {
                        const nameTo = r.to_table || r.toTable || r.to;
                        const t = tables.find(tt => tt.name === nameTo);
                        if (t) toId = t.id;
                    }

                    if (!fromId || !toId) {
                        console.warn('[import] Relação ignorada:', r);
                        return null;
                    }

                    return {
                        from: fromId,
                        to: toId,
                        card: r.card || 'n:1'
                    };
                }).filter(Boolean);

                console.log('[import] Relações:', relations.length, '/', (raw.relations || []).length);

                state = { tables, relations };
                selectedId = null;
                inspector.hidden = true;
                render();
                showToast(
                    'Importadas ' + tables.length + ' tabelas · ' + relations.length + ' relações',
                    'ok'
                );
            } catch (err) {
                console.error('Import error:', err);
                showToast('Erro: ' + err.message, 'err');
            } finally {
                ev.target.value = '';
            }
        };

        r.onerror = () => {
            showToast('Erro a ler o ficheiro', 'err');
            ev.target.value = '';
        };

        r.readAsText(f);
    });

    // ─── Teclado ─────────────────────────────────────────────

    document.addEventListener('keydown', e => {
        if (e.key === 'Delete' && selectedId && document.activeElement.tagName !== 'INPUT') {
            document.getElementById('btn-delete-table').click();
        }
    });

    // ─── Handler DELEGADO para handles ───────────────────────

    canvas.addEventListener('mousedown', e => {
        const handle = e.target.closest('.tbl-handle');
        if (!handle) return;

        const tbl = handle.closest('.tbl');
        if (!tbl) return;

        const table = state.tables.find(t => t.id === tbl.dataset.id);
        if (!table) return;

        startRelation(e, table);
    }, true);

    // ═══════════════════════════════════════════════════════
    //  MODAL DE EDIÇÃO DE RELAÇÃO
    // ═══════════════════════════════════════════════════════

    let editingRelationIndex = -1;

    function openRelationModal(idx) {
        console.log('[openRelationModal] idx =', idx);

        const r = state.relations[idx];
        if (!r) {
            console.warn('[modal] relação não existe:', idx);
            return;
        }

        const a = state.tables.find(t => t.id === r.from);
        const b = state.tables.find(t => t.id === r.to);
        if (!a || !b) {
            console.warn('[modal] tabelas não encontradas');
            return;
        }

        editingRelationIndex = idx;

        const setText = (id, txt) => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = txt;
            } else {
                console.warn('[modal] falta #' + id);
            }
        };

        setText('rel-title', 'Relação #' + (idx + 1));
        setText('rel-subtitle', a.name + ' → ' + b.name);

        const currentCard = r.card || 'n:1';
        document.querySelectorAll('.card-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.card === currentCard);
        });

        const issues = validateRelation(a, b, currentCard);

        const infoEl = document.getElementById('rel-info');
        if (infoEl) {
            let html = `
                <div><strong>FROM:</strong> ${escape(a.name)}</div>
                <div><strong>TO:</strong> ${escape(b.name)}</div>
            `;

            if (issues.length) {
                html += '<div style="margin-top:12px;border-top:1px solid #E4E6EA;padding-top:10px">';
                issues.forEach(i => {
                    const icon = i.level === 'error' ? '🔴' : i.level === 'warning' ? '🟡' : 'ℹ️';
                    html += `<div style="font-size:0.78rem;margin-bottom:4px">${icon} ${escape(i.message)}</div>`;
                });
                html += '</div>';
            }

            infoEl.innerHTML = html;
        } else {
            console.warn('[modal] falta #rel-info');
        }

        const modal = document.getElementById('relation-modal');
        if (modal) {
            modal.style.display = 'grid';
            console.log('[modal] aberta ✅');
        } else {
            console.error('[modal] #relation-modal não existe!');
        }
    }

    function closeRelationModal() {
        document.getElementById('relation-modal').style.display = 'none';
        editingRelationIndex = -1;
    }

    // ─── Handlers da modal ───

    document.querySelectorAll('.card-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.card-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    document.getElementById('rel-save').addEventListener('click', () => {
        if (editingRelationIndex < 0) return;

        const activeBtn = document.querySelector('.card-btn.active');
        const newCard = activeBtn ? activeBtn.dataset.card : 'n:1';

        state.relations[editingRelationIndex].card = newCard;

        save();
        render();
        closeRelationModal();
        showToast('Relação atualizada para ' + newCard, 'ok');
    });

    document.getElementById('rel-delete').addEventListener('click', () => {
        if (editingRelationIndex < 0) return;
        if (!confirm('Eliminar esta relação?')) return;

        state.relations.splice(editingRelationIndex, 1);
        save();
        render();
        closeRelationModal();
        showToast('Relação eliminada', 'ok');
    });

    document.getElementById('rel-cancel').addEventListener('click', closeRelationModal);

    document.getElementById('relation-modal').addEventListener('click', e => {
        if (e.target.id === 'relation-modal') closeRelationModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeRelationModal();
    });

    // ─── Início ──────────────────────────────────────────────

    load();
    render();

    // ═══════════════════════════════════════════════════════
    //  API PÚBLICA (para preview / export / auto-layout)
    // ═══════════════════════════════════════════════════════

    window.SqlWizardCanvas = {
        render: render,
        save: save,
        getState: function () { return state; },
        setState: function (newState) { state = newState; render(); }
    };

})();
