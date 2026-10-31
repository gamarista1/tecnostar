/**
 * Tienda: aplica filtros, búsqueda y paginación sin recargar.
 * Pide la misma URL que tendría el enlace (HTML normal del servidor), extrae los fragmentos
 * y actualiza la URL con history.pushState. Sin JavaScript, enlaces y formulario funcionan solos.
 */
(function () {
  'use strict';
  var raiz = document.querySelector('[data-ts-tienda]');
  if (!raiz || !window.fetch || !window.DOMParser || !window.history || !history.pushState) return;

  var estado = document.querySelector('[data-ts-estado]');
  var control = null;

  function q(sel, ctx) { return (ctx || document).querySelector(sel); }

  function anunciar(doc) {
    if (!estado) return;
    var vacio = q('[data-ts-vacio] .ts-vacio__t', doc);
    var cuenta = q('.woocommerce-result-count', doc);
    var texto = vacio ? vacio.textContent : (cuenta ? cuenta.textContent : '');
    estado.textContent = '';
    // Pausa breve para que el lector de pantalla lea el texto aunque se repita.
    setTimeout(function () { estado.textContent = texto.replace(/\s+/g, ' ').trim(); }, 60);
  }

  function reemplazar(sel, doc) {
    var nuevo = q(sel, doc), viejo = q(sel, raiz);
    if (nuevo && viejo) viejo.innerHTML = nuevo.innerHTML;
  }

  function cargar(url, opciones) {
    opciones = opciones || {};
    if (control) control.abort();
    control = window.AbortController ? new AbortController() : null;
    var res = q('[data-ts-resultados]', raiz);
    res.setAttribute('aria-busy', 'true');
    raiz.classList.add('is-cargando');
    return fetch(url, { credentials: 'same-origin', signal: control ? control.signal : undefined, headers: { 'X-Requested-With': 'tienda' } })
      .then(function (r) {
        if (!r.ok && r.status !== 404) throw new Error('HTTP ' + r.status);
        return r.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        if (!q('[data-ts-tienda]', doc)) throw new Error('sin fragmento');
        reemplazar('[data-ts-grupos]', doc);
        reemplazar('[data-ts-resumen]', doc);
        reemplazar('[data-ts-resultados]', doc);
        var campo = q('#ts-buscar-q', raiz), campoNuevo = q('#ts-buscar-q', doc);
        if (campo && campoNuevo && document.activeElement !== campo) campo.value = campoNuevo.value;
        var ocultos = q('[data-ts-buscar]', raiz), ocultosNuevos = q('[data-ts-buscar]', doc);
        if (ocultos && ocultosNuevos) {
          ocultos.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.remove(); });
          ocultosNuevos.querySelectorAll('input[type="hidden"]').forEach(function (i) { ocultos.insertBefore(document.importNode(i, true), q('button', ocultos)); });
        }
        var h1 = q('.page-title'), h1Nuevo = q('.page-title', doc);
        if (h1 && h1Nuevo) h1.textContent = h1Nuevo.textContent;
        if (doc.title) document.title = doc.title;
        if (opciones.push !== false) history.pushState({ tsTienda: true }, '', url);
        anunciar(doc);
        // Foco: el mismo filtro en la lista nueva; si no existe, los resultados.
        if (opciones.clave) {
          var mismo = raiz.querySelector('[data-ts-clave="' + opciones.clave.replace(/"/g, '') + '"]');
          if (mismo && mismo.offsetParent !== null) { mismo.focus(); }
          else { res.focus({ preventScroll: true }); }
        } else if (opciones.foco === 'resultados') {
          res.focus({ preventScroll: true });
          var arriba = raiz.getBoundingClientRect().top;
          if (arriba < 0) raiz.scrollIntoView({ block: 'start' });
        }
      })
      .catch(function (e) {
        if (e && e.name === 'AbortError') return;
        window.location.href = url;
      })
      .then(function () {
        res = q('[data-ts-resultados]', raiz);
        if (res) res.removeAttribute('aria-busy');
        raiz.classList.remove('is-cargando');
      });
  }

  function mismoOrigen(a) {
    return a.origin === window.location.origin;
  }

  raiz.addEventListener('click', function (ev) {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    var a = ev.target.closest('a');
    if (!a || !raiz.contains(a) || !mismoOrigen(a)) return;
    if (a.hasAttribute('data-ts-filtro')) {
      ev.preventDefault();
      cargar(a.href, { clave: a.getAttribute('data-ts-clave') === 'limpiar' ? '' : a.getAttribute('data-ts-clave'), foco: 'resultados' });
    } else if (a.closest('.woocommerce-pagination')) {
      ev.preventDefault();
      cargar(a.href, { foco: 'resultados' });
    }
  });

  var form = q('[data-ts-buscar]', raiz);
  if (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var datos = new URLSearchParams(new FormData(form));
      cargar(form.action + (form.action.indexOf('?') === -1 ? '?' : '&') + datos.toString());
    });
  }

  history.replaceState({ tsTienda: true }, '', window.location.href);
  window.addEventListener('popstate', function (ev) {
    if (ev.state && ev.state.tsTienda) cargar(window.location.href, { push: false });
  });
})();
