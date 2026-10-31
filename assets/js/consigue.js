/**
 * Tarjeta "¿No encuentras lo que buscas? Si no lo tenemos, te lo conseguimos." (tienda y categorías).
 * Aparece a los 15 s o al 50 % de scroll, lo que ocurra primero. Se cierra con la X y no vuelve en 7 días;
 * tampoco vuelve después de abrir el menú de WhatsApp (evento ts:wa o menú abierto). Nunca toma el foco.
 */
(function () {
  'use strict';
  var card = document.querySelector('[data-ts-consigue]');
  if (!card) return;

  var KEY = 'ts_consigue_oculta';
  var SIETE_DIAS = 7 * 24 * 60 * 60 * 1000;
  var ESPERA = 15000;
  var hecho = false, timer = 0, rafId = 0;

  function oculta() {
    try {
      var t = parseInt(localStorage.getItem(KEY) || '0', 10);
      return t > 0 && Date.now() - t < SIETE_DIAS;
    } catch (e) { return false; }
  }
  function recordar() { try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {} }

  function soltar() {
    hecho = true;
    clearTimeout(timer);
    window.removeEventListener('scroll', alScroll);
  }

  function mostrar() {
    if (hecho) return;
    soltar();
    if (oculta()) return;
    card.hidden = false;
    // Un cuadro después, para que la transición de entrada arranque desde el estado inicial.
    requestAnimationFrame(function () { requestAnimationFrame(function () { card.classList.add('is-visible'); }); });
  }

  function cerrar(persistir) {
    soltar();
    if (persistir) recordar();
    if (card.hidden) return;
    var tenia = card.contains(document.activeElement);
    card.classList.remove('is-visible');
    card.hidden = true;
    if (tenia) {
      var destino = document.querySelector('[data-ts-resultados]') || document.querySelector('main') || document.body;
      if (destino.focus) destino.focus({ preventScroll: true });
    }
  }

  function alScroll() {
    if (rafId) return;
    rafId = requestAnimationFrame(function () {
      rafId = 0;
      var d = document.documentElement;
      var max = d.scrollHeight - window.innerHeight;
      if (max > 0 && (window.scrollY || d.scrollTop) / max >= 0.5) mostrar();
    });
  }

  if (oculta()) { soltar(); return; }

  timer = setTimeout(mostrar, ESPERA);
  window.addEventListener('scroll', alScroll, { passive: true });

  card.querySelector('[data-ts-consigue-cerrar]').addEventListener('click', function () { cerrar(true); });
  card.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(true); });

  // Su botón abre el menú de WhatsApp (whatsapp.js escucha [data-wa-abrir]); la tarjeta ya cumplió.
  card.querySelector('[data-wa-abrir]').addEventListener('click', function () {
    recordar();
    soltar();
    card.classList.remove('is-visible');
    card.hidden = true;
  });

  // Si el cliente ya abrió el menú de WhatsApp o eligió un canal, no se vuelve a mostrar.
  window.addEventListener('ts:wa', function () { cerrar(true); });
  var wa = document.querySelector('[data-wa]');
  if (wa && window.MutationObserver) {
    new MutationObserver(function () {
      if (wa.classList.contains('is-open')) cerrar(true);
    }).observe(wa, { attributes: true, attributeFilter: ['class'] });
  }
})();
