/**
 * Reproductor de video diferido (template-parts/video.php). Nada del video se descarga antes del toque.
 * - [data-src]:   URL directa del MP4 (ejemplo o URL completa).
 * - [data-clave]: clave del bucket privado de R2; al tocar se pide { url } prefirmada (1 hora) a admin-ajax ts_video.
 *   Si la URL caduca con el video abierto, se pide otra una vez y se sigue donde iba.
 * Si algo falla: mensaje y enlace a WhatsApp. Sin audio (muted), playsinline. Sin dependencias.
 */
(function () {
  'use strict';

  function pedirUrl(fig) {
    var ajax = fig.getAttribute('data-ajax'), clave = fig.getAttribute('data-clave');
    if (!clave) return Promise.resolve(fig.getAttribute('data-src'));
    if (!window.fetch || !ajax) return Promise.reject(new Error('sin-fetch'));
    return fetch(ajax + '?action=ts_video&clave=' + encodeURIComponent(clave), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { if (!r.ok || !d || !d.url) throw new Error((d && d.error) || 'http'); return d.url; }); });
  }

  function fallo(fig, btn) {
    fig.classList.remove('is-cargando');
    fig.classList.add('is-error');
    fig.removeAttribute('aria-busy');
    if (btn) btn.hidden = true;
    var est = fig.querySelector('[data-ts-video-estado]');
    if (!est) return;
    est.textContent = 'No pudimos cargar el video en este momento. ';
    var wa = fig.getAttribute('data-wa-url');
    if (wa) {
      var a = document.createElement('a');
      a.href = wa;
      a.target = '_blank';
      a.rel = 'noopener';
      a.className = 'ts-video__wa';
      a.setAttribute('data-wa-abrir', fig.getAttribute('data-wa-contexto') || 'video');
      a.textContent = 'Pídelo por WhatsApp';
      est.appendChild(a);
    }
  }

  function videoDe(fig) {
    var v = fig.querySelector('video');
    if (v) return v;
    v = document.createElement('video');
    v.className = 'ts-video__v';
    v.setAttribute('playsinline', '');
    v.setAttribute('aria-label', fig.getAttribute('data-etiqueta') || 'Video');
    var img = fig.querySelector('.ts-video__poster');
    if (img) { v.poster = img.currentSrc || img.src; img.replaceWith(v); } else fig.insertBefore(v, fig.firstChild);
    return v;
  }

  function iniciar(fig, btn) {
    if (fig.classList.contains('is-cargando') || fig.classList.contains('is-activo')) return;
    fig.classList.add('is-cargando');
    fig.setAttribute('aria-busy', 'true');
    pedirUrl(fig).then(function (url) {
      if (!url) throw new Error('sin-url');
      var v = videoDe(fig), reintento = false;
      v.muted = true;
      v.playsInline = true;
      v.controls = true;
      v.preload = 'auto';
      v.addEventListener('error', function () {
        // URL vencida (más de 1 hora abierta) o red: una vez se pide otra y se sigue en el mismo punto.
        if (fig.getAttribute('data-clave') && !reintento) {
          reintento = true;
          var t = v.currentTime || 0;
          pedirUrl(fig).then(function (u) { v.src = u; v.currentTime = t; var p2 = v.play(); if (p2 && p2.catch) p2.catch(function () {}); }).catch(function () { fallo(fig, btn); });
          return;
        }
        fallo(fig, btn);
      });
      v.src = url;
      fig.classList.remove('is-cargando');
      fig.classList.add('is-activo');
      fig.removeAttribute('aria-busy');
      btn.hidden = true;
      var p = v.play();
      if (p && p.catch) p.catch(function () {});
      try { v.focus({ preventScroll: true }); } catch (e) { v.focus(); }
    }).catch(function () { fallo(fig, btn); });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-ts-video-play]');
    if (!btn) return;
    var fig = btn.closest('[data-ts-video]');
    if (fig) iniciar(fig, btn);
  });
})();
