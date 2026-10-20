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
 * Descarta um rascunho.
 *
 * Uso:
 *   php beaver draft:discard <id>
 *
 * Apaga a pasta do rascunho em database/drafts/<id>/.
 *
 * NÃO toca na base de dados.
 * NÃO afeta migrations já aplicadas.
 * Se o rascunho não existir, avisa e sai sem erro.
 */
class DraftDiscardCommand extends Command
{
    protected string $signature = 'draft:discard {id}';
    protected string $description = 'Discard a draft (does NOT touch the database)';

    public function handle(): int
    {
        $id = (string) $this->argument('id', '');

        if ($id === '') {
            $this->error('Falta o ID do rascunho.');
            $this->line();
            $this->line('Uso:  php beaver draft:discard <id>');
            $this->line('Lista: php beaver draft:list');
            return self::INVALID;
        }

        $store = new DraftStore(rtrim($this->projectPath, '/') . '/database/drafts');

        if (!$store->exists($id)) {
            $this->error("Rascunho não encontrado: {$id}");
            $this->line();
            $this->line('Lista os disponíveis com:  php beaver draft:list');
            return self::FAILURE;
        }

        $draft = $store->find($id);

        // Aviso ao utilizador
        $this->line();
        $this->warning('Vais apagar o rascunho:');
        $this->line();
        $this->line("    ID:     {$id}");
        $this->line("    Tabela: {$draft?->table}");
        $this->line("    Campos: " . count($draft?->fields ?? []));
        $this->line();
        $this->line('Isto NÃO toca na base de dados.');
        $this->line('Isto NÃO afeta migrations já aplicadas.');
        $this->line();

        if (!$this->confirm('Confirmar?', false)) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        $store->delete($id);
        $this->success("Rascunho descartado: {$id}");

        return self::SUCCESS;
    }
}
