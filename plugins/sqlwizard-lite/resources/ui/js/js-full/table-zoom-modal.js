/* SqlWizard · Modal "tabela em grande escala" */
window.SqlWizardTableZoom = (function (window, document) {
    'use strict';

    var root = null;
    var currentDraft = null;
    var currentCard = null;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ── Parse (mesma lógica do canvas) ─────────────────── */

    function parseField(raw) {
        var info = {
            name: '', type: 'str', args: '', nullable: false,
            isPk: false, isUnique: false, default: null, ref: null
        };

        var s = String(raw).trim();

        if (s === 'id') {
            info.name = 'id';
            info.type = 'id';
            info.isPk = true;
            return info;
        }

        // ref.table
        var m = s.match(/:ref\.([a-z0-9_]+)/i);
        if (m) info.ref = m[1];

        if (s.indexOf('?') !== -1) { info.nullable = true; s = s.replace(/\?/g, ''); }

        if (s.indexOf('=') !== -1) {
            var eq = s.indexOf('=');
            info.default = s.substring(eq + 1);
            s = s.substring(0, eq);
        }

        var colon = s.indexOf(':');
        if (colon === -1) { info.name = s; return info; }

        info.name = s.substring(0, colon);
        var rest = s.substring(colon + 1);

        // tirar :ref.X
        rest = rest.replace(/:ref\.[a-z0-9_]+/i, '');

        var paren = rest.indexOf('(');
        if (paren === -1) {
            info.type = rest;
        } else {
            info.type = rest.substring(0, paren);
            info.args = rest.substring(paren + 1, rest.indexOf(')'));
        }

        if (info.name === 'email' || info.name.indexOf('unique') !== -1) {
            info.isUnique = true;
        }

        return info;
    }

    function detectFkTarget(draft, colName, allDrafts) {
        // padrão: fornecedor_id → fornecedores
        var m = colName.match(/^(.+)_id$/);
        if (!m) return null;

        var base = m[1];
        var candidates = [base, base + 's', base + 'es', base.replace(/y$/, 'ies')];

        for (var i = 0; i < candidates.length; i++) {
            for (var j = 0; j < allDrafts.length; j++) {
                if (allDrafts[j].table === candidates[i]) {
                    return { table: candidates[i], column: 'id' };
                }
            }
        }
        return null;
    }

    /* ── Layout da modal ────────────────────────────────── */

    function build() {
        var el = document.createElement('div');
        el.className = 'tz-backdrop';
        el.innerHTML =
            '<div class="tz-modal">' +
            '  <div class="tz-header">' +
            '    <span class="tz-icon" id="tz-icon">▦</span>' +
            '    <div class="tz-header-text">' +
            '      <div class="tz-title" id="tz-title">tabela</div>' +
            '      <div class="tz-subtitle" id="tz-subtitle">— colunas</div>' +
            '    </div>' +
            '    <button class="tz-close" id="tz-close" title="Fechar (Esc)">✕</button>' +
            '  </div>' +

            '  <div class="tz-body">' +
            '    <div class="tz-col-head">' +
            '      <span class="tz-col-name">Coluna</span>' +
            '      <span class="tz-col-type">Tipo</span>' +
            '      <span class="tz-col-flags">Notas</span>' +
            '    </div>' +
            '    <ul class="tz-cols" id="tz-cols"></ul>' +
            '  </div>' +

            '  <div class="tz-footer">' +
            '    <div class="tz-meta" id="tz-meta"></div>' +
            '    <div class="tz-actions">' +
            '      <button class="tz-btn tz-btn-ghost" data-act="edit">✏ Editar</button>' +
            '      <button class="tz-btn tz-btn-ghost" data-act="data">📊 Ver dados</button>' +
            '      <button class="tz-btn tz-btn-ghost" data-act="export">⬇ Exportar SQL</button>' +
            '      <button class="tz-btn tz-btn-danger" data-act="remove">🗑 Remover do canvas</button>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('#tz-close').onclick = close;
        el.addEventListener('click', function (e) {
            if (e.target === el) close();
        });

        el.querySelectorAll('[data-act]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                action(btn.getAttribute('data-act'));
            });
        });

        return el;
    }

    function render(draft) {
        root.querySelector('#tz-title').textContent = draft.table;
        root.querySelector('#tz-subtitle').textContent = (draft.fields || []).length + ' colunas';

        // ícone do card (se existir)
        var cardIcon = currentCard ? currentCard.querySelector('.card-icon') : null;
        if (cardIcon) root.querySelector('#tz-icon').textContent = cardIcon.textContent;

        var allDrafts = (window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) || [];
        var fields = (draft.fields || []).map(parseField);

        var html = '';
        fields.forEach(function (f, idx) {
            var flags = [];

            if (f.isPk) flags.push('<span class="tz-badge tz-badge-pk">PK</span>');
            if (f.isUnique) flags.push('<span class="tz-badge tz-badge-uniq">UNIQUE</span>');
            if (f.nullable) flags.push('<span class="tz-badge tz-badge-null">NULL</span>');
            if (!f.nullable && !f.isPk) flags.push('<span class="tz-badge tz-badge-notnull">NOT NULL</span>');
            if (f.default !== null) flags.push('<span class="tz-badge tz-badge-def">= ' + esc(f.default) + '</span>');

            // FK
            var fk = f.ref ? { table: f.ref, column: 'id' } : detectFkTarget(draft, f.name, allDrafts);
            if (fk) {
                flags.push(
                    '<span class="tz-badge tz-badge-fk">→ ' +
                    esc(fk.table) + '.' + esc(fk.column) + '</span>'
                );
            }

            var typeStr = f.type + (f.args ? '(' + f.args + ')' : '');

            html +=
                '<li class="tz-col" data-idx="' + idx + '">' +
                '  <span class="tz-col-name">' +
                '    <span class="tz-col-icon">' +
                (f.isPk ? '🔑' : (fk ? '🔗' : (f.isUnique ? '⚿' : '○'))) +
                '    </span>' +
                '    ' + esc(f.name) +
                '  </span>' +
                '  <span class="tz-col-type">' + esc(typeStr) + '</span>' +
                '  <span class="tz-col-flags">' + flags.join(' ') + '</span>' +
                '</li>';
        });

        root.querySelector('#tz-cols').innerHTML = html;

        // meta
        var meta = [];
        if (draft.source) {
            meta.push('📁 ' + esc(draft.source.database + '.' + draft.source.table));
            meta.push('🔌 ' + esc(draft.source.driver));
        } else {
            meta.push('📄 draft local (sem BD)');
        }
        meta.push('ID: ' + esc(draft.id));
        root.querySelector('#tz-meta').innerHTML = meta.join(' &nbsp;·&nbsp; ');
    }

    function action(act) {
        if (!currentDraft) return;

        switch (act) {
            case 'edit':
                if (window.SqlWizardInspector) {
                    var draft = currentDraft;

                    close();

                    // abre o Inspector
                    window.SqlWizardInspector.show(draft);
                    window.SqlWizardInspector.edit(draft);

                    // seta a apontar para o Inspector
                    if (window.SqlWizardPointer) {
                        setTimeout(function () {
                            window.SqlWizardPointer.point(
                                '#inspector, .inspector-panel',
                                { label: 'Editar aqui', position: 'left', duration: 3500 }
                            );
                        }, 150);
                    }

                    if (window.SqlWizardToast) {
                        SqlWizardToast.info('Inspector aberto em modo edição', { duration: 2500 });
                    }
                }
                break;

            case 'data':
                if (window.SqlWizardBottom) {
                    var bottom = window.SqlWizardBottom;
                    var draft = currentDraft;

                    close();

                    bottom.loadData(draft);              // 1. seta o draft
                    if (bottom.setActiveTab) {
                        bottom.setActiveTab('dados');     // 2. muda a tab
                    }

                    // rede de segurança
                    setTimeout(function () {
                        if (bottom.activeTab === 'dados') bottom.render();
                    }, 250);

                    // seta a apontar para o painel inferior
                    if (window.SqlWizardPointer) {
                        setTimeout(function () {
                            window.SqlWizardPointer.point(
                                '#bottom, #bottom-body, .bottom-panel',
                                { label: 'Dados aqui', position: 'bottom', duration: 3500 }
                            );
                        }, 200);
                    }
                }
                break;

            case 'export':
                if (window.SqlWizardExportModal) {
                    // fecha esta, abre a de exportação (o schema sai só com esta tabela no canvas)
                    close();
                    window.SqlWizardExportModal.open('sql');
                }
                break;

            case 'remove':
                if (!window.SqlWizardCanvas) return;
                if (!confirm('Remover "' + currentDraft.table + '" do canvas?\n\nO draft continua guardado.')) return;
                window.SqlWizardCanvas.remove(currentDraft.id);
                close();
                if (window.SqlWizardToast) SqlWizardToast.success('Removida do canvas', { duration: 2000 });
                break;
        }
    }

    /* ── API ── */

    function open(draft, card) {
        if (!draft) return;
        if (root) root.remove();

        currentDraft = draft;
        currentCard = card || null;

        root = build();
        render(draft);

        // teclado: ESC fecha
        document.addEventListener('keydown', onKey);
    }

    function onKey(e) {
        if (e.key === 'Escape') close();
    }

    function close() {
        if (root) { root.remove(); root = null; }
        currentDraft = null;
        currentCard = null;
        document.removeEventListener('keydown', onKey);
    }

    return { open: open, close: close };
})(window, document);
