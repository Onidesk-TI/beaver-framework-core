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
use Beaver\Draft\DraftStore;

/**
 * Lista os rascunhos pendentes.
 *
 * Uso:
 *   php beaver draft:list
 *
 * Lê database/drafts/ e mostra uma tabela com ID, tabela alvo,
 * número de campos e data de criação.
 *
 * Não toca na base de dados nem em migrations aplicadas.
 */
class DraftListCommand extends Command
{
    protected string $signature = 'draft:list';
    protected string $description = 'List all pending drafts';

    public function handle(): int
    {
        $store  = new DraftStore(rtrim($this->projectPath, '/') . '/database/drafts');
        $drafts = $store->list();

        if ($drafts === []) {
            $this->info('Nenhum rascunho.');
            $this->line();
            $this->line('Cria um com:');
            $this->line('  php beaver table:wizard <nome>');
            return self::SUCCESS;
        }

        $this->line();
        $this->line(sprintf(
            "  \033[1m%-46s %-20s %-8s %s\033[0m",
            'ID',
            'Tabela',
            'Campos',
            'Criado em'
        ));
        $this->line('  ' . str_repeat('─', 100));

        foreach ($drafts as $d) {
            $this->line(sprintf(
                "  %-46s %-20s %-8d %s",
                $d->id,
                $d->table,
                count($d->fields),
                $d->createdAt
            ));
        }

        $this->line();
        $this->line('  Aplicar:   php beaver draft:apply <id>');
        $this->line('  Descartar: php beaver draft:discard <id>');
        $this->line();

        return self::SUCCESS;
    }
}
