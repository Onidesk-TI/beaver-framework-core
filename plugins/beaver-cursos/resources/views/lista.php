<?php
/** @var array  $cursos */
/** @var int    $total */
/** @var int    $pagina */
/** @var int    $paginas */
/** @var int    $porPag */
/** @var ?array $user */
?>
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cursos · Beaver</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/plugins/beaver-cursos/ui/css/curso.css?v=1790283138">
</head>
<body class="cursos-page">

<header class="cursos-nav">
  <div class="cursos-nav-inner">
    <a href="/" class="cursos-nav-brand">
      <span class="cursos-nav-logo">🦫</span>
      <span class="cursos-nav-name">Beaver<span class="accent">.</span></span>
    </a>
    <nav class="cursos-nav-links">
      <a href="/">Início</a>
      <a href="/cursos" class="active">Cursos</a>
      <a href="/manual">Manual</a>
      <a href="/pro">Pro</a>
    </nav>
    <div class="cursos-nav-actions">
      <button class="theme-btn-inline" id="theme-toggle" aria-label="Tema">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <?php if ($user) : ?>
        <span class="cursos-nav-user">Olá, <?= htmlspecialchars($user['nome']) ?></span>
      <?php else : ?>
        <a href="/cursos" class="cursos-nav-login">Entrar</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<section class="cursos-hero">
  <h1>Cursos disponíveis</h1>
  <p class="cursos-hero-lead"><?= $total ?> curso<?= $total === 1 ? '' : 's' ?> · certificado incluído · acesso imediato</p>
</section>

<main class="cursos-container">
  <?php if (empty($cursos)) : ?>
    <div class="cursos-empty">
      <div class="cursos-empty-ico">📚</div>
      <h2>Ainda não há cursos</h2>
      <p>Assim que um curso for ativado, aparece aqui.</p>
    </div>
  <?php else : ?>
    <div class="cursos-cards">
      <?php foreach ($cursos as $c) : ?>
        <a class="cursos-card-item" href="/cursos/<?= htmlspecialchars($c['slug']) ?>">
        <div class="cursos-card-thumb"
            <?php if (!empty($c['imagem'])) : ?>
        style="background-image:url('<?= htmlspecialchars($c['imagem']) ?>')"
        data-has-image="1"
            <?php endif; ?>>
        <span class="cursos-card-icon">🎓</span>
            <?php if (!empty($c['licenca_key'])) : ?>
        <span class="cursos-card-badge">PRO</span>
            <?php endif; ?>
        </div>
          <div class="cursos-card-body">
            <h3><?= htmlspecialchars($c['titulo']) ?></h3>
            <p><?= htmlspecialchars((string)$c['descricao']) ?></p>
            <div class="cursos-card-meta">
              <span class="chip">⏱ <?= (int)$c['duracao_min'] ?> min</span>
            </div>
            <div class="cursos-card-foot">
              <span class="cursos-card-price">€<?= number_format((float)$c['preco'], 2, ',', '.') ?></span>
              <span class="cursos-card-cta">Começar →</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

      <?php if ($paginas > 1) : ?>
      <nav class="cursos-pagination" aria-label="Paginação">
            <?php if ($pagina > 1) : ?>
          <a href="/cursos?page=<?= $pagina - 1 ?>" class="pag-btn">← Anterior</a>
            <?php else : ?>
          <span class="pag-btn disabled">← Anterior</span>
            <?php endif; ?>

        <div class="pag-numbers">
            <?php
            $inicio = max(1, $pagina - 2);
            $fim    = min($paginas, $pagina + 2);
            if ($inicio > 1) {
                echo '<a class="pag-num" href="/cursos?page=1">1</a>';
                if ($inicio > 2) {
                    echo '<span class="pag-dots">…</span>';
                }
            }
            for ($i = $inicio; $i <= $fim; $i++) :
                $ativo = $i === $pagina ? ' active' : '';
                ?>
            <a class="pag-num<?= $ativo ?>" href="/cursos?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($fim < $paginas) {
                if ($fim < $paginas - 1) {
                    echo '<span class="pag-dots">…</span>';
                }
                echo '<a class="pag-num" href="/cursos?page=' . $paginas . '">' . $paginas . '</a>';
            } ?>
        </div>

            <?php if ($pagina < $paginas) : ?>
          <a href="/cursos?page=<?= $pagina + 1 ?>" class="pag-btn">Seguinte →</a>
            <?php else : ?>
          <span class="pag-btn disabled">Seguinte →</span>
            <?php endif; ?>
      </nav>

      <p class="cursos-pag-info">
        A mostrar <b><?= count($cursos) ?></b> de <b><?= $total ?></b> cursos · Página <?= $pagina ?> de <?= $paginas ?>
      </p>
      <?php endif; ?>
  <?php endif; ?>
</main>

<footer class="cursos-footer">
  Beaver Framework · feito com <span style="color:var(--accent)">🦫</span>
</footer>

<script>window.CURSOS_CONFIG = {};</script>
<script src="/plugins/beaver-cursos/ui/js/curso.js?v=1790270394"></script>
<script>
document.querySelectorAll('.cursos-card-thumb[data-has-image]').forEach(el => {
  const m = el.style.backgroundImage.match(/url\(['"]?(.*?)['"]?\)/);
  if (!m) return;
  const img = new Image();
  img.onerror = () => {
    el.style.backgroundImage = '';
    el.removeAttribute('data-has-image');
  };
  img.src = m[1];
});
</script>
</body>
</html>
