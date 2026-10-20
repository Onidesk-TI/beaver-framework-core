/* SqlWizard Lite — Preview SQL ao vivo */
window.SqlWizardPreview = (function (window, document) {
    'use strict';

    var TYPES_MAP = {
        'INT':      { mysql: 'INT',        pgsql: 'INTEGER',   sqlite: 'INTEGER' },
        'BIGINT':   { mysql: 'BIGINT',     pgsql: 'BIGINT',    sqlite: 'INTEGER' },
        'VARCHAR':  { mysql: 'VARCHAR',    pgsql: 'VARCHAR',   sqlite: 'TEXT' },
        'TEXT':     { mysql: 'TEXT',       pgsql: 'TEXT',      sqlite: 'TEXT' },
        'DATE':     { mysql: 'DATE',       pgsql: 'DATE',      sqlite: 'TEXT' },
        'DATETIME': { mysql: 'DATETIME',   pgsql: 'TIMESTAMP', sqlite: 'TEXT' },
        'BOOLEAN':  { mysql: 'TINYINT(1)', pgsql: 'BOOLEAN',   sqlite: 'INTEGER' },
        'DECIMAL':  { mysql: 'DECIMAL',    pgsql: 'NUMERIC',   sqlite: 'REAL' },
        'JSON':     { mysql: 'JSON',       pgsql: 'JSONB',     sqlite: 'TEXT' }
    };

    function getState() {
        if (window.__SQLWIZARD_STATE__) return window.__SQLWIZARD_STATE__;
        if (window.SqlWizardCanvas && window.SqlWizardCanvas.getState) {
            try { return window.SqlWizardCanvas.getState(); } catch (e) {}
        }
        try {
            var raw = localStorage.getItem('sqlwizard-lite-draft') || localStorage.getItem('sqlwizard-lite');
            if (raw) return JSON.parse(raw);
        } catch (e) {}
        return { tables: [], relations: [] };
    }

    function sortTables(tables, relations, order) {
        if (order === 'alphabetical') {
            return tables.slice().sort(function (a, b) { return a.name.localeCompare(b.name); });
        }
        if (order !== 'topological') return tables.slice();
        var deps = {}, all = {}, result = [], visited = {}, visiting = {};
        tables.forEach(function (t) { deps[t.id] = []; all[t.id] = t; });
        relations.forEach(function (r) { if (deps[r.to] && r.from !== r.to) deps[r.to].push(r.from); });
        function visit(id) {
            if (visited[id] || visiting[id]) return;
            visiting[id] = true;
            (deps[id] || []).forEach(visit);
            visiting[id] = false;
            visited[id] = true;
            result.push(all[id]);
        }
        tables.forEach(function (t) { visit(t.id); });
        return result;
    }

    function quote(name, driver) {
        if (driver === 'mysql') return '`' + name + '`';
        return '"' + name + '"';
    }

    function mapType(type, driver) {
        var base = String(type || 'INT').toUpperCase().replace(/\(.*\)/, '').trim();
        var args = (type || '').match(/\((.*)\)/);
        var map = TYPES_MAP[base] || TYPES_MAP['VARCHAR'];
        return map[driver] + (args ? '(' + args[1] + ')' : '');
    }

    function formatDefault(val, driver) {
        var v = String(val).trim();
        if (v === '') return 'NULL';
        if (/^-?\d+(\.\d+)?$/.test(v)) return v;
        if (v.toLowerCase() === 'null') return 'NULL';
        if (v.toLowerCase() === 'true' || v.toLowerCase() === 'false') {
            return driver === 'pgsql' ? v.toUpperCase() : (v.toLowerCase() === 'true' ? '1' : '0');
        }
        if (v.toUpperCase() === 'CURRENT_TIMESTAMP') return v.toUpperCase();
        return "'" + v.replace(/'/g, "''") + "'";
    }

    function generateSQL(options) {
        options = options || {};
        var driver = options.driver || 'mysql';
        var order = options.order || 'topological';
        var noFk = !!options.no_fk;
        var noDrop = !!options.no_drop;
        var state = getState();
        var tables = sortTables(state.tables || [], state.relations || [], order);
        var relations = state.relations || [];
        var lines = [];

        lines.push('-- SqlWizard Lite — ' + new Date().toISOString());
        lines.push('-- Driver: ' + driver);
        lines.push('');

        if (!noDrop) {
            tables.slice().reverse().forEach(function (t) {
                lines.push('DROP TABLE IF EXISTS ' + quote(t.name, driver) + ';');
            });
            lines.push('');
        }

        tables.forEach(function (t) {
            var cols = (t.columns || []).map(function (c) {
                var def = '  ' + quote(c.name, driver) + ' ' + mapType(c.type, driver);
                if (c.pk) def += ' PRIMARY KEY';
                else if (c.nullable === false) def += ' NOT NULL';
                if (c.default != null && c.default !== '') def += ' DEFAULT ' + formatDefault(c.default, driver);
                return def;
            });
            if (!cols.length) cols.push('  -- sem colunas');
            lines.push('CREATE TABLE ' + quote(t.name, driver) + ' (');
            lines.push(cols.join(',\n'));
            lines.push(');');
            lines.push('');
        });

        if (!noFk && relations.length) {
            lines.push('-- Foreign keys');
            relations.forEach(function (r) {
                var a = tables.find(function (t) { return t.id === r.from; });
                var b = tables.find(function (t) { return t.id === r.to; });
                if (!a || !b) return;
                var pkB = (b.columns || []).find(function (c) { return c.pk; });
                var pkName = pkB ? pkB.name : 'id';
                var fkCol = b.name + '_' + pkName;
                lines.push('ALTER TABLE ' + quote(a.name, driver) +
                    ' ADD COLUMN ' + quote(fkCol, driver) + ' ' + mapType(pkB ? pkB.type : 'INT', driver) + ';');
                lines.push('ALTER TABLE ' + quote(a.name, driver) +
                    ' ADD CONSTRAINT ' + quote('fk_' + a.name + '_' + b.name, driver) +
                    ' FOREIGN KEY (' + quote(fkCol, driver) + ')' +
                    ' REFERENCES ' + quote(b.name, driver) + ' (' + quote(pkName, driver) + ');');
            });
        }
        return lines.join('\n');
    }

    function sanitize(name) {
        return String(name || '').replace(/[^a-zA-Z0-9_]/g, '_');
    }

    function generateMermaid() {
        var state = getState();
        var tables = state.tables || [];
        var relations = state.relations || [];
        var lines = ['erDiagram'];
        var arrows = {
            '1:1':  '||--||',
            '1:n':  '||--o{',
            'n:1':  '}o--||',
            'n:n':  '}o--o{'
        };

        tables.forEach(function (t) {
            lines.push('  ' + sanitize(t.name) + ' {');
            (t.columns || []).forEach(function (c) {
                var type = String(c.type || 'INT').toLowerCase().replace(/\(.*\)/, '');
                var key = c.pk ? 'PK' : '';
                var nullable = (c.nullable === false) ? '' : '?';
                lines.push('    ' + type + ' ' + sanitize(c.name) + ' ' + key + nullable);
            });
            lines.push('  }');
        });

        relations.forEach(function (r) {
            var a = tables.find(function (t) { return t.id === r.from; });
            var b = tables.find(function (t) { return t.id === r.to; });
            if (!a || !b) return;
            var card = r.card || 'n:1';
            var arrow = arrows[card] || '}o--||';
            lines.push('  ' + sanitize(a.name) + ' ' + arrow + ' ' + sanitize(b.name) + ' : "rel"');
        });
        return lines.join('\n');
    }

    function generateMarkdown() {
        var state = getState();
        var tables = state.tables || [];
        var relations = state.relations || [];
        var lines = ['# Schema', ''];
        tables.forEach(function (t) {
            lines.push('## ' + t.name);
            lines.push('');
            lines.push('| Coluna | Tipo | PK | Nulo | Default |');
            lines.push('|--------|------|----|------|---------|');
            (t.columns || []).forEach(function (c) {
                lines.push('| ' + c.name + ' | ' + c.type + ' | ' + (c.pk ? 'x' : '') + ' | ' + (c.nullable ? 'x' : '') + ' | ' + (c.default || '') + ' |');
            });
            lines.push('');
        });
        if (relations.length) {
            lines.push('## Relações', '');
            relations.forEach(function (r) {
                var a = tables.find(function (t) { return t.id === r.from; });
                var b = tables.find(function (t) { return t.id === r.to; });
                if (!a || !b) return;
                lines.push('- ' + a.name + ' **' + (r.card || 'n:1') + '** ' + b.name);
            });
        }
        return lines.join('\n');
    }

    function generateJSON() {
        var state = getState();
        return JSON.stringify({
            version: '1.0',
            generator: 'SqlWizard Lite',
            exported_at: new Date().toISOString(),
            tables: state.tables,
            relations: state.relations
        }, null, 2);
    }

    function generate(options) {
        var format = (options && options.format) || 'sql';
        var content;
        switch (format) {
            case 'mermaid':  content = generateMermaid();  break;
            case 'markdown': content = generateMarkdown(); break;
            case 'json':     content = generateJSON();     break;
            default:         content = generateSQL(options);
        }
        return { format: format, content: content, bytes: content.length };
    }

    /* ─── Painel lateral ─── */

    var panel = null;
    var pollTimer = null;

    function openPanel() {
        if (panel) panel.remove();
        panel = document.createElement('div');
        panel.className = 'sql-preview-panel';
        panel.innerHTML =
            '<div class="sp-head">' +
            '  <span class="sp-title">Preview SQL</span>' +
            '  <select class="sp-driver">' +
            '    <option value="mysql">MySQL</option>' +
            '    <option value="pgsql">PostgreSQL</option>' +
            '    <option value="sqlite">SQLite</option>' +
            '  </select>' +
            '  <button class="sp-close">x</button>' +
            '</div>' +
            '<pre class="sp-code"></pre>' +
            '<div class="sp-footer">' +
            '  <span class="sp-info"></span>' +
            '  <button class="sp-copy">Copiar</button>' +
            '</div>';
        document.body.appendChild(panel);

        panel.querySelector('.sp-close').onclick = closePanel;
        panel.querySelector('.sp-driver').onchange = update;
        panel.querySelector('.sp-copy').onclick = function () {
            var code = panel.querySelector('.sp-code').textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function () {
                    if (window.SqlWizardToast) SqlWizardToast.success('Copiado', { duration: 1500 });
                    else if (window.showToast) window.showToast('Copiado', 'ok');
                });
            }
        };
        update();
        pollTimer = setInterval(update, 600);
    }

    function closePanel() {
        if (panel) { panel.remove(); panel = null; }
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function update() {
        if (!panel) return;
        var driver = panel.querySelector('.sp-driver').value;
        try {
            var res = generate({ format: 'sql', driver: driver, order: 'topological' });
            panel.querySelector('.sp-code').textContent = res.content;
            panel.querySelector('.sp-info').textContent = res.bytes + ' bytes';
        } catch (e) {
            panel.querySelector('.sp-code').textContent = 'Erro: ' + e.message;
        }
    }

    function togglePanel() {
        if (panel) closePanel(); else openPanel();
    }

    return {
        generate: generate,
        getState: getState,
        openPanel: openPanel,
        closePanel: closePanel,
        togglePanel: togglePanel
    };
})(window, document);
