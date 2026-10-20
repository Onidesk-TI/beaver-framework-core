/* SQL · Sugestões/autocomplete para o editor */
window.SqlSuggest = (function (window, document) {
    'use strict';

    /* ── Dicionário base ───────────────────────────────────── */

    var KEYWORDS = [
        'SELECT','FROM','WHERE','GROUP','BY','ORDER','HAVING','LIMIT','OFFSET',
        'INSERT','INTO','VALUES','UPDATE','SET','DELETE',
        'CREATE','ALTER','DROP','TABLE','INDEX','VIEW','TRIGGER','PROCEDURE','FUNCTION',
        'DATABASE','SCHEMA','IF','NOT','EXISTS','OR','REPLACE',
        'PRIMARY','KEY','FOREIGN','REFERENCES','UNIQUE','CHECK','DEFAULT','NULL',
        'AUTO_INCREMENT','UNSIGNED','BIGINT','INT','SMALLINT','TINYINT',
        'VARCHAR','CHAR','TEXT','MEDIUMTEXT','LONGTEXT',
        'DATE','DATETIME','TIMESTAMP','TIME','YEAR',
        'DECIMAL','NUMERIC','FLOAT','DOUBLE','REAL','BOOL','BOOLEAN',
        'JSON','ENUM','SET','BLOB','BINARY','VARBINARY',
        'JOIN','INNER','LEFT','RIGHT','FULL','OUTER','CROSS','ON','USING',
        'AS','AND','OR','NOT','IN','LIKE','BETWEEN','IS','EXISTS',
        'ASC','DESC','DISTINCT','ALL','UNION','EXCEPT','INTERSECT',
        'CASE','WHEN','THEN','ELSE','END',
        'BEGIN','COMMIT','ROLLBACK','TRANSACTION','SAVEPOINT',
        'START','SHOW','DESCRIBE','DESC','EXPLAIN','USE','LOCK','UNLOCK',
        'TRUNCATE','GRANT','REVOKE','FLUSH','ANALYZE','OPTIMIZE'
    ];

    var FUNCTIONS = [
        'COUNT','SUM','AVG','MIN','MAX','GROUP_CONCAT',
        'NOW','CURDATE','CURTIME','DATE','TIME','YEAR','MONTH','DAY','HOUR','MINUTE','SECOND',
        'DATE_FORMAT','DATE_ADD','DATE_SUB','DATEDIFF','TIMESTAMPDIFF',
        'CONCAT','CONCAT_WS','SUBSTRING','SUBSTR','LEFT','RIGHT','LENGTH','CHAR_LENGTH',
        'UPPER','LOWER','TRIM','LTRIM','RTRIM','REPLACE','REVERSE','LPAD','RPAD',
        'ROUND','FLOOR','CEIL','ABS','POW','SQRT','MOD',
        'IFNULL','NULLIF','COALESCE','IF','CAST','CONVERT',
        'JSON_EXTRACT','JSON_UNQUOTE','JSON_OBJECT','JSON_ARRAY',
        'LAST_INSERT_ID','ROW_COUNT','UUID','MD5','SHA1','SHA2',
        'USER','DATABASE','VERSION','CONNECTION_ID'
    ];

    /* ── Estado ────────────────────────────────────────────── */

    var state = {
        input: null,
        dropdown: null,
        items: [],
        index: 0,
        word: '',
        wordStart: 0,
        dynamic: { tables: [], columns: [], databases: [] }
    };

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── API pública ──────────────────────────────────────── */

    function setDynamic(data) {
        if (!data) return;
        if (data.tables)    state.dynamic.tables    = data.tables.slice();
        if (data.columns)   state.dynamic.columns   = data.columns.slice();
        if (data.databases) state.dynamic.databases = data.databases.slice();
    }

    function attach(inputEl, opts) {
        if (!inputEl) return;
        state.input = inputEl;

        buildDropdown();

        inputEl.addEventListener('input', onInput);
        inputEl.addEventListener('keydown', onKeyDown);
        inputEl.addEventListener('blur', function () {
            // pequeno delay para permitir o clique no item
            setTimeout(hide, 150);
        });
    }

    /* ── Dropdown DOM ────────────────────────────────────── */

    function buildDropdown() {
        var el = document.createElement('div');
        el.className = 'sql-suggest';
        el.setAttribute('hidden', '');
        document.body.appendChild(el);
        state.dropdown = el;

        // clique nos itens
        el.addEventListener('mousedown', function (e) {
            var item = e.target.closest('.sql-suggest-item');
            if (!item) return;
            e.preventDefault(); // não perde o focus do input
            accept(item.getAttribute('data-index'));
        });
    }

    /* ── Análise do texto ────────────────────────────────── */

    function currentWord() {
        var el = state.input;
        var pos = el.selectionStart;
        var text = el.value.substring(0, pos);

        // palavra atual: letras, números, underscore
        var m = text.match(/([A-Za-z_][A-Za-z0-9_]*)$/);
        if (!m) return { word: '', start: pos, end: pos };

        return {
            word: m[1],
            start: pos - m[1].length,
            end: pos
        };
    }

    function computeSuggestions() {
        var w = state.word.toLowerCase();
        if (w.length < 1) return [];

        var all = [];

        // tabelas (peso 1)
        state.dynamic.tables.forEach(function (t) {
            if (t.toLowerCase().indexOf(w) === 0) {
                all.push({ value: t, kind: 'table', label: t, desc: 'tabela' });
            }
        });

        // colunas (peso 2)
        state.dynamic.columns.forEach(function (c) {
            if (c.toLowerCase().indexOf(w) === 0) {
                all.push({ value: c, kind: 'column', label: c, desc: 'coluna' });
            }
        });

        // keywords (peso 3)
        KEYWORDS.forEach(function (k) {
            if (k.toLowerCase().indexOf(w) === 0 && k.toLowerCase() !== w) {
                all.push({ value: k, kind: 'keyword', label: k, desc: 'palavra-chave' });
            }
        });

        // functions (peso 4)
        FUNCTIONS.forEach(function (f) {
            if (f.toLowerCase().indexOf(w) === 0 && f.toLowerCase() !== w) {
                all.push({ value: f + '()', kind: 'function', label: f, desc: 'função' });
            }
        });

        // dedupe por valor
        var seen = {};
        var unique = [];
        all.forEach(function (i) {
            if (seen[i.value]) return;
            seen[i.value] = true;
            unique.push(i);
        });

        return unique.slice(0, 10);
    }

    /* ── Eventos ────────────────────────────────────────── */

    function onInput() {
        var cw = currentWord();
        state.word = cw.word;
        state.wordStart = cw.start;

        if (cw.word.length < 1) {
            hide();
            return;
        }

        var items = computeSuggestions();
        if (!items.length) {
            hide();
            return;
        }

        state.items = items;
        state.index = 0;
        render();
    }

    function onKeyDown(e) {
        if (!state.items.length || state.dropdown.hasAttribute('hidden')) {
            // atalho manual: Ctrl+Space
            if (e.ctrlKey && e.key === ' ') {
                e.preventDefault();
                onInput();
                return;
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            state.index = (state.index + 1) % state.items.length;
            render();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            state.index = (state.index - 1 + state.items.length) % state.items.length;
            render();
        } else if (e.key === 'Tab' || e.key === 'Enter') {
            e.preventDefault();
            accept(state.index);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            hide();
        }
    }

    /* ── Aceitar uma sugestão ──────────────────────────── */

    function accept(idx) {
        var item = state.items[idx];
        if (!item) return;

        var el = state.input;
        var before = el.value.substring(0, state.wordStart);
        var after  = el.value.substring(el.selectionStart);

        el.value = before + item.value + after;

        // cursor após a inserção
        var newPos = before.length + item.value.length;
        el.setSelectionRange(newPos, newPos);
        el.focus();

        hide();
    }

    /* ── Render ─────────────────────────────────────────── */

    function render() {
        var el = state.dropdown;
        el.innerHTML = state.items.map(function (it, i) {
            return '<div class="sql-suggest-item' + (i === state.index ? ' is-active' : '') + '" ' +
                   'data-index="' + i + '">' +
                   '<span class="sql-suggest-kind sql-suggest-' + it.kind + '">' +
                       esc(it.kind.charAt(0).toUpperCase()) +
                   '</span>' +
                   '<span class="sql-suggest-value">' + esc(it.label) + '</span>' +
                   '<span class="sql-suggest-desc">' + esc(it.desc) + '</span>' +
                   '</div>';
        }).join('');

        // posicionar acima do cursor (input está no fundo)
        var rect = state.input.getBoundingClientRect();
        el.style.left = rect.left + 'px';
        el.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
        el.removeAttribute('hidden');
    }

    function hide() {
        if (state.dropdown) state.dropdown.setAttribute('hidden', '');
        state.items = [];
    }

    return { attach: attach, setDynamic: setDynamic };
})(window, document);