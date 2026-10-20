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

namespace Beaver\Database\Migrations;

/**
 * Parse de sintaxe compacta para colunas.
 *
 * Formatos suportados:
 *
 *   id                          → $t->id()
 *   timestamps                  → $t->timestamps()
 *   name:str                    → $t->string('name')
 *   name:str(100)               → $t->string('name', 100)
 *   name:str?                   → nullable
 *   name:str=x                  → default 'x'
 *   name:str?=x                 → nullable + default
 *   amount:dec                  → $t->decimal('amount')
 *   amount:dec(10,4)            → $t->decimal('amount', 10, 4)
 *   user:ref.users              → foreignId('user_id')->references('id')->on('users')
 *   date:date
 *   time:time
 *   json:json
 *   uuid:uuid
 *   is_active:bool=1
 */
class FieldParser
{
    private string $field;
    private string $name = '';
    private string $type = 'str';
    private array $typeArgs = [];
    private bool $nullable = false;
    private mixed $default = null;
    private bool $hasDefault = false;
    private ?string $referencesTable = null;

    public function __construct(string $field)
    {
        $this->field = trim($field);
    }

    public function parse(): self
    {
        $raw = $this->field;

        // 1. Nome (antes do ':')
        $parts = explode(':', $raw, 2);
        $this->name = trim($parts[0]);

        if (!isset($parts[1])) {
            // 'id', 'timestamps' — sem tipo
            $this->type = strtolower($this->name);
            return $this;
        }

        // 2. Resto (tipo + modificadores)
        $rest = $parts[1];

        // 3. Separar modificadores (?, =)
        //    Ordem: tipo(args)?=default
        if (str_contains($rest, '?')) {
            $this->nullable = true;
            $rest = str_replace('?', '', $rest);
        }

        if (str_contains($rest, '=')) {
            [$rest, $default] = explode('=', $rest, 2);
            $this->default = $this->parseDefault($default);
            $this->hasDefault = true;
        }

        // 4. Tipo + argumentos
        if (preg_match('/^([a-z_]+)(?:\(([^)]+)\))?$/i', $rest, $m)) {
            $this->type = strtolower($m[1]);

            if (isset($m[2])) {
                $this->typeArgs = array_map('trim', explode(',', $m[2]));
            }
        } elseif (str_starts_with($rest, 'ref.')) {
            $this->type = 'ref';
            $this->referencesTable = substr($rest, 4);
        } else {
            $this->type = strtolower($rest);
        }

        return $this;
    }

    public function applyTo(Blueprint $b): void
    {
        $this->parse();

        switch ($this->type) {
            case 'id':
                $b->id($this->name);
                return;

            case 'timestamps':
                $b->timestamps();
                return;

            case 'str':
            case 'string':
                $length = isset($this->typeArgs[0]) ? (int) $this->typeArgs[0] : 255;
                $b->string($this->name, $length);
                break;

            case 'text':
                $b->text($this->name);
                break;

            case 'int':
            case 'integer':
                $b->integer($this->name);
                break;

            case 'dec':
            case 'decimal':
                $precision = isset($this->typeArgs[0]) ? (int) $this->typeArgs[0] : 10;
                $scale     = isset($this->typeArgs[1]) ? (int) $this->typeArgs[1] : 2;
                $b->decimal($this->name, $precision, $scale);
                break;

            case 'bool':
            case 'boolean':
                $default = $this->hasDefault ? (bool) $this->default : false;
                $b->boolean($this->name, $default);
                break;

            case 'date':
                $b->date($this->name);
                break;

            case 'time':
                $b->time($this->name);
                break;

            case 'datetime':
            case 'timestamp':
                $b->datetime($this->name);
                break;

            case 'json':
                $b->json($this->name);
                break;

            case 'uuid':
                $b->uuid($this->name);
                break;

            case 'ref':
                // 'user' → 'user_id' automaticamente
                $col = str_ends_with($this->name, '_id') ? $this->name : $this->name . '_id';
                $b->foreignId($col)->references($this->referencesTable);
                break;

            default:
                // Tipo desconhecido → string
                $b->string($this->name);
                break;
        }

        // Aplicar modificadores
        if ($this->nullable) {
            $b->nullable();
        }

        if ($this->hasDefault) {
            $b->default($this->default);
        }
    }

    private function parseDefault(string $raw): mixed
    {
        $raw = trim($raw);

        if ($raw === 'null' || $raw === 'NULL') {
            return null;
        }
        if ($raw === 'true' || $raw === 'TRUE') {
            return true;
        }
        if ($raw === 'false' || $raw === 'FALSE') {
            return false;
        }

        // Número
        if (is_numeric($raw)) {
            return str_contains($raw, '.') ? (float) $raw : (int) $raw;
        }

        // String entre aspas ou simples
        return trim($raw, "'\"");
    }
}
