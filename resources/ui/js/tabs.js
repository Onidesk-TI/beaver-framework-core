/* ============================================================
   Beaver — Code Window Tabs
   ============================================================ */

(function () {
  var tabs  = document.querySelectorAll('.code-tab');
  var panes = document.querySelectorAll('.code-pane');
  if (!tabs.length || !panes.length) return;

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.dataset.tab;

      tabs.forEach(function (t) {
        t.classList.toggle('active', t === tab);
      });

      panes.forEach(function (p) {
        p.classList.toggle('active', p.id === target);
      });
    });
  });
})();
