<?php
/** @var array  $curso */
/** @var ?array $user */
/** @var ?array $tentativa */
/** @var int    $totalQuestoes */
?>
<!DOCTYPE html>
<html lang="pt" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= htmlspecialchars($curso['titulo']) ?> · Beaver Cursos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<?php
  $cssFile = dirname(__DIR__) . '/ui/css/curso.css';
  $cssVer  = is_file($cssFile) ? filemtime($cssFile) : '1';
?>
<link rel="stylesheet" href="/plugins/beaver-cursos/ui/css/curso.css?v=<?= $cssVer ?>">
</head>
<body class="cursos-landing">

<button class="theme-btn" id="theme-toggle" aria-label="Alternar tema">
  <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
  <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
</button>

<div class="cursos-card">
  <div class="cursos-logo">🦫</div>
  <div class="cursos-eyebrow">Curso Oficial</div>
  <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
  <p class="cursos-lead"><?= htmlspecialchars((string)$curso['descricao']) ?></p>

  <div class="cursos-meta">
    <span class="chip">⏱ <b><?= (int)$curso['duracao_min'] ?> min</b></span>
    <span class="chip">📝 <b><?= $totalQuestoes ?> questões</b></span>
    <span class="chip">🔒 <b>Anti-cópia</b></span>
  </div>

  <div class="cursos-price">€<?= number_format((float)$curso['preco'], 2, ',', '.') ?><em> · acesso único</em></div>

  <?php if (!empty($_GET['expirado'])) : ?>
    <div class="cursos-alert err">⏰ O tempo do teste terminou.</div>
  <?php elseif (!empty($_GET['terminado'])) : ?>
    <div class="cursos-alert ok">✅ Teste concluído.</div>
  <?php endif; ?>

  <?php if (!$user) : ?>
    <div class="cursos-divider">Inicia sessão para começar</div>
   <form id="formLogin" method="POST" action="/cursos/login" autocomplete="off">
      <div class="cursos-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="teu@email.com" required>
      </div>
      <div class="cursos-field">
        <label for="password">Palavra-passe</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>
      <div class="cursos-alert err" id="loginError" style="display:none"></div>
      <button type="submit" class="cursos-btn primary">Entrar</button>
    </form>
    <p class="cursos-alt">Ainda não tens conta? <a href="/registo">Criar conta</a></p>

  <?php else : ?>
    <div class="cursos-alert ok">Olá, <strong><?= htmlspecialchars($user['nome']) ?></strong>.</div>

      <?php if ($tentativa && $tentativa['status'] === 'em_curso') : ?>
      <div class="cursos-alert warn">⚠ Tens um teste em curso.</div>
      <a href="/cursos/<?= htmlspecialchars($curso['slug']) ?>/teste" class="cursos-btn primary">Retomar teste</a>

      <?php elseif ($tentativa && $tentativa['status'] === 'terminado') : ?>
      <div class="cursos-alert ok">
        Já concluíste com <strong><?= (int)$tentativa['score'] ?>/<?= (int)$tentativa['total'] ?></strong> corretas.
      </div>
      <a href="/cursos/<?= htmlspecialchars($curso['slug']) ?>/resultado" class="cursos-btn primary">Ver resultado</a>

      <?php else : ?>
      <form id="formIniciar">
        <input type="hidden" name="curso" value="<?= htmlspecialchars($curso['slug']) ?>">
        <button type="submit" class="cursos-btn primary">Iniciar teste</button>
      </form>
      <p class="cursos-alt small">O timer de <b><?= (int)$curso['duracao_min'] ?> minutos</b> começa ao clicar.</p>
      <?php endif; ?>

    <form method="post" action="/cursos/logout" style="margin-top:12px">
      <button type="submit" class="cursos-btn ghost small">Terminar sessão</button>
    </form>
  <?php endif; ?>
</div>

<script>
window.CURSOS_CONFIG = { cursoSlug: <?= json_encode($curso['slug']) ?> };
</script>

<?php
  $jsFile  = dirname(__DIR__) . '/ui/js/curso.js';
  $jsVer   = is_file($jsFile) ? filemtime($jsFile) : '1';
?>
<script src="/plugins/beaver-cursos/ui/js/curso.js?v=<?= $jsVer ?>" defer></script>
</body>
</body>
</html>

