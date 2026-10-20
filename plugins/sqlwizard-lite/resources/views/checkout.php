<?php
/**
 * SqlWizard Lite — Checkout (MBWay / Cartão / PayPal)
 * Recebe por query string: product, price, plan_label, plan_period
 */
declare(strict_types=1);

$product    = strtolower((string)($_GET['product']  ?? 'pro'));
$price      = (string)($_GET['price']               ?? '9');
$planLabel  = (string)($_GET['plan_label']          ?? ucfirst($product));
$planPeriod = (string)($_GET['plan_period']         ?? 'mês');

$userName  = $user['name'] ?? $user['nome'] ?? 'Visitante';
$userEmail = $user['email'] ?? '';

$activePage = 'checkout';
$version = function_exists('beaver_version') ? beaver_version() : '1.0';
?>
<!DOCTYPE html>
<html lang="<?= app()->make(\Beaver\I18n\Translator::class)->getLocale() ?: 'pt' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pagamento — <?= htmlspecialchars($planLabel) ?> · Beaver Framework</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/plugins/sqlwizard-lite/css/canvas.css">
<link rel="stylesheet" href="/plugins/sqlwizard-lite/css/checkout.css">
<link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body class="checkout-page">

<?php require __DIR__ . '/../../../../app/views/partials/header.php'; ?>

<main class="co-main">

    <a href="/painel/upgrade" class="co-back">← Voltar aos planos</a>

    <div class="co-grid">

        <section class="co-form-wrap">
            <div class="co-form-header">
                <h1>Finalizar pagamento</h1>
                <p>Escolhe o método de pagamento preferido.</p>
            </div>

            <div class="co-tabs" role="tablist">
                <button class="co-tab active" data-method="mbway" role="tab" aria-selected="true">
                    <span class="co-tab-icon">📱</span>
                    <span class="co-tab-label">MBWay</span>
                </button>
                <button class="co-tab" data-method="card" role="tab" aria-selected="false">
                    <span class="co-tab-icon">💳</span>
                    <span class="co-tab-label">Cartão</span>
                </button>
                <button class="co-tab" data-method="paypal" role="tab" aria-selected="false">
                    <span class="co-tab-icon">🅿️</span>
                    <span class="co-tab-label">PayPal</span>
                </button>
            </div>

            <div class="co-panel active" id="co-panel-mbway" role="tabpanel">
                <p class="co-info">
                    Receberás um pedido de pagamento na app MBWay.
                    Tens <strong>5 minutos</strong> para o aceitar.
                </p>
                <label class="co-label">Número de telemóvel</label>
                <div class="co-input-group">
                    <span class="co-input-prefix">🇵🇹 +351</span>
                    <input type="tel" class="co-input" id="co-mbway-phone"
                           placeholder="9XX XXX XXX" maxlength="9"
                           inputmode="numeric" pattern="[0-9]{9}">
                </div>
                <button class="co-btn co-btn-primary" data-method="mbway"
                        data-price="<?= htmlspecialchars($price) ?>"
                        data-product="<?= htmlspecialchars($product) ?>">
                    Pagar €<?= htmlspecialchars($price) ?> via MBWay →
                </button>
            </div>

            <div class="co-panel" id="co-panel-card" role="tabpanel">
                <p class="co-info">
                    Pagamento seguro via <strong>Stripe</strong>.
                    Os dados do cartão não passam pelo nosso servidor.
                </p>
                <label class="co-label">Número do cartão</label>
                <input type="text" class="co-input" id="co-card-number"
                       placeholder="1234 5678 9012 3456" maxlength="19"
                       inputmode="numeric" autocomplete="cc-number">

                <div class="co-row">
                    <div class="co-col">
                        <label class="co-label">Validade</label>
                        <input type="text" class="co-input" id="co-card-expiry"
                               placeholder="MM/AA" maxlength="5" autocomplete="cc-exp">
                    </div>
                    <div class="co-col">
                        <label class="co-label">CVC</label>
                        <input type="text" class="co-input" id="co-card-cvc"
                               placeholder="123" maxlength="4" autocomplete="cc-csc">
                    </div>
                </div>

                <label class="co-label">Nome no cartão</label>
                <input type="text" class="co-input" id="co-card-name"
                       placeholder="Como está no cartão" autocomplete="cc-name">

                <button class="co-btn co-btn-primary" data-method="card"
                        data-price="<?= htmlspecialchars($price) ?>"
                        data-product="<?= htmlspecialchars($product) ?>">
                    Pagar €<?= htmlspecialchars($price) ?> com cartão →
                </button>
            </div>

            <div class="co-panel" id="co-panel-paypal" role="tabpanel">
                <p class="co-info">
                    Serás redirecionado para o PayPal para completar o pagamento.
                </p>
                <div class="co-paypal-box">
                    <div class="co-paypal-icon">🅿️</div>
                    <div>
                        <strong>PayPal</strong>
                        <p>Paga com a tua conta PayPal ou cartão.</p>
                    </div>
                </div>
                <button class="co-btn co-btn-paypal" data-method="paypal"
                        data-price="<?= htmlspecialchars($price) ?>"
                        data-product="<?= htmlspecialchars($product) ?>">
                    Continuar com PayPal →
                </button>
            </div>

            <p class="co-secure">🔒 Pagamento seguro · SSL · Sem compromisso</p>
        </section>

        <aside class="co-summary">
            <h2>Resumo</h2>

            <div class="co-summary-product">
                <div class="co-summary-icon">⚡</div>
                <div>
                    <div class="co-summary-name">Plano <?= htmlspecialchars($planLabel) ?></div>
                    <div class="co-summary-sub">Beaver Framework · SqlWizard</div>
                </div>
            </div>

            <div class="co-summary-line">
                <span>Plano <?= htmlspecialchars($planLabel) ?> (<?= htmlspecialchars($planPeriod) ?>)</span>
                <span>€<?= htmlspecialchars($price) ?></span>
            </div>
            <div class="co-summary-line co-summary-total">
                <span>Total</span>
                <span>€<?= htmlspecialchars($price) ?></span>
            </div>

            <ul class="co-summary-features">
                <li>✅ Acesso imediato</li>
                <li>✅ Cancela quando quiseres</li>
                <li>✅ Suporte prioritário</li>
                <li>✅ Sem taxas escondidas</li>
            </ul>

            <div class="co-summary-user">
                <strong>Conta:</strong> <?= htmlspecialchars($userName) ?>
                <br><small><?= htmlspecialchars($userEmail) ?></small>
            </div>
        </aside>

    </div>
