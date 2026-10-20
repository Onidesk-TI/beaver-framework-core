<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Performance;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class NPlusOneScanner implements CodeScannerInterface
{
    public function id(): string
    {
        return 'perf-nplus1';
    }
    public function name(): string
    {
        return 'Queries N+1 em loops';
    }
    public function category(): string
    {
        return 'performance';
    }
    public function defaultSeverity(): string
    {
        return 'medium';
    }

    /** Padroes de acesso a BD */
    private array $dbPatterns = [
        '->query\s*\(',
        '->exec\s*\(',
        '->prepare\s*\(',
        '->find\s*\(',
        '->findBy\s*\(',
        '->all\s*\(',
        '->get\s*\(',
        '->first\s*\(',
        '->count\s*\(',
        'DB::',
        'Db::',
        '->select\s*\(',
        '->where\s*\(',
        '->fetchAll\s*\(',
    ];

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        foreach ($files as $file) {
            $rel = $ctx->relPath($file);

            // Ignorar ficheiros de setup/migrations/seeds (configuravel)
            if ($ctx->opt('skipScripts', true)) {
                if (preg_match('#^(scripts|database/migrations|database/seeders|src/Database/Migrations|\.backups)/#i', $rel)) {
                    continue;
                }
                if (preg_match('#(setup-|migrate|seed|skeleton-|Schema\.php|Migration)#i', basename($rel))) {
                    continue;
                }
            }

            $lines = $ctx->readLines($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                // Detetar inicio de loop
                if (!preg_match('/\b(foreach|for|while|do)\b/', $line)) continue;

                // Ignorar loops triviais (configuravel)
                if ($ctx->opt('skipTrivialLoops', true)) {
                    if (preg_match('/foreach\s*\(\s*\$(admins?|planos?|roles?|users?|configs?|settings?|opcoes?|options?|items?|listas?|categorias?|dirs?|files?|paths?|entries?|nodes?|keys?|values?|columns?|campos?|linhas?|rows?|elementos?)\b/i', $line)) {
                        continue;
                    }
                }

                // Ignorar se a query está na própria linha do foreach
                $procurarQuery = true;
                foreach ($this->dbPatterns as $pat) {
                    if (preg_match('/' . $pat . '/', $line)) {
                        $procurarQuery = false;
                        break;
                    }
                }
                if (!$procurarQuery) continue;

                // Ver as proximas 15 linhas dentro do loop
                $loopDepth = 0;
                $hasQuery  = false;
                $queryLine = 0;
                $queryCode = '';

                for ($i = $n; $i < min($n + 15, count($lines) + 1); $i++) {
                    $l = $lines[$i] ?? '';

                    $loopDepth += substr_count($l, '{');
                    $loopDepth -= substr_count($l, '}');

                    if ($i > $n && $loopDepth <= 0) break;

                    // Ignorar DDL (configuravel)
                    if ($ctx->opt('skipDdl', true) && preg_match('/\b(OPTIMIZE|PRAGMA|CREATE|DROP|ALTER|TRUNCATE|ANALYZE|VACUUM)\s+/i', $l)) {
                        continue;
                    }

                    if (!$hasQuery) {
                        foreach ($this->dbPatterns as $pat) {
                            if (preg_match('/' . $pat . '/', $l)) {
                                $hasQuery  = true;
                                $queryLine = $i;
                                $queryCode = trim($l);
                                break;
                            }
                        }
                    }
                }

                // B: ignorar SELECTs triviais (find/por ID) — falso positivo comum
                if ($hasQuery && $ctx->opt('skipTrivialLoops', true)) {
                    $qc = $queryCode;

                    // $this->find($x), ->findById($x), ->first($x), ->get($x) — 1 registo
                    if (preg_match('/->(find|findById|findOne|first|get|fetchOne|load)\s*\(/i', $qc)
                        && !preg_match('/->(find|get|load)All\s*\(/i', $qc)) {
                        $hasQuery = false;
                    }

                    // SELECT ... WHERE id = :x / ? / $x (busca por PK)
                    if ($hasQuery
                        && preg_match('/\bSELECT\b/i', $qc)
                        && preg_match('/\bWHERE\b[^;]*\b(id|uuid|pk|key)\s*=\s*[:?\$]/i', $qc)) {
                        $hasQuery = false;
                    }
                }

                if ($hasQuery) {
                    $findings[] = new Finding(
                        scanner: $this->id(),
                        category: $this->category(),
                        severity: 'medium',
                        title: 'Possivel N+1 em ' . $rel . ':' . $n,
                        description: 'Query de BD dentro de um loop (linha ' . $queryLine . '). Pode executar N queries em vez de 1.',
                        table: null,
                        column: null,
                        impact: 'Com muitos registos, o loop faz 1 query por iteracao — degrada drasticamente o desempenho.',
                        suggestion: "Carrega os dados antes do loop (eager loading):\n\n\$ids = array_column(\$items, 'id');\n\$dados = \$repo->findByIds(\$ids);  // 1 query\n\nforeach (\$items as \$item) {\n    \$detalhe = \$dados[\$item['id']] ?? null;\n}",
                        reference: 'https://stackoverflow.com/questions/97197/what-is-the-n1-selects-problem',
                        data: [
                            'file'       => $rel,
                            'line'       => $n,
                            'code'       => trim($line),
                            'query_line' => $queryLine,
                            'query_code' => $queryCode,
                        ],
                    );
                }
            }
        }

        return $findings;
    }
}
