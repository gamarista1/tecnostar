/*
  Mini carrito lateral: abre al agregar un producto o al pulsar el carrito de la cabecera.
  Cantidades y quitar usan un punto de WooCommerce propio (wc-ajax=ts_mc_cantidad); el carrito sigue siendo el de WooCommerce.
*/
(function () {
  'use strict';

  var marco = document.querySelector('[data-mc]');
  if (!marco) return;

  var panel = marco.querySelector('.ts-mc__panel');
  var $ = window.jQuery;
  var cfg = window.tsMiniCart || {};
  var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var ultimoFoco = null;
  var temporizador = 0;
  var abierto = false;

  function url(ep) { return (cfg.ajax || '').replace('%%endpoint%%', ep); }

  function enfocables() {
    return [].slice.call(panel.querySelectorAll('a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])')).filter(function (el) { return el.offsetParent !== null; });
  }

  function abrir() {
    if (abierto) return;
    abierto = true;
    ultimoFoco = document.activeElement;
    clearTimeout(temporizador);
    marco.classList.remove('is-cerrando');
    void marco.offsetWidth;
    marco.classList.add('is-abierto');
    document.body.classList.add('ts-mc-abierto');
    panel.style.transform = '';
    panel.focus({ preventScroll: true });
  }

  function cerrar() {
    if (!abierto) return;
    abierto = false;
    marco.classList.remove('is-abierto');
    marco.classList.add('is-cerrando');
    document.body.classList.remove('ts-mc-abierto');
    temporizador = setTimeout(function () { marco.classList.remove('is-cerrando'); }, reducido ? 200 : 520);
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus({ preventScroll: true });
  }

  function aplicar(fragmentos) {
    if (!fragmentos) return;
    Object.keys(fragmentos).forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        var t = document.createElement('template');
        t.innerHTML = String(fragmentos[sel]).trim();
        var nuevo = t.content.firstElementChild;
        if (nuevo) el.replaceWith(nuevo);
      });
    });
    var cuenta = document.querySelector('.cart-count');
    if (cuenta) {
      cuenta.classList.remove('ts-tick');
      void cuenta.offsetWidth;
      cuenta.classList.add('ts-tick');
    }
  }

  function rebote() {
    var ic = document.querySelector('[data-mc-abrir]');
    if (!ic || reducido) return;
    ic.classList.remove('ts-rebota');
    void ic.offsetWidth;
    ic.classList.add('ts-rebota');
  }

  // El producto "vuela" hacia el carrito de la cabecera
  function volar(btn, despues) {
    var cont = btn && btn.closest ? btn.closest('li.product, .product, .type-product') : null;
    var img = cont && cont.querySelector('img');
    var meta = document.querySelector('[data-mc-abrir]');
    if (reducido || !img || !meta || !img.getBoundingClientRect().width) { despues(); return; }
    var r = img.getBoundingClientRect();
    var m = meta.getBoundingClientRect();
    var c = document.createElement('img');
    c.src = img.currentSrc || img.src;
    c.alt = '';
    c.className = 'ts-vuela';
    c.style.left = r.left + 'px';
    c.style.top = r.top + 'px';
    c.style.width = r.width + 'px';
    c.style.height = r.height + 'px';
    document.body.appendChild(c);
    var dx = m.left + m.width / 2 - (r.left + r.width / 2);
    var dy = m.top + m.height / 2 - (r.top + r.height / 2);
    var a = c.animate([
      { transform: 'translate(0, 0) scale(1)', opacity: 1 },
      { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(0.12)', opacity: 0.4 }
    ], { duration: 620, easing: 'cubic-bezier(0.5, 0, 0.75, 0)' });
    a.onfinish = function () { c.remove(); despues(); };
    a.oncancel = function () { c.remove(); despues(); };
  }

  // ---- Cantidades y quitar ------------------------------------------------------
  function enviar(clave, cantidad) {
    var cuerpo = marco.querySelector('.ts-mc__cuerpo');
    if (cuerpo) cuerpo.classList.add('is-ocupado');
    var datos = new URLSearchParams({ key: clave, qty: String(cantidad), nonce: cfg.nonce || '' });
    fetch(url('ts_mc_cantidad'), { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (r && r.success) {
          aplicar(r.data.fragments);
          if ($) $(document.body).trigger('wc_fragments_refreshed');
        } else {
          window.location.reload();
        }
      })
      .catch(function () { window.location.reload(); })
      .then(function () {
        var c = marco.querySelector('.ts-mc__cuerpo');
        if (c) c.classList.remove('is-ocupado');
      });
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-mc-abrir], [data-mc-cerrar], [data-mc-menos], [data-mc-mas], [data-mc-quitar]');
    if (!t) return;
    if (t.hasAttribute('data-mc-abrir')) { e.preventDefault(); abrir(); return; }
    if (t.hasAttribute('data-mc-cerrar')) { cerrar(); return; }
    var li = t.closest('.ts-mc__item');
    if (!li) return;
    var salida = li.querySelector('[data-mc-n-item]');
    var n = parseInt(salida.textContent, 10) || 1;
    var clave = li.getAttribute('data-key');
    if (t.hasAttribute('data-mc-quitar') || (t.hasAttribute('data-mc-menos') && n <= 1)) {
      li.classList.add('is-saliendo');
      setTimeout(function () { enviar(clave, 0); }, reducido ? 0 : 200);
      return;
    }
    var mas = li.querySelector('[data-mc-mas]');
    var max = parseInt(mas.getAttribute('data-max'), 10) || 0;
    n += t.hasAttribute('data-mc-mas') ? 1 : -1;
    if (max > 0 && n > max) return;
    salida.textContent = String(n);
    mas.disabled = max > 0 && n >= max;
    clearTimeout(li._t);
    li._t = setTimeout(function () { enviar(clave, n); }, 320);
  });

  // ---- Teclado: Escape y foco atrapado -------------------------------------------
  document.addEventListener('keydown', function (e) {
    if (!abierto) return;
    if (e.key === 'Escape') { cerrar(); return; }
    if (e.key !== 'Tab') return;
    var f = enfocables();
    if (!f.length) return;
    var primero = f[0];
    var ultimo = f[f.length - 1];
    if (e.shiftKey && (document.activeElement === primero || document.activeElement === panel)) { e.preventDefault(); ultimo.focus(); }
    else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus(); }
  });

  // ---- Arrastrar hacia la derecha para cerrar (sigue al dedo y conserva la velocidad) ----
  var cab = marco.querySelector('.ts-mc__cab');
  var arrastre = null;
  cab.addEventListener('pointerdown', function (e) {
    if (e.target.closest('button') || e.pointerType === 'mouse') return;
    cab.setPointerCapture(e.pointerId);
    arrastre = { x0: e.clientX, dx: 0, historial: [{ x: e.clientX, t: e.timeStamp }] };
    panel.classList.add('is-arrastrando');
  });
  cab.addEventListener('pointermove', function (e) {
    if (!arrastre) return;
    arrastre.dx = Math.max(0, e.clientX - arrastre.x0);
    arrastre.historial.push({ x: e.clientX, t: e.timeStamp });
    if (arrastre.historial.length > 6) arrastre.historial.shift();
    panel.style.transform = 'translateX(' + arrastre.dx + 'px)';
  });
  function soltar() {
    if (!arrastre) return;
    var h = arrastre.historial;
    var v = h.length > 1 ? (h[h.length - 1].x - h[0].x) / Math.max(1, h[h.length - 1].t - h[0].t) : 0;
    var proyectado = arrastre.dx + v * 200;
    panel.classList.remove('is-arrastrando');
    panel.style.transform = '';
    if (proyectado > panel.offsetWidth * 0.4) cerrar();
    arrastre = null;
  }
  cab.addEventListener('pointerup', soltar);
  cab.addEventListener('pointercancel', soltar);

  // ---- Agregar al carrito -------------------------------------------------------
  if ($) {
    $(document.body).on('added_to_cart', function (e, fragmentos, hash, $btn) {
      aplicar(fragmentos);
      var b = $btn && $btn[0];
      volar(b, function () { rebote(); abrir(); });
    });
  }

  // Ficha de producto simple: se agrega sin recargar la página
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('form.cart') || f.classList.contains('variations_form') || f.classList.contains('grouped_form')) return;
    var idEl = f.querySelector('[name="add-to-cart"]');
    if (!idEl || !idEl.value) return;
    e.preventDefault();
    var btn = f.querySelector('.single_add_to_cart_button');
    var q = f.querySelector('input.qty');
    var datos = new URLSearchParams({ product_id: idEl.value, quantity: q ? q.value : '1' });
    if (btn) btn.classList.add('is-cargando');
    fetch(url('add_to_cart'), { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (btn) btn.classList.remove('is-cargando');
        if (d.error && d.product_url) { window.location = d.product_url; return; }
        if ($) $(document.body).trigger('added_to_cart', [d.fragments, d.cart_hash, $(btn)]);
        if (btn) {
          btn.classList.add('is-listo');
          setTimeout(function () { btn.classList.remove('is-listo'); }, 1400);
        }
      })
      .catch(function () { HTMLFormElement.prototype.submit.call(f); });
  });
})();
