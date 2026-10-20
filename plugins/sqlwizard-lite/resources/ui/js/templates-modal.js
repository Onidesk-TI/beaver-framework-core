/* SqlWizard Lite — Templates Modal */
window.SqlWizardTemplates = (function (window, document) {
    'use strict';

    var TEMPLATES = [
        { slug: 'blog',      icon: '📝', name: 'Blog',       desc: '5 tabelas · posts, comments, tags' },
        { slug: 'ecommerce', icon: '🛒', name: 'E-commerce', desc: '5 tabelas · products, orders, items' },
        { slug: 'crm',       icon: '👥', name: 'CRM',        desc: '5 tabelas · clients, deals, activities' },
        { slug: 'lms',       icon: '📚', name: 'LMS',        desc: '5 tabelas · courses, lessons, enrollments' },
        { slug: 'helpdesk',  icon: '🎫', name: 'HelpDesk',   desc: '5 tabelas · tickets, messages, categories' },
        { slug: 'booking',   icon: '📅', name: 'Booking',    desc: '4 tabelas · resources, bookings, payments' }
    ];

    var root = null;

    function build() {
        var el = document.createElement('div');
        el.className = 'tpl-backdrop';
        el.innerHTML =
            '<div class="tpl-modal">' +
            '  <div class="tpl-header">' +
            '    <span class="tpl-icon">🎨</span>' +
            '    <div class="tpl-header-text">' +
            '      <div class="tpl-title">Começar de um template</div>' +
            '      <div class="tpl-subtitle">Escolhe um modelo e começa com tabelas prontas</div>' +
            '    </div>' +
            '    <button class="tpl-close" id="tpl-close">x</button>' +
            '  </div>' +
            '  <div class="tpl-grid">' +
            TEMPLATES.map(function (t) {
                return '<button class="tpl-card" data-slug="' + t.slug + '" type="button">' +
                    '  <span class="tpl-card-icon">' + t.icon + '</span>' +
                    '  <span class="tpl-card-info">' +
                    '    <strong>' + t.name + '</strong>' +
                    '    <small>' + t.desc + '</small>' +
                    '  </span>' +
                    '</button>';
            }).join('') +
            '  </div>' +
            '  <div class="tpl-custom-section" id="tpl-custom-section" style="display:none;">' +
'    <div class="tpl-custom-title">📁 Meus templates</div>' +
'    <div class="tpl-custom-list" id="tpl-custom-list"></div>' +
'  </div>' +
'  <div class="tpl-footer">' +
            '    <button class="tpl-btn-ghost" id="tpl-cancel">Cancelar</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(el);

        el.querySelector('#tpl-close').onclick = close;
        el.querySelector('#tpl-cancel').onclick = close;
        el.addEventListener('click', function (e) { if (e.target === el) close(); });

        renderCustomTemplates(el);

        el.querySelectorAll('.tpl-card').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var slug = btn.getAttribute('data-slug');
                loadTemplate(slug);
            });
        });
        return el;
    }

    function renderCustomTemplates(el) {
        if (!window.SqlWizardCustomTemplates) return;
        var list = el.querySelector('#tpl-custom-list');
        var section = el.querySelector('#tpl-custom-section');
        if (!list || !section) return;

        var customs = window.SqlWizardCustomTemplates.loadAll();
        if (!customs.length) {
            section.style.display = 'none';
            return;
        }

        section.style.display = '';
        list.innerHTML = customs.map(function (t) {
            return '<div class="tpl-custom-item">' +
                '  <button class="tpl-custom-load" data-id="' + t.id + '" type="button">' +
                '    <span class="tpl-custom-icon">💾</span>' +
                '    <span class="tpl-custom-info">' +
                '      <strong>' + escape(t.name) + '</strong>' +
                '      <small>' + (t.tables ? t.tables.length : 0) + ' tabelas' +
                (t.description ? ' · ' + escape(t.description) : '') + '</small>' +
                '    </span>' +
                '  </button>' +
                '  <button class="tpl-custom-delete" data-del="' + t.id + '" type="button" title="Apagar">🗑</button>' +
                '</div>';
        }).join('');

        list.querySelectorAll('.tpl-custom-load').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                if (window.SqlWizardCustomTemplates.loadTemplate(id)) {
                    close();
                    if (window.SqlWizardToast) SqlWizardToast.success('Template carregado!', { duration: 2000 });
                }
            });
        });

        list.querySelectorAll('.tpl-custom-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.getAttribute('data-del');
                if (!confirm('Apagar este template?')) return;
                window.SqlWizardCustomTemplates.deleteTemplate(id);
                renderCustomTemplates(el);
            });
        });
    }

    function escape(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function loadTemplate(slug) {
        fetch('/plugins/sqlwizard-lite/templates/' + slug + '.json')
            .then(function (r) {
                if (!r.ok) throw new Error('Template não encontrado: ' + slug);
                return r.json();
            })
            .then(function (data) {
                if (!window.SqlWizardCanvas || !window.SqlWizardCanvas.setState) {
                    throw new Error('SqlWizardCanvas não disponível');
                }
                window.SqlWizardCanvas.setState({
                    tables: data.tables || [],
                    relations: data.relations || []
                });
                close();
                if (window.SqlWizardToast) SqlWizardToast.success('Template "' + slug + '" carregado!', { duration: 2500 });
                else if (window.showToast) window.showToast('Template "' + slug + '" carregado!', 'ok');
            })
            .catch(function (err) {
                console.error('[templates]', err);
                if (window.SqlWizardToast) SqlWizardToast.error(err.message, { title: 'Erro' });
                else if (window.showToast) window.showToast(err.message, 'err');
            });
    }

    function open() {
        if (root) root.remove();
        root = build();
    }

    function close() {
        if (root) { root.remove(); root = null; }
    }

    return { open: open, close: close };
})(window, document);
