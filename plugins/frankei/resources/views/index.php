<!DOCTYPE html>
<html lang="pt" data-theme="amber">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Frankei — Dashboard</title>
<link rel="stylesheet" href="/plugins/frankei/css/panel.css?v=<?= time() ?>">
<link rel="stylesheet" href="/plugins/frankei/css/setup.css?v=<?= time() ?>">
</head>
<body class="fk-dash-body">

<header class="fk-topbar">
    <div class="fk-topbar-left">
        <a href="/frankei" class="fk-topbar-brand">
            <span class="fk-topbar-logo">🔍</span>
            <b>Frankei</b>
        </a>
        <code class="fk-topbar-path" title="Aplicação em análise">
            <?= htmlspecialchars($path ?? '—') ?>
        </code>
    </div>
    <div class="fk-topbar-right">
        <span class="fk-topbar-user">
            Olá, <b><?= htmlspecialchars($user['nome'] ?? 'dev') ?></b>
        </span>
        <a href="/frankei/logout" class="fk-topbar-logout">Sair</a>
    </div>
</header>

<main class="fk-dash-main">
    <div id="frankei-host"></div>
</main>

<script src="/plugins/frankei/js/frankei-api.js?v=<?= time() ?>"></script>
<script src="/plugins/frankei/js/frankei-panel.js?v=<?= time() ?>"></script>
<script src="/plugins/frankei/js/frankei-render.js?v=<?= time() ?>"></script>
<script src="/plugins/frankei/js/frankei-app.js?v=<?= time() ?>"></script>

</body>
</html>