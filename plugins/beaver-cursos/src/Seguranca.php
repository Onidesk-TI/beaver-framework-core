<?php
declare(strict_types=1);

namespace Beaver\Plugins\Cursos;

class Seguranca
{
    public static function avaliar(array $questao, string $valor): bool
    {
        if (($questao['tipo'] ?? '') === 'escolha') {
            return (string)$questao['resposta'] === $valor;
        }
        $regex = '~' . str_replace('~', '\~', (string)$questao['resposta']) . '~i';
        return (bool)preg_match($regex, trim($valor));
    }
}
