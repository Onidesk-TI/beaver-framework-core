<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Console\Commands;

use Beaver\Console\Command;
use Beaver\Database\Database;
use Beaver\Database\Migrations\Blueprint;
use Beaver\Database\Migrations\FieldParser;
use Beaver\Draft\Draft;
use Beaver\Draft\DraftStore;

/**
 * Wizard interativo para criar uma tabela.
 *
 * Uso:
 *   php beaver table:wizard <nome>
 *
 * Fluxo:
 *   1. Pergunta campos (sintaxe compacta: name:str, email:str?, amount:dec(10,2))
 *   2. Pergunta se quer adicionar timestamps automaticamente
 *   3. Gera SQL de preview (SEM executar)
 *   4. Guarda rascunho em database/drafts/<id>/
 *   5. Pergunta se quer aplicar já
 *
 * Nada toca na base de dados até o utilizador confirmar "aplicar".
 */
class TableWizardCommand extends Command
{
    protected string $signature = 'table:wizard {name}';
    protected string $description = 'Interactive wizard to create a table (creates a draft)';

    public function handle(): int
    {
        $raw = (string) $this->argument('name', '');

        if ($raw === '') {
            $this->error('Falta o nome da tabela.');
            $this->line();
            $this->line('Uso: php beaver table:wizard <nome>');
            return self::INVALID;
        }

        $table = $this->normalizeSlug($raw);

        if ($table === '') {
            $this->error('Nome inválido após normalização.');
            return self::INVALID;
        }

        $this->line();
        $this->info("Wizard: criar tabela `{$table}`");
        $this->line();

        // ── 1. Recolher campos ───────────────────────────────────
        $fields = ['id'];

        while (true) {
            $input = $this->ask('Campo (Enter para terminar)', '');

            if ($input === '') {
                break;
            }

          // Bloquear campos automáticos
            $lower = strtolower(trim($input));
            if ($lower === 'id' || str_starts_with($lower, 'id:')) {
                $this->warning('O campo `id` é adicionado automaticamente. Ignorado.');
                continue;
            }
            if ($lower === 'timestamps') {
                $this->warning('`timestamps` é perguntado no fim. Ignorado.');
                continue;
            }

            // Validar sintaxe com o FieldParser existente
            try {
                (new FieldParser($input))->parse();
                $fields[] = $input;
                $this->line("  \033[32m✓\033[0m {$input}");
            } catch (\Throwable $e) {
                $this->warning("Inválido: {$e->getMessage()}");
            }
        }

        if (count($fields) === 1) {
            $this->line();
            $this->warning('Nenhum campo além de `id`.');
            if (!$this->confirm('Continuar assim mesmo?', false)) {
                $this->info('Cancelado.');
                return self::SUCCESS;
            }
        }

        // ── 2. Timestamps automáticos ────────────────────────────
        if (!in_array('timestamps', $fields, true)) {
            if ($this->confirm('Adicionar timestamps automaticamente?', true)) {
                $fields[] = 'timestamps';
            }
        }

        // ── 3. Gerar SQL de preview ──────────────────────────────
        try {
            $sql = $this->buildSql($table, $fields);
        } catch (\Throwable $e) {
            $this->error('Erro a gerar SQL: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line();
        $this->line("\033[38;5;240m── Preview (SQL) ──────────────────────────────\033[0m");
        foreach (explode("\n", $sql) as $line) {
            $this->line('  ' . $line);
        }
        $this->line("\033[38;5;240m───────────────────────────────────────────────\033[0m");
        $this->line();

        // ── 4. Guardar rascunho ──────────────────────────────────
        $id    = Draft::makeId($table, 'create');
        $draft = new Draft(
            id:        $id,
            action:    'create_table',
            table:     $table,
            fields:    $fields,
            sql:       $sql,
            createdAt: date('c'),
        );

        $store = new DraftStore(rtrim($this->projectPath, '/') . '/database/drafts');

        try {
            $store->save($draft);
        } catch (\Throwable $e) {
            $this->error('Não foi possível guardar o rascunho: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->success("Rascunho criado: {$id}");
        $this->line("     database/drafts/{$id}/");
        $this->line();

        // ── 5. Aplicar agora? ────────────────────────────────────
        if ($this->confirm('Aplicar agora?', false)) {
            $apply = new DraftApplyCommand();
            $apply->setProjectPath($this->projectPath);
            $apply->parse([$id]);
            return $apply->handle();
        }

        $this->line('Próximos passos:');
        $this->line("  php beaver draft:apply   {$id}");
        $this->line("  php beaver draft:discard {$id}");
        $this->line();

        return self::SUCCESS;
    }

    /**
     * Gera o SQL de CREATE TABLE sem executar nada.
     *
     * @param string[] $fields
     */
    private function buildSql(string $table, array $fields): string
    {
        $blueprint = new Blueprint($table);

        foreach ($fields as $field) {
            (new FieldParser($field))->applyTo($blueprint);
        }

        $driver = Database::getInstance()->getDriverName();

        return $blueprint->toCreateSql($driver);
    }

    /**
     * Normaliza um nome de tabela.
     *
     *   "Users"        → "users"
     *   "blog-posts"   → "blog_posts"
     *   "  posts  "    → "posts"
     */
    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
        return trim($slug, '_');
    }
}
