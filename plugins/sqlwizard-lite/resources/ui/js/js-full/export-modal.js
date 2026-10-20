/* SqlWizard · Modal de exportação */
window.SqlWizardExportModal = (function (window, document) {
    'use strict';

    var root = null;
    var schema = null;
    var lastName = 'schema';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function build() {
        var el = document.createElement('div');
        el.className = 'ex-backdrop';
        el.innerHTML =
            '<div class="ex-modal">' +
            '  <div class="ex-header">' +
            '    <span class="ex-icon">⬇️</span>' +
            '    <div class="ex-header-text">' +
            '      <div class="ex-title">Exportar schema</div>' +
            '      <div class="ex-subtitle" id="ex-subtitle">a carregar…</div>' +
            '    </div>' +
            '    <button class="ex-close" id="ex-close">✕</button>' +
            '  </div>' +

            '  <div class="ex-body">' +
            '    <div class="ex-fields">' +

            '      <label class="ex-field">' +
            '        <span class="ex-label">Formato</span>' +
            '        <select id="ex-format">' +
            '          <option value="sql">SQL (CREATE TABLE…)</option>' +
            '          <option value="mermaid">Mermaid (ER diagram)</option>' +
            '          <option value="markdown">Markdown (documentação)</option>' +
            '          <option value="json">JSON (canónico)</option>' +
            '        </select>' +
            '      </label>' +

            '      <label class="ex-field" id="ex-driver-wrap">' +
            '        <span class="ex-label">Driver SQL</span>' +
            '        <select id="ex-driver">' +
            '          <option value="mysql">MySQL / MariaDB</option>' +
            '          <option value="pgsql">PostgreSQL</option>' +
            '          <option value="sqlite">SQLite</option>' +
            '        </select>' +
            '      </label>' +

            '      <label class="ex-field" id="ex-order-wrap">' +
            '        <span class="ex-label">Ordem das tabelas</span>' +
            '        <select id="ex-order">' +
            '          <option value="original">Original (do canvas)</option>' +
            '          <option value="topological">Topológica (deps primeiro)</option>' +
            '          <option value="alphabetical">Alfabética</option>' +
            '        </select>' +
            '      </label>' +

            '      <div class="ex-field ex-checks" id="ex-checks-wrap">' +
            '        <label class="ex-check"><input type="checkbox" id="ex-no-fk">   Sem foreign keys</label>' +
            '        <label class="ex-check"><input type="checkbox" id="ex-no-drop"> Sem DROP TABLE</label>' +
            '      </div>' +
            '    </div>' +

            '    <div class="ex-preview-label">Pré-visualização</div>' +
            '    <pre class="ex-preview" id="ex-preview">a gerar…</pre>' +
            '  </div>' +

            '  <div class="ex-footer">' +
            '    <span class="ex-info" id="ex-info"></span>' +
            '    <div class="ex-actions">' +
            '      <button class="ex-btn ex-btn-ghost" id="ex-copy">📋 Copiar</button>' +
            '      <button class="ex-btn ex-btn-ghost" id="ex-cancel">Cancelar</button>' +
            '      <button class="ex-btn ex-btn-primary" id="ex-download">⬇ Descarregar</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#ex-close').onclick    = close;
        el.querySelector('#ex-cancel').onclick   = close;
        el.querySelector('#ex-copy').onclick     = copyContent;
        el.querySelector('#ex-download').onclick = download;

        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        // ao mudar o formato, mostrar/esconder opções SQL
        el.querySelector('#ex-format').addEventListener('change', function () {
            var sqlMode = this.value === 'sql';
            el.querySelector('#ex-driver-wrap').hidden = !sqlMode;
            el.querySelector('#ex-order-wrap').hidden  = !sqlMode;
            el.querySelector('#ex-checks-wrap').hidden = !sqlMode;
            regenerate();
        });

        ['#ex-driver', '#ex-order', '#ex-no-fk', '#ex-no-drop'].forEach(function (sel) {
            var el2 = el.querySelector(sel);
            if (el2) {
                el2.addEventListener('change', regenerate);
            }
        });

        return el;
    }

    function getOptions() {
        return {
            format:  root.querySelector('#ex-format').value,
            driver:  root.querySelector('#ex-driver').value,
            order:   root.querySelector('#ex-order').value,
            no_fk:   root.querySelector('#ex-no-fk').checked,
            no_drop: root.querySelector('#ex-no-drop').checked
        };
    }

    function regenerate() {
        if (!schema) return;

        var preview = root.querySelector('#ex-preview');
        var info    = root.querySelector('#ex-info');

        preview.textContent = 'a gerar…';
        info.textContent = '';

        fetch('/api/sqlwizard/schema/format', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(Object.assign({ schema: schema }, getOptions()))
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) throw new Error(res.error || 'Falha ao gerar');
            preview.textContent = res.content;
            info.textContent = res.bytes + ' bytes';
            root._content = res.content;
            root._format  = res.format;
        })
        .catch(function (err) {
            preview.textContent = '✗ ' + err.message;
            info.textContent = '';
            root._content = null;
        });
    }

    function copyContent() {
        if (!root._content) return;
        navigator.clipboard.writeText(root._content).then(function () {
            if (window.SqlWizardToast) SqlWizardToast.success('Copiado', { duration: 1500 });
        });
    }

    function download() {
        if (!root._content) return;

        var opts = getOptions();
        var extMap = { sql: 'sql', mermaid: 'mmd', markdown: 'md', json: 'json' };
        var ext = extMap[opts.format] || 'txt';

        var name = lastName + '.' + ext;
        if (opts.format === 'sql' && opts.driver !== 'mysql') {
            name = lastName + '-' + opts.driver + '.sql';
        }

        var mime = match(opts.format, {
            sql: 'text/sql',
            mermaid: 'text/plain',
            markdown: 'text/markdown',
            json: 'application/json',
            default: 'text/plain'
        });

        var blob = new Blob([root._content], { type: mime });
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

        if (window.SqlWizardToast) {
            SqlWizardToast.success('Descarregado: ' + name, { duration: 2500 });
        }
    }

    function match(key, table) {
        return table[key] || table.default;
    }

    /* ── API ── */

    function open(initialFormat) {
        if (root) root.remove();
        root = build();

        // guardar nome base
        lastName = 'sqlwizard-schema-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');

        if (initialFormat && root.querySelector('#ex-format').value !== initialFormat) {
            root.querySelector('#ex-format').value = initialFormat;
            root.querySelector('#ex-format').dispatchEvent(new Event('change'));
        }

        // obter schema do canvas
        var canvas = window.SqlWizardCanvas;
        var cards = (canvas && canvas.cards) || [];

        if (!cards.length) {
            root.querySelector('#ex-subtitle').textContent = 'Canvas vazio';
            root.querySelector('#ex-preview').textContent =
                '// Não há tabelas no canvas.\n' +
                '// Abre tabelas no canvas (clica nos drafts) antes de exportar.';
            root.querySelector('#ex-download').disabled = true;
            root.querySelector('#ex-copy').disabled = true;
            return;
        }

        var positions = {};
        try { positions = JSON.parse(localStorage.getItem('sqlwizard:positions') || '{}'); } catch (e) {}
        var edgeTypes = canvas.edgeTypes || {};

        root.querySelector('#ex-subtitle').textContent = 'a obter schema…';

        fetch('/api/sqlwizard/schema/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                draft_ids: cards,
                positions: positions,
                edge_types: edgeTypes
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok || !res.schema) throw new Error(res.error || 'Falha no export');

            schema = res.schema;

            root.querySelector('#ex-subtitle').textContent =
                cards.length + ' tabelas · ' +
                (schema.relations || []).length + ' relações';

            regenerate();
        })
        .catch(function (err) {
            root.querySelector('#ex-preview').textContent = '✗ ' + err.message;
        });
    }

    function close() {
        if (root) { root.remove(); root = null; }
        schema = null;
    }

    return { open: open, close: close };
})(window, document);
