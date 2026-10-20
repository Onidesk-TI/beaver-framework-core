<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos\Models;

use Beaver\Database\Model\Model;

class Resposta extends Model
{
    protected static string $table = 'respostas';
    protected static bool $timestamps = false;

    protected array $fillable = [
        'tentativa_id', 'questao_id', 'resposta', 'correta', 'answered_at',
    ];

    public static function paraQuestao(int $tentativaId, int $questaoId): ?static
    {
        return static::where('tentativa_id', '=', $tentativaId)
            ->where('questao_id', '=', $questaoId)
            ->first();
    }

    public static function mapaDaTentativa(int $tentativaId): array
    {
        $lista = static::where('tentativa_id', '=', $tentativaId)->get();
        $out = [];
        foreach ($lista as $r) {
            $out[(int)$r->questao_id] = [
                'resposta' => $r->resposta,
                'correta'  => (int)$r->correta === 1,
            ];
        }
        return $out;
    }
}
