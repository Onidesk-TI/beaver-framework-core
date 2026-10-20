<!DOCTYPE html>
<html lang="pt" data-theme="amber">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frankei — Configurar aplicação</title>
    <link rel="stylesheet" href="/plugins/frankei/css/setup.css?v=<?= time() ?>">
</head>

<body>

    <div class="fk-setup-wrap">

        <header class="fk-setup-top">
            <a href="/" class="fk-setup-brand">
                <span class="fk-setup-logo">
                    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="bvgBrand" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#FFD79A" />
                                <stop offset="48%" stop-color="#FF9A3C" />
                                <stop offset="100%" stop-color="#FF6B35" />
                            </linearGradient>
                        </defs>
                        <circle cx="13" cy="17.5" r="7.5" fill="url(#bvgBrand)" opacity=".72" />
                        <circle cx="51" cy="17.5" r="7.5" fill="url(#bvgBrand)" opacity=".72" />
                        <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvgBrand)" />
                        <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".2" />
                        <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208" />
                        <circle cx="22.5" cy="26" r="3.4" fill="#1A1208" />
                        <circle cx="41.5" cy="26" r="3.4" fill="#1A1208" />
                        <circle cx="23.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85" />
                        <circle cx="42.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85" />
                        <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8" />
                        <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8" />
                    </svg>
                </span>
                <span><b>Beaver</b> · Frankei</span>
            </a>
            <div class="fk-setup-user">
                <?php if (!empty($user['nome'])): ?>
                    <span>Olá, <b><?= htmlspecialchars($user['nome']) ?></b></span>
                <?php endif; ?>
                <a href="/frankei/logout" class="fk-link-logout">Sair</a>
            </div>
        </header>

        <main class="fk-setup-main">

            <div class="fk-steps">
                <span class="fk-step fk-step-done">1 · Conta</span>
                <span class="fk-step-line"></span>
                <span class="fk-step fk-step-active">2 · Aplicação</span>
                <span class="fk-step-line"></span>
                <span class="fk-step">3 · Análise</span>
            </div>

            <div class="fk-hero-img">
                <img src="/plugins/frankei/img/frankei_splash.png" alt="Frankei — análise de segurança" loading="eager">
            </div>

            <div class="fk-seal">
                <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="bvgSeal" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FFD79A" />
                            <stop offset="48%" stop-color="#FF9A3C" />
                            <stop offset="100%" stop-color="#FF6B35" />
                        </linearGradient>
                    </defs>
                    <circle cx="13" cy="17.5" r="7.5" fill="url(#bvgSeal)" opacity=".72" />
                    <circle cx="51" cy="17.5" r="7.5" fill="url(#bvgSeal)" opacity=".72" />
                    <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvgSeal)" />
                    <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".2" />
                    <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208" />
                    <circle cx="22.5" cy="26" r="3.4" fill="#1A1208" />
                    <circle cx="41.5" cy="26" r="3.4" fill="#1A1208" />
                    <circle cx="23.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85" />
                    <circle cx="42.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85" />
                    <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8" />
                    <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8" />
                </svg>
            </div>

            <h1 class="fk-setup-title">Onde está o teu projeto?</h1>
            <p class="fk-setup-sub">
                Indica o caminho absoluto da aplicação PHP que queres analisar.
                O Frankei vai ler os ficheiros localmente.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="fk-alert fk-alert-error"><?= htmlspecialchars($errors) ?></div>
            <?php endif; ?>


            <div class="fk-free-banner" id="fkFreeBanner">
                <span class="fk-free-ico">⚡</span>
                <span class="fk-free-text">
                    <b>Versão grátis</b> — máximo <b>25 ficheiros PHP</b> por projeto.<br>
                    <small>Seleciona abaixo os que queres testar (por defeito, os primeiros 25).</small>
                </span>
            </div>

            <?php
// ═══════════ Determinar estado do user ═══════════
$gateState   = 'anon';
$gateMessage = '';
$gateKind    = '';       // 'info' | 'warn' | 'danger'
$daysLeft    = 0;

