<?php
/** @var array $curso */
/** @var array $user */
/** @var array $tentativa */
/** @var array $questoes */
/** @var array $respostas */
$endsAt = strtotime((string)$tentativa['ends_at']);
$remain = max(0, $endsAt - time());
?>
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Teste · <?= htmlspecialchars($curso['titulo']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/plugins/beaver-cursos/ui/css/curso.css?v=1790283138">
</head>
<body class="cursos-teste">

<header class="teste-bar">
  <div class="teste-bar-left">
    <span class="teste-logo">🦫</span>
    <span class="teste-title"><?= htmlspecialchars($curso['titulo']) ?></span>
  </div>
  <div class="teste-timer" id="timer" data-remain="<?= $remain ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    <span id="timerText">--:--</span>
  </div>
  <button class="teste-finish" id="btnFinish" type="button">Terminar</button>
</header>

<main class="teste-wrap">
  <aside class="teste-nav">
    <h3>Questões</h3>
    <ol id="navList">
      <?php foreach ($questoes as $i => $q) : ?>
        <li>
          <button data-goto="<?= $i ?>" class="<?= isset($respostas[(int)$q['id']]) ? 'done' : '' ?>">
            <?= $i + 1 ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ol>
  </aside>

  <section class="teste-main">
    <div class="teste-progress">
      <div class="bar"><div class="fill" id="progressFill"></div></div>
      <span id="progressText">0 / <?= count($questoes) ?></span>
    </div>

    <?php foreach ($questoes as $i => $q) : ?>
        <?php
        $opcoes = !empty($q['opcoes']) ? json_decode((string)$q['opcoes'], true) : [];
        $respAtual = $respostas[(int)$q['id']]['resposta'] ?? null;
        ?>
      <article class="questao" data-index="<?= $i ?>" data-id="<?= (int)$q['id'] ?>" data-tipo="<?= htmlspecialchars($q['tipo']) ?>" <?= $i > 0 ? 'hidden' : '' ?>>
        <div class="questao-num">Questão <?= $i + 1 ?> de <?= count($questoes) ?></div>
        <h2 class="questao-enunciado"><?= nl2br(htmlspecialchars($q['enunciado'])) ?></h2>

        <?php if ($q['tipo'] === 'escolha') : ?>
          <div class="questao-opcoes">
            <?php foreach ($opcoes as $idx => $opt) : ?>
              <label class="opt">
                <input type="radio" name="q_<?= (int)$q['id'] ?>" value="<?= $idx ?>" <?= $respAtual === (string)$idx ? 'checked' : '' ?>>
                <span class="opt-mark"></span>
                <span class="opt-text"><?= htmlspecialchars((string)$opt) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php else : ?>
          <div class="questao-texto">
            <textarea name="q_<?= (int)$q['id'] ?>" placeholder="Escreve a tua resposta…" rows="3" autocomplete="off" spellcheck="false"><?= htmlspecialchars((string)($respAtual ?? '')) ?></textarea>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>

    <div class="teste-actions">
      <button class="cursos-btn ghost" id="btnPrev" type="button" disabled>← Anterior</button>
      <button class="cursos-btn primary" id="btnNext" type="button">Seguinte →</button>
    </div>
  </section>
</main>

<div class="teste-overlay" id="overlay" hidden>
  <div class="teste-modal">
    <h3>Tens a certeza?</h3>
    <p>Depois de terminado, não podes voltar a responder.</p>
    <div class="teste-modal-actions">
      <button class="cursos-btn ghost" id="btnCancel" type="button">Continuar teste</button>
      <button class="cursos-btn primary" id="btnConfirm" type="button">Terminar e submeter</button>
    </div>
  </div>
</div>

<script>
window.CURSOS_CONFIG = {
  cursoSlug: <?= json_encode($curso['slug']) ?>,
  total:     <?= count($questoes) ?>
};
</script>
<script src="/plugins/beaver-cursos/ui/js/curso.js?v=1790270394"></script>
</body>
</html>
