/*
  Pago en 3 pasos sobre el <form class="checkout"> de WooCommerce.
  No cambia cómo se envía el pedido: solo muestra un paso a la vez, valida cada campo al salir de él
  y lleva al cliente al paso del error si el servidor rechaza algo. Sin JavaScript se ven todos los pasos.
*/
(function () {
  'use strict';

  var raiz = document.querySelector('[data-co]');
  if (!raiz) return;

  var form = raiz.querySelector('form.checkout');
  var paneles = [].slice.call(raiz.querySelectorAll('.ts-paso'));
  var navs = [].slice.call(raiz.querySelectorAll('.ts-co__paso'));
  var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = window.jQuery;
  var actual = 0;
  var maximo = 0;
  var anims = [];
  var EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

  var REGLAS = {
    billing_first_name: function (v) { return v.trim().length >= 2 || 'Escribe tu nombre'; },
    billing_last_name: function (v) { return v.trim().length >= 2 || 'Escribe tu apellido'; },
    billing_id: function (v) {
      var d = v.replace(/\D/g, '');
      return (d.length >= 5 && d.length <= 12) || 'Escribe tu cédula o NIT, solo números';
    },
    billing_email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) || 'Revisa tu correo, parece incompleto'; },
    billing_phone: function (v) { return v.replace(/\D/g, '').length >= 10 || 'Escribe tu celular de 10 dígitos'; },
    billing_state: function (v) { return !!v || 'Elige tu departamento'; },
    billing_city: function (v) { return v.trim().length >= 2 || 'Escribe tu ciudad'; },
    billing_address_1: function (v) { return v.trim().length >= 5 || 'Escribe tu dirección completa'; }
  };

  function campo(nombre) { return form.querySelector('[name="' + nombre + '"]'); }

  // ---- Validación por campo -----------------------------------------------------
  function marcar(el, resultado) {
    var fila = el.closest('.form-row');
    if (!fila) return;
    var previo = fila.querySelector('.ts-error');
    if (previo) previo.remove();
    fila.classList.remove('ts-valid', 'ts-invalid');
    if (resultado === true) {
      fila.classList.add('ts-valid');
      el.removeAttribute('aria-invalid');
      el.removeAttribute('aria-describedby');
      return;
    }
    var aviso = document.createElement('span');
    aviso.className = 'ts-error';
    aviso.id = (el.id || el.name) + '_error';
    aviso.setAttribute('role', 'alert');
    aviso.textContent = resultado;
    fila.appendChild(aviso);
    fila.classList.add('ts-invalid');
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', aviso.id);
  }

  function validar(el) {
    var regla = REGLAS[el.name];
    if (!regla) return true;
    var r = regla(el.value || '');
    marcar(el, r);
    return r === true;
  }

  function camposRegla(panel) {
    return [].slice.call(panel.querySelectorAll('input, select, textarea')).filter(function (el) {
      return REGLAS[el.name] && el.type !== 'hidden';
    });
  }

  function validarPaso(i) {
    var primero = null;
    camposRegla(paneles[i]).forEach(function (el) {
      if (!validar(el) && !primero) primero = el;
    });
    if (i === 1 && !primero) {
      var radios = [].slice.call(paneles[1].querySelectorAll('.ts-envio input.shipping_method[type="radio"]'));
      if (radios.length && !radios.some(function (r) { return r.checked; })) {
        var caja = paneles[1].querySelector('.ts-envio');
        marcar(radios[0], 'Elige cómo quieres recibir tu pedido');
        caja.scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
        return false;
      }
    }
    if (primero) {
      primero.focus({ preventScroll: true });
      primero.scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
      return false;
    }
    return true;
  }

  form.addEventListener('input', function (e) {
    var t = e.target;
    if (t.name === 'billing_id') t.value = t.value.replace(/[^\d.\-\s]/g, '');
    if (t.name === 'billing_phone') t.value = t.value.replace(/[^\d+\s]/g, '');
    var fila = t.closest && t.closest('.form-row');
    if (fila && fila.classList.contains('ts-invalid')) validar(t);
  });
  form.addEventListener('focusout', function (e) {
    var t = e.target;
    if (!REGLAS[t.name]) return;
    var fila = t.closest('.form-row');
    if (t.value || (fila && fila.classList.contains('ts-invalid'))) validar(t);
  });
  if ($) {
    $(form).on('change', 'select', function () {
      if (REGLAS[this.name]) validar(this);
    });
  }

  // ---- Navegación entre pasos ---------------------------------------------------
  function recap() {
    var r0 = raiz.querySelector('[data-recap="0"]');
    var r1 = raiz.querySelector('[data-recap="1"]');
    function llenar(nodo, texto, destino) {
      if (!nodo) return;
      nodo.textContent = '';
      var span = document.createElement('span');
      span.textContent = texto;
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('data-ir', String(destino));
      b.textContent = 'Editar';
      nodo.appendChild(span);
      nodo.appendChild(b);
    }
    var v = function (n) { var el = campo(n); return el ? el.value.trim() : ''; };
    llenar(r0, 'Para ' + (v('billing_first_name') + ' ' + v('billing_last_name')).trim() + ' · ' + v('billing_email') + ' · ' + v('billing_phone'), 0);
    var estado = campo('billing_state');
    var depto = estado && estado.options && estado.selectedIndex >= 0 ? estado.options[estado.selectedIndex].text : v('billing_state');
    llenar(r1, 'Entrega en ' + v('billing_address_1') + ', ' + v('billing_city') + (depto ? ', ' + depto : ''), 1);
  }

  function pintarNav() {
    navs.forEach(function (li, i) {
      var btn = li.querySelector('.ts-co__paso-btn');
      li.classList.toggle('is-actual', i === actual);
      li.classList.toggle('is-hecho', i < actual);
      btn.disabled = i > maximo;
      if (i === actual) btn.setAttribute('aria-current', 'step');
      else btn.removeAttribute('aria-current');
    });
  }

  function cancelar() {
    anims.forEach(function (a) { try { a.cancel(); } catch (e) { /* ya terminó */ } });
    anims = [];
  }

  function ir(n, desdeHistorial) {
    if (n === actual || n < 0 || n >= paneles.length) return;
    var dir = n > actual ? 1 : -1;
    var viejo = paneles[actual];
    var nuevo = paneles[n];
    cancelar();
    var h0 = form.offsetHeight;
    actual = n;
    maximo = Math.max(maximo, n);
    if (n > 0 && !desdeHistorial && window.history && history.pushState) history.pushState({ paso: n }, '', '#paso-' + (n + 1));
    recap();
    pintarNav();

    function mostrar() {
      paneles.forEach(function (p) { p.classList.toggle('is-activo', p === nuevo); });
      if (!reducido) {
        var h1 = form.offsetHeight;
        if (Math.abs(h1 - h0) > 2) {
          form.style.overflow = 'hidden';
          var ah = form.animate([{ height: h0 + 'px' }, { height: h1 + 'px' }], { duration: 320, easing: EASE });
          ah.onfinish = ah.oncancel = function () { form.style.overflow = ''; };
          anims.push(ah);
        }
        anims.push(nuevo.animate([{ opacity: 0, transform: 'translateX(' + 24 * dir + 'px)' }, { opacity: 1, transform: 'none' }], { duration: 360, easing: EASE }));
      }
      var titulo = nuevo.querySelector('.ts-paso__titulo');
      if (titulo) titulo.focus({ preventScroll: true });
      if (raiz.getBoundingClientRect().top < 0) raiz.scrollIntoView({ block: 'start', behavior: reducido ? 'auto' : 'smooth' });
    }

    if (reducido) {
      mostrar();
    } else {
      var salida = viejo.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateX(' + -24 * dir + 'px)' }], { duration: 150, easing: 'ease-in', fill: 'forwards' });
      anims.push(salida);
      salida.onfinish = function () { salida.cancel(); mostrar(); };
    }
    if (n === 2 && $) $(document.body).trigger('update_checkout');
  }

  function siguiente() {
    if (validarPaso(actual)) ir(actual + 1);
  }

  raiz.addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b) return;
    if (b.hasAttribute('data-sig')) siguiente();
    else if (b.hasAttribute('data-atras')) ir(actual - 1);
    else if (b.hasAttribute('data-ir') && !b.disabled) ir(parseInt(b.getAttribute('data-ir'), 10));
    else if (b.classList.contains('ts-resumen__toggle')) b.setAttribute('aria-expanded', String(b.getAttribute('aria-expanded') !== 'true'));
  });

  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && actual < 2 && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
      e.preventDefault();
      siguiente();
    }
  });

  window.addEventListener('popstate', function (e) {
    ir(e.state && typeof e.state.paso === 'number' ? e.state.paso : 0, true);
  });

  // ---- WooCommerce: totales y errores del servidor ------------------------------
  if ($) {
    $(document.body).on('updated_checkout', function () {
      var total = raiz.querySelector('.order-total td');
      var rotulo = raiz.querySelector('[data-total]');
      if (total && rotulo) rotulo.textContent = total.textContent.trim();
      var fila = raiz.querySelector('.order-total');
      if (fila) {
        fila.classList.remove('ts-tick');
        void fila.offsetWidth;
        fila.classList.add('ts-tick');
      }
    });

    $(document.body).on('checkout_error', function () {
      var li = raiz.querySelector('.woocommerce-error li[data-id]');
      var destino = 2;
      if (li) {
        var el = form.querySelector('#' + li.getAttribute('data-id'));
        var panel = el && el.closest('.ts-paso');
        if (panel) {
          destino = paneles.indexOf(panel);
          if (destino !== actual) ir(destino);
          if (REGLAS[el.name]) marcar(el, li.textContent.trim());
          el.focus({ preventScroll: true });
        }
      }
    });
  }

  recap();
  pintarNav();
})();
