<?php
use Beaver\Plugins\Translations\TranslationsPlugin;

$current = TranslationsPlugin::currentLocale();
$langs   = TranslationsPlugin::labels();
$active  = $langs[$current] ?? ['label' => $current, 'flag' => '🌐'];

// Página atual (para voltar após trocar)
$ref  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($ref, PHP_URL_PATH) ?: '/';
$qs   = parse_url($ref, PHP_URL_QUERY);
parse_str($qs ?? '', $q);
unset($q['lang']);
?>
<div class="beaver-lang-switcher" data-current="<?= htmlspecialchars($current) ?>">
    <button type="button" class="beaver-lang-btn" aria-haspopup="true" aria-expanded="false">
        <span class="beaver-lang-flag"><?= $active['flag'] ?></span>
        <span class="beaver-lang-label"><?= htmlspecialchars($active['label']) ?></span>
        <span class="beaver-lang-caret">▾</span>
    </button>
    <ul class="beaver-lang-menu" role="menu">
        <?php foreach ($langs as $code => $info): ?>
            <?php
                $isActive = $code === $current;
                $targetQs = $q;
                $targetQs['lang'] = $code;
                $href = '/?' . http_build_query($targetQs);
            ?>
            <li role="none">
                <a role="menuitem" href="<?= htmlspecialchars($href) ?>"
                   class="<?= $isActive ? 'is-active' : '' ?>"
                   data-locale="<?= $code ?>">
                    <span class="beaver-lang-flag"><?= $info['flag'] ?></span>
                    <span class="beaver-lang-label"><?= htmlspecialchars($info['label']) ?></span>
                    <?php if ($isActive): ?><span class="beaver-lang-check">✓</span><?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<style>
.beaver-lang-switcher { position: relative; display: inline-block; font-family: system-ui, sans-serif; }
.beaver-lang-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; background:#1e1e1e; color:#fff; border:1px solid #333; cursor:pointer; font-size:14px; }
.beaver-lang-btn:hover { background:#2a2a2a; }
.beaver-lang-menu { display:none; position:absolute; top:100%; right:0; margin:4px 0 0; padding:4px; list-style:none; min-width:180px; background:#1e1e1e; border:1px solid #333; border-radius:6px; box-shadow:0 8px 24px rgba(0,0,0,0.35); z-index:1000; }
.beaver-lang-switcher.open .beaver-lang-menu { display:block; }
.beaver-lang-menu a { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:4px; color:#fff; text-decoration:none; font-size:14px; }
.beaver-lang-menu a:hover { background:#2a2a2a; }
.beaver-lang-menu a.is-active { background:#2f6f3f; }
.beaver-lang-check { margin-left:auto; }
</style>
<script>
(function () {
    document.querySelectorAll('.beaver-lang-switcher').forEach(function (sw) {
        var btn = sw.querySelector('.beaver-lang-btn');
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            sw.classList.toggle('open');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.beaver-lang-switcher.open').forEach(function (sw) {
            sw.classList.remove('open');
        });
    });
})();
</script>
