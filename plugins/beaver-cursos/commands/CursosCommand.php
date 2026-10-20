<?php
declare(strict_types=1);

namespace Plugins\BeaverCursos;

class CursosCommand
{
    public function listar(): void
    {
        $db = db();
        $rows = $db->query('SELECT slug, titulo, duracao_min, preco, ativo FROM cursos ORDER BY titulo')->fetchAll();

        echo "\n";
        echo "CURSOS INSTALADOS\n";
        echo str_repeat('─', 79) . "\n";
        printf("  %-28s %-28s %6s %8s\n", 'slug', 'título', 'min', 'estado');
        echo str_repeat('─', 79) . "\n";

        foreach ($rows as $r) {
            printf(
                "  %-28s %-28s %6d %8s\n",
                $r['slug'],
                mb_substr($r['titulo'], 0, 26),
                (int)$r['duracao_min'],
                $r['ativo'] ? '● ativo' : '○ inativo'
            );
        }
        echo "\n";
    }

    public function sync(): void
    {
        $base = dirname(__DIR__, 3);
        $loader = new CourseLoader([$base . '/plugins', $base . '/vendor']);
        $report = $loader->syncAll();

        echo "✔ Cursos sincronizados: {$report['ok']}\n";
        if (!empty($report['erros'])) {
            echo "✖ Erros:\n";
            foreach ($report['erros'] as $e) echo "  - $e\n";
        }
    }

    public function info(string $slug): void
    {
        $repo = new CursoRepository(db());
        $curso = $repo->findBySlug($slug);

        if (!$curso) {
            echo "✖ Curso não encontrado: $slug\n";
            return;
        }

        $total = $repo->countQuestoes((int)$curso['id']);

        echo "\n";
        echo "  Título:   {$curso['titulo']}\n";
        echo "  Slug:     {$curso['slug']}\n";
        echo "  Duração:  {$curso['duracao_min']} min\n";
        echo "  Preço:    €" . number_format((float)$curso['preco'], 2, ',', '.') . "\n";
        echo "  Questões: $total\n";
        echo "  Pack:     {$curso['pack_path']}\n";
        echo "  Estado:   " . ($curso['ativo'] ? '● ativo' : '○ inativo') . "\n";
        echo "\n";
    }
}