if (\App\Services\UserService::autenticado()) {
    $u = \App\Services\UserService::atual();
    if ($u) {
        $rows = \App\Services\SiteDB::select(
            'SELECT trial_started_at, trial_expires_at, trial_used FROM users WHERE id = :id',
            [':id' => (int) $u['id']]
        );
        $r = $rows[0] ?? null;

        if ($r) {
            if (!empty($r['trial_used'])) {
                $gateState   = 'used';
                $gateKind    = 'danger';
                $gateMessage = '<b>Já completaste o trial gratuito.</b> Esta conta já usou os 8 dias de acesso grátis. Obrigado!';
            } elseif (!empty($r['trial_started_at']) && (int) $r['trial_expires_at'] > time()) {
                $gateState   = 'active';
                $gateKind    = 'info';
                $secs        = (int) $r['trial_expires_at'] - time();
                $daysLeft    = (int) floor($secs / 86400);
                $hoursLeft   = (int) floor(($secs % 86400) / 3600);
                $gateMessage = '<b>Trial ativo.</b> Faltam <b>' . $daysLeft . ' dias e ' . $hoursLeft . ' horas</b>.';
            } elseif (!empty($r['trial_started_at']) && (int) $r['trial_expires_at'] <= time()) {
                $gateState   = 'expired';
                $gateKind    = 'warn';
                $expDate     = date('d/m/Y H:i', (int) $r['trial_expires_at']);
                $gateMessage = '<b>Trial expirado</b> em ' . $expDate . '. Pede novo acesso.';
            } else {
                $gateState = 'no-trial';
            }
        }
    }
}
?>

        <form class="fk-form" id="fkFormTrial" novalidate>
            <div class="fk-alert fk-alert-error" id="fkErro" hidden></div>
            <div class="fk-alert fk-alert-ok" id="fkOk" hidden></div>

            <?php if ($gateMessage !== ''): ?>
            <div class="fk-gate-banner fk-gate-<?= htmlspecialchars($gateKind) ?>">
                <span class="fk-gate-icon"><?= $gateKind === 'danger' ? '🔒' : ($gateKind === 'warn' ? '⏰' : '✓') ?></span>
                <div class="fk-gate-text"><?= $gateMessage ?></div>
            </div>
        <?php endif; ?>

        


                <label class="fk-label" for="fkPath">Caminho da aplicação</label>

                <div class="fk-input-group">
                    <span class="fk-input-icon">📁</span>
                    <input
                        type="text"
                        id="fkPath"
                        name="path"
                        class="fk-input"
                        placeholder="/var/www/beaver-project"
                        value="<?= htmlspecialchars($old ?? '') ?>"
                        autocomplete="off"
                        spellcheck="false"
                        autofocus>
                    <span class="fk-input-status" id="fkStatus"></span>
                </div>


                <div class="fk-files-wrap" id="fkFilesWrap" hidden>
                    <div class="fk-files-head">
                        <span class="fk-files-title">Ficheiros PHP detetados</span>
                        <div class="fk-files-actions">
                            <button type="button" class="fk-mini-btn" id="fkSelectAll">Todos</button>
                            <button type="button" class="fk-mini-btn" id="fkSelectNone">Nenhum</button>
                            <span class="fk-files-count" id="fkFilesCount"></span>
                        </div>
                    </div>
                    <div class="fk-files-list" id="fkFilesList"></div>
                    <div class="fk-files-limit" id="fkFilesLimit"></div>
                </div>

                <div class="fk-tech-section">
                    <label class="fk-label">Tecnologia principal</label>
                    <div class="fk-radio-row" id="fkTechPrincipal">
                        <label class="fk-radio"><input type="radio" name="tech_main" value="php" checked><span>PHP</span></label>
                        <label class="fk-radio"><input type="radio" name="tech_main" value="js"><span>JavaScript</span></label>
                        <label class="fk-radio"><input type="radio" name="tech_main" value="python"><span>Python</span></label>
                        <label class="fk-radio"><input type="radio" name="tech_main" value="other"><span>Outro</span></label>
                    </div>
                </div>

                <div class="fk-tech-section">
                    <label class="fk-label">Outras tecnologias usadas</label>
                    <div class="fk-checkbox-grid" id="fkTechOutras">
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="css"><span>CSS</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="html"><span>HTML</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="sql"><span>SQL</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="ts"><span>TypeScript</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="vue"><span>Vue</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="react"><span>React</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="node"><span>Node.js</span></label>
                        <label class="fk-checkbox"><input type="checkbox" name="tech[]" value="bash"><span>Bash</span></label>
                    </div>
                </div>

                <div class="fk-checks" id="fkChecks" aria-live="polite">
                    <!-- preenchido pelo JS -->
                </div>

                            <?php if (!\App\Services\UserService::autenticado()): ?>
            <!-- ═══════ DADOS PESSOAIS ═══════ -->
            <div class="fk-tech-section">
                <label class="fk-label" for="fkNome">O teu nome</label>
                <div class="fk-input-group">
                    <span class="fk-input-icon">👤</span>
                    <input type="text" id="fkNome" name="nome" class="fk-input" placeholder="Ex.: João Silva" required autocomplete="name">
                </div>
            </div>

            <div class="fk-tech-section">
                <label class="fk-label" for="fkEmail">O teu email</label>
                <div class="fk-input-group">
                    <span class="fk-input-icon">✉️</span>
                    <input type="email" id="fkEmail" name="email" class="fk-input" placeholder="tu@empresa.com" required autocomplete="email">
                </div>
                <div class="fk-hint">É para aqui que enviamos a chave de acesso.</div>
            </div>

            <div class="fk-tech-section">
                <label class="fk-label" for="fkPhone">Teu telemóvel</label>
                <div class="fk-input-group">
                    <span class="fk-input-icon">📱</span>
                    <input type="tel" id="fkPhone" name="phone" class="fk-input" placeholder="+351 912 345 678" required autocomplete="tel">
                </div>
                <div class="fk-hint">Formato internacional: +351, +34, +55, etc.</div>
            </div>

            <?php endif; ?>

            <div class="fk-privacy">
                    <span class="fk-privacy-ico">🔒</span>
                    <div>
                        <b>A tua privacidade</b>
                        <p>
                            O Frankei analisa o teu código <b>localmente</b>.
                            Não copiamos, não enviamos e não armazenamos o teu código-fonte.
                            Guardamos apenas:
                        </p>
                        <ul>
                            <li>O caminho da aplicação</li>
                            <li>A estrutura de pastas (nomes e contagem)</li>
                            <li>Os problemas encontrados (findings agregados)</li>
                        </ul>
                    </div>
                </div>

                <div class="fk-free-note">
                    <span>⚡</span>
                    <span><b>Plano Free:</b> 1 análise apenas. Não poderás trocar de aplicação depois.</span>
                </div>

                <div class="fk-actions">
                    <a href="/frankei" class="fk-btn fk-btn-ghost">Cancelar</a>

                    <button type="button" class="fk-btn fk-btn-ghost" id="fkTrialBtn">
                        ⏱️ Testar 30 min (sem conta)
                    </button>

                    <button type="submit" class="fk-btn fk-btn-primary" id="fkSubmitBtn">
                        Criar conta grátis (8 dias)
                        <span class="fk-btn-arrow">→</span>
                    </button>
                </div>

            </form>

        </main>

        <footer class="fk-setup-foot">
            <span>Frankei · Beaver Framework</span>
            <span>·</span>
            <span>Segurança local</span>
        </footer>

    </div>

    <script>
        window.FK_USER_ID = <?= (int)($user['id'] ?? 0) ?>;
    </script>
    <script src="/plugins/frankei/js/path-files.js?v=<?= time() ?>"></script>

    <!-- ═══════════ DEMO CTA ═══════════ -->
    <button type="button" class="fk-demo-btn" id="fkDemoBtn">
        <span class="fk-demo-ico">✨</span>
        <span class="fk-demo-label">Solicitar demo</span>
    </button>

    <div class="fk-demo-overlay" id="fkDemoOverlay">
        <div class="fk-demo-modal">
            <button type="button" class="fk-demo-close" id="fkDemoClose" aria-label="Fechar">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>

            <div class="fk-demo-head">
                <div class="fk-demo-icon">🦫</div>
                <h3>Solicitar demo</h3>
                <p>Diz-nos o teu email e telefone. Recebe uma password de acesso GRÁTIS.</p>
            </div>

            <form id="fkDemoForm" class="fk-demo-form" novalidate>
                <div class="fk-alert fk-alert-error" id="fkDemoErro" hidden></div>
                <div class="fk-alert fk-alert-ok" id="fkDemoOk" hidden></div>

                <label class="fk-label" for="fkDemoEmail">Email</label>
                <div class="fk-input-group">
                    <span class="fk-input-icon">✉️</span>
                    <input type="email" id="fkDemoEmail" name="demo_email" class="fk-input" placeholder="tu@empresa.com" required>
                </div>

                <label class="fk-label" for="fkDemoPhone" style="margin-top:14px;">Telemóvel</label>
                <div class="fk-input-group">
                    <span class="fk-input-icon">📱</span>
                    <input type="tel" id="fkDemoPhone" name="demo_phone" class="fk-input" placeholder="+351 912 345 678" required>
                </div>

                <button type="submit" class="fk-btn fk-btn-primary fk-demo-submit" id="fkDemoSubmit">
                    Solicitar demo
                    <span class="fk-btn-arrow">→</span>
                </button>
            </form>

            <p class="fk-demo-foot">Não partilhamos os teus dados com terceiros.</p>
        </div>
    </div>

    <script>
        (function() {
            var btn = document.getElementById('fkDemoBtn');
            var overlay = document.getElementById('fkDemoOverlay');
            var close = document.getElementById('fkDemoClose');
            var form = document.getElementById('fkDemoForm');
            var submit = document.getElementById('fkDemoSubmit');
            var errEl = document.getElementById('fkDemoErro');
            var okEl = document.getElementById('fkDemoOk');

            if (!btn || !overlay || !form) return;

            function open() {
                overlay.classList.add('open');
                setTimeout(function() {
                    document.getElementById('fkDemoEmail').focus();
                }, 200);
            }

            function closeM() {
                overlay.classList.remove('open');
            }

            btn.addEventListener('click', open);
            close.addEventListener('click', closeM);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeM();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeM();
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                errEl.hidden = true;
                okEl.hidden = true;
                submit.disabled = true;
                submit.innerHTML = 'A enviar... <span class="fk-btn-arrow">⏳</span>';

                var fd = new FormData(form);

                fetch('/api/frankei/request-demo', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.ok) {
                            // Se o backend pediu redirect → navega
                            if (data.redirect) {
                                okEl.hidden = false;
                                okEl.innerHTML = '✅ ' + (data.mensagem || 'A entrar...');
                                setTimeout(function () {
                                    window.location.href = data.redirect;
                                }, 800);
                                return;
                            }

                            okEl.hidden = false;
                            okEl.textContent = data.mensagem || 'Pedido recebido. Entramos em contacto em breve.';
                            form.reset();
                            submit.innerHTML = 'Enviar novamente <span class="fk-btn-arrow">→</span>';
                            submit.disabled = false;
                            setTimeout(closeM, 3500);
                        } else {
                            errEl.hidden = false;
                            errEl.textContent = data.erro || 'Erro desconhecido.';
                            submit.innerHTML = 'Tentar de novo <span class="fk-btn-arrow">→</span>';
                            submit.disabled = false;
                        }
                    })
                    .catch(function(err) {
                        errEl.hidden = false;
                        errEl.textContent = 'Erro de rede: ' + err.message;
                        submit.innerHTML = 'Tentar de novo <span class="fk-btn-arrow">→</span>';
                        submit.disabled = false;
                    });
            });
        })();
    </script>


