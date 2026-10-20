<!DOCTYPE html>
<html lang="pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Frankei — Plano Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    :root {
      --amber: #F5A623;
      --amber-2: #E07800;
      --amber-3: #FFC46B;
      --amber-deep: #7A3F00;
      --amber-soft: #FFF4E0;
      --text: #2B2F36;
      --soft: #5F656D;
      --muted: #8A9098;
      --bg: #FBF8F2;
      --line: #EDE6DA;
      --line-2: #E4D9C4;
      --purple: #7C3AED\
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.65;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      z-index: -1;
      background:
        radial-gradient(900px 500px at 12% -8%, rgba(245, 166, 35, .18), transparent 62%),
        radial-gradient(760px 460px at 88% 4%, rgba(124, 58, 237, .08), transparent 60%)
    }

    .back {
      position: fixed;
      top: 24px;
      left: 24px;
      padding: 8px 14px;
      border-radius: 9px;
      background: rgba(255, 255, 255, .9);
      border: 1px solid var(--line);
      color: var(--soft);
      text-decoration: none;
      font-size: .82rem;
      font-weight: 600;
      backdrop-filter: blur(10px);
      transition: all .15s;
      z-index: 100;
      box-shadow: 0 4px 12px -6px rgba(122, 63, 0, .12)
    }

    .back:hover {
      color: var(--amber-2);
      border-color: var(--amber);
      box-shadow: 0 6px 16px -6px rgba(224, 120, 0, .25)
    }

    .layout {
      max-width: 1280px;
      margin: 0 auto;
      padding: 60px 24px 80px;
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 48px
    }

    @media(max-width:960px) {
      .layout {
        grid-template-columns: 1fr;
        gap: 32px;
        padding-top: 80px
      }
    }

    /* ── Sidebar de navegação ── */
    .nav {
      position: sticky;
      top: 32px;
      align-self: start
    }

    @media(max-width:960px) {
      .nav {
        position: static
      }
    }

    .nav-title {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .16em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px
    }

    .nav-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 2px
    }

    @media(max-width:960px) {
      .nav-list {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 6px
      }
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 9px;
      font-size: .85rem;
      color: var(--soft);
      text-decoration: none;
      transition: all .15s;
      border: 1px solid transparent
    }

    .nav-link:hover {
      background: var(--amber-soft);
      color: var(--amber-deep);
      border-color: rgba(245, 166, 35, .25)
    }

    .nav-link .ico {
      font-size: 1rem;
      flex: none
    }

    .nav-link .lbl {
      flex: 1
    }

    .nav-link .cnt {
      font-size: .68rem;
      color: var(--muted);
      background: rgba(43, 47, 54, .06);
      padding: 1px 6px;
      border-radius: 5px;
      font-family: 'JetBrains Mono', monospace;
      font-weight: 600
    }

    .nav-link.pro-flag .cnt {
      background: rgba(124, 58, 237, .12);
      color: var(--purple)
    }

    .nav-link.prem-flag .cnt {
      background: rgba(245, 166, 35, .15);
      color: var(--amber-2)
    }

    /* ── Conteúdo principal ── */
    .content {
      min-width: 0
    }

    .hero {
      margin-bottom: 56px
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .18em;
      text-transform: uppercase;
      color: var(--amber-2);
      margin-bottom: 14px
    }

    .eyebrow::before {
      content: "";
      width: 22px;
      height: 1.5px;
      background: linear-gradient(90deg, var(--amber-2), transparent)
    }

    .hero h1 {
      font-size: clamp(1.9rem, 4vw, 2.8rem);
      font-weight: 900;
      letter-spacing: -.03em;
      line-height: 1.1;
      margin-bottom: 14px;
      color: var(--text)
    }

    .hero h1 .accent {
      background: linear-gradient(120deg, var(--amber-2), var(--amber));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent
    }

    .hero p {
      color: var(--soft);
      font-size: 1.05rem;
      max-width: 640px;
      margin-bottom: 22px
    }

    .hero-cta {
      display: flex;
      gap: 12px;
      flex-wrap: wrap
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 22px;
      border-radius: 11px;
      font-weight: 700;
      font-size: .92rem;
      text-decoration: none;
      border: 0;
      cursor: pointer;
      transition: all .15s
    }

    .btn-primary {
      background: linear-gradient(150deg, var(--amber-3), var(--amber) 55%, var(--amber-2));
      color: #fff;
      box-shadow: 0 10px 24px -12px rgba(224, 120, 0, .6)
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 32px -12px rgba(224, 120, 0, .8)
    }

    .btn-ghost {
      background: #fff;
      color: var(--soft);
      border: 1px solid var(--line-2)
    }

    .btn-ghost:hover {
      background: var(--amber-soft);
      color: var(--amber-deep);
      border-color: var(--amber)
    }

    /* ── Secções ── */
    .section {
      margin-bottom: 44px;
      scroll-margin-top: 32px
    }

    .section-head {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--line)
    }

    .section-ico {
      width: 46px;
      height: 46px;
      border-radius: 13px;
      display: grid;
      place-items: center;
      font-size: 1.3rem;
      background: linear-gradient(140deg, var(--amber-soft), #FFF8E8);
      border: 1px solid rgba(224, 120, 0, .18);
      flex: none
    }

    .section-info {
      flex: 1;
      min-width: 0
    }

    .section-info h2 {
      font-size: 1.25rem;
      font-weight: 800;
      letter-spacing: -.02em;
      margin-bottom: 4px;
      color: var(--text)
    }

    .section-info p {
      color: var(--soft);
      font-size: .88rem
    }

    .section-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left: auto;
      flex: none
    }

    .badge {
      font-size: .62rem;
      font-weight: 800;
      letter-spacing: .08em;
      padding: 4px 9px;
      border-radius: 6px;
      text-transform: uppercase
    }

    .badge-pro {
      background: linear-gradient(135deg, var(--purple), #6D5AFF);
      color: #fff
    }

    .badge-premium {
      background: linear-gradient(135deg, var(--amber-3), var(--amber-2));
      color: #fff;
      box-shadow: 0 4px 10px -4px rgba(224, 120, 0, .4)
    }

    .badge-free {
      background: rgba(43, 47, 54, .06);
      color: var(--muted)
    }

    /* ── Grid de subtabs ── */
    .subs {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 12px
    }

    .sub {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 13px;
      padding: 16px 18px;
      transition: .2s;
      box-shadow: 0 2px 8px -6px rgba(122, 63, 0, .08)
    }

    .sub:hover {
      border-color: var(--line-2);
      transform: translateY(-2px);
      box-shadow: 0 12px 24px -18px rgba(122, 63, 0, .2)
    }

    .sub.locked {
      opacity: .72;
      background: #FDFCF9
    }

    .sub.locked:hover {
      opacity: .9;
      border-color: rgba(124, 58, 237, .25)
    }

    .sub-head {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px
    }

    .sub-ico {
      font-size: 1.1rem;
      flex: none
    }

    .sub-name {
      font-size: .92rem;
      font-weight: 700;
      letter-spacing: -.01em;
      flex: 1;
      color: var(--text)
    }

    .sub-flag {
      font-size: .58rem;
      font-weight: 800;
      letter-spacing: .06em;
      padding: 3px 6px;
      border-radius: 5px;
      text-transform: uppercase;
      flex: none
    }

    .flag-pro {
      background: rgba(124, 58, 237, .12);
      color: var(--purple)
    }

    .flag-premium {
      background: rgba(245, 166, 35, .16);
      color: var(--amber-2)
    }

    .sub-desc {
      color: var(--soft);
      font-size: .8rem;
      line-height: 1.55
    }

    .sub-list {
      list-style: none;
      padding: 10px 0 0;
      margin: 10px 0 0;
      border-top: 1px solid rgba(43, 47, 54, .06)
    }

    .sub-list li {
      font-size: .78rem;
      color: var(--soft);
      padding: 3px 0;
      padding-left: 16px;
      position: relative
    }

    .sub-list li::before {
      content: "›";
      position: absolute;
      left: 4px;
      color: var(--amber-2);
      font-weight: 800
    }

    /* ── CTA final ── */
    .final {
      background: linear-gradient(150deg, #fff, var(--amber-soft));
      border: 1px solid rgba(224, 120, 0, .25);
      border-radius: 24px;
      padding: 44px 36px;
      text-align: center;
      margin-top: 40px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 16px 40px -24px rgba(224, 120, 0, .25)
    }

    .final::before {
      content: "";
      position: absolute;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      left: 50%;
      top: -340px;
      transform: translateX(-50%);
      background: radial-gradient(circle, rgba(245, 166, 35, .25), transparent 65%);
      filter: blur(50px)
    }

    .final>* {
      position: relative
    }

    .final h2 {
      font-size: clamp(1.4rem, 3vw, 2rem);
      font-weight: 800;
      letter-spacing: -.03em;
      margin-bottom: 12px;
      color: var(--text)
    }

    .final p {
      color: var(--soft);
      max-width: 480px;
      margin: 0 auto 26px
    }

    .final-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap
    }
  </style>
</head>

<body>

  <a href="/frankei" class="back">← Voltar ao painel</a>

  <div class="layout">

    <!-- ═══ Sidebar de navegação ═══ -->
    <nav class="nav">
      <div class="nav-title">Secções</div>
      <ul class="nav-list">
        <li><a href="#all" class="nav-link"><span class="ico">📋</span><span class="lbl">Tudo</span><span class="cnt">1</span></a></li>
        <li><a href="#db" class="nav-link"><span class="ico">📊</span><span class="lbl">Bases de dados</span><span class="cnt">4</span></a></li>
        <li><a href="#security" class="nav-link prem-flag"><span class="ico">🔒</span><span class="lbl">Segurança</span><span class="cnt">11</span></a></li>
        <li><a href="#passwords" class="nav-link"><span class="ico">🔑</span><span class="lbl">Palavras-passe</span><span class="cnt">6</span></a></li>
        <li><a href="#quality" class="nav-link"><span class="ico">📐</span><span class="lbl">Código & Qualidade</span><span class="cnt">4</span></a></li>
        <li><a href="#tests" class="nav-link prem-flag"><span class="ico">🧪</span><span class="lbl">Testes</span><span class="cnt">2</span></a></li>
        <li><a href="#performance" class="nav-link"><span class="ico">⚡</span><span class="lbl">Desempenho</span><span class="cnt">4</span></a></li>
        <li><a href="#config" class="nav-link pro-flag"><span class="ico">⚙️</span><span class="lbl">Configuração</span><span class="cnt">PRO</span></a></li>
        <li><a href="#services" class="nav-link pro-flag"><span class="ico">🔌</span><span class="lbl">Microserviços</span><span class="cnt">PRO</span></a></li>
        <li><a href="#report" class="nav-link pro-flag"><span class="ico">📝</span><span class="lbl">Relatório</span><span class="cnt">PRO</span></a></li>
      </ul>
    </nav>

    <!-- ═══ Conteúdo ═══ -->
    <main class="content">

      <header class="hero">
        <span class="eyebrow">Plano Pro · Detalhes</span>
        <h1>O que podes fazer no <span class="accent">Frankei Pro</span></h1>
        <p>Cada secção explica o que analisa, que subtabs tem e o que te dá. Um mapa completo da ferramenta.</p>
        <div class="hero-cta">
          <a href="/frankei/precos" class="btn btn-primary">Ver planos →</a>
          <a href="/frankei" class="btn btn-ghost">← Voltar ao painel</a>
        </div>
      </header>

      <!-- ═══════════ TUDO ═══════════ -->
      <section class="section" id="all">
        <div class="section-head">
          <div class="section-ico">📋</div>
          <div class="section-info">
            <h2>Tudo</h2>
            <p>Corre todos os scanners disponíveis na tua conta de uma vez.</p>
          </div>
          <div class="section-meta"><span class="badge badge-free">Grátis</span></div>
        </div>
        <div class="subs">
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🚀</span><span class="sub-name">Todos os scanners</span></div>
            <div class="sub-desc">Executa em sequência todos os scanners a que tens acesso. Resultado agregado num só relatório.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ BASES DE DADOS ═══════════ -->
      <section class="section" id="db">
        <div class="section-head">
          <div class="section-ico">📊</div>
          <div class="section-info">
            <h2>Bases de dados</h2>
            <p>Analisa esquema, índices, constraints e boas práticas SQL.</p>
          </div>
          <div class="section-meta"><span class="badge badge-free">Grátis</span></div>
        </div>
        <div class="subs">
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📋</span><span class="sub-name">Tudo</span></div>
            <div class="sub-desc">Corre as três subtabs de uma vez — estrutura, qualidade e índices.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🏗️</span><span class="sub-name">Estrutura</span></div>
            <div class="sub-desc">Tabelas, colunas, foreign keys, primary keys. Deteta tabelas sem PK, colunas nullable a mais, tipos errados.</div>
            <ul class="sub-list">
              <li>Tabelas sem chave primária</li>
              <li>Foreign keys ausentes</li>
              <li>Colunas nullable excessivas</li>
              <li>Tipos suspeitos (VARCHAR grande, TEXT em índices)</li>
            </ul>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🔒</span><span class="sub-name">Qualidade</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Analisa nomes, comentários, consistência e convenções das tabelas.</div>
            <ul class="sub-list">
              <li>Nomes inconsistentes (snake_case vs camelCase)</li>
              <li>Tabelas sem comentário/descrição</li>
              <li>Colunas com nomes reservados</li>
              <li>Colunas duplicadas lógicas</li>
            </ul>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🔒</span><span class="sub-name">Índices</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Analisa cobertura, redundância e oportunidades de otimização.</div>
            <ul class="sub-list">
              <li>Índices duplicados/redundantes</li>
              <li>FKs sem índice</li>
              <li>Colunas muito filtradas sem índice</li>
              <li>Índices nunca usados (via EXPLAIN)</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- ═══════════ SEGURANÇA ═══════════ -->
      <section class="section" id="security">
        <div class="section-head">
          <div class="section-ico">🔒</div>
          <div class="section-info">
            <h2>Segurança</h2>
            <p>10+ scanners de vulnerabilidades. O que o OWASP Top 10 avisa, o Frankei deteta.</p>
          </div>
          <div class="section-meta"><span class="badge badge-premium">Premium</span></div>
        </div>
        <div class="subs">
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🚨</span><span class="sub-name">XSS</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Cross-site scripting. Deteta echo direto de input em HTML.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">💉</span><span class="sub-name">SQL Injection</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Concatenação de SQL com input do utilizador, queries dinâmicas.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🎭</span><span class="sub-name">CSRF</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Formulários sem token CSRF, endpoints POST sem validação.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🌐</span><span class="sub-name">SSRF</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Server-side request forgery em chamadas HTTP com URL dinâmico.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📄</span><span class="sub-name">XXE</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">XML external entities em parsers sem LIBXML_NOENT.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📂</span><span class="sub-name">LFI / RFI</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Inclusão de ficheiros remotos ou locais via include/require dinâmico.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">⌨️</span><span class="sub-name">Command Injection</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Uso de exec/shell_exec com input não sanitizado.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🗺️</span><span class="sub-name">Path Traversal</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Leitura de ficheiros fora do diretório permitido (../).</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📦</span><span class="sub-name">Deserialização</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Uso de unserialize() com dados não confiáveis.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">↪️</span><span class="sub-name">Open Redirect</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Redirecionamentos com URL controlado pelo utilizador.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📋</span><span class="sub-name">Tudo</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Corre todos os scanners de segurança de uma vez.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ PALAVRAS-PASSE ═══════════ -->
      <section class="section" id="passwords">
        <div class="section-head">
          <div class="section-ico">🔑</div>
          <div class="section-info">
            <h2>Palavras-passe</h2>
            <p>Políticas, hashing e exposição de credenciais no código.</p>
          </div>
          <div class="section-meta"><span class="badge badge-free">Grátis</span></div>
        </div>
        <div class="subs">
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📋</span><span class="sub-name">Tudo</span></div>
            <div class="sub-desc">Corre todas as verificações de passwords.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🔒</span><span class="sub-name">Hashes fracos</span></div>
            <div class="sub-desc">Deteta md5, sha1, crypt em passwords.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📜</span><span class="sub-name">Política</span></div>
            <div class="sub-desc">Comprimento mínimo, complexidade, rotação.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">💻</span><span class="sub-name">No código</span></div>
            <div class="sub-desc">Passwords hardcoded em ficheiros PHP/JS.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🌍</span><span class="sub-name">.env exposto</span></div>
            <div class="sub-desc">Ficheiros .env em diretórios públicos.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🔐</span><span class="sub-name">2FA</span></div>
            <div class="sub-desc">Presença de autenticação de dois fatores.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ CÓDIGO & QUALIDADE ═══════════ -->
      <section class="section" id="quality">
        <div class="section-head">
          <div class="section-ico">📐</div>
          <div class="section-info">
            <h2>Código & Qualidade</h2>
            <p>Boas práticas, manutenibilidade e "code smells" no PHP.</p>
          </div>
          <div class="section-meta"><span class="badge badge-free">Grátis</span></div>
        </div>
        <div class="subs">
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📝</span><span class="sub-name">TODO / FIXME / HACK</span></div>
            <div class="sub-desc">Comentários que indicam trabalho pendente.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🚫</span><span class="sub-name">Supressão de erros (@)</span></div>
            <div class="sub-desc">Operador @ esconde warnings.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🔢</span><span class="sub-name">Números mágicos</span></div>
            <div class="sub-desc">Literais numéricos sem constante nomeada.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📏</span><span class="sub-name">Funções longas / aninhamento</span></div>
            <div class="sub-desc">Métodos > 50 linhas ou 4+ níveis de indentação.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ TESTES ═══════════ -->
      <section class="section" id="tests">
        <div class="section-head">
          <div class="section-ico">🧪</div>
          <div class="section-info">
            <h2>Testes</h2>
            <p>Corre testes unitários com Pest ou PHPUnit, com cobertura integrada.</p>
          </div>
          <div class="section-meta"><span class="badge badge-premium">Premium</span></div>
        </div>
        <div class="subs">
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🐀</span><span class="sub-name">Pest</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">Sintaxe elegante e expressiva. Ideal para pseudocódigo que se lê como uma história.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🐘</span><span class="sub-name">PHPUnit</span><span class="sub-flag flag-premium">Premium</span></div>
            <div class="sub-desc">O padrão da indústria. Estrutura em classes, ideal para controlo total.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ DESEMPENHO ═══════════ -->
      <section class="section" id="performance">
        <div class="section-head">
          <div class="section-ico">⚡</div>
          <div class="section-info">
            <h2>Desempenho</h2>
            <p>Deteta gargalos comuns em aplicações PHP.</p>
          </div>
          <div class="section-meta"><span class="badge badge-free">Grátis</span></div>
        </div>
        <div class="subs">
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🔁</span><span class="sub-name">N+1 Queries</span></div>
            <div class="sub-desc">Queries dentro de loops que podiam ser agrupadas.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">⭐</span><span class="sub-name">SELECT *</span></div>
            <div class="sub-desc">Trazer todas as colunas em vez de apenas as usadas.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">🔂</span><span class="sub-name">Loops pesados</span></div>
            <div class="sub-desc">Loops aninhados com complexidade alta.</div>
          </div>
          <div class="sub">
            <div class="sub-head"><span class="sub-ico">📊</span><span class="sub-name">Tudo</span></div>
            <div class="sub-desc">Corre todos os detectores de desempenho.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ CONFIGURAÇÃO (PRO) ═══════════ -->
      <section class="section" id="config">
        <div class="section-head">
          <div class="section-ico">⚙️</div>
          <div class="section-info">
            <h2>Configuração</h2>
            <p>Analisa ficheiros de configuração e variáveis de ambiente.</p>
          </div>
          <div class="section-meta"><span class="badge badge-pro">Pro</span></div>
        </div>
        <div class="subs">
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🔍</span><span class="sub-name">Variáveis de ambiente</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Valida .env, valores por defeito, consistência entre ambientes.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📄</span><span class="sub-name">Configurações de framework</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Análise de config/*.php, composer.json, .htaccess.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ MICROSERVIÇOS (PRO) ═══════════ -->
      <section class="section" id="services">
        <div class="section-head">
          <div class="section-ico">🔌</div>
          <div class="section-info">
            <h2>Microserviços</h2>
            <p>Valida integrações HTTP, webhooks e serviços externos.</p>
          </div>
          <div class="section-meta"><span class="badge badge-pro">Pro</span></div>
        </div>
        <div class="subs">
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📡</span><span class="sub-name">Health checks</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Verifica se os endpoints de serviços externos respondem.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🔔</span><span class="sub-name">Webhooks</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Valida configuração de webhooks e assinaturas.</div>
          </div>
        </div>
      </section>

      <!-- ═══════════ RELATÓRIO (PRO) ═══════════ -->
      <section class="section" id="report">
        <div class="section-head">
          <div class="section-ico">📝</div>
          <div class="section-info">
            <h2>Relatório</h2>
            <p>Exporta, partilha e agenda relatórios completos.</p>
          </div>
          <div class="section-meta"><span class="badge badge-pro">Pro</span></div>
        </div>
        <div class="subs">
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📥</span><span class="sub-name">Export</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">PDF, HTML, JSON. Personalizável.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">📤</span><span class="sub-name">Partilha</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">URLs temporários para partilhar com a equipa.</div>
          </div>
          <div class="sub locked">
            <div class="sub-head"><span class="sub-ico">🕐</span><span class="sub-name">Agendamento</span><span class="sub-flag flag-pro">Pro</span></div>
            <div class="sub-desc">Corridas periódicas com envio por email.</div>
          </div>
        </div>
      </section>

      <!-- ═══ CTA final ═══ -->
      <div class="final">
        <h2>Pronto para o Pro?</h2>
        <p>Análise completa sem limites. Começa hoje, cancela quando quiseres.</p>
        <div class="final-actions">
          <a href="/frankei/precos" class="btn btn-primary">Escolher Pro (49€/ano) →</a>
          <a href="/frankei/precos" class="btn btn-ghost">Ver todos os planos</a>
        </div>
      </div>

    </main>
  </div>

  <script>
    document.querySelectorAll('.nav-link').forEach(function(a) {
      a.addEventListener('click', function(e) {
        var href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
          e.preventDefault();
          var target = document.querySelector(href);
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
            history.replaceState(null, '', href);
          }
        }
      });
    });
  </script>
</body>

</html>