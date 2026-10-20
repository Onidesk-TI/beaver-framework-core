<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class MakeSeederCommand extends Command
{
    protected string $signature = 'make:seeder {name} {--force}';
    protected string $description = 'Cria um novo seeder em database/seeders/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if ($name === '') {
            $this->error('Falta o nome do seeder. Ex: make:seeder ProdutoSeeder');
            return self::INVALID;
        }

        $className = preg_replace('/Seeder$/', '', $name) . 'Seeder';

        $dir  = $this->projectPath . '/database/seeders';
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

        $this->success("Criado: database/seeders/{$className}.php");
        return self::SUCCESS;
    }

    private function template(string $class): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

class {$class}
{
    public function run(): void
    {
        // Insere aqui a lógica de seed.
        //
        // Exemplo:
        //   \$user = App\Models\User::create([
        //       'name'  => 'Admin',
        //       'email' => 'admin@example.com',
        //   ]);
    }
}
PHP;
    }
}