<script>
/* ════════ Form principal — pedir chave por email ════════ */
(function () {
    var form  = document.getElementById('fkFormTrial');
    var btn   = document.getElementById('fkSubmitBtn');
    var errEl = document.getElementById('fkErro') || {hidden:true,textContent:'',innerHTML:''};
    var okEl  = document.getElementById('fkOk') || {hidden:true,textContent:'',innerHTML:''};

    if (!form) return;


})();
</script>




<script>
/* ════════ Gate por estado ════════ */
(function () {
    var state     = <?= json_encode($gateState) ?>;
    var submitBtn = document.getElementById('fkSubmitBtn');
    var overlay   = document.getElementById('fkDemoOverlay');
    var demoBtn   = document.getElementById('fkDemoBtn');

    if (!submitBtn) return;

    function abrirDemo() {
        if (overlay && overlay.classList.contains('open')) return;
        if (demoBtn) demoBtn.click();
    }

    /* ── Estado ATIVO → botão normal, submete ── */
    if (state === 'active' || state === 'no-trial') {
        submitBtn.disabled = false;
        submitBtn.classList.remove('is-blocked');

        if (state === 'active') {
            submitBtn.innerHTML = 'Iniciar análise <span class="fk-btn-arrow">→</span>';
            submitBtn.setAttribute('title', 'Guardar path e abrir Frankei');
        }
        return;
    }

    /* ── Estado USADO / EXPIRADO → botão bloqueado com mensagem ── */
    if (state === 'used' || state === 'expired') {
        submitBtn.disabled = true;
        submitBtn.classList.add('is-blocked');
        submitBtn.style.cursor = 'not-allowed';

        if (state === 'used') {
            submitBtn.innerHTML = '🔒 Já completaste o trial';
        } else {
            submitBtn.innerHTML = '🔒 Trial expirado';
        }

        // Bloquear todos os inputs do form
        document.querySelectorAll('#fkFormTrial input, #fkFormTrial button[type="submit"]').forEach(function (el) {
            el.disabled = true;
            el.style.opacity = '.6';
        });
        return;
    }

    /* ── Estado ANÓNIMO → hover abre modal demo ── */
    submitBtn.classList.add('is-blocked');
    submitBtn.setAttribute('title', 'Precisas de acesso. Passa o rato para pedir demo.');

    submitBtn.addEventListener('mouseenter', abrirDemo);
    submitBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        abrirDemo();
    });
    submitBtn.addEventListener('focus', abrirDemo);
})();
</script>

