/* Widget Mi cuenta: tarjeta cargada al abrir (admin-ajax ts_cuenta_panel); formularios por AJAX sin recargar. Sin JS el botón es un enlace. */
(function () {
  'use strict';
  var btn = document.querySelector('[data-cuenta-abrir]'), caja = document.getElementById('ts-cuenta'), cfg = window.tsCuenta || {};
  if (!btn || !caja || !cfg.ajax || !window.fetch) return;
  var reducido = matchMedia('(prefers-reduced-motion: reduce)'), movil = matchMedia('(max-width: 40rem)');
  var hoverOk = matchMedia('(hover: hover) and (pointer: fine)'), porHover = false, panel = false, velo = null, cierre = 0;
  var abierto = false, cargado = false, cargando = null, tmp = 0, html = document.documentElement;

  function tarjeta() { return caja.querySelector('[data-cuenta-tarjeta]'); }
  function poner(h) { caja.innerHTML = h; cargado = !!tarjeta(); }
  function texto(t) { var p = document.createElement('p'); p.className = 'ts-cuenta__carga'; p.textContent = t; caja.replaceChildren(p); return p; }
  function aviso(p, t, tipo) { if (p) { p.textContent = t; p.className = 'ts-cuenta__aviso' + (tipo ? ' is-' + tipo : ''); } }
  function foco(el) { el.focus({ preventScroll: true }); }

  function cargar() {
    caja.setAttribute('aria-busy', 'true');
    return cargando || (cargando = fetch(cfg.ajax + '?action=ts_cuenta_panel', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { if (!r.ok) throw 0; return r.text(); })
      .then(poner, function () {
        var a = document.createElement('a');
        a.href = btn.href; a.textContent = 'Ir a Mi cuenta';
        texto('No pudimos cargar tu cuenta. ').appendChild(a);
      })
      .then(function () { cargando = null; caja.removeAttribute('aria-busy'); }));
  }

  function ubicar() {
    var r = btn.getBoundingClientRect();
    caja.style.setProperty('--ts-cuenta-top', Math.round(r.bottom + 8) + 'px');
    caja.style.setProperty('--ts-cuenta-right', Math.max(8, Math.round(html.clientWidth - r.right)) + 'px');
    caja.setAttribute('aria-modal', String(movil.matches));
  }

  function enfocar() {
    if (!abierto || porHover) return;
    var c = caja.querySelector('[data-cara]:not([hidden])');
    foco((c && (c.querySelector('input:not([type=checkbox]):not([tabindex="-1"])') || c.querySelector('a[href]'))) || caja);
  }

  function abrir(hover) {
    abierto = true;
    porHover = hover === true;
    clearTimeout(tmp);
    clearTimeout(cierre);
    ubicar();
    caja.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    html.classList.add('ts-cuenta-abierta');
    void caja.offsetWidth;
    caja.classList.add('is-abierto');
    if (cargado) return enfocar();
    texto('Cargando…');
    if (!porHover) foco(caja);
    cargar().then(enfocar);
  }

  function cerrar(volver) {
    if (!abierto) return;
    abierto = false;
    caja.classList.remove('is-abierto');
    btn.setAttribute('aria-expanded', 'false');
    html.classList.remove('ts-cuenta-abierta');
    var eraPanel = panel;
    if (panel) { velo.classList.remove('is-abierto'); html.classList.remove('ts-cuenta-panel'); }
    tmp = setTimeout(function () {
      caja.hidden = true;
      if (eraPanel) { panel = false; caja.classList.remove('is-panel'); if (tarjeta()) caras('entrar'); }
    }, reducido.matches ? 0 : (eraPanel ? 480 : 200));
    porHover = false;
    if (volver !== false) foco(btn);
  }

  function caras(cual) {
    tarjeta().querySelectorAll('[data-cara]').forEach(function (s) { s.hidden = s.getAttribute('data-cara') !== cual; });
  }

  // Crear cuenta se abre en un panel lateral, como el mini carrito.
  function aPanel() {
    if (!velo) {
      velo = document.createElement('div');
      velo.className = 'ts-cuenta-velo';
      velo.addEventListener('click', function () { cerrar(); });
      document.body.appendChild(velo);
    }
    panel = true;
    porHover = false;
    caja.classList.remove('is-abierto');
    caja.classList.add('is-panel');
    caja.setAttribute('aria-modal', 'true');
    html.classList.add('ts-cuenta-panel');
    void caja.offsetWidth;
    caja.classList.add('is-abierto');
    velo.classList.add('is-abierto');
  }
  function sinPanel() {
    panel = false;
    velo.classList.remove('is-abierto');
    html.classList.remove('ts-cuenta-panel');
    caja.classList.remove('is-panel');
    ubicar();
  }

  // Cambia de cara (ingresar, crear, recuperar) con una transición breve de altura.
  function mostrar(cual) {
    var t = tarjeta(), h0 = t.offsetHeight;
    if (cual === 'crear' && !panel) { aPanel(); caras(cual); t.querySelectorAll('[data-cuenta-aviso]').forEach(function (p) { aviso(p, ''); }); return enfocar(); }
    if (cual !== 'crear' && panel) sinPanel();
    t.querySelectorAll('[data-cara]').forEach(function (s) { s.hidden = s.getAttribute('data-cara') !== cual; });
    t.querySelectorAll('[data-cuenta-aviso]').forEach(function (p) { aviso(p, ''); });
    if (!reducido.matches && !movil.matches) {
      var h1 = t.offsetHeight;
      t.style.height = h0 + 'px';
      void t.offsetHeight;
      t.classList.add('is-midiendo');
      t.style.height = h1 + 'px';
      setTimeout(function () { t.style.height = ''; t.classList.remove('is-midiendo'); }, 260);
    }
    enfocar();
  }

  function enviar(form) {
    var boton = form.querySelector('[type=submit]'), p = form.querySelector('[data-cuenta-aviso]'), d = new FormData(form);
    d.set('action', 'ts_cuenta_' + form.getAttribute('data-cuenta-form'));
    d.set('nonce', tarjeta().getAttribute('data-nonce') || '');
    function listo() { boton.disabled = false; boton.classList.remove('is-cargando'); }
    boton.disabled = true;
    boton.classList.add('is-cargando');
    aviso(p, '');
    fetch(cfg.ajax, { method: 'POST', body: d, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j || {} }; }); })
      .then(function (r) {
        var j = r.j, cl = document.body.classList, tipo = j.ok ? 'ok' : 'error';
        if (!j.html) { aviso(p, j.mensaje || 'Algo falló. Inténtalo de nuevo.', tipo); return listo(); }
        // Con html: sesión iniciada, o nonce vencido (403, tarjeta fresca con nonce nuevo).
        // Mi cuenta y el pago pintan la sesión en la página: ahí se recarga.
        if (j.ok && (cl.contains('woocommerce-account') || cl.contains('woocommerce-checkout'))) return location.reload();
        poner(j.html);
        enfocar();
        var nuevo = caja.querySelector('[data-cuenta-aviso]');
        setTimeout(function () { aviso(nuevo, j.mensaje || '', tipo); }, 60);
      })
      .catch(function () { aviso(p, 'Sin conexión. Inténtalo de nuevo.', 'error'); listo(); });
  }

  btn.addEventListener('click', function (e) {
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0) return;
    e.preventDefault();
    if (hoverOk.matches && e.detail > 0) { if (!abierto) abrir(); else porHover = false; return; }
    if (abierto) cerrar(); else abrir();
  });

  // Con puntero fino, el desplegable también se abre al pasar por encima y se cierra al salir (con una pequeña espera).
  function salir() {
    clearTimeout(cierre);
    cierre = setTimeout(function () {
      if (!abierto || panel) return;
      var a = document.activeElement;
      if (caja.contains(a) && /^(INPUT|BUTTON)$/.test(a.tagName) && !porHover) return;
      cerrar(false);
    }, 280);
  }
  function entrar() {
    if (!hoverOk.matches || panel) return;
    clearTimeout(cierre);
    if (!abierto) abrir(true);
  }
  caja.addEventListener('focusin', function () { porHover = false; });
  btn.addEventListener('mouseenter', entrar);
  caja.addEventListener('mouseenter', function () { clearTimeout(cierre); });
  btn.addEventListener('mouseleave', salir);
  caja.addEventListener('mouseleave', salir);

  caja.addEventListener('click', function (e) {
    var ir = e.target.closest('[data-cuenta-ir]');
    if (ir) { e.preventDefault(); porHover = false; mostrar(ir.getAttribute('data-cuenta-ir')); }
    else if (e.target.closest('[data-cuenta-cerrar]')) cerrar();
  });

  caja.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-cuenta-form]');
    if (!form) return;
    e.preventDefault();
    var vacio = [].filter.call(form.querySelectorAll('[required]'), function (i) { return !i.value.trim(); })[0];
    if (!vacio) return enviar(form);
    aviso(form.querySelector('[data-cuenta-aviso]'), 'Completa los campos para continuar.', 'error');
    vacio.focus();
  });

  // Clic fuera cierra; el foco vuelve al botón salvo que se pulsara otro control.
  document.addEventListener('click', function (e) {
    if (abierto && !caja.contains(e.target) && !btn.contains(e.target)) cerrar(!e.target.closest('a, button, input, select, textarea, [tabindex]'));
  });

  // Escape cierra; Tab queda atrapado en la tarjeta.
  document.addEventListener('keydown', function (e) {
    if (!abierto) return;
    if (e.key === 'Escape') { e.preventDefault(); return cerrar(); }
    if (e.key !== 'Tab' || porHover) return;
    var f = [].filter.call(caja.querySelectorAll('a[href], button:not([disabled]), input:not([tabindex="-1"])'), function (el) { return el.offsetParent; });
    var a = document.activeElement, ini = f[0], fin = f[f.length - 1], ir = null;
    if (!f.length) ir = caja;
    else if (!caja.contains(a)) ir = ini;
    else if (e.shiftKey && (a === ini || a === caja)) ir = fin;
    else if (!e.shiftKey && a === fin) ir = ini;
    if (ir) { e.preventDefault(); foco(ir); }
  });

  addEventListener('resize', function () { if (abierto) ubicar(); });
})();
