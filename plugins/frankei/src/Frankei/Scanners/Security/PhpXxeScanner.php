<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpXxeScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-xxe'; }
    public function name(): string            { return 'XXE (XML External Entity)'; }
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
                $f = $this->checkLine($line, $rel, $n);
                if ($f !== null) $findings[] = $f;
            }
        }

        return $findings;
    }

    private function checkLine(string $line, string $file, int $n): ?Finding
    {
        // Ignorar strings
        if (preg_match('/^\s*[\'"]/', $line)) return null;

        // simplexml_load_*
        if (preg_match('/\bsimplexml_load_(string|file)\s*\(/i', $line, $m)) {
            if ($this->temProtecao($line)) return null;

            return $this->finding($file, $n, $line, strtolower($m[1]),
                'simplexml sem protecao contra XXE.'
            );
        }

        // DOMDocument::loadXML
        if (preg_match('/->loadXML\s*\(/i', $line)) {
            if ($this->temProtecao($line)) return null;

            return $this->finding($file, $n, $line, 'loadXML',
                'DOMDocument::loadXML sem protecao contra XXE.'
            );
        }

        // new DOMDocument + load
        if (preg_match('/\b(new\s+DOMDocument|->load)\s*\([^)]*\$_(GET|POST|REQUEST|FILES)/i', $line)) {
            if ($this->temProtecao($line)) return null;

            return $this->finding($file, $n, $line, 'DOMDocument',
                'DOMDocument a carregar XML controlado pelo utilizador.'
            );
        }

        return null;
    }

    private function temProtecao(string $line): bool
    {
        // Protecoes reais: NONET (sem rede), disable_entity_loader (legacy), resolveExternals=false
        // ATENCAO: LIBXML_NOENT NAO e protecao — pelo contrario, ATIVA expansao de entidades (XXE)
        if (str_contains($line, 'LIBXML_NOENT')) {
            return false; // e o vetor, nao a defesa
        }

        return str_contains($line, 'LIBXML_NONET')
            || str_contains($line, 'libxml_disable_entity_loader')
            || str_contains($line, 'resolveExternals');
    }

    private function finding(string $file, int $n, string $line, string $fn, string $desc): Finding
    {
        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    'high',
            title:       'Possivel XXE em ' . $file . ':' . $n,
            description: $desc . ' (' . $fn . ')',
            suggestion:  "Desativa entidades externas:\n\n\$xml = simplexml_load_string(\$input, 'SimpleXMLElement', LIBXML_NONET);\n\n// Em PHP 8+, libxml >= 2.9 ja desativa entidades externas por defeito.\n// NUNCA uses LIBXML_NOENT (ativa expansao de entidades = XXE).",
            impact:      'Um atacante pode ler ficheiros locais, fazer SSRF ou exfiltrar dados via entidades externas.',
            reference:   'https://owasp.org/www-community/vulnerabilities/XML_External_Entity_(XXE)_Processing',
            data:        ['file' => $file, 'line' => $n, 'code' => trim($line), 'function' => $fn],
        );
    }
}
