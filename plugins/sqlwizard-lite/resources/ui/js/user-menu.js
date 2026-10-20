/* SqlWizard Lite — User menu (account, profile, logout) */
window.SqlWizardUserMenu = (function (window, document) {
    'use strict';

    var dropdown = null;
    var backdrop = null;
    var userCache = null;

    /* ─── API interna ─── */

    function getUser() {
        if (userCache) return userCache;
        // Fonte principal: PHP injeta em window.__SQLWIZARD_USER__
        if (window.__SQLWIZARD_USER__) {
            var u = window.__SQLWIZARD_USER__;
            // Normalizar: garantir account_type
            if (!u.account_type) {
                u.account_type = u.is_trial ? 'trial' : (u.plan || 'trial');
            }
            // Normalizar: garantir account_label
            if (!u.account_label) {
                u.account_label = labelFromPlan(u);
            }
            userCache = u;
            return userCache;
        }
        if (window.USER) {
            userCache = window.USER;
            return userCache;
        }
        // Fallback: ler data-attrs do body
        var b = document.body;
        if (b && b.dataset.userName) {
            userCache = {
                name: b.dataset.userName,
                email: b.dataset.userEmail || '',
                account_type: b.dataset.accountType || 'trial',
                account_label: b.dataset.accountLabel || 'Trial',
                avatar_url: b.dataset.avatarUrl || ''
            };
            return userCache;
        }
        return null;
    }

    function initials(name) {
        if (!name) return '?';
        var parts = String(name).trim().split(/\s+/);
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }


    /* ─── Helpers de plano ─── */

    function labelFromPlan(u) {
        var plan = (u.plan || 'trial').toLowerCase();
        if (plan === 'pro' || plan === 'premium' || plan === 'paid') return 'Pro';
        if (plan === 'free') return 'Free';
        if (plan === 'trial') {
            var d = daysLeft(u.plan_expires_at);
            if (d === null) return 'Trial';
            if (d <= 0) return 'Trial · expirado';
            return 'Trial · ' + d + 'd';
        }
        return plan.charAt(0).toUpperCase() + plan.slice(1);
    }

    function daysLeft(expiresAt) {
        if (!expiresAt) return null;
        var t = new Date(expiresAt).getTime();
        if (isNaN(t)) return null;
        return Math.ceil((t - Date.now()) / 86400000);
    }

    function badgeClassFromPlan(u) {
        var plan = (u.plan || 'trial').toLowerCase();
        if (plan === 'pro' || plan === 'premium' || plan === 'paid') return 'um-badge-pro';
        var d = daysLeft(u.plan_expires_at);
        if (d !== null && d <= 0) return 'um-badge-expired';
        return 'um-badge-trial';
    }

    function shouldUpgrade(u) {
        var plan = (u.plan || 'trial').toLowerCase();
        if (plan === 'pro' || plan === 'premium' || plan === 'paid') return false;
        var d = daysLeft(u.plan_expires_at);
        // Trial expirado AINDA mostra upgrade (para renovar)
        return true;
    }

    /* ─── Construir botão ─── */

    function buildButton() {
        var btn = document.createElement('button');
        btn.id = 'btn-user-menu';
        btn.type = 'button';
        btn.className = 'tb-btn tb-user-btn';
        btn.title = 'Conta';
        btn.setAttribute('aria-haspopup', 'true');
        btn.setAttribute('aria-expanded', 'false');

        var user = getUser();
        if (user && user.name) {
            btn.innerHTML =
                '<span class="tb-user-avatar">' + escapeHtml(initials(user.name)) + '</span>' +
                '<span class="tb-user-name">' + escapeHtml(user.name.split(' ')[0]) + '</span>' +
                '<span class="tb-user-caret">▾</span>';
        } else {
            btn.innerHTML =
                '<span class="tb-user-avatar">👤</span>' +
                '<span class="tb-user-caret">▾</span>';
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            open();
        });

        return btn;
    }

    /* ─── Abrir / fechar dropdown ─── */

    function open() {
        close(true);
        var btn = document.getElementById('btn-user-menu');
        if (!btn) return;

        var user = getUser() || {};
        var accountLabel = labelFromPlan(user);
        var accountBadgeClass = badgeClassFromPlan(user);
        var isTrial = shouldUpgrade(user);

        // Backdrop invisível (fecha ao clicar fora)
        backdrop = document.createElement('div');
        backdrop.className = 'um-backdrop';
        backdrop.addEventListener('click', function () { close(); });
        document.body.appendChild(backdrop);

        dropdown = document.createElement('div');
        dropdown.className = 'um-dropdown';
        dropdown.setAttribute('role', 'menu');

        var rect = btn.getBoundingClientRect();
        dropdown.style.top = (rect.bottom + 8) + 'px';
        dropdown.style.right = (window.innerWidth - rect.right) + 'px';

        dropdown.innerHTML =
            '<div class="um-header">' +
            '  <div class="um-avatar-lg">' + escapeHtml(initials(user.name || 'Guest')) + '</div>' +
            '  <div class="um-header-info">' +
            '    <div class="um-name">' + escapeHtml(user.name || 'Visitante') + '</div>' +
            '    <div class="um-email">' + escapeHtml(user.email || 'sem sessão') + '</div>' +
            '  </div>' +
            '</div>' +

            '<div class="um-account">' +
            '  <div class="um-account-row">' +
            '    <span class="um-label">Tipo de conta</span>' +
            '    <span class="um-badge ' + accountBadgeClass + '">' + escapeHtml(accountLabel) + '</span>' +
            '  </div>' +
            (isTrial
                ? '<button class="um-upgrade" type="button" data-action="upgrade">' +
                  '  ⚡ Fazer upgrade para Pro' +
                  '</button>'
                : (accountLabel.indexOf('expirado') !== -1
                    ? '<button class="um-upgrade" type="button" data-action="upgrade">' +
                      '  🔄 Renovar plano' +
                      '</button>'
                    : '<div class="um-account-note">Conta ativa · obrigado!</div>')
            ) +
            '</div>' +

            '<div class="um-divider"></div>' +

            '<div class="um-section">' +
            '  <button class="um-item" type="button" data-action="profile" role="menuitem">' +
            '    <span class="um-item-icon">👤</span>' +
            '    <span class="um-item-text">Editar perfil</span>' +
            '    <span class="um-item-kbd">→</span>' +
            '  </button>' +
            '  <button class="um-item" type="button" data-action="theme" role="menuitem">' +
            '    <span class="um-item-icon" id="um-theme-icon">🌙</span>' +
            '    <span class="um-item-text">Alternar tema</span>' +
            '  </button>' +
            '  <button class="um-item" type="button" data-action="templates" role="menuitem">' +
            '    <span class="um-item-icon">📋</span>' +
            '    <span class="um-item-text">Os meus templates</span>' +
            '  </button>' +
            '</div>' +

            '<div class="um-divider"></div>' +

            '<div class="um-section">' +
            '  <button class="um-item um-item-danger" type="button" data-action="logout" role="menuitem">' +
            '    <span class="um-item-icon">🚪</span>' +
            '    <span class="um-item-text">Terminar sessão</span>' +
            '  </button>' +
            '</div>' +

            '<div class="um-footer">' +
            '  SqlWizard Lite · v1.0' +
            '</div>';

        document.body.appendChild(dropdown);

        // Atualizar ícone do tema
        var themeIcon = dropdown.querySelector('#um-theme-icon');
        if (themeIcon && window.SqlWizardDarkMode) {
            themeIcon.textContent = window.SqlWizardDarkMode.current() === 'dark' ? '☀️' : '🌙';
        }

        // Listeners
        dropdown.querySelectorAll('.um-item, .um-upgrade').forEach(function (el) {
            el.addEventListener('click', function () {
                var action = el.getAttribute('data-action');
                handleAction(action);
            });
        });

        btn.setAttribute('aria-expanded', 'true');

        // Fechar com ESC
        document.addEventListener('keydown', onKeyDown);

        // Animar entrada
        requestAnimationFrame(function () { dropdown.classList.add('um-open'); });
    }

    function onKeyDown(e) {
        if (e.key === 'Escape') close();
    }

    function close(silent) {
        if (backdrop) { backdrop.remove(); backdrop = null; }
        if (dropdown) { dropdown.remove(); dropdown = null; }
        var btn = document.getElementById('btn-user-menu');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', onKeyDown);
    }

    /* ─── Ações ─── */

    function handleAction(action) {
        close();
        switch (action) {
            case 'profile':
                goToProfile();
                break;
            case 'upgrade':
                goToUpgrade();
                break;
            case 'logout':
                doLogout();
                break;
            case 'theme':
                if (window.SqlWizardDarkMode) window.SqlWizardDarkMode.toggle();
                break;
            case 'templates':
                // Abre o modal de templates (já existe)
                var btnTpl = document.getElementById('btn-templates');
                if (btnTpl) btnTpl.click();
                break;
        }
    }

    function goToProfile() {
        // Tentar rotas comuns; o backend do Beaver decide qual existe.
        var routes = [
            '/perfil',
            '/profile',
            '/account',
            '/settings/profile',
            '/utilizador/perfil'
        ];
        // Se a app expõe uma rota, usa-a
        if (window.__SQLWIZARD_PROFILE_URL__) {
            window.location.href = window.__SQLWIZARD_PROFILE_URL__;
            return;
        }
        // Tenta a primeira rota conhecida
        window.location.href = routes[0];
    }

    function goToUpgrade() {
        if (window.__SQLWIZARD_UPGRADE_URL__) {
            window.location.href = window.__SQLWIZARD_UPGRADE_URL__;
            return;
        }
        window.location.href = '/upgrade';
    }

    function doLogout() {
        if (!confirm('Terminar sessão?')) return;

        // Form POST para /logout (CSRF se existir)
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.__SQLWIZARD_LOGOUT_URL__ || '/logout';
        form.style.display = 'none';

        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }

    /* ─── Boot ─── */

    function boot() {
        if (document.getElementById('btn-user-menu')) return;
        var toolbar = document.querySelector('.toolbar-center') || document.querySelector('.toolbar');
        if (!toolbar) return;

        var btn = buildButton();

        // Inserir no fim (depois do dark mode)
        toolbar.appendChild(btn);

        // Recalcular posição ao redimensionar
        window.addEventListener('resize', function () { if (dropdown) close(); });
    }

    return {
        boot: boot,
        open: open,
        close: close,
        getUser: getUser
    };
})(window, document);
