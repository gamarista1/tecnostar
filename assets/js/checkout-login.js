/*
  Acceso dentro del pago: abre la tarjeta, voltea entre "Acceder" y "Recuperar contraseña" y envía ambos
  formularios sin salir de la página. Sin JavaScript funcionan con los envíos normales de WooCommerce.
*/
(function () {
  'use strict';
  var raiz = document.querySelector('[data-login]');
  if (!raiz) return;

  var toggle = raiz.querySelector('[data-login-toggle]');
  var cuerpo = raiz.querySelector('.ts-login__cuerpo');
  var tarjeta = raiz.querySelector('[data-login-card]');
  var caras = { entrar: raiz.querySelector('[data-login-face="entrar"]'), olvide: raiz.querySelector('[data-login-face="olvide"]') };
  var sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');
  var actual = 'entrar';

  function medir(sinAnimar) {
    if (sinAnimar) tarjeta.style.transition = 'none';
    tarjeta.style.height = caras[actual].offsetHeight + 'px';
    if (sinAnimar) {
      void tarjeta.offsetHeight;
      tarjeta.style.transition = '';
    }
  }

  function mostrar(cual, interactivo) {
    actual = cual;
    var atras = cual === 'olvide';
    tarjeta.classList.toggle('is-flipped', atras);
    caras.entrar.toggleAttribute('inert', atras);
    caras.entrar.setAttribute('aria-hidden', String(atras));
    caras.olvide.toggleAttribute('inert', !atras);
    caras.olvide.setAttribute('aria-hidden', String(!atras));
    medir(!interactivo);
    if (!interactivo) return;
    setTimeout(function () {
      var campo = caras[cual].querySelector('input');
      if (campo) campo.focus({ preventScroll: true });
    }, sinMovimiento.matches ? 0 : 280);
  }

  function abrir(abierto) {
    toggle.setAttribute('aria-expanded', String(abierto));
    cuerpo.classList.toggle('is-abierto', abierto);
    cuerpo.toggleAttribute('inert', !abierto);
    if (abierto) {
      medir(true);
      setTimeout(function () {
        var campo = caras[actual].querySelector('input');
        if (campo) campo.focus({ preventScroll: true });
      }, sinMovimiento.matches ? 0 : 320);
    }
  }

  mostrar('entrar', false);
  abrir(false);

  toggle.addEventListener('click', function () { abrir(toggle.getAttribute('aria-expanded') !== 'true'); });

  raiz.addEventListener('click', function (e) {
    var b = e.target.closest('[data-login-voltear]');
    if (!b) return;
    e.preventDefault();
    mostrar(b.getAttribute('data-login-voltear'), true);
  });

  if ('ResizeObserver' in window) {
    var obs = new ResizeObserver(function () { medir(true); });
    obs.observe(caras.entrar);
    obs.observe(caras.olvide);
  }

  // ---- Envío sin recargar -------------------------------------------------------
  function aviso(form, texto, tipo) {
    var p = form.querySelector('.ts-login__aviso');
    p.textContent = texto;
    p.hidden = !texto;
    p.classList.toggle('is-ok', tipo === 'ok');
  }

  function errorDe(doc) {
    var li = doc.querySelector('.woocommerce-error li, .woocommerce-error');
    return li ? li.textContent.replace(/\s+/g, ' ').trim() : '';
  }

  raiz.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-login-form]');
    if (!form) return;
    var tipo = form.getAttribute('data-login-form');
    var campo = form.querySelector('input[type="text"]');
    var clave = form.querySelector('input[type="password"]');
    if (!campo.value.trim() || (clave && !clave.value)) {
      e.preventDefault();
      aviso(form, tipo === 'entrar' ? 'Escribe tu usuario y tu contraseña.' : 'Escribe tu usuario o correo.', 'error');
      (campo.value.trim() ? clave : campo).focus();
      return;
    }
    e.preventDefault();
    var boton = form.querySelector('button[type="submit"]');
    boton.classList.add('is-cargando');
    boton.disabled = true;
    aviso(form, '', '');
    var datos = new FormData(form);
    if (tipo === 'entrar') datos.set('login', 'Acceder');

    fetch(form.action, { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(function (r) { return r.text().then(function (t) { return { url: r.url, texto: t }; }); })
      .then(function (r) {
        var doc = new DOMParser().parseFromString(r.texto, 'text/html');
        if (tipo === 'entrar') {
          if (doc.body.classList.contains('logged-in')) { window.location.reload(); return; }
          aviso(form, errorDe(doc) || 'No pudimos acceder. Revisa tus datos e inténtalo de nuevo.', 'error');
        } else if (/reset-link-sent/.test(r.url) || doc.querySelector('.woocommerce-message')) {
          aviso(form, 'Listo. Si el dato existe, te enviamos un enlace al correo. Revisa también el spam.', 'ok');
        } else {
          aviso(form, errorDe(doc) || 'No pudimos enviar el enlace. Inténtalo de nuevo.', 'error');
        }
      })
      .catch(function () { aviso(form, 'Sin conexión. Inténtalo de nuevo.', 'error'); })
      .then(function () {
        boton.classList.remove('is-cargando');
        boton.disabled = false;
      });
  });
})();
