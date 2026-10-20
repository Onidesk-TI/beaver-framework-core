═══════════════════════════════════════════════════════════════════════════
FRANKEI — Analisador de código e segurança
═══════════════════════════════════════════════════════════════════════════

Versão: 0.1.0
Autor: Onidesk
Licença: GPL-3.0-or-later
PHP: >=8.1

───────────────────────────────────────────────────────────────────────────
1. O QUE É
───────────────────────────────────────────────────────────────────────────

O Frankei é um plugin do Beaver Framework que analisa código PHP, esquemas
de BD e configurações em busca de:

  • Vulnerabilidades de segurança (XSS, SQLi, CSRF, SSRF, LFI, etc.)
  • Problemas de performance (N+1, SELECT *, loops pesados)
  • Hashes de password fracos (md5, sha1)
  • Problemas estruturais em bases de dados (PKs, FKs, índices)

Tem 2 interfaces:
  • Dashboard web (com 6 tabs de configuração)
  • API JSON (para automação / CI)

───────────────────────────────────────────────────────────────────────────
2. INSTALAÇÃO
───────────────────────────────────────────────────────────────────────────

O plugin já está integrado. Para ativar:

  • Ativa o plugin no painel de plugins do Beaver
  • Acede ao dashboard em: /frankei
  • O dashboard precisa de um path para analisar (por defeito, o projeto atual)

───────────────────────────────────────────────────────────────────────────
3. DASHBOARD (6 TABS)
───────────────────────────────────────────────────────────────────────────

  3.1 — 📊 Análise
        • Modo: Todas / Só segurança / Só passwords / Só desempenho / Só BD
        • Análise sequencial (uma tab de cada vez com feedback)
        • Badges em tempo real

  3.2 — 🔧 Auto-fix
        • Ativar auto-fix (CSRF, XSS)
        • Criar backup antes de alterar (.bak)
        • Não pedir confirmação (aplica direto)
        • Mostrar corrigidos

  3.3 — ⚡ Desempenho
        • Ignorar scripts/ (setup, migrations, seeds)
        • Ignorar DDL (OPTIMIZE, PRAGMA, CREATE, DROP)
        • Ignorar loops triviais ($admins, $planos, $roles)
        • Ignorar SELECT * por ID (WHERE id = :x)
        • Ignorar views/ (falsos positivos em templates)
        • SELECT * como erro (em vez de info)

  3.4 — 🔒 Segurança (NOVO)
        Scanners (todos ON por defeito):
          ☑ XSS               ☑ SQL Injection
          ☑ Command Injection ☑ Code Execution
          ☑ CSRF              ☑ Deserialization
          ☑ File Inclusion    ☑ Path Traversal
          ☑ Open Redirect     ☑ SSRF
          ☑ XXE

        Pastas ignoradas (todas ON por defeito):
          ☑ vendor/     ☑ node_modules/
          ☑ tests/fixtures/  ☑ .backups/
          ☑ build/

        Performance:
          ☑ Queries N+1
          ☑ SELECT * em vez de colunas
          ☑ Loops pesados

        Passwords:
          ☑ Hashes fracos (md5, sha1, crypt)

  3.5 — 🎯 Avançado (NOVO)
        Contexto de análise:
          • Linhas de contexto (1-10, default 3)
            → usado por SSRF e Path Traversal
            → quanto maior, mais falsos positivos + mais deteções

        Tabelas que requerem atenção:
          Sensíveis (🔴):
            ☑ users          ☑ api_keys
            ☑ api_logs       ☑ user_sessions
            ☑ user_devices   ☑ email_queue
            ☑ moderation_log ☑ votes

          Grandes (🟡, crescem muito):
            ☑ jobs              ☑ scheduled_jobs
            ☑ image_optimizer_log ☑ translation_history
            ☐ threads           ☐ replies
            ☐ notifications     ☐ mentions

          Personalizadas (CSV):
            input livre: "minha_tabela,outra_tabela"
            → adiciona à lista acima

        Limites:
          • Limite de findings (0 = sem limite, default 500)
          • Timeout por scanner em segundos (default 30)

  3.6 — 👤 Conta
        • Trocar aplicação
        • Terminar sessão