<!-- ═══════════ MODAL DE MENSAGEM ═══════════ -->
<div class="fk-msg-overlay" id="fkMsgOverlay" hidden>
    <div class="fk-msg-modal" id="fkMsgModal">
        <button type="button" class="fk-msg-close" id="fkMsgClose" aria-label="Fechar">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <div class="fk-msg-icon" id="fkMsgIcon">ℹ️</div>
        <h3 class="fk-msg-title" id="fkMsgTitle">Aviso</h3>
        <p class="fk-msg-text" id="fkMsgText"></p>
        <button type="button" class="fk-btn fk-btn-primary fk-msg-ok" id="fkMsgOk">Entendido</button>
    </div>
</div>


<script>
/* ════════ Modal de mensagem ════════ */
window.fkShowMessage = function (title, text, kind) {
    var overlay = document.getElementById('fkMsgOverlay');
    var icon    = document.getElementById('fkMsgIcon');
    var titleEl = document.getElementById('fkMsgTitle');
    var textEl  = document.getElementById('fkMsgText');

    if (!overlay) return;

    var icons = { ok: '✅', error: '❌', warn: '⚠️', info: 'ℹ️' };
    icon.textContent    = icons[kind] || icons.info;
    titleEl.textContent = title;
    textEl.innerHTML    = text;

    overlay.hidden = false;
    overlay.classList.add('open');

    // Fechar com ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fkCloseMessage();
    }, { once: true });
};

