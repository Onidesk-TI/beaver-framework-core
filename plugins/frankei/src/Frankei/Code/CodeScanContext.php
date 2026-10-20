<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Code;

class CodeScanContext
{
    /** @var string[] */
    public array $files = [];

    /** Diretórios a ignorar */
       /** Diretórios a ignorar */
    public array $ignoreDirs = [
        'vendor', 'node_modules', '.git', 'storage', 'cache',
        'tests', 'test', 'docs', 'build', 'dist', 'public/uploads',
        'backups', '.backups', 'resources/data', 'data/docs',
        'logs', 'tmp', 'temp', 'hand_tests',
    ];

    /** Ficheiros a ignorar (nome exato) */
    public array $ignoreFiles = [
        'documentacao.php',
        'exemplos.php',
    ];

    /** Padrões de path a ignorar (regex) */
    public array $ignorePathPatterns = [
        '/Scanner[s]?\//',  // os próprios scanners do Frankei
    ];

    /** Opções do utilizador (localStorage) */
    public array $options = [];

    public function __construct(
        public string $root,
        public int $maxFileSize = 1048576, // 1MB
        array $options = [],
    ) {
        $this->options = $options;
        $this->files = $this->discoverPhpFiles($root);
    }

    /** Helper: ler opcao com default */
    public function opt(string $key, mixed $default = false): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /** @return string[] */
    private function discoverPhpFiles(string $root): array
    {
        if (!is_dir($root)) return [];

        $files = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            if ($file->getExtension() !== 'php') continue;

            $path = $file->getPathname();
            $rel  = str_replace($root . '/', '', $path);

            // Ignorar diretórios configurados
            $skip = false;
            foreach ($this->ignoreDirs as $ign) {
                // Verifica qualquer segmento do path (não só início)
                $ign = trim($ign, '/');
                if (str_starts_with($rel, $ign . '/')
                    || str_contains($rel, '/' . $ign . '/')
                    || str_ends_with(dirname($rel), '/' . $ign)
                    || dirname($rel) === $ign) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if ($file->getSize() > $this->maxFileSize) continue;

            // Ignorar ficheiros específicos
            if (in_array(basename($path), $this->ignoreFiles, true)) {
                continue;
            }

            // Ignorar por padrão de path
            foreach ($this->ignorePathPatterns as $pat) {
                if (preg_match($pat, $rel)) {
                    continue 2;
                }
            }

            // Filtros configuráveis pelo utilizador (painel Segurança)
            $optionRules = [
                'ignoreVendor'        => '#(^|/)vendor/#',
                'ignoreNodeModules'   => '#(^|/)node_modules/#',
                'ignoreTestsFixtures' => '#(^|/)tests/fixtures/#',
                'ignoreBackups'       => '#(^|/)\.backups/#',
                'ignoreBuild'         => '#(^|/)build/#',
            ];
            foreach ($optionRules as $optName => $re) {
                if ($this->opt($optName, true) && preg_match($re, $rel)) {
                    continue 2;
                }
            }

            $files[] = $path;
        }

        return $files;
    }

    /**
     * Devolve as linhas de um ficheiro com número de linha.
     * @return array<int, string>  [lineNumber => content]
     */
    public function readLines(string $file): array
    {
        $content = @file_get_contents($file);
        if ($content === false) return [];

        $lines = preg_split('/\r?\n/', $content);
        $out = [];
        foreach ($lines as $i => $l) {
            $out[$i + 1] = $l;
        }
        return $out;
    }

    /** Caminho relativo ao root */
    public function relPath(string $file): string
    {
        return str_starts_with($file, $this->root)
            ? ltrim(substr($file, strlen($this->root)), '/')
            : $file;
    }

    /**
     * Verifica se uma linha está dentro de um comentário ou string.
     * Heurística simples: ignora linhas que começam com //, #, * ou <!—
     */
    public function isComment(string $line): bool
    {
        $t = ltrim($line);
        return str_starts_with($t, '//')
            || str_starts_with($t, '#')
            || str_starts_with($t, '*')
            || str_starts_with($t, '/*')
            || str_starts_with($t, '<!--');
    }

    /**
     * Verifica se a linha já contém uma função de escape.
     */
    public function hasEscape(string $line): bool
    {
        $escapes = ['htmlspecialchars', 'htmlentities', 'strip_tags', 'e(', 'esc('];
        foreach ($escapes as $fn) {
            if (str_contains($line, $fn)) return true;
        }
        return false;
    }
}
