// Cargador de islas: descarga el código de cada isla cuando su zona está cerca de la pantalla y el
// arranque de la página ya pasó. Así GSAP no compite con el primer pintado ni con la primera interacción.
const islas = {
  'banner-motion': () => import('./banner-motion.js'),
};

// Espera tras el evento load antes de arrancar las islas (si el usuario interactúa, arrancan de inmediato).
const ESPERA_MS = 5500;

function cuandoListo(fn) {
  let hecho = false;
  const eventos = ['pointerdown', 'keydown', 'touchstart', 'wheel'];
  const ir = () => {
    if (hecho) return;
    hecho = true;
    eventos.forEach((e) => removeEventListener(e, ir, { passive: true }));
    fn();
  };
  eventos.forEach((e) => addEventListener(e, ir, { passive: true }));
  const programar = () => setTimeout(() => ('requestIdleCallback' in window ? requestIdleCallback(ir, { timeout: 1000 }) : ir()), ESPERA_MS);
  if (document.readyState === 'complete') programar();
  else addEventListener('load', programar, { once: true });
}

function cargar(raiz) {
  const cargarIsla = islas[raiz.dataset.isla];
  if (cargarIsla) cargarIsla().then((m) => m.iniciar(raiz));
}

cuandoListo(() => {
  document.querySelectorAll('[data-isla]').forEach((raiz) => {
    if (!('IntersectionObserver' in window)) return cargar(raiz);
    const observador = new IntersectionObserver(
      (entradas) => {
        if (entradas.some((e) => e.isIntersecting)) {
          observador.disconnect();
          cargar(raiz);
        }
      },
      { rootMargin: '300px' }
    );
    observador.observe(raiz);
  });
});
