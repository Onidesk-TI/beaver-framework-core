<?php
/**
 * SqlWizard Lite — View principal do canvas.
 */
declare(strict_types=1);

$assetBase = '/plugins/sqlwizard-lite';
?>
<?php
// Fallback user (caso a rota não passe $user)
if (!isset($user) || !is_array($user)) {
    $user = [];
    if (class_exists(\Beaver\Auth\Auth::class)) {
        try {
            $u = \Beaver\Auth\Auth::user();
            if ($u) {
                if (is_object($u) && method_exists($u, 'toArray')) $user = $u->toArray();
                elseif (is_object($u)) $user = json_decode(json_encode($u), true) ?: [];
                elseif (is_array($u)) $user = $u;
            }
        } catch (\Throwable $e) { /* silencioso */ }
    }
}
// Normalizar nome
if (empty($user['name']) && !empty($user['nome'])) $user['name'] = $user['nome'];

$plan = strtolower((string)($user['plan'] ?? 'trial'));
$planExpiresAt = $user['plan_expires_at'] ?? ($user['trial_expires_at'] ?? null);
if (is_numeric($planExpiresAt)) {
    $planExpiresAt = date('c', (int) $planExpiresAt);
}

// CSRF (para o POST de logout)
$csrfToken = $csrfToken ?? '';
if ($csrfToken === '') {
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $csrfToken = (string) ($_SESSION['csrf_token'] ?? $_SESSION['_csrf'] ?? $_SESSION['csrf'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SqlWizard Lite — Canvas</title>
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $assetBase ?>/css/canvas.css">
</head>
<body>

<div class="lite-banner">
  <strong>SqlWizard Lite</strong> · canvas + 1 draft local
  <a href="/painel/upgrade">Fazer upgrade →</a>
</div>

<header class="toolbar">
  <a class="toolbar-left toolbar-home" href="/" title="Voltar ao Beaver Framework">
    <span class="logo">🦫</span>
    <span class="brand">Sql<span class="accent">Wizard</span> <em>Lite</em></span>
  </a>
  <div class="toolbar-center">
    <button class="tb-btn" id="btn-undo" type="button" title="Desfazer (Ctrl+Z)">↶</button>
        <button class="tb-btn" id="btn-redo" type="button" title="Refazer (Ctrl+Shift+Z)">↷</button>
        <button class="tb-btn" id="btn-duplicate" type="button" title="Duplicar tabela (Ctrl+D)">⧉</button>
        <button id="btn-add-table" class="tb-btn tb-primary">+ Nova tabela</button>
    <button id="btn-export-sql" class="tb-btn">Exportar SQL</button>
    <button class="tb-btn" id="btn-import-sql" type="button" title="Importar SQL">📥 Import SQL</button>
        <button class="tb-btn" id="btn-save-template" type="button" title="Guardar schema como template">💾 Guardar template</button>
        <button class="tb-btn" id="btn-templates" type="button" title="Começar de um template">📋 Templates</button>
        <button class="tb-btn" id="btn-preview-sql" type="button" title="Preview SQL ao vivo">&#128065; Preview SQL</button>
        <button class="tb-btn" id="btn-export-png" type="button" title="Exportar canvas como PNG">🖼 PNG</button>
        <button class="tb-btn" id="btn-export-modal" type="button" title="Exportar SQL/JSON/Mermaid">&#11015; Exportar</button>
        <button class="tb-btn" id="btn-auto-layout" type="button" title="Auto-layout">&#9889; Auto-layout</button>
        <button id="btn-export-json" class="tb-btn">Guardar JSON</button>
    <label class="tb-btn tb-file">
      Importar JSON
      <input type="file" id="file-import" accept=".json" hidden>
    </label>
  </div>
  <div class="toolbar-right">
    <span class="hint" id="status">pronto</span>
    <button id="btn-clear" class="tb-btn tb-danger">Limpar</button>
  </div>
</header>

<main class="canvas-wrap">
  <svg id="relations" class="relations-layer"></svg>
  <div id="canvas" class="canvas"></div>
  <div class="canvas-empty" id="empty-state">
    <div class="empty-icon">🎨</div>
    <h2>Começa o teu schema</h2>
    <p>Clica em <strong>+ Nova tabela</strong> para adicionar a primeira tabela.</p>
  </div>
</main>

<aside class="inspector" id="inspector" hidden>
  <header class="inspector-head">
    <h3 id="insp-title">Tabela</h3>
    <button id="insp-close" class="insp-close">×</button>
  </header>
  <div class="inspector-body">
    <label class="field">
      <span>Nome da tabela</span>
      <input type="text" id="insp-table-name" class="input">
    </label>
    <div class="columns-head">
      <span>Colunas</span>
      <button id="btn-add-column" class="btn-mini">+ coluna</button>
    </div>
    <ul class="columns-list" id="columns-list"></ul>
    <button id="btn-delete-table" class="btn btn-danger">Apagar tabela</button>
  </div>
</aside>

<div class="toast" id="toast" hidden></div>

<script src="<?= $assetBase ?>/js/canvas.js?v=<?= filemtime(__DIR__ . "/../ui/js/canvas.js") ?>" defer></script>
<!-- ─── Modal: editar relação ─── -->
<div id="relation-modal" class="relation-modal-backdrop" style="display:none">
    <div class="relation-modal">
        <h3>
            <span id="rel-icon">🔗</span>
            <span id="rel-title">Relação</span>
        </h3>
        <p class="subtitle" id="rel-subtitle">Tabela A → Tabela B</p>

        <!-- Cardinalidade -->
        <div class="field">
            <label>Cardinalidade</label>
            <div class="cardinality-grid">
                <button class="card-btn" data-card="1:1">1:1</button>
                <button class="card-btn" data-card="1:n">1:n</button>
                <button class="card-btn" data-card="n:1">n:1</button>
                <button class="card-btn" data-card="n:n">n:n</button>
            </div>
        </div>

        <!-- Info -->
        <div class="relation-info" id="rel-info">
            <div><strong>FROM:</strong> <span id="rel-from">—</span></div>
        <div><strong>TO:</strong> <span id="rel-to">—</span></div>
        </div>

        <!-- Ações -->
        <div class="relation-actions">
            <button class="btn btn-danger" id="rel-delete">🗑️ Eliminar</button>
            <button class="btn" id="rel-cancel">Cancelar</button>
            <button class="btn btn-primary" id="rel-save">Guardar</button>
        </div>
    </div>
</div>

<script src="/plugins/sqlwizard-lite/js/validation.js"></script>
<script src="/plugins/sqlwizard-lite/js/history.js"></script>
<script src="/plugins/sqlwizard-lite/js/keyboard.js"></script>
<script src="/plugins/sqlwizard-lite/js/import-sql.js"></script>
<script>
window.__SQLWIZARD_USER__ = {
    name: <?= json_encode((string)($user['name'] ?? $user['nome'] ?? 'Visitante')) ?>,
    email: <?= json_encode((string)($user['email'] ?? '')) ?>,
    plan: <?= json_encode($plan) ?>,
    plan_expires_at: <?= json_encode($planExpiresAt) ?>,
    is_trial: <?= json_encode($plan === 'trial') ?>
};
window.__SQLWIZARD_PROFILE_URL__ = '/painel/perfil';
window.__SQLWIZARD_LOGOUT_URL__  = '/logout';
window.__SQLWIZARD_UPGRADE_URL__ = '/painel/upgrade';
</script>
<script src="/plugins/sqlwizard-lite/js/user-menu.js?v=<?= time() ?>"></script>
<script src="/plugins/sqlwizard-lite/js/dark-mode.js?v=<?= time() ?>"></script>
<script src="/plugins/sqlwizard-lite/js/templates-custom.js?v=<?= time() ?>"></script>
<script src="/plugins/sqlwizard-lite/js/templates-modal.js"></script>
<script src="/plugins/sqlwizard-lite/js/sql-preview.js"></script>
<script src="/plugins/sqlwizard-lite/js/export-png.js?v=<?= time() ?>"></script>

<script src="/plugins/sqlwizard-lite/js/export-modal-lite.js"></script>
<script src="/plugins/sqlwizard-lite/js/auto-layout.js"></script>
<script>
(function () {
    var btnPreview = document.getElementById("btn-preview-sql");
    var btnExport  = document.getElementById("btn-export-modal");
    var btnExportPNG = document.getElementById("btn-export-png");
    var btnLayout  = document.getElementById("btn-auto-layout");
    var btnTemplates = document.getElementById("btn-templates");
    var btnSaveTemplate = document.getElementById("btn-save-template");
    var btnImportSQL = document.getElementById("btn-import-sql");
    if (btnPreview) btnPreview.addEventListener("click", function () { window.SqlWizardPreview.togglePanel(); });
    if (btnExportPNG) btnExportPNG.addEventListener("click", function () { window.SqlWizardExportPNG.exportPNG(); });
    if (btnExport)  btnExport.addEventListener("click",  function () { window.SqlWizardExportModal.open("sql"); });
    if (btnImportSQL) btnImportSQL.addEventListener("click", function () { window.SqlWizardImportSQL.open(); });
    if (btnSaveTemplate) btnSaveTemplate.addEventListener("click", function () { window.SqlWizardCustomTemplates.openSaveModal(); });
    if (btnTemplates) btnTemplates.addEventListener("click", function () { window.SqlWizardTemplates.open(); });
    if (btnLayout)  btnLayout.addEventListener("click",  function () { window.SqlWizardAutoLayout.layout(); });

    var btnUndo       = document.getElementById("btn-undo");
    var btnRedo       = document.getElementById("btn-redo");
    var btnDuplicate  = document.getElementById("btn-duplicate");
    var btnZoomIn     = document.getElementById("btn-zoom-in");
    var btnZoomOut    = document.getElementById("btn-zoom-out");
    var btnZoomFit    = document.getElementById("btn-zoom-fit");
    if (btnUndo)      btnUndo.addEventListener("click", function () { window.SqlWizardHistory.undo(); });
    if (btnRedo)      btnRedo.addEventListener("click", function () { window.SqlWizardHistory.redo(); });
    if (btnDuplicate) btnDuplicate.addEventListener("click", function () { window.SqlWizardKeyboard.duplicateSelected(); });
    if (btnZoomIn)    btnZoomIn.addEventListener("click", function () { window.SqlWizardKeyboard.zoomIn(); });
    if (btnZoomOut)   btnZoomOut.addEventListener("click", function () { window.SqlWizardKeyboard.zoomOut(); });
    if (btnZoomFit)   btnZoomFit.addEventListener("click", function () { window.SqlWizardKeyboard.zoomFit(); });
})();
</script>
<div id="zoom-controls" style="position:fixed;bottom:16px;right:16px;display:flex;gap:6px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:10px;padding:6px;box-shadow:0 8px 24px -12px rgba(0,0,0,.2);z-index:15;">
  <button class="tb-btn" id="btn-zoom-out" type="button" title="Zoom out (Ctrl+-)" style="padding:6px 10px;">−</button>
  <span id="zoom-display" style="font-family:JetBrains Mono,monospace;font-size:.72rem;font-weight:700;color:var(--text);min-width:44px;text-align:center;">100%</span>
  <button class="tb-btn" id="btn-zoom-in" type="button" title="Zoom in (Ctrl++)" style="padding:6px 10px;">+</button>
  <button class="tb-btn" id="btn-zoom-fit" type="button" title="Fit (Ctrl+Shift+F)" style="padding:6px 10px;">⊡</button>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (window.SqlWizardValidation) window.SqlWizardValidation.start();
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (window.SqlWizardDarkMode) window.SqlWizardDarkMode.boot();
    if (window.SqlWizardUserMenu) window.SqlWizardUserMenu.boot();
});
</script>
</body>
</html>
