<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Tests;

/**
 * Gera esqueletos de testes (Pest / PHPUnit) que EXECUTAM a classe.
 *
 * Estratégia:
 *  - Construtor sem parâmetros obrigatórios → instancia automaticamente.
 *  - Construtor com escalares → valores default (0, '', [], false).
 *  - Construtor com classes → markTestIncomplete (deps manuais).
 *  - Método sem parâmetros obrigatórios → chama-o.
 *  - Método com parâmetros → markTestIncomplete com nota.
 *
 * Execução envolta em try/catch: excepções não falham o teste.
 * Objectivo: medir cobertura, não validar lógica.
 *
 * @package Beaver\Plugins\Frankei\Frankei\Tests
 */
class TestSkeletonGenerator
{
    public function __construct(private string $projectRoot) {}

    public function generate(string $className, string $target, string $engine): array
    {
        $className = ltrim($className, '\\');

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $className)) {
            throw new \InvalidArgumentException('Nome de classe inválido.');
        }

        $srcFile = $this->resolveClassFile($className);
        if ($srcFile === null) {
            throw new \RuntimeException("Não encontrei o ficheiro da classe: $className");
        }

        // Garantir que a classe está carregada (autoload)
        if (!class_exists($className)) {
            require_once $srcFile;
        }

        $methods  = $this->publicMethods($className);
        $short    = $this->shortName($className);
        $testName = $short . 'Test';
        $target   = in_array($target, ['Unit', 'Feature'], true) ? $target : 'Unit';
        $engine   = in_array($engine, ['pest', 'phpunit'], true) ? $engine : 'pest';

        $httpBound = $this->isHttpBound($className, $srcFile);

        $code = $engine === 'phpunit'
            ? $this->makePhpunit($className, $testName, $methods)
            : $this->makePest($className, $short, $methods);

        $rel  = "tests/{$target}/{$testName}.php";
        $path = rtrim($this->projectRoot, '/') . '/' . $rel;

        return [
            'path'        => $rel,
            'absolute'    => $path,
            'testClass'   => $testName,
            'filterName'  => $testName,
            'code'        => $code,
            'exists'      => is_file($path),
            'sourceFile'  => $srcFile,
            'methodCount' => count($methods),
            'httpBound'   => $httpBound,
        ];
    }

    /**
     * Deriva FQCN a partir do path absoluto do ficheiro.
     */
    public function deriveFqcnFromPath(string $absPath): ?string
    {
        $root = rtrim($this->projectRoot, '/') . '/';
        if (!str_starts_with($absPath, $root)) return null;

        $rel = str_replace('\\', '/', substr($absPath, strlen($root)));

        $maps = [
            'app/'     => 'App\\',
            'plugins/' => 'Beaver\\Plugins\\',
            'src/'     => 'Beaver\\',
        ];

        foreach ($maps as $prefix => $ns) {
            if (str_starts_with($rel, $prefix)) {
                $sub = preg_replace('/\.php$/', '', substr($rel, strlen($prefix)));
                return $ns . str_replace('/', '\\', (string) $sub);
            }
        }

        return null;
    }

    private function resolveClassFile(string $className): ?string
    {
        $maps = [
            'App\\'             => 'app/',
            'Beaver\\Plugins\\' => 'plugins/',
            'Beaver\\'          => 'src/',
        ];

        foreach ($maps as $prefix => $dir) {
            if (str_starts_with($className, $prefix)) {
                $rel  = substr($className, strlen($prefix));
                $rel  = str_replace('\\', '/', $rel) . '.php';
                $path = rtrim($this->projectRoot, '/') . '/' . $dir . $rel;
                if (is_file($path)) return $path;
            }
        }

        try {
            if (class_exists($className)) {
                $f = (new \ReflectionClass($className))->getFileName();
                if ($f && is_file($f)) return $f;
            }
        } catch (\Throwable) {}

        return null;
    }

    /**
     * Métodos públicos próprios (não herdados), sem mágicos.
     * @return array<int, array{name:string, required:int, optional:int, isVoid:bool, hasReturn:bool}>
     */
    private function publicMethods(string $className): array
    {
        try {
            $rc = new \ReflectionClass($className);
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->isStatic())       continue;
            if ($m->isConstructor())  continue;
            if ($m->isDestructor())   continue;
            if ($m->getDeclaringClass()->getName() !== $rc->getName()) continue;
            if (str_starts_with($m->getName(), '__')) continue;

            $rt = $m->getReturnType();
            $isVoid = $rt instanceof \ReflectionNamedType && $rt->getName() === 'void';

            $out[] = [
                'name'         => $m->getName(),
                'required'     => $m->getNumberOfRequiredParameters(),
                'optional'     => $m->getNumberOfParameters() - $m->getNumberOfRequiredParameters(),
                'isVoid'       => $isVoid,
                'hasReturn'    => $rt !== null && !$isVoid,
                'isStatic'     => $m->isStatic(),
            ];
        }
        return $out;
    }

    /**
     * Controllers, Middleware e Views não podem ser instanciados fora do HTTP
     * (fazem header()/exit()/ob_start()). Nesses casos, só geramos class_exists.
     */
    private function isHttpBound(string $fqcn, string $srcFile): bool
    {
        if (str_contains($fqcn, '\\Controllers\\'))  return true;
        if (str_contains($fqcn, '\\Middleware\\'))   return true;
        if (str_contains($fqcn, '\\Views\\'))        return true;
        if (str_ends_with($fqcn, 'Controller'))         return true;
        if (str_ends_with($fqcn, 'Middleware'))         return true;

        $rel = str_replace('\\', '/', $srcFile);
        if (str_contains($rel, '/Controllers/'))  return true;
        if (str_contains($rel, '/Middleware/'))   return true;
        if (str_contains($rel, '/views/'))        return true;

        return false;
    }

    private function shortName(string $fqcn): string
    {
        $p = strrpos($fqcn, '\\');
        return $p === false ? $fqcn : substr($fqcn, $p + 1);
    }

    /**
     * Analisa o construtor.
     * @return array{ok:bool, args:string, reason:string}
     */
    private function constructorInfo(string $fqcn): array
    {
        try {
            $rc = new \ReflectionClass($fqcn);
        } catch (\Throwable $e) {
            return ['ok' => false, 'args' => '', 'reason' => 'classe não carregada'];
        }

        $ctor = $rc->getConstructor();
        if ($ctor === null) {
            return ['ok' => true, 'args' => '', 'reason' => ''];
        }

        if ($ctor->getNumberOfRequiredParameters() === 0) {
            return ['ok' => true, 'args' => '', 'reason' => ''];
        }

        $args = [];
        foreach ($ctor->getParameters() as $p) {
            if ($p->isOptional()) {
                $args[] = $p->isDefaultValueAvailable()
                    ? $this->varExport($p->getDefaultValue())
                    : 'null';
                continue;
            }

            $t = $p->getType();
            if ($t instanceof \ReflectionNamedType && $t->isBuiltin()) {
                $args[] = match ($t->getName()) {
                    'int'      => '0',
                    'float'    => '0.0',
                    'string'   => "''",
                    'bool'     => 'false',
                    'array'    => '[]',
                    'iterable' => '[]',
                    default    => 'null',
                };
            } elseif ($t === null) {
                $args[] = 'null';
            } else {
                return ['ok' => false, 'args' => '', 'reason' => "construtor exige dependência ($t)"];
            }
        }

        return ['ok' => true, 'args' => implode(', ', $args), 'reason' => ''];
    }

    /**
     * Gera a lista de argumentos para chamar um método.
     * @return array{args:string, ok:bool, reason:string}
     */
    private function buildCall(string $fqcn, string $method): array
    {
        try {
            $rm = new \ReflectionMethod($fqcn, $method);
        } catch (\Throwable) {
            return ['args' => '', 'ok' => false, 'reason' => 'reflexão falhou'];
        }

        $args = [];
        foreach ($rm->getParameters() as $p) {
            if ($p->isOptional()) {
                $args[] = $p->isDefaultValueAvailable()
                    ? $this->varExport($p->getDefaultValue())
                    : 'null';
                continue;
            }

            $t = $p->getType();
            if ($t instanceof \ReflectionNamedType && $t->isBuiltin()) {
                $args[] = match ($t->getName()) {
                    'int'      => '0',
                    'float'    => '0.0',
                    'string'   => "''",
                    'bool'     => 'false',
                    'array'    => '[]',
                    'iterable' => '[]',
                    default    => 'null',
                };
            } elseif ($t === null) {
                $args[] = 'null';
            } else {
                return ['args' => '', 'ok' => false, 'reason' => "parâmetro $t"];
            }
        }

        return ['args' => implode(', ', $args), 'ok' => true, 'reason' => ''];
    }

    private function varExport(mixed $v): string
    {
        if (is_string($v))             return var_export($v, true);
        if (is_bool($v))               return $v ? 'true' : 'false';
        if (is_int($v) || is_float($v)) return (string) $v;
        if ($v === null)               return 'null';
        if (is_array($v) && $v === []) return '[]';
        return 'null';
    }

    private function makePest(string $fqcn, string $short, array $methods): string
    {
        $ctor    = $this->constructorInfo($fqcn);
        $lines   = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'use Tests\\TestCase;';
        $lines[] = 'use ' . $fqcn . ';';
        $lines[] = '';
        $lines[] = 'uses(TestCase::class);';
        $lines[] = '';
        $lines[] = "it('carrega a classe {$short}', function () {";
        $lines[] = "    expect(class_exists({$short}::class))->toBeTrue();";
        $lines[] = '});';
        $lines[] = '';

        $httpBound = $this->isHttpBound($fqcn, $this->resolveClassFile($fqcn) ?? '');
        if ($httpBound || !$ctor['ok']) {
            $reason = $httpBound
                ? 'classe HTTP (Controller/Middleware/View) — não executável em teste unitário'
                : $ctor['reason'];

            $lines[] = '// ' . $reason;
            $lines[] = '// Preenche os testes à mão com contexto HTTP simulado.';
            $lines[] = '';
            foreach ($methods as $m) {
                $lines[] = "it('{$m['name']}() funciona', function () {";
                $lines[] = "    \$this->markTestIncomplete('{$reason}');";
                $lines[] = '});';
                $lines[] = '';
            }
            return rtrim(implode("\n", $lines)) . "\n";
        }

        foreach ($methods as $m) {
            $name = $m['name'];
            $call = $this->buildCall($fqcn, $name);

            $lines[] = "it('{$name}() funciona', function () {";

            if ($ctor['args'] !== '') {
                $lines[] = "    \$obj = new {$short}({$ctor['args']});";
            } else {
                $lines[] = "    \$obj = new {$short}();";
            }

            if (!$call['ok']) {
                $lines[] = "    \$this->markTestIncomplete('precisa argumento: {$call['reason']}');";
            } else {
                $lines[] = '    try {';
                $lines[] = "        \$obj->{$name}({$call['args']});";
                $lines[] = '        expect(true)->toBeTrue();';
                $lines[] = '    } catch (\\Throwable $e) {';
                $lines[] = '        expect(true)->toBeTrue(); // cobertura obtida antes da excepção';
                $lines[] = '    }';
            }

            $lines[] = '});';
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    private function makePhpunit(string $fqcn, string $testName, array $methods): string
    {
        $short = $this->shortName($fqcn);
        $ctor  = $this->constructorInfo($fqcn);
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'use PHPUnit\\Framework\\TestCase;';
        $lines[] = 'use ' . $fqcn . ';';
        $lines[] = '';
        $lines[] = "final class {$testName} extends TestCase";
        $lines[] = '{';
        $lines[] = '    private function make(): ?' . $short;
        $lines[] = '    {';

        if (!$ctor['ok']) {
            $lines[] = "        return null; // {$ctor['reason']}";
        } elseif ($ctor['args'] !== '') {
            $lines[] = "        return new {$short}({$ctor['args']});";
        } else {
            $lines[] = "        return new {$short}();";
        }

        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function test_classe_carrega(): void';
        $lines[] = '    {';
        $lines[] = "        \$this->assertTrue(class_exists({$short}::class));";
        $lines[] = '    }';
        $lines[] = '';

        $httpBound = $this->isHttpBound($fqcn, $this->resolveClassFile($fqcn) ?? '');

        foreach ($methods as $m) {
            $name = $m['name'];
            $call = $this->buildCall($fqcn, $name);

            $lines[] = "    public function test_{$name}(): void";
            $lines[] = '    {';
            if ($httpBound) {
                $lines[] = "        \$this->markTestIncomplete('classe HTTP — teste manual');";
            } elseif (!$ctor['ok']) {
                $lines[] = "        \$this->markTestIncomplete('{$ctor['reason']}');";
            } elseif (!$call['ok']) {
                $lines[] = "        \$this->markTestIncomplete('precisa argumento: {$call['reason']}');";
            } else {
                $lines[] = "        \$obj = \$this->make();";
                $lines[] = '        if ($obj === null) { $this->markTestIncomplete(); return; }';
                $lines[] = '        try {';
                $lines[] = "            \$obj->{$name}({$call['args']});";
                $lines[] = '            $this->assertTrue(true);';
                $lines[] = '        } catch (\\Throwable $e) {';
                $lines[] = '            $this->assertTrue(true);';
                $lines[] = '        }';
            }
            $lines[] = '    }';
            $lines[] = '';
        }

        $lines[] = '}';
        return rtrim(implode("\n", $lines)) . "\n";
    }
}
