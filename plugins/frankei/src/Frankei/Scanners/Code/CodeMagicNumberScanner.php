<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Code;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class CodeMagicNumberScanner implements CodeScannerInterface
{
    public function id(): string              { return 'code-magic-number'; }
    public function name(): string            { return 'Numeros magicos'; }
    public function category(): string        { return 'style'; }
    public function defaultSeverity(): string { return 'info'; }

    /** Numeros conhecidos que nao merecem alerta */
    private const KNOWN_NUMBERS = [
        // HTTP status
        100, 101, 200, 201, 204, 301, 302, 304, 400, 401, 403, 404, 405,
        409, 422, 429, 500, 502, 503,
        // Permissoes ficheiro (octal + decimal)
        '0700', '0755', '0775', '0777', '0600', '0644', '0664', '0666',
        '033', '022', '0333',
        700, 755, 775, 777, 600, 644, 664, 666, 255, 220, 224, 245, 333,
        // Comuns (tempo/bytes)
        1000, 1024, 2048, 3600, 86400, 250, 500, 900, 60, 24, 365,
    ];

    /** Contextos em que um numero nao e "magico" */
    private const SAFE_CONTEXTS = '/\b(chmod|umask|fileperms|http_response_code|status|header|setStatus|str_pad|sprintf|number_format|date|time|mktime)\b/i';

    /** Propriedades CSS que nao sao numeros magicos */
    private const CSS_PROPS = '/\b(font-weight|font-size|line-height|letter-spacing|margin|padding|width|height|z-index|border-radius|opacity|grid|gap|top|left|right|bottom|flex|transform|transition|border|outline|box-shadow)\s*:/i';

    /** Regras CSS inline (seletor{prop:valor}) */
    private const CSS_RULE = '/\{[^}]*:[^}]*;[^}]*\}/';

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];
        $minLen = max(2, min(10, (int) $ctx->opt('magicNumberMinLength', 3)));

        foreach ($files as $file) {
            $lines = $ctx->readLines($file);
            $rel   = $ctx->relPath($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;
                if (preg_match('/^\s*[\'"]/', $line)) continue;

                // Ignorar linhas com define() ou const
                if (preg_match('/\b(define|const)\s*\(/i', $line)) continue;

                // Ignorar contextos conhecidos (permissoes, HTTP, formatacao)
                if (preg_match(self::SAFE_CONTEXTS, $line)) continue;

                // Ignorar CSS inline (font-weight:800, etc.)
                if (preg_match(self::CSS_PROPS, $line)) continue;
                if (preg_match(self::CSS_RULE, $line)) continue;

                // Ignorar linhas que parecem HTML/CSS (contêm < e > ou muitas chaves)
                if (preg_match('/<[a-z]+[^>]*>/i', $line) && preg_match('/[:;{}]/', $line)) continue;

                // Procurar literais numericos com pelo menos N digitos
                if (preg_match_all('/\b(\d{' . $minLen . ',})\b/', $line, $m)) {
                    foreach ($m[1] as $num) {
                        // Ignorar versoes, IPs, hex, etc.
                        if (strlen($num) >= 8) continue;
                        if (preg_match('/(0x|0b)/', $line)) continue;

                        // Ignorar numeros conhecidos (HTTP, permissoes, etc.)
                        if (in_array($num, self::KNOWN_NUMBERS, false) || in_array((int) $num, self::KNOWN_NUMBERS, true)) {
                            continue;
                        }

                        $findings[] = $this->finding($rel, $n, $line, $num);
                        break; // 1 finding por linha
                    }
                }
            }
        }

        return $findings;
    }

    private function finding(string $file, int $n, string $line, string $num): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'info',
            title:       'Numero magico ' . $num . ' em ' . $file . ':' . $n,
            description: 'Literal numerico sem constante nomeada.',
            suggestion:  "Extrai para constante:\n\nconst MAX_RETRIES = " . $num . ";\n// usa a constante no codigo",
            impact:      'Numeros magicos dificultam manutencao e alteracao de valores.',
            reference:   'https://en.wikipedia.org/wiki/Magic_number_(programming)',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'number' => $num],
        );
    }
}
