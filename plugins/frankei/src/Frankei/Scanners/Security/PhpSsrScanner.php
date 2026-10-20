<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpSsrScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-ssrf'; }
    public function name(): string            { return 'SSRF em pedidos HTTP'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'high'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;
                // Passar as 3 linhas anteriores para detetar $url = $_GET[..]; ...file_get_contents($url)
                
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
        // Ignorar linhas que são strings
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // Tem de ter superglobal OU variavel que veio de superglobal nas 3 linhas anteriores
        $hasUserInput = (bool) preg_match('/\$_(GET|POST|REQUEST|COOKIE)/', $line);

        // Se não tem superglobal direta, tentar detetar via contexto:
        // $url = $_GET['x'];       <- 3 linhas antes
        // file_get_contents($url)  <- linha atual
        if (!$hasUserInput && !empty($prevLines)) {
            // Extrair nomes de variaveis atribuidas a partir de superglobals
            $tainted = [];
            foreach ($prevLines as $pl) {
                if (preg_match_all('/\$(\w+)\s*=\s*[^;]*\$_(GET|POST|REQUEST|COOKIE)/', $pl, $m)) {
                    foreach ($m[1] as $var) {
                        $tainted[$var] = true;
                    }
                }
            }
            // Se a linha atual usa alguma dessas variaveis, marcar como tainted
            foreach (array_keys($tainted) as $var) {
                if (preg_match('/\$' . preg_quote($var, '/') . '\b/', $line)) {
                    $hasUserInput = true;
                    break;
                }
            }
        }

        if (!$hasUserInput) return null;

        // curl_* com URL controlada (direta ou variavel tainted)
        if (preg_match('/curl_(init|setopt)\s*\([^)]*\$\w+/i', $line)) {
            return $this->finding($file, $n, $line, 'curl',
                'curl com URL controlada pelo utilizador.'
            );
        }

        // file_get_contents com URL (direta ou variavel tainted)
        if (preg_match('/file_get_contents\s*\([^)]*\$\w+/i', $line)) {
            return $this->finding($file, $n, $line, 'file_get_contents',
                'file_get_contents com URL controlada pelo utilizador.'
            );
        }

        // fopen / fsockopen / stream (direta ou variavel tainted)
        if (preg_match('/\b(fopen|fsockopen|readfile|get_headers)\s*\([^)]*\$\w+/i', $line, $m)) {
            return $this->finding($file, $n, $line, strtolower($m[1]),
                $m[1] . '() com URL controlada pelo utilizador.'
            );
        }

        return null;
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'high',
            title:       'Possivel SSRF em ' . $file . ':' . $n,
            description: $desc . ' (' . $fn . ')',
            suggestion:  "Valida a URL contra uma allowlist:\n\n\$host = parse_url(\$url, PHP_URL_HOST);\n\$allowed = ['api.exemplo.com'];\nif (!in_array(\$host, \$allowed, true)) {\n    throw new \\RuntimeException('Host nao permitido');\n}",
            impact:      'Um atacante pode fazer o servidor aceder a recursos internos (metadata cloud, admin APIs, BD).',
            reference:   'https://owasp.org/www-community/attacks/Server_Side_Request_Forgery',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