window.fkCloseMessage = function () {
    var overlay = document.getElementById('fkMsgOverlay');
    if (overlay) {
        overlay.classList.remove('open');
        overlay.hidden = true;
    }
};

(function () {
    var overlay = document.getElementById('fkMsgOverlay');
    var closeBtn = document.getElementById('fkMsgClose');
    var okBtn   = document.getElementById('fkMsgOk');

    if (!overlay) return;

    closeBtn && closeBtn.addEventListener('click', fkCloseMessage);
    okBtn && okBtn.addEventListener('click', fkCloseMessage);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) fkCloseMessage();
    });
})();

/* ════════ Interceptar o submit do form ════════ */
(function () {
    var form = document.getElementById('fkFormTrial');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        // Só mostrar modal se o handler principal não tratou
        // (não corremos preventDefault aqui — deixamos o handler principal agir)
    }, true);  // capture=true → corre primeiro

    // Interceptar XHR/fetch para mostrar erros de forma destacada
    var _fetch = window.fetch;
    window.fetch = function (url, opts) {
        var promise = _fetch.apply(this, arguments);
        if (typeof url === 'string' && (url.includes('save-setup') || url.includes('request-trial'))) {
            promise.then(function (response) {
                var clone = response.clone();
                clone.json().then(function (data) {
                    if (data && data.ok === false) {
                        window.fkShowMessage(
                            'Erro',
                            data.erro || data.mensagem || 'Ocorreu um erro.',
                            'error'
                        );
                    } else if (data && data.ok === true && !data.redirect && !data.email_enviado && !data.password) {
                        window.fkShowMessage(
                            'Sucesso',
                            data.mensagem || 'Pedido recebido.',
                            'ok'
                        );
                    }
                }).catch(function () { /* não é JSON */ });
            }).catch(function (err) {
                window.fkShowMessage('Erro de rede', err.message, 'error');
            });
        }
        return promise;
    };
})();
</script>


