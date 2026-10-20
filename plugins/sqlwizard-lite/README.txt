================================================================================
SqlWizard Lite
================================================================================

Editor visual de schema SQL minimalista para Beaver Framework.

Licença: GPL v3
Requisitos: PHP >= 8.1, Composer, Beaver Framework

================================================================================
PRE-REQUISITOS
================================================================================

Este plugin NAO funciona isolado. Precisa do Beaver Framework instalado.

  1. Beaver Framework (obrigatorio)

     git clone git@github.com:Onidesk-TI/beaver-framework.git
     cd beaver-framework
     composer install

     Ou instala seguindo a documentacao oficial:
     https://github.com/Onidesk-TI/beaver-framework

  2. PHP >= 8.1

  3. Composer

  4. Extensoes PHP necessarias:
       - ext-json
       - ext-pdo

================================================================================
FUNCIONALIDADES
================================================================================

  - Canvas visual
      Arrasta tabelas, edita colunas, define chaves primarias.

  - Relacoes
      Liga tabelas com drag do handle (circulo lateral).

  - Cardinalidades
      1:1, 1:n, n:1, n:n — com badge clicavel na linha.

  - Validacao
      Deteta relacoes duplicadas, ciclos e tabelas sem PK.

  - Highlight do destino
      A tabela alvo acende durante o drag.

  - Modal de edicao
      Clica na badge para mudar cardinalidade ou eliminar.

  - Draft local
      Guardado automaticamente em localStorage.

  - Export/Import JSON
      Compativel com SqlWizard full.

  - Export SQL
      Gera CREATE TABLE para MySQL/PostgreSQL.

  - Interface em Portugues

================================================================================
INSTALACAO
================================================================================

Passo 1 — Instalar o Beaver Framework (se ainda nao tiveres)

  git clone git@github.com:Onidesk-TI/beaver-framework.git
  cd beaver-framework
  composer install

Passo 2 — Clonar este plugin para dentro do Beaver

  cd /caminho/para/beaver-framework/plugins
  git clone git@github.com:Onidesk-TI/sqlwizard-lite.git
  cd sqlwizard-lite
  composer install

Passo 3 — Registar o namespace no composer.json do Beaver

  Edita o ficheiro:
    /caminho/para/beaver-framework/composer.json

  Adiciona dentro do bloco "autoload" > "psr-4":

    "Beaver\\Plugins\\SqlWizardLite\\": "plugins/sqlwizard-lite/src/",

  Exemplo do bloco completo:

    "autoload": {
        "psr-4": {
            "Beaver\\": "src/",
            "App\\": "app/",
            "Beaver\\Plugins\\SqlWizardLite\\": "plugins/sqlwizard-lite/src/"
        }
    }

Passo 4 — Regenerar o autoload

  cd /caminho/para/beaver-framework
  composer dump-autoload

Passo 5 — Registar as rotas no routes/web.php do Beaver

  Adiciona ao fim do ficheiro:
    /caminho/para/beaver-framework/routes/web.php

    // SqlWizard Lite
    $litePath = '/caminho/para/beaver-framework/plugins/sqlwizard-lite';
    $plugin = new \Beaver\Plugins\SqlWizardLite\SqlWizardLitePlugin($litePath);
    require $litePath . '/routes/web.php';

Passo 6 — Abrir no browser

  http://localhost:9000/sqlwizard-lite

  (ajusta o host/porta conforme o teu Beaver)

================================================================================
UTILIZACAO
================================================================================

  1. Importar um JSON exportado do SqlWizard full
     OU
     Criar tabelas do zero com o botao "+ Nova tabela"

  2. Editar colunas no inspector lateral (nome, tipo, PK)

  3. Criar relacoes:
     arrasta o circulo lateral de uma tabela ate outra

  4. Editar cardinalidade:
     clica na badge "n:1" no meio da linha

  5. Exportar:
     "Guardar JSON" para guardar o draft
     "Exportar SQL"  para gerar DDL

================================================================================
ESTRUTURA
================================================================================

sqlwizard-lite/
  plugin.json                    Metadados Beaver
  composer.json                  Autoload PSR-4
  LICENSE                        GPL v3
  README.txt                     Este ficheiro
  routes/
    web.php                      Rotas HTTP
  src/
    SqlWizardLitePlugin.php      Classe principal
    Http/
      AssetHelper.php            Serve CSS/JS
  resources/
    views/
      index.php                  View principal
    ui/
      css/
        canvas.css               Estilos
      js/
        canvas.js                Logica do canvas
      img/
        sqlwizard.png            Imagem

================================================================================
LICENCA
================================================================================

GPL v3 — ver ficheiro LICENSE.

Qualquer fork ou redistribuicao tem de manter o codigo aberto sob a mesma
licenca.

================================================================================
SOBRE
================================================================================

Versao Lite do SqlWizard:
  https://github.com/Onidesk-TI/sqlwizard

Parte do ecossistema Beaver Framework:
  https://github.com/Onidesk-TI/beaver-framework

Requer Beaver Framework instalado:
  https://github.com/Onidesk-TI/beaver-framework

Feito em Portugal.
