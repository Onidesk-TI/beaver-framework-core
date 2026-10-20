/* SqlWizard Lite — Templates custom */
window.SqlWizardCustomTemplates = (function (window, document) {
    'use strict';

    var STORAGE_KEY = 'sqlwizard-lite-custom-templates';
    var modal = null;

    function loadAll() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return [];
            var arr = JSON.parse(raw);
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }

    function saveAll(arr) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); } catch (e) {}
    }

    function saveCurrent(name, description) {
        if (!window.SqlWizardCanvas || !window.SqlWizardCanvas.getState) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas indisponivel');
            return false;
        }
        var state = window.SqlWizardCanvas.getState();
        if (!state.tables || !state.tables.length) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas vazio');
            return false;
        }
        var all = loadAll();
        var template = {
            id: 'custom_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7),
            name: name || 'Template sem nome',
            description: description || '',
            icon: '💾',
            custom: true,
            created_at: new Date().toISOString(),
            tables: state.tables,
            relations: state.relations
        };
        all.push(template);
        saveAll(all);
        return template;
    }

    function deleteTemplate(id) {
        var all = loadAll();
        all = all.filter(function (t) { return t.id !== id; });
        saveAll(all);
    }

    function getTemplate(id) {
        var all = loadAll();
        return all.find(function (t) { return t.id === id; });
    }

    function loadTemplate(id) {
        var t = getTemplate(id);
        if (!t) return false;
        if (!window.SqlWizardCanvas || !window.SqlWizardCanvas.setState) return false;
        window.SqlWizardCanvas.setState({
            tables: JSON.parse(JSON.stringify(t.tables)),
            relations: JSON.parse(JSON.stringify(t.relations))
        });
        return true;
    }

    /* ─── Modal para guardar ─── */

    function openSaveModal() {
        if (modal) modal.remove();
        var state = window.SqlWizardCanvas && window.SqlWizardCanvas.getState ? window.SqlWizardCanvas.getState() : null;
        var tableCount = state ? (state.tables || []).length : 0;
        var relCount = state ? (state.relations || []).length : 0;

        if (!tableCount) {
            if (window.SqlWizardToast) SqlWizardToast.error('Canvas vazio — cria tabelas primeiro');
            return;
        }

        modal = document.createElement('div');
        modal.className = 'tpl-save-backdrop';
        modal.innerHTML =
            '<div class="tpl-save-modal">' +
            '  <div class="tpl-save-header">' +
            '    <span class="tpl-save-icon">💾</span>' +
            '    <div>' +
            '      <div class="tpl-save-title">Guardar como template</div>' +
            '      <div class="tpl-save-subtitle">' + tableCount + ' tabelas · ' + relCount + ' relacoes</div>' +
            '    </div>' +
            '    <button class="tpl-save-close" id="tpl-save-close">x</button>' +
            '  </div>' +
            '  <div class="tpl-save-body">' +
            '    <label class="tpl-save-label">Nome do template</label>' +
            '    <input type="text" class="tpl-save-input" id="tpl-save-name" placeholder="ex: Auth base, Ecommerce v2..." maxlength="60" autofocus>' +
            '    <label class="tpl-save-label">Descricao (opcional)</label>' +
            '    <input type="text" class="tpl-save-input" id="tpl-save-desc" placeholder="Para que serve este template?" maxlength="120">' +
            '  </div>' +
            '  <div class="tpl-save-footer">' +
            '    <button class="tpl-save-btn tpl-save-ghost" id="tpl-save-cancel">Cancelar</button>' +
            '    <button class="tpl-save-btn tpl-save-primary" id="tpl-save-confirm">Guardar</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(modal);

        var nameInput = modal.querySelector('#tpl-save-name');
        var descInput = modal.querySelector('#tpl-save-desc');
        var confirm = modal.querySelector('#tpl-save-confirm');
        var close = modal.querySelector('#tpl-save-close');
        var cancel = modal.querySelector('#tpl-save-cancel');

        setTimeout(function () { nameInput.focus(); }, 50);

        function doSave() {
            var name = nameInput.value.trim();
            if (!name) {
                nameInput.focus();
                nameInput.style.borderColor = '#D0483A';
                return;
            }
            var t = saveCurrent(name, descInput.value.trim());
            if (t) {
                if (window.SqlWizardToast) SqlWizardToast.success('Template "' + t.name + '" guardado!', { duration: 2500 });
                closeModal();
            }
        }

        confirm.addEventListener('click', doSave);
        nameInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') doSave(); });
        descInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') doSave(); });
        close.addEventListener('click', closeModal);
        cancel.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    }

    function closeModal() {
        if (modal) { modal.remove(); modal = null; }
    }

    return {
        loadAll: loadAll,
        saveCurrent: saveCurrent,
        deleteTemplate: deleteTemplate,
        loadTemplate: loadTemplate,
        getTemplate: getTemplate,
        openSaveModal: openSaveModal,
        closeModal: closeModal
    };
})(window, document);
