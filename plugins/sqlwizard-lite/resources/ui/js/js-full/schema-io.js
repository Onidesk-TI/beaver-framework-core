/* SqlWizard · Export/Import de schema (JSON) */
window.SqlWizardSchemaIO = (function (window, document) {
    'use strict';

    var POS_KEY = 'sqlwizard:positions';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function loadPositions() {
        try { return JSON.parse(localStorage.getItem(POS_KEY) || '{}'); }
        catch (e) { return {}; }
    }

    /* ── Export ───────────────────────────────────────────── */

    function exportSchema() {
        var cards = (window.SqlWizardCanvas && window.SqlWizardCanvas.cards) || [];
        if (!cards.length) {
            if (window.SqlWizardToast) SqlWizardToast.warning('Sem tabelas no canvas para exportar');
            return;
        }

        var positions = loadPositions();
        var edgeTypes = (window.SqlWizardCanvas && window.SqlWizardCanvas.edgeTypes) || {};

        fetch('/api/sqlwizard/schema/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                draft_ids:  cards,
                positions:  positions,
                edge_types: edgeTypes
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok || !res.schema) throw new Error(res.error || 'Erro no export');

            var json = JSON.stringify(res.schema, null, 2);
            var blob = new Blob([json], { type: 'application/json' });
            var url = URL.createObjectURL(blob);

            var name = 'sqlwizard-schema-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.json';

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
                SqlWizardToast.success(
                    'Exportadas ' + res.schema.tables.length + ' tabelas',
                    { title: 'Schema exportado' }
                );
            }
        })
        .catch(function (err) {
            console.error('[schema-io] export', err);
            if (window.SqlWizardToast) SqlWizardToast.error(err.message);
        });
    }

    /* ── Import ───────────────────────────────────────────── */

    function importSchema() {
        // cria input de ficheiro escondido
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json,application/json';
        input.style.display = 'none';

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { input.remove(); return; }

            var reader = new FileReader();
            reader.onload = function (e) {
                try {
                    var schema = JSON.parse(e.target.result);
                    doImport(schema);
                } catch (err) {
                    if (window.SqlWizardToast) SqlWizardToast.error('JSON inválido: ' + err.message);
                }
                input.remove();
            };
            reader.readAsText(file);
        });

        document.body.appendChild(input);
        input.click();
    }

    function doImport(schema) {
        if (!schema || !schema.tables || !schema.tables.length) {
            if (window.SqlWizardToast) SqlWizardToast.warning('Ficheiro sem tabelas');
            return;
        }

        // pergunta modo
        var mode = 'create';
        if (window.confirm('Modo de importação:\n\nOK  → Substituir tabelas com o mesmo ID\nCancelar → Manter as existentes (skip)')) {
            mode = 'merge';
        }

        if (window.SqlWizardToast) {
            SqlWizardToast.info('A importar ' + schema.tables.length + ' tabelas…');
        }

        fetch('/api/sqlwizard/schema/import', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ schema: schema, mode: mode })
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) throw new Error(res.error || 'Erro no import');

            // restaura posições no localStorage
                       // restaura posições no localStorage
            var positions = loadPositions();
            var added = 0;
            res.created.forEach(function (c) {
                if (c.position) {
                    positions[c.id] = { x: c.position.x, y: c.position.y };
                    added++;
                }
            });

            // normaliza: se algum card estiver fora do canto visível, move o grupo todo
            var valid = res.created.filter(function (c) { return c.position; });
            if (valid.length) {
                var minX = Math.min.apply(null, valid.map(function (c) { return c.position.x; }));
                var minY = Math.min.apply(null, valid.map(function (c) { return c.position.y; }));
                var dx = (minX < 40) ? (40 - minX) : 0;
                var dy = (minY < 40) ? (40 - minY) : 0;

                if (dx || dy) {
                    valid.forEach(function (c) {
                        positions[c.id] = {
                            x: c.position.x + dx,
                            y: c.position.y + dy
                        };
                    });
                }
            }

            try {
                localStorage.setItem(POS_KEY, JSON.stringify(positions));
            } catch (e) {}

            // restaura edgeTypes
            if (res.edge_types && Object.keys(res.edge_types).length) {
                window.SqlWizardCanvas = window.SqlWizardCanvas || {};
                window.SqlWizardCanvas.edgeTypes = Object.assign(
                    window.SqlWizardCanvas.edgeTypes || {},
                    res.edge_types
                );
            }

            // recarrega explorer
            if (window.SqlWizardExplorer) {
                window.SqlWizardExplorer.load();
            }

            // mensagem
            var msg = 'Importadas ' + res.created.length + ' tabelas';
            if (res.skipped.length)  msg += ', ' + res.skipped.length + ' já existiam';
            if (res.errors.length)   msg += ', ' + res.errors.length + ' com erro';

            if (window.SqlWizardToast) {
                if (res.errors.length) SqlWizardToast.warning(msg);
                else SqlWizardToast.success(msg);
            }

            // adiciona ao canvas após o explorer carregar
            setTimeout(function () {
                addImportedToCanvas(res.created);
            }, 600);
        })
        .catch(function (err) {
            console.error('[schema-io] import', err);
            if (window.SqlWizardToast) SqlWizardToast.error(err.message);
        });
    }

    function addImportedToCanvas(created) {
        if (!window.SqlWizardCanvas || !window.SqlWizardExplorer) return;

        var drafts = window.SqlWizardExplorer.drafts || [];

        created.forEach(function (c) {
            var draft = null;
            for (var i = 0; i < drafts.length; i++) {
                if (drafts[i].id === c.id) { draft = drafts[i]; break; }
            }
            if (!draft) return;
            if (window.SqlWizardCanvas.has && window.SqlWizardCanvas.has(c.id)) return;

            window.SqlWizardCanvas.add(draft);
        });

        // redesenha linhas
        setTimeout(function () {
            if (window.SqlWizardCanvas.drawEdges) window.SqlWizardCanvas.drawEdges();
        }, 200);
    }

    return {
        export: exportSchema,
        import: importSchema
    };
})(window, document);
