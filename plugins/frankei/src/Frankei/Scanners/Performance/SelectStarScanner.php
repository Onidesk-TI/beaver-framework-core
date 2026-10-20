<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Scanners\Performance;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Finding;

class SelectStarScanner implements CodeScannerInterface
{
    /**
     * Tabelas cujo SELECT * requer atencao:
     * - Sensiveis (PII/segredos)
     * - Grandes (crescem muito)
     */
    private const TABELAS_ATENCAO = [
        // Sensiveis
        'users', 'api_keys', 'api_logs', 'user_sessions', 'user_devices',
        'email_queue', 'moderation_log', 'votes',
        // Grandes
        'jobs', 'scheduled_jobs', 'image_optimizer_log', 'translation_history',
        'threads', 'replies', 'notifications', 'mentions',
    ];

    public function id(): string              { return 'perf-select-star'; }
    public function name(): string            { return 'SELECT * em vez de colunas'; }
    public function category(): string        { return 'performance'; }
    public function defaultSeverity(): string { return 'info'; }

    public function scan(array $files, string $root, array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);
        $findings = [];

        $ignoreById = $ctx->opt('ignoreSelectById', true);
        $asError    = $ctx->opt('selectStarAsError', false);

        // Tabelas que requerem atencao — configuravel pelo utilizador.
        // Se a chave 'attentionTables' NAO veio nas opcoes → usa default.
        // Se veio (mesmo vazia) → respeita (vazio = sem atencao).
        $attentionTables = array_key_exists('attentionTables', $ctx->options)
            ? (array) $ctx->options['attentionTables']
            : self::TABELAS_ATENCAO;

        foreach ($files as $file) {
            $rel = $ctx->relPath($file);

            // Ignorar views/ (configuravel)
            if ($ctx->opt('skipViews', true)) {
                if (preg_match('#(^|/)(views|resources/views|templates)/#i', $rel)) {
                    continue;
                }
            }

            $lines = $ctx->readLines($file);

            foreach ($lines as $n => $line) {
                if ($ctx->isComment($line)) continue;

                $isSelectStar = false;

                // SELECT * FROM (dentro de strings SQL)
                if (preg_match('/["\']\s*SELECT\s+\*\s+FROM/i', $line, $m)) {
                    $isSelectStar = true;
                }
                // SELECT * em queries com concat
                elseif (preg_match('/\bSELECT\s+\*[^a-zA-Z]/i', $line, $m)) {
                    if (str_contains($line, '*/*') || str_contains($line, '/*')) continue;
                    $isSelectStar = true;
                }

                if (!$isSelectStar) continue;

                // Ignorar SELECT * dentro de strings-template (codigo que gera codigo)
                // Heuristica: linhas com "\$" (variavel PHP escapada) sao templates
                if (str_contains($line, '\\$')) {
                    continue;
                }

                // Ignorar SELECT * WHERE id = :x (busca por 1 linha)
                if ($ignoreById) {
                    if (preg_match('/WHERE\s+(\w+\.)?id\s*=\s*[:?\$]/i', $line)) {
                        continue;
                    }
                }

                $findings[] = $this->finding($file, $n, $line, $rel, $asError, $attentionTables);
            }
        }

        return $findings;
    }

    private function finding(
        string $file,
        int $n,
        string $line,
        string $rel,
        bool $asError = false,
        array $attentionTables = []
    ): Finding {
        // Determinar se a tabela requer atencao (sensivel ou grande)
        $attention = false;
        foreach ($attentionTables as $t) {
            if (preg_match('/\b(FROM|JOIN)\s+[`"]?' . preg_quote($t, '/') . '[`"]?\b/i', $line)) {
                $attention = true;
                break;
            }
        }

        return new Finding(
            scanner:     $this->id(),
            category:    $this->category(),
            severity:    $asError ? 'low' : 'info',
            title:       'SELECT * em ' . $rel . ':' . $n,
            description: 'Query usa SELECT * em vez de listar apenas as colunas necessarias.',
            table:       null,
            column:      null,
            impact:      'SELECT * traz dados desnecessarios, aumenta o trafego de rede, memória e tempo de query.',
            suggestion:  "Lista apenas as colunas que precisas:\n\nSELECT id, nome, email FROM users",
            reference:   'https://use-the-index-luke.com/sql/select/select-star',
            data:        [
                'file' => $rel,
                'line' => $n,
                'code' => trim($line),
            ],
            attention:   $attention,
        );
    }
}
