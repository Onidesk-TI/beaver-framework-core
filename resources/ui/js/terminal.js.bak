/* ============================================================
   BEAVER FRAMEWORK — Terminal Sandbox
   ============================================================ */

(function () {
  var body = document.getElementById('termBody');
  var resetBtn = document.getElementById('termReset');
  if (!body) return;

  var state = {
    history: [],
    histIdx: -1,
    draft: '',
    busy: false,
    booting: false
  };

  var commands = {
    help: {
      desc: 'Lista os comandos disponíveis',
      run: function () {
        return [
          { t: 'Comandos disponíveis:', c: 'c-out' },
          { t: '', c: '' },
          { t: '  ── Framework ─────────────────────────────────────', c: 'c-out' },
          { t: '  beaver serve                           Inicia o servidor local', c: '' },
          { t: '  beaver about                           Sobre o Beaver', c: '' },
          { t: '  beaver --version                       Mostra a versão', c: '' },
          { t: '  beaver --help                          Mostra esta ajuda', c: '' },
          { t: '', c: '' },
          { t: '  ── Geração de código ─────────────────────────────', c: 'c-out' },
          { t: '  beaver make:model <Nome>               Modelo + migration + factory', c: '' },
          { t: '  beaver make:controller <Nome>          Controller (+ --resource)', c: '' },
          { t: '  beaver make:migration <nome>           Migration vazia', c: '' },
          { t: '  beaver make:seeder <Nome>              Seeder', c: '' },
          { t: '  beaver make:middleware <Nome>          Middleware', c: '' },
          { t: '  beaver make:request <Nome>             Form request', c: '' },
          { t: '', c: '' },
          { t: '  ── Base de dados ────────────────────────────────', c: 'c-out' },
          { t: '  beaver migrate                         Aplica migrações', c: '' },
          { t: '  beaver migrate --seed                  Migrações + seeders', c: '' },
          { t: '  beaver migrate:rollback                Reverte a última', c: '' },
          { t: '  beaver migrate:fresh                   Recria tudo', c: '' },
          { t: '  beaver db:seed [Nome]                  Executa seeders', c: '' },
          { t: '  beaver db:wipe                         Limpa as tabelas', c: '' },
          { t: '  beaver db:status                       Estado das migrações', c: '' },
          { t: '', c: '' },
          { t: '  ── Testes ──────────────────────────────────────', c: 'c-out' },
          { t: '  beaver test                            Todos os testes', c: '' },
          { t: '  beaver test --parallel                 Em paralelo', c: '' },
          { t: '  beaver test --filter=<Nome>            Só um teste', c: '' },
          { t: '', c: '' },
          { t: '  ── Ficheiros ───────────────────────────────────', c: 'c-out' },
          { t: '  ls                                     Lista ficheiros', c: '' },
          { t: '  pwd                                    Diretório atual', c: '' },
          { t: '  cat <ficheiro>                         Mostra conteúdo', c: '' },
          { t: '', c: '' },
          { t: '  ── Utilitários ─────────────────────────────────', c: 'c-out' },
          { t: '  clear                                  Limpa o terminal', c: '' },
          { t: '  about                                  Sobre o Beaver', c: '' }
        ];
      }
    },

    'beaver': {
      desc: 'CLI do Beaver Framework',
      run: function (args) {
        var sub = (args[0] || '').toLowerCase();

        switch (sub) {
          case 'serve':
          case 'server':
            return {
              delay: 700,
              lines: [
                { t: '  ██████╗ ███████╗ █████╗ ██╗   ██╗███████╗██████╗', c: 'c-out' },
                { t: '  ██╔══██╗██╔════╝██╔══██╗██║   ██║██╔════╝██╔══██╗', c: 'c-out' },
                { t: '  ██████╔╝█████╗  ███████║██║   ██║█████╗  ██████╔╝', c: 'c-out' },
                { t: '  ██╔══██╗██╔══╝  ██╔══██║╚██╗ ██╔╝██╔══╝  ██╔══██╗', c: 'c-out' },
                { t: '  ██████╔╝███████╗██║  ██║ ╚████╔╝ ███████╗██║  ██║', c: 'c-out' },
                { t: '  ╚═════╝ ╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝', c: 'c-out' },
                { t: ' ', c: '' },
                { t: '  Beaver Framework ' + span('v0.3.0', 'c-str') + '  ' + span('(PHP 8.3)', 'c-out'), c: '', raw: true },
                { t: '  ➜  Servidor local: ' + span('http://localhost:8000', 'c-str'), c: '', raw: true },
                { t: '  ➜  Pronto em ' + span('342ms', 'c-str') + ' 🦫', c: '', raw: true }
              ]
            };

          case 'about':
            return commands.about.run();

          case 'make:model':
            if (!args[1]) return err('Falta o nome do modelo. Ex: beaver make:model Produto');
            var model = capitalize(args[1]);
            var table = pluralize(model.toLowerCase());
            return {
              delay: 400,
              lines: [
                { t: '  ' + span('✔', 'c-str') + ' Modelo criado     app/Models/' + model + '.php', c: 'c-out', raw: true },
                { t: '  ' + span('✔', 'c-str') + ' Migration criada  database/migrations/2024_05_12_153045_create_' + table + '_table.php', c: 'c-out', raw: true },
                { t: '  ' + span('✔', 'c-str') + ' Factory criada    database/factories/' + model + 'Factory.php', c: 'c-out', raw: true },
                { t: '  ' + span('✔', 'c-str') + ' Seeder criado     database/seeders/' + model + 'Seeder.php', c: 'c-out', raw: true }
              ]
            };

          case 'make:controller':
            if (!args[1]) return err('Falta o nome do controller. Ex: beaver make:controller ProdutoController');
            var ctrl = args[1];
            var res = args.indexOf('--resource') !== -1;
            var cLines = [
              { t: '  ' + span('✔', 'c-str') + ' Controller criado  app/Controllers/' + ctrl + '.php', c: 'c-out', raw: true },
              { t: '  ' + span('✔', 'c-str') + ' Rotas registadas   routes/web.php', c: 'c-out', raw: true }
            ];
            if (res) cLines.push({ t: '  ' + span('✔', 'c-str') + ' Rotas resource     7 rotas geradas', c: 'c-out', raw: true });
            cLines.push({ t: '  ' + span('✔', 'c-str') + ' Teste gerado       tests/Feature/' + ctrl.replace('Controller', '') + 'Test.php', c: 'c-out', raw: true });
            return { delay: 500, lines: cLines };

          case 'make:migration':
            if (!args[1]) return err('Falta o nome da migration. Ex: beaver make:migration create_pedidos_table');
            var migName = args[1];
            var migFile = '2024_05_12_153045_' + migName + '.php';
            return {
              delay: 400,
              lines: [
                { t: '  ' + span('✔', 'c-str') + ' Migration criada  database/migrations/' + migFile, c: 'c-out', raw: true },
                { t: '  ' + span('➜', 'c-fn') + '  Edita o ficheiro para definir o schema', c: 'c-out', raw: true }
              ]
            };

          case 'make:seeder':
            if (!args[1]) return err('Falta o nome do seeder. Ex: beaver make:seeder ProdutoSeeder');
            var seedName = capitalize(args[1]);
            if (!/Seeder$/.test(seedName)) seedName += 'Seeder';
            return {
              delay: 300,
              lines: [
                { t: '  ' + span('✔', 'c-str') + ' Seeder criado  database/seeders/' + seedName + '.php', c: 'c-out', raw: true },
                { t: '  ' + span('➜', 'c-fn') + '  Corre "beaver db:seed ' + seedName.replace('Seeder', '') + '" para executar', c: 'c-out', raw: true }
              ]
            };

          case 'make:middleware':
            if (!args[1]) return err('Falta o nome do middleware. Ex: beaver make:middleware AuthMiddleware');
            var mwName = capitalize(args[1]);
            return {
              delay: 300,
              lines: [
                { t: '  ' + span('✔', 'c-str') + ' Middleware criado  app/Middleware/' + mwName + '.php', c: 'c-out', raw: true }
              ]
            };

          case 'make:request':
            if (!args[1]) return err('Falta o nome do request. Ex: beaver make:request StoreProdutoRequest');
            var reqName = capitalize(args[1]);
            return {
              delay: 300,
              lines: [
                { t: '  ' + span('✔', 'c-str') + ' Request criado  app/Requests/' + reqName + '.php', c: 'c-out', raw: true }
              ]
            };

          case 'migrate':
            var seed = args.indexOf('--seed') !== -1;
            var mLines = [
              { t: '  Migrando: 2024_05_10_create_categorias_table ......... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
              { t: '  Migrando: 2024_05_12_create_produtos_table .......... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
              { t: '  Migrando: 2024_05_14_create_utilizadores_table ...... ' + span('DONE', 'c-str'), c: 'c-out', raw: true }
            ];
            if (seed) {
              mLines.push({ t: '', c: '' });
              mLines.push({ t: '  Seeding: CategoriaSeeder .............. ' + span('8 registos', 'c-str'), c: 'c-out', raw: true });
              mLines.push({ t: '  Seeding: ProdutoSeeder ................ ' + span('42 registos', 'c-str'), c: 'c-out', raw: true });
              mLines.push({ t: '  Seeding: UtilizadorSeeder ............. ' + span('3 registos', 'c-str'), c: 'c-out', raw: true });
            }
            mLines.push({ t: '', c: '' });
            mLines.push({ t: '  ' + span('✔', 'c-str') + ' Migrações aplicadas com sucesso', c: 'c-out', raw: true });
            return { delay: 700, lines: mLines };

          case 'migrate:rollback':
          case 'migrate:reset':
            return {
              delay: 500,
              lines: [
                { t: '  Revertendo: 2024_05_14_create_utilizadores_table .... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
                { t: '', c: '' },
                { t: '  ' + span('✔', 'c-str') + ' Última migração revertida', c: 'c-out', raw: true }
              ]
            };

          case 'migrate:fresh':
            return {
              delay: 800,
              lines: [
                { t: '  ' + span('✖', 'c-str') + ' A apagar todas as tabelas...', c: 'c-out', raw: true },
                { t: '  ' + span('✔', 'c-str') + ' Tabelas apagadas', c: 'c-out', raw: true },
                { t: '', c: '' },
                { t: '  Migrando: 2024_05_10_create_categorias_table ......... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
                { t: '  Migrando: 2024_05_12_create_produtos_table .......... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
                { t: '  Migrando: 2024_05_14_create_utilizadores_table ...... ' + span('DONE', 'c-str'), c: 'c-out', raw: true },
                { t: '', c: '' },
                { t: '  ' + span('✔', 'c-str') + ' Base de dados recriada com sucesso', c: 'c-out', raw: true }
              ]
            };

          case 'test':
            var par = args.indexOf('--parallel') !== -1;
            var filter = null;
            args.forEach(function (a) {
              var m = a.match(/^--filter=(.+)$/);
              if (m) filter = m[1];
            });
            var tLines = [];
            if (filter) {
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Feature\\' + filter, c: 'c-out', raw: true });
              tLines.push({ t: '', c: '' });
              tLines.push({ t: '  Testes: ' + span('1 passou', 'c-str') + ' · Duração: ' + span('0.12s', 'c-str'), c: 'c-out', raw: true });
            } else {
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Feature\\ProdutoTest', c: 'c-out', raw: true });
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Feature\\AuthTest', c: 'c-out', raw: true });
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Feature\\CategoriaTest', c: 'c-out', raw: true });
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Unit\\ModelTest', c: 'c-out', raw: true });
              tLines.push({ t: '  ' + span('PASS', 'c-str') + '  Tests\\Unit\\HelperTest', c: 'c-out', raw: true });
              tLines.push({ t: '', c: '' });
              tLines.push({ t: '  Testes: ' + span('42 passaram', 'c-str') + ' · Duração: ' + span('1.28s', 'c-str') + (par ? ' · parallel' : ''), c: 'c-out', raw: true });
            }
            return { delay: 900, lines: tLines };

          case '--version':
          case '-v':
          case 'version':
            return [{ t: '  Beaver Framework ' + span('v0.3.0', 'c-str') + '  ' + span('(PHP 8.3)', 'c-out'), c: '', raw: true }];

          case '--help':
          case 'help':
          case '':
            return commands.help.run();

          default:
            return err('Comando desconhecido: beaver ' + sub + '\nCorre "beaver --help" para ver os comandos.');
        }
      }
    },

    'db:seed': {
      desc: 'Executa seeders',
      run: function (args) {
        var only = args[0];
        var seeders = [
          { name: 'CategoriaSeeder', n: 8 },
          { name: 'ProdutoSeeder', n: 42 },
          { name: 'UtilizadorSeeder', n: 3 }
        ];
        if (only) {
          seeders = seeders.filter(function (s) {
            return s.name.toLowerCase().indexOf(only.toLowerCase()) !== -1;
          });
          if (seeders.length === 0) return err('Seeder não encontrado: ' + only);
        }
        var lines = seeders.map(function (s) {
          return { t: '  ' + span('✔', 'c-str') + ' ' + s.name + ' ................. ' + s.n + ' registos', c: 'c-out', raw: true };
        });
        var total = seeders.reduce(function (sum, s) { return sum + s.n; }, 0);
        lines.push({ t: '', c: '' });
        lines.push({ t: '  ' + span('✔', 'c-str') + ' ' + seeders.length + ' seeders · ' + total + ' registos', c: 'c-out', raw: true });
        return { delay: 600, lines: lines };
      }
    },

    'db:wipe': {
      desc: 'Limpa todas as tabelas',
      run: function () {
        return {
          delay: 500,
          lines: [
            { t: '  ' + span('✔', 'c-str') + ' Tabela produtos ....... apagada (42 registos)', c: 'c-out', raw: true },
            { t: '  ' + span('✔', 'c-str') + ' Tabela categorias ..... apagada (8 registos)', c: 'c-out', raw: true },
            { t: '  ' + span('✔', 'c-str') + ' Tabela utilizadores ... apagada (3 registos)', c: 'c-out', raw: true },
            { t: '', c: '' },
            { t: '  ' + span('✔', 'c-str') + ' Base de dados limpa', c: 'c-out', raw: true }
          ]
        };
      }
    },

    'db:status': {
      desc: 'Estado das migrações',
      run: function () {
        return [
          { t: '  Migration                                          Batch  Status', c: 'c-out' },
          { t: '  ─────────────────────────────────────────────────────────────', c: 'c-out' },
          { t: '  2024_05_10_create_categorias_table                 1      ' + span('Ran', 'c-str'), c: '', raw: true },
          { t: '  2024_05_12_create_produtos_table                   1      ' + span('Ran', 'c-str'), c: '', raw: true },
          { t: '  2024_05_14_create_utilizadores_table               1      ' + span('Ran', 'c-str'), c: '', raw: true },
          { t: '  2024_05_15_create_pedidos_table                    2      ' + span('Ran', 'c-str'), c: '', raw: true },
          { t: '', c: '' },
          { t: '  4 migrações · 0 pendentes', c: 'c-out' }
        ];
      }
    },

    cat: {
      desc: 'Mostra o conteúdo de um ficheiro',
      run: function (args) {
        if (!args[0]) return err('Uso: cat <ficheiro>\nEx: cat routes/web.php');
        var path = args.join(' ').toLowerCase();

        var files = {
          'routes/web.php': [
            { t: '// routes/web.php', c: 'c-cm', raw: true },
            { t: 'use Beaver\\Http\\Router;', c: '' },
            { t: 'use Beaver\\Http\\Response;', c: '' }, 
            { t: '', c: '' },
            { t: '$router = Router::current();', c: '' },
            { t: "$router->get('/', fn() => view('home'));", c: '' },
            { t: '', c: '' },
            { t: "$router->get('/ping', fn() => Response::json(['ok' => true]));", c: '' }
          ],
          'app/models/produto.php': [
            { t: '// app/Models/Produto.php', c: 'c-cm', raw: true },
            { t: 'declare(strict_types=1);', c: '' },
            { t: '', c: '' },
            { t: 'namespace App\\Models;', c: '' },
            { t: '', c: '' },
            { t: 'use Beaver\\Database\\Model\\Model;', c: '' },
            { t: '', c: '' },
            { t: 'class Produto extends Model', c: '' },
            { t: '{', c: '' },
            { t: "    protected static string $table = 'produtos';", c: '' },
            { t: '', c: '' },
            { t: "    protected array $fillable = ['nome', 'preco'];", c: '' },
            { t: '}', c: '' }
          ],
          'composer.json': [
            { t: '{', c: '' },
            { t: '    "name": "beaver/framework",', c: '' },
            { t: '    "type": "library",', c: '' },
            { t: '    "require": {', c: '' },
            { t: '        "php": ">=8.1"', c: '' },
            { t: '    }', c: '' },
            { t: '}', c: '' }
          ],
          '.env': [
            { t: 'APP_NAME=Beaver', c: '' },
            { t: 'APP_ENV=local', c: '' },
            { t: 'APP_DEBUG=true', c: '' },
            { t: '', c: '' },
            { t: 'DB_CONNECTION=sqlite', c: '' }
          ],
          'readme.md': [
            { t: '# Beaver Framework 🦫', c: 'c-var', raw: true },
            { t: '', c: '' },
            { t: 'Modern PHP framework with a plugin ecosystem.', c: '' }
          ]
        };

        var key = Object.keys(files).find(function (k) { return k === path; });
        if (!key) {
          var suffix = Object.keys(files).find(function (k) { return k.endsWith(path); });
          if (suffix) key = suffix;
        }
        if (!key) return err('Ficheiro não encontrado: ' + args.join(' ') + '\nExperimenta: cat routes/web.php');

        return files[key].map(function (l) {
          return { t: l.t, c: l.c || '', raw: l.c === 'c-cm' || l.c === 'c-var' };
        });
      }
    },

    ls: {
      desc: 'Lista ficheiros do projeto',
      run: function () {
        return [
          { t: 'app/', c: 'c-var' },
          { t: '  Controllers/', c: '' },
          { t: '  Models/', c: '' },
          { t: '  views/', c: '' },
          { t: 'plugins/', c: 'c-var' },
          { t: '  sqlwizard-lite/', c: '' },
          { t: 'routes/', c: 'c-var' },
          { t: '  web.php', c: '' },
          { t: 'src/', c: 'c-var' },
          { t: 'composer.json', c: '' },
          { t: 'README.md', c: '' }
        ];
      }
    },

    pwd: {
      desc: 'Mostra o diretório atual',
      run: function () {
        return [{ t: '/var/www/onidesk/beaver-framework2', c: 'c-var', raw: true }];
      }
    },

    clear: {
      desc: 'Limpa o terminal',
      run: function () {
        body.innerHTML = '';
        return null;
      }
    },

    about: {
      desc: 'Sobre o Beaver Framework',
      run: function () {
        return [
          { t: 'Beaver Framework v0.3.0', c: 'c-var', raw: true },
          { t: 'Modern PHP framework with a plugin ecosystem.', c: '' },
          { t: '', c: '' },
          { t: 'GitHub: github.com/Onidesk-TI/beaver-framework', c: 'c-out' }
        ];
      }
    }
  };

  // Aliases
  commands['serve'] = commands.beaver;
  commands['make:model'] = commands.beaver;
  commands['make:controller'] = commands.beaver;
  commands['make:migration'] = commands.beaver;
  commands['make:seeder'] = commands.beaver;
  commands['make:middleware'] = commands.beaver;
  commands['make:request'] = commands.beaver;
  commands['migrate'] = commands.beaver;
  commands['migrate:rollback'] = commands.beaver;
  commands['migrate:reset'] = commands.beaver;
  commands['migrate:fresh'] = commands.beaver;
  commands['test'] = commands.beaver;

  function span(text, cls) { return '<span class="' + cls + '">' + escapeHtml(text) + '</span>'; }
  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function pluralize(s) {
    if (s.endsWith('s') || s.endsWith('x') || s.endsWith('z')) return s + 'es';
    if (s.endsWith('y')) return s.slice(0, -1) + 'ies';
    return s + 's';
  }
  function err(msg) { return [{ t: '  ✖ ' + msg, c: 'c-str' }]; }

  function appendLine(html, cls) {
    var line = document.createElement('div');
    line.className = 'term-line ' + (cls || '');
    line.innerHTML = html;
    body.appendChild(line);
    body.scrollTop = body.scrollHeight;
    return line;
  }

  function appendLines(lines, opts) {
    opts = opts || {};
    var animate = !!opts.animate;
    var speed = opts.speed || 40;
    var charSpeed = opts.charSpeed || 12;
    var onDone = typeof opts.onDone === 'function' ? opts.onDone : null;

    if (!animate) {
      lines.forEach(function (l) {
        if (l.raw) appendLine(l.t, l.c);
        else appendLine(escapeHtml(l.t), l.c);
      });
      if (onDone) onDone();
      return;
    }

    var i = 0;

    function nextLine() {
      if (i >= lines.length) {
        if (onDone) onDone();
        return;
      }
      var l = lines[i];
      var lineEl = appendLine('', l.c || '');

      if (l.raw) {
        lineEl.style.opacity = '0';
        lineEl.innerHTML = l.t;
        lineEl.style.transition = 'opacity .15s';
        requestAnimationFrame(function () { lineEl.style.opacity = '1'; });
        i++;
        setTimeout(nextLine, speed);
      } else {
        var plain = l.t;
        var chars = String(plain).split('');
        var k = 0;
        function typeChar() {
          if (k >= chars.length) {
            i++;
            setTimeout(nextLine, speed);
            return;
          }
          lineEl.textContent += chars[k];
          body.scrollTop = body.scrollHeight;
          k++;
          setTimeout(typeChar, charSpeed);
        }
        typeChar();
      }
    }

    nextLine();
  }

  function showPrompt(value) {
    var prompt = document.createElement('div');
    prompt.className = 'term-line term-prompt';
    prompt.innerHTML =
      '<span class="term-user">$</span>' +
      '<input class="term-input" type="text" autocomplete="off" spellcheck="false" ' +
      'value="' + escapeHtml(value || '') + '">';
    body.appendChild(prompt);
    body.scrollTop = body.scrollHeight;

    var input = prompt.querySelector('.term-input');
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);

    input.addEventListener('keydown', onInputKey);
    input.addEventListener('input', function () {
      state.draft = input.value;
      state.histIdx = -1;
    });

    return input;
  }

  function onInputKey(e) {
    var input = e.target;

    if (e.key === 'Enter') {
      e.preventDefault();
      var text = input.value.trim();
      var prompt = input.parentNode;
      prompt.innerHTML = '<span class="term-user">$</span><span>' + escapeHtml(input.value) + '</span>';

      state.booting = false;

      if (text) state.history.push(text);
      state.histIdx = -1;
      state.draft = '';

      if (!text) { showPrompt(''); return; }
      runCommand(text);
      return;
    }

    if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (state.history.length === 0) return;
      if (state.histIdx === -1) state.histIdx = state.history.length;
      state.histIdx = Math.max(0, state.histIdx - 1);
      input.value = state.history[state.histIdx];
      input.setSelectionRange(input.value.length, input.value.length);
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (state.history.length === 0 || state.histIdx === -1) return;
      state.histIdx = Math.min(state.history.length, state.histIdx + 1);
      if (state.histIdx === state.history.length) {
        input.value = state.draft || '';
      } else {
        input.value = state.history[state.histIdx];
      }
      input.setSelectionRange(input.value.length, input.value.length);
      return;
    }

    if (e.key === 'Tab') {
      e.preventDefault();
      var val = input.value.trim();

      if (/^cat\s/i.test(val)) {
        var filesList = ['routes/web.php', 'composer.json', '.env', 'readme.md'];
        var fMatches = filesList.filter(function (f) {
          return f.toLowerCase().indexOf(val.slice(4).toLowerCase()) === 0;
        });
        if (fMatches.length === 1) input.value = 'cat ' + fMatches[0];
        else if (fMatches.length > 1) appendLine(escapeHtml('  ' + fMatches.join('   ')), 'c-out');
        return;
      }

      var v = val.toLowerCase();
      var top = Object.keys(commands).filter(function (k) {
        return k.indexOf(v) === 0 && k.indexOf(':') === -1;
      });
      var subs = ['make:model', 'make:controller', 'make:migration', 'make:seeder',
        'make:middleware', 'make:request', 'migrate:rollback', 'migrate:fresh',
        'db:seed', 'db:wipe', 'db:status'];
      var subMatches = subs.filter(function (k) { return k.indexOf(v) === 0; });

      var all = top.concat(subMatches);
      if (all.length === 1) input.value = all[0];
      else if (all.length > 1) appendLine(escapeHtml('  ' + all.join('   ')), 'c-out');
      return;
    }

    if (e.key === 'l' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      body.innerHTML = '';
      showPrompt('');
    }
  }

  function runCommand(text, opts) {
    opts = opts || {};
    var parts = text.split(/\s+/);
    var cmd = parts[0].toLowerCase();
    var args = parts.slice(1);

    var handler = commands[cmd];
    var result;

    if (cmd === 'beaver') {
      var sub = (args[0] || '').toLowerCase();
      if (sub === 'db:seed') return runCommand('db:seed ' + args.slice(1).join(' '), opts);
      if (sub === 'db:wipe') return runCommand('db:wipe', opts);
      if (sub === 'db:status') return runCommand('db:status', opts);
      if (sub === 'ls') return runCommand('ls', opts);
      if (sub === 'pwd') return runCommand('pwd', opts);
      if (sub === 'cat') return runCommand('cat ' + args.slice(1).join(' '), opts);
      if (sub === 'about') return runCommand('about', opts);
      if (sub === 'clear') return runCommand('clear', opts);
      result = commands.beaver.run(args);

    } else if (cmd === 'serve') {
      result = commands.beaver.run(['serve']);
    } else if (cmd === 'make:model') {
      result = commands.beaver.run(['make:model'].concat(args));
    } else if (cmd === 'make:controller') {
      result = commands.beaver.run(['make:controller'].concat(args));
    } else if (cmd === 'make:migration') {
      result = commands.beaver.run(['make:migration'].concat(args));
    } else if (cmd === 'make:seeder') {
      result = commands.beaver.run(['make:seeder'].concat(args));
    } else if (cmd === 'make:middleware') {
      result = commands.beaver.run(['make:middleware'].concat(args));
    } else if (cmd === 'make:request') {
      result = commands.beaver.run(['make:request'].concat(args));
    } else if (cmd === 'migrate' || cmd === 'migrate:rollback' || cmd === 'migrate:reset' || cmd === 'migrate:fresh') {
      result = commands.beaver.run([cmd].concat(args));
    } else if (cmd === 'test') {
      result = commands.beaver.run(['test'].concat(args));
    } else if (handler) {
      result = handler.run(args);
    } else {
      result = err('Comando não encontrado: ' + cmd + '\nEscreve "help" para ver a lista.');
    }

    if (result === null) {
      showPrompt('');
      if (opts.onDone) opts.onDone();
      return;
    }

    state.busy = true;

    var lines = Array.isArray(result) ? result : result.lines;
    var delay = (result && result.delay) || 200;

    setTimeout(function () {
      appendLines(lines, {
        animate: !!opts.animate,
        speed: opts.speed || 40,
        charSpeed: opts.charSpeed || 12,
        onDone: function () {
          state.busy = false;
          showPrompt('');
          if (opts.onDone) opts.onDone();
        }
      });
    }, delay);
  }

  body.addEventListener('click', function (e) {
    if (e.target.closest('.term-input')) return;
    var input = body.querySelector('.term-input');
    if (input) input.focus();
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      state.booting = true;
      boot(true);
    });
  }

  function boot(isRestart) {
    state.booting = true;

    if (isRestart) {
      body.innerHTML = '';
    } else {
      var hint = document.getElementById('termHelp');
      if (hint) hint.remove();
    }

    var banner = commands.beaver.run(['server']);
    appendLines(banner.lines, {
      animate: true,
      speed: 180,
      charSpeed: 20,
      onDone: function () {
        setTimeout(runAutoHelp, 800);
      }
    });
  }

  function runAutoHelp() {
    var prompt = document.createElement('div');
    prompt.className = 'term-line term-prompt';
    prompt.innerHTML =
      '<span class="term-user">$</span>' +
      '<span class="term-typed"></span>' +
      '<span class="term-caret"></span>';
    body.appendChild(prompt);
    body.scrollTop = body.scrollHeight;

    var typedEl = prompt.querySelector('.term-typed');
    var caretEl = prompt.querySelector('.term-caret');
    var cmd = 'beaver --help';
    var i = 0;

    function typeCommand() {
      if (i < cmd.length) {
        typedEl.textContent += cmd.charAt(i);
        i++;
        body.scrollTop = body.scrollHeight;
        setTimeout(typeCommand, 55);
      } else {
        caretEl.remove();
        state.history.push(cmd);

        setTimeout(function () {
          runCommand(cmd, {
            animate: true,
            speed: 60,
            charSpeed: 14,
            onDone: function () {
              showPrompt('');
              state.booting = false;
            }
          });
        }, 250);
      }
    }
    setTimeout(typeCommand, 400);
  }

  boot();

})();
