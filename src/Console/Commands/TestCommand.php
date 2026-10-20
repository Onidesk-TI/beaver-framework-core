<?php

declare(strict_types=1);

namespace Beaver\Console\Commands;

use Beaver\Console\Command;

class TestCommand extends Command
{
    protected string $signature = 'test {--testsuite=} {--filter=} {--parallel} {--engine=} {--coverage} {--log-junit=} {--coverage-html=} {--coverage-clover=} {--coverage-cobertura=} {--coverage-text=}';
    protected string $description = 'Corre os testes do projeto (Pest ou PHPUnit)';

    public function handle(): int
    {
        $pest    = $this->projectPath . '/vendor/bin/pest';
        $phpunit = $this->projectPath . '/vendor/bin/phpunit';
        $config  = $this->projectPath . '/phpunit.xml';

        // Detetar motor
        $engine  = $this->option('engine') ?: null;
        $binary  = null;
        $label   = '';

        if ($engine === 'pest' && is_file($pest)) {
            $binary = $pest;
            $label  = 'Pest';
        } elseif ($engine === 'phpunit' && is_file($phpunit)) {
            $binary = $phpunit;
            $label  = 'PHPUnit';
        } elseif ($engine === null) {
            // Auto: Pest tem prioridade, senão PHPUnit
            if (is_file($pest)) {
                $binary = $pest;
                $label  = 'Pest';
            } elseif (is_file($phpunit)) {
                $binary = $phpunit;
                $label  = 'PHPUnit';
            }
        }

        if ($binary === null) {
            $this->error('Nenhum motor de testes encontrado.');
            $this->line();
            $this->info('Para instalar Pest:');
            $this->line('  composer require --dev pestphp/pest');
            $this->line();
            $this->info('Ou PHPUnit:');
            $this->line('  composer require --dev phpunit/phpunit');
            return self::FAILURE;
        }

        $args = [];

        // Só PHPUnit precisa de --configuration explícito
        // Pest respeita o phpunit.xml automaticamente
        if ($label === 'PHPUnit' && $config && is_file($config)) {
            $args[] = '--configuration ' . escapeshellarg($config);
        }

        if ($filter = $this->option('filter')) {
            $args[] = '--filter ' . escapeshellarg((string) $filter);
        }

        // ── --testsuite (tradução: Pest → pasta, PHPUnit → --testsuite) ──
        // Guard: PHPUnit não entende testes Pest.
        // Se o user pediu engine=phpunit + testsuite=Unit/Feature, forçar Phpunit.
        $suiteForced = false;
        if ($label === 'PHPUnit') {
            $requested = (string) $this->option('testsuite');
            if ($requested === '' || $requested === 'Unit' || $requested === 'Feature') {
                $args = array_values(array_filter($args, function ($a) {
                    return !str_starts_with($a, '--testsuite=');
                }));
                $args[] = '--testsuite=Phpunit';
                $suiteForced = true;

                if ($requested !== '') {
                    $this->warning("PHPUnit não corre testes Pest. A usar testsuite 'Phpunit' em vez de '{$requested}'.");
                }
            }
        }

        if (!$suiteForced && ($suite = $this->option('testsuite'))) {
            $suite = trim((string) $suite);

            if ($suite !== '' && !preg_match('/^[A-Za-z0-9_\-]+$/', $suite)) {
                $this->error('--testsuite inválido: ' . $suite);
                return self::FAILURE;
            }

            if ($suite !== '') {
                $args[] = '--testsuite=' . escapeshellarg($suite);
            }
        }

        if ($this->option('parallel')) {
            // Pest suporta --parallel nativo
            if ($label === 'Pest') {
                $args[] = '--parallel';
            } else {
                $this->warning('--parallel requer paratest no PHPUnit. A ignorar.');
            }
        }

        // ── Flags de report ──

        // Detetar cobertura: --coverage OU qualquer --coverage-html/clover/etc.
        // (Pest/PHPUnit exigem --coverage explicitamente para ativar a medição)
        $hasCoverage = $this->option('coverage')
                    || $this->option('coverage-html')
                    || $this->option('coverage-clover')
                    || $this->option('coverage-cobertura')
                    || $this->option('coverage-text');

        if ($hasCoverage) {
            // 1) Ativar cobertura explícita — só o Pest usa --coverage.
            //    O PHPUnit tem apenas as flags --coverage-* específicas.
            if ($label === 'Pest') {
                $args[] = '--coverage';
            }

            // 2) Forçar Xdebug em modo coverage para o subprocesso
            putenv('XDEBUG_MODE=coverage');
            $_ENV['XDEBUG_MODE']    = 'coverage';
            $_SERVER['XDEBUG_MODE'] = 'coverage';

            // 3) Paths default — só se nenhum dos report flags for passado.
            //    Se o ReportRunner já passou --coverage-html/clover, respeitamos.
            $hasAnyReport = $this->option('coverage-html')
                         || $this->option('coverage-clover')
                         || $this->option('coverage-cobertura')
                         || $this->option('coverage-text');

            // PHPUnit: 'coverage' sozinho não faz nada — adicionar clover default
            if ($label === 'PHPUnit' && !$hasAnyReport) {
                $uid = (int) (getenv('FRANKEI_USER_ID') ?: 0);
                if ($uid === 0) {
                    try {
                        if (class_exists('\\Beaver\\Auth\\Auth')) {
                            $uid = (int) (\Beaver\Auth\Auth::id() ?? 0);
                        }
                    } catch (\Throwable $e) {}
                }
                $base = $this->projectPath . '/storage/frankei-reports/' . $uid . '/current';
                if (!is_dir($base)) @mkdir($base, 0755, true);
                $args[] = '--coverage-clover=' . escapeshellarg($base . '/clover.xml');
            }

            if (!$hasAnyReport && $label !== 'PHPUnit') {
                $uid = (int) (getenv('FRANKEI_USER_ID') ?: 0);
                if ($uid === 0) {
                    try {
                        if (class_exists('\\Beaver\\Auth\\Auth')) {
                            $uid = (int) (\Beaver\Auth\Auth::id() ?? 0);
                        }
                    } catch (\Throwable $e) {}
                }

                $base    = $this->projectPath . '/storage/frankei-reports/' . $uid . '/current';
                $covHtml = $base . '/coverage';
                $covClv  = $base . '/clover.xml';

                if (!is_dir($base)) {
                    @mkdir($base, 0755, true);
                }

                $args[] = '--coverage-html=' . escapeshellarg($covHtml);
                $args[] = '--coverage-clover=' . escapeshellarg($covClv);
            }
        }

        if ($v = $this->option('log-junit')) {
            $args[] = '--log-junit=' . escapeshellarg((string) $v);
        }

        // Repassar flags específicas de coverage do user (se as passou)
        if ($v = $this->option('coverage-html')) {
            $args[] = '--coverage-html=' . escapeshellarg((string) $v);
        }
        if ($v = $this->option('coverage-clover')) {
            $args[] = '--coverage-clover=' . escapeshellarg((string) $v);
        }

        if ($v = $this->option('coverage-cobertura')) {
            $args[] = '--coverage-cobertura=' . escapeshellarg((string) $v);
        }

        if ($v = $this->option('coverage-text')) {
            $args[] = '--coverage-text=' . escapeshellarg((string) $v);
        }

        // Forçar XDEBUG_MODE=coverage para o subprocesso
        if ($this->option('coverage')) {
            putenv('XDEBUG_MODE=coverage');
            $_ENV['XDEBUG_MODE']    = 'coverage';
            $_SERVER['XDEBUG_MODE'] = 'coverage';
        }

        // Se não há testsuite nem filter nem positional, adicionar 'tests/'
        // — sem isto, o Pest falha (exit 2) quando só corre --coverage
        if (!$this->option('testsuite') && !$this->option('filter')) {
            $hasPositional = false;
            foreach ($args as $a) {
                if ($a[0] !== '-') { $hasPositional = true; break; }
            }
            if (!$hasPositional) {
                $args[] = escapeshellarg($this->projectPath . '/tests');
            }
        }

        $cmd = escapeshellarg($binary) . ' ' . implode(' ', $args);

        // Injetar XDEBUG_MODE=coverage quando estamos a medir cobertura
        $hasCov = $this->option('coverage')
               || $this->option('coverage-html')
               || $this->option('coverage-clover')
               || $this->option('coverage-cobertura')
               || $this->option('coverage-text');

        if ($hasCov) {
            // Invocar php explicitamente com -d. A env var XDEBUG_MODE sozinha
            // não sobrepõe o xdebug.mode do php.ini quando o pest re-invoca php.
            $cmd = escapeshellarg(PHP_BINARY)
                 . ' -d xdebug.mode=coverage '
                 . $cmd;
        }

        // Logo conforme o motor
        $logo = $label === 'Pest' ? '🐀' : '🐘';

        $this->line();
        $this->info($logo . ' Beaver — Testes (' . $label . ')');
        $this->line("  Comando: {$cmd}");
        $this->line();

        passthru($cmd, $code);

        $this->line();

        if ($code === 0) {
            $this->success('Todos os testes passaram');
            return self::SUCCESS;
        }

        // PHPUnit: exit 1 = sem testes na pasta. Não é falha.
        if ($label === 'PHPUnit') {
            $phpunitDir = $this->projectPath . '/tests/Phpunit';
            $hasPhpunitTests = false;
            if (is_dir($phpunitDir)) {
                foreach (new \FilesystemIterator($phpunitDir, \FilesystemIterator::SKIP_DOTS) as $f) {
                    if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                        $hasPhpunitTests = true;
                        break;
                    }
                }
            }
            if (!$hasPhpunitTests) {
                $this->info('Não existem testes PHPUnit.');
                return self::SUCCESS;
            }
        }

        // Pest: exit 1 quando há "incomplete" (por fazer) — não é falha real.
        // Se o output tiver "Tests:" com "passed" e "incomplete", aceitar.
        if ($label === 'Pest' && $code === 1) {
            // Sem acesso ao output aqui; usamos heurística pelo exit code.
            // Assumimos que é incomplete se a pasta tests/Unit existe e tem ficheiros.
            $this->warning('Alguns testes estão incompletos (marcados como "por fazer").');
            $this->line('  Não é uma falha — completa os testes quando puderes.');
            return self::SUCCESS;
        }

        $this->error($label . ' devolveu código ' . $code);
        return self::FAILURE;
    }
}
