<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos\Models;

use Beaver\Database\Model\Model;

class Tentativa extends Model
{
    protected static string $table = 'tentativas';
    protected static bool $timestamps = false;

    protected array $fillable = [
        'user_id', 'curso_id', 'started_at', 'ends_at',
        'finished_at', 'score', 'total', 'status',
    ];

    public static function ativaDoUser(int $userId): ?static
    {
        return static::where('user_id', '=', $userId)
            ->where('status', '=', 'em_curso')
            ->orderBy('id', 'desc')
            ->first();
    }

    public static function ultimaDoUser(int $userId, int $cursoId): ?static
    {
        return static::where('user_id', '=', $userId)
            ->where('curso_id', '=', $cursoId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function expirou(): bool
    {
        return time() >= strtotime((string)$this->ends_at);
    }

    public function respostas(): array
    {
        return Resposta::where('tentativa_id', '=', $this->id)->get();
    }

    public function finalizar(): array
    {
        $corretas = 0;
        foreach ($this->respostas() as $r) {
            if ((int)$r->correta === 1) $corretas++;
        }

        $this->score       = $corretas;
        $this->status      = 'terminado';
        $this->finished_at = date('Y-m-d H:i:s');
        $this->save();

        return ['score' => $corretas, 'total' => (int)$this->total];
    }

    public function expirar(): void
    {
        $this->status      = 'expirado';
        $this->finished_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
