<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Fixer;

class XssFixer
{
    public function preview(string $file, int $line): array
    {
        if (!is_file($file) || !is_readable($file)) {
            return ['ok' => false, 'error' => 'Ficheiro invalido', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        $content = file_get_contents($file);
        $lines = preg_split('/(\r?\n)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lineIdx = ($line - 1) * 2;

        if (!isset($lines[$lineIdx])) {
            return ['ok' => false, 'error' => 'Linha invalida', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        $linhaOriginal = $lines[$lineIdx];

        if (str_contains($linhaOriginal, 'htmlspecialchars')) {
            return ['ok' => false, 'error' => 'Ja tem htmlspecialchars', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        $novaLinha = $this->envolverComEscape($linhaOriginal);

        if ($novaLinha === $linhaOriginal) {
            return ['ok' => false, 'error' => 'Nao consegui aplicar escape automatico', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        return [
            'ok'     => true,
            'file'   => $file,
            'line'   => $line,
            'before' => $linhaOriginal,
            'after'  => $novaLinha,
            'diff'   => $this->makeDiff($linhaOriginal, $novaLinha),
        ];
    }

    public function apply(string $file, int $line, bool $backup = true): array
    {
        $preview = $this->preview($file, $line);
        if (!$preview['ok']) {
            return ['ok' => false, 'error' => $preview['error']];
        }

        $backupPath = null;
        if ($backup) {
            $backupPath = $file . '.bak.' . date('Ymd_His');
            if (!@copy($file, $backupPath)) {
                return ['ok' => false, 'error' => 'Erro ao criar backup'];
            }
        }

        $content = file_get_contents($file);
        $lines = preg_split('/(\r?\n)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lineIdx = ($line - 1) * 2;

        $lines[$lineIdx] = $preview['after'];
        $novoConteudo = implode('', $lines);

        if (@file_put_contents($file, $novoConteudo) === false) {
            return ['ok' => false, 'error' => 'Erro ao escrever'];
        }

        return ['ok' => true, 'backup' => $backupPath, 'file' => $file];
    }

    /**
     * Envolve variaveis com htmlspecialchars, mesmo em concatenacoes.
     */
    private function envolverComEscape(string $linha): string
    {

        if (preg_match('/<\?=\s*(\$[A-Za-z_][A-Za-z0-9_\[\]\'"\$>-]*(?:\s*\?\?\s*[^)]+)?)\s*\?>/', $linha, $m)) {
            /** Caso 1: <?= $var ?> */
            return $this->substituirVar($linha, $m[0], $m[1]);
        }

        // Caso 2: echo/print com concatenacao ou variavel
        if (preg_match('/\b(echo|print)\s+(.+?)\s*;?\s*$/', $linha, $m)) {
            $stmt   = $m[2];
            $prefix = $m[1];
            $novoStmt = $this->envolverVariaveis($stmt);
            if ($novoStmt !== $stmt) {
                return str_replace($prefix . ' ' . $stmt, $prefix . ' ' . $novoStmt, $linha);
            }
        }


        if (preg_match('/<\?=\s*(.+?)\s*\?>/', $linha, $m)) {
            /**  Caso 3: fallback — variavel em <?= sem fecho */

            $expr = $m[1];
            $novoExpr = $this->envolverVariaveis($expr);
            if ($novoExpr !== $expr) {
                return str_replace($expr, $novoExpr, $linha);
            }
        }

        return $linha;
    }

    /**
     * Envolve todas as variaveis que nao estejam ja dentro de htmlspecialchars.
     */
    private function envolverVariaveis(string $expr): string
    {
        // Regex que apanha $var, $var['x'], $var->x, $_SERVER['x'] ?? 'default'
        $pattern = '/(\$[A-Za-z_][A-Za-z0-9_]*(?:\[[^\]]+\]|->[A-Za-z_][A-Za-z0-9_]*)*(?:\s*\?\?\s*[^,)]+)?)/';

        $resultado = '';
        $offset    = 0;

        if (preg_match_all($pattern, $expr, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $var     = $match[0];
                $pos     = $match[1];

                // Verificar se ja esta dentro de htmlspecialchars (procurar para tras)
                $antes = substr($expr, max(0, $pos - 25), 25);
                if (stripos($antes, 'htmlspecialchars(') !== false) {
                    continue;
                }

                $resultado .= substr($expr, $offset, $pos - $offset);
                $resultado .= 'htmlspecialchars(' . $var . ", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')";
                $offset = $pos + strlen($var);
            }
        }

        $resultado .= substr($expr, $offset);
        return $resultado;
    }

    private function substituirVar(string $linha, string $antigo, string $var): string
    {
        $novo = 'htmlspecialchars(' . $var . ", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')";
        return str_replace($antigo, $novo, $linha);
    }

    private function makeDiff(string $antes, string $depois): string
    {
        return "<span class='fk-diff-rem'>- " . htmlspecialchars(rtrim($antes)) . "</span>\n" .
            "<span class='fk-diff-add'>+ " . htmlspecialchars(rtrim($depois)) . "</span>";
    }
}
