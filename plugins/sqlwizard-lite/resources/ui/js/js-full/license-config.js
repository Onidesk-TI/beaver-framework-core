/* SqlWizard · Configuração de Licença */
window.SqlWizardLicenseConfig = (function (window, document) {
    'use strict';

    var root = null;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function build() {
        var el = document.createElement('div');
        el.className = 'lc-backdrop';
        el.innerHTML =
            '<div class="lc-modal">' +
            '  <div class="lc-header">' +
            '    <span class="lc-icon">🔑</span>' +
            '    <div class="lc-header-text">' +
            '      <div class="lc-title">Configuração de licença</div>' +
            '      <div class="lc-subtitle" id="lc-subtitle">a carregar…</div>' +
            '    </div>' +
            '    <button class="lc-close" id="lc-close">✕</button>' +
            '  </div>' +
            '  <div class="lc-body">' +
            '    <div class="lc-status" id="lc-status"></div>' +

            '    <label class="lc-field">' +
            '      <span class="lc-label">Servidor de licenças (URL)</span>' +
            '      <input type="url" id="lc-url" placeholder="https://licenses.onidesk.com/api/license.php">' +
            '      <span class="lc-hint">Endpoint completo que emite a licença. Deixa vazio para modo community puro.</span>' +
            '    </label>' +

            '    <label class="lc-field">' +
            '      <span class="lc-label">Edição</span>' +
            '      <select id="lc-edition">' +
            '        <option value="community">Community</option>' +
            '        <option value="pro">Pro</option>' +
            '      </select>' +
            '    </label>' +

            '    <div class="lc-field">' +
            '      <span class="lc-label">Chave pública Ed25519</span>' +
            '      <div class="lc-pk-tabs">' +
            '        <button type="button" class="lc-tab is-active" data-pk="base64">Colar base64</button>' +
            '        <button type="button" class="lc-tab" data-pk="path">Carregar ficheiro .key</button>' +
            '      </div>' +
            '      <div id="lc-pk-base64">' +
            '        <textarea id="lc-pk-text" placeholder="base64 da chave pública (44 chars)…"></textarea>' +
            '      </div>' +
            '      <div id="lc-pk-path" hidden>' +
            '        <input type="file" id="lc-pk-file" accept=".key,application/octet-stream">' +
            '        <div class="lc-file-info" id="lc-pk-file-info"></div>' +
            '      </div>' +
            '      <span class="lc-hint" id="lc-pk-hint">Deixa vazio para manter a chave atual.</span>' +
            '    </div>' +

            '    <div class="lc-info">' +
            '      <div><strong>Fingerprint desta máquina:</strong></div>' +
            '      <code id="lc-fp">—</code>' +
            '    </div>' +
            '  </div>' +
            '  <div class="lc-footer">' +
            '    <span class="lc-msg" id="lc-msg"></span>' +
            '    <div class="lc-actions">' +
            '      <button class="lc-btn lc-btn-ghost" id="lc-test">🧪 Testar ligação</button>' +
            '      <button class="lc-btn lc-btn-ghost" id="lc-cancel">Cancelar</button>' +
            '      <button class="lc-btn lc-btn-primary" id="lc-save">Gravar</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#lc-close').onclick  = close;
        el.querySelector('#lc-cancel').onclick = close;
        el.querySelector('#lc-save').onclick   = save;
        el.querySelector('#lc-test').onclick   = test;

        el.querySelectorAll('.lc-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                el.querySelectorAll('.lc-tab').forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');

                var mode = tab.getAttribute('data-pk');
                el.querySelector('#lc-pk-base64').hidden = (mode !== 'base64');
                el.querySelector('#lc-pk-path').hidden   = (mode !== 'path');
            });
        });

        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        // upload de .key — guarda base64 em memória
        var pkFileInput = el.querySelector('#lc-pk-file');
        if (pkFileInput) {
            pkFileInput.addEventListener('change', function () {
                var file = pkFileInput.files && pkFileInput.files[0];
                var info = el.querySelector('#lc-pk-file-info');
                if (!file) {
                    info.textContent = '';
                    el._pkBase64 = null;
                    return;
                }

                if (!/\.key$/i.test(file.name)) {
                    info.innerHTML = '<span class="lc-file-err">✗ Só são aceites ficheiros .key</span>';
                    pkFileInput.value = '';
                    el._pkBase64 = null;
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    var bytes = new Uint8Array(e.target.result);
                    if (bytes.length !== 32) {
                        info.innerHTML = '<span class="lc-file-err">✗ Chave inválida: ' +
                            bytes.length + ' bytes (esperados 32)</span>';
                        el._pkBase64 = null;
                        return;
                    }

                    // converte para base64
                    var bin = '';
                    for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
                    var b64 = btoa(bin);

                    el._pkBase64 = b64;

                    info.innerHTML = '<span class="lc-file-ok">✓ ' +
                        file.name + ' · ' + bytes.length + ' bytes · base64 pronto</span>';
                };
                reader.readAsArrayBuffer(file);
            });
        }

        return el;
    }

    function fill(cfg) {
        root.querySelector('#lc-url').value = cfg.server_url || '';
        root.querySelector('#lc-edition').value = cfg.edition || 'community';
        root.querySelector('#lc-fp').textContent = cfg.fingerprint || '—';

        var status = root.querySelector('#lc-status');
        var subtitle = root.querySelector('#lc-subtitle');

        if (cfg.has_public_key) {
            subtitle.textContent = 'Chave pública presente';
        } else {
            subtitle.textContent = 'Sem chave pública';
        }
    }

    function showStatus(s) {
        var box = root.querySelector('#lc-status');
        if (!s) { box.innerHTML = ''; return; }

        box.className = 'lc-status is-' + (s.kind || 'info');
        box.innerHTML = s.html || '';
    }

    function save() {
        var msg = root.querySelector('#lc-msg');
        msg.textContent = 'A gravar…';
        msg.className = 'lc-msg';

        var pkMode = root.querySelector('.lc-tab.is-active').getAttribute('data-pk');

        var body = {
            server_url: root.querySelector('#lc-url').value.trim(),
            edition:    root.querySelector('#lc-edition').value,
        };

        if (pkMode === 'base64') {
            body.public_key_base64 = root.querySelector('#lc-pk-text').value.trim();
        } else {
            // tab upload .key — usa o base64 guardado em memória
            if (root._pkBase64) {
                body.public_key_base64 = root._pkBase64;
            }
            // se não há ficheiro carregado, não envia chave nova (mantém a atual)
        }

        fetch('/api/sqlwizard/license/config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.error) throw new Error(res.error);

            msg.textContent = '✓ ' + (res.message || 'Gravado');
            msg.className = 'lc-msg is-ok';

            if (window.SqlWizardToast) {
                SqlWizardToast.success('Configuração de licença atualizada', { duration: 3000 });
            }

            setTimeout(close, 1200);
        })
        .catch(function (err) {
            msg.textContent = '✗ ' + err.message;
            msg.className = 'lc-msg is-error';
        });
    }

    function test() {
        var msg = root.querySelector('#lc-msg');
        msg.textContent = 'A testar…';
        msg.className = 'lc-msg';

        var body = {
            server_url: root.querySelector('#lc-url').value.trim(),
            edition:    root.querySelector('#lc-edition').value,
        };

        fetch('/api/sqlwizard/license/config/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.ok) {
                msg.textContent = '✓ Servidor respondeu em ' + res.ms + 'ms · edição: ' + res.edition;
                msg.className = 'lc-msg is-ok';

                showStatus({
                    kind: 'ok',
                    html:
                        '<strong>Ligacão válida</strong><br>' +
                        'Edição: <code>' + esc(res.edition) + '</code><br>' +
                        'Chave: <code>' + esc(res.key || '?') + '</code><br>' +
                        'Expira: ' + esc(res.exp || 'nunca') + '<br>' +
                        'Drivers: <code>' + esc((res.features.drivers || []).join(', ')) + '</code>'
                });
            } else {
                msg.textContent = '✗ ' + (res.error || 'Falha');
                msg.className = 'lc-msg is-error';

                if (res.hint) {
                    showStatus({ kind: 'warn', html: '<strong>Dica:</strong> ' + esc(res.hint) });
                }
            }
        })
        .catch(function (err) {
            msg.textContent = '✗ ' + err.message;
            msg.className = 'lc-msg is-error';
        });
    }

    function open() {
        if (root) root.remove();
        root = build();

        fetch('/api/sqlwizard/license/config', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (cfg) {
                fill(cfg);

                // estado atual da licença
                fetch('/api/sqlwizard/license', { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (st) {
                        if (st.active) {
                            showStatus({
                                kind: 'ok',
                                html:
                                    '<strong>Licença ativa</strong> · edição <code>' + esc(st.edition) + '</code><br>' +
                                    'Drivers: <code>' + esc((st.features.drivers || []).join(', ')) + '</code>'
                            });
                        } else {
                            showStatus({
                                kind: 'warn',
                                html:
                                    '<strong>Sem licença ativa</strong> · modo community' +
                                    (st.error ? '<br>' + esc(st.error) : '')
                            });
                        }
                    });
            })
            .catch(function (err) {
                showStatus({ kind: 'error', html: 'Erro ao carregar config: ' + esc(err.message) });
            });
    }

    function close() {
        if (root) { root.remove(); root = null; }
    }

    return { open: open, close: close };
})(window, document);
