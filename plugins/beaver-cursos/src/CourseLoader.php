<?php

declare(strict_types=1);

namespace Beaver\Plugins\Cursos;

use Beaver\Plugins\Cursos\Models\Curso;
use Beaver\Plugins\Cursos\Models\Questao;

class CourseLoader
{
    public function __construct(private array $scanDirs)
    {
    }

    public function syncAll(): array
    {
        $report = ['ok' => 0, 'erros' => []];

        foreach ($this->scanDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . '/*/curso.json') ?: [] as $file) {
                try {
                    $this->syncFromFile($file);
                    $report['ok']++;
                } catch (\Throwable $e) {
                    $report['erros'][] = $file . ': ' . $e->getMessage();
                }
            }
        }

        return $report;
    }

    private function syncFromFile(string $file): void
    {
        $meta = json_decode((string)file_get_contents($file), true);
        if (!is_array($meta) || empty($meta['slug'])) {
            return;
        }

        $dir = dirname($file);

        $curso = Curso::where('slug', '=', $meta['slug'])->first();
        $dados = [
        'slug'        => (string)$meta['slug'],
        'titulo'      => (string)($meta['titulo'] ?? $meta['slug']),
        'descricao'   => (string)($meta['descricao'] ?? ''),
        'imagem'      => $meta['imagem'] ?? null,     // ← nova linha
        'preco'       => (string)($meta['preco'] ?? '49.00'),
        'duracao_min' => (int)($meta['duracao_min'] ?? 35),
        'licenca_key' => $meta['licenca_key'] ?? null,
        'pack_path'   => $dir,
        'ativo'       => 1,
        ];

        if ($curso) {
            $curso->fill($dados)->save();
        } else {
            $curso = Curso::create($dados);
        }

        $this->syncQuestoes((int)$curso->id, $dir . '/questoes.json');
    }

    private function syncQuestoes(int $cursoId, string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $lista = json_decode((string)file_get_contents($file), true);
        if (!is_array($lista)) {
            return;
        }

        // Apagar antigas
        $antigas = Questao::where('curso_id', '=', $cursoId)->get();
        foreach ($antigas as $q) {
            $q->delete();
        }

        foreach ($lista as $q) {
            Questao::create([
                'curso_id'  => $cursoId,
                'ordem'     => (int)($q['ordem'] ?? 0),
                'tipo'      => (string)($q['tipo'] ?? 'escolha'),
                'enunciado' => (string)($q['enunciado'] ?? ''),
                'opcoes'    => isset($q['opcoes'])
                    ? json_encode($q['opcoes'], JSON_UNESCAPED_UNICODE)
                    : null,
                'resposta'  => (string)($q['resposta'] ?? ''),
                'pontos'    => (int)($q['pontos'] ?? 1),
            ]);
        }
    }
}
