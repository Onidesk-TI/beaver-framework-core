<?php
/** @var string $plan */
/** @var array  $plans */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planos · Frankei Trial</title>
    <link rel="stylesheet" href="/plugins/frankei/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/plugins/frankei-trial/css/trial.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/plugins/frankei-trial/css/prices.css?v=<?= time() ?>">
    <script src="/plugins/frankei/js/frankei-i18n.js?v=<?= time() ?>" defer></script>
</head>
<body class="fk-trial-body fk-prices-page">

    <!-- ═══════════════════════════════════════════════════════════
         TOPBAR (logo + volta + idioma)
         ═══════════════════════════════════════════════════════════ -->
    <header class="fk-trial-topbar fk-prices-topbar">
        <div class="fk-trial-topbar-left">
            <a href="/frankei-trial" class="fk-trial-brand">
                <svg class="fk-trial-brand-logo-svg" viewBox="0 0 64 64" aria-hidden="true">
                    <defs>
                        <linearGradient id="bvgPrices" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FFD79A"/>
                            <stop offset="48%" stop-color="#FF9A3C"/>
                            <stop offset="100%" stop-color="#FF6B35"/>
                        </linearGradient>
                    </defs>
                    <circle cx="13" cy="17.5" r="7.5" fill="url(#bvgPrices)" opacity=".72"/>
                    <circle cx="51" cy="17.5" r="7.5" fill="url(#bvgPrices)" opacity=".72"/>
                    <ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvgPrices)"/>
                    <ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".2"/>
                    <ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208"/>
                    <circle cx="22.5" cy="26" r="3.4" fill="#1A1208"/>
                    <circle cx="41.5" cy="26" r="3.4" fill="#1A1208"/>
                    <rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>
                    <rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/>
                </svg>
                <span><b>Frankei</b> Trial</span>
            </a>
            <a href="/frankei-trial" class="fk-prices-back" aria-label="Voltar">← <span data-i18n="Voltar">Voltar</span></a>
        </div>

        <div class="fk-trial-topbar-right">
            <!-- ── Chip de idioma (mesmo do trial.php) ── -->
            <div class="fk-lang-chip-wrap" id="fkLangChipWrap">
                <button type="button" class="fk-lang-chip" id="fkLangChip" title="Idioma / Language" aria-label="Mudar idioma">
                    <span class="fk-lang-chip-flag" id="fkLangChipFlag">🇵🇹</span>
                    <span class="fk-lang-chip-code" id="fkLangChipCode">PT</span>
                    <span class="fk-lang-chip-caret">▾</span>
                </button>
                <div class="fk-lang-dropdown" id="fkLangDropdown" hidden>
                    <button type="button" class="fk-lang-option" data-lang="pt">
                        <span class="fk-lang-opt-flag">🇵🇹</span>
                        <span class="fk-lang-opt-name">Português</span>
                    </button>
                    <button type="button" class="fk-lang-option" data-lang="en">
                        <span class="fk-lang-opt-flag">🇬🇧</span>
                        <span class="fk-lang-opt-name">English</span>
                    </button>
                    <button type="button" class="fk-lang-option" data-lang="fr">
                        <span class="fk-lang-opt-flag">🇫🇷</span>
                        <span class="fk-lang-opt-name">Français</span>
                    </button>
                    <button type="button" class="fk-lang-option" data-lang="es">
                        <span class="fk-lang-opt-flag">🇪🇸</span>
                        <span class="fk-lang-opt-name">Español</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════
         CONTEÚDO
         ═══════════════════════════════════════════════════════════ -->
    <main class="fk-prices-main">

        <header class="fk-prices-head">
            <h1 data-i18n="Escolhe o teu plano">Escolhe o teu plano</h1>
            <p data-i18n="prices.subtitle">Começa grátis. Faz upgrade quando a equipa crescer. Sem surpresas, cancelas quando quiseres.</p>
        </header>

        <section class="fk-prices-grid">

            <!-- ── FREE ── -->
            <article class="fk-price-card" data-plano="free">
                <header class="fk-price-head">
                    <p class="fk-price-name" data-i18n="Free">Free</p>
                    <p class="fk-price-value">
                        <span class="fk-price-number">0€</span>
                        <span class="fk-price-period" data-i18n="sempre">/ sempre</span>
                    </p>
                    <p class="fk-price-sub" data-i18n="Free subtitle">Para experimentar e projectos pessoais</p>
                </header>
                <ul class="fk-price-feats">
                    <li>100 <span data-i18n="testes por mês">testes por mês</span></li>
                    <li>100 MB <span data-i18n="de espaço">de espaço</span></li>
                    <li>1 <span data-i18n="utilizador">utilizador</span></li>
                    <li data-i18n="Updates de segurança">Updates de segurança</li>
                    <li data-i18n="Suporte comunitário">Suporte comunitário</li>
                    <li class="is-muted" data-i18n="Sem relatórios avançados">Sem relatórios avançados</li>
                    <li class="is-muted" data-i18n="Com marca Frankei Trial">Com marca Frankei Trial</li>
                </ul>
                <button type="button" class="fk-price-cta" data-plano="free" data-i18n="Começar grátis">Começar grátis</button>
            </article>

            <!-- ── PRO ── -->
            <article class="fk-price-card is-featured" data-plano="pro">
                <span class="fk-price-badge" data-i18n="Recomendado">Recomendado</span>
                <header class="fk-price-head">
                    <p class="fk-price-name">Pro</p>
                    <p class="fk-price-value">
                        <span class="fk-price-number">49€</span>
                        <span class="fk-price-period" data-i18n="por ano">/ ano</span>
                    </p>
                    <p class="fk-price-sub" data-i18n="Pro subtitle">Para equipas pequenas a médias</p>
                </header>
                <ul class="fk-price-feats">
                    <li data-i18n="Testes ilimitados">Testes ilimitados</li>
                    <li>5 GB <span data-i18n="de espaço">de espaço</span></li>
                    <li>5 <span data-i18n="utilizadores">utilizadores</span></li>
                    <li data-i18n="Updates incluídos">Updates incluídos</li>
                    <li data-i18n="Suporte por email">Suporte por email</li>
                    <li data-i18n="Relatórios avançados e exportação">Relatórios avançados + exportação</li>
                    <li data-i18n="Sem marca Frankei Trial">Sem marca Frankei Trial</li>
                </ul>
                <button type="button" class="fk-price-cta" data-plano="pro" data-i18n="Comprar agora">Comprar agora →</button>
            </article>

            <!-- ── PREMIUM ── -->
            <article class="fk-price-card" data-plano="premium">
                <header class="fk-price-head">
                    <p class="fk-price-name">Premium</p>
                    <p class="fk-price-value">
                        <span class="fk-price-number">149,99€</span>
                        <span class="fk-price-period" data-i18n="por ano">/ ano</span>
                    </p>
                    <p class="fk-price-sub" data-i18n="Premium subtitle">Para agências e equipas em escala</p>
                </header>
                <ul class="fk-price-feats">
                    <li class="is-head" data-i18n="Tudo do Pro, mais:">Tudo do Pro, mais:</li>
                    <li>50 GB <span data-i18n="de espaço dedicado">de espaço dedicado</span></li>
                    <li data-i18n="Utilizadores ilimitados">Utilizadores ilimitados</li>
                    <li data-i18n="Updates gratuitos para sempre">Updates gratuitos para sempre</li>
                    <li data-i18n="Suporte prioritário 24/7">Suporte prioritário 24/7</li>
                    <li data-i18n="Early access a novas features">Early access a novas features</li>
                    <li data-i18n="Onboarding dedicado">Onboarding dedicado (1 sessão)</li>
                    <li data-i18n="Backups automáticos diários">Backups automáticos diários</li>
                </ul>
                <button type="button" class="fk-price-cta" data-plano="premium" data-i18n="Comprar agora">Comprar agora →</button>
            </article>

        </section>

        <footer class="fk-prices-foot">
            <a href="/frankei-trial" data-i18n="Voltar">← Voltar</a>
            <span>·</span>
            <span data-i18n="prices.footnote">Todos os preços em EUR sem IVA</span>
        </footer>

    </main>

    <script src="/plugins/frankei-trial/js/prices.js?v=<?= time() ?>"></script>
</body>
</html>
