/* beaver-framework/plugins/sqlwizard/resources/ui/js/relation-wizard.js */

window.SqlWizardRelationWizard = (function (window, document) {
    'use strict';

    var backdrop = null;
    var modalEl  = null;
    var state    = null;

    /* ── Helpers ─────────────────────────────────────────── */

    function parseColumnName(field) {
        var m = String(field).match(/^([^:?=]+)/);
        return m ? m[1].trim() : String(field).trim();
    }

    function columnsOf(draft) {
        return (draft.fields || []).map(parseColumnName);
    }

    function findDraftByTable(table) {
        var ex = window.SqlWizardExplorer;
        if (!ex || !ex.drafts) return null;
        for (var i = 0; i < ex.drafts.length; i++) {
            if (ex.drafts[i].table === table) return ex.drafts[i];
        }
        return null;
    }

    function findDraftById(id) {
        var ex = window.SqlWizardExplorer;
        if (!ex || !ex.drafts) return null;
        for (var i = 0; i < ex.drafts.length; i++) {
            if (ex.drafts[i].id === id) return ex.drafts[i];
        }
        return null;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function optionHtml(value, label, selected) {
        return '<option value="' + escapeHtml(value) + '"' +
               (value === selected ? ' selected' : '') + '>' +
               escapeHtml(label) + '</option>';
    }

    /* ── API pública ─────────────────────────────────────── */

    function open(opts) {
        opts = opts || {};
        backdrop = document.getElementById('modal-backdrop');
        modalEl  = document.getElementById('modal');

        if (!backdrop || !modalEl) {
            console.warn('[relation-wizard] modal nao encontrado');
            return;
        }

        // Descobre source: pelo ctx ou pelos parâmetros
        var sourceDraft = null;
        if (opts.draftId) {
            sourceDraft = findDraftById(opts.draftId);
        } else if (opts.table) {
            sourceDraft = findDraftByTable(opts.table);
        }

        if (!sourceDraft) {
            if (window.SqlWizardToast) {
                window.SqlWizardToast.error('Não foi possível identificar a tabela de origem');
            }
            return;
        }

        var allDrafts = (window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) || [];
        var otherDrafts = allDrafts.filter(function (d) {
            return d.id !== sourceDraft.id;
        });

        if (!otherDrafts.length) {
            if (window.SqlWizardToast) {
                window.SqlWizardToast.warning('Não há outras tabelas no canvas para relacionar');
            }
            return;
        }

        state = {
            sourceDraft: sourceDraft,
            sourceColumns: columnsOf(sourceDraft),
            allTargets: otherDrafts,
            payload: null
        };

        renderStep();
        backdrop.hidden = false;
    }

    /* ── Render ──────────────────────────────────────────── */

    function renderStep() {
        var src = state.sourceDraft;
        var srcCols = state.sourceColumns;

        // Tabela alvo default: primeira
        var defaultTarget = state.payload ? state.payload.targetTable : state.allTargets[0].table;
        var targetDraft = findDraftByTable(defaultTarget) || state.allTargets[0];
        var targetCols = columnsOf(targetDraft);

        var defaultSourceCol = state.payload ? state.payload.sourceColumn :
            (srcCols.indexOf('id') !== -1 ? srcCols[0] : srcCols[0]);
        var defaultTargetCol = state.payload ? state.payload.targetColumn :
            (targetCols.indexOf('id') !== -1 ? 'id' : targetCols[0]);

        var relationTypes = [
            { value: 'n:1', label: 'n : 1  ·  muitos para um' },
            { value: '1:1', label: '1 : 1  ·  um para um' },
            { value: 'n:n', label: 'n : n  ·  muitos para muitos' }
        ];
        var onDeleteOptions = ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION', 'SET DEFAULT'];
        var onUpdateOptions = ['CASCADE', 'RESTRICT', 'NO ACTION'];

        var srcOptions  = srcCols.map(function (c)  { return optionHtml(c, c, defaultSourceCol); }).join('');
        var tgtOptions  = targetCols.map(function (c) { return optionHtml(c, c, defaultTargetCol); }).join('');
        var tblOptions  = state.allTargets.map(function (d) {
            return optionHtml(d.table, d.table, defaultTarget);
        }).join('');

        var relOptions = relationTypes.map(function (r) {
            return optionHtml(r.value, r.label, state.payload ? state.payload.relationType : 'n:1');
        }).join('');

        var delOptions = onDeleteOptions.map(function (v) {
            return optionHtml(v, v, state.payload ? state.payload.onDelete : 'CASCADE');
        }).join('');

        var updOptions = onUpdateOptions.map(function (v) {
            return optionHtml(v, v, state.payload ? state.payload.onUpdate : 'CASCADE');
        }).join('');

        var html =
            '<div class="modal-header">' +
            '  <span class="modal-icon">🪄</span>' +
            '  <span>Wizard de relação</span>' +
            '</div>' +
            '<div class="modal-body">' +
            '  <div class="rw-step">' +
            '    <div class="rw-step-num">1</div>' +
            '    <div class="rw-step-body">' +
            '      <div class="rw-step-title">Origem</div>' +
            '      <div class="rw-row">' +
            '        <label>Tabela</label>' +
            '        <div class="rw-fixed">' + escapeHtml(src.table) + '</div>' +
            '      </div>' +
            '      <div class="rw-row">' +
            '        <label>Coluna</label>' +
            '        <select class="modal-select" data-name="source_column">' + srcOptions + '</select>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +

            '  <div class="rw-step">' +
            '    <div class="rw-step-num">2</div>' +
            '    <div class="rw-step-body">' +
            '      <div class="rw-step-title">Destino</div>' +
            '      <div class="rw-row">' +
            '        <label>Tabela</label>' +
            '        <select class="modal-select" data-name="target_table">' + tblOptions + '</select>' +
            '      </div>' +
            '      <div class="rw-row">' +
            '        <label>Coluna</label>' +
            '        <select class="modal-select" data-name="target_column">' + tgtOptions + '</select>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +

            '  <div class="rw-step">' +
            '    <div class="rw-step-num">3</div>' +
            '    <div class="rw-step-body">' +
            '      <div class="rw-step-title">Tipo e comportamento</div>' +
            '      <div class="rw-row">' +
            '        <label>Tipo de relação</label>' +
            '        <select class="modal-select" data-name="relation_type">' + relOptions + '</select>' +
            '      </div>' +
            '      <div class="rw-row rw-row-2col">' +
            '        <div>' +
            '          <label>ON DELETE</label>' +
            '          <select class="modal-select" data-name="on_delete">' + delOptions + '</select>' +
            '        </div>' +
            '        <div>' +
            '          <label>ON UPDATE</label>' +
            '          <select class="modal-select" data-name="on_update">' + updOptions + '</select>' +
            '        </div>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +

            '  <div class="rw-preview">' +
            '    <div class="rw-preview-label">Pré-visualização</div>' +
            '    <div class="rw-preview-sql" id="rw-preview"></div>' +
            '  </div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '  <button class="btn" data-role="cancel">Cancelar</button>' +
            '  <button class="btn is-primary" data-role="confirm">Criar relação</button>' +
            '</div>';

        modalEl.innerHTML = html;

        // Bind
        modalEl.querySelector('[data-role="cancel"]').addEventListener('click', close);
        modalEl.querySelector('[data-role="confirm"]').addEventListener('click', submit);

        var selectTargetTable = modalEl.querySelector('[data-name="target_table"]');
        selectTargetTable.addEventListener('change', function () {
            updateTargetColumns(selectTargetTable.value);
            updatePreview();
        });

        modalEl.querySelectorAll('[data-name]').forEach(function (el) {
            el.addEventListener('change', updatePreview);
        });

        updatePreview();
    }

    function updateTargetColumns(targetTable) {
        var draft = findDraftByTable(targetTable);
        if (!draft) return;

        var cols = columnsOf(draft);
        var sel  = modalEl.querySelector('[data-name="target_column"]');
        if (!sel) return;

        var current = sel.value;
        sel.innerHTML = cols.map(function (c) {
            return optionHtml(c, c, c === current ? c : (c === 'id' ? c : null));
        }).join('');
    }

    function readValues() {
        var vals = {};
        modalEl.querySelectorAll('[data-name]').forEach(function (el) {
            vals[el.getAttribute('data-name')] = el.value;
        });
        return vals;
    }

    function updatePreview() {
        var vals = readValues();
        var el = document.getElementById('rw-preview');
        if (!el) return;

        var sql =
            'ALTER TABLE `' + state.sourceDraft.table + '`\n' +
            '  ADD CONSTRAINT `fk_' + state.sourceDraft.table + '_' + vals.source_column + '`\n' +
            '  FOREIGN KEY (`' + vals.source_column + '`)\n' +
            '  REFERENCES `' + vals.target_table + '` (`' + vals.target_column + '`)\n' +
            '  ON DELETE ' + vals.on_delete + '\n' +
            '  ON UPDATE ' + vals.on_update + ';';

        el.textContent = sql;
    }

    /* ── Submit ──────────────────────────────────────────── */

    function submit() {
        var vals = readValues();
        var src  = state.sourceDraft;

        if (!src.source || !src.source.connection_id || !src.source.database) {
            if (window.SqlWizardToast) {
                window.SqlWizardToast.error('Tabela de origem sem ligação/base de dados');
            }
            return;
        }

        if (!window.SqlWizardApi || typeof window.SqlWizardApi.createFk !== 'function') {
            if (window.SqlWizardToast) {
                window.SqlWizardToast.error('API createFk indisponível');
            }
            return;
        }

        var btn = modalEl.querySelector('[data-role="confirm"]');
        if (btn) { btn.disabled = true; btn.textContent = 'A criar…'; }

        window.SqlWizardApi.createFk(
            src.source.connection_id,
            src.source.database,
            src.table,
            {
                column:            vals.source_column,
                references_table:  vals.target_table,
                references_column: vals.target_column,
                on_delete:         vals.on_delete,
                on_update:         vals.on_update,
                relation_type:     vals.relation_type
            }
        ).then(function () {
            if (window.SqlWizardToast) {
                window.SqlWizardToast.success(
                    'Relação criada: ' + src.table + '.' + vals.source_column +
                    ' → ' + vals.target_table + '.' + vals.target_column,
                    { title: 'SqlWizard', duration: 3500 }
                );
            }

            if (window.SqlWizardCanvas && typeof window.SqlWizardCanvas.drawEdges === 'function') {
                setTimeout(function () { window.SqlWizardCanvas.drawEdges(); }, 250);
            }

            if (window.SqlWizardExplorer && typeof window.SqlWizardExplorer.load === 'function') {
                window.SqlWizardExplorer.load();
            }

            close();
        }).catch(function (err) {
            console.error('[relation-wizard] erro:', err);
            if (window.SqlWizardToast) {
                window.SqlWizardToast.error(
                    (err && err.message) || 'Erro ao criar relação'
                );
            }
            if (btn) { btn.disabled = false; btn.textContent = 'Criar relação'; }
        });
    }

    function close() {
        if (backdrop) backdrop.hidden = true;
        state = null;
    }

    return {
        open: open,
        close: close
    };

})(window, document);