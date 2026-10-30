/*
  Carrito: los botones + y − actualizan solos (sin pulsar "Actualizar") usando el propio botón de WooCommerce.
  Quitar un producto anima la salida antes de recargar el carrito.
*/
(function () {
  'use strict';
  var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var temporizador = 0;

  function actualizar(form) {
    var btn = form && form.querySelector('[name="update_cart"]');
    if (!btn) return;
    btn.disabled = false;
    btn.removeAttribute('aria-disabled');
    form.classList.add('is-ocupado');
    btn.click();
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-ca-menos], [data-ca-mas], .ts-ca__quitar');
    if (!t) return;
    var fila = t.closest('.ts-ca__item');
    if (!fila) return;
    if (t.classList.contains('ts-ca__quitar')) {
      if (reducido) return;
      e.preventDefault();
      fila.classList.add('is-saliendo');
      var href = t.href;
      setTimeout(function () { window.location.href = href; }, 220);
      return;
    }
    var input = fila.querySelector('input.qty');
    var n = (parseInt(input.value, 10) || 0) + (t.hasAttribute('data-ca-mas') ? 1 : -1);
    var max = parseInt(input.getAttribute('max'), 10) || 0;
    if (n < 0 || (max > 0 && n > max)) return;
    input.value = String(n);
    clearTimeout(temporizador);
    temporizador = setTimeout(function () { actualizar(t.closest('form')); }, 450);
  });

  document.addEventListener('change', function (e) {
    if (e.target.matches('.ts-ca input.qty')) {
      clearTimeout(temporizador);
      temporizador = setTimeout(function () { actualizar(e.target.closest('form')); }, 450);
    }
  });

  var titulo = document.querySelector('.ts-ok__titulo');
  if (titulo) titulo.focus({ preventScroll: true });
})();
