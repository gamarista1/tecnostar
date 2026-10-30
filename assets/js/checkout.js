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
    billing_phone: function (v) { return /^3\d{9}$/.test(v.replace(/\D/g, '')) || 'Escribe tu celular de 10 dígitos, empieza por 3 (ej. 300 123 4567)'; },
    billing_state: function (v) { return !!v || 'Elige tu departamento'; },
    billing_city: function (v) { return v.trim().length >= 2 || 'Escribe tu ciudad'; },
    billing_address_1: function (v) { return v.trim().length >= 5 || 'Escribe tu dirección completa'; }
  };

  // Celular colombiano: 10 dígitos, "300 123 4567". Acepta pegar con +57 o 57 delante.
  function mascaraCelular(v) {
    var d = v.replace(/\D/g, '');
    if (d.length > 10 && d.indexOf('57') === 0) d = d.slice(2);
    d = d.slice(0, 10);
    return [d.slice(0, 3), d.slice(3, 6), d.slice(6, 10)].filter(Boolean).join(' ');
  }

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
    if (t.name === 'billing_phone') t.value = mascaraCelular(t.value);
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
    actualizarSig();
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

  // ---- Botón contextual: "Siguiente" pasa al campo contiguo; "Continuar" cambia de paso ----
  function camposPaso(i) {
    return [].slice.call(paneles[i].querySelectorAll('input, select, textarea')).filter(function (el) {
      return ['hidden', 'checkbox', 'radio', 'submit', 'button'].indexOf(el.type) < 0 && !el.disabled && !el.readOnly && el.offsetParent !== null;
    });
  }

  function indiceActivo(campos) {
    var a = document.activeElement;
    if (!a || a === document.body) return -1;
    for (var k = 0; k < campos.length; k++) {
      var c = campos[k];
      if (c === a || (c.nextElementSibling && c.nextElementSibling.contains(a))) return k;
    }
    return -1;
  }

  function pasoCompleto(i) {
    return camposRegla(paneles[i]).every(function (el) { return REGLAS[el.name](el.value || '') === true; });
  }

  function estadoSig() {
    var campos = camposPaso(actual);
    var k = indiceActivo(campos);
    var proximo = k >= 0 && k < campos.length - 1 && !pasoCompleto(actual) ? campos[k + 1] : null;
    return { actual: k >= 0 ? campos[k] : null, proximo: proximo };
  }

  function actualizarSig() {
    var btn = paneles[actual] && paneles[actual].querySelector('[data-sig]');
    if (!btn) return;
    if (!btn.hasAttribute('data-texto')) btn.setAttribute('data-texto', btn.textContent);
    var texto = estadoSig().proximo ? 'Siguiente' : btn.getAttribute('data-texto');
    if (btn.textContent !== texto) btn.textContent = texto;
  }

  function enfocar(el) {
    if ($ && el.tagName === 'SELECT' && $.fn.select2 && $(el).data('select2')) {
      $(el).select2('open');
      return;
    }
    el.focus({ preventScroll: true });
    el.scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
  }

  function siguiente() {
    var e = estadoSig();
    if (e.proximo) {
      if (e.actual && REGLAS[e.actual.name] && !validar(e.actual)) return;
      enfocar(e.proximo);
      return;
    }
    if (validarPaso(actual)) ir(actual + 1);
  }

  // Pulsar el botón no le quita el foco al campo: el teclado del móvil se queda abierto.
  raiz.addEventListener('mousedown', function (e) {
    if (e.target.closest('[data-sig]')) e.preventDefault();
  });
  raiz.addEventListener('focusin', actualizarSig);
  raiz.addEventListener('focusout', function () { setTimeout(actualizarSig, 0); });
  form.addEventListener('input', actualizarSig);
  form.addEventListener('change', actualizarSig);

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
      var envio = [].slice.call(raiz.querySelectorAll('.woocommerce-error li')).filter(function (l) { return /m[eé]todo de env[ií]o|env[ií]o/i.test(l.textContent) && !l.hasAttribute('data-id'); })[0];
      if (envio) {
        if (actual !== 1) ir(1);
        setTimeout(function () {
          var caja = paneles[1].querySelector('.ts-envio');
          if (!caja) return;
          var radio = caja.querySelector('input.shipping_method[type="radio"]');
          var previo = caja.parentNode.querySelector('.ts-error--envio');
          if (previo) previo.remove();
          var aviso = document.createElement('p');
          aviso.className = 'ts-error ts-error--envio';
          aviso.setAttribute('role', 'alert');
          aviso.textContent = radio ? 'Elige cómo quieres recibir tu pedido.' : 'No hay envío disponible para esa dirección. Revísala o escríbenos por WhatsApp.';
          caja.parentNode.insertBefore(aviso, caja.nextSibling);
          caja.classList.remove('ts-llamar');
          void caja.offsetWidth;
          caja.classList.add('ts-llamar');
          caja.scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
          if (radio) radio.focus({ preventScroll: true });
        }, reducido ? 0 : 380);
        return;
      }
      // Cualquier otro error: el cursor va al primer campo que falta, en el paso donde está.
      var el = null;
      [].slice.call(raiz.querySelectorAll('.woocommerce-error li[data-id]')).some(function (li) {
        var id = li.getAttribute('data-id');
        var c = form.querySelector('#' + id) || form.querySelector('#' + id.replace(/_field$/, '')) || form.querySelector('[name="' + id.replace(/_field$/, '') + '"]');
        if (!c) return false;
        el = c;
        if (REGLAS[c.name]) marcar(c, li.textContent.trim());
        return true;
      });
      if (!el) {
        for (var i = 0; i < 2 && !el; i++) {
          var falla = camposRegla(paneles[i]).filter(function (c) { return REGLAS[c.name](c.value || '') !== true; })[0];
          if (falla) { el = falla; marcar(falla, REGLAS[falla.name](falla.value || '')); }
        }
      }
      if (!el) return;
      var panel = el.closest('.ts-paso');
      var destino = panel ? paneles.indexOf(panel) : actual;
      if (destino !== actual) ir(destino);
      setTimeout(function () {
        if (el.tagName === 'SELECT' && $ && $.fn.select2 && $(el).data('select2')) {
          el.closest('.form-row').scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
          $(el).select2('open');
          return;
        }
        el.focus({ preventScroll: true });
        el.scrollIntoView({ block: 'center', behavior: reducido ? 'auto' : 'smooth' });
      }, destino !== actual || !reducido ? 380 : 0);
    });
  }

  recap();
  pintarNav();
  // ---- Mapa de la tienda en una ventana ----
  var modal = document.querySelector('[data-mapa-modal]');
  if (modal && modal.showModal) {
    var mapas = {};
    try { mapas = JSON.parse(modal.getAttribute('data-mapas') || '{}'); } catch (e) { mapas = {}; }
    var marco = modal.querySelector('iframe');
    var cerrando = false;
    var cerrar = function () {
      if (cerrando || !modal.open) return;
      cerrando = true;
      modal.classList.add('is-cerrando');
      setTimeout(function () { modal.close(); modal.classList.remove('is-cerrando'); cerrando = false; marco.removeAttribute('src'); }, reducido ? 0 : 220);
    };
    raiz.addEventListener('click', function (e) {
      var a = e.target.closest('[data-mapa]');
      if (!a) return;
      e.preventDefault();
      e.stopPropagation();
      var m = mapas[a.getAttribute('data-mapa')];
      if (!m) return;
      modal.querySelector('h2').textContent = m.nombre;
      modal.querySelector('.ts-mapa__abrir').href = m.abrir;
      marco.src = m.src;
      modal.showModal();
    });
    modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-mapa-cerrar]')) cerrar(); });
    modal.addEventListener('cancel', function (e) { e.preventDefault(); cerrar(); });
  }

})();
