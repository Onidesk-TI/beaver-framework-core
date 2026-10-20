<?php

/**
 * InstallCommand — cria um novo projeto Beaver a partir do esqueleto.
 *
 * Uso:
 *   ./beaver install --new=myapp
 *   ./beaver install --new=myapp --path=/tmp
 *   ./beaver install --new=myapp --no-install
 */

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Foundation\Version;

class InstallCommand extends Command
{
    protected string $signature = 'install {--new=} {--path=} {--force} {--no-install} {--no-key}';
    protected string $description = 'Cria um novo projeto Beaver a partir do esqueleto (stubs/app)';

    public function handle(): int
    {
        $name = trim((string) ($this->option('new') ?: ''));
        if ($name === '') {
            $this->error('Falta o nome do projeto. Ex: install --new=myapp');
            return self::INVALID;
        }

        // Normalizar slug: minúsculas, só [a-z0-9-]
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $this->error('Nome inválido — usa letras e números.');
            return self::INVALID;
        }

        // Nome legível (first-letter maiúscula de cada palavra)
        $displayName = ucwords(str_replace(['-', '_'], ' ', $slug));

        $basePath = (string) ($this->option('path') ?: getcwd());
        $target   = rtrim($basePath, '/') . '/' . $slug;

        // A raiz do framework é onde vive o stubs/ — derivar de __DIR__ (src/Console/Commands/)
        $frameworkRoot = dirname(__DIR__, 3);
        $stubPath = $frameworkRoot . '/stubs/app';
        if (!is_dir($stubPath)) {
            $this->error("Esqueleto não encontrado em: {$stubPath}");
            return self::FAILURE;
        }

        // Verificar destino
        if (is_dir($target) && !$this->option('force')) {
            $this->error("Destino já existe: {$target}");
            $this->line('  Usa --force para sobrepor.');
            return self::FAILURE;
        }

        $this->line();
        $this->info('🦫  Beaver — Novo Projeto');
        $this->line("  Nome:    {$displayName}");
        $this->line("  Slug:    {$slug}");
        $this->line("  Destino: {$target}");
        $this->line("  Framework: v" . Version::number() . " (^" . Version::majorMinor() . ")");
        $this->line();

        // 1) Criar destino
        if (!is_dir($target) && !@mkdir($target, 0755, true)) {
            $this->error("Não consegui criar: {$target}");
            return self::FAILURE;
        }

        // 2) Copiar esqueleto
        if (!$this->copiar($stubPath, $target)) {
            $this->error('Falha ao copiar o esqueleto.');
            return self::FAILURE;
        }
        $this->success('Esqueleto copiado');

        // 3) Substituir placeholders
        $substituicoes = [
            '{{ APP_NAME }}'                 => $displayName,
            '{{ APP_SLUG }}'                 => $slug,
            '{{ FRAMEWORK_VERSION_MINOR }}'  => Version::majorMinor(),
            '{{ FRAMEWORK_VERSION }}'        => Version::number(),
        ];

        $this->substituirEmTodo($target, $substituicoes);
        $this->success('Placeholders substituídos');

        // 4) Gerar .env
        $envExample = $target . '/.env.example';
        $envTarget  = $target . '/.env';

        if (is_file($envExample) && !is_file($envTarget)) {
            copy($envExample, $envTarget);

            if (!$this->option('no-key')) {
                $key = 'base64:' . base64_encode(random_bytes(32));
                $content = (string) file_get_contents($envTarget);
                $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $content);
                file_put_contents($envTarget, $content);
                $this->success('.env criado com APP_KEY gerada');
            } else {
                $this->success('.env criado (sem APP_KEY)');
            }
        }

        // 5) Composer install
        if (!$this->option('no-install')) {
            $this->line();
            $this->info('A correr composer install…');

            $composer = $this->encontrarComposer();
            if ($composer === null) {
                $this->warning('Composer não encontrado no PATH. Salta install.');
            } else {
                $cmd = sprintf(
                    'cd %s && %s install --no-interaction --quiet 2>&1',
                    escapeshellarg($target),
                    escapeshellarg($composer)
                );
                passthru($cmd, $code);

                if ($code === 0) {
                    $this->success('Dependências instaladas');
                } else {
                    $this->warning('composer install falhou — corre manualmente.');
                }
            }
        }

        // 6) Próximos passos
        $this->line();
        $this->success("Projeto criado em: {$target}");
        $this->line();
        $this->info('Próximos passos:');
        $this->line("  cd {$target}");
        $this->line('  ./beaver migrate');
        $this->line('  ./beaver serve');
        $this->line();
        $this->line("  Depois abre: http://localhost:9000");
        $this->line();

        return self::SUCCESS;
    }

    /**
     * Copia recursivamente mantendo permissões de execução.
     */
    private function copiar(string $from, string $to): bool
    {
        if (!is_dir($from)) return false;
        if (!is_dir($to) && !@mkdir($to, 0755, true)) return false;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $rel    = substr($item->getPathname(), strlen($from) + 1);
            $target = $to . '/' . $rel;

            if ($item->isDir()) {
                if (!is_dir($target)) @mkdir($target, 0755, true);
            } else {
                copy($item->getPathname(), $target);
                // Preservar executáveis (beaver)
                if (is_executable($item->getPathname())) {
                    @chmod($target, 0755);
                }
            }
        }

        return true;
    }

    /**
     * Substitui placeholders em ficheiros de texto (skip binários).
     */
    private function substituirEmTodo(string $dir, array $map): void
    {
        $skipExt = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'zip', 'tar', 'gz'];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            if (in_array($ext, $skipExt, true)) continue;

            $path = $file->getPathname();
            $content = file_get_contents($path);
            if ($content === false) continue;

            // Só ficheiros de texto (heurística: sem bytes nulos nos primeiros 8KB)
            $sample = substr($content, 0, 8192);
            if (strpos($sample, "\0") !== false) continue;

            $novo = strtr($content, $map);
            if ($novo !== $content) {
                file_put_contents($path, $novo);
            }
        }
    }

    /**
     * Encontra o binário do composer.
     */
    private function encontrarComposer(): ?string
    {
        // 1) PATH
        $which = trim((string) shell_exec('which composer 2>/dev/null'));
        if ($which !== '' && is_executable($which)) return $which;

        // 2) Local no framework
        $local = $this->projectPath . '/composer.phar';
        if (is_file($local)) return 'php ' . escapeshellarg($local);

        return null;
    }
}
