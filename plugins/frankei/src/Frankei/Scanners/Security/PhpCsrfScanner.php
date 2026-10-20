<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Security;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class PhpCsrfScanner implements CodeScannerInterface
{
    public function id(): string              { return 'php-csrf'; }
    public function name(): string            { return 'CSRF em formularios'; }
    public function category(): string        { return 'security'; }
    public function defaultSeverity(): string { return 'medium'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $findings = [];

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) continue;
            if (strpos($content, '<form') === false) continue;

            $ctx = new CodeScanContext($root, 1048576, $options);
            $rel = $ctx->relPath($file);
            $lines = $ctx->readLines($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                // Ignorar forms de login/logout/registo (não precisam de CSRF)
                if (preg_match('#/(auth|login|logout|registar|signin|signup|install)#i', $rel)) continue;

                // Ignorar forms de logout no próprio código
                if (preg_match('#action=["\'][^"\']*logout#i', $line)) continue;

                // Ignorar paths de plugins de sistema (não é do utilizador)
                if (preg_match('#/(frankei|beaver-installer|mobile-editor)/#i', $rel)) continue;

                // <form method="post"> sem csrf_token por perto
                if (preg_match('/<form[^>]+method=["\']post["\'][^>]*>/i', $line)) {
                    $proximas = array_slice($lines, $n - 1, 10, true);
                    $temToken = false;
                    foreach ($proximas as $l) {
                        if (stripos($l, 'csrf') !== false || stripos($l, '_token') !== false) {
                            $temToken = true;
                            break;
                        }
                    }

                    if (!$temToken) {
                        $findings[] = new Finding(
                            scanner:     $this->id(),
                            category:    $this->category(),
                            severity:    'medium',
                            title:       'Form POST sem CSRF token em ' . $rel . ':' . $n,
                            description: 'Formulario POST sem campo csrf_token/_token.',
                            suggestion:  "Adiciona um campo escondido com o token CSRF:\n\n<input type=\"hidden\" name=\"_token\" value=\"<?= csrf_token() ?>\">",
                            impact:      'Um atacante pode forcar o utilizador autenticado a submeter o formulario sem o saber (CSRF).',
                            reference:   'https://owasp.org/www-community/attacks/csrf',
                            data:        ['file' => $rel, 'line' => $n, 'code' => trim($line)],
                        );
                    }
                }
            }
        }

        return $findings;
    }
}
