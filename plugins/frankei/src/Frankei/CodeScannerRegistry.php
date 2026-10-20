<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

use Beaver\Plugins\Frankei\Frankei\Code\CodeScannerInterface;
use Beaver\Plugins\Frankei\Frankei\Code\CodeScanContext;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpXssScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpSqlInjectionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpCommandInjectionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpCodeExecutionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpPathTraversalScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpFileInclusionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpDeserializationScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpCsrfScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpSsrScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpOpenRedirectScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Security\PhpXxeScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Performance\NPlusOneScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Performance\SelectStarScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Performance\HeavyLoopScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Passwords\WeakPasswordHashScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Code\CodeTodoScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Code\CodeErrorSuppressionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Code\CodeMagicNumberScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Code\CodeLongFunctionScanner;
use Beaver\Plugins\Frankei\Frankei\Scanners\Code\CodeDeepNestingScanner;

class CodeScannerRegistry
{
    /**
     * Mapeamento scanner_id -> chave de opção booleana.
     * Se a opção existir e for false, o scanner NÃO corre.
     */
    private const SCANNER_TO_OPT = [
        'php-xss'          => 'runXss',
        'php-sqli'         => 'runSqli',
        'php-cmdi'         => 'runCmdi',
        'php-code-exec'    => 'runCodeExec',
        'php-csrf'         => 'runCsrf',
        'php-deserialize'  => 'runDeserialize',
        'php-fileinclusion'=> 'runFileInclusion',
        'php-pathtraversal'=> 'runPathTraversal',
        'php-openredirect' => 'runOpenRedirect',
        'php-ssrf'         => 'runSsrf',
        'php-xxe'          => 'runXxe',
        // Performance
        'perf-nplus1'      => 'runPerfNplus1',
        'perf-select-star' => 'runPerfSelectStar',
        'perf-heavy-loop'  => 'runPerfHeavyLoop',
        // Passwords
        'weak-password-hash' => 'runWeakPasswordHash',
        // Codigo & Qualidade
        'code-todo'              => 'runCodeTodo',
        'code-error-suppression' => 'runCodeErrorSuppression',
        'code-magic-number'      => 'runCodeMagicNumber',
        'code-long-function'     => 'runCodeLongFunction',
        'code-deep-nesting'      => 'runCodeDeepNesting',
    ];

    /** @var array<string, CodeScannerInterface> */
    private array $scanners = [];

    public function __construct()
    {
        // Segurança
        $this->register(new PhpXssScanner());
        $this->register(new PhpSqlInjectionScanner());
        $this->register(new PhpCommandInjectionScanner());
        $this->register(new PhpCodeExecutionScanner());
        $this->register(new PhpPathTraversalScanner());
        $this->register(new PhpFileInclusionScanner());
        $this->register(new PhpDeserializationScanner());
        $this->register(new PhpCsrfScanner());
        $this->register(new PhpSsrScanner());
        $this->register(new PhpOpenRedirectScanner());
        $this->register(new PhpXxeScanner());

        // Palavras-passe
        // Desempenho
        $this->register(new NPlusOneScanner());
        $this->register(new SelectStarScanner());
        $this->register(new HeavyLoopScanner());

        // Palavras-passe
        $this->register(new WeakPasswordHashScanner());

        // Codigo & Qualidade
        $this->register(new CodeTodoScanner());
        $this->register(new CodeErrorSuppressionScanner());
        $this->register(new CodeMagicNumberScanner());
        $this->register(new CodeLongFunctionScanner());
        $this->register(new CodeDeepNestingScanner());
    }

    public function register(CodeScannerInterface $scanner): void
    {
        $this->scanners[$scanner->id()] = $scanner;
    }

    public function byId(string $id): ?CodeScannerInterface
    {
        return $this->scanners[$id] ?? null;
    }

    /** @return array<string, CodeScannerInterface> */
    public function all(): array
    {
        return $this->scanners;
    }

    public function metadata(): array
    {
        $out = [];
        foreach ($this->scanners as $id => $s) {
            $out[] = [
                'id'               => $id,
                'name'             => $s->name(),
                'category'         => $s->category(),
                'default_severity' => $s->defaultSeverity(),
            ];
        }
        return $out;
    }

    /**
     * Corre os scanners pedidos sobre o projeto.
     *
     * @param string[] $scannerIds   IDs dos scanners a correr (vazio = todos)
     * @return array{findings: array, stats: array, errors: array}
     */
    public function run(string $root, array $scannerIds = [], array $options = []): array
    {
        $ctx = new CodeScanContext($root, 1048576, $options);

        $toRun = $scannerIds === []
            ? $this->scanners
            : array_filter(array_map(fn($id) => $this->byId($id), $scannerIds));

        // Filtrar scanners desativados pelo utilizador
        $toRun = array_filter($toRun, function (CodeScannerInterface $scanner) use ($options): bool {
            $optKey = self::SCANNER_TO_OPT[$scanner->id()] ?? null;
            if ($optKey === null) {
                return true; // sem mapeamento → corre sempre
            }
            return ($options[$optKey] ?? true) !== false;
        });

        $findings = [];
        $errors   = [];
        $t0       = microtime(true);

        foreach ($toRun as $id => $scanner) {
            try {
                foreach ($scanner->scan($ctx->files, $root, $options) as $f) {
                    $findings[] = $f->toArray();
                }
            } catch (\Throwable $e) {
                $errors[] = ['scanner' => $id, 'message' => $e->getMessage()];
            }
        }

        // Ordenar por severidade (critical -> info) antes de truncar
        // Garante que os findings importantes ficam nos primeiros
        $sevOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3, 'info' => 4];
        usort($findings, function ($a, $b) use ($sevOrder) {
            $sa = $sevOrder[$a['severity'] ?? 'info'] ?? 99;
            $sb = $sevOrder[$b['severity'] ?? 'info'] ?? 99;
            return $sa - $sb;
        });

        // Limite de findings (configuravel; 0 = sem limite)
        $maxFindings         = (int) ($options['maxFindings'] ?? 0);
        $truncado            = false;
        $totalBeforeTruncate = count($findings);

        if ($maxFindings > 0 && count($findings) > $maxFindings) {
            $findings = array_slice($findings, 0, $maxFindings);
            $truncado = true;
            $errors[] = [
                'scanner' => '*',
                'message' => "Resultado truncado a {$maxFindings} findings (limite configuravel).",
            ];
        }

        // Stats
        $bySeverity = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0];
        $byCategory = [];
        foreach ($findings as $f) {
            $sev = $f['severity'] ?? 'info';
            $cat = $f['category'] ?? 'other';
            if (isset($bySeverity[$sev])) $bySeverity[$sev]++;
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
        }

        return [
            'findings' => $findings,
            'stats'    => [
                'total'        => count($findings),
                'by_severity'  => $bySeverity,
                'by_category'  => $byCategory,
                'elapsed_ms'   => (int) round((microtime(true) - $t0) * 1000),
                'files_scanned'=> count($ctx->files),
                'truncated'              => $truncado,
                'max_findings'           => $maxFindings,
                'total_before_truncation'=> $totalBeforeTruncate,
            ],
            'errors'   => $errors,
        ];
    }
}
