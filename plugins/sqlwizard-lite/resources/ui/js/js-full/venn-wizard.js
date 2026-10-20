/* SqlWizard · Venn SQL Wizard
 *
 * Cria SQL a partir de um diagrama de Venn de 2 tabelas.
 * Regiões: A-only (LEFT JOIN ... IS NULL), B-only, A∩B (INNER JOIN), A∪B (UNION)
 */
window.SqlWizardVennWizard = (function (window, document) {
    'use strict';

    var root = null;
    var state = {
        tableA: '',
        tableB: '',
        keyA: 'id',
        keyB: 'id',
        region: 'intersection'   // 'a-only' | 'b-only' | 'intersection' | 'union'
    };

    var REGIONS = {
        'a-only': { label: 'A apenas', desc: 'Registos em A sem par em B' },
        'b-only': { label: 'B apenas', desc: 'Registos em B sem par em A' },
        'intersection': { label: 'A ∩ B', desc: 'Registos em ambos (JOIN)' },
        'union': { label: 'A ∪ B', desc: 'Tudo de A e de B (sem duplicados)' }
    };

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Construção da modal ──────────────────────────── */

    function build() {
        var el = document.createElement('div');
        el.className = 'vw-backdrop';
        el.innerHTML =
            '<div class="vw-modal">' +

            '  <div class="vw-header">' +
            '    <span class="vw-icon">🎯</span>' +
            '    <div class="vw-header-text">' +
            '      <div class="vw-title">Venn SQL Wizard</div>' +
            '      <div class="vw-subtitle">Gera SQL a partir de um diagrama de Venn</div>' +
            '    </div>' +
            '    <button class="vw-close" id="vw-close">✕</button>' +
            '  </div>' +

            '  <div class="vw-body">' +

            '    <div class="vw-config">' +
            '      <div class="vw-config-col">' +
            '        <div class="vw-tag vw-tag-a">A</div>' +
            '        <label class="vw-field">' +
            '          <span>Tabela</span>' +
            '          <select id="vw-table-a"><option value="">— escolher —</option></select>' +
            '        </label>' +
            '        <label class="vw-field">' +
            '          <span>Coluna chave</span>' +
            '          <input type="text" id="vw-key-a" value="id" placeholder="id">' +
            '        </label>' +
            '      </div>' +

            '      <div class="vw-config-col">' +
            '        <div class="vw-tag vw-tag-b">B</div>' +
            '        <label class="vw-field">' +
            '          <span>Tabela</span>' +
            '          <select id="vw-table-b"><option value="">— escolher —</option></select>' +
            '        </label>' +
            '        <label class="vw-field">' +
            '          <span>Coluna chave</span>' +
            '          <input type="text" id="vw-key-b" value="id" placeholder="id">' +
            '        </label>' +
            '      </div>' +
            '    </div>' +

            '    <div class="vw-diagram-wrap">' +
            '      <svg class="vw-diagram" viewBox="0 0 400 220" id="vw-svg">' +
            '        <defs>' +
            '          <clipPath id="vw-clip-a"><circle cx="140" cy="110" r="80"/></clipPath>' +
            '          <clipPath id="vw-clip-b"><circle cx="260" cy="110" r="80"/></clipPath>' +
            '          <mask id="vw-mask-not-a">' +
            '            <rect width="400" height="220" fill="white"/>' +
            '            <circle cx="140" cy="110" r="80" fill="black"/>' +
            '          </mask>' +
            '          <mask id="vw-mask-not-b">' +
            '            <rect width="400" height="220" fill="white"/>' +
            '            <circle cx="260" cy="110" r="80" fill="black"/>' +
            '          </mask>' +
            '        </defs>' +

            '        <circle class="vw-base" cx="140" cy="110" r="80" fill="#2b5cb8" fill-opacity="0.08"/>' +
            '        <circle class="vw-base" cx="260" cy="110" r="80" fill="#b03a3a" fill-opacity="0.08"/>' +

            '        <circle class="vw-region vw-region-a-only" cx="140" cy="110" r="80" ' +
            '                fill="#2b5cb8" fill-opacity="0.7" mask="url(#vw-mask-not-b)" data-region="a-only"/>' +
            '        <circle class="vw-region vw-region-b-only" cx="260" cy="110" r="80" ' +
            '                fill="#b03a3a" fill-opacity="0.7" mask="url(#vw-mask-not-a)" data-region="b-only"/>' +
            '        <circle class="vw-region vw-region-intersection" cx="260" cy="110" r="80" ' +
            '                fill="#f0c419" fill-opacity="0.85" clip-path="url(#vw-clip-a)" data-region="intersection"/>' +

            '        <circle class="vw-outline vw-outline-a" cx="140" cy="110" r="80" ' +
            '                fill="none" stroke="#2b5cb8" stroke-width="2"/>' +
            '        <circle class="vw-outline vw-outline-b" cx="260" cy="110" r="80" ' +
            '                fill="none" stroke="#b03a3a" stroke-width="2"/>' +

            '        <text class="vw-label vw-label-a" x="90" y="115">A</text>' +
            '        <text class="vw-label vw-label-ab" x="200" y="115">A∩B</text>' +
            '        <text class="vw-label vw-label-b" x="300" y="115">B</text>' +

            '        <text class="vw-name vw-name-a" x="80" y="205" text-anchor="middle" id="vw-name-a-el">—</text>' +
            '        <text class="vw-name vw-name-b" x="320" y="205" text-anchor="middle" id="vw-name-b-el">—</text>' +
            '      </svg>' +
            '    </div>' +

            '    <div class="vw-regions" id="vw-regions"></div>' +

            '    <div class="vw-preview-label">SQL gerado</div>' +
            '    <pre class="vw-preview" id="vw-preview">// Escolhe as tabelas e a região</pre>' +
            '  </div>' +

            '  <div class="vw-footer">' +
            '    <span class="vw-info" id="vw-info"></span>' +
            '    <div class="vw-actions">' +
            '      <button class="vw-btn vw-btn-ghost" id="vw-copy">📋 Copiar</button>' +
            '      <button class="vw-btn vw-btn-ghost" id="vw-download">⬇ Descarregar</button>' +
            '      <button class="vw-btn vw-btn-primary" id="vw-sandbox">🎯 Enviar para Sandbox</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#vw-close').onclick = close;
        el.querySelector('#vw-copy').onclick = copySql;
        el.querySelector('#vw-download').onclick = downloadSql;
        el.querySelector('#vw-sandbox').onclick = sendToSandbox;

        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        // SVG click handler
        var svg = el.querySelector('#vw-svg');
        svg.addEventListener('click', onSvgClick);

        // inputs
        ['#vw-table-a', '#vw-table-b', '#vw-key-a', '#vw-key-b'].forEach(function (sel) {
            var input = el.querySelector(sel);
            input.addEventListener('input', onInputChange);
            input.addEventListener('change', onInputChange);
        });

        return el;
    }

    function onSvgClick(e) {
        var svg = root.querySelector('#vw-svg');
        var pt = svg.createSVGPoint();
        pt.x = e.clientX;
        pt.y = e.clientY;

        var svgPt = pt.matrixTransform(svg.getScreenCTM().inverse());
        var x = svgPt.x, y = svgPt.y;

        // círculos: A (140,110,r80), B (260,110,r80)
        var inA = Math.pow(x - 140, 2) + Math.pow(y - 110, 2) <= 80 * 80;
        var inB = Math.pow(x - 260, 2) + Math.pow(y - 110, 2) <= 80 * 80;

        if (inA && inB) state.region = 'intersection';
        else if (inA) state.region = 'a-only';
        else if (inB) state.region = 'b-only';
        else return;

        render();
    }

    function onInputChange() {
        var prevA = state.tableA;
        var prevB = state.tableB;

        state.tableA = root.querySelector('#vw-table-a').value;
        state.tableB = root.querySelector('#vw-table-b').value;

        // se as tabelas mudaram, tenta auto-detectar chaves
        var tablesChanged = (state.tableA !== prevA || state.tableB !== prevB);

        if (tablesChanged && state.tableA && state.tableB) {
            var keys = detectKeys(state.tableA, state.tableB);
            if (keys) {
                state.keyA = keys.keyA;
                state.keyB = keys.keyB;
                root.querySelector('#vw-key-a').value = keys.keyA;
                root.querySelector('#vw-key-b').value = keys.keyB;
            }
        } else {
            // atualização manual das chaves
            state.keyA = root.querySelector('#vw-key-a').value.trim() || 'id';
            state.keyB = root.querySelector('#vw-key-b').value.trim() || 'id';
        }

        render();
    }

    /* ── Popula selects com drafts do explorer ────────── */

    function fillTables() {
        var explorer = window.SqlWizardExplorer;
        if (!explorer || !explorer.drafts) return;

        var options = '<option value="">— escolher —</option>' +
            explorer.drafts.map(function (d) {
                return '<option value="' + esc(d.table) + '">' +
                    esc(d.table) + '</option>';
            }).join('');

        root.querySelector('#vw-table-a').innerHTML = options;
        root.querySelector('#vw-table-b').innerHTML = options;

        // restaura valores
        if (state.tableA) root.querySelector('#vw-table-a').value = state.tableA;
        if (state.tableB) root.querySelector('#vw-table-b').value = state.tableB;
    }

    /* ── Auto-detecção de coluna chave ────────────────── */

    /**
     * Procura a melhor coluna para JOIN entre tableA e tableB.
     * Devolve { keyA, keyB } ou null.
     *
     * Estratégia (por ordem):
     *  1. FK explícita (campo :ref.outraTabela)
     *  2. Coluna com nome comum em ambas (ex: ambas têm "encomenda_id")
     *  3. Convenção: tableA tem "<tableB>_id" ou vice-versa
     *  4. Fallback: "id" em ambos
     */
    function detectKeys(tableA, tableB) {
        var explorer = window.SqlWizardExplorer;
        if (!explorer || !explorer.drafts) return null;

        var draftA = null, draftB = null;
        explorer.drafts.forEach(function (d) {
            if (d.table === tableA) draftA = d;
            if (d.table === tableB) draftB = d;
        });

        if (!draftA || !draftB) return null;

        var fieldsA = (draftA.fields || []).map(fieldName);
        var fieldsB = (draftB.fields || []).map(fieldName);

        // 1. FK explícita: A aponta para B (ex: A.encomenda_id:ref.faturas)
        var fkA = findFkTo(draftA, tableB);
        if (fkA) return { keyA: fkA.column, keyB: 'id' };

        // 2. FK explícita: B aponta para A
        var fkB = findFkTo(draftB, tableA);
        if (fkB) return { keyA: 'id', keyB: fkB.column };

        // 3. Coluna comum — mas SÓ se for uma coluna "chave-ável"
        var common = fieldsA.filter(function (fa) {
            return fieldsB.indexOf(fa) !== -1 && isJoinableColumn(fa);
        });
        if (common.length) {
            // prefere <x>_id sobre outras comuns
            common.sort(function (a, b) {
                var aId = /_id$/i.test(a) ? 0 : 1;
                var bId = /_id$/i.test(b) ? 0 : 1;
                return aId - bId;
            });
            return { keyA: common[0], keyB: common[0] };
        }

        // 4. Convenção <tableB>_id em A
        var byNameA = fieldsA.indexOf(singular(tableB) + '_id');
        if (byNameA !== -1) return { keyA: singular(tableB) + '_id', keyB: 'id' };

        // 5. Convenção <tableA>_id em B
        var byNameB = fieldsB.indexOf(singular(tableA) + '_id');
        if (byNameB !== -1) return { keyA: 'id', keyB: singular(tableA) + '_id' };

        // 6. Fallback
        return { keyA: 'id', keyB: 'id' };
    }

    /**
     * Uma coluna só faz sentido como chave de JOIN se for "_id" ou um
     * identificador conhecido, e NUNCA texto livre / timestamp / flag.
     */
    function isJoinableColumn(name) {
        var n = String(name).toLowerCase();

        if (/^(observacoes|notas|notes|descricao|morada|endereco|comentario|mensagem|texto|conteudo)$/i.test(n)) return false;
        if (/^(created_at|updated_at|criado_em|atualizado_em|deleted_at|data_|dt_)/i.test(n)) return false;
        if (/^(ativo|active|enabled|is_|has_|flag_|estado|status|tipo)$/i.test(n)) return false;

        if (/_id$/i.test(n)) return true;
        if (/^(id|uuid|code|codigo|sku|slug|nif|nipc|email|e_mail|mail|username|login)$/i.test(n)) return true;

        return false;
    }

    function findFkTo(draft, targetTable) {
        for (var i = 0; i < (draft.fields || []).length; i++) {
            var f = String(draft.fields[i]);
            var m = f.match(/^([a-z0-9_]+):ref\.([a-z0-9_]+)/i);
            if (m && m[2] === targetTable) {
                return { column: m[1], refTable: m[2] };
            }
        }
        return null;
    }

    function fieldName(raw) {
        var s = String(raw).trim();
        if (s === 'id') return 'id';
        var m = s.match(/^([^:?=]+)/);
        return m ? m[1].trim() : s;
    }

    function singular(word) {
        var w = String(word);
        if (/ies$/.test(w)) return w.replace(/ies$/, 'y');
        if (/es$/.test(w)) return w.replace(/es$/, '');
        if (/s$/.test(w)) return w.replace(/s$/, '');
        return w;
    }

    /* ── Render ───────────────────────────────────────── */

    function render() {
        // nomes no diagrama
        root.querySelector('#vw-name-a-el').textContent = state.tableA || 'A';
        root.querySelector('#vw-name-b-el').textContent = state.tableB || 'B';

        // realça a região ativa
        var svg = root.querySelector('#vw-svg');

        // reset
        svg.removeAttribute('data-region');
        root.querySelectorAll('.vw-region').forEach(function (r) {
            r.classList.remove('is-active');
        });

        if (state.region === 'union') {
            // união: pinta tudo (A + B + interceção em amarelo)
            svg.setAttribute('data-region', 'union');
        } else if (state.region) {
            var target = root.querySelector('.vw-region-' + state.region);
            if (target) target.classList.add('is-active');
        }

        // botões de região
        var regionsEl = root.querySelector('#vw-regions');
        regionsEl.innerHTML = Object.keys(REGIONS).map(function (key) {
            var r = REGIONS[key];
            var active = state.region === key ? ' is-active' : '';
            return '<button class="vw-region-btn' + active + '" data-region="' + key + '">' +
                '<span class="vw-region-code">' + esc(keyLabel(key)) + '</span>' +
                '<span class="vw-region-desc">' + esc(r.desc) + '</span>' +
                '</button>';
        }).join('');

        regionsEl.querySelectorAll('.vw-region-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.region = btn.getAttribute('data-region');
                render();
            });
        });

        // SQL
        var sql = generateSql();
        var preview = root.querySelector('#vw-preview');
        preview.textContent = sql || '// Escolhe as tabelas';

        var info = root.querySelector('#vw-info');
        info.textContent = sql ? sql.split('\n').length + ' linhas' : '';

        root._sql = sql;
    }

    function keyLabel(key) {
        return {
            'a-only': 'A só',
            'b-only': 'B só',
            'intersection': 'A ∩ B',
            'union': 'A ∪ B'
        }[key] || key;
    }

    /* ── Geração do SQL ───────────────────────────────── */

    function generateSql() {
        var a = state.tableA;
        var b = state.tableB;

        if (!a || !b) return '';

        var ka = state.keyA;
        var kb = state.keyB;

        var sql = '';

        switch (state.region) {
            case 'a-only':
                sql =
                    '-- Registos em ' + a + ' sem par em ' + b + '\n' +
                    'SELECT a.*\n' +
                    'FROM `' + a + '` a\n' +
                    'LEFT JOIN `' + b + '` b\n' +
                    '  ON a.`' + ka + '` = b.`' + kb + '`\n' +
                    'WHERE b.`' + kb + '` IS NULL;';
                break;

            case 'b-only':
                sql =
                    '-- Registos em ' + b + ' sem par em ' + a + '\n' +
                    'SELECT b.*\n' +
                    'FROM `' + b + '` b\n' +
                    'LEFT JOIN `' + a + '` a\n' +
                    '  ON b.`' + kb + '` = a.`' + ka + '`\n' +
                    'WHERE a.`' + ka + '` IS NULL;';
                break;

            case 'intersection':
                sql =
                    '-- Registos em ambos ' + a + ' e ' + b + '\n' +
                    'SELECT a.*, b.*\n' +
                    'FROM `' + a + '` a\n' +
                    'INNER JOIN `' + b + '` b\n' +
                    '  ON a.`' + ka + '` = b.`' + kb + '`;';
                break;

            case 'union':
                sql =
                    '-- Todos os registos de ' + a + ' e ' + b + '\n' +
                    'SELECT a.*\n' +
                    'FROM `' + a + '` a\n' +
                    'LEFT JOIN `' + b + '` b\n' +
                    '  ON a.`' + ka + '` = b.`' + kb + '`\n' +
                    'UNION\n' +
                    'SELECT b.*\n' +
                    'FROM `' + b + '` b\n' +
                    'LEFT JOIN `' + a + '` a\n' +
                    '  ON b.`' + kb + '` = a.`' + ka + '`;';
                break;
        }

        return sql;
    }

    /* ── Ações ────────────────────────────────────────── */

    function copySql() {
        if (!root._sql) return;
        navigator.clipboard.writeText(root._sql).then(function () {
            if (window.SqlWizardToast) SqlWizardToast.success('SQL copiado', { duration: 1500 });
        });
    }

    function downloadSql() {
        if (!root._sql) return;

        var name = 'venn-' + state.tableA + '-' + state.tableB + '.sql';
        var blob = new Blob([root._sql], { type: 'text/sql' });
        var url = URL.createObjectURL(blob);

        var a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
    }

        function sendToSandbox() {
        // guarda o SQL ANTES de fechar (o close() faz root = null)
        var sql = (root && root._sql) ? root._sql : '';
        if (!sql) {
            if (window.SqlWizardToast) SqlWizardToast.warning('Gera o SQL primeiro', { duration: 2500 });
            return;
        }

        var bottom = window.SqlWizardBottom;
        if (!bottom) {
            if (window.SqlWizardToast) SqlWizardToast.error('Painel inferior indisponível');
            return;
        }

        // ── NOVO: encontra o draft que corresponde à tabela A do wizard ──
        // A sandbox certa é a do draft que contém state.tableA,
        // porque é essa que tem as tabelas do SQL gerado.
        var targetTable = state.tableA;
        var draftForSql = null;
        var explorer = window.SqlWizardExplorer;

        if (explorer && Array.isArray(explorer.drafts) && targetTable) {
            for (var i = 0; i < explorer.drafts.length; i++) {
                if (explorer.drafts[i] && explorer.drafts[i].table === targetTable) {
                    draftForSql = explorer.drafts[i];
                    break;
                }
            }
        }

        if (!draftForSql) {
            if (window.SqlWizardToast) {
                SqlWizardToast.warning(
                    'Não encontrei o draft de "' + targetTable + '". Abre a tabela no canvas primeiro.',
                    { duration: 4000 }
                );
            }
            return;
        }

        close();

        // ── NOVO: passa o draft certo ao setActiveTab ──
        // O bottom.js atualiza o currentDraft e faz loadSandbox automaticamente.
        bottom.setActiveTab('sql', draftForSql);

        setTimeout(function () {
            var ta = document.getElementById('bottom-sql-input');

            if (ta) {
                ta.value = sql;
                ta.focus();
                if (window.SqlWizardToast) {
                    SqlWizardToast.success('SQL colado no editor', { duration: 2000 });
                }
                return;
            }

            // textarea ainda não existe → loadSandbox é async, dá mais um pouco
            setTimeout(function () {
                var ta2 = document.getElementById('bottom-sql-input');

                if (ta2) {
                    ta2.value = sql;
                    ta2.focus();
                    if (window.SqlWizardToast) {
                        SqlWizardToast.success('SQL colado no editor', { duration: 2000 });
                    }
                    return;
                }

                // textarea continua a não existir → falta sandbox
                if (!bottom.sandbox) {
                    if (window.SqlWizardToast) {
                        SqlWizardToast.warning(
                            'Cria uma sandbox primeiro (botão "+ Criar" na tab SQL)',
                            { duration: 4000 }
                        );
                    }

                    if (window.SqlWizardPointer) {
                        window.SqlWizardPointer.point(
                            '.bottom-sql-bar [data-rows="10"]',
                            { label: 'Criar sandbox', position: 'bottom', duration: 4000 }
                        );
                    }
                }
            }, 400);
        }, 350);
    }

    /* ── API ──────────────────────────────────────────── */

    function open(opts) {
        opts = opts || {};

        if (opts.tableA) state.tableA = opts.tableA;
        if (opts.tableB) state.tableB = opts.tableB;

        if (root) root.remove();
        root = build();

        fillTables();

        // guarda o SQL na própria root para o sendToSandbox poder usar
        root.lastSql = '';

        render();

        document.addEventListener('keydown', onKey);
    }

    function onKey(e) {
        if (e.key === 'Escape') close();
    }

    function close() {
        if (root) {
            // guarda o sql antes de fechar (para o sendToSandbox)
            if (root._sql) root.lastSql = root._sql;
            root.remove();
            root = null;
        }
        document.removeEventListener('keydown', onKey);
    }

    return { open: open, close: close };
})(window, document);
