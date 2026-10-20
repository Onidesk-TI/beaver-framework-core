/* SqlWizard Lite — Modal de exportação */
window.SqlWizardExportModal = (function (window, document) {
    'use strict';
    var root = null;
    var lastName = 'schema';

    function build() {
        var el = document.createElement('div');
        el.className = 'ex-backdrop';
        el.innerHTML =
            '<div class="ex-modal">' +
            '  <div class="ex-header">' +
            '    <span class="ex-icon">Export</span>' +
            '    <div class="ex-header-text">' +
            '      <div class="ex-title">Exportar schema</div>' +
            '      <div class="ex-subtitle" id="ex-subtitle">a carregar...</div>' +
            '    </div>' +
            '    <button class="ex-close" id="ex-close">x</button>' +
            '  </div>' +
            '  <div class="ex-body">' +
            '    <div class="ex-fields">' +
            '      <label class="ex-field">' +
            '        <span class="ex-label">Formato</span>' +
            '        <select id="ex-format">' +
            '          <option value="sql">SQL (CREATE TABLE...)</option>' +
            '          <option value="mermaid">Mermaid (ER diagram)</option>' +
            '          <option value="markdown">Markdown (documentacao)</option>' +
            '          <option value="json">JSON (canonico)</option><option value="png">PNG (imagem do canvas)</option>' +
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
            '        <span class="ex-label">Ordem</span>' +
            '        <select id="ex-order">' +
            '          <option value="original">Original</option>' +
            '          <option value="topological" selected>Topologica (deps primeiro)</option>' +
            '          <option value="alphabetical">Alfabetica</option>' +
            '        </select>' +
            '      </label>' +
            '      <div class="ex-field ex-checks" id="ex-checks-wrap">' +
            '        <label class="ex-check"><input type="checkbox" id="ex-no-fk"> Sem foreign keys</label>' +
            '        <label class="ex-check"><input type="checkbox" id="ex-no-drop"> Sem DROP TABLE</label>' +
            '      </div>' +
            '    </div>' +
            '    <div class="ex-preview-label">Pre-visualizacao</div>' +
            '    <pre class="ex-preview" id="ex-preview">a gerar...</pre>' +
            '  </div>' +
            '  <div class="ex-footer">' +
            '    <span class="ex-info" id="ex-info"></span>' +
            '    <div class="ex-actions">' +
            '      <button class="ex-btn ex-btn-ghost" id="ex-copy">Copiar</button>' +
            '      <button class="ex-btn ex-btn-ghost" id="ex-cancel">Cancelar</button>' +
            '      <button class="ex-btn ex-btn-primary" id="ex-download">Descarregar</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(el);

        el.querySelector('#ex-close').onclick = close;
        el.querySelector('#ex-cancel').onclick = close;
        el.querySelector('#ex-copy').onclick = copyContent;
        el.querySelector('#ex-download').onclick = download;
        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        el.querySelector('#ex-format').addEventListener('change', function () {
            var sqlMode = this.value === 'sql';
            el.querySelector('#ex-driver-wrap').hidden = !sqlMode;
            el.querySelector('#ex-order-wrap').hidden = !sqlMode;
            el.querySelector('#ex-checks-wrap').hidden = !sqlMode;
            if (this.value === 'png') {
                el.querySelector('#ex-download').textContent = 'Descarregar PNG';
            } else {
                el.querySelector('#ex-download').textContent = 'Descarregar';
            }
            regenerate();
        });

        ['#ex-driver', '#ex-order', '#ex-no-fk', '#ex-no-drop'].forEach(function (sel) {
            var el2 = el.querySelector(sel);
            if (el2) el2.addEventListener('change', regenerate);
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
        if (!root) return;
        var preview = root.querySelector('#ex-preview');
        var info = root.querySelector('#ex-info');

        // PNG — não mostra preview, tem botão próprio
        if (root.querySelector('#ex-format').value === 'png') {
            preview.textContent = 'PNG do canvas — clica em "Descarregar" para gerar a imagem.';
            info.textContent = '';
            root._content = null;
            root._format = 'png';
            return;
        }

        try {
            var res = window.SqlWizardPreview.generate(getOptions());
            preview.textContent = res.content;
            info.textContent = res.bytes + ' bytes';
            root._content = res.content;
        } catch (e) {
            preview.textContent = 'Erro: ' + e.message;
            info.textContent = '';
            root._content = null;
        }
    }

    function copyContent() {
        if (!root || !root._content) return;
        navigator.clipboard.writeText(root._content).then(function () {
            if (window.SqlWizardToast) SqlWizardToast.success('Copiado', { duration: 1500 });
            else if (window.showToast) window.showToast('Copiado', 'ok');
        });
    }

    function download() {
        if (!root) return;

        // PNG — usar exportPNG dedicado
        if (root.querySelector('#ex-format').value === 'png') {
            if (window.SqlWizardExportPNG) {
                window.SqlWizardExportPNG.exportPNG();
            } else {
                if (window.SqlWizardToast) SqlWizardToast.error('Export PNG indisponivel');
            }
            return;
        }

        if (!root._content) return;
        var opts = getOptions();
        var extMap = { sql: 'sql', mermaid: 'mmd', markdown: 'md', json: 'json' };
        var ext = extMap[opts.format] || 'txt';
        var name = lastName + '.' + ext;
        if (opts.format === 'sql' && opts.driver !== 'mysql') name = lastName + '-' + opts.driver + '.sql';
        var mime = { sql: 'text/sql', mermaid: 'text/plain', markdown: 'text/markdown', json: 'application/json' }[opts.format] || 'text/plain';
        var blob = new Blob([root._content], { type: mime });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = name;
        document.body.appendChild(a); a.click();
        setTimeout(function () { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
        if (window.SqlWizardToast) SqlWizardToast.success('Descarregado: ' + name, { duration: 2500 });
        else if (window.showToast) window.showToast('Descarregado: ' + name, 'ok');
    }

    function open(initialFormat) {
        if (root) root.remove();
        root = build();
        lastName = 'sqlwizard-schema-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
        if (initialFormat && root.querySelector('#ex-format').value !== initialFormat) {
            root.querySelector('#ex-format').value = initialFormat;
            root.querySelector('#ex-format').dispatchEvent(new Event('change'));
        }
        var state = window.SqlWizardPreview.getState();
        var tables = state.tables || [];
        if (!tables.length) {
            root.querySelector('#ex-subtitle').textContent = 'Canvas vazio';
            root.querySelector('#ex-preview').textContent = '// Nao ha tabelas no canvas.';
            root.querySelector('#ex-download').disabled = true;
            root.querySelector('#ex-copy').disabled = true;
            return;
        }
        root.querySelector('#ex-subtitle').textContent = tables.length + ' tabelas · ' + (state.relations || []).length + ' relacoes';
        regenerate();
    }

    function close() { if (root) { root.remove(); root = null; } }

    return { open: open, close: close };
})(window, document);
