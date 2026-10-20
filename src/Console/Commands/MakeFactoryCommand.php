<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class MakeFactoryCommand extends Command
{
    protected string $signature = 'make:factory {name} {--force}';
    protected string $description = 'Cria uma nova factory em database/factories/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Falta o nome da factory. Ex: make:factory Produto');
            return self::INVALID;
        }

        $className = preg_replace('/Factory$/', '', $name) . 'Factory';

        $dir  = $this->projectPath . '/database/factories';
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

        $this->success("Criado: database/factories/{$className}.php");
        return self::SUCCESS;
    }

    private function template(string $class): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

class {$class}
{
    /**
     * Definição dos atributos.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'nome'  => 'Produto de teste',
            // 'preco' => 9.99,
        ];
    }

    /**
     * Cria N registos.
     */
    public function create(int \$count = 1): array
    {
        \$created = [];

        for (\$i = 0; \$i < \$count; \$i++) {
            \$created[] = [
                // ... \$this->definition() com valores únicos
            ];
        }

        return \$created;
    }
}
PHP;
    }
}
