/**
 * Ficha de producto: tooltips de las características (mismo patrón que los de Wompi en el pago).
 * El video corto va en assets/js/video.js. Sin dependencias.
 */
(function () {
  'use strict';

  // ---- Tooltips: hover y foco por CSS; toque o clic alternan .is-abierto; tocar fuera o Escape cierran.
  var raiz = document.querySelector('[data-ts-meta]');
  function centrar(t) {
    var g = t.querySelector('.ts-tip__globo');
    if (!g) return;
    var r = t.getBoundingClientRect(), m = 12;
    var min = m, max = document.documentElement.clientWidth - m;
    g.style.maxWidth = Math.min(256, max - min) + 'px';
    var w = g.offsetWidth, cen = r.left + r.width / 2;
    var izq = Math.max(min, Math.min(cen - w / 2, max - w));
    var ax = Math.max(14, Math.min(cen - izq, w - 14)); // la flecha siempre apunta al botón
    g.style.setProperty('--gl', (izq - r.left) + 'px');
    g.style.setProperty('--ax', ax + 'px');
  }
  if (raiz) {
    var cerrarTodos = function (salvo) {
      raiz.querySelectorAll('[data-tip].is-abierto').forEach(function (n) {
        if (n !== salvo) { n.classList.remove('is-abierto'); n.setAttribute('aria-expanded', 'false'); }
      });
    };
    raiz.addEventListener('mouseover', function (e) { var t = e.target.closest('[data-tip]'); if (t) centrar(t); });
    raiz.addEventListener('focusin', function (e) { var t = e.target.closest('[data-tip]'); if (t) centrar(t); });
    document.addEventListener('click', function (e) {
      var t = e.target.closest && e.target.closest('[data-tip]');
      if (t && !raiz.contains(t)) t = null;
      cerrarTodos(t);
      if (t) {
        centrar(t);
        var abierto = t.classList.toggle('is-abierto');
        t.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarTodos(null); });
    window.addEventListener('resize', function () { raiz.querySelectorAll('[data-tip].is-abierto').forEach(centrar); }, { passive: true });
  }
  // El video corto lo maneja assets/js/video.js (reproductor común con Nosotros).
})();
