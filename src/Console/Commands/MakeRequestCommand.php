<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class MakeRequestCommand extends Command
{
    protected string $signature = 'make:request {name} {--force}';
    protected string $description = 'Cria um novo form request em app/Requests/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Falta o nome do request. Ex: make:request StoreProdutoRequest');
            return self::INVALID;
        }

        $className = preg_replace('/Request$/', '', $name) . 'Request';

        $dir  = $this->projectPath . '/app/Requests';
        $file = $dir . '/' . $className . '.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->error("Não consegui criar a pasta: {$dir}");
            return self::FAILURE;
        }

        if (is_file($file) && !$this->option('force')) {
            $this->error("Já existe: {$file}  (usa --force para substituir)");
            return self::FAILURE;
        }

        file_put_contents($file, $this->template($className));

        $this->success("Criado: app/Requests/{$className}.php");
        return self::SUCCESS;
    }

    private function template(string $class): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Requests;

use Beaver\Http\Request;

class {$class}
{
    /**
     * Regras de validação.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            // 'nome'  => 'required|string|max:255',
            // 'email' => 'required|email',
        ];
    }

    /**
     * Autorização.
     */
    public function authorize(Request \$req): bool
    {
        return true;
    }
}
PHP;
    }
}
