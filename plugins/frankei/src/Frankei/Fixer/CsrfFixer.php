<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Fixer;

class CsrfFixer
{
    /**
     * Gera preview da correcao (sem escrever).
     *
     * @return array{ok:bool, before:string, after:string, diff:string, file:string, error?:string}
     */
    public function preview(string $file, int $line): array
    {
        if (!is_file($file)) {
            return ['ok' => false, 'error' => 'Ficheiro nao existe: ' . $file, 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        if (!is_readable($file)) {
            return ['ok' => false, 'error' => 'Sem permissao de leitura', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return ['ok' => false, 'error' => 'Erro ao ler ficheiro', 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        $lines = preg_split('/(\r?\n)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Encontrar a linha do <form> (a partir da linha indicada)
        $lineIdx = ($line - 1) * 2; // cada linha = [conteudo, \n]
        $linhaOriginal = $lines[$lineIdx] ?? '';

        if (!preg_match('/<form[^>]*>/i', $linhaOriginal, $m)) {
            return ['ok' => false, 'error' => 'Nao encontrei <form> na linha ' . $line, 'before' => '', 'after' => '', 'diff' => '', 'file' => $file];
        }

        // Indentacao da linha
        preg_match('/^(\s*)/', $linhaOriginal, $ind);
        $indent = $ind[1] ?? '';

        // Substituir o <form ...> original por ele + csrf_field
        $formTag = $m[0];
        $novoFormTag = $formTag . "\n" . $indent . '    <?= csrf_field() ?>';

        $novaLinha = str_replace($formTag, $novoFormTag, $linhaOriginal);

        $lines[$lineIdx] = $novaLinha;
        $novoConteudo = implode('', $lines);

        return [
            'ok'     => true,
            'file'   => $file,
            'line'   => $line,
            'before' => $linhaOriginal,
            'after'  => $novaLinha,
            'diff'   => $this->makeDiff($linhaOriginal, $novaLinha),
        ];
    }

    /**
     * Aplica a correcao (com backup opcional).
     *
     * @return array{ok:bool, backup?:string, error?:string}
     */
    public function apply(string $file, int $line, bool $backup = true): array
    {
        // Prevenir: ficheiro tem de estar dentro de um root valido
        // (a verificacao de path seguro fica a cargo do controller)

        $preview = $this->preview($file, $line);
        if (!$preview['ok']) {
            return ['ok' => false, 'error' => $preview['error']];
        }

        // Backup
        $backupPath = null;
        if ($backup) {
            $backupPath = $file . '.bak.' . date('Ymd_His');
            if (!@copy($file, $backupPath)) {
                return ['ok' => false, 'error' => 'Erro ao criar backup'];
            }
        }

        // Aplicar
        $content = file_get_contents($file);
        $lines = preg_split('/(\r?\n)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $lineIdx = ($line - 1) * 2;

        if (!isset($lines[$lineIdx])) {
            return ['ok' => false, 'error' => 'Linha invalida'];
        }

        $linhaOriginal = $lines[$lineIdx];
        if (!preg_match('/<form[^>]*>/i', $linhaOriginal, $m)) {
            return ['ok' => false, 'error' => 'Nao encontrei <form> na linha ' . $line];
        }

        preg_match('/^(\s*)/', $linhaOriginal, $ind);
        $indent = $ind[1] ?? '';
        $formTag = $m[0];
        $novoFormTag = $formTag . "\n" . $indent . '    <?= csrf_field() ?>';

        $lines[$lineIdx] = str_replace($formTag, $novoFormTag, $linhaOriginal);
        $novoConteudo = implode('', $lines);

        if (@file_put_contents($file, $novoConteudo) === false) {
            return ['ok' => false, 'error' => 'Erro ao escrever ficheiro'];
        }

        return [
            'ok'     => true,
            'backup' => $backupPath,
            'file'   => $file,
        ];
    }

    private function makeDiff(string $antes, string $depois): string
    {
        $out = '';
        $out .= "<span class='fk-diff-rem'>- " . htmlspecialchars(rtrim($antes)) . "</span>\n";
        $out .= "<span class='fk-diff-add'>+ " . htmlspecialchars(rtrim($depois)) . "</span>";
        return $out;
    }
}
