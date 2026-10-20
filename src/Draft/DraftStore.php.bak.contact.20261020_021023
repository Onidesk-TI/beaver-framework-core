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
 */
final class DraftStore
{
    public function __construct(
        private readonly string $draftsDir,
    ) {
    }

    /**
     * Caminho absoluto da pasta de um rascunho.
     */
    public function path(string $id): string
    {
        return rtrim($this->draftsDir, '/') . '/' . $id;
    }

    /**
     * Guarda um rascunho em disco.
     *
     * Cria a pasta se não existir e escreve dois ficheiros:
     *   - draft.json  (fonte de verdade)
     *   - preview.sql (só para leitura humana)
     *
     * @throws \RuntimeException  Se não conseguir criar a pasta ou escrever.
     */
    public function save(Draft $draft): void
    {
        $dir = $this->path($draft->id);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Não foi possível criar: {$dir}");
        }

        $json = json_encode(
            $draft->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new \RuntimeException('Falha a codificar JSON.');
        }

        if (file_put_contents($dir . '/draft.json', $json . "\n") === false) {
            throw new \RuntimeException("Não foi possível escrever: {$dir}/draft.json");
        }

        $preview = "-- Rascunho: {$draft->id}\n"
            . "-- Ação:      {$draft->action}\n"
            . "-- Tabela:    {$draft->table}\n"
            . "-- Criado em: {$draft->createdAt}\n"
            . "--\n"
            . "-- Este ficheiro é apenas informativo.\n"
            . "-- A fonte de verdade é draft.json.\n"
            . "-- Aplica com:  php beaver draft:apply {$draft->id}\n\n"
            . $draft->sql . "\n";

        file_put_contents($dir . '/preview.sql', $preview);
    }

    /**
     * Lê um rascunho pelo ID.
     *
     * Devolve null se a pasta não existir, se draft.json faltar,
     * ou se o JSON estiver corrompido.
     */
    public function find(string $id): ?Draft
    {
        $file = $this->path($id) . '/draft.json';

        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (!is_array($data)) {
            return null;
        }

        return Draft::fromArray($data);
    }

    /**
     * Lista todos os rascunhos válidos, ordenados do mais recente para o mais antigo.
     *
     * Ignora pastas cujo draft.json esteja ausente ou corrompido.
     *
     * @return Draft[]
     */
    public function list(): array
    {
        if (!is_dir($this->draftsDir)) {
            return [];
        }

        $dirs = glob(rtrim($this->draftsDir, '/') . '/*', GLOB_ONLYDIR) ?: [];
        $drafts = [];

        foreach ($dirs as $dir) {
            $draft = $this->find(basename($dir));
            if ($draft !== null) {
                $drafts[] = $draft;
            }
        }

        usort($drafts, fn (Draft $a, Draft $b) => strcmp($b->createdAt, $a->createdAt));

        return $drafts;
    }

    /**
     * Apaga um rascunho (pasta + ficheiros).
     *
     * Não toca na base de dados nem em migrations aplicadas.
     * Idempotente: se não existir, não faz nada.
     */
    public function delete(string $id): void
    {
        $dir = $this->path($id);

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }

    /**
     * Verifica se existe um rascunho com este ID.
     *
     * Verifica especificamente o draft.json (não a pasta),
     * para que pastas vazias/órfãs não contem como rascunhos.
     */
    public function exists(string $id): bool
    {
        return is_file($this->path($id) . '/draft.json');
    }
}
