/**
 * JUnit XML Viewer — Vista bonita
 * window.JUnitView.render(container, xmlString)
 */
(function (window, document) {
    // FK_LAZY_BILLBOARD - garante billboard.js carregado
    var _billboardPromise = null;
    function loadBillboard() {
        if (window.billboard || window.bb) return Promise.resolve(window.billboard || window.bb);
        if (_billboardPromise) return _billboardPromise;
        _billboardPromise = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/billboard.js@3.10.3/dist/billboard.pkgd.min.js';
            s.onload = function () { resolve(window.billboard || window.bb); };
            s.onerror = function () { reject(new Error('Falha ao carregar billboard.js')); };
            document.head.appendChild(s);
        });
        return _billboardPromise;
    }

    'use strict';

    function parseJUnit(xmlString) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(xmlString, 'application/xml');
        const err = doc.querySelector('parsererror');
        if (err) throw new Error('XML inválido: ' + err.textContent);

        const result = {
            testsuites: [],
            totals: { tests: 0, assertions: 0, errors: 0, failures: 0, skipped: 0, time: 0 }
        };

        const root = doc.documentElement;
        const topSuites = root.tagName === 'testsuites'
            ? root.querySelectorAll(':scope > testsuite')
            : [root];

        topSuites.forEach(ts => walkSuite(ts, result, []));
        return result;
    }

    function detectEngine(testcase) {
        var name  = String(testcase.name || '');
        var cls   = String(testcase.classname || '');
        if (/^(it\s|it_)/i.test(name)) return 'pest';
        if (/__pest_evaluable_/.test(name)) return 'pest';
        if (/^P\\/.test(cls)) return 'pest';
        return 'phpunit';
    }

