<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei\Terminal;

use Beaver\Foundation\Application;

class BeaverReportGenerator
{
  private string $root;
  private string $reportDir;
  private string $resourcesDir;

  public function __construct()
  {
    $this->root         = Application::getInstance()->basePath ?? getcwd();
    $this->reportDir    = $this->root . '/storage/frankei-reports';
    $this->resourcesDir = dirname(__DIR__, 3) . '/resources/reports';
  }

  /** @return array{ok: bool, path?: string, html?: string, error?: string} */
  public function generate(int $userId, string $plan, string $lang = 'pt'): array
  {
    $currentDir = $this->reportDir . '/' . $userId . '/current';

    if (!is_dir($currentDir)) {
      return ['ok' => false, 'error' => 'Sem reports anteriores. Gera o report primeiro.'];
    }

    $junit  = $currentDir . '/junit.xml';
    $clover = $currentDir . '/clover.xml';

    if (!is_file($junit)) {
      return ['ok' => false, 'error' => 'junit.xml não encontrado'];
    }

    $testes   = $this->parseJunit($junit);
    $coverage = is_file($clover) ? $this->parseClover($clover) : null;

    $html = $this->renderHtml($testes, $coverage);
    // FK_I18N_GEN: injetar i18n + donuts
    $html = $this->fkInjectI18n($html, $lang);
    $html = $this->fkInjectDonuts($html, $testes, $coverage);

    $outPath = $currentDir . '/beaver-test-report.html';
    @file_put_contents($outPath, $html);

    return ['ok' => true, 'path' => $outPath, 'html' => $html];
  }

  private function parseJunit(string $file): array
    {
        $xml = @simplexml_load_file($file);
        if ($xml === false) {
            return ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'time' => 0.0, 'suites' => []];
        }

