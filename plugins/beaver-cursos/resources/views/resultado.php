<?php
/** @var array $curso */
/** @var array $user */
/** @var array $tentativa */
/** @var array $questoes */
/** @var array $respostas */

$total  = (int)$tentativa['total'];
$score  = (int)$tentativa['score'];
$pct    = $total > 0 ? (int)round(($score / $total) * 100) : 0;
$passou = $pct >= 60;
?>
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resultado · <?= htmlspecialchars($curso['titulo']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/plugins/beaver-cursos/ui/css/curso.css?v=1790283138">
</head>
<body class="cursos-landing">

<button class="theme-btn" id="theme-toggle" aria-label="Alternar tema">
  <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
  <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>

<div class="cursos-card resultado">
  <div class="resultado-ico <?= $passou ? 'ok' : 'fail' ?>">
    <?= $passou ? '🎉' : '📚' ?>
  </div>
  <div class="cursos-eyebrow"><?= $passou ? 'Aprovado' : 'Podes melhorar' ?></div>
  <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
  <p class="cursos-lead">Olá, <strong><?= htmlspecialchars($user['nome']) ?></strong>.</p>

  <div class="resultado-score">
    <b><?= $score ?>/<?= $total ?></b>
    <em><?= $pct ?>%</em>
  </div>

  <div class="resultado-bar">
    <div class="fill" style="width: <?= $pct ?>%"></div>
  </div>

  <div class="resultado-detalhe">
    <?php foreach ($questoes as $i => $q) : ?>
        <?php $r = $respostas[(int)$q['id']] ?? null; ?>
      <div class="resultado-item <?= $r && $r['correta'] ? 'ok' : 'fail' ?>">
        <span class="num"><?= $i + 1 ?></span>
        <div>
          <div class="enunciado"><?= htmlspecialchars($q['enunciado']) ?></div>
          <?php if ($r) : ?>
            <div class="resposta">
                <?= $r['correta'] ? '✔ Correta' : '✖ Incorreta' ?>
                <?php if ($q['tipo'] === 'texto') : ?>
                — <code><?= htmlspecialchars((string)$r['resposta']) ?></code>
                <?php endif; ?>
            </div>
          <?php else : ?>
            <div class="resposta">— Sem resposta</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="resultado-acoes">
    <a href="/cursos/<?= htmlspecialchars($curso['slug']) ?>" class="cursos-btn primary">Voltar ao curso</a>
    <a href="/cursos" class="cursos-btn ghost">Ver todos os cursos</a>
  </div>
</div>

<script>
window.CURSOS_CONFIG = { cursoSlug: <?= json_encode($curso['slug']) ?> };
</script>
<script src="/plugins/beaver-cursos/ui/js/curso.js?v=1790270394"></script>
</body>
</html>
