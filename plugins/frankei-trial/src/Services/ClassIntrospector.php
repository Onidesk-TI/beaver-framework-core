<?php

declare(strict_types=1);

namespace Beaver\Plugins\FrankeiTrial\Services;

use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * FK_AI_RAG — extrai factos de uma classe PHP via Reflection.
 * Uso: construir prompts com informacao real em vez de regras abstratas.
 */
final class ClassIntrospector
{
    /**
     * @return array{
     *   fqcn:string, file:?string, abstract:bool, final:bool, interface:bool,
     *   http_bound:bool, methods:array<int,array{name:string,params:array,return:string,static:bool}>
     * }|null
     */
    public function introspect(string $fqcn): ?array
    {
        $fqcn = ltrim($fqcn, '\\');
        if ($fqcn === '' || !class_exists($fqcn)) {
            return null;
        }

        try {
            $ref = new ReflectionClass($fqcn);
        } catch (Throwable $e) {
            return null;
        }

        $methods = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            // Só métodos proprios (ignora herdados de TestCase, BaseController, etc.)
            if ($m->getDeclaringClass()->getName() !== $fqcn) {
                continue;
            }
            if ($m->isConstructor() || $m->isDestructor()) {
                continue;
            }
            // Ignora metodos magicos (__*)
            if (strpos($m->getName(), '__') === 0) {
                continue;
            }

            $params = [];
            foreach ($m->getParameters() as $p) {
                $params[] = [
                    'name'     => $p->getName(),
                    'type'     => $p->hasType() ? (string) $p->getType() : 'mixed',
                    'optional' => $p->isOptional(),
                    'default'  => $p->isDefaultValueAvailable()
                        ? var_export($p->getDefaultValue(), true)
                        : null,
                ];
            }

            $methods[] = [
                'name'   => $m->getName(),
                'params' => $params,
                'return' => $m->hasReturnType() ? (string) $m->getReturnType() : 'mixed',
                'static' => $m->isStatic(),
            ];
        }

        $sourceFile = $ref->getFileName() ?: null;
        $sourceCode = ($sourceFile !== null && is_file($sourceFile))
            ? (string) file_get_contents($sourceFile)
            : '';

        return [
            'fqcn'        => $fqcn,
            'file'        => $sourceFile,
            'source_file' => $sourceFile,
            'source_code' => $sourceCode,
            'abstract'    => $ref->isAbstract(),
            'final'       => $ref->isFinal(),
            'interface'   => $ref->isInterface(),
            'http_bound'  => $this->looksHttpBound($ref, $fqcn),
            'methods'     => $methods,
        ];
    }

    /**
     * Devolve o FQCN da classe sob teste a partir do conteudo do ficheiro de teste.
     * Procura "use App\...;" ou "use Beaver\...;" que nao seja TestCase.
     */
    public function extractTargetFromTest(string $testCode): ?string
    {
        // 0. Para Pest: tentar o namespace Tests\Integration\LLM\<Motor>\<Nome>Test
        if (preg_match('/namespace\s+Tests\\Integration\\LLM\\(\w+)\\(\w+);/', $testCode, $mPest)) {
            $base = preg_replace('/Test$/', '', $mPest[2]);
            if ($base !== '') {
                foreach (
                    [
                        'App\\Http\\Controllers\\' . $base,
                        'App\\Controllers\\' . $base,
                        'App\\Models\\' . $base,
                        'App\\' . $base,
                    ] as $candidate
                ) {
                    if (class_exists($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        // 1. PRIMEIRO: nome da classe de teste -> remover "Test" -> resolver FQCN
        if (preg_match('/\bclass\s+([A-Za-z_][A-Za-z0-9_]*)\s+extends\b/', $testCode, $m)) {
            $testClass = $m[1];
            $base      = preg_replace('/Test$/', '', $testClass);
            if ($base !== '' && $base !== $testClass) {
                foreach (
                    [
                        'App\\Http\\Controllers\\' . $base,
                        'App\\Controllers\\' . $base,
                        'App\\Models\\' . $base,
                        'App\\Services\\' . $base,
                        'App\\' . $base,
                    ] as $candidate
                ) {
                    if (class_exists($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        // 2. FALLBACK: primeiro import "nao-TestCase"
        if (preg_match_all('/^use\s+((?:App|Beaver)\S+);/m', $testCode, $m) === false) {
            return null;
        }

        $imports = array_values(array_filter((array) ($m[1] ?? []), 'is_string'));
        foreach ($imports as $fqcn) {
            if (strpos($fqcn, 'Tests\\') === 0) continue;
            if (substr($fqcn, -8) === 'TestCase') continue;
            return $fqcn;
        }
        return null;
    }

    private function looksHttpBound(ReflectionClass $ref, string $fqcn): bool
    {
        if (strpos($fqcn, '\\Controllers\\') !== false) {
            return true;
        }

        $parent = $ref->getParentClass();
        if ($parent !== false && stripos($parent->getName(), 'Controller') !== false) {
            return true;
        }

        return false;
    }
}