</main>

<?php require __DIR__ . '/../../../../app/views/partials/footer.php'; ?>

<script>
(function () {
    'use strict';

    var tabs = document.querySelectorAll('.co-tab');
    var panels = document.querySelectorAll('.co-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var method = tab.getAttribute('data-method');
            tabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            var panel = document.getElementById('co-panel-' + method);
            if (panel) panel.classList.add('active');
        });
    });

    var mbwayPhone = document.getElementById('co-mbway-phone');
    if (mbwayPhone) {
        mbwayPhone.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    }

    var cardNumber = document.getElementById('co-card-number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function () {
            var v = this.value.replace(/\D/g, '').slice(0, 16);
            this.value = v.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    var cardExpiry = document.getElementById('co-card-expiry');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function () {
            var v = this.value.replace(/\D/g, '').slice(0, 4);
            if (v.length >= 2) v = v.slice(0, 2) + '/' + v.slice(2);
            this.value = v;
        });
    }

    var cardCvc = document.getElementById('co-card-cvc');
    if (cardCvc) {
        cardCvc.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    document.querySelectorAll('.co-btn-primary, .co-btn-paypal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var method = btn.getAttribute('data-method');
            var price = btn.getAttribute('data-price');
            var product = btn.getAttribute('data-product');

            if (method === 'mbway') {
                var phone = mbwayPhone.value.replace(/\D/g, '');
                if (phone.length !== 9) {
                    alert('Introduz um número MBWay válido (9 dígitos).');
                    mbwayPhone.focus();
                    return;
                }
            } else if (method === 'card') {
                var num = (cardNumber.value || '').replace(/\D/g, '');
                if (num.length < 15) {
                    alert('Introduz um número de cartão válido.');
                    cardNumber.focus();
                    return;
                }
            }

            alert(
                '✅ Pedido de pagamento\n\n' +
                'Método: ' + method.toUpperCase() + '\n' +
                'Plano: ' + product + '\n' +
                'Valor: €' + price + '\n\n' +
                'O backend ainda não está configurado.\n' +
                'Contacta suporte@onidesk.pt para completar o upgrade.'
            );
        });
    });
})();
</script>

<script src="/assets/js/header.js"></script>
</body>
</html>
