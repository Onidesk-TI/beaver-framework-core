<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Frankei — Planos</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --amber:#F5A623;--amber-2:#E07800;--amber-3:#FFC46B;
  --amber-deep:#7A3F00;--amber-soft:#FFF4E0;
  --text:#2B2F36;--soft:#5F656D;--muted:#8A9098;
  --bg:#FBF8F2;--line:#EDE6DA;--line-2:#E4D9C4;
  --purple:#7C3AED;
  --green:#10B981;
}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;min-height:100vh;padding:60px 20px;-webkit-font-smoothing:antialiased}
body::before{content:"";position:fixed;inset:0;z-index:-1;background:
  radial-gradient(900px 500px at 12% -8%,rgba(245,166,35,.18),transparent 62%),
  radial-gradient(760px 460px at 88% 4%,rgba(124,58,237,.08),transparent 60%)}

.back{position:fixed;top:24px;left:24px;padding:8px 14px;border-radius:9px;background:rgba(255,255,255,.9);border:1px solid var(--line);color:var(--soft);text-decoration:none;font-size:.82rem;font-weight:600;backdrop-filter:blur(10px);transition:all .15s;box-shadow:0 4px 12px -6px rgba(122,63,0,.12)}
.back:hover{color:var(--amber-2);border-color:var(--amber);box-shadow:0 6px 16px -6px rgba(224,120,0,.25)}

.wrap{max-width:1200px;margin:0 auto}

.head{text-align:center;margin-bottom:56px}
.eyebrow{display:inline-block;font-size:.75rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--amber-2);margin-bottom:16px}
.head h1{font-size:clamp(1.8rem,4vw,2.6rem);font-weight:900;letter-spacing:-.03em;margin-bottom:14px;color:var(--text)}
.head h1 .accent{background:linear-gradient(120deg,var(--amber-2),var(--amber));-webkit-background-clip:text;background-clip:text;color:transparent}
.head p{color:var(--soft);max-width:560px;margin:0 auto;font-size:1.05rem}

.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;align-items:start}
@media(max-width:960px){.cards{grid-template-columns:1fr;max-width:420px;margin:0 auto}}

.card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:32px 28px;position:relative;overflow:hidden;transition:.3s cubic-bezier(.4,0,.2,1);box-shadow:0 8px 24px -18px rgba(122,63,0,.15)}
.card:hover{transform:translateY(-4px);border-color:var(--line-2);box-shadow:0 20px 40px -24px rgba(122,63,0,.25)}
.card::before{content:"";position:absolute;top:0;left:22px;right:22px;height:1px;background:linear-gradient(90deg,transparent,rgba(245,166,35,.7),transparent);opacity:0;transition:.3s}
.card:hover::before{opacity:1}

