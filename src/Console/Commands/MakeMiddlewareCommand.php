<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $signature = 'make:middleware {name} {--force}';
    protected string $description = 'Cria um novo middleware em app/Middleware/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Falta o nome do middleware. Ex: make:middleware AuthMiddleware');
            return self::INVALID;
        }

        $className = preg_replace('/Middleware$/', '', $name) . 'Middleware';

        $dir  = $this->projectPath . '/app/Middleware';
        $file = $dir . '/' . $className . '.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->error("Não consegui criar a pasta: {$dir}");
            return self::FAILURE;
        }

        if (is_file($file) && !$this->option('force')) {
            $this->error("Já existe: {$file}  (usa --force para substituir)");
            return self::FAILURE;
        }

        $body = <<<PHP
<?php

declare(strict_types=1);

namespace App\Middleware;

use Beaver\Http\Request;
use Beaver\Http\Response;

class {$className}
{
    public function handle(Request \$req, callable \$next): Response
    {
        // Antes do pedido chegar ao controller.
        // Ex.: verificar autenticação, rate limit, CORS...

        \$response = \$next(\$req);

        // Depois da resposta (opcional).
        return \$response;
    }
}
PHP;

        file_put_contents($file, $body);

        $this->success("Criado: app/Middleware/{$className}.php");
        return self::SUCCESS;
    }
}
