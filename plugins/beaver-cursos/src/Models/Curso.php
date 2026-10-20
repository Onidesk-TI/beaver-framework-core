<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos\Models;

use Beaver\Database\Model\Model;

class Curso extends Model
{
    protected static string $table = 'cursos';

    protected array $fillable = [
        'slug', 'titulo', 'descricao', 'preco', 'duracao_min',
        'licenca_key', 'pack_path', 'ativo',
    ];

    public static function ativos(): array
    {
        return static::where('ativo', '=', 1)->orderBy('titulo')->get();
    }

    public static function porSlug(string $slug): ?static
    {
        return static::where('slug', '=', $slug)
            ->where('ativo', '=', 1)
            ->first();
    }

    public function questoes(): array
    {
        return Questao::where('curso_id', '=', $this->id)
            ->orderBy('ordem')
            ->get();
    }

    public function totalQuestoes(): int
    {
        return count($this->questoes());
    }
}
