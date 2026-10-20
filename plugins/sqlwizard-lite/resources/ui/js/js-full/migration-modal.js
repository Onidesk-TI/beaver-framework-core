/* SqlWizard · Modal de geração de migrations Beaver a partir do canvas */
window.SqlWizardMigrationModal = (function (window, document) {
    'use strict';

    var root = null;
    var state = { php: '', filename: '', saving: false, saved: null, error: null };

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function build() {
        var el = document.createElement('div');
        el.className = 'mm-backdrop';
        el.innerHTML =
            '<div class="mm-modal">' +
            '  <div class="mm-header">' +
            '    <span class="mm-icon">' +
            '      <svg viewBox="0 0 64 64" width="26" height="26">' +
            '        <defs>' +
            '          <linearGradient id="mm-bvg" x1="0" y1="0" x2="1" y2="1">' +
            '            <stop offset="0%" stop-color="#FFD79A"/>' +
            '            <stop offset="48%" stop-color="#FF9A3C"/>' +
            '            <stop offset="100%" stop-color="#FF6B35"/>' +
            '          </linearGradient>' +
            '        </defs>' +
            '        <circle cx="13" cy="17.5" r="7.5" fill="url(#mm-bvg)" opacity=".72"/>' +
            '        <circle cx="51" cy="17.5" r="7.5" fill="url(#mm-bvg)" opacity=".72"/>' +
            '        <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#mm-bvg)"/>' +
            '        <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".2"/>' +
            '        <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208"/>' +
            '        <circle cx="22.5" cy="26" r="3.4" fill="#1A1208"/>' +
            '        <circle cx="41.5" cy="26" r="3.4" fill="#1A1208"/>' +
            '        <circle cx="23.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85"/>' +
            '        <circle cx="42.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85"/>' +
            '        <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>' +
            '        <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>' +
            '      </svg>' +
            '    </span>' +
            '    <div class="mm-header-text">' +
            '      <div class="mm-title">Gerar Migration</div>' +
            '      <div class="mm-subtitle">Código Beaver a partir das tabelas do canvas</div>' +
            '    </div>' +
            '    <button class="mm-close" id="mm-close">✕</button>' +
            '  </div>' +

            '  <div class="mm-body">' +
            '    <div class="mm-status" id="mm-status"></div>' +
            '    <div class="mm-file">' +
            '      <span class="mm-file-label">Ficheiro</span>' +
            '      <code id="mm-filename">—</code>' +
            '    </div>' +
            '    <pre class="mm-code" id="mm-code"></pre>' +
            '  </div>' +

            '  <div class="mm-footer">' +
            '    <span class="mm-info" id="mm-info"></span>' +
            '    <div class="mm-actions">' +
            '      <button class="mm-btn mm-btn-ghost" id="mm-copy">📋 Copiar</button>' +
            '      <button class="mm-btn mm-btn-ghost" id="mm-download">⬇ Descarregar</button>' +
            '      <button class="mm-btn mm-btn-primary" id="mm-save">💾 Guardar em migrations/</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#mm-close').onclick = close;
        el.addEventListener('click', function (e) { if (e.target === el) close(); });
        el.querySelector('#mm-copy').onclick = copyPhp;
        el.querySelector('#mm-download').onclick = downloadPhp;
        el.querySelector('#mm-save').onclick = savePhp;

        return el;
    }

    function render() {
        var code = root.querySelector('#mm-code');
        var file = root.querySelector('#mm-filename');
        var info = root.querySelector('#mm-info');
        var status = root.querySelector('#mm-status');

        file.textContent = state.filename || '—';
        code.textContent = state.php || '// a gerar…';

        if (state.error) {
            status.innerHTML = '<span class="mm-status-error">⚠ ' + esc(state.error) + '</span>';
        } else if (state.saved) {
            status.innerHTML = '<span class="mm-status-ok">✓ Guardado em <code>' + esc(state.saved) + '</code></span>';
        } else if (state.saving) {
            status.innerHTML = '<span class="mm-status-info">A guardar…</span>';
        } else {
            status.innerHTML = '';
        }

        var lines = state.php ? state.php.split('\n').length : 0;
        info.textContent = lines ? lines + ' linhas' : '';

        var saveBtn = root.querySelector('#mm-save');
        saveBtn.disabled = state.saving || !!state.saved;
        saveBtn.textContent = state.saved ? '✓ Guardado' : (state.saving ? 'A guardar…' : '💾 Guardar em migrations/');
    }

    function copyPhp() {
        if (!state.php) return;
        navigator.clipboard.writeText(state.php).then(function () {
            if (window.SqlWizardToast) SqlWizardToast.success('PHP copiado', { duration: 1500 });
        });
    }

    function downloadPhp() {
        if (!state.php) return;
        var blob = new Blob([state.php], { type: 'text/x-php' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = state.filename || 'migration.php';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 100);
    }

    function savePhp() {
        if (!state.php || state.saving || state.saved) return;

        state.saving = true;
        state.error = null;
        render();

        fetch('/api/sqlwizard/migrations/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ drafts: state.drafts, save: true })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            state.saving = false;
            if (res.error) {
                state.error = res.error;
            } else {
                state.saved    = res.saved;
                state.filename = res.filename;
                if (window.SqlWizardToast) SqlWizardToast.success('Migration guardada', { duration: 2500 });
            }
            render();
        })
        .catch(function (err) {
            state.saving = false;
            state.error = err.message || 'Erro de rede';
            render();
        });
    }

    function open(drafts) {
        state = { php: '', filename: '', saving: false, saved: null, error: null, drafts: drafts || [] };

        if (root) root.remove();
        root = build();
        render();

        document.addEventListener('keydown', onKey);

        fetch('/api/sqlwizard/migrations/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ drafts: drafts, save: false })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.error) {
                state.error = res.error;
            } else {
                state.php      = res.php      || '';
                state.filename = res.filename || 'migration.php';
            }
            render();
        })
        .catch(function (err) {
            state.error = err.message || 'Erro de rede';
            render();
        });
    }

    function onKey(e) {
        if (e.key === 'Escape') close();
    }

    function close() {
        if (root) { root.remove(); root = null; }
        document.removeEventListener('keydown', onKey);
    }

    return { open: open, close: close };
})(window, document);
