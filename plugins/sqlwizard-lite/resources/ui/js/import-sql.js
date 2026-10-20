/* SqlWizard Lite — Import SQL */
window.SqlWizardImportSQL = (function (window, document) {
    'use strict';

    var root = null;

    function build() {
        var el = document.createElement('div');
        el.className = 'imp-backdrop';
        el.innerHTML =
            '<div class="imp-modal">' +
            '  <div class="imp-header">' +
            '    <span class="imp-icon">📥</span>' +
            '    <div class="imp-header-text">' +
            '      <div class="imp-title">Importar SQL</div>' +
            '      <div class="imp-subtitle">Cola CREATE TABLE / ALTER TABLE — o parser gera o diagrama</div>' +
            '    </div>' +
            '    <button class="imp-close" id="imp-close">x</button>' +
            '  </div>' +
            '  <div class="imp-body">' +
            '    <textarea class="imp-textarea" id="imp-sql" placeholder="-- Cola aqui o teu SQL...&#10;&#10;CREATE TABLE users (&#10;  id INT PRIMARY KEY,&#10;  name VARCHAR(255),&#10;  email VARCHAR(255)&#10;);&#10;&#10;CREATE TABLE posts (&#10;  id INT PRIMARY KEY,&#10;  user_id INT,&#10;  title VARCHAR(255),&#10;  FOREIGN KEY (user_id) REFERENCES users(id)&#10;);"></textarea>' +
            '    <div class="imp-info" id="imp-info"></div>' +
            '  </div>' +
            '  <div class="imp-footer">' +
            '    <span class="imp-meta" id="imp-meta"></span>' +
            '    <div class="imp-actions">' +
            '      <button class="imp-btn imp-btn-ghost" id="imp-cancel">Cancelar</button>' +
            '      <button class="imp-btn imp-btn-primary" id="imp-run">Importar</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(el);

        el.querySelector('#imp-close').onclick = close;
        el.querySelector('#imp-cancel').onclick = close;
        el.querySelector('#imp-run').onclick = runImport;
        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        var ta = el.querySelector('#imp-sql');
        ta.addEventListener('input', function () {
            var info = parseSQL(ta.value);
            var meta = el.querySelector('#imp-meta');
            var infoEl = el.querySelector('#imp-info');
            if (!info.tables.length) {
                meta.textContent = '';
                infoEl.innerHTML = '';
                return;
            }
            meta.textContent = info.tables.length + ' tabelas · ' + info.relations.length + ' relacoes';
            var lines = [];
            info.tables.forEach(function (t) {
                lines.push('  ✓ ' + t.name + ' (' + t.columns.length + ' colunas)');
            });
            info.relations.forEach(function (r) {
                var a = info.tables.find(function (t) { return t.id === r.from; });
                var b = info.tables.find(function (t) { return t.id === r.to; });
                if (a && b) lines.push('  🔗 ' + a.name + ' → ' + b.name);
            });
            infoEl.innerHTML = '<strong>Pre-visualizacao:</strong><br>' + lines.join('<br>');
        });

        return el;
    }

    /* ─── Parser SQL ─── */

    function parseSQL(sql) {
        var tables = [];
        var relations = [];
        var tableMap = {};

        // Remove comentários
        sql = sql.replace(/--[^\n]*/g, '');
        sql = sql.replace(/\/\*[\s\S]*?\*\//g, '');

        // ── CREATE TABLE ──
        var createRe = /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\[]?(\w+)[`"\]]?\s*\(([\s\S]*?)\)\s*(?:;|$)/gi;
        var m;
        while ((m = createRe.exec(sql)) !== null) {
            var tableName = m[1];
            var body = m[2];
            var cols = [];
            var fks = [];

            // Split colunas por vírgula (fora de parêntesis)
            var parts = [];
            var depth = 0;
            var cur = '';
            for (var i = 0; i < body.length; i++) {
                var ch = body[i];
                if (ch === '(') depth++;
                if (ch === ')') depth--;
                if (ch === ',' && depth === 0) {
                    parts.push(cur.trim());
                    cur = '';
                } else {
                    cur += ch;
                }
            }
            if (cur.trim()) parts.push(cur.trim());

            parts.forEach(function (part) {
                // FK inline: FOREIGN KEY (col) REFERENCES table (col)
                var fkMatch = part.match(/FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"\[]?(\w+)[`"\]]?\s*\(([^)]+)\)/i);
                if (fkMatch) {
                    fks.push({ column: fkMatch[1].trim(), ref_table: fkMatch[2], ref_column: fkMatch[3].trim() });
                    return;
                }
                // CONSTRAINT ... FOREIGN KEY
                var cMatch = part.match(/CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"\[]?(\w+)[`"\]]?\s*\(([^)]+)\)/i);
                if (cMatch) {
                    fks.push({ column: cMatch[1].trim(), ref_table: cMatch[2], ref_column: cMatch[3].trim() });
                    return;
                }
                // PRIMARY KEY (col) separado
                var pkMatch = part.match(/PRIMARY\s+KEY\s*\(([^)]+)\)/i);
                if (pkMatch) {
                    var pkCols = pkMatch[1].split(',').map(function (s) { return s.trim(); });
                    pkCols.forEach(function (pc) {
                        var col = cols.find(function (c) { return c.name === pc; });
                        if (col) col.pk = true;
                    });
                    return;
                }
                // Coluna normal: name type [constraints]
                var colMatch = part.match(/^[`"\[]?(\w+)[`"\]]?\s+([A-Z]+(?:\([^)]+\))?)/i);
                if (colMatch) {
                    var colName = colMatch[1];
                    var colType = colMatch[2].toUpperCase();
                    var isPk = /PRIMARY\s+KEY/i.test(part);
                    var nullable = !/NOT\s+NULL/i.test(part);
                    var defMatch = part.match(/DEFAULT\s+([^\s,]+)/i);
                    cols.push({
                        name: colName,
                        type: colType,
                        pk: isPk,
                        nullable: nullable,
                        default: defMatch ? defMatch[1] : null
                    });
                }
            });

            var tableId = 't_' + tableName;
            var table = {
                id: tableId,
                name: tableName,
                x: 60 + (tables.length % 4) * 360,
                y: 60 + Math.floor(tables.length / 4) * 300,
                columns: cols
            };
            tables.push(table);
            tableMap[tableName] = tableId;

            // Guardar FKs para processar depois
            fks.forEach(function (fk) {
                relations.push({
                    from: tableId,   // table com a FK
                    to: 'REF:' + fk.ref_table,
                    card: 'n:1'
                });
            });
        }

        // ── ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY ──
        var alterRe = /ALTER\s+TABLE\s+[`"\[]?(\w+)[`"\]]?\s+ADD\s+(?:CONSTRAINT\s+\w+\s+)?FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"\[]?(\w+)[`"\]]?\s*\(([^)]+)\)/gi;
        while ((m = alterRe.exec(sql)) !== null) {
            var fromTable = m[1];
            var refTable = m[3];
            if (tableMap[fromTable] && tableMap[refTable]) {
                relations.push({
                    from: tableMap[fromTable],
                    to: tableMap[refTable],
                    card: 'n:1'
                });
            }
        }

        // Resolver FKs com 'REF:'
        relations = relations.map(function (r) {
            if (r.to && r.to.indexOf('REF:') === 0) {
                var refName = r.to.substring(4);
                if (tableMap[refName]) {
                    return { from: r.from, to: tableMap[refName], card: r.card };
                }
                return null;
            }
            return r;
        }).filter(Boolean);

        // Remover duplicadas
        var seen = {};
        relations = relations.filter(function (r) {
            var key = r.from + '->' + r.to;
            if (seen[key]) return false;
            seen[key] = true;
            return true;
        });

        return { tables: tables, relations: relations };
    }

    function runImport() {
        var ta = root.querySelector('#imp-sql');
        var sql = ta.value.trim();
        if (!sql) {
            if (window.SqlWizardToast) SqlWizardToast.error('Cola SQL primeiro', { title: 'Vazio' });
            return;
        }
        var info = parseSQL(sql);
        if (!info.tables.length) {
            if (window.SqlWizardToast) SqlWizardToast.error('Nenhuma tabela encontrada', { title: 'Erro' });
            return;
        }
        if (!window.SqlWizardCanvas || !window.SqlWizardCanvas.setState) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas indisponivel', { title: 'Erro' });
            return;
        }
        window.SqlWizardCanvas.setState({ tables: info.tables, relations: info.relations });
        close();
        if (window.SqlWizardToast) {
            SqlWizardToast.success(
                info.tables.length + ' tabelas · ' + info.relations.length + ' relacoes importadas',
                { duration: 2500 }
            );
        }
    }

    function open() {
        if (root) root.remove();
        root = build();
    }

    function close() {
        if (root) { root.remove(); root = null; }
    }

    return { open: open, close: close, parseSQL: parseSQL };
})(window, document);
