/* ══════════════════════════════════════════════════════════════
   prices.js — chip de idioma + CTAs
   ══════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  // ── Chip de idioma ──
  var chip = document.getElementById('fkLangChip');
  var dropdown = document.getElementById('fkLangDropdown');
  var wrap = document.getElementById('fkLangChipWrap');
  var flag = document.getElementById('fkLangChipFlag');
  var code = document.getElementById('fkLangChipCode');

  var FLAGS = { pt: '🇵🇹', en: '🇬🇧', fr: '🇫🇷', es: '🇪🇸' };

  function aplicarVisual(lang) {
    if (flag) flag.textContent = FLAGS[lang] || '🇵🇹';
    if (code) code.textContent = lang.toUpperCase();
    if (dropdown) {
      dropdown.querySelectorAll('.fk-lang-option').forEach(function (b) {
        b.classList.toggle('is-active', b.getAttribute('data-lang') === lang);
      });
    }
  }

  // Detectar língua guardada (mesmo storage do i18n.js)
  var saved = 'pt';
  try { saved = localStorage.getItem('frankei:lang') || 'pt'; } catch (e) {}
  aplicarVisual(saved);

  if (chip && dropdown) {
    chip.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.hidden = !dropdown.hidden;
    });

    document.addEventListener('click', function (e) {
      if (dropdown.hidden) return;
      if (!wrap || !wrap.contains(e.target)) dropdown.hidden = true;
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') dropdown.hidden = true;
    });

    dropdown.querySelectorAll('.fk-lang-option').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var lang = this.getAttribute('data-lang');
        try { localStorage.setItem('frankei:lang', lang); } catch (e) {}
        aplicarVisual(lang);
        dropdown.hidden = true;
        // Avisar o i18n global
        window.dispatchEvent(new CustomEvent('frankei:lang', { detail: { lang: lang } }));
        // Se o i18n expuser setLang, chamar também
        if (typeof window.setLang === 'function') {
          try { window.setLang(lang); } catch (e) {}
        }
      });
    });
  }

  // ── CTAs ──
  document.querySelectorAll('.fk-price-cta').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var plano = this.getAttribute('data-plano');
      console.log('[fk-prices] plano escolhido:', plano);
      if (plano === 'free') {
        window.location.href = '/frankei-trial';
        return;
      }
      // Checkout (ajusta se tiveres endpoint próprio)
      window.location.href = '/frankei/precos/checkout?plano=' + encodeURIComponent(plano);
    });
  });

  console.log('[fk-prices] pronto · língua:', saved);
})();
