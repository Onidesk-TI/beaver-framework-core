<?php
/**
 * SqlWizard Lite — Página de planos (upgrade)
 * Usa o header + footer partilhados do Beaver.
 */
declare(strict_types=1);

$currentPlan = strtolower((string)($user['plan'] ?? 'free'));
$userName = $user['name'] ?? $user['nome'] ?? 'Visitante';

// Para o header partilhado
$activePage = 'upgrade';
$version = function_exists('beaver_version') ? beaver_version() : '1.0';
?>
<!DOCTYPE html>
<html lang="<?= app()->make(\Beaver\I18n\Translator::class)->getLocale() ?: 'pt' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Planos — SqlWizard Lite · Beaver Framework</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/plugins/sqlwizard-lite/css/canvas.css">
<link rel="stylesheet" href="/plugins/sqlwizard-lite/css/upgrade.css">
<link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body class="upgrade-page">

<?php require __DIR__ . '/../../../../app/views/partials/header.php'; ?>

<?php
$__from = $_GET['from'] ?? ($_SESSION['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
if (str_contains($__from, '/painel/upgrade')) { $__from = '/'; }
$__back_url = $__from !== '' && str_starts_with($__from, '/') ? $__from : '/';
?>
<a href="<?= htmlspecialchars($__back_url, ENT_QUOTES, 'UTF-8') ?>" class="up-back-floating">← Voltar</a>

<main class="up-main">

    <div class="up-hero">
        <h1>Escolhe o teu plano</h1>
        <p>Faz upgrade para desbloquear todas as funcionalidades do SqlWizard.</p>
        <?php if ($currentPlan === 'free'): ?>
        <div class="up-trial-banner">
            ⚡ Estás no plano <strong>Free</strong>. Faz upgrade para remover limites.
        </div>
        <?php elseif ($currentPlan === 'pro'): ?>
        <div class="up-trial-banner up-trial-banner-pro">
            ✅ Estás no plano <strong>Pro</strong>. Obrigado pelo apoio!
        </div>
        <?php endif; ?>
    </div>

    <div class="up-plans">

        <!-- ═══ FREE ═══ -->
        <div class="up-plan <?= $currentPlan === 'free' ? 'up-plan-current' : '' ?>">
            <div class="up-plan-header">
                <span class="up-plan-icon">🆓</span>
                <h2>Free</h2>
                <div class="up-plan-price">
                    <span class="up-price">€0</span>
                    <span class="up-period">/ sempre</span>
                </div>
            </div>
            <ul class="up-features">
                <li class="on">Canvas drag&drop</li>
                <li class="on">6 templates prontos</li>
                <li class="on">Export PNG, SQL, Mermaid, JSON</li>
                <li class="on">Import SQL</li>
                <li class="on">Undo/Redo + Zoom</li>
                <li class="on">Preview SQL ao vivo</li>
                <li class="on">Validação de FK</li>
                <li class="on">Dark mode</li>
                <li class="off">Templates custom</li>
                <li class="off">Histórico visual</li>
                <li class="off">Export SVG</li>
            </ul>
            <div class="up-plan-footer">
                <?php if ($currentPlan === 'free'): ?>
                <button class="up-btn up-btn-current" disabled>Plano atual</button>
                <?php else: ?>
                <button class="up-btn up-btn-ghost" disabled>Incluído</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ PRO ═══ -->
        <div class="up-plan up-plan-featured <?= $currentPlan === 'pro' ? 'up-plan-current' : '' ?>">
            <div class="up-ribbon">⭐ POPULAR</div>
            <div class="up-plan-header">
                <span class="up-plan-icon">⚡</span>
                <h2>Pro</h2>
                <div class="up-plan-price">
                    <span class="up-price">€9</span>
                    <span class="up-period">/ mês</span>
                </div>
            </div>
            <ul class="up-features">
                <li class="on">Tudo do Free</li>
                <li class="on"><strong>Templates custom ilimitados</strong></li>
                <li class="on"><strong>Histórico visual (sidebar)</strong></li>
                <li class="on"><strong>Sem limite de tabelas</strong></li>
                <li class="on">Export SVG (vetorial)</li>
                <li class="on">Comentários nas tabelas</li>
                <li class="on">Multi-seleção</li>
                <li class="on">Suporte prioritário</li>
            </ul>
            <div class="up-plan-footer">
                <?php if ($currentPlan === 'pro'): ?>
                <button class="up-btn up-btn-current" disabled>Plano atual</button>
                <?php else: ?>
                <a class="up-btn up-btn-primary" href="/checkout/pro/9">Fazer upgrade →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ BUSINESS ═══ -->
        <div class="up-plan <?= $currentPlan === 'business' ? 'up-plan-current' : '' ?>">
            <div class="up-plan-header">
                <span class="up-plan-icon">💼</span>
                <h2>Business</h2>
                <div class="up-plan-price">
                    <span class="up-price">€29</span>
                    <span class="up-period">/ mês</span>
                </div>
            </div>
            <ul class="up-features">
                <li class="on">Tudo do Pro</li>
                <li class="on">Até 10 utilizadores</li>
                <li class="on">API de export</li>
                <li class="on">Templates partilhados</li>
                <li class="on">Suporte 24/7</li>
                <li class="on">Onboarding dedicado</li>
            </ul>
            <div class="up-plan-footer">
                <?php if ($currentPlan === 'business'): ?>
                <button class="up-btn up-btn-current" disabled>Plano atual</button>
                <?php else: ?>
                <a class="up-btn up-btn-primary" href="/checkout/business/29">Fazer upgrade →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══ FAQ ═══ -->
    <div class="up-faq">
        <h3>Perguntas frequentes</h3>
        <details>
            <summary>Posso cancelar a qualquer momento?</summary>
            <p>Sim. Podes cancelar em qualquer altura no teu perfil. O acesso Pro fica ativo até ao fim do período pago.</p>
        </details>
        <details>
            <summary>O que acontece se o trial expirar?</summary>
            <p>A conta volta ao plano Free. Os schemas continuam guardados, mas perdes as funcionalidades Pro.</p>
        </details>
        <details>
            <summary>Como funciona o pagamento?</summary>
            <p>Aceitamos cartão de crédito, MB Way e transferência bancária. Todos os pagamentos são processados em segurança.</p>
        </details>
        <details>
            <summary>Já sou Pro. Posso mudar para Business?</summary>
            <p>Sim. Contacta-nos e fazemos o upgrade com ajuste proporcional do valor pago.</p>
        </details>
        <details>
            <summary>Precisas de ajuda?</summary>
            <p>Contacta-nos: <a href="mailto:suporte@onidesk.pt">suporte@onidesk.pt</a></p>
        </details>
    </div>

</main>

<?php require __DIR__ . '/../../../../app/views/partials/footer.php'; ?>

<script>
document.querySelectorAll('.up-btn-primary[data-plan]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var plan = btn.getAttribute('data-plan');
        var price = btn.getAttribute('data-price');
        if (plan === 'business') {
            window.location.href = 'mailto:suporte@onidesk.pt?subject=Upgrade%20para%20Business&body=Quero%20fazer%20upgrade%20para%20o%20plano%20Business%20(€' + price + '%2Fmês).';
            return;
        }
        // TODO: integração com Stripe/MB Way
        alert('Checkout para o plano "' + plan + '" (€' + price + '/mês) ainda não está configurado.\n\nContacta suporte@onidesk.pt para fazer o upgrade.');
    });
});
</script>

<script src="/assets/js/header.js"></script>
</body>
</html>
