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

namespace Beaver\Draft;

/**
 * Persistência de rascunhos em database/drafts/.
 *
 * Um rascunho é uma migração que ainda NÃO foi escrita em
 * database/migrations/ nem aplicada à base de dados. Vive em disco
 * como uma pasta com dois ficheiros:
 *
 *   database/drafts/<id>/
 *     draft.json    ← fonte de verdade (lida e escrita pelo código)
 *     preview.sql   ← SQL gerado (apenas para leitura humana)
 *
 * Esta classe NÃO sabe nada sobre SQL, migrações ou base de dados.
 * Apenas lê, escreve, lista e apaga pastas de rascunho em disco.
 *
 * Uso:
 *   $store = new DraftStore('/caminho/para/database/drafts');
 *   $store->save($draft);
 *   $draft = $store->find($id);
 *   $all   = $store->list();
 *   $store->delete($id);
 */

final class Draft
{
    public function __construct(
        public readonly string $id,
        public readonly string $action,
        public readonly string $table,
        public readonly array $fields,
        public readonly string $sql,
        public readonly string $createdAt,
        public readonly array $source = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'action'     => $this->action,
            'table'      => $this->table,
            'fields'     => $this->fields,
            'sql'        => $this->sql,
            'created_at' => $this->createdAt,
            'source'     => $this->source,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id:        (string) ($data['id'] ?? ''),
            action:    (string) ($data['action'] ?? 'create_table'),
            table:     (string) ($data['table'] ?? ''),
            fields:    (array)  ($data['fields'] ?? []),
            sql:       (string) ($data['sql'] ?? ''),
            createdAt: (string) ($data['created_at'] ?? date('c')),
            source:    (array)  ($data['source'] ?? []),
        );
    }

    public static function makeId(string $table, string $action = 'create'): string
    {
        return date('Y_m_d_His') . '_' . $action . '_' . $table . '_table';
    }
}