<script>
/* ════════ Form principal — submit unificado ════════ */
(function () {
    var form = document.getElementById('fkFormTrial');
    var btn  = document.getElementById('fkSubmitBtn');
    if (!form || !btn) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Verificar se o botão está bloqueado
        if (btn.classList.contains('is-blocked') || btn.disabled) {
            return;
        }

        btn.disabled = true;
        var originalText = btn.innerHTML;
        btn.innerHTML = 'A enviar... <span class="fk-btn-arrow">⏳</span>';

        var fd = new FormData(form);

        var endpoint = <?= json_encode(\App\Services\UserService::autenticado()) ?>
            ? '/api/frankei/save-setup'
            : '/api/frankei/request-trial';

        fetch(endpoint, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.ok && data.email_enviado) {
                window.fkShowMessage && window.fkShowMessage(
                    'Chave enviada por email',
                    'Verifica a caixa de entrada de <b>' + (data.email || '') + '</b>.<br>A chave tem 24h para ser usada.',
                    'ok'
                );
            } else if (data.ok && data.password) {
                window.fkShowMessage && window.fkShowMessage(
                    'Email falhou',
                    'A tua chave:<br><code style="display:inline-block;font-size:1.15rem;padding:8px 14px;margin:10px 0;background:#FFF7E8;color:#7A3F00;border-radius:8px;letter-spacing:.06em;font-weight:700;">' + data.password + '</code>',
                    'warn'
                );
            } else if (!data.ok) {
                window.fkShowMessage && window.fkShowMessage(
                    'Erro',
                    data.erro || data.mensagem || 'Erro desconhecido.',
                    'error'
                );
            } else {
                window.fkShowMessage && window.fkShowMessage(
                    'Sucesso',
                    data.mensagem || 'Pedido recebido.',
                    'ok'
                );
            }
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(function (err) {
            window.fkShowMessage && window.fkShowMessage('Erro de rede', err.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
})();
</script>


<!-- ═══════════ MODAL DE CÓDIGO (trial 30min) ═══════════ -->
<div class="fk-msg-overlay" id="fkCodeOverlay" hidden>
    <div class="fk-msg-modal">
        <button type="button" class="fk-msg-close" id="fkCodeClose" aria-label="Fechar">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>

        <div class="fk-msg-icon" id="fkCodeIcon">📩</div>
        <h3 class="fk-msg-title" id="fkCodeTitle">Verifica o teu email</h3>
        <p class="fk-msg-text" id="fkCodeText">
            Enviámos um código de 6 dígitos para o teu email.
            Insere-o abaixo para começar os 30 minutos.
        </p>

        <div class="fk-code-input-wrap">
            <input type="text" id="fkCodeInput" class="fk-code-input"
                   placeholder="000000" maxlength="6" inputmode="numeric"
                   autocomplete="one-time-code">
        </div>

        <div class="fk-code-actions">
            <button type="button" class="fk-btn fk-btn-ghost" id="fkCodeCancel">Cancelar</button>
            <button type="button" class="fk-btn fk-btn-primary" id="fkCodeVerify">
                Confirmar e começar
                <span class="fk-btn-arrow">→</span>
            </button>
        </div>

        <div class="fk-msg-text" id="fkCodeError" style="color:#991B1B;margin-top:14px;display:none;"></div>
    </div>
</div>


<script>
/* ════════ Botão "Testar 30 min" ════════ */
(function () {
    var btn = document.getElementById('fkTrialBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var isAuthed = <?= json_encode(\App\Services\UserService::autenticado()) ?>;
        if (isAuthed) {
            window.fkShowMessage('Sessão ativa', 'Já estás autenticado. Usa "Criar conta grátis".', 'info');
            return;
        }

        var email = document.getElementById('fkEmail')?.value || '';
        var nome  = document.getElementById('fkNome')?.value  || '';
        var phone = document.getElementById('fkPhone')?.value || '';

        if (!email || !email.includes('@')) {
            window.fkShowMessage('Email necessário', 'Precisas de indicar um email válido.', 'error');
            document.getElementById('fkEmail')?.focus();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = 'A enviar código... ⏳';

        var fd = new FormData();
        fd.append('email', email);
        fd.append('nome',  nome);
        fd.append('phone', phone);

        fetch('/api/frankei/trial/start', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btn.disabled = false;
            btn.innerHTML = '⏱️ Testar 30 min (sem conta)';

            if (!data.ok) {
                window.fkShowMessage('Erro', data.erro || 'Erro desconhecido.', 'error');
                return;
            }

            window.__fkTrialToken = data.token;
            document.getElementById('fkCodeOverlay').hidden = false;
            document.getElementById('fkCodeOverlay').classList.add('open');
            document.getElementById('fkCodeError').style.display = 'none';
            document.getElementById('fkCodeInput').value = '';
            setTimeout(function () { document.getElementById('fkCodeInput').focus(); }, 200);

            if (!data.enviado && data.code) {
                document.getElementById('fkCodeText').innerHTML =
                    'Email falhou. O teu código é:<br><b style="font-size:1.4rem;letter-spacing:.1em;">' + data.code + '</b>';
            } else {
                document.getElementById('fkCodeText').innerHTML =
                    'Enviámos um código de 6 dígitos para <b>' + data.email + '</b>.<br>Verifica o spam se não aparecer.';
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '⏱️ Testar 30 min (sem conta)';
            window.fkShowMessage('Erro de rede', err.message, 'error');
        });
    });

    /* ── Fechar modal ── */
    var closeBtn  = document.getElementById('fkCodeClose');
    var cancelBtn = document.getElementById('fkCodeCancel');
    var overlay   = document.getElementById('fkCodeOverlay');

    function fechar() {
        overlay.classList.remove('open');
        overlay.hidden = true;
    }
    closeBtn  && closeBtn.addEventListener('click', fechar);
    cancelBtn && cancelBtn.addEventListener('click', fechar);
    overlay   && overlay.addEventListener('click', function (e) {
        if (e.target === overlay) fechar();
    });

    /* ── Input: só dígitos ── */
    var codeInput = document.getElementById('fkCodeInput');
    codeInput && codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length === 6) {
            document.getElementById('fkCodeVerify').click();
        }
    });

    /* ── Confirmar código ── */
    var verifyBtn = document.getElementById('fkCodeVerify');
    verifyBtn && verifyBtn.addEventListener('click', function () {
        var code  = document.getElementById('fkCodeInput').value.trim();
        var token = window.__fkTrialToken || '';
        var errEl = document.getElementById('fkCodeError');

        if (code.length !== 6) {
            errEl.textContent = 'O código tem 6 dígitos.';
            errEl.style.display = 'block';
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = 'A verificar... ⏳';

        var fd = new FormData();
        fd.append('token', token);
        fd.append('code',  code);

        fetch('/api/frankei/trial/verify', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = 'Confirmar e começar <span class="fk-btn-arrow">→</span>';

            if (data.ok) {
                window.location.href = data.redirect || '/frankei';
            } else {
                errEl.textContent = data.erro || 'Código inválido.';
                errEl.style.display = 'block';
            }
        })
        .catch(function (err) {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = 'Confirmar e começar <span class="fk-btn-arrow">→</span>';
            errEl.textContent = 'Erro de rede: ' + err.message;
            errEl.style.display = 'block';
        });
    });
})();
</script>

</body>

</html>