(function (window, document) {
    'use strict';

    var App = {
        init: function () {
            console.log('[sqlwizard] a arrancar');

            // O dock cria os painéis e inicializa Explorer, Canvas, Inspector e Bottom
            window.SqlWizardDock.init();

            // Globais (não pertencem a nenhum painel)
            window.SqlWizardContextMenu.init();
            window.SqlWizardModal.init();



            window.addEventListener('sqlwizard:draft-selected', function (e) {
                App.onDraftSelected(e.detail.draft);
            });

            window.addEventListener('sqlwizard:context-action', function (e) {
                App.onContextAction(e.detail.action, e.detail.ctx);
            });


            window.addEventListener('sqlwizard:card-clicked', function (e) {
                App.onCardClicked(e.detail.draftId);
            });
            window.addEventListener('sqlwizard:tab-closed', function (e) {
                if (window.SqlWizardCanvas) {
                    window.SqlWizardCanvas.remove(e.detail.draftId);
                }
            });

            this.bindPanelToggles();
            this.bindBottomTabs();

            console.log('[sqlwizard] pronto');
        },

        onContextAction: function (action, ctx) {
            console.log('[sqlwizard] context action:', action, ctx);

            switch (action) {
                case 'view-table':
                    this.viewTable(ctx);
                    break;

                case 'edit-table':
                    // Por agora, só um placeholder
                    console.log('TODO: abrir editor de tabela para', ctx.draftId);
                    this.editTable(ctx);
                    break;

                case 'add-column':
                    console.log('TODO: adicionar coluna a', ctx.draftId);
                    break;

                case 'config-columns':
                    console.log('TODO: configurar colunas de', ctx.draftId);
                    break;

                case 'duplicate-table':
                    console.log('TODO: duplicar', ctx.draftId);
                    this.duplicateTable(ctx);
                    break;

                case 'export-sql':
                    console.log('TODO: exportar SQL de', ctx.draftId);
                    this.exportSql(ctx);
                    break;

                case 'delete-draft':
                    this.beforeDelete(ctx);
                    break;

                // ── Ações de relação (linha) ──
                case 'edit-relation':
                    this.editRelation(ctx);
                    break;

                case 'copy-relation':
                    this.copyRelation(ctx);
                    break;

                case 'relation-details':
                    this.relationDetails(ctx);
                    break;

                case 'relation-wizard':
                    this.openRelationWizard(ctx);
                    break;

                case 'delete-relation':
                    this.beforeDeleteRelation(ctx);
                    break;

                //eng reversa
                // ── Ações na conexão ──
                case 'reverse-engineer':
                    if (!window.SqlWizardReverse) {
                        console.warn('[sqlwizard] SqlWizardReverse não está carregado');
                        if (window.SqlWizardToast) SqlWizardToast.error('Engenharia reversa indisponível');
                        break;
                    }
                    if (!ctx.database) {
                        if (window.SqlWizardToast) SqlWizardToast.warning('Seleciona uma base de dados primeiro');
                        break;
                    }
                    window.SqlWizardReverse.open({
                        connectionId: ctx.connectionId,
                        connectionName: ctx.connection && ctx.connection.name,
                        database: ctx.database
                    });
                    break;

                default:
                    console.warn('[sqlwizard] ação desconhecida:', action);


            }
        },

        duplicateTable: function (ctx) {
            var draftId = ctx.draftId;
            var self = this;

            var explorer = window.SqlWizardExplorer;
            var original = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === draftId) {
                        original = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!original) {
                alert('Draft nao encontrado.');
                return;
            }

            window.SqlWizardModal.prompt({
                title: 'Duplicar tabela',
                body: '<p>Duplicar <code>' + this.escape(original.table) + '</code> para uma nova tabela.</p>',
                label: 'Nome da nova tabela:',
                defaultValue: original.table + '_copy',
                confirmLabel: 'Duplicar',
                cancelLabel: 'Cancelar',
                onConfirm: function (newName) {
                    if (!newName) return;
                    self.doDuplicateTable(original, newName);
                }
            });
        },
        editTable: function (ctx) {
            if (!window.SqlWizardInspector) return;

            // Encontra o draft no Explorer
            var explorer = window.SqlWizardExplorer;
            var draft = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === ctx.draftId) {
                        draft = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!draft) {
                alert('Draft não encontrado.');
                return;
            }

            window.SqlWizardInspector.edit(draft);
        },

        doDuplicateTable: function (original, newTable) {
            console.log('[sqlwizard] a duplicar', original.table, 'para', newTable);

            var payload = {
                table: newTable,
                action: 'create_table',
                fields: original.fields || []
            };

            window.SqlWizardApi.createDraft(payload)
                .then(function (res) {
                    console.log('[sqlwizard] draft duplicado:', res.draft.id);
                    window.SqlWizardExplorer.load();
                })
                .catch(function (err) {
                    console.error('[sqlwizard] erro ao duplicar:', err);
                    alert('Erro ao duplicar: ' + err.message);
                });
        },

        /**
 * Exporta o SQL do draft selecionado como ficheiro .sql
 */
        exportSql: function (ctx) {
            var draftId = ctx.draftId;

            // 1. Encontra o draft no Explorer
            var explorer = window.SqlWizardExplorer;
            var draft = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === draftId) {
                        draft = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!draft) {
                console.warn('[sqlwizard] draft não encontrado para exportar:', draftId);
                alert('Draft não encontrado.');
                return;
            }

            if (!draft.sql) {
                alert('Este draft não tem SQL.');
                return;
            }

            // 2. Cabeçalho com metadados
            var header =
                '-- ============================================================\n' +
                '-- Beaver · SqlWizard\n' +
                '-- ------------------------------------------------------------\n' +
                '-- Tabela:    ' + draft.table + '\n' +
                '-- Draft ID:  ' + draft.id + '\n' +
                '-- Ação:      ' + (draft.action || 'create_table') + '\n' +
                '-- Criado em: ' + (draft.created_at || '') + '\n' +
                '-- Exportado: ' + new Date().toISOString() + '\n' +
                '-- ============================================================\n\n';

            var content = header + draft.sql + '\n';

            // 3. Dispara o download
            this.downloadFile(draft.table + '.sql', content, 'text/sql');
        },

        /**
         * Força o download de um ficheiro com um dado conteúdo.
         */
        downloadFile: function (filename, content, mimeType) {
            var blob = new Blob([content], { type: mimeType || 'text/plain' });
            var url = URL.createObjectURL(blob);

            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.style.display = 'none';

            document.body.appendChild(a);
            a.click();

            // Cleanup
            setTimeout(function () {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 100);
        },

        onCardClicked: function (draftId) {
            console.log('[sqlwizard] card clicked:', draftId);

            // Encontra o draft no Explorer
            var explorer = window.SqlWizardExplorer;
            var draft = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === draftId) {
                        draft = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!draft) {
                console.warn('[sqlwizard] draft nao encontrado:', draftId);
                return;
            }

            // Abre no Inspector em modo edição
            if (window.SqlWizardInspector) {
                window.SqlWizardInspector.show(draft);
                window.SqlWizardInspector.show(draft);
                window.SqlWizardInspector.show(draft);
                window.SqlWizardInspector.edit(draft);
            }

            // atualiza o painel inferior (sandbox/dados) para este draft
            if (window.SqlWizardBottom) {
                window.SqlWizardBottom.loadData(draft);      // muda currentDraft + reseta sandbox
                window.SqlWizardBottom.loadSandbox(draft);   // carrega a sandbox deste draft (se existir)
            }
        },
        onDraftSelected: function (draft) {
            console.log('[sqlwizard] draft selecionado:', draft.id);

            // 1. Adicionar cartão ao canvas (acumula)
            if (window.SqlWizardCanvas) {
                window.SqlWizardCanvas.add(draft);
            }

            // 2. Abrir tab no Inspector
            if (window.SqlWizardInspector) {
                window.SqlWizardInspector.show(draft);
            }

            // 3. Carregar dados no painel inferior (se tem source)
            if (window.SqlWizardBottom) {
                window.SqlWizardBottom.loadData(draft);
            }
        },

        /**
 * Pede confirmação antes de apagar um draft.
 * Mostra uma modal com o nome da tabela.
 */
        beforeDelete: function (ctx) {
            var draftId = ctx.draftId;
            var table = ctx.table || draftId;
            var self = this;

            window.SqlWizardModal.confirm({
                title: 'Apagar draft',
                body:
                    '<p>Tens a certeza que queres apagar o draft da tabela <code>' +
                    this.escape(table) +
                    '</code>?</p>' +
                    '<p class="is-muted">Isto <strong>não</strong> toca na base de dados. ' +
                    'Isto <strong>não</strong> afeta migrações já aplicadas.</p>' +
                    '<p class="is-muted" style="font-size: 11px;">ID: ' +
                    this.escape(draftId) +
                    '</p>',
                confirmLabel: 'Apagar',
                cancelLabel: 'Cancelar',
                danger: true,
                onConfirm: function () {
                    self.doDeleteDraft(draftId);
                }
            });
        },

        /**
         * Apaga o draft (chamado depois da confirmação).
         */
        doDeleteDraft: function (draftId) {
            console.log('[sqlwizard] a apagar draft:', draftId);
            window.SqlWizardApi.deleteDraft(draftId)
                .then(function () {
                    console.log('[sqlwizard] draft apagado:', draftId);

                    // Remover o cartão do canvas
                    if (window.SqlWizardCanvas) {
                        window.SqlWizardCanvas.remove(draftId);
                    }

                    // Fechar a tab no Inspector
                    if (window.SqlWizardInspector) {
                        window.SqlWizardInspector.closeTab(draftId);
                    }

                    // Se era o selecionado, limpar referência
                    if (window.SqlWizardExplorer.selected === draftId) {
                        window.SqlWizardExplorer.selected = null;

                        var pre = document.getElementById('bottom-pre');
                        if (pre) pre.textContent = '-- SQL vai aparecer aqui';
                    }

                    // Recarregar a lista de drafts no Explorer
                    window.SqlWizardExplorer.load();
                })
                .catch(function (err) {
                    console.error('[sqlwizard] erro ao apagar:', err);
                    alert('Erro ao apagar: ' + err.message);
                });
        },
        bindPanelToggles: function () {
            var self = this;

            // Botão "Reset layout" (⟲)
            var resetBtn = document.getElementById('btn-reset-layout');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    if (!confirm('Repor o layout inicial?\n\nAs tuas alterações de painéis serão perdidas.')) {
                        return;
                    }

                    if (window.SqlWizardDock) {
                        window.SqlWizardDock.reset();
                    }

                    if (window.SqlWizardToast) {
                        window.SqlWizardToast.success('Layout reposto', { duration: 2000 });
                    }
                });
            }

            // ─────────────────────────────────────────────────────────
            // Botão "Frankei" (🔍)
            // ─────────────────────────────────────────────────────────
            var frankeiBtn = document.getElementById('btn-frankei');
            if (frankeiBtn) {
                frankeiBtn.addEventListener('click', function () {

                    if (!window.FrankeiPanel) {
                        console.warn('[sqlwizard] FrankeiPanel não está carregado');
                        if (window.SqlWizardToast) {
                            window.SqlWizardToast.error('Frankei não está disponível');
                        }
                        return;
                    }

                    // ── 1. Descobrir contexto atual (draft selecionado) ──
                    var selected = (window.SqlWizardCanvas && window.SqlWizardCanvas.selected)
                        ? window.SqlWizardCanvas.selected
                        : [];

                    var draftId = selected[0] || null;
                    var draft = null;

                    if (draftId && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                        for (var i = 0; i < window.SqlWizardExplorer.drafts.length; i++) {
                            if (window.SqlWizardExplorer.drafts[i].id === draftId) {
                                draft = window.SqlWizardExplorer.drafts[i];
                                break;
                            }
                        }
                    }

                    // ligação/db do draft (se tiver source)
                    var ctxConnId = (draft && draft.source) ? draft.source.connection_id : null;
                    var ctxDbName = (draft && draft.source) ? draft.source.database : null;

                    // fallback: primeira ligação do explorer
                    if (!ctxConnId && window.SqlWizardExplorer && window.SqlWizardExplorer.connections) {
                        var firstConn = window.SqlWizardExplorer.connections[0];
                        if (firstConn) {
                            ctxConnId = firstConn.id;
                            ctxDbName = firstConn.selectedDb || null;
                        }
                    }

                    // ── 2. Criar painel (uma única vez) ──
                    if (!window.Frankei) window.Frankei = {};

                    if (!window.Frankei.panel) {
                        window.Frankei.panel = window.FrankeiPanel.create({ title: 'Frankei' });

                        // registar o painel no frankei-app.js
                        // (dá acesso ao state interno que o runScan usa)
                        if (typeof window.Frankei.attachPanel === 'function') {
                            window.Frankei.attachPanel(window.Frankei.panel);
                        } else {
                            console.warn('[sqlwizard] Frankei.attachPanel não disponível');
                        }

                        // handler das ações do painel (runScan / clear)
                        window.Frankei.panel.onAction = function (action) {
                            console.log('[frankei] ação:', action);

                            if (action === 'scan') {
                                // Recalcular contexto no momento do clique
                                var sel = (window.SqlWizardCanvas && window.SqlWizardCanvas.selected) || [];
                                var dId = sel[0] || null;
                                var d = null;

                                if (dId && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                                    for (var j = 0; j < window.SqlWizardExplorer.drafts.length; j++) {
                                        if (window.SqlWizardExplorer.drafts[j].id === dId) {
                                            d = window.SqlWizardExplorer.drafts[j];
                                            break;
                                        }
                                    }
                                }

                                var connId = (d && d.source) ? d.source.connection_id : null;
                                var dbName = (d && d.source) ? d.source.database : null;

                                if (!connId && window.SqlWizardExplorer && window.SqlWizardExplorer.connections) {
                                    var c = window.SqlWizardExplorer.connections[0];
                                    if (c) {
                                        connId = c.id;
                                        dbName = c.selectedDb || null;
                                    }
                                }

                                if (!connId) {
                                    if (window.SqlWizardToast) {
                                        SqlWizardToast.warning('Sem ligação selecionada. Escolhe uma no explorer.');
                                    }
                                    return;
                                }

                                window.Frankei.panel.setContext({
                                    draftId: dId,
                                    draft: d,
                                    table: d ? d.table : null,
                                    source: d ? d.source : null,
                                    connectionId: connId,
                                    database: dbName
                                });

                                if (typeof window.Frankei.runScan === 'function') {
                                    window.Frankei.runScan({});
                                } else {
                                    console.warn('[frankei] runScan não está exposto');
                                }
                            }

                            if (action === 'clear' && typeof window.Frankei.clearResults === 'function') {
                                window.Frankei.clearResults();
                            }
                        };
                    }

                    // ── 3. Abrir o painel ──
                    window.Frankei.panel.open();

                    // ── 4. Passar contexto atual (com connectionId/database) ──
                    window.Frankei.panel.setContext({
                        draftId: draftId,
                        draft: draft,
                        table: draft ? draft.table : null,
                        source: draft ? draft.source : null,
                        connectionId: ctxConnId,
                        database: ctxDbName
                    });
                });
            }


            // Botão "Library" (📚)
            var libraryBtn = document.getElementById('btn-library');
            if (libraryBtn) {
                libraryBtn.addEventListener('click', function () {
                    if (!window.LibraryPanel) {
                        console.warn('[sqlwizard] LibraryPanel não está carregado');
                        if (window.SqlWizardToast) SqlWizardToast.error('Library não está disponível');
                        return;
                    }

                    // contexto atual
                    var selected = (window.SqlWizardCanvas && window.SqlWizardCanvas.selected) || [];
                    var draftId = selected[0] || null;
                    var draft = null;

                    if (draftId && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                        for (var i = 0; i < window.SqlWizardExplorer.drafts.length; i++) {
                            if (window.SqlWizardExplorer.drafts[i].id === draftId) {
                                draft = window.SqlWizardExplorer.drafts[i];
                                break;
                            }
                        }
                    }

                    var connId = (draft && draft.source) ? draft.source.connection_id : null;
                    var dbName = (draft && draft.source) ? draft.source.database : null;

                    if (!connId && window.SqlWizardExplorer && window.SqlWizardExplorer.connections) {
                        var conn = window.SqlWizardExplorer.connections[0];
                        if (conn) { connId = conn.id; dbName = conn.selectedDb || null; }
                    }

                    // lista de tabelas do canvas
                    var tables = [];
                    if (window.SqlWizardCanvas && window.SqlWizardCanvas.cards) {
                        window.SqlWizardCanvas.cards.forEach(function (id) {
                            if (draft && draft.id === id) { tables.push(draft.table); return; }
                            // procura no explorer
                            if (window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                                for (var j = 0; j < window.SqlWizardExplorer.drafts.length; j++) {
                                    if (window.SqlWizardExplorer.drafts[j].id === id) {
                                        tables.push(window.SqlWizardExplorer.drafts[j].table);
                                        break;
                                    }
                                }
                            }
                        });
                    }
                    // fallback: todas as drafts
                    if (!tables.length && window.SqlWizardExplorer && window.SqlWizardExplorer.drafts) {
                        tables = window.SqlWizardExplorer.drafts.map(function (d) { return d.table; });
                    }

                    // cria/reutiliza painel
                    if (!window.Library) window.Library = {};
                    if (!window.Library.panel) {
                        window.Library.panel = window.LibraryPanel.create({ title: 'Library' });
                    }

                    window.Library.panel.open();

                    // função que renderiza depois do layout.html carregar
                    function renderLibrary(root) {
                        window.LibraryBrowser.render(root, {
                            onItemSelected: function (library, item) {
                                window.LibraryEditor.render(root, library, item, {
                                    connectionId: connId,
                                    database: dbName,
                                    tables: tables,
                                    table: draft ? draft.table : null
                                });
                            }
                        });
                    }

                    var root = window.Library.panel.body();
                    if (root && root.querySelector('#lib-content')) {
                        renderLibrary(root);
                    } else {
                        // layout ainda não carregou — espera
                        var tries = 0;
                        (function tick() {
                            var r = window.Library.panel.body();
                            if (r && r.querySelector('#lib-content')) {
                                renderLibrary(r);
                            } else if (tries++ < 15) {
                                setTimeout(tick, 100);
                            }
                        })();
                    }
                });

                // Botão "Licença" (🔑)
                var licenseBtn = document.getElementById('btn-license');
                if (licenseBtn) {
                    licenseBtn.addEventListener('click', function () {
                        if (window.SqlWizardLicenseConfig) {
                            window.SqlWizardLicenseConfig.open();
                        } else {
                            console.warn('[sqlwizard] SqlWizardLicenseConfig não está carregado');
                        }
                    });
                }
            }



            // Botão "Console" (▸)
            var consoleBtn = document.getElementById("btn-console");
            if (consoleBtn) {
                consoleBtn.addEventListener("click", function () {
                    if (window.BeaverCliConsole) {
                        window.BeaverCliConsole.open();
                    } else {
                        console.warn("[sqlwizard] BeaverCliConsole não está carregado");
                        if (window.SqlWizardToast) SqlWizardToast.error("Console não está disponível");
                    }
                });
            }

            // Botão "Tips" (💡)
            var tipsBtn = document.getElementById('btn-tips');
            if (tipsBtn) {
                tipsBtn.addEventListener('click', function () {
                    if (!window.Tips || typeof window.Tips.open !== 'function') {
                        console.warn('[sqlwizard] Tips não está carregado');
                        if (window.SqlWizardToast) SqlWizardToast.error('Tips não está disponível');
                        return;
                    }
                    window.Tips.open();
                });
            }

            // Botões Schema export/import
            var expBtn = document.getElementById('btn-schema-export');
            if (expBtn) {
                expBtn.addEventListener('click', function () {
                    if (window.SqlWizardSchemaIO) window.SqlWizardSchemaIO.export();
                });
            }

            var impBtn = document.getElementById('btn-schema-import');
            if (impBtn) {
                impBtn.addEventListener('click', function () {
                    if (window.SqlWizardSchemaIO) window.SqlWizardSchemaIO.import();
                });
            }
        },

        bindBottomTabs: function () {
            var self = this;

            document.querySelectorAll('.bottom-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var name = tab.getAttribute('data-tab');

                    document.querySelectorAll('.bottom-tab').forEach(function (t) {
                        t.classList.remove('is-active');
                    });
                    tab.classList.add('is-active');

                    if (window.SqlWizardBottom) {
                        window.SqlWizardBottom.setActiveTab(name);
                    }
                });
            });
        },

        /* ── Ações sobre relações (linhas) ─────────────────── */

        editRelation: function (ctx) {
            if (!window.SqlWizardCanvas) return;

            window.SqlWizardCanvas.cycleEdgeType(ctx.draftId, ctx.column);
        },
        openRelationWizard: function (ctx) {
            if (!window.SqlWizardRelationWizard) {
                console.warn('[sqlwizard] SqlWizardRelationWizard não está carregado');
                return;
            }

            window.SqlWizardRelationWizard.open({
                draftId: ctx.draftId,
                table: ctx.table,
                column: ctx.column,
                refTable: ctx.refTable,
                refColumn: ctx.refColumn,
                relation: ctx.relation
            }, function (result) {
                if (!result || result.cancelled) return;

                // Aplicar o tipo escolhido no canvas
                if (window.SqlWizardCanvas && ctx.draftId && ctx.column) {
                    var key = ctx.draftId + ':' + ctx.column;
                    window.SqlWizardCanvas.edgeTypes[key] = result.relation;
                    window.SqlWizardCanvas.drawEdges();
                }

                if (window.SqlWizardToast) {
                    window.SqlWizardToast.success(
                        'Tipo alterado para ' + result.relation,
                        { duration: 2000 }
                    );
                }
            });
        },

        copyRelation: function (ctx) {
            var originCard = this.relationOriginCard(ctx.relation);
            var targetCard = this.relationTargetCard(ctx.relation);

            var text =
                ctx.table + '.' + ctx.column + ' (' + originCard + ')' +
                ' → ' +
                ctx.refTable + '.' + ctx.refColumn + ' (' + targetCard + ')' +
                '  [' + ctx.relation + ']';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    if (window.SqlWizardToast) {
                        window.SqlWizardToast.success(text, {
                            title: 'Copiado',
                            duration: 2500
                        });
                    }
                });
            } else {
                alert('Copiado: ' + text);
            }
        },

        // Devolve a cardinalidade da origem ('N', '1' ou 'N')
        relationOriginCard: function (relation) {
            var parts = String(relation).split(':');
            return parts[0] === 'n' ? 'N' : parts[0];
        },

        // Devolve a cardinalidade do destino
        relationTargetCard: function (relation) {
            var parts = String(relation).split(':');
            return parts[1] === 'n' ? 'N' : parts[1];
        },

        // Texto humano da relação
        relationHumanText: function (relation, ctx) {
            if (relation === 'n:1') {
                return 'Muitas ' + ctx.table + ' apontam para 1 ' + ctx.refTable;
            }
            if (relation === '1:n') {
                return '1 ' + ctx.table + ' tem muitas ' + ctx.refTable;
            }
            if (relation === '1:1') {
                return '1 ' + ctx.table + ' está ligada a 1 ' + ctx.refTable;
            }
            if (relation === 'n:n') {
                return 'Muitas ' + ctx.table + ' ligam a muitas ' + ctx.refTable;
            }
            return '';
        },

        // Devolve a cardinalidade da origem ('N', '1' ou 'N')
        relationOriginCard: function (relation) {
            var parts = String(relation).split(':');
            return parts[0] === 'n' ? 'N' : parts[0];
        },

        // Devolve a cardinalidade do destino
        relationTargetCard: function (relation) {
            var parts = String(relation).split(':');
            return parts[1] === 'n' ? 'N' : parts[1];
        },

        // Texto humano da relação
        relationHumanText: function (relation, ctx) {
            if (relation === 'n:1') {
                return 'Muitas ' + ctx.table + ' apontam para 1 ' + this.singularize(ctx.refTable);
            }
            if (relation === '1:n') {
                return '1 ' + this.singularize(ctx.table) + ' tem muitas ' + ctx.refTable;
            }
            if (relation === '1:1') {
                return '1 ' + this.singularize(ctx.table) + ' está ligado a 1 ' + this.singularize(ctx.refTable);
            }
            if (relation === 'n:n') {
                return 'Muitas ' + ctx.table + ' ligam a muitas ' + ctx.refTable;
            }
            return '';
        },

        /**
         * Singularização simples (para textos humanos):
         *   users      → user
         *   posts      → post
         *   categories → category
         *   boxes      → box
         *   fornecedores → fornecedor
         */
        singularize: function (word) {
            var w = String(word);

            // -ies → -y  (categories → category)
            if (/ies$/.test(w)) {
                return w.replace(/ies$/, 'y');
            }

            // -es → ""  (boxes → box, fornecedores → fornecedor)
            if (/es$/.test(w)) {
                return w.replace(/es$/, '');
            }

            // -s → ""  (users → user)
            if (/s$/.test(w)) {
                return w.replace(/s$/, '');
            }

            return w;
        },

        pluralize: function (word) {
            if (/y$/.test(word)) return word.replace(/y$/, 'ies');
            if (/(s|x|z|ch|sh)$/.test(word)) return word + 'es';
            return word + 's';
        },

        relationDetails: function (ctx) {
            if (!window.SqlWizardModal) return;

            var originCard = this.relationOriginCard(ctx.relation);
            var targetCard = this.relationTargetCard(ctx.relation);
            var human = this.relationHumanText(ctx.relation, ctx);

            var body =
                '<p><strong>Origem:</strong> <code>' +
                this.escape(ctx.table + '.' + ctx.column) +
                '</code> <span class="is-muted">(' + originCard + ')</span></p>' +

                '<p><strong>Destino:</strong> <code>' +
                this.escape(ctx.refTable + '.' + ctx.refColumn) +
                '</code> <span class="is-muted">(' + targetCard + ')</span></p>' +

                '<p><strong>Tipo:</strong> <code>' + this.escape(ctx.relation) + '</code></p>' +

                (human
                    ? '<p class="is-muted" style="margin-top: 8px; font-style: italic;">' +
                    this.escape(human) +
                    '</p>'
                    : '');

            window.SqlWizardModal.confirm({
                title: 'Relação',
                body: body,
                confirmLabel: 'Alterar tipo',
                cancelLabel: 'Fechar',
                onConfirm: function () {
                    window.SqlWizardCanvas.cycleEdgeType(ctx.draftId, ctx.column);
                }
            });
        },

        beforeDeleteRelation: function (ctx) {
            var self = this;

            if (!window.SqlWizardModal) {
                if (!confirm('Apagar relação ' + ctx.table + '.' + ctx.column + '?')) return;
                self.deleteRelation(ctx);
                return;
            }

            window.SqlWizardModal.confirm({
                title: 'Apagar relação',
                body:
                    '<p>Vais remover a relação:</p>' +
                    '<p><code>' + this.escape(ctx.table + '.' + ctx.column) + '</code> → ' +
                    '<code>' + this.escape(ctx.refTable + '.' + ctx.refColumn) + '</code></p>' +
                    '<p class="is-muted">Isto altera o draft <code>' + this.escape(ctx.table) + '</code>: ' +
                    'remove <code>:ref.' + this.escape(ctx.refTable) + '</code> do campo.</p>' +
                    '<p class="is-muted">A coluna <code>' + this.escape(ctx.column) + '</code> fica como ' +
                    '<code>int</code> simples (sem FK).</p>',
                confirmLabel: 'Apagar relação',
                cancelLabel: 'Cancelar',
                danger: true,
                onConfirm: function () {
                    self.deleteRelation(ctx);
                }
            });
        },

        deleteRelation: function (ctx) {
            var self = this;

            var explorer = window.SqlWizardExplorer;
            var draft = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === ctx.draftId) {
                        draft = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!draft) {
                if (window.SqlWizardToast) {
                    window.SqlWizardToast.error('Draft não encontrado');
                }
                return;
            }

            // Procurar e modificar o field
            var newFields = [];
            var changed = false;

            (draft.fields || []).forEach(function (f) {
                var s = String(f);
                var prefix = ctx.column + ':';

                if (s === ctx.column + ':ref.' + ctx.refTable) {
                    // Exato
                    newFields.push(ctx.column + ':int');
                    changed = true;
                    return;
                }

                if (s.startsWith(prefix)) {
                    // Verifica se tem :ref.refTable
                    var rest = s.substring(prefix.length);

                    // Remove a parte :ref.X
                    var newRest = rest.replace(/:ref\.\w+/, '');

                    if (newRest !== rest) {
                        // Normaliza: se ficou vazio ou começa com ?, adiciona int
                        if (newRest === '' || newRest.charAt(0) === '?' || newRest.charAt(0) === '=') {
                            newRest = 'int' + newRest;
                        }

                        newFields.push(ctx.column + ':' + newRest);
                        changed = true;
                        return;
                    }
                }

                newFields.push(s);
            });

            if (!changed) {
                // Não é explícito — só auto-detecção
                if (window.SqlWizardToast) {
                    window.SqlWizardToast.warning(
                        'Esta é uma relação automática (convenção). ' +
                        'O campo já é int — não há nada a remover do schema.',
                        { title: 'Relação automática', duration: 5000 }
                    );
                }

                // Mesmo assim, remove o tipo visual da memória
                if (window.SqlWizardCanvas) {
                    var key = ctx.draftId + ':' + ctx.column;
                    delete window.SqlWizardCanvas.edgeTypes[key];
                    window.SqlWizardCanvas.drawEdges();
                }
                return;
            }

            // Guardar via API
            window.SqlWizardApi.updateDraftFull(ctx.draftId, {
                table: draft.table,
                fields: newFields
            })
                .then(function (res) {
                    if (window.SqlWizardToast) {
                        window.SqlWizardToast.success('Relação removida do schema', { duration: 2000 });
                    }

                    // Limpar override de tipo
                    if (window.SqlWizardCanvas) {
                        var key = ctx.draftId + ':' + ctx.column;
                        delete window.SqlWizardCanvas.edgeTypes[key];
                    }

                    // Recarregar draft + canvas
                    if (window.SqlWizardExplorer) {
                        window.SqlWizardExplorer.load();
                    }

                    if (window.SqlWizardCanvas) {
                        var card = window.SqlWizardCanvas.el.querySelector('.card[data-id="' + ctx.draftId + '"]');
                        if (card && res.draft) {
                            // Re-render do cartão
                            window.SqlWizardCanvas.remove(ctx.draftId);
                            setTimeout(function () {
                                window.SqlWizardCanvas.add(res.draft);
                            }, 50);
                        }
                    }
                })
                .catch(function (err) {
                    if (window.SqlWizardToast) {
                        window.SqlWizardToast.error(err.message || 'Erro', {
                            title: 'Erro a apagar relação'
                        });
                    }
                });
        },



        /* ── Visualizar tabela em grande escala ──────────── */

        viewTable: function (ctx) {
            if (!window.SqlWizardTableZoom) {
                if (window.SqlWizardToast) SqlWizardToast.error('Módulo indisponível');
                return;
            }

            var explorer = window.SqlWizardExplorer;
            var draft = null;

            if (explorer && explorer.drafts) {
                for (var i = 0; i < explorer.drafts.length; i++) {
                    if (explorer.drafts[i].id === ctx.draftId) {
                        draft = explorer.drafts[i];
                        break;
                    }
                }
            }

            if (!draft) {
                if (window.SqlWizardToast) SqlWizardToast.error('Draft não encontrado');
                return;
            }

            var card = null;
            if (window.SqlWizardCanvas && window.SqlWizardCanvas.el) {
                card = window.SqlWizardCanvas.el.querySelector('.card[data-id="' + ctx.draftId + '"]');
            }

            window.SqlWizardTableZoom.open(draft, card);
        },

        escape: function (str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }


    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { App.init(); });
    } else {
        App.init();
    }




})(window, document);



/* ── Footer flutuante ────────────────────────────────────── */
(function () {
    'use strict';

    function initFooter() {
        var footer = document.getElementById('beaver-float-footer');
        if (!footer) {
            console.warn('[footer] elemento não encontrado');
            return;
        }

        // Se já foi escondido nesta sessão, esconde logo
        if (sessionStorage.getItem('bff_hidden') === '1') {
            footer.style.display = 'none';
            return;
        }

        // Delegação: apanha cliques em #bff-close mesmo que o botão mude
        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('#bff-close');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            console.log('[footer] a fechar…');

            footer.classList.add('is-hidden');
            sessionStorage.setItem('bff_hidden', '1');

            setTimeout(function () {
                footer.style.display = 'none';
            }, 450);
        });

        console.log('[footer] inicializado');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFooter);
    } else {
        initFooter();
    }
})();