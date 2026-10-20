<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Support;

use Throwable;

/**
 * Auto-fix de bugs conhecidos no plugin Frankei.
 *
 * Corre no boot do plugin. Aplica patches idempotentes em ficheiros do
 * próprio plugin. Só corre 1× (marca com lock file) para não fazer I/O
 * em cada request.
 *
 * @package Beaver\Plugins\Frankei\Support
 */
class AutoFixService
{
    private const LOCK_VERSION = '1';   // incrementar quando se adiciona novo fix

    private string $pluginDir;
    private string $lockFile;

    public function __construct(?string $pluginDir = null)
    {
        $this->pluginDir = $pluginDir ?? dirname(__DIR__, 2);
        $this->lockFile  = $this->pluginDir . '/storage/.autofix.lock';
    }

    /**
     * Corre todos os fixes. Devolve relatório (ok, aplicados[]).
     */
    public function run(): array
    {
        $aplicados = [];

        // Já correu esta versão?
        if ($this->lockVersionMatches()) {
            return ['ok' => true, 'aplicados' => []];
        }

        // ─── Fix 1: Auth::id() → UserService::id() ───
        if ($this->fixTerminalControllerAuth()) {
            $aplicados[] = 'terminal_controller_auth';
        }

        // Marcar como corrido
        $this->writeLock();

        return ['ok' => true, 'aplicados' => $aplicados];
    }

    /* ═══════════════════════════════════════════════════════════
       FIX 1: Auth::id() → UserService::id()
       ═══════════════════════════════════════════════════════════ */

    private function fixTerminalControllerAuth(): bool
    {
        $file = $this->pluginDir . '/src/Http/TerminalController.php';
        if (!is_file($file)) return false;

        $s = @file_get_contents($file);
        if (!is_string($s) || $s === '') return false;

        // Já corrigido?
        if (str_contains($s, 'UserService::id()') && !str_contains($s, 'Auth::id()')) {
            return false;
        }
        if (!str_contains($s, 'Auth::id()')) {
            return false;
        }

        // Backup
        $bak = $file . '.bak.autofix-' . date('Ymd_His');
        @copy($file, $bak);

        // Import
        if (!str_contains($s, 'use App\\Services\\UserService;')) {
            $s = str_replace(
                'use Beaver\\Auth\\Auth;',
                'use Beaver\\Auth\\Auth;' . "\n" . 'use App\\Services\\UserService;',
                $s
            );
        }

        // Substituição
        $n = 0;
        $s = str_replace('Auth::id()', 'UserService::id()', $s, $n);

        // Escrever
        $ok = @file_put_contents($file, $s) !== false;

        // Log
        $this->log(sprintf(
            '[autofix] TerminalController: %d substituições (backup: %s)',
            $n,
            basename($bak)
        ));

        return $ok && $n > 0;
    }

    /* ═══════════════════════════════════════════════════════════
       LOCK
       ═══════════════════════════════════════════════════════════ */

    private function lockVersionMatches(): bool
    {
        if (!is_file($this->lockFile)) return false;
        $content = trim((string) @file_get_contents($this->lockFile));
        return $content === self::LOCK_VERSION;
    }

    private function writeLock(): void
    {
        @mkdir(dirname($this->lockFile), 0755, true);
        @file_put_contents($this->lockFile, self::LOCK_VERSION);
    }

    private function log(string $msg): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
        @file_put_contents($this->pluginDir . '/storage/autofix.log', $line, FILE_APPEND);
    }
}
