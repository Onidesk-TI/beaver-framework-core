<?php
declare(strict_types=1);

namespace Beaver\Plugins\CursoBeaverFundamentos;

use Beaver\Plugin\PluginBase;

class CursoPlugin extends PluginBase
{
    public function boot(): void
    {
        // Nada a fazer — o motor beaver-cursos descobre este curso
        // através do curso.json
    }
}
