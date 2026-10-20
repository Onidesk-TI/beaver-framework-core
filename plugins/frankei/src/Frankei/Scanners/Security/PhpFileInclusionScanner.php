<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpFileInclusionScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-fileinclusion'; }
    public function name(): string            { return 'LFI / RFI (File Inclusion)'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'critical'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;
                $f = $this->checkLine($line, $rel, $n);
                if ($f !== null) $findings[] = $f;
            }
        }

        return $findings;
    }

    private function checkLine(string $line, string $file, int $n): ?Finding
    {
        // Ignorar linhas que são strings (código de exemplo)
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // Só alertar se houver superglobal na linha
        $hasUserInput = (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE|FILES)/', $line);

        if (!$hasUserInput) return null;

        // include/require com superglobal
        if (preg_match('/\b(include|include_once|require|require_once)\s*\(?\s*\$_(GET|POST|REQUEST|COOKIE)/i', $line, $m)) {
            return $this->finding($file, $n, $line, strtolower($m[1]),
                'Inclusao de ficheiro com input direto do utilizador - LFI/RFI critico.'
            );
        }

        // include/require de variável que vem de superglobal (concatenada)
        if (preg_match('/\b(include|include_once|require|require_once)\s*\(?[^;]*\$_/i', $line, $m)) {
            return $this->finding($file, $n, $line, strtolower($m[1]),
                'Inclusao de ficheiro construida com input do utilizador.'
            );
        }

        return null;
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'critical',
            title:       'File Inclusion em ' . $file . ':' . $n,
            description: $desc . ' (' . $fn . ')',
            suggestion:  "Nunca incluir ficheiros diretamente a partir de input. Usa uma allowlist:\n\n\$allowed = ['home' => 'pages/home.php', 'about' => 'pages/about.php'];\n\$page = \$allowed[\$_GET['page']] ?? 'pages/404.php';\ninclude __DIR__ . '/' . \$page;",
            impact:      'LFI permite ler ficheiros locais (config, /etc/passwd). RFI permite executar código remoto — o servidor fica totalmente comprometido.',
            reference:   'https://owasp.org/www-community/attacks/Path_Traversal',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