        $out = ['tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'time' => 0.0, 'suites' => []];

        // FK_JUNIT_RECURSIVE
        $this->fkWalkSuiteRecursive($xml->testsuite, $out);

        usort($out['suites'], function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        return $out;
    }

    private function fkWalkSuiteRecursive($suites, &$out): void
    {
        foreach ($suites as $suite) {
            $temFilhos    = isset($suite->testsuite) && count($suite->testsuite) > 0;
            $temTestcases = isset($suite->testcase)  && count($suite->testcase)  > 0;

            if ($temFilhos) {
                $this->fkWalkSuiteRecursive($suite->testsuite, $out);
                continue;
            }

            if (!$temTestcases) {
                continue;
            }

            $tests      = (int) ($suite['tests'] ?? 0);
            $assertions = (int) ($suite['assertions'] ?? 0);
            $failures   = (int) ($suite['failures'] ?? 0);
            $errors     = (int) ($suite['errors'] ?? 0);
            $skipped    = (int) ($suite['skipped'] ?? 0);
            $time       = (float) ($suite['time'] ?? 0);
            $name       = (string) ($suite['name'] ?? '');

            $out['tests']      += $tests;
            $out['assertions'] += $assertions;
            $out['failures']   += $failures;
            $out['errors']     += $errors;
            $out['time']       += $time;

            $out['suites'][] = [
                'name'       => $this->shortName($name),
                'name_full'  => $name,
                'tests'      => $tests,
                'assertions' => $assertions,
                'failures'   => $failures,
                'errors'     => $errors,
                'skipped'    => $skipped,
                'time'       => $time,
            ];
        }
    }

  private function parseClover(string $file): ?array
  {
    $xml = @simplexml_load_file($file);
    if ($xml === false) return null;

    $metrics = $xml->project->metrics ?? null;
    if ($metrics === null) return null;

    $total = [
      'statements' => (int) $metrics['statements'],
      'covered'    => (int) $metrics['coveredstatements'],
    ];
    $total['percent'] = $total['statements'] > 0
      ? round(100 * $total['covered'] / $total['statements'], 1)
      : 0;

    $files = [];
    foreach ($xml->project->file as $f) {
      $m = $f->metrics ?? null;
      if ($m === null) continue;

      $st = (int) $m['statements'];
      $cv = (int) $m['coveredstatements'];
      if ($st === 0) continue;

      $pct   = round(100 * $cv / $st, 1);
      $level = 'notcovered';
      if ($cv >= $st)   $level = 'covered';
      elseif ($cv > 0)  $level = 'partial';

      $files[] = [
        'name'    => (string) $f['name'],
        'short'   => $this->shortPath((string) $f['name']),
        'stmts'   => $st,
        'covered' => $cv,
        'percent' => $pct,
        'level'   => $level,
      ];
    }

    usort($files, fn($a, $b) => $b['percent'] <=> $a['percent']);

    return ['total' => $total, 'files' => $files];
  }

  private function shortName(string $name): string
  {
    $parts = explode('\\', $name);
    return end($parts);
  }

  private function shortPath(string $path): string
  {
    $path = str_replace($this->root, '', $path);
    return ltrim($path, '/');
  }

  private function renderHtml(array $testes, ?array $coverage): string
  {
    // Carregar template + CSS
    $templateFile = $this->resourcesDir . '/beaver-report.html';
    $cssFile      = $this->resourcesDir . '/beaver-report.css';

    if (!is_file($templateFile) || !is_file($cssFile)) {
      throw new \RuntimeException('Template ou CSS do Beaver Report não encontrado em ' . $this->resourcesDir);
    }

    $template = (string) file_get_contents($templateFile);
    $css      = (string) file_get_contents($cssFile);

    // Cards
    $tests    = $testes['tests'];
    $failures = $testes['failures'] + $testes['errors'];
    $passed   = $tests - $failures;
    $time     = number_format($testes['time'], 3);
    $covPct   = $coverage['total']['percent'] ?? 0;

    $cards  = $this->card('✅', (string) $passed, 'Testes passados', 'pass');
    $cards .= $this->card('❌', (string) $failures, 'Falhas / Erros', 'fail');
    $cards .= $this->card('📊', (string) $testes['assertions'], 'Assertions');
    $cards .= $this->card('⚡', $time . 's', 'Duração');
    $cards .= $this->card('📈', $covPct . '%', 'Cobertura');

    // Secções
    $sections = '';

    // ── Lista completa com filtros ──
    if (!empty($coverage['files'])) {
      $sections .= '  <div class="section">' . "\n";
      $sections .= '    <h2>📋 Cobertura por ficheiro</h2>' . "\n";
      $sections .= '    <div class="fk-cov-tabs">' . "\n";
      // FK_FILTER_I18N_BUTTONS: labels traduziveis com data-i18n      // FK_REAL_COUNTS_V2: emojis literais + contagem correta
      $totalFiles = count($coverage['files'] ?? []);
      $nCovered = 0; $nPartial = 0; $nNotCov = 0;
      foreach (($coverage['files'] ?? []) as $f) {
        // Tentar várias estruturas possíveis
        $cov = null;
        if (isset($f['lines']['covered']) && isset($f['lines']['total'])) {
          $cov = ['c' => (int)$f['lines']['covered'], 't' => (int)$f['lines']['total']];
        } elseif (isset($f['covered']) && isset($f['total'])) {
          $cov = ['c' => (int)$f['covered'], 't' => (int)$f['total']];
        } elseif (isset($f['statements']['covered']) && isset($f['statements']['total'])) {
          $cov = ['c' => (int)$f['statements']['covered'], 't' => (int)$f['statements']['total']];
        } elseif (isset($f['percent'])) {
          // Sem contagens, usar percentagem directa
          $pct = (float)$f['percent'];
          if ($pct >= 100)      $nCovered++;
          elseif ($pct > 0)     $nPartial++;
          else                  $nNotCov++;
          continue;
        }
        if ($cov === null) {
          $nNotCov++;
          continue;
        }
        if ($cov['t'] <= 0) {
          $nNotCov++;
        } elseif ($cov['c'] >= $cov['t']) {
          $nCovered++;
        } elseif ($cov['c'] > 0) {
          $nPartial++;
        } else {
          $nNotCov++;
        }
      }

      $sections .= '      <button data-filter="all" class="active">📋 <span data-i18n="f_all">Todas</span> (' . $totalFiles . ')</button>' . "\n";
      $sections .= '      <button data-filter="covered">🟢 <span data-i18n="f_covered">Cobertas</span> (' . $nCovered . ')</button>' . "\n";
      $sections .= '      <button data-filter="partial">🟠 <span data-i18n="f_partial">Parcial</span> (' . $nPartial . ')</button>' . "\n";
      $sections .= '      <button data-filter="notcovered">🔴 <span data-i18n="f_uncovered">Nao cobertas</span> (' . $nNotCov . ')</button>' . "\n";
      $sections .= '    <div class="fk-cov-list">' . "\n";
      foreach ($coverage['files'] as $f) {
        $sections .= $this->renderBar($f);
      }
      $sections .= '    </div>' . "\n";
      $sections .= "  </div>\n";
    }

    if ($testes['suites']) {
      $sections .= '  <div class="section">' . "\n";
      $sections .= '    <h2>🧪 Suites de testes</h2>' . "\n";
      $sections .= '    <div class="test-list">' . "\n";
      foreach ($testes['suites'] as $s) {
        $cls  = ($s['failures'] + $s['errors']) > 0 ? 'fail' : '';
        $icon = ($s['failures'] + $s['errors']) > 0 ? '❌' : '✅';
        $sections .= '      <div class="test-item ' . $cls . '">' . "\n";
        $sections .= '        <span class="name">' . $icon . ' ' . htmlspecialchars($s['name']) . '</span>' . "\n";
        $sections .= '        <span class="time">' . number_format($s['time'], 3) . 's</span>' . "\n";
        $sections .= '      </div>' . "\n";
      }
      $sections .= "    </div>\n";
      $sections .= "  </div>\n";
    }

    // Classes condicionais do body
    $opts = ['icons' => true, 'gradient' => true, 'anim' => true, 'count' => true, 'group' => false, 'module' => true, 'tooltip' => true];
    $bodyClasses = [];
    foreach ($opts as $k => $v) {
      $bodyClasses[] = 'beaver-' . $k . '-' . ($v ? 'on' : 'off');
    }

    // JS dos filtros
    $filtersJs = <<<'JS'
<script>
(function(){
    document.querySelectorAll('.fk-cov-tabs button').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('.fk-cov-tabs button').forEach(function(b){
                b.classList.remove('active');
            });
            btn.classList.add('active');

            var filter = btn.dataset.filter;
            document.querySelectorAll('.fk-cov-list .bar-row').forEach(function(row){
                var level = row.dataset.level || '';
                row.style.display = (filter === 'all' || level === filter) ? '' : 'none';
            });
        });
    });
})();
</script>
JS;

    // Substituir placeholders
    $html = str_replace(
      ['{{CSS}}', '{{BODY_CLASSES}}', '{{DATE}}', '{{CARDS}}', '{{SECTIONS}}', '{{TESTS}}', '{{ASSERTIONS}}'],
      [$css,       implode(' ', $bodyClasses), $this->now(), $cards, $sections, (string) $tests, (string) $testes['assertions']],
      $template
    );

    // Inserir JS antes de </body>
    $html = str_replace('</body>', $filtersJs . "\n</body>", $html);

    return $html;
  }

  private function card(string $icon, string $value, string $label, string $cls = ''): string
  {
    $c = $cls !== '' ? ' ' . $cls : '';
    return '    <div class="card' . $c . '">' . "\n"
      . '      <div class="card-icon">' . $icon . '</div>' . "\n"
      . '      <div class="card-value">' . htmlspecialchars($value) . '</div>' . "\n"
      . '      <div class="card-label">' . htmlspecialchars($label) . '</div>' . "\n"
      . '    </div>' . "\n";
  }

  private function renderBar(array $f): string
  {
    $pct = $f['percent'];
    $cls = $pct >= 80 ? 'g' : ($pct >= 50 ? 'y' : 'r');

    $short = $f['short'];
    $parts = explode('\\', $short);
    $class = array_pop($parts);
    $module = implode('\\', $parts);

    $moduleHtml = $module !== ''
      ? '<span class="module">' . htmlspecialchars($module) . '\\</span>'
      : '';
    $classHtml = '<span class="class">' . htmlspecialchars($class) . '</span>';

    $stmts   = $f['stmts'] ?? 0;
    $covered = $f['covered'] ?? 0;
    $title   = htmlspecialchars($f['name']);

    $level = $f['level'] ?? 'notcovered';
    $h  = '    <div class="bar-row" data-level="' . $level . '" title="' . $title . '">' . "\n";
    $h .= '      <span class="bar-icon ' . $cls . '"></span>' . "\n";
    $h .= '      <span class="bar-name">' . $moduleHtml . $classHtml . '</span>' . "\n";
    $h .= '      <div class="bar-track">' . "\n";
    $h .= '        <div class="bar-fill ' . $cls . '" style="width:' . $pct . '%"></div>' . "\n";
    $h .= '      </div>' . "\n";
    $h .= '      <span class="bar-count">' . $covered . ' / ' . $stmts . '</span>' . "\n";
    $h .= '      <span class="bar-pct ' . $cls . '">' . $pct . '%</span>' . "\n";
    $h .= '    </div>' . "\n";

    return $h;
  }

  private function now(): string
  {
    return date('d/m/Y H:i:s');
  }

    // ================================================================
    // FK_I18N_GEN — helpers de i18n + donuts
    // ================================================================

    private function fkLoadI18n(): array
    {
        $file = $this->resourcesDir . '/i18n/all.json';
        if (!is_file($file)) {
            return ['pt' => ['flag' => '🇵🇹', 'name' => 'Português']];
        }
        $data = @json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    private function fkInjectI18n(string $html, string $lang): string
    {
        $lang = preg_replace('/[^a-z]/', '', strtolower($lang));
        if (!in_array($lang, ['pt','en','fr','es'], true)) {
            $lang = 'pt';
        }
        $i18n = $this->fkLoadI18n();

        $html = str_replace('{{I18N_JSON}}', json_encode($i18n, JSON_UNESCAPED_UNICODE), $html);
        $html = str_replace('{{LANG}}', $lang, $html);

        return $html;
    }

    private function fkInjectDonuts(string $html, array $testes, ?array $coverage): string
    {
        // 1. Taxa de sucesso dos testes
        $total   = (int) ($testes['tests'] ?? 0);
        $passed  = max(0, $total - (int)($testes['failures'] ?? 0) - (int)($testes['errors'] ?? 0));
        $passPct = $total > 0 ? round($passed / $total * 100, 1) : 0;

        // 2. Cobertura de linhas (clover)
        $covPct = 0;
        if ($coverage && isset($coverage['lines']['percent'])) {
            $covPct = (float) $coverage['lines']['percent'];
        } elseif ($coverage && isset($coverage['percent'])) {
            $covPct = (float) $coverage['percent'];
        }

        // 3. Assertions por teste
        $asserts = (int) ($testes['assertions'] ?? 0);
        $assertAvg = $total > 0 ? round($asserts / $total, 1) : 0;
        // Usar como "percentagem" ate 10 (escala visual)
        $assertPct = min(100, $assertAvg * 10);

        // Donuts
        $donuts = [
            [
                'pct'   => $passPct,
                'label' => 'donut_pass',
                'sub'   => $passed . ' / ' . $total . ' ok',
            ],
            [
                'pct'   => $covPct,
                'label' => 'donut_coverage',
                'sub'   => number_format($covPct, 1, ',', '.') . '%',
            ],
            [
                'pct'   => $assertPct,
                'label' => 'donut_assertions',
                'sub'   => $asserts . ' total',
            ],
        ];

        $html_out = '';
        $json = [];
        foreach ($donuts as $d) {
            $pct = round((float) $d['pct'], 1);
            // Circunferencia para r=42 -> 2*PI*42 = 263.89
            $circ = 263.89;
            $offset = $circ * (1 - $pct / 100);
            $lvl = $pct >= 80 ? 'good' : ($pct >= 50 ? 'medium' : ($pct > 0 ? 'low' : 'none'));

            $html_out .= '<div class="fk-donut ' . $lvl . '">'
                . '<svg viewBox="0 0 100 100" width="120" height="120" aria-hidden="true">'
                .   '<circle class="fk-donut-ring-track" cx="50" cy="50" r="42"></circle>'
                .   '<circle class="fk-donut-ring-fill" cx="50" cy="50" r="42" '
                .     'stroke-dasharray="' . $circ . '" '
                .     'stroke-dashoffset="' . round($offset, 2) . '"></circle>'
                .   '<text class="fk-donut-pct" x="50" y="50" dy=".35em">' . number_format($pct, 1, ',', '.') . '%</text>'
                . '</svg>'
                . '<span class="fk-donut-label" data-i18n="' . $d['label'] . '">' . $d['label'] . '</span>'
                . '<span class="fk-donut-sub">' . htmlspecialchars($d['sub']) . '</span>'
                . '</div>';

            $json[] = ['pct' => $pct, 'label' => $d['label'], 'sub' => $d['sub']];
        }

        $html = str_replace('{{DONUTS}}', $html_out, $html);
        $html = str_replace('{{DONUTS_JSON}}', json_encode($json, JSON_UNESCAPED_UNICODE), $html);

        return $html;
    }

}
