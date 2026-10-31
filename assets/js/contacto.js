/* Nosotros / Contáctenos. Sin dependencias.
   - Mapa diferido: [data-mapa] guarda la URL del mapa; el iframe de Google solo se crea al tocar [data-mapa-abrir].
     Sin JS, ese enlace abre Google Maps en otra pestaña.
   - Video diferido: [data-video] guarda la URL; el MP4 no se pide hasta tocar [data-video-play]. */
(function () {
  document.addEventListener('click', function (e) {
    var abrir = e.target.closest && e.target.closest('[data-mapa-abrir]');
    if (abrir) {
      var caja = abrir.closest('[data-mapa]');
      if (!caja || e.ctrlKey || e.metaKey || e.shiftKey) return;
      e.preventDefault();
      var f = document.createElement('iframe');
      f.src = caja.getAttribute('data-mapa');
      f.title = caja.getAttribute('data-mapa-titulo') || 'Mapa';
      f.loading = 'lazy';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      f.setAttribute('allowfullscreen', '');
      caja.innerHTML = '';
      caja.appendChild(f);
      f.setAttribute('tabindex', '0');
      f.focus();
      return;
    }
    var play = e.target.closest && e.target.closest('[data-video-play]');
    if (play) {
      var v = play.closest('[data-video]');
      if (!v) return;
      var video = document.createElement('video');
      video.src = v.getAttribute('data-video');
      video.controls = true;
      video.muted = true;
      video.playsInline = true;
      video.setAttribute('playsinline', '');
      v.innerHTML = '';
      v.appendChild(video);
      var p = video.play();
      if (p && p.catch) p.catch(function () {});
      video.focus();
    }
  });

  /* Formulario: envío por admin-ajax sin recargar. Respuesta { ok, wa_url, mensaje, errores? }.
     Sin JS el formulario hace POST a la misma página y el servidor pinta el botón de WhatsApp. */
  var form = document.querySelector('[data-contacto]');
  if (!form || !window.fetch || !window.FormData) return;
  var estado = form.querySelector('[data-contacto-estado]');
  var boton = form.querySelector('[type="submit"]');
  var ocupado = false;

  function limpiarErrores() {
    [].forEach.call(form.querySelectorAll('.ns-form__err'), function (s) { s.remove(); });
    [].forEach.call(form.querySelectorAll('[aria-invalid]'), function (c) { c.removeAttribute('aria-invalid'); c.removeAttribute('aria-describedby'); });
  }
  function marcarErrores(errores) {
    var primero = null;
    Object.keys(errores || {}).forEach(function (k) {
      var c = form.elements[k];
      if (!c) return;
      var s = document.createElement('span');
      s.className = 'ns-form__err';
      s.id = k + '-err';
      s.textContent = errores[k];
      c.insertAdjacentElement('afterend', s);
      c.setAttribute('aria-invalid', 'true');
      c.setAttribute('aria-describedby', s.id);
      if (!primero) primero = c;
    });
    if (primero) primero.focus();
  }
  function pintar(ok, texto, wa) {
    estado.innerHTML = '';
    var p = document.createElement('p');
    p.className = ok ? 'is-ok' : 'is-error';
    p.textContent = texto;
    estado.appendChild(p);
    if (wa) {
      var a = document.createElement('a');
      a.className = 'btn btn--accent ns-form__wa';
      a.href = wa;
      a.target = '_blank';
      a.rel = 'noopener';
      a.setAttribute('data-contacto-wa', '');
      a.textContent = 'Abrir WhatsApp';
      estado.appendChild(a);
    }
  }
  function enviar(reintento) {
    var datos = new FormData(form);
    datos.append('action', 'ts_contacto');
    return fetch(form.getAttribute('data-ajax'), { method: 'POST', body: datos, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        // Nonce vencido (página en caché o abierta mucho tiempo): se toma el nuevo y se reintenta una vez.
        if (res.s === 403 && res.j && res.j.nonce && !reintento) {
          form.elements.ts_nonce.value = res.j.nonce;
          return enviar(true);
        }
        return res;
      });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (ocupado) return;
    limpiarErrores();
    // Validación del navegador primero (sin mensajes nativos: se muestran los nuestros).
    var faltan = {};
    if (form.elements.ts_nombre.value.trim().length < 2) faltan.ts_nombre = 'Escribe tu nombre.';
    if (form.elements.ts_celular.value.replace(/\D/g, '').length < 7) faltan.ts_celular = 'Escribe un número de celular válido.';
    if (!form.elements.ts_sede.value) faltan.ts_sede = 'Elige la sede que te queda mejor.';
    if (!form.elements.ts_mensaje.value.trim()) faltan.ts_mensaje = 'Cuéntanos en qué te podemos ayudar.';
    if (Object.keys(faltan).length) { marcarErrores(faltan); pintar(false, 'Revisa los campos marcados.'); return; }

    ocupado = true;
    boton.disabled = true;
    boton.setAttribute('aria-busy', 'true');
    // La ventana se abre ya, dentro del gesto del usuario, para que el navegador no la bloquee; luego se le pone la URL.
    var ventana = null;
    try { ventana = window.open('', '_blank'); } catch (err) {}
    enviar(false).then(function (res) {
      var j = res.j || {};
      if (j.ok && j.wa_url) {
        pintar(true, j.mensaje + ' Si no se abrió, toca el botón.', j.wa_url);
        if (ventana && !ventana.closed) { try { ventana.opener = null; ventana.location.href = j.wa_url; } catch (err) {} }
        form.reset();
      } else {
        if (ventana && !ventana.closed) ventana.close();
        if (j.errores) marcarErrores(j.errores);
        pintar(!!j.ok, j.mensaje || 'No pudimos enviar tu mensaje. Inténtalo de nuevo.');
      }
    }).catch(function () {
      if (ventana && !ventana.closed) ventana.close();
      pintar(false, 'No pudimos enviar tu mensaje. Revisa tu conexión e inténtalo de nuevo.');
    }).then(function () {
      ocupado = false;
      boton.disabled = false;
      boton.removeAttribute('aria-busy');
    });
  });
})();
