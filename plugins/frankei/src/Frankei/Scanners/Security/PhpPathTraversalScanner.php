<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpPathTraversalScanner implements CodeScannerInterface
{
    public function id(): string
    {
        return 'php-pathtraversal';
    }
    public function name(): string
    {
        return 'Path Traversal';
    }
    public function category(): string
    {
        return 'security';
    }
    public function defaultSeverity(): string
    {
        return 'high';
    }

    private array $fileFns = [
        'file_get_contents',
        'file_put_contents',
        'fopen',
        'readfile',
        'unlink',
        'copy',
        'rename',
        'mkdir',
        'rmdir',
        'scandir',
        'glob',
        'is_file',
        'is_dir',
        'filesize',
        'filemtime',
        'stat',
        'touch',
        'chmod',
        'chown',
        'realpath',
        'readlink',
        'parse_ini_file',
    ];

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                $ctxLines = max(1, min(10, (int) $ctx->opt('contextLines', 3)));

                $prevLines = array_slice($lines, max(0, $n - $ctxLines), min($ctxLines, $n));
                $f = $this->checkLine($line, $rel, $n, $prevLines);
                if ($f !== null) $findings[] = $f;
            }
        }

        return $findings;
    }

    private function checkLine(string $line, string $file, int $n, array $prevLines = []): ?Finding
    {
        // Só alertar se houver input do utilizador direto ou ../
        $hasUserInput = (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE|FILES)/', $line);
        $hasDotDot    = (bool) preg_match('#(^|[^.])\.\./(?![\./])#', $line) || (bool) preg_match('#\.\.\\[a-zA-Z0-9_]#', $line);

        // Contexto: $file = $_GET['file']; ... readfile($file)
        if (!$hasUserInput && !empty($prevLines)) {
            $tainted = [];
            foreach ($prevLines as $pl) {
                if (preg_match_all('/\$(\w+)\s*=\s*[^;]*\$_(GET|POST|REQUEST|COOKIE|FILES)/', $pl, $m)) {
                    foreach ($m[1] as $var) $tainted[$var] = true;
                }
            }
            foreach (array_keys($tainted) as $var) {
                if (preg_match('/\$' . preg_quote($var, '/') . '\b/', $line)) {
                    $hasUserInput = true;
                    break;
                }
            }
        }

        if (!$hasUserInput && !$hasDotDot) return null;

        // Ignorar linhas de escrita em /tmp
        if (str_contains($line, "'/tmp/") || str_contains($line, '"/tmp/')) {
            if (!$hasUserInput) return null;
        }

        // Ignorar linhas que são strings (código de exemplo)
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // Ignorar paths com constantes/helpers do framework
        if (preg_match('/__DIR__|__FILE__|basePath|base_path|framework_root|storage_path|plugin_path|dirname\s*\(/', $line)) {
            return null;
        }

        $fn = '';

        // Funções de ficheiro com superglobals (direta OU variavel tainted)
        if (preg_match('/\b(file_get_contents|file_put_contents|fopen|readfile|unlink|include|require|copy|rename|mkdir|rmdir|scandir|glob|is_file|is_dir|filesize|filemtime|stat|touch|chmod|chown|realpath|readlink|parse_ini_file)\s*\([^)]*\$\w+/i', $line, $m)) {
            $fn = strtolower($m[1]);
        } elseif ($hasDotDot && preg_match('/\$[A-Za-z_]/', $line)) {
            $fn = '..';
        } else {
            return null;
        }

        if (preg_match('/(basename|realpath)\s*\(/', $line)) return null;

        return $this->finding(
            $file,
            $n,
            $line,
            $fn,
            'Path com input do utilizador ou "../" controlavel.'
        );
    }

    private function isSafe(string $line): bool
    {
        return str_contains($line, 'basename(')
            || str_contains($line, 'realpath(')
            || str_contains($line, 'pathinfo(');
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner: $this->id(),
            category: $this->category(),
            severity: 'high',
            title: 'Path Traversal em ' . $file . ':' . $n,
            description: $desc . ' (' . $fn . ')',
            suggestion: "Restringe o caminho a uma base segura com basename() + realpath() + verificacao de prefixo.",
            impact: 'Um atacante pode ler ou escrever ficheiros fora da pasta esperada.',
            reference: 'https://owasp.org/www-community/attacks/Path_Traversal',
            data: ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
