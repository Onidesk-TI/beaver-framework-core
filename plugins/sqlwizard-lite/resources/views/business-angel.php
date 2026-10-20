<?php
/**
 * Beaver Framework — Business Angel Program
 */
declare(strict_types=1);

$activePage = 'business-angel';
$version = function_exists('beaver_version') ? beaver_version() : '1.0';
$userName = $user['name'] ?? $user['nome'] ?? 'Visitante';
$userEmail = $user['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= app()->make(\Beaver\I18n\Translator::class)->getLocale() ?: 'pt' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Business Angel — Beaver Framework</title>
<meta name="description" content="Programa de partilha de receita do Beaver Framework. Transparência total, 2% revenue share, parceria real.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/plugins/sqlwizard-lite/css/business-angel.css">
<link rel="stylesheet" href="/assets/css/footer.css">
</head>
<body class="ba-page">

<?php require __DIR__ . '/../../../../app/views/partials/header.php'; ?>

<main class="ba-main">

    <section class="ba-hero">
        <div class="ba-hero-badge">💎 Programa de Partilha de Receita</div>
        <h1>Transparência em Primeiro Lugar.<br><span class="grad">Retorno Claro. Parceria Real.</span></h1>
        <p class="ba-lead">
            Na Beaver Framework, acreditamos que uma parceria sólida começa com transparência total.
            Os nossos Business Angels não investem às cegas — têm acesso direto aos projetos em vigor,
            sabem exatamente onde está o seu dinheiro e acompanham o crescimento do ecossistema em tempo real.
        </p>
        <div class="ba-hero-ctas">
            <a href="#contacto" class="ba-btn ba-btn-primary">Quero ser Business Angel →</a>
            <a href="#modelo" class="ba-btn ba-btn-secondary">Ver o modelo</a>
        </div>
    </section>

    <section class="ba-section" id="transparencia">
        <div class="ba-section-head">
            <span class="ba-kicker">🔍 Transparência Total</span>
            <h2>Acesso aos projetos em vigor</h2>
            <p>Cada Business Angel tem acesso a uma área dedicada onde pode consultar todos os projetos ativos — estado, progresso, receita gerada e retorno acumulado.</p>
        </div>
        <div class="ba-cards ba-cards-3">
            <div class="ba-card">
                <div class="ba-card-icon">📂</div>
                <h3>Projetos em Vigor</h3>
                <p>Acesso direto ao estado de cada projeto — progresso, milestones, equipa.</p>
                <span class="ba-badge ba-badge-soon">Em breve</span>
            </div>
            <div class="ba-card">
                <div class="ba-card-icon">📊</div>
                <h3>Relatórios Claros</h3>
                <ul class="ba-list">
                    <li>✅ Receita por projeto</li>
                    <li>✅ Comissões geradas</li>
                    <li>✅ Histórico de pagamentos</li>
                    <li>✅ Projeções atualizadas</li>
                </ul>
            </div>
            <div class="ba-card">
                <div class="ba-card-icon">🤝</div>
                <h3>Sem Letras Pequenas</h3>
                <p>Termos claros, contrato definido, comunicação aberta. Sabes sempre o que esperar — e o que estás a ganhar.</p>
            </div>
        </div>
    </section>

    <section class="ba-section ba-section-modelo" id="modelo">
        <div class="ba-modelo-box">
            <div class="ba-modelo-big">2%</div>
            <div class="ba-modelo-text">
                <h2>Revenue Share em Projetos Elegíveis</h2>
                <p>Os Business Angels recebem <strong>2% de partilha de receita</strong> sobre projetos qualificados do ecossistema Beaver — pago <strong>trimestralmente</strong>, com termos claros e transparentes.</p>
                <p class="ba-disclaimer">⚖️ Programa de partilha de receita. Não é um produto de investimento. Termos e condições aplicáveis.</p>
            </div>
        </div>
    </section>

    <section class="ba-section" id="porque">
        <div class="ba-section-head">
            <span class="ba-kicker">🚀 Porquê</span>
            <h2>Porquê tornar-te um Beaver Business Angel?</h2>
            <p>Um ecossistema a funcionar — não uma promessa.</p>
        </div>
        <div class="ba-porque-grid">
            <div class="ba-porque-item">
                <div class="ba-porque-icon">🏗️</div>
                <h3>Um Ecossistema a Funcionar</h3>
                <ul class="ba-list">
                    <li>✅ <strong>Beaver API</strong> — completa e pronta para vender componentes, plugins, templates, cursos e projetos</li>
                    <li>✅ <strong>SQLWizard Lite</strong> — ativo, com templates de dados pré-concebidos</li>
                    <li>✅ <strong>Website completo</strong> — a lançar em breve</li>
                    <li>✅ <strong>SQLWizard Profissional</strong> — em desenvolvimento</li>
                </ul>
            </div>
            <div class="ba-porque-item">
                <div class="ba-porque-icon">🤝</div>
                <h3>Uma Equipa que Trabalha Contigo</h3>
                <p>Nós produzimos. Nós damos apoio. Tu acompanhas e ganhas. A nossa equipa trata do desenvolvimento, da infraestrutura e do suporte ao cliente.</p>
            </div>
            <div class="ba-porque-item">
                <div class="ba-porque-icon">🌍</div>
                <h3>Produtos Reais, Clientes Reais</h3>
                <p>Imagina os teus clientes precisarem de uma loja online ou de um projeto web — e tu poderes mostrar produtos já concebidos, com uma equipa que os apoia em cada passo.</p>
            </div>
            <div class="ba-porque-item">
                <div class="ba-porque-icon">📈</div>
                <h3>Cresce Connosco</h3>
                <p>À medida que o ecossistema Beaver se expande — novos componentes, novos plugins, novos mercados — o teu retorno cresce com ele.</p>
            </div>
        </div>
    </section>

    <section class="ba-section" id="vantagem">
        <div class="ba-section-head">
            <span class="ba-kicker">🔥 A Vantagem Beaver</span>
            <h2>O que recebes e porquê importa</h2>
        </div>
        <div class="ba-table-wrap">
            <table class="ba-table">
                <thead><tr><th>O que Recebes</th><th>Porquê Importa</th></tr></thead>
                <tbody>
                    <tr><td><strong>2% revenue share</strong></td><td>Retorno claro sobre projetos elegíveis</td></tr>
                    <tr><td>Acesso aos projetos em vigor</td><td>Transparência total sobre o que está ativo</td></tr>
                    <tr><td>Relatórios detalhados</td><td>Sabes sempre quanto e de onde vem</td></tr>
                    <tr><td>Produtos prontos</td><td>Vende componentes, plugins, templates, cursos e projetos</td></tr>
                    <tr><td>Equipa de apoio</td><td>Nós tratamos do trabalho pesado</td></tr>
                    <tr><td>SQLWizard tools</td><td>Gestão de dados profissional</td></tr>
                    <tr><td>Beaver API</td><td>Vende diretamente no teu próprio site</td></tr>
                    <tr><td>Catálogo em crescimento</td><td>Mais produtos = mais potencial</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="ba-section" id="paraquem">
        <div class="ba-section-head">
            <span class="ba-kicker">🎯 Para Quem É</span>
            <h2>Feito para quem constrói a sério</h2>
        </div>
        <div class="ba-perfis">
            <div class="ba-perfil"><div class="ba-perfil-icon">💼</div><h3>Investidores</h3><p>Que procuram participação num ecossistema tecnológico em crescimento.</p></div>
            <div class="ba-perfil"><div class="ba-perfil-icon">🏢</div><h3>Agências</h3><p>Que querem oferecer soluções prontas aos clientes.</p></div>
            <div class="ba-perfil"><div class="ba-perfil-icon">💻</div><h3>Developers</h3><p>Que querem ganhar com projetos que ajudam a promover.</p></div>
            <div class="ba-perfil"><div class="ba-perfil-icon">🚀</div><h3>Empreendedores</h3><p>Prontos para construir com uma equipa ao lado.</p></div>
        </div>
    </section>

    <section class="ba-section ba-filosofia">
        <blockquote class="ba-quote">
            <p>"Nós produzimos. Tu ganhas. Crescemos todos."</p>
            <footer>— A Filosofia Beaver</footer>
        </blockquote>
        <p class="ba-filosofia-text">Acreditamos no sucesso partilhado. Cada Business Angel não é apenas um investidor — é um parceiro na construção de algo duradouro.</p>
    </section>

    <section class="ba-section ba-cta-section" id="contacto">
        <div class="ba-cta-box">
            <span class="ba-kicker">📩 Pronto para Entrar?</span>
            <h2>Os lugares são limitados.</h2>
            <p>O Beaver Framework está prestes a lançar — e os lugares de Business Angel são limitados. Entra cedo. Acompanha tudo. Cresce connosco.</p>

            <form class="ba-form" method="post" action="mailto:contact@beaverphp.com" enctype="text/plain">
                <div class="ba-form-row">
                    <div>
                        <label>Nome</label>
                        <input type="text" name="nome" placeholder="O teu nome" required value="<?= htmlspecialchars($userName) ?>">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" placeholder="email@exemplo.com" required value="<?= htmlspecialchars($userEmail) ?>">
                    </div>
                </div>
                <label>Mensagem</label>
                <textarea name="mensagem" rows="4" placeholder="Conta-nos porque queres ser Business Angel..." required></textarea>
                <button type="submit" class="ba-btn ba-btn-primary ba-btn-block">📩 Contacta-nos hoje →</button>
            </form>

            <div class="ba-contact-alt">
                <p>Ou contacta-nos diretamente:</p>
                <a href="mailto:contact@beaverphp.com?subject=Beaver%20Business%20Angel" class="ba-contact-link">✉️ contact@beaverphp.com</a>
            </div>
        </div>
    </section>

</main>

<?php require __DIR__ . '/../../../../app/views/partials/footer.php'; ?>

<script src="/assets/js/header.js"></script>
</body>
</html>