/* Destaque: Premium */
.card.premium{background:linear-gradient(180deg,#fff,var(--amber-soft));border-color:rgba(224,120,0,.35);transform:scale(1.03);box-shadow:0 16px 40px -24px rgba(224,120,0,.35)}
.card.premium:hover{transform:scale(1.03) translateY(-4px)}
.card.premium::after{content:"RECOMENDADO";position:absolute;top:16px;right:16px;background:linear-gradient(135deg,var(--amber),var(--amber-2));color:#fff;font-size:.62rem;font-weight:800;letter-spacing:.08em;padding:4px 10px;border-radius:6px;box-shadow:0 4px 12px -4px rgba(224,120,0,.5)}

.tag{display:inline-flex;align-items:center;gap:8px;font-size:.72rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:5px 12px;border-radius:100px;margin-bottom:18px}
.tag.free{color:var(--soft);background:rgba(43,47,54,.06)}
.tag.pro{color:var(--purple);background:rgba(124,58,237,.1)}
.tag.premium{color:var(--amber-2);background:rgba(245,166,35,.15)}

.name{font-size:1.4rem;font-weight:800;letter-spacing:-.03em;margin-bottom:6px;color:var(--text)}
.desc{color:var(--soft);font-size:.88rem;margin-bottom:22px;min-height:40px}

.price{display:flex;align-items:baseline;gap:6px;margin-bottom:22px}
.price .value{font-size:2.4rem;font-weight:900;letter-spacing:-.04em;color:var(--text)}
.price .period{color:var(--muted);font-size:.88rem}

.cta{display:block;width:100%;padding:13px 20px;border-radius:11px;font-family:inherit;font-size:.92rem;font-weight:700;text-align:center;text-decoration:none;border:1px solid transparent;cursor:pointer;transition:all .15s;margin-bottom:24px}
.cta.free{background:var(--text);color:#fff}
.cta.free:hover{background:#1a1d22}
.cta.pro{background:rgba(124,58,237,.1);color:var(--purple);border-color:rgba(124,58,237,.25)}
.cta.pro:hover{background:rgba(124,58,237,.18)}
.cta.premium{background:linear-gradient(150deg,var(--amber-3),var(--amber) 55%,var(--amber-2));color:#fff;box-shadow:0 10px 24px -12px rgba(224,120,0,.6)}
.cta.premium:hover{transform:translateY(-1px);box-shadow:0 16px 32px -12px rgba(224,120,0,.8)}

.features{list-style:none;padding:0;margin:0}
.features li{display:flex;gap:10px;align-items:flex-start;padding:9px 0;font-size:.87rem;color:var(--soft);border-bottom:1px solid rgba(43,47,54,.05)}
.features li:last-child{border-bottom:0}
.feat-ico{width:18px;height:18px;flex:none;display:grid;place-items:center;font-size:.72rem;margin-top:2px;font-weight:800}
.feat-ok{color:var(--green)}
.feat-no{color:#C7CDD4}
.features li.off{color:var(--muted)}
.features li.off .feat-ico{opacity:.6}

.footer{text-align:center;margin-top:60px;color:var(--muted);font-size:.85rem}
.footer a{color:var(--amber-2);text-decoration:none;font-weight:600}
.footer a:hover{text-decoration:underline}
</style>
</head>
<body>

<a href="/frankei" class="back">← Voltar ao painel</a>

<div class="wrap">

  <div class="head">
    <span class="eyebrow">Planos</span>
    <h1>Escolhe o <span class="accent">plano certo</span> para ti</h1>
    <p>Começa grátis. Faz upgrade quando precisares. Sem cartão de crédito obrigatório.</p>
  </div>

  <div class="cards">

    <!-- ═══════ GRÁTIS ═══════ -->
    <div class="card free">
      <span class="tag free">🆓 Grátis</span>
      <h2 class="name">Grátis</h2>
      <p class="desc">Para começar. Análise básica do teu projeto.</p>

      <div class="price">
        <span class="value">0€</span>
        <span class="period">para sempre</span>
      </div>

      <a href="/frankei/unit-tests" class="cta free">Começar agora</a>

      <ul class="features">
        <li><span class="feat-ico feat-ok">✓</span> Análise de estrutura</li>
        <li><span class="feat-ico feat-ok">✓</span> Até 25 ficheiros por projeto</li>
        <li><span class="feat-ico feat-ok">✓</span> Relatórios básicos</li>
        <li><span class="feat-ico feat-ok">✓</span> 8 dias de acesso</li>
        <li class="off"><span class="feat-ico feat-no">✗</span> Testes unitários</li>
        <li class="off"><span class="feat-ico feat-no">✗</span> Segurança completa</li>
        <li class="off"><span class="feat-ico feat-no">✗</span> Índices e otimizações</li>
      </ul>
    </div>

    <!-- ═══════ PRO ═══════ -->
    <div class="card pro">
      <span class="tag pro">🚀 Pro</span>
      <h2 class="name">Pro</h2>
      <p class="desc">Para quem desenvolve a sério. Análise avançada sem limites.</p>

      <div class="price">
        <span class="value">49€</span>
        <span class="period">/ ano</span>
      </div>

      <a href="/frankei/pro" class="cta pro">Escolher Pro</a>

      <ul class="features">
        <li><span class="feat-ico feat-ok">✓</span> Tudo do plano Grátis</li>
        <li><span class="feat-ico feat-ok">✓</span> Ficheiros ilimitados</li>
        <li><span class="feat-ico feat-ok">✓</span> Análise de estrutura completa</li>
        <li><span class="feat-ico feat-ok">✓</span> Índices e otimizações de BD</li>
        <li><span class="feat-ico feat-ok">✓</span> Relatórios avançados</li>
        <li><span class="feat-ico feat-ok">✓</span> Exportação em PDF/ZIP</li>
        <li class="off"><span class="feat-ico feat-no">✗</span> Testes unitários</li>
        <li class="off"><span class="feat-ico feat-no">✗</span> Segurança completa</li>
      </ul>
    </div>

    <!-- ═══════ PREMIUM ═══════ -->
    <div class="card premium">
      <span class="tag premium">👑 Premium</span>
      <h2 class="name">Premium</h2>
      <p class="desc">Tudo incluído. Para equipas e projetos críticos.</p>

      <div class="price">
        <span class="value">199,99€</span>
        <span class="period">/ ano</span>
      </div>

      <a href="/frankei/premium" class="cta premium">Escolher Premium</a>

      <ul class="features">
        <li><span class="feat-ico feat-ok">✓</span> Tudo do plano Pro</li>
        <li><span class="feat-ico feat-ok">✓</span> <b>Testes unitários</b> (Pest + PHPUnit)</li>
        <li><span class="feat-ico feat-ok">✓</span> <b>Segurança completa</b> (10+ scanners)</li>
        <li><span class="feat-ico feat-ok">✓</span> Auto-fix inteligente</li>
        <li><span class="feat-ico feat-ok">✓</span> Análise de desempenho N+1</li>
        <li><span class="feat-ico feat-ok">✓</span> Suporte prioritário</li>
        <li><span class="feat-ico feat-ok">✓</span> Licença comercial</li>
        <li><span class="feat-ico feat-ok">✓</span> Atualizações incluídas</li>
      </ul>
    </div>

  </div>

  <div class="footer">
    Dúvidas? <a href="mailto:tissurina@gmail.com">Contacta-nos</a> · Preços em EUR, sem IVA.
  </div>

</div>

</body>
</html>
