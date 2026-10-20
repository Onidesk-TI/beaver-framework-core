<?php

declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Http;

use Beaver\Http\Response;

class UnitTestsController
{
    public function index(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Unit tests — Frankei</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--amber:#F5A623;--amber-2:#E07800;--amber-3:#FFC46B;--amber-deep:#7A3F00;--amber-soft:#FFF4E0;--text:#2B2F36;--soft:#5F656D;--muted:#8A9098;--bg:#FBF8F2;--line:#EDE6DA;--line-2:#E4D9C4;--purple:#7C3AED}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased}
body::before{content:"";position:fixed;inset:0;z-index:-1;background:radial-gradient(900px 500px at 10% -5%,rgba(245,166,35,.15),transparent 60%),radial-gradient(800px 500px at 95% 10%,rgba(124,58,237,.06),transparent 55%)}
code,pre{font-family:'JetBrains Mono',ui-monospace,monospace}
header{position:sticky;top:0;z-index:10;backdrop-filter:saturate(180%) blur(14px);background:rgba(251,248,242,.85);border-bottom:1px solid var(--line);padding:14px 0}
.wrap{max-width:1120px;margin:0 auto;padding:0 24px}
.nav{display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.1rem;text-decoration:none;color:var(--text)}
.brand svg{width:36px;height:36px}
.nav-links{display:flex;gap:22px;font-size:.9rem;font-weight:500}
.nav-links a{color:var(--soft);text-decoration:none}
.nav-links a:hover{color:var(--amber-2)}
.nav-cta{padding:8px 14px;border-radius:8px;background:var(--text);color:#fff;text-decoration:none;font-weight:600;font-size:.82rem}
.hero{padding:70px 0 50px}
.eyebrow{display:inline-block;font-size:.75rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--amber-2);margin-bottom:14px}
.hero h1{font-size:clamp(2rem,4.5vw,3.2rem);font-weight:900;letter-spacing:-.03em;line-height:1.05;margin-bottom:18px}
.hero h1 .accent{background:linear-gradient(120deg,var(--amber-2),var(--amber));-webkit-background-clip:text;background-clip:text;color:transparent}
.hero .lead{font-size:1.1rem;color:var(--soft);max-width:640px;margin-bottom:30px}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:11px;font-weight:600;font-size:.92rem;text-decoration:none;border:0;cursor:pointer;transition:transform .1s,box-shadow .15s}
.btn-primary{background:linear-gradient(150deg,var(--amber-3),var(--amber) 55%,var(--amber-2));color:var(--amber-deep);box-shadow:0 12px 30px -12px rgba(224,120,0,.5)}
.btn-primary:hover{box-shadow:0 16px 40px -14px rgba(224,120,0,.65);transform:translateY(-1px)}
.btn-ghost{background:#fff;color:var(--text);border:1px solid var(--line-2)}
.btn-ghost:hover{border-color:var(--amber);color:var(--amber-2)}
section{padding:60px 0}
.section-head{max-width:640px;margin-bottom:40px}
.section-head h2{font-size:clamp(1.5rem,3vw,2rem);font-weight:800;letter-spacing:-.02em;margin-bottom:10px}
.section-head p{color:var(--soft)}
.engines{display:grid;grid-template-columns:1fr 1fr;gap:22px}
@media(max-width:720px){.engines{grid-template-columns:1fr}}
.engine{background:#fff;border:1px solid var(--line);border-radius:16px;padding:26px;transition:.2s}
.engine:hover{border-color:var(--amber);box-shadow:0 20px 40px -24px rgba(224,120,0,.35)}
.engine-head{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.engine-logo{width:42px;height:42px;border-radius:11px;display:grid;place-items:center;font-weight:800;font-size:1.2rem}
.engine-logo.pest{background:#F3EEFF;color:#7C3AED}
.engine-logo.phpunit{background:#EAF2FF;color:#3B82F6}
.engine h3{font-size:1.1rem;font-weight:700}
.engine .tag{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.engine p{color:var(--soft);font-size:.9rem;margin:12px 0 16px}
.engine pre{background:#FAF6EE;border:1px solid var(--line);border-radius:10px;padding:14px 16px;font-size:.75rem;line-height:1.7;overflow-x:auto;color:#2B2F36}
.kw{color:#7C3AED;font-weight:600}.fn{color:#E07800;font-weight:600}.str{color:#10B981}.cm{color:#9CA3AF;font-style:italic}.num{color:#DC2626}
.cheatsheet{background:#0A0A0A;border:1px solid #1F1F1F;border-radius:14px;overflow:hidden;box-shadow:0 30px 60px -30px rgba(0,0,0,.5)}
.cheat-bar{display:flex;align-items:center;gap:8px;padding:11px 16px;background:#141414;border-bottom:1px solid #1F1F1F}
.cheat-bar .dot{width:11px;height:11px;border-radius:50%}
.cheat-bar .dot.r{background:#FF5F56}.cheat-bar .dot.y{background:#FFBD2E}.cheat-bar .dot.g{background:#27C93F}
.cheat-bar .title{margin-left:8px;font-size:.74rem;color:#6B6B6B;font-family:'JetBrains Mono',monospace}
.cheat-body{padding:20px 22px;font-family:'JetBrains Mono',monospace;font-size:.78rem;line-height:1.9;color:#E5E5E5;overflow-x:auto}
.cheat-body .cmd{color:#FFF}.cheat-body .comment{color:#6B6B6B;font-style:italic}.cheat-body .prompt{color:#FF9A3C;font-weight:600}.cheat-body .flag{color:#FFC46B}.cheat-body .arg{color:#FFE4B8}
.flow{background:linear-gradient(180deg,#fff,#FBF8F2);border:1px solid var(--line);border-radius:20px;padding:38px}
.flow-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}
@media(max-width:900px){.flow-steps{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.flow-steps{grid-template-columns:1fr}}
.step{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px}
.step-num{width:30px;height:30px;border-radius:8px;background:var(--amber-soft);color:var(--amber-2);display:grid;place-items:center;font-weight:800;font-size:.85rem;margin-bottom:12px}
.step h4{font-size:.92rem;margin-bottom:6px;font-weight:700}.step p{font-size:.8rem;color:var(--soft)}
.cta{background:linear-gradient(135deg,#2B1A0E,#4A2A12);color:#fff;border-radius:20px;padding:52px 44px;text-align:center;position:relative;overflow:hidden}
.cta::before{content:"";position:absolute;inset:0;background:radial-gradient(600px 400px at 50% 0%,rgba(245,166,35,.25),transparent 65%);pointer-events:none}
.cta h2{font-size:clamp(1.5rem,3.5vw,2.2rem);font-weight:900;letter-spacing:-.02em;margin-bottom:12px;position:relative}
.cta p{color:rgba(255,255,255,.7);max-width:520px;margin:0 auto 28px;position:relative}
.cta .btn-primary{position:relative;padding:14px 26px}
footer{padding:36px 0 50px;color:var(--muted);font-size:.82rem;border-top:1px solid var(--line);margin-top:60px}
footer .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
footer a{color:var(--soft);text-decoration:none;margin-left:18px}
footer a:hover{color:var(--amber-2)}
</style>
</head>
<body>
<header>
  <div class="wrap nav">
    <a href="/frankei" class="brand">
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><linearGradient id="bvgNav" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FFD79A"/><stop offset="48%" stop-color="#FF9A3C"/><stop offset="100%" stop-color="#FF6B35"/></linearGradient></defs><circle cx="13" cy="17.5" r="7.5" fill="url(#bvgNav)" opacity=".72"/><circle cx="51" cy="17.5" r="7.5" fill="url(#bvgNav)" opacity=".72"/><ellipse cx="32" cy="33" rx="23" ry="21" fill="url(#bvgNav)"/><ellipse cx="32" cy="41" rx="15.5" ry="12" fill="#0A0E14" opacity=".2"/><ellipse cx="32" cy="34.5" rx="4.6" ry="3.2" fill="#1A1208"/><circle cx="22.5" cy="26" r="3.4" fill="#1A1208"/><circle cx="41.5" cy="26" r="3.4" fill="#1A1208"/><circle cx="23.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85"/><circle cx="42.6" cy="25" r="1.1" fill="#FFF6E8" opacity=".85"/><rect x="27.3" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/><rect x="32.4" y="44.5" width="4.3" height="10.5" rx="1.7" fill="#FFF6E8"/></svg>
      <span>Frankei</span>
    </a>
    <nav class="nav-links">
      <a href="/frankei">← Painel</a>
      <a href="#engines">Engines</a>
      <a href="#cli">CLI</a>
      <a href="#fluxo">Fluxo</a>
    </nav>
    <a href="/frankei" class="nav-cta">Abrir consola →</a>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <span class="eyebrow">Unit tests</span>
    <h1>Testes unitários,<br><span class="accent">sem cerimónia.</span></h1>
    <p class="lead">Um motor só, duas sintaxes. Escreves como preferes — Pest ou PHPUnit — e o Frankei trata do resto: correr, ler, automatizar e mostrar.</p>
    <div class="hero-cta">
      <a href="/frankei" class="btn btn-primary">Abrir a consola →</a>
      <a href="#cli" class="btn btn-ghost">Ver comandos</a>
    </div>
  </div>
</section>

<section id="engines">
  <div class="wrap">
    <div class="section-head">
      <h2>Um motor. Duas sintaxes.</h2>
      <p>Escreves como preferes. O Frankei traduz, corre e mostra.</p>
    </div>
    <div class="engines">
      <div class="engine">
        <div class="engine-head">
          <div class="engine-logo pest">P</div>
          <div><h3>Pest</h3><span class="tag">Elegante · Expressivo</span></div>
        </div>
        <p>Escrito por <strong>Nuno Maduro</strong> 🇵🇹. Sintaxe próxima da linguagem natural.</p>
<pre><span class="fn">it</span>(<span class="str">'processa um pagamento válido'</span>, <span class="kw">function</span> () {
    <span class="fn">expect</span>(<span class="fn">pay</span>(<span class="num">100</span>))-><span class="fn">toBe</span>(<span class="str">'ok'</span>);
});</pre>
      </div>
      <div class="engine">
        <div class="engine-head">
          <div class="engine-logo phpunit">U</div>
          <div><h3>PHPUnit</h3><span class="tag">Clássico · Robusto</span></div>
        </div>
        <p>O padrão da indústria. Estrutura em classes.</p>
<pre><span class="kw">class</span> <span class="fn">PayTest</span> <span class="kw">extends</span> TestCase
{
    <span class="kw">public function</span> <span class="fn">testValid</span>(): <span class="kw">void</span>
    {
        <span class="fn">assertSame</span>(<span class="str">'ok'</span>, <span class="fn">pay</span>(<span class="num">100</span>));
    }
}</pre>
      </div>
    </div>
  </div>
</section>

<section id="cli">
  <div class="wrap">
    <div class="section-head">
      <h2>Cheatsheet da consola</h2>
      <p>Todos os comandos que precisas.</p>
    </div>
    <div class="cheatsheet">
      <div class="cheat-bar">
        <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
        <span class="title">beaver.sh — frankei</span>
      </div>
      <div class="cheat-body">
<span class="comment"># correr todos os testes</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">test</span>

<span class="comment"># correr só um controller / módulo</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">test</span> <span class="flag">UserController</span> <span class="flag">--</span>

<span class="comment"># com cobertura de código</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">test</span> <span class="flag">--coverage</span>

<span class="comment"># em paralelo</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">test</span> <span class="flag">--parallel</span>

<span class="comment"># filtrar por nome</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">test</span> <span class="flag">--filter</span>=<span class="arg">testPayment</span>

<span class="comment"># gerar a partir de pseudocódigo</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">automate</span> <span class="arg">pseudocode/mbway.fk</span>

<span class="comment"># criar um teste manualmente</span>
<span class="prompt">$</span> <span class="cmd">frankei</span> <span class="arg">make:test</span> <span class="arg">PaymentTest</span>
      </div>
    </div>
  </div>
</section>

<section id="fluxo">
  <div class="wrap">
    <div class="flow">
      <div class="section-head" style="margin-bottom:0">
        <h2>Do pseudocódigo ao teste, em 4 passos</h2>
        <p>Escreves a intenção. O Frankei trata do resto.</p>
      </div>
      <div class="flow-steps">
        <div class="step"><div class="step-num">1</div><h4>Pseudocódigo</h4><p>Ficheiro <code>.fk</code> em português.</p></div>
        <div class="step"><div class="step-num">2</div><h4>Análise</h4><p>Entradas, saídas e casos-limite.</p></div>
        <div class="step"><div class="step-num">3</div><h4>Geração</h4><p>Pest ou PHPUnit, pronto a rever.</p></div>
        <div class="step"><div class="step-num">4</div><h4>Corre e vê</h4><p>Consola unificada, coverage integrada.</p></div>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="cta">
      <h2>Pronto para testar como pensas?</h2>
      <p>Abre a consola Frankei e começa.</p>
      <a href="/frankei" class="btn btn-primary">Abrir a consola Frankei →</a>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div>🦫 <strong>Frankei</strong> — parte do Beaver Framework</div>
    <div>
      <a href="/frankei">Painel</a>
      <a href="/frankei/path">Path</a>
      <a href="/frankei/logout">Sair</a>
    </div>
  </div>
</footer>
</body>
</html>
HTML;

        return (new Response($html))
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