function walkSuite(node, result, parentPath) {
        const name = node.getAttribute('name') || 'Sem nome';
        const path = parentPath.concat(name);
        const suite = {
            name: name,
            path: path.join(' › '),
            tests: parseInt(node.getAttribute('tests') || '0', 10),
            assertions: parseInt(node.getAttribute('assertions') || '0', 10),
            errors: parseInt(node.getAttribute('errors') || '0', 10),
            failures: parseInt(node.getAttribute('failures') || '0', 10),
            skipped: parseInt(node.getAttribute('skipped') || '0', 10),
            time: parseFloat(node.getAttribute('time') || '0'),
            children: [],
            testcases: []
        };

        node.querySelectorAll(':scope > testsuite').forEach(child => {
            suite.children.push(walkSuite(child, result, path));
        });

        node.querySelectorAll(':scope > testcase').forEach(tc => {
            const t = {
                name: tc.getAttribute('name') || 'Sem nome',
                engine: detectEngine(
                    tc.getAttribute('name') || '',
                    tc.getAttribute('classname') || ''
                ),
                engine: detectEngine({
                    name: tc.getAttribute('name') || '',
                    classname: tc.getAttribute('classname') || ''
                }),
                classname: tc.getAttribute('classname') || '',
                class: tc.getAttribute('class') || '',
                file: tc.getAttribute('file') || '',
                assertions: parseInt(tc.getAttribute('assertions') || '0', 10),
                time: parseFloat(tc.getAttribute('time') || '0'),
                status: 'pass',
                suite: name,
                suitePath: path.join(' › ')
            };
            if (tc.querySelector('failure')) {
                t.status = 'fail';
                const f = tc.querySelector('failure');
                t.message = f.getAttribute('message') || '';
                t.detail = f.textContent || '';
            } else if (tc.querySelector('error')) {
                t.status = 'fail';
                const e = tc.querySelector('error');
                t.message = e.getAttribute('message') || '';
                t.detail = e.textContent || '';
            } else if (tc.querySelector('skipped')) {
                t.status = 'skip';
            }
            suite.testcases.push(t);
            result.totals.tests += 1;
            result.totals.assertions += t.assertions;
            result.totals.time += t.time;
            if (t.status === 'fail') result.totals.failures += 1;
            if (t.status === 'skip') result.totals.skipped += 1;
        });

        result.testsuites.push(suite);
        return suite;
    }

    function fmtTime(s) {
        if (s < 0.001) return (s * 1000000).toFixed(0) + ' µs';
        if (s < 1) return (s * 1000).toFixed(1) + ' ms';
        return s.toFixed(2) + ' s';
    }
    function fmtNum(n) { return n.toLocaleString('pt-PT'); }
    function escapeHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function render(container, xmlString) {
        // FK_JUNIT_RENDER_CLEAN: limpar container antes de renderizar
        try { container.innerHTML = ''; } catch(_) {}
        const data = parseJUnit(xmlString);
        const allTests = [];
        data.testsuites.forEach(s => s.testcases.forEach(t => allTests.push(t)));

        const html = `
            <div class="junit-view">
                <div class="jv-header">
                    <h2><span class="jv-icon">🧪</span> Resultados dos Testes</h2>
                    <div class="jv-header-actions">
                        <span class="jv-engine-badge" id="jvEngineBadge" title="Distribuição por motor"></span>
                        <button class="jv-btn jv-btn-ghost" id="jvExportJson">📥 Exportar JSON</button>
                    </div>
                </div>

                <div class="jv-cards">
                    <div class="jv-card">
                        <div class="jv-card-icon">📋</div>
                        <div class="jv-card-label">Total Testes</div>
                        <div class="jv-card-value">${fmtNum(data.totals.tests)}</div>
                        <div class="jv-card-sub">${data.testsuites.length} suites</div>
                    </div>
                    <div class="jv-card jv-card-success">
                        <div class="jv-card-icon">✅</div>
                        <div class="jv-card-label">Passaram</div>
                        <div class="jv-card-value">${fmtNum(data.totals.tests - data.totals.failures - data.totals.skipped)}</div>
                        <div class="jv-card-sub">${((data.totals.tests - data.totals.failures - data.totals.skipped) / Math.max(data.totals.tests,1) * 100).toFixed(1)}% sucesso</div>
                    </div>
                    <div class="jv-card ${data.totals.failures > 0 ? 'jv-card-danger' : ''}">
                        <div class="jv-card-icon">${data.totals.failures > 0 ? '❌' : '✨'}</div>
                        <div class="jv-card-label">Falhas</div>
                        <div class="jv-card-value">${fmtNum(data.totals.failures)}</div>
                        <div class="jv-card-sub">${data.totals.errors} erros</div>
                    </div>
                    <div class="jv-card jv-card-warning">
                        <div class="jv-card-icon">⏱️</div>
                        <div class="jv-card-label">Duração</div>
                        <div class="jv-card-value">${fmtTime(data.totals.time)}</div>
                        <div class="jv-card-sub">${fmtNum(data.totals.assertions)} assertions</div>
                    </div>
                </div>

                <div class="jv-charts">
                    <div class="jv-chart-card">
                        <h3>⚡ Tempo por Suite</h3>
                        <div class="jv-chart" id="jvChartTime"></div>
                    </div>
                    <div class="jv-chart-card">
                        <h3>📊 Assertions por Suite</h3>
                        <div class="jv-chart" id="jvChartAssertions"></div>
                    </div>
                </div>

                <div class="jv-filters">
                    <input type="text" class="jv-filter-input" id="jvSearch" placeholder="🔍 Procurar teste...">
                    <button class="jv-filter-chip active" data-filter="all">Todos</button>
                    <button class="jv-filter-chip" data-filter="pass">✅ Pass</button>
                    <button class="jv-filter-chip" data-filter="fail">❌ Fail</button>
                    <button class="jv-filter-chip" data-filter="skip">⏭️ Skip</button>
                    <button class="jv-filter-chip" data-filter="pest">🐀 Pest</button>
                    <button class="jv-filter-chip" data-filter="phpunit">🐘 PHPUnit</button>
                </div>

                <div class="jv-table-card">
                    <table class="jv-table" id="jvTable">
                        <thead>
                            <tr>
                                <th data-sort="status">Estado <span class="jv-sort">↕</span></th>
                                <th data-sort="name">Teste <span class="jv-sort">↕</span></th>
                                <th data-sort="suite">Suite <span class="jv-sort">↕</span></th>
                                <th class="jv-col-actions">Ações</th>
                                <th data-sort="assertions" style="text-align:right">Assertions <span class="jv-sort">↕</span></th>
                                <th data-sort="time" style="text-align:right">Tempo <span class="jv-sort">↕</span></th>
                            </tr>
                        </thead>
                        <tbody id="jvTableBody"></tbody>
                    </table>
                </div>
            </div>
        `;
        container.innerHTML = html;

        const state = { tests: allTests, filter: 'all', search: '', sort: { field: 'time', dir: 'desc' } };

        function renderTable() {
            let rows = state.tests.slice();
            // FK_FILTER_FIX
            if (state.filter !== 'all') {
                if (state.filter === 'phpunit' || state.filter === 'pest') {
                    rows = rows.filter(t => t.engine === state.filter);
                } else {
                    rows = rows.filter(t => t.status === state.filter);
                }
            }
            if (state.search) {
                const q = state.search.toLowerCase();
                rows = rows.filter(t =>
                    t.name.toLowerCase().includes(q) ||
                    t.suite.toLowerCase().includes(q) ||
                    t.classname.toLowerCase().includes(q)
                );
            }
            rows.sort((a, b) => {
                const av = a[state.sort.field], bv = b[state.sort.field];
                if (typeof av === 'string') return state.sort.dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
                return state.sort.dir === 'asc' ? av - bv : bv - av;
            });

            const icons = { pass: '✅', fail: '❌', skip: '⏭️' };
            const labels = { pass: 'Pass', fail: 'Fail', skip: 'Skip' };
            document.getElementById('jvTableBody').innerHTML = rows.map((t, i) => `
                <tr data-index="${state.tests.indexOf(t)}">
                    <td><span class="jv-test-status ${t.status}">${icons[t.status]} ${labels[t.status]}</span></td>
                    <td>
                        <div class="jv-test-name">${t.engine === 'pest' ? '🐀' : '🐘'} ${escapeHtml(t.name)}</div>
                        <div class="jv-test-suite">${escapeHtml(t.classname)}</div>
                    </td>
                    <td>${escapeHtml(t.suite)}</td>
                    <td class="jv-col-actions">
                        <button type="button" class="jv-action-btn" data-view-test="${escapeHtml(t.classname)}" title="Ver código do teste">
                            👁️
                        </button>
                    </td>
                    <td style="text-align:right">${fmtNum(t.assertions)}</td>
                    <td style="text-align:right" class="jv-test-time">${fmtTime(t.time)}</td>
                </tr>
            `).join('') || '<tr><td colspan="5" class="jv-empty"><div class="jv-empty-icon">🔍</div><p>Nenhum teste encontrado</p></td></tr>';

            document.querySelectorAll('#jvTableBody tr[data-index]').forEach(tr => {
                tr.addEventListener('click', () => openModal(state.tests[parseInt(tr.dataset.index, 10)]));
            });
        }

        function renderCharts() {
            // FK_LAZY_BILLBOARD - so renderiza se billboard disponivel
            if (!window.billboard && !window.bb) {
                loadBillboard().then(function(){ renderCharts(); })
                    .catch(function(e){ console.warn('[junit-view] charts indisponiveis:', e.message); });
                return;
            }

            const timeEl = document.getElementById('jvChartTime');
            const assertEl = document.getElementById('jvChartAssertions');

            if (typeof bb === 'undefined') {
                console.warn('billboard.js não está carregado');
                if (timeEl) timeEl.innerHTML = '<div class="jv-chart-empty">⚠️ billboard.js não carregado</div>';
                if (assertEl) assertEl.innerHTML = '<div class="jv-chart-empty">⚠️ billboard.js não carregado</div>';
                return;
            }
            const topSuites = data.testsuites.slice().sort((a, b) => b.time - a.time).slice(0, 8);

            if (!topSuites.length) {
                if (timeEl) timeEl.innerHTML = '<div class="jv-chart-empty">Sem dados de suites</div>';
                if (assertEl) assertEl.innerHTML = '<div class="jv-chart-empty">Sem dados de suites</div>';
                return;
            }

            const names = topSuites.map(s => s.name.split('\\').pop());
            const times = topSuites.map(s => +(s.time * 1000).toFixed(2));
            const assertions = topSuites.map(s => s.assertions);

            bb.generate({
                bindto: '#jvChartTime',
                data: { columns: [['Tempo (ms)'].concat(times)], type: 'bar', colors: { 'Tempo (ms)': '#F5A623' } },
                axis: { x: { type: 'category', categories: names, tick: { rotate: -25, multiline: false } }, y: { label: 'ms' } },
                bar: { width: { ratio: 0.7 } }, legend: { show: false }, tooltip: { grouped: false }, padding: { bottom: 40 }
            });
            bb.generate({
                bindto: '#jvChartAssertions',
                data: { columns: [['Assertions'].concat(assertions)], type: 'bar', colors: { 'Assertions': '#2E9B5C' } },
                axis: { x: { type: 'category', categories: names, tick: { rotate: -25, multiline: false } }, y: { label: 'count' } },
                bar: { width: { ratio: 0.7 } }, legend: { show: false }, tooltip: { grouped: false }, padding: { bottom: 40 }
            });
        }

        function openModal(test) {
            const backdrop = document.createElement('div');
            backdrop.className = 'jv-modal-backdrop';
            const icons = { pass: '✅', fail: '❌', skip: '⏭️' };
            backdrop.innerHTML = `
                <div class="jv-modal">
                    <div class="jv-modal-head">
                        <div>
                            <h3>${icons[test.status]} ${escapeHtml(test.name)}</h3>
                            <div class="jv-modal-suite">${escapeHtml(test.suitePath || test.suite)}</div>
                        </div>
                        <button class="jv-modal-close" aria-label="Fechar">×</button>
                    </div>
                    <div class="jv-modal-body">
                        <div class="jv-modal-row"><span class="jv-modal-key">Estado</span><span class="jv-modal-value">${icons[test.status]} ${test.status}</span></div>
                        <div class="jv-modal-row"><span class="jv-modal-key">Classe</span><span class="jv-modal-value">${escapeHtml(test.classname)}</span></div>
                        <div class="jv-modal-row"><span class="jv-modal-key">Suite</span><span class="jv-modal-value">${escapeHtml(test.suite)}</span></div>
                        <div class="jv-modal-row"><span class="jv-modal-key">Assertions</span><span class="jv-modal-value">${fmtNum(test.assertions)}</span></div>
                        <div class="jv-modal-row"><span class="jv-modal-key">Duração</span><span class="jv-modal-value">${fmtTime(test.time)}</span></div>
                        ${test.message ? `<div class="jv-modal-row"><span class="jv-modal-key">Mensagem</span><span class="jv-modal-value">${escapeHtml(test.message)}</span></div>` : ''}
                        ${test.detail ? `<div style="margin-top:16px"><div class="jv-modal-key" style="margin-bottom:8px">Detalhes</div><pre style="background:#F4F5F7;padding:12px;border-radius:8px;overflow-x:auto;font-size:.82rem;line-height:1.5">${escapeHtml(test.detail)}</pre></div>` : ''}
                    </div>
                </div>
            `;
            document.body.appendChild(backdrop);
            const close = () => backdrop.remove();
            backdrop.querySelector('.jv-modal-close').addEventListener('click', close);
            backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
            document.addEventListener('keydown', function esc(e) {
                if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
            });
        }

        // FK_JUNIT_DELEGATION — pesquisa
    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'jvSearch') {
            state.search = e.target.value; renderTable();
        }
    });
        // FK_JUNIT_DELEGATION — chips de filtro
    document.addEventListener('click', function (e) {
        var chip = e.target.closest('.jv-filter-chip');
        if (!chip) return;
        document.querySelectorAll('.jv-filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                state.filter = chip.dataset.filter;
                renderTable();
    });
        document.querySelectorAll('#jvTable th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const field = th.dataset.sort;
                if (state.sort.field === field) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
                else { state.sort.field = field; state.sort.dir = 'asc'; }
                document.querySelectorAll('#jvTable th').forEach(t => t.classList.remove('jv-sorted'));
                th.classList.add('jv-sorted');
                renderTable();
            });
        });
        document.getElementById('jvExportJson').addEventListener('click', () => {
            const blob = new Blob([JSON.stringify({ totals: data.totals, tests: allTests }, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'junit-report-' + Date.now() + '.json';
            a.click();
            URL.revokeObjectURL(url);
        });

        // Badge do motor (Pest/PHPUnit)
        (function updateEngineBadge(){
            var badge = document.getElementById('jvEngineBadge');
            if (!badge) return;
            function setLabel(eng){
                if (eng === 'pest')    badge.textContent = '🐀 Pest';
                else if (eng === 'phpunit') badge.textContent = '🐘 PHPUnit';
                else if (eng === 'none')    badge.textContent = '⚠️ sem motor';
                else                        badge.textContent = '⏳ a detetar…';
            }
            setLabel(window.FK_TEST_ENGINE || null);
            if (!window.FK_TEST_ENGINE) {
                fetch('/api/frankei/terminal/metadata', { credentials: 'include' })
                  .then(function(r){ return r.json(); })
                  .then(function(d){
                      var cur = (d && d.test_engine && d.test_engine.current) || 'none';
                      window.FK_TEST_ENGINE = cur;
                      setLabel(cur);
                  })
                  .catch(function(){ setLabel('none'); });
            }
        })();

        // Badge: contagem Pest vs PHPUnit
        (function updateEngineBadge(){
            var badge = document.getElementById('jvEngineBadge');
            if (!badge) return;
            var pest = 0, phpunit = 0;
            allTests.forEach(function(t){
                if (t.engine === 'pest') pest++; else phpunit++;
            });
            if (!pest && !phpunit) {
                badge.textContent = '';
                badge.style.display = 'none';
                return;
            }
            badge.innerHTML = '🐀 ' + pest + ' Pest  ·  🐘 ' + phpunit + ' PHPUnit';
        })();

        renderTable();
        renderCharts();
        _bindViewTestButtons();
    }

    function _bindViewTestButtons() {
        document.querySelectorAll('.jv-action-btn[data-view-test]').forEach(function (btn) {
            if (btn.dataset.fkBound) return;
            btn.dataset.fkBound = '1';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                _openTestViewer(btn.dataset.viewTest);
            });
        });
    }

    function _openTestViewer(classname) {
        var backdrop = document.createElement('div');
        backdrop.className = 'jv-modal-backdrop';
        backdrop.innerHTML = `
            <div class="jv-modal jv-modal-wide">
                <div class="jv-modal-head">
                    <div>
                        <h3>✏️ ${escapeHtml(classname)}</h3>
                        <div class="jv-modal-suite" data-role="sub">a carregar…</div>
                    </div>
                    <button class="jv-modal-close" aria-label="Fechar">×</button>
                </div>
                <div class="jv-modal-body" style="padding:0">
                    <textarea class="jv-code jv-code-editor" data-role="code" spellcheck="false"></textarea>
                </div>
                <div class="jv-modal-foot" style="display:flex; gap:8px; padding:12px 16px; border-top:1px solid var(--jv-border); align-items:center">
                    <span class="jv-editor-status" data-role="status"></span>
                    <span style="flex:1"></span>
                    <button class="jv-btn jv-btn-ghost" data-role="cancel">Cancelar</button>
                    <button class="jv-btn jv-btn-primary" data-role="save">💾 Guardar</button>
                    <button class="jv-btn jv-btn-primary" data-role="save-run">▶ Guardar e correr</button>
                </div>
            </div>
        `;
        document.body.appendChild(backdrop);

        var ta   = backdrop.querySelector('[data-role=code]');
        var sub  = backdrop.querySelector('[data-role=sub]');
        var stat = backdrop.querySelector('[data-role=status]');

        var close = function () { backdrop.remove(); };
        backdrop.querySelector('.jv-modal-close').addEventListener('click', close);
        backdrop.querySelector('[data-role=cancel]').addEventListener('click', close);
        backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
        });

        function setStatus(txt, ok) {
            if (!stat) return;
            stat.textContent = txt || '';
            stat.style.color = ok === false ? '#dc2626' : (ok === true ? '#16a34a' : 'var(--jv-text-soft)');
        }

        function loadContent() {
            setStatus('a carregar…');
            fetch('/api/frankei/test-file?class=' + encodeURIComponent(classname),
                  { credentials: 'include' })
              .then(function (r) { return r.json(); })
              .then(function (d) {
                  if (!d.ok) { setStatus('❌ ' + (d.error || 'erro'), false); return; }
                  if (sub) sub.textContent = d.file + '  ·  ' + d.size + ' bytes';
                  ta.value = d.content;
                  setStatus('');
              })
              .catch(function (e) { setStatus('❌ ' + e.message, false); });
        }

        function save(run) {
            var content = ta.value;
            setStatus('a gravar…');
            fetch('/api/frankei/test-file', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ class: classname, content: content })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { setStatus('❌ ' + (d.error || 'erro'), false); return; }
                setStatus('✓ gravado (' + d.bytes + ' bytes · backup ' + d.backup + ')', true);
                if (run && typeof window.execTermCommand === 'function') {
                    var shortName = classname.split('\\').pop().replace('Test','');
                    window.execTermCommand('beaver test --filter=' + shortName);
                    setStatus('✓ gravado — a correr…', true);
                    setTimeout(close, 400);
                }
            })
            .catch(function (e) { setStatus('❌ ' + e.message, false); });
        }

        backdrop.querySelector('[data-role=save]').addEventListener('click', function(){ save(false); });
        backdrop.querySelector('[data-role=save-run]').addEventListener('click', function(){ save(true); });

        loadContent();
    }

    window.JUnitView = { render: render, parse: parseJUnit };
})(window, document);
