<?php
/**
 * Beaver — página de erro.
 *
 * @var int    $code
 * @var string $title
 * @var string $message     (pode conter HTML mínimo, como <code>)
 * @var string $image       (caminho da imagem, sem extensão)
 * @var bool   $debug
 * @var array  $debugData
 */
$trace = $debugData['trace']     ?? null;
$file  = $debugData['file']      ?? null;
$line  = $debugData['line']      ?? null;
$class = $debugData['class']     ?? null;
$exc   = $debugData['exception'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $code ?> · <?= htmlspecialchars($title) ?> · Beaver</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family:Inter,sans-serif;
    background:#fdf8ef;
    color:#2E1A10;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem;
  }
  .error{max-width:900px;width:100%;text-align:center}
  .error img{
    max-width:512px;
    width:100%;
    height:auto;
    margin-bottom:1.5rem;
  }
  .error-code{
    font-family:'JetBrains Mono',monospace;
    font-size:4.5rem;
    font-weight:800;
    color:#c25a1f;
    line-height:1;
    margin:0;
    letter-spacing:-.03em;
  }
  .error h1{
    margin:.4rem 0 1rem;
    font-size:1.5rem;
    font-weight:700;
    color:#2E1A10;
  }
  .error p{
    font-size:1rem;
    color:#6b5544;
    line-height:1.6;
    margin:0 0 2rem;
  }
  .error p code{
    background:#f5ead0;
    padding:.15rem .45rem;
    border-radius:4px;
    font-family:'JetBrains Mono',monospace;
    font-size:.9em;
    color:#c25a1f;
  }
  .actions{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}
  .btn{
    padding:.75rem 1.5rem;
    border-radius:8px;
    font-weight:600;
    text-decoration:none;
    font-size:.9rem;
    border:0;
    cursor:pointer;
    font-family:inherit;
    transition:opacity .15s;
  }
  .btn:hover{opacity:.9}
  .btn-primary{background:linear-gradient(140deg,#FF9A3C,#FF6B35);color:#fff}
  .btn-ghost{background:#fff;border:1px solid #eadfc7;color:#2E1A10}
  .debug{
    margin-top:2.5rem;
    text-align:left;
    background:#2E1A10;
    color:#fdf8ef;
    border-radius:10px;
    padding:1.5rem;
    font-family:'JetBrains Mono',monospace;
    font-size:.78rem;
    overflow:auto;
    max-height:400px;
  }
  .debug summary{
    cursor:pointer;
    color:#FF9A3C;
    margin-bottom:1rem;
    font-weight:600;
    font-size:.85rem;
  }
  .debug summary::marker{color:#FF9A3C}
  .debug p{margin:.5rem 0;color:#fdf8ef}
  .debug pre{margin:.5rem 0 0;white-space:pre-wrap;word-break:break-word;color:#d9cbb2}
  .brand{
    margin-top:3rem;
    font-size:.85rem;
    color:#a08f7a;
    text-decoration:none;
  }
  .brand strong{color:#c25a1f}
</style>
</head>
<body>
  <div class="error">
    <img
      src="<?= htmlspecialchars($image) ?>"
      alt="Beaver <?= $code ?>"
      onerror="this.style.display='none'"
    >

    <p class="error-code"><?= $code ?></p>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= $message /* já vem seguro ou com <code> controlado */ ?></p>

    <div class="actions">
      <a href="/" class="btn btn-primary">Voltar ao início</a>
      <button type="button" onclick="history.back()" class="btn btn-ghost">Voltar atrás</button>
    </div>

    <?php if ($debug && $trace !== null): ?>
      <details class="debug" open>
        <summary><?= htmlspecialchars((string) $class) ?>: <?= htmlspecialchars((string) ($exc?->getMessage() ?? '')) ?></summary>
        <p><strong><?= htmlspecialchars((string) $file) ?>:<?= (int) $line ?></strong></p>
        <pre><?= htmlspecialchars((string) $trace) ?></pre>
      </details>
    <?php endif; ?>

    <a href="/" class="brand">Beaver<strong>.</strong> Framework</a>
  </div>
</body>
</html>
