/* SqlWizard Lite — Dark mode */
window.SqlWizardDarkMode = (function (window, document) {
    'use strict';

    var STORAGE_KEY = 'sqlwizard-lite-theme';
    var DEFAULT = 'light';

    function current() {
        try { return localStorage.getItem(STORAGE_KEY) || DEFAULT; } catch (e) { return DEFAULT; }
    }

    function apply(theme) {
        if (theme !== 'dark' && theme !== 'light') theme = DEFAULT;
        document.body.setAttribute('data-theme', theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
        updateButton(theme);
    }

    function toggle() {
        var next = current() === 'dark' ? 'light' : 'dark';
        apply(next);
        if (window.SqlWizardToast) {
            SqlWizardToast.success(next === 'dark' ? '🌙 Modo escuro' : '☀️ Modo claro', { duration: 1500 });
        }
    }

    function updateButton(theme) {
        var btn = document.getElementById('btn-dark-mode');
        if (!btn) return;
        btn.textContent = theme === 'dark' ? '☀️' : '🌙';
        btn.title = theme === 'dark' ? 'Mudar para modo claro' : 'Mudar para modo escuro';
    }

    function boot() {
        // Garantir botão
        if (!document.getElementById('btn-dark-mode')) {
            var btn = document.createElement('button');
            btn.id = 'btn-dark-mode';
            btn.type = 'button';
            btn.className = 'tb-btn';
            btn.textContent = '🌙';
            btn.title = 'Mudar para modo escuro';
            btn.addEventListener('click', toggle);

            var toolbar = document.querySelector('.toolbar-center') || document.querySelector('.toolbar');
            if (toolbar) toolbar.appendChild(btn);
        }

        // Aplicar tema guardado
        apply(current());
    }

    return {
        toggle: toggle,
        apply: apply,
        current: current,
        boot: boot
    };
})(window, document);
