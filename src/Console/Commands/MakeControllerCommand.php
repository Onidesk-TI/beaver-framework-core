<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller {name} {--resource} {--force}';
    protected string $description = 'Cria um novo controller em app/Controllers/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Falta o nome do controller. Ex: make:controller ProdutoController');
            return self::INVALID;
        }

        // Normalizar: garantir sufixo "Controller"
        $className = preg_replace('/Controller$/', '', $name) . 'Controller';

        $dir  = $this->projectPath . '/app/Controllers';
        $file = $dir . '/' . $className . '.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->error("Não consegui criar a pasta: {$dir}");
            return self::FAILURE;
        }

        if (is_file($file) && !$this->option('force')) {
            $this->error("Já existe: {$file}  (usa --force para substituir)");
            return self::FAILURE;
        }

        $resource = (bool) $this->option('resource');

        $body = $resource
            ? $this->resourceTemplate($className)
            : $this->plainTemplate($className);

        file_put_contents($file, $body);

        $this->success("Criado: app/Controllers/{$className}.php");
        if ($resource) {
            $this->line('  (modo --resource: inclui index/store/show/update/destroy)');
        }

        return self::SUCCESS;
    }

    private function plainTemplate(string $class): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use Beaver\Http\Request;
use Beaver\Http\Response;

class {$class}
{
    public function index(Request \$req): Response
    {
        return Response::html('<h1>{$class}</h1>');
    }
}
PHP;
    }

    private function resourceTemplate(string $class): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use Beaver\Http\Request;
use Beaver\Http\Response;

class {$class}
{
    public function index(Request \$req): Response
    {
        return Response::html('<h1>{$class} · index</h1>');
    }

    public function store(Request \$req): Response
    {
        return Response::json(['ok' => true]);
    }

    public function show(Request \$req, int \$id): Response
    {
        return Response::json(['id' => \$id]);
    }

    public function update(Request \$req, int \$id): Response
    {
        return Response::json(['ok' => true, 'id' => \$id]);
    }

    public function destroy(Request \$req, int \$id): Response
    {
        return Response::json(['ok' => true, 'deleted' => \$id]);
    }
}
PHP;
    }
}