───────────────────────────────────────────────────────────────────────────
4. SCANNERS (24 no total)
───────────────────────────────────────────────────────────────────────────

  4.1 — SEGURANÇA (11)
    php-xss             XSS em código PHP
    php-sqli            SQL Injection em código PHP
    php-cmdi            Command Injection
    php-code-exec       Execução de código dinâmico (eval, assert)
    php-csrf            CSRF em formulários
    php-deserialize     Deserialização insegura
    php-fileinclusion   LFI / RFI (File Inclusion)
    php-pathtraversal   Path Traversal
    php-openredirect    Open Redirect
    php-ssrf            SSRF em pedidos HTTP
    php-xxe             XXE (XML External Entity)

  4.2 — PERFORMANCE (3)
    perf-nplus1         Queries N+1 em loops
    perf-select-star    SELECT * em vez de colunas
    perf-heavy-loop     Loops aninhados com queries

  4.3 — PASSWORDS (1)
    weak-password-hash  Hashes de password fracos (md5, sha1, crypt)

  4.4 — DATABASE (7)
    list-tables         Lista de tabelas
    list-columns        Colunas por tabela
    list-indexes        Índices
    list-fks            Foreign keys
    missing-pk          Tabelas sem PRIMARY KEY
    fk-without-index    FKs sem índice
    excessive-nullable  Colunas nullable em excesso
    suspicious-type     Tipos suspeitos

───────────────────────────────────────────────────────────────────────────
5. OPÇÕES (~35 no total)
───────────────────────────────────────────────────────────────────────────

Todas as opções são guardadas em localStorage com prefixo "frankei:".

  Auto-fix:
    autoFixCsrf         Ativar auto-fix CSRF
    backup              Criar backup antes de alterar
    skipConfirm         Não pedir confirmação
    showFixed           Mostrar corrigidos

  Análise:
    sequential          Análise sequencial
    liveBadges          Badges em tempo real

  Desempenho:
    skipScripts         Ignorar scripts/
    skipDdl             Ignorar DDL
    skipTrivialLoops    Ignorar loops triviais
    ignoreSelectById    Ignorar SELECT * por ID
    skipViews           Ignorar views/
    selectStarAsError   SELECT * como erro

  Segurança — scanners:
    runXss, runSqli, runCmdi, runCodeExec, runCsrf, runDeserialize,
    runFileInclusion, runPathTraversal, runOpenRedirect, runSsrf, runXxe

  Segurança — pastas:
    ignoreVendor, ignoreNodeModules, ignoreTestsFixtures,
    ignoreBackups, ignoreBuild

  Performance:
    runPerfNplus1, runPerfSelectStar, runPerfHeavyLoop

  Passwords:
    runWeakPasswordHash

  Avançado:
    contextLines         Linhas de contexto (1-10)
    maxFindings          Limite de findings (0 = sem limite)
    scannerTimeout       Timeout por scanner (s)
    attentionTablesCustom CSV de tabelas personalizadas
    attUsers, attApiKeys, attApiLogs, attUserSessions, attUserDevices,
    attEmailQueue, attModerationLog, attVotes, attJobs, attScheduledJobs,
    attImageOptimizerLog, attTranslationHistory,
    attThreads, attReplies, attNotifications, attMentions

───────────────────────────────────────────────────────────────────────────
6. API
───────────────────────────────────────────────────────────────────────────

POST /api/frankei/scan-code

Body (JSON):
  {
    "path": "/var/www/meu-projeto",
    "scanners": ["php-xss","php-sqli"],           // opcional; vazio = todos
    "options": {                                   // opcional
      "skipDdl": true,
      "runXss": true,
      "ignoreVendor": true,
      "contextLines": 3,
      "maxFindings": 500,
      "attentionTables": ["users","api_keys"]
    }
  }

Response (JSON):
  {
    "path": "...",
    "findings": [
      {
        "scanner": "php-xss",
        "category": "security",
        "severity": "high",
        "title": "Possivel XSS em public/_debug.php:18",
        "description": "Saida de variavel sem escape",
        "impact": "...",
        "suggestion": "htmlspecialchars($_SERVER['PHP_SELF'], ...)",
        "reference": "https://owasp.org/...",
        "data": {
          "file": "public/_debug.php",
          "line": 18,
          "code": "..."
        },
        "attention": false
      }
    ],
    "stats": {
      "total": 15,
      "by_severity": { "critical":0, "high":0, "medium":0, "low":0, "info":15 },
      "by_category": { "performance":15 },
      "elapsed_ms": 2910,
      "files_scanned": 427,
      "truncated": false,
      "max_findings": 0
    },
    "errors": [],
    "scanners": [ /* metadata dos scanners */ ],
    "scanned_at": "2026-10-09T12:00:00+00:00"
  }

