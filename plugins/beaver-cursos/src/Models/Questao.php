<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos\Models;

use Beaver\Database\Model\Model;

class Questao extends Model
{
    protected static string $table = 'questoes';
    protected static bool $timestamps = false;

    protected array $fillable = [
        'curso_id', 'ordem', 'tipo', 'enunciado', 'opcoes', 'resposta', 'pontos',
    ];

    public function opcoesArray(): array
    {
        if (empty($this->opcoes)) return [];
        $arr = json_decode((string)$this->opcoes, true);
        return is_array($arr) ? $arr : [];
    }

    public function avaliar(string $valor): bool
    {
        if ($this->tipo === 'escolha') {
            return (string)$this->resposta === $valor;
        }
        $regex = '~' . str_replace('~', '\~', (string)$this->resposta) . '~i';
        return (bool)preg_match($regex, trim($valor));
    }
}
