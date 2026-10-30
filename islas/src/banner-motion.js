import gsap from 'gsap';

const DURACION = 5.6; // segundos por escena
const SALIDA = 0.55;

// Chispas de la escena "despacho": hacia dónde salta cada una.
const CHISPAS = [
  [-64, -46],
  [58, -58],
  [10, -78],
];

export function iniciar(raiz) {
  const escenas = [...raiz.querySelectorAll('.bm__scene')];
  const pasos = [...raiz.querySelectorAll('.bm__paso')];
  const rellenos = pasos.map((p) => p.querySelector('.bm__relleno'));
  const fondos = [...raiz.querySelectorAll('.bm__fondo')];
  const pausa = raiz.querySelector('.bm__pausa');
  const nav = raiz.querySelector('.bm__nav');
  const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  escenas.forEach((s) => (s.hidden = false));
  nav.hidden = false;

  // Los títulos se parten en palabras para animarlas una a una (el texto sigue siendo el mismo).
  escenas.forEach((s) => {
    const titulo = s.querySelector('.bm__title');
    const palabras = titulo.textContent.trim().split(/\s+/);
    titulo.textContent = '';
    palabras.forEach((palabra, k) => {
      const caja = document.createElement('span');
      caja.className = 'w';
      const interior = document.createElement('span');
      interior.className = 'w__i';
      interior.textContent = palabra;
      caja.appendChild(interior);
      titulo.appendChild(caja);
      if (k < palabras.length - 1) titulo.appendChild(document.createTextNode(' '));
    });
  });

  let actual = 0;
  let linea = null;
  let pausaUsuario = false;
  let pausaTemporal = false;

  function marcar(i) {
    actual = i;
    escenas.forEach((s, j) => {
      s.classList.toggle('is-active', j === i);
      s.setAttribute('aria-hidden', String(j !== i));
    });
    pasos.forEach((p, j) => {
      if (j === i) p.setAttribute('aria-current', 'true');
      else p.removeAttribute('aria-current');
    });
    fondos.forEach((f, j) => f.classList.toggle('is-on', j === i));
  }

  // Sin movimiento: solo cambia la escena visible, sin reproducción automática.
  if (reducido) {
    pausa.hidden = true;
    marcar(0);
    rellenos.forEach((r, j) => gsap.set(r, { scaleX: j === 0 ? 1 : 0 }));
    pasos.forEach((p, j) =>
      p.addEventListener('click', () => {
        marcar(j);
        rellenos.forEach((r, k) => gsap.set(r, { scaleX: k === j ? 1 : 0 }));
      })
    );
    return;
  }

  pausa.hidden = false;

  function construir(i) {
    const s = escenas[i];
    const q = (sel) => s.querySelectorAll(sel);
    const texto = s.querySelector('.bm__text');
    const arte = s.querySelector('.bm__art');
    const imagen = s.querySelector('.bm__art img');
    const palabras = q('.w__i');
    const fx = [...q('.bm__fx i')];
    const envoltura = s.querySelector('.bm__fx');
    const tipo = s.dataset.fx;

    gsap.set([texto, arte, envoltura], { clearProps: 'all' });
    gsap.set(texto, { autoAlpha: 1, y: 0 });

    const tl = gsap.timeline({ onComplete: () => ir((i + 1) % escenas.length) });

    // Progreso de la escena en su barra
    tl.fromTo(rellenos[i], { scaleX: 0 }, { scaleX: 1, duration: DURACION, ease: 'none' }, 0);

    // Texto: etiqueta, título palabra por palabra, descripción
    tl.fromTo(q('.bm__n'), { y: 14, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.5, ease: 'power3.out' }, 0.1);
    tl.fromTo(palabras, { yPercent: 118, rotate: 5 }, { yPercent: 0, rotate: 0, duration: 0.9, ease: 'power4.out', stagger: 0.07 }, 0.2);
    tl.fromTo(q('.bm__desc'), { y: 16, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.7, ease: 'power3.out' }, 0.55);

    // Ilustración: entra con rebote suave y flota
    const desdeX = tipo === 'veloz' ? -110 : 0;
    tl.fromTo(
      arte,
      { scale: 0.6, rotate: -10, x: desdeX, autoAlpha: 0 },
      { scale: 1, rotate: 0, x: 0, autoAlpha: 1, duration: 1, ease: tipo === 'veloz' ? 'power4.out' : 'back.out(1.6)' },
      0.15
    );
    tl.to(imagen, { y: -8, duration: 1.5, yoyo: true, repeat: 3, ease: 'sine.inOut' }, 1.1);

    // Efecto propio de cada escena
    if (tipo === 'orbita') {
      tl.fromTo(envoltura, { rotation: 0, autoAlpha: 0 }, { rotation: 360, autoAlpha: 1, duration: DURACION, ease: 'none' }, 0.2);
    } else if (tipo === 'veloz') {
      tl.fromTo(fx, { scaleX: 0, autoAlpha: 0.9 }, { scaleX: 1, autoAlpha: 0, duration: 0.7, ease: 'power2.out', stagger: 0.12, repeat: 3, repeatDelay: 0.35 }, 0.3);
    } else if (tipo === 'ping') {
      tl.fromTo(fx, { scale: 0.5, autoAlpha: 0.7 }, { scale: 2.1, autoAlpha: 0, duration: 1.4, ease: 'power1.out', stagger: 0.35, repeat: 2, repeatDelay: 0.1 }, 0.6);
    } else if (tipo === 'chispa') {
      tl.fromTo(
        fx,
        { x: 0, y: 0, scale: 0.4, autoAlpha: 1 },
        { x: (k) => CHISPAS[k % CHISPAS.length][0], y: (k) => CHISPAS[k % CHISPAS.length][1], scale: 1, autoAlpha: 0, duration: 1, ease: 'power2.out', stagger: 0.15, repeat: 2, repeatDelay: 0.4 },
        0.8
      );
    }

    // Salida
    tl.to(texto, { autoAlpha: 0, y: -14, duration: SALIDA - 0.1, ease: 'power2.in' }, DURACION - SALIDA);
    tl.to(arte, { autoAlpha: 0, scale: 0.9, duration: SALIDA - 0.1, ease: 'power2.in' }, DURACION - SALIDA);
    tl.set({}, {}, DURACION); // fija la duración total
    return tl;
  }

  function aplicarPausa() {
    if (linea) linea.paused(pausaUsuario || pausaTemporal);
  }

  function ir(i) {
    if (linea) linea.kill();
    marcar(i);
    rellenos.forEach((r, j) => gsap.set(r, { scaleX: j < i ? 1 : 0 }));
    gsap.to(fondos, { autoAlpha: (k) => (k === i ? 1 : 0), duration: 0.8, ease: 'power2.out', overwrite: true });
    linea = construir(i);
    aplicarPausa();
  }

  pasos.forEach((p, j) => p.addEventListener('click', () => ir(j)));

  pausa.addEventListener('click', () => {
    pausaUsuario = !pausaUsuario;
    pausa.setAttribute('aria-pressed', String(pausaUsuario));
    pausa.setAttribute('aria-label', pausaUsuario ? 'Reanudar animación' : 'Pausar animación');
    raiz.classList.toggle('is-pausado', pausaUsuario);
    aplicarPausa();
  });

  // Pausa temporal: puntero encima, foco dentro, pestaña oculta o banner fuera de pantalla.
  let encima = false;
  let dentro = false;
  let visible = true;
  let pestana = true;
  const evaluar = () => {
    pausaTemporal = encima || dentro || !visible || !pestana;
    aplicarPausa();
  };
  raiz.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') { encima = true; evaluar(); } });
  raiz.addEventListener('pointerleave', () => { encima = false; evaluar(); });
  raiz.addEventListener('focusin', () => { dentro = true; evaluar(); });
  raiz.addEventListener('focusout', () => { dentro = false; evaluar(); });
  document.addEventListener('visibilitychange', () => { pestana = !document.hidden; evaluar(); });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver((es) => { visible = es[es.length - 1].isIntersecting; evaluar(); }, { threshold: 0.1 }).observe(raiz);
  }

  ir(0);
}
