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
})();
