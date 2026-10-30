import gsap from 'gsap';

/*
  Banner de portada como un video corto de 15 s, en bucle, con una sola línea de tiempo:
    0.00  intro: el logo entra con un destello
    1.75  01 Comunidad     5.15  02 Entrega Express
    8.55  03 Medios de pago   11.95  04 Despacho inmediato
    14.35 cierre y vuelta al inicio
  Entre capítulos pasa una cortina de color; el contenido cambia justo cuando la cubre.
*/
const TOTAL = 15;
const INICIOS = [1.75, 5.15, 8.55, 11.95];
const DURACION = 3.4; // duración de cada capítulo
const CORTINA = 0.85; // recorrido de cada cortina
const ANTES = 0.55; // la cortina empieza esto antes del cambio

// Chispas de la escena "despacho": hacia dónde salta cada una.
const CHISPAS = [
  [-64, -46],
  [58, -58],
  [10, -78],
];

const limitar = (v) => Math.min(1, Math.max(0, v));

export function iniciar(raiz) {
  const escenas = [...raiz.querySelectorAll('.bm__scene')];
  const pasos = [...raiz.querySelectorAll('.bm__paso')];
  const rellenos = pasos.map((p) => p.querySelector('.bm__relleno'));
  const fondos = [...raiz.querySelectorAll('.bm__fondo')];
  const pausa = raiz.querySelector('.bm__pausa');
  const nav = raiz.querySelector('.bm__nav');
  const intro = raiz.querySelector('.bm__intro');
  const logo = raiz.querySelector('.bm__logo img');
  const brillo = raiz.querySelector('.bm__brillo');
  const cortinas = [...raiz.querySelectorAll('.bm__wipe')];
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

  function marcar(i) {
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

  // ---- Línea de tiempo maestra -------------------------------------------------
  const tl = gsap.timeline({ repeat: -1, paused: true, onUpdate: actualizarProgreso });

  // Estado inicial (también se reaplica en cada vuelta del bucle)
  tl.set(escenas, { autoAlpha: 0 }, 0);
  tl.set(cortinas, { xPercent: -150, skewX: -18, autoAlpha: 1 }, 0);
  tl.set(intro, { autoAlpha: 1 }, 0);
  tl.call(() => marcar(-1), null, 0);
  tl.set(fondos, { autoAlpha: 0 }, 0);

  // Intro
  tl.fromTo(logo, { scale: 0.78, autoAlpha: 0, y: 12 }, { scale: 1, autoAlpha: 1, y: 0, duration: 0.8, ease: 'back.out(1.5)' }, 0.15);
  tl.fromTo(brillo, { xPercent: -160 }, { xPercent: 160, duration: 0.75, ease: 'power2.inOut' }, 0.7);
  tl.to(logo, { scale: 1.06, autoAlpha: 0, duration: 0.3, ease: 'power2.in' }, INICIOS[0] - ANTES - 0.05);
  tl.set(intro, { autoAlpha: 0 }, INICIOS[0]);

  // Cortina de color: la ámbar abre paso, la azul la sigue; el cambio ocurre cuando la azul cubre todo.
  function cortina(inicio) {
    cortinas.forEach((c, k) => {
      const t = inicio + k * 0.12;
      tl.set(c, { xPercent: -150 }, t - 0.001);
      tl.to(c, { xPercent: 150, duration: CORTINA, ease: 'power3.inOut', immediateRender: false }, t);
    });
  }

  function escena(i) {
    const t0 = INICIOS[i];
    const s = escenas[i];
    const q = (sel) => s.querySelectorAll(sel);
    const texto = s.querySelector('.bm__text');
    const arte = s.querySelector('.bm__art');
    const imagen = s.querySelector('.bm__art img');
    const fantasma = s.querySelector('.bm__ghost');
    const palabras = q('.w__i');
    const fx = [...q('.bm__fx i')];
    const envoltura = s.querySelector('.bm__fx');
    const tipo = s.dataset.fx;
    const anterior = i === 0 ? null : escenas[i - 1];

    cortina(t0 - ANTES);
    if (anterior) tl.set(anterior, { autoAlpha: 0 }, t0);
    tl.set(s, { autoAlpha: 1 }, t0);
    tl.call(() => marcar(i), null, t0);
    tl.to(fondos, { autoAlpha: (k) => (k === i ? 1 : 0), duration: 0.01 }, t0);

    // Cámara lenta: la escena se acerca un poco mientras dura
    tl.fromTo(texto, { scale: 1 }, { scale: 1.035, transformOrigin: 'left center', duration: DURACION, ease: 'none' }, t0);

    // Número gigante de fondo (paralaje)
    tl.fromTo(fantasma, { x: 70, autoAlpha: 0 }, { x: -50, autoAlpha: 1, duration: DURACION, ease: 'none' }, t0);

    // Texto
    tl.fromTo(q('.bm__n'), { y: 14, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.5, ease: 'power3.out' }, t0 + 0.2);
    tl.fromTo(palabras, { yPercent: 118, rotate: 6, skewX: -8 }, { yPercent: 0, rotate: 0, skewX: 0, duration: 0.85, ease: 'power4.out', stagger: 0.07 }, t0 + 0.3);
    tl.fromTo(q('.bm__desc'), { y: 16, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.7, ease: 'power3.out' }, t0 + 0.7);

    // Ilustración
    tl.fromTo(
      arte,
      { scale: 0.55, rotate: -12, x: tipo === 'veloz' ? -120 : 0, autoAlpha: 0 },
      { scale: 1, rotate: 0, x: 0, autoAlpha: 1, duration: 1, ease: tipo === 'veloz' ? 'power4.out' : 'back.out(1.6)' },
      t0 + 0.3
    );
    tl.fromTo(imagen, { y: 0 }, { y: -8, duration: 1.4, yoyo: true, repeat: 1, ease: 'sine.inOut' }, t0 + 1.1);

    // Efecto propio de cada escena
    if (tipo === 'orbita') {
      tl.fromTo(envoltura, { rotation: 0, autoAlpha: 0 }, { rotation: 360, autoAlpha: 1, duration: DURACION, ease: 'none' }, t0 + 0.2);
    } else if (tipo === 'veloz') {
      tl.fromTo(fx, { scaleX: 0, autoAlpha: 0.9 }, { scaleX: 1, autoAlpha: 0, duration: 0.7, ease: 'power2.out', stagger: 0.12, repeat: 1, repeatDelay: 0.3 }, t0 + 0.4);
    } else if (tipo === 'ping') {
      tl.fromTo(fx, { scale: 0.5, autoAlpha: 0.7 }, { scale: 2.1, autoAlpha: 0, duration: 1, ease: 'power1.out', stagger: 0.35, repeat: 1 }, t0 + 0.8);
    } else if (tipo === 'chispa') {
      tl.fromTo(
        fx,
        { x: 0, y: 0, scale: 0.4, autoAlpha: 1 },
        { x: (k) => CHISPAS[k % CHISPAS.length][0], y: (k) => CHISPAS[k % CHISPAS.length][1], scale: 1, autoAlpha: 0, duration: 0.9, ease: 'power2.out', stagger: 0.15, repeat: 1, repeatDelay: 0.3 },
        t0 + 0.9
      );
    }
  }

  escenas.forEach((_, i) => escena(i));

  // Cierre: cortina final, se vacía el escenario y el video vuelve a empezar
  const cierre = INICIOS[3] + DURACION;
  cortina(cierre - ANTES);
  tl.set(escenas[3], { autoAlpha: 0 }, cierre);
  tl.call(() => marcar(-1), null, cierre);
  tl.set({}, {}, TOTAL); // fija la duración total

  // ---- Barra de progreso: un capítulo por barra --------------------------------
  function actualizarProgreso() {
    const t = tl.time();
    rellenos.forEach((r, k) => gsap.set(r, { scaleX: limitar((t - INICIOS[k]) / DURACION) }));
  }

  pasos.forEach((p, k) =>
    p.addEventListener('click', () => {
      tl.seek(INICIOS[k] + 0.45);
      aplicarPausa();
    })
  );

  // ---- Pausa -------------------------------------------------------------------
  let pausaUsuario = false;
  let pausaTemporal = false;
  function aplicarPausa() {
    tl.paused(pausaUsuario || pausaTemporal);
  }

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

  // Exponer el control para pruebas y para saltar a un momento concreto (?bm=7.5)
  raiz.__linea = tl;
  const salto = parseFloat(new URLSearchParams(location.search).get('bm'));
  if (!Number.isNaN(salto)) tl.seek(salto);
  aplicarPausa();
}