───────────────────────────────────────────────────────────────────────────
7. COMO ADICIONAR UM SCANNER
───────────────────────────────────────────────────────────────────────────

  1) Cria o ficheiro em src/Frankei/Scanners/<Categoria>/MeuScanner.php
     Implementa a interface CodeScannerInterface

  2) Assinatura obrigatória:
       public function scan(array $files, string $root, array $options = []): array
     Retorna array de Finding

  3) Registra-o em src/Frankei/CodeScannerRegistry.php (construtor)

  4) Se for configurável, adiciona o mapeamento em SCANNER_TO_OPT:
       'meu-scanner-id' => 'runMeuScanner',

  5) No dashboard.js:
     - Adiciona o toggle no painel correto (dashboard.php)
     - Adiciona ao initOptions() e ao FrankeiOpts
     - Adiciona ao perfOptions (para enviar via fetch)

───────────────────────────────────────────────────────────────────────────
8. TESTES COM FIXTURES
───────────────────────────────────────────────────────────────────────────

Existem fixtures em tests/fixtures/vulnerable/ com 1 exemplo por scanner:

  xss.php                → php-xss
  cmdi.php               → php-cmdi
  code-exec.php          → php-code-exec
  sqli.php               → php-sqli
  csrf.php               → php-csrf
  deserialize.php        → php-deserialize
  lfi.php                → php-fileinclusion
  pathtraversal.php      → php-pathtraversal
  openredirect.php       → php-openredirect
  ssrf.php               → php-ssrf
  xxe.php                → php-xxe
  ssrf-veryfar.php       → testa contextLines
  ssrf-multiline.php     → testa contexto curto

Correr scan contra as fixtures:

  curl -s -X POST http://localhost:9000/api/frankei/scan-code \
    -H "Content-Type: application/json" \
    -d '{"path":"/var/www/onidesk/beaver-framework2/tests/fixtures/vulnerable"}'

Esperado: 11 scanners de segurança detetam os seus ficheiros.

───────────────────────────────────────────────────────────────────────────
9. ESTRUTURA DE FICHEIROS
───────────────────────────────────────────────────────────────────────────

plugins/frankei/
├── plugin.json                  Configuração do plugin
├── composer.json                Dependências (PHP >=8.1)
├── README.txt                   Este ficheiro
├── routes/
│   └── web.php                  Rotas HTTP (dashboard, API)
├── resources/
│   ├── views/
│   │   └── dashboard.php        Template do dashboard
│   └── ui/
│       ├── css/dashboard.css    Estilos
│       └── js/dashboard.js      Lógica do frontend
└── src/
    ├── FrankeiPlugin.php        Entrypoint
    ├── Http/
    │   ├── FrankeiController.php  scanCode(), etc.
    │   ├── FixController.php      Auto-fixes
    │   └── SetupController.php    Setup inicial
    └── Frankei/
        ├── Code/
        │   ├── CodeScannerInterface.php
        │   └── CodeScanContext.php
        ├── CodeScannerRegistry.php
        ├── Finding.php
        ├── Fixer/                Aplicadores de correção
        └── Scanners/
            ├── Security/         (11 scanners)
            ├── Performance/      (3 scanners)
            └── Passwords/        (1 scanner)

───────────────────────────────────────────────────────────────────────────
10. TROUBLESHOOTING
───────────────────────────────────────────────────────────────────────────

  Sintoma: findings não aparecem na lista mas o emulador mostra N
  Causa: filtro por tab ativa (matchesTab em dashboard.js)
  Fix: verifica que o modo correto está selecionado ou usa "Todas"

  Sintoma: mudar uma opção no dashboard não tem efeito
  Causa: o scanner não está a ler $ctx->opt()
  Fix: verifica que o scanner recebe $options e passa ao CodeScanContext

  Sintoma: opções não persistem entre sessões
  Causa: localStorage limpo ou bloqueado
  Fix: verifica cookies/localStorage no browser

  Sintoma: contextLines maior deteta mais mas com falsos positivos
  Causa: procura de taint em janelas maiores
  Fix: ajusta para o valor mínimo que deteta os casos reais

───────────────────────────────────────────────────────────────────────────
