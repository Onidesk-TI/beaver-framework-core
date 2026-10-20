<?php
/**
 * Beaver Admin — layout base.
 *
 * @var array  $sidebarItems  Items do menu [{label, href, icon, order, section}, ...]
 * @var string $pageTitle     Título da página
 * @var string $pageContent   HTML do conteúdo central (sem <main>)
 * @var string $activeHref    Href do item ativo
 * @var string $userName      Nome do utilizador
 * @var string $userEmail     Email do utilizador
 */

// Agrupar items por secção
$sections = [];
foreach ($sidebarItems as $item) {
    $section = $item['section'] ?? 'Principal';
    $sections[$section][] = $item;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> · Beaver Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/resources/ui/css/beaver.css">
<style>
  *{box-sizing:border-box}
  body{margin:0;font-family:Inter,sans-serif;background:#fdf8ef;color:#2E1A10;margin-left:240px;padding-top:56px}
  .sidebar{position:fixed;left:0;top:0;bottom:0;width:240px;background:#fff8ec;border-right:1px solid #eadfc7;display:flex;flex-direction:column;z-index:50}
  .sidebar-brand{display:flex;align-items:center;gap:.55rem;padding:1.1rem 1.2rem;border-bottom:1px solid #eadfc7;font-weight:700;font-size:1rem}
  .sidebar-nav{flex:1;overflow-y:auto;padding:.8rem .6rem;display:flex;flex-direction:column;gap:.1rem}
  .sidebar-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#a08f7a;padding:.9rem .8rem .35rem;font-weight:600}
  .sidebar-link{display:flex;align-items:center;gap:.7rem;padding:.55rem .8rem;border-radius:8px;color:#6b5544;text-decoration:none;font-size:.88rem}
  .sidebar-link i{width:18px;text-align:center;color:#a08f7a}
  .sidebar-link:hover{background:#f5ead0;color:#2E1A10}
  .sidebar-link:hover i{color:#c25a1f}
  .sidebar-link.is-active{background:linear-gradient(140deg,#FF9A3C,#FF6B35);color:#fff;font-weight:600}
  .sidebar-link.is-active i{color:#fff}
  .sidebar-footer{padding:.6rem;border-top:1px solid #eadfc7}
  .app-header{position:fixed;top:0;left:240px;right:0;height:56px;background:#fff8ec;border-bottom:1px solid #eadfc7;z-index:40}
  .app-header-inner{display:flex;justify-content:space-between;align-items:center;height:100%;padding:0 1.5rem;max-width:1100px;margin:0 auto}
  .app-header-nav{display:flex;gap:1rem;align-items:center}
  .app-header-link{text-decoration:none;color:#6b5544;font-size:.88rem}
  .app-header-link:hover{color:#c25a1f}
  .user-menu{position:relative}
  .user-btn{display:flex;align-items:center;justify-content:center;width:38px;height:38px;background:#fff;border:1px solid #eadfc7;border-radius:50%;cursor:pointer;color:#c25a1f}
  .user-icon{width:20px;height:20px}
  .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;min-width:220px;background:#fff;border:1px solid #eadfc7;border-radius:10px;box-shadow:0 8px 24px rgba(74,44,29,.14);padding:.8rem;display:none;z-index:60}
  .user-dropdown.open{display:block}
  .user-info{display:flex;flex-direction:column;gap:.15rem;padding-bottom:.6rem;margin-bottom:.6rem;border-bottom:1px solid #eee4cf}
  .user-info small{font-size:.75rem;color:#8a7560;font-family:'JetBrains Mono',monospace;word-break:break-all}
  .user-logout{width:100%;padding:.55rem;background:#fff;border:1px solid #e2d4b8;border-radius:6px;cursor:pointer;font-family:inherit;font-size:.85rem;color:#991b1b}
  .user-logout:hover{background:#fef2f2}
  main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem}
</style>
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <linearGradient id="bvg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#FFD79A"/>
      <stop offset="48%" stop-color="#FF9A3C"/>
      <stop offset="100%" stop-color="#FF6B35"/>
    </linearGradient>
    <symbol id="beaver" viewBox="0 0 64 64">
      <circle cx="13" cy="17.5" r="7.5" fill="url(#bvg)" opacity=".72"/>
      <circle cx="51" cy="17.5" r="7.5" fill="url(#bvg)" opacity=".72"/>
      <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvg)"/>
      <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".18"/>
      <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208"/>
      <circle cx="22.5" cy="26" r="3.4" fill="#1A1208"/>
      <circle cx="41.5" cy="26" r="3.4" fill="#1A1208"/>
      <circle cx="23.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".9"/>
      <circle cx="42.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".9"/>
      <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>
      <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>
    </symbol>
  </defs>
</svg>

<aside class="sidebar">
  <div class="sidebar-brand">
    <svg style="width:28px;height:28px"><use href="#beaver"/></svg>
    <span>Beaver<span style="color:#c25a1f">.</span> Admin</span>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($sections as $sectionName => $items) : ?>
      <span class="sidebar-label"> <?= htmlspecialchars($sectionName) ?></span>
        <?php foreach ($items as $item) : ?>
            <?php
            $href = (string) ($item['href'] ?? '#');
            $isActive = $activeHref !== '' && (
              $href === $activeHref
              || str_starts_with($activeHref, rtrim($href, '/') . '/')
            );
            ?>
        <a
          href="<?= htmlspecialchars($href) ?>"
          class="sidebar-link<?= $isActive ? ' is-active' : '' ?>"
        >
          <i class="fas <?= htmlspecialchars($item['icon'] ?? 'fa-circle') ?>"></i>
            <?= htmlspecialchars((string) ($item['label'] ?? '')) ?>
        </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
</aside>

<header class="app-header">
  <div class="app-header-inner">
    <div></div>
    <nav class="app-header-nav">
      <a href="/" class="app-header-link">Home</a>
      <a href="/plugins" class="app-header-link">Marketplace</a>
      <a href="/sdk" class="app-header-link">SDK</a>
      <div class="user-menu">
        <button type="button" class="user-btn" id="user-toggle" title="<?= htmlspecialchars($userName) ?>">
          <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>
          </svg>
        </button>
        <div class="user-dropdown" id="user-dropdown">
          <div class="user-info">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <small><?= htmlspecialchars($userEmail) ?></small>
          </div>
          <form method="post" action="/logout">
            <button type="submit" class="user-logout">
              <i class="fas fa-sign-out-alt"></i> Sair
            </button>
          </form>
        </div>
      </div>
    </nav>
  </div>
</header>

<main>
  <?= $pageContent ?>
</main>

<script>
(function(){
  const toggle = document.getElementById('user-toggle');
  const drop   = document.getElementById('user-dropdown');
  if (toggle && drop) {
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      drop.classList.toggle('open');
    });
    document.addEventListener('click', () => drop.classList.remove('open'));
  }
})();
</script>
</body>
</html>